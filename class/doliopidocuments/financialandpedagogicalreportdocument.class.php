<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/doliopidocuments/financialandpedagogicalreportdocument.class.php
 * \ingroup doliopi
 * \brief   This file is a class file for FinancialAndPedagogicalReportDocument
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedocuments.class.php';

/**
 * Class for FinancialAndPedagogicalReportDocument
 */
class FinancialAndPedagogicalReportDocument extends SaturneDocuments
{
    /**
     * @var string Module name
     */
    public $module = 'doliopi';

    /**
     * @var string Element type of object
     */
    public $element = 'financialandpedagogicalreportdocument';

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Load dashboard info
     *
     * @return array
     * @throws Exception
     */
    public function loadDashboard(): array
    {
        $array    = [];
        $BPFParts = ['A', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G', 'H', 'Footer'];
        $BPFInfos = self::loadBPFInfos();

        foreach ($BPFParts as $part) {
            $getBPFPart = 'getBPFPart' . $part;
            if (in_array($part, ['B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G'])) {
                $array['widgets'][] = self::$getBPFPart($BPFInfos);
            } else {
                $array['widgets'][] = self::$getBPFPart();
            }
        }

        return $array;
    }

    /**
     * Load BPF infos (formation services, invoices, etc.)
     *
     * @return array
     * @throws Exception
     */
    public function loadBPFInfos(): array
    {
        global $conf;

        // Protect against direct access due to the use of this class in the dashboard
        if (!isModEnabled('product') || !isModEnabled('invoice')) {
            return [];
        }

        // Load Dolibarr libraries
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

        // Load Saturne libraries
        require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

        // load Doliopi libraries
        require_once __DIR__ . '/../trainingsession.class.php';

        // Initialize technical objects
        $trainingSession = new TrainingSession($this->db);
        $signatory       = new SaturneSignature($this->db, 'doliopi', $trainingSession->element);

        // Load BPF subcategories
        $BPFSubTagsC = json_decode(getDolGlobalString('DOLIOPI_FINANCIAL_AND_PEDAGOGICAL_REPORT_SUBCATEGORIES_ID'), true);
        if (empty($BPFSubTagsC)) {
            return [];
        }
        $BPFSubTagsC = array_merge($BPFSubTagsC, $BPFSubTagsC['C1bis'][0]);
        unset($BPFSubTagsC['C1bis']);

        $formationServices = [];
        foreach ($BPFSubTagsC as $BPFSubTag) {
            // Load formation services
            $filter   = ['customsql' => 't.fk_product_type = 1 AND t.entity = ' . $conf->entity . ' AND cp.fk_categorie IN (' . $BPFSubTag . ')'];
            $products = saturne_fetch_all_object_type('Product', '', '', 0, 0, $filter, 'AND', false, true, true);
            if (!is_array($products) || empty($products)) {
                continue;
            }
            $formationServices[$BPFSubTag] = array_column($products, 'id', 'id');
        }

        // Load invoices
        $year                 = GETPOSTISSET('search_year') ? GETPOSTINT('search_year') : date('Y');
        $firstDayOfFiscalYear = dol_get_first_day($year, getDolGlobalString('SOCIETE_FISCAL_MONTH_START'));
        $lastDayOfFiscalYear  = dol_time_plus_duree($firstDayOfFiscalYear, 1, 'y');
        $lastDayOfFiscalYear  = dol_time_plus_duree($lastDayOfFiscalYear, -1, 'd');

        $filter   = ['customsql' => 't.fk_statut IN (' . Facture::STATUS_VALIDATED . ',' . Facture::STATUS_CLOSED . ') AND t.datef BETWEEN ' . "'" . dol_print_date($firstDayOfFiscalYear, 'dayrfc') . "'" . ' AND ' . "'" . dol_print_date($lastDayOfFiscalYear, 'dayrfc') . "'"];
        $invoices = saturne_fetch_all_object_type('Facture', '', '', 0, 0, $filter);
        if (!is_array($invoices) || empty($invoices)) {
            return [];
        }

        $BPFInfos      = ['sales' => 0];
        $BPFInfosByTag = [];
        foreach ($BPFSubTagsC as $BPFSubTag => $BPFSubTagID) {
            $BPFInfosByTag[$BPFSubTag] = [
                'totalHT'                  => 0,
                'NbTrainees'               => 0,
                'TrainingSessionDurations' => 0
            ];
        }

        // Per-training-type accumulators, used by Part F3.
        // Refs match llx_c_trainingsession_type seed values.
        $BPFInfosByTrainingType = [
            'BilanCompetences'             => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'ActionVAE'                    => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'ActionFormation'              => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'ActionFormationApprentissage' => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0]
        ];
        $countedSessionIds = [];

        foreach ($invoices as $invoice) {
            $invoice->fetch_lines();
            if (!is_array($invoice->lines) || empty($invoice->lines)) {
                continue;
            }
            $BPFInfos['sales'] += $invoice->total_ht;

            foreach ($BPFSubTagsC as $BPFSubTag => $BPFSubTagID) {
                if (!is_array($formationServices[$BPFSubTagID]) || empty($formationServices[$BPFSubTagID])) {
                    continue;
                }

                foreach ($invoice->lines as $line) {
                    if (!in_array($line->fk_product, $formationServices[$BPFSubTagID])) {
                        continue;
                    }
                    $BPFInfosByTag[$BPFSubTag]['totalHT'] += $line->total_ht;

                    // Load invoice linked objects for get propal
                    $invoice->fetchObjectLinked(null, 'propal', $invoice->id, $invoice->element);
                    if (!is_array($invoice->linkedObjects) || empty($invoice->linkedObjectsIds)) {
                        continue;
                    }
                    $propal = current($invoice->linkedObjects['propal']);

                    // Load propal linked objects for get contract
                    $propal->fetchObjectLinked($propal->id, $propal->element, null, 'contrat', 'OR', 1, 'sourcetype', 0);
                    if (!is_array($propal->linkedObjectsIds) || empty($propal->linkedObjectsIds)) {
                        continue;
                    }
                    $contractID = current($propal->linkedObjectsIds['contrat']);

                    // Load training sessions from contract
                    $filter           = ['customsql' => 't.fk_contrat = ' . $contractID . ' AND t.status IN (' . Session::STATUS_VALIDATED . ',' . Session::STATUS_LOCKED . ') AND t.date_start BETWEEN ' . "'" . dol_print_date($firstDayOfFiscalYear, 'dayrfc') . "'" . ' AND ' . "'" . dol_print_date($lastDayOfFiscalYear, 'dayrfc') . "'"];
                    $trainingSessions = $trainingSession->fetchAll('', '', 0, 0, $filter);
                    if (!is_array($trainingSessions) || empty($trainingSessions)) {
                        continue;
                    }

                    foreach ($trainingSessions as $trainingSession) {
                        $signatories = $signatory->fetchSignatories($trainingSession->id, $trainingSession->element);
                        if (!is_array($signatories) || empty($signatories)) {
                            continue;
                        }

                        $nbTraineesSession = 0;
                        foreach ($signatories as $sig) {
                            if ($sig->role == 'Trainee') {
                                $nbTraineesSession++;
                            }
                        }

                        $BPFInfosByTag[$BPFSubTag]['NbTrainees']               += $nbTraineesSession;
                        $BPFInfosByTag[$BPFSubTag]['TrainingSessionDurations'] += $nbTraineesSession * $trainingSession->duration;

                        // Tally by training session type once per session, even if traversed via multiple invoices/lines.
                        if (!isset($countedSessionIds[$trainingSession->id])) {
                            $trainingSession->fetch_optionals();
                            $typeID    = $trainingSession->array_options['options_trainingsession_type'] ?? 0;
                            $typeRef   = $this->getTrainingSessionTypeRef((int) $typeID);
                            if (isset($BPFInfosByTrainingType[$typeRef])) {
                                $BPFInfosByTrainingType[$typeRef]['NbTrainees']               += $nbTraineesSession;
                                $BPFInfosByTrainingType[$typeRef]['TrainingSessionDurations'] += $nbTraineesSession * $trainingSession->duration;
                            }
                            $countedSessionIds[$trainingSession->id] = true;
                        }
                    }
                }
            }
        }

        return [
            'BPFInfos'               => array_merge($BPFInfos, $BPFInfosByTag),
            'firstDayOfFiscalYear'   => $firstDayOfFiscalYear,
            'lastDayOfFiscalYear'    => $lastDayOfFiscalYear,
            'BPFPartDAmounts'        => $this->loadBPFPartDAmounts($firstDayOfFiscalYear, $lastDayOfFiscalYear),
            'BPFPartETrainers'       => $this->loadBPFPartETrainers($firstDayOfFiscalYear, $lastDayOfFiscalYear),
            'BPFInfosByTrainingType' => $BPFInfosByTrainingType
        ];
    }

    /**
     * Resolve a training session type rowid to its ref string.
     *
     * @param  int    $typeID Row id from llx_c_trainingsession_type, 0 if unset.
     * @return string         Ref ('ActionFormation', 'BilanCompetences', 'ActionVAE',
     *                        'ActionFormationApprentissage'), empty if not found.
     */
    private function getTrainingSessionTypeRef(int $typeID): string
    {
        if ($typeID <= 0) {
            return '';
        }

        $sql    = 'SELECT ref FROM ' . MAIN_DB_PREFIX . 'c_trainingsession_type WHERE rowid = ' . $typeID;
        $resql  = $this->db->query($sql);
        if (!$resql) {
            return '';
        }
        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        return $obj ? $obj->ref : '';
    }

    /**
     * Load BPF Part D amounts (charges) from the accounting bookkeeping.
     * Total      = all class 6 accounts (charges)
     * Salaries   = accounts starting with 6411 (rémunérations du personnel)
     * Purchases  = accounts starting with 604 (achats d'études et prestations) or 6226 (honoraires)
     *
     * @param  int   $firstDay First day of fiscal year (timestamp).
     * @param  int   $lastDay  Last day of fiscal year (timestamp).
     * @return array           ['total' => float, 'salaries' => float, 'purchases' => float].
     */
    public function loadBPFPartDAmounts(int $firstDay, int $lastDay): array
    {
        global $conf;

        $amounts = ['total' => 0, 'salaries' => 0, 'purchases' => 0];

        if (!isModEnabled('accounting')) {
            return $amounts;
        }

        $sql  = 'SELECT';
        $sql .= ' SUM(CASE WHEN numero_compte LIKE \'6%\' THEN debit - credit ELSE 0 END) AS total,';
        $sql .= ' SUM(CASE WHEN numero_compte LIKE \'6411%\' THEN debit - credit ELSE 0 END) AS salaries,';
        $sql .= ' SUM(CASE WHEN numero_compte LIKE \'604%\' OR numero_compte LIKE \'6226%\' THEN debit - credit ELSE 0 END) AS purchases';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'accounting_bookkeeping';
        $sql .= ' WHERE entity = ' . ((int) $conf->entity);
        $sql .= ' AND doc_date BETWEEN \'' . $this->db->idate($firstDay) . '\' AND \'' . $this->db->idate($lastDay) . '\'';

        $resql = $this->db->query($sql);
        if (!$resql) {
            return $amounts;
        }

        $obj = $this->db->fetch_object($resql);
        if ($obj) {
            $amounts['total']     = (float) $obj->total;
            $amounts['salaries']  = (float) $obj->salaries;
            $amounts['purchases'] = (float) $obj->purchases;
        }
        $this->db->free($resql);

        return $amounts;
    }

    public function getBPFPartA(): array
    {
        global $langs, $mysoc;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartA');
        $array['picto']      = 'fas fa-info';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [
            $langs->transnoentities('TrainingOrganizationNumber'),
            $langs->transnoentities('JuridicalStatus'),
            $langs->transnoentities('ProfId2ShortFR'),
            $langs->transnoentities('ProfId3ShortFR'),
            $langs->transnoentities('CompanyName'),
            $langs->transnoentities('Address'),
            $langs->transnoentities('Phone'),
            $langs->transnoentities('BPFAddressPublic'),
            $langs->transnoentities('Email')
        ];

        $array['content'] = [
            getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'),
            getFormeJuridiqueLabel($mysoc->forme_juridique_code),
            $mysoc->idprof2,
            $mysoc->idprof3,
            $mysoc->name,
            $mysoc->address,
            $mysoc->phone,
            $langs->transnoentities(getDolGlobalInt('DOLIOPI_BPF_ADDRESS_PUBLIC') ? 'Yes' : 'No'),
            $mysoc->email
        ];

        return $array;
    }

    public function getBPFPartB(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartB');
        $array['picto']      = 'fas fa-info';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [
            $langs->transnoentities('FiscalYearInformation'),
            $langs->transnoentities('BPFRemoteTraining')
        ];

        $array['content'] = [
            dol_print_date($BPFInfos['firstDayOfFiscalYear'], 'day') . ' - ' . dol_print_date($BPFInfos['lastDayOfFiscalYear'], 'day'),
            $langs->transnoentities(getDolGlobalInt('DOLIOPI_BPF_REMOTE_TRAINING') ? 'Yes' : 'No')
        ];

        return $array;
    }

    public function getBPFPartC(array $BPFInfos): array
    {
        global $conf, $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartC');
        $array['picto']      = 'fas fa-file-invoice-dollar';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $BPFTotalPartC   = 0;
        $BPFLabelsPartC = ['C1', 'C1a', 'C1b', 'C1c', 'C1d', 'C1e', 'C1f', 'C1g', 'C1h', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10', 'C11'];
        foreach ($BPFLabelsPartC as $BPFLabelPartC) {
            $array['label'][] = $langs->transnoentities('BPFTag' . $BPFLabelPartC . 'Description');
            if ($BPFLabelPartC == 'C2') {
                $totalHT            = 0;
                $BPFLabelsPartC1bis = ['C1a', 'C1b', 'C1c', 'C1d', 'C1e', 'C1f', 'C1g', 'C1h'];
                foreach ($BPFLabelsPartC1bis as $BPFLabelPartC1bis) {
                    $totalHT += $BPFInfos['BPFInfos'][$BPFLabelPartC1bis]['totalHT'];
                }
                $array['content'][] = round($totalHT) . ' ' . $langs->getCurrencySymbol($conf->currency);
            } else {
                $BPFTotalPartC     += $BPFInfos['BPFInfos'][$BPFLabelPartC]['totalHT'];
                $array['content'][] = round($BPFInfos['BPFInfos'][$BPFLabelPartC]['totalHT']) . ' ' . $langs->getCurrencySymbol($conf->currency);
            }
        }

        $array['label'][] = $langs->transnoentities('BPFTotalPartC');
        $array['label'][] = $langs->transnoentities('BPFCAPartC');

        $array['content'][] = round($BPFTotalPartC) . ' ' . $langs->getCurrencySymbol($conf->currency);

        $salesPercent = $langs->trans('NoData');
        if ($BPFTotalPartC > 0 && $BPFInfos['BPFInfos']['sales'] > 0) {
            $salesPercent = ceil($BPFTotalPartC / $BPFInfos['BPFInfos']['sales'] * 100) . ' %';
        }
        $array['content'][] = $salesPercent;

        return $array;
    }

    public function getBPFPartD(array $BPFInfos): array
    {
        global $conf, $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartD');
        $array['picto']      = 'fas fa-file-invoice-dollar';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $amounts        = $BPFInfos['BPFPartDAmounts'] ?? ['total' => 0, 'salaries' => 0, 'purchases' => 0];
        $currencySymbol = $langs->getCurrencySymbol($conf->currency);

        $array['label'] = [
            $langs->transnoentities('BPFLabelPartD1'),
            $langs->transnoentities('BPFLabelPartD1a'),
            $langs->transnoentities('BPFLabelPartD1b')
        ];

        $array['content'] = [
            round($amounts['total']) . ' ' . $currencySymbol,
            round($amounts['salaries']) . ' ' . $currencySymbol,
            round($amounts['purchases']) . ' ' . $currencySymbol
        ];

        return $array;
    }

    /**
     * Load BPF Part E trainer counts and dispensed hours over the fiscal period.
     * Internal = signatories with element_type 'user' (Dolibarr users / employees).
     * External = signatories with element_type 'socpeople' (third-party contacts / subcontractors).
     * Each trainer is counted once; hours are summed per (trainer, session) so two trainers
     * on a 6h session counts as 12 trainer-hours total.
     *
     * @param  int   $firstDay First day of fiscal year (timestamp).
     * @param  int   $lastDay  Last day of fiscal year (timestamp).
     * @return array           ['internal' => ['count' => int, 'hours' => int], 'external' => [...]].
     */
    public function loadBPFPartETrainers(int $firstDay, int $lastDay): array
    {
        // Load Saturne libraries
        require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

        // load Doliopi libraries
        require_once __DIR__ . '/../trainingsession.class.php';

        $result = [
            'internal' => ['count' => 0, 'hours' => 0],
            'external' => ['count' => 0, 'hours' => 0]
        ];

        $trainingSession = new TrainingSession($this->db);
        $signatory       = new SaturneSignature($this->db, 'doliopi', $trainingSession->element);

        $filter           = ['customsql' => 't.status IN (' . Session::STATUS_VALIDATED . ',' . Session::STATUS_LOCKED . ') AND t.date_start BETWEEN \'' . dol_print_date($firstDay, 'dayrfc') . '\' AND \'' . dol_print_date($lastDay, 'dayrfc') . '\''];
        $trainingSessions = $trainingSession->fetchAll('', '', 0, 0, $filter);
        if (!is_array($trainingSessions) || empty($trainingSessions)) {
            return $result;
        }

        $internalIds = [];
        $externalIds = [];
        foreach ($trainingSessions as $session) {
            $signatories = $signatory->fetchSignatories($session->id, $session->element);
            if (!is_array($signatories) || empty($signatories)) {
                continue;
            }

            foreach ($signatories as $sig) {
                if ($sig->role != 'SessionTrainer') {
                    continue;
                }

                if ($sig->element_type == 'user') {
                    $internalIds[$sig->element_id] = true;
                    $result['internal']['hours']  += $session->duration;
                } elseif ($sig->element_type == 'socpeople') {
                    $externalIds[$sig->element_id] = true;
                    $result['external']['hours']  += $session->duration;
                }
            }
        }

        $result['internal']['count'] = count($internalIds);
        $result['external']['count'] = count($externalIds);

        return $result;
    }

    public function getBPFPartE(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartE');
        $array['picto']      = 'fas fa-chalkboard-teacher';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $trainers = $BPFInfos['BPFPartETrainers'] ?? [
            'internal' => ['count' => 0, 'hours' => 0],
            'external' => ['count' => 0, 'hours' => 0]
        ];

        $array['label'] = [
            $langs->transnoentities('BPFLabelPartEInternal'),
            $langs->transnoentities('BPFLabelPartEExternal')
        ];

        $array['content'] = [
            img_picto('', 'fa-chalkboard-teacher', 'class="pictofixedwidth"') . $trainers['internal']['count'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime((int) $trainers['internal']['hours'], 'allhour') . ' H',
            img_picto('', 'fa-chalkboard-teacher', 'class="pictofixedwidth"') . $trainers['external']['count'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime((int) $trainers['external']['hours'], 'allhour') . ' H'
        ];

        return $array;
    }

    /**
     * @throws Exception
     */
    public function getBPFPartF1(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartF1');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartF1';
        $array['widgetName'] = 'BPFTitlePartF1';

        // Widget parameters
        [$totalNbTrainees , $totalTrainingSessionDurations] = [0, 0];
        $BPFLabelsPartF1 = [
            'F1a' => 'C1', 'F1b' => 'C1a', 'F1c' => 'C1f', 'F1d' => 'C9',
            'F1e' => ['C1b', 'C1c', 'C1d', 'C1e', 'C1g', 'C1h', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C11'],
            'F1'  => 'F1'
        ];
        foreach ($BPFLabelsPartF1 as $BPFLabelPartF1 => $BPFLabelPartC) {
            $array['label'][] = $langs->transnoentities('BPFLabelPart' . $BPFLabelPartF1);
            if ($BPFLabelPartF1 == 'F1e') {
                [$totalNbTraineesPartF1e , $totalTrainingSessionDurationsPartF1e] = [0, 0];
                foreach ($BPFLabelPartC as $BPFLabelPartF1e) {
                    $totalNbTraineesPartF1e               += $BPFInfos['BPFInfos'][$BPFLabelPartF1e]['NbTrainees'];
                    $totalTrainingSessionDurationsPartF1e += $BPFInfos['BPFInfos'][$BPFLabelPartF1e]['TrainingSessionDurations'];
                }
                $totalNbTrainees               += $totalNbTraineesPartF1e;
                $totalTrainingSessionDurations += $totalTrainingSessionDurationsPartF1e;
                $array['content'][]             = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNbTraineesPartF1e . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime($totalTrainingSessionDurationsPartF1e, 'allhour') . ' H';
            } elseif ($BPFLabelPartF1 == 'F1') {
                $array['content'][] = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNbTrainees . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime($totalTrainingSessionDurations, 'allhour') . ' H';
            } else {
                $totalNbTrainees               += $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'];
                $totalTrainingSessionDurations += $BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations'];
                $array['content'][]             = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime($BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations'], 'allhour') . ' H';
            }
        }

        return $array;
    }

    public function getBPFPartF2(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartF2');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartF2';
        $array['widgetName'] = 'BPFTitlePartF2';

        // No reliable signal in doliopi to detect sub-contracted sessions; render placeholders
        // that the user fills manually on the official BPF portal.
        $array['label']   = [$langs->transnoentities('BPFLabelPartF2')];
        $array['content'] = [img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . '0 - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . '0 H'];

        return $array;
    }

    public function getBPFPartF3(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartF3');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartF3';
        $array['widgetName'] = 'BPFTitlePartF3';

        $byType = $BPFInfos['BPFInfosByTrainingType'] ?? [];
        $bilan  = $byType['BilanCompetences']             ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];
        $vae    = $byType['ActionVAE']                    ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];
        $other1 = $byType['ActionFormation']              ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];
        $other2 = $byType['ActionFormationApprentissage'] ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];

        // F3a (RNCP), F3b (RS) and F3c (CQP non-enregistré) require certification metadata
        // not stored in doliopi — they stay at zero until extrafields are added.
        // RNCP sub-levels (a) match the breakdown displayed on the official Cerfa.
        $lines = [
            'F3a'        => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3aLevel68' => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3aLevel5'  => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3aLevel4'  => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3aLevel3'  => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3aLevel2'  => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3aCQP'     => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3b'        => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3c'        => ['NbTrainees' => 0, 'TrainingSessionDurations' => 0],
            'F3d'        => [
                'NbTrainees'               => $other1['NbTrainees'] + $other2['NbTrainees'],
                'TrainingSessionDurations' => $other1['TrainingSessionDurations'] + $other2['TrainingSessionDurations']
            ],
            'F3e'        => $bilan,
            'F3f'        => $vae
        ];

        // Lines that count toward the F3 total — RNCP sub-levels are NOT summed
        // (they break down F3a, which would double-count).
        $totalKeys = ['F3a', 'F3b', 'F3c', 'F3d', 'F3e', 'F3f'];

        [$totalNb, $totalDuration] = [0, 0];
        foreach ($lines as $key => $line) {
            $array['label'][]   = $langs->transnoentities('BPFLabelPart' . $key);
            $array['content'][] = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $line['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime((int) $line['TrainingSessionDurations'], 'allhour') . ' H';
            if (in_array($key, $totalKeys, true)) {
                $totalNb       += $line['NbTrainees'];
                $totalDuration += $line['TrainingSessionDurations'];
            }
        }

        $array['label'][]   = $langs->transnoentities('BPFLabelPartF3');
        $array['content'][] = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNb . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime((int) $totalDuration, 'allhour') . ' H';

        return $array;
    }

    public function getBPFPartF4(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartF4');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartF4';
        $array['widgetName'] = 'BPFTitlePartF4';

        // F4 ventilates F1 by NSF specialty code (annex page 4 of the notice). doliopi has no
        // such field on sessions/products, so the 5 specialty rows stay empty and everything
        // is bucketed into "Autres spécialités". Total mirrors F1.
        [$totalNb, $totalDuration] = [0, 0];
        $excludedTags = ['C2', 'C10']; // C2 = Σ(C1a-h), C10 = G (sub-contractor for someone else)
        foreach ($BPFInfos['BPFInfos'] ?? [] as $tag => $values) {
            if (!is_array($values) || in_array($tag, $excludedTags, true) || !isset($values['NbTrainees'])) {
                continue;
            }
            $totalNb       += $values['NbTrainees'];
            $totalDuration += $values['TrainingSessionDurations'];
        }

        $emptyCell = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . '0 - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . '0 H';
        $totalCell = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNb . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime((int) $totalDuration, 'allhour') . ' H';

        $array['label']   = [];
        $array['content'] = [];
        for ($i = 1; $i <= 5; $i++) {
            $array['label'][]   = $langs->transnoentities('BPFLabelPartF4Specialty', $i);
            $array['content'][] = $emptyCell;
        }
        $array['label'][]   = $langs->transnoentities('BPFLabelPartF4Other');
        $array['content'][] = $totalCell;
        $array['label'][]   = $langs->transnoentities('BPFLabelPartF4');
        $array['content'][] = $totalCell;

        return $array;
    }

    public function getBPFPartG(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartG');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartG';
        $array['widgetName'] = 'BPFTitlePartG';

        $c10 = $BPFInfos['BPFInfos']['C10'] ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];

        $array['label']   = [$langs->transnoentities('BPFLabelPartG5')];
        $array['content'] = [img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $c10['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime((int) $c10['TrainingSessionDurations'], 'allhour') . ' H'];

        return $array;
    }

    public function getBPFPartH(): array
    {
        global $langs, $mysoc;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartH');
        $array['picto']      = 'fas fa-user-tie';
        $array['name']       = 'BPFTitlePartH';
        $array['widgetName'] = 'BPFTitlePartH';

        $array['label'] = [
            $langs->transnoentities('NameAndFirstName'),
            $langs->transnoentities('ManagementStatus')
        ];

        $array['content'] = [
            $mysoc->managers,
            getDolGlobalString('DOLIOPI_BPF_MANAGER_STATUS')
        ];

        return $array;
    }

    public function getBPFPartFooter(): array
    {
        global $langs, $mysoc, $user;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartFooter');
        $array['picto']      = 'fas fa-info';
        $array['name']       = 'BPFTitlePartFooter';
        $array['widgetName'] = 'BPFTitlePartFooter';

        $array['label'] = [
            $langs->transnoentities('À'),
            $langs->transnoentities('The'),
            $langs->transnoentities('NameAndPositionOfSignatory'),
            $langs->transnoentities('Email'),
            $langs->transnoentities('Phone'),
            $langs->transnoentities('Signature')
        ];

        $array['content'] = [
            $mysoc->town,
            dol_print_date(dol_now('tzuser'), 'dayhour'),
            $user->getFullName($langs) . ' - ' . $user->job,
            $user->email,
            $user->user_mobile,
            ''
        ];

        return $array;
    }
}

