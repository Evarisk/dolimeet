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
 * \file    class/dolimeetdocuments/financialandpedagogicalreportdocument.class.php
 * \ingroup dolimeet
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
    public $module = 'dolimeet';

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
            if (in_array($part, ['B', 'C', 'F1', 'F2', 'F3', 'F4', 'G'])) {
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

        // load DoliMeet libraries
        require_once __DIR__ . '/../trainingsession.class.php';

        // Initialize technical objects
        $trainingSession = new TrainingSession($this->db);
        $signatory       = new SaturneSignature($this->db, 'dolimeet', $trainingSession->element);

        // Load BPF subcategories
        $BPFSubTagsC = json_decode(getDolGlobalString('DOLIMEET_FINANCIAL_AND_PEDAGOGICAL_REPORT_SUBCATEGORIES_ID'), true);
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

        $BPFInfos                                          = ['sales' => 0];
        $BPFInfosByTag                                     = [];
        [$totalHT, $NbTrainees, $TrainingSessionDurations] = [0, 0, 0];
        foreach ($invoices as $invoice) {
            $invoice->fetch_lines();
            if (!is_array($invoice->lines) || empty($invoice->lines)) {
                continue;
            }
            $BPFInfos['sales'] += $invoice->total_ht;

            foreach ($BPFSubTagsC as $BPFSubTag => $BPFSubTagID) {
                if (!is_array($formationServices[$BPFSubTagID]) || empty($formationServices[$BPFSubTagID])) {
                    $BPFInfosByTag[$BPFSubTag] = [
                        'totalHT'                  => 0,
                        'NbTrainees'               => 0,
                        'TrainingSessionDurations' => 0
                    ];
                    continue;
                }

                foreach ($invoice->lines as $line) {
                    if (!in_array($line->fk_product, $formationServices[$BPFSubTagID])) {
                        continue;
                    }
                    $totalHT += $line->total_ht;

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
                        // Load trainee signatories
                        $signatories = $signatory->fetchSignatories($trainingSession->id, $trainingSession->element);
                        if (!is_array($signatories) || empty($signatories)) {
                            continue;
                        }
                        $NbTrainees               += count($signatories);
                        $TrainingSessionDurations += count($signatories) * $trainingSession->duration;

                        foreach ($signatories as $signatory) {
                            if ($signatory->role  != 'SessionTrainer' || $signatory->element_type  != 'socpeople') {
                                continue;
                            }

                            $abd  += count($signatories);
                            $abdc += count($signatories) * $trainingSession->duration;
                        }
                    }
                }

                $BPFInfosByTag[$BPFSubTag] = [
                    'totalHT'                  => $totalHT,
                    'NbTrainees'               => $NbTrainees,
                    'TrainingSessionDurations' => $TrainingSessionDurations,
                    'abd' => $abd,
                    'abdc' => $abdc,
                ];
            }
        }

        return [
            'BPFInfos'             => array_merge($BPFInfos, $BPFInfosByTag),
            'firstDayOfFiscalYear' => $firstDayOfFiscalYear,
            'lastDayOfFiscalYear'  => $lastDayOfFiscalYear
        ];
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
            $langs->transnoentities('FiscalYearInformation')
        ];

        $array['content'] = [
            dol_print_date($BPFInfos['firstDayOfFiscalYear'], 'day') . ' - ' . dol_print_date($BPFInfos['lastDayOfFiscalYear'], 'day')
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

    public function getBPFPartD()
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartD');
        $array['picto']      = 'fas fa-file-invoice-dollar';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [];

        $array['content'] = [];

        return $array;
    }

    public function getBPFPartE()
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartE');
        $array['picto']      = 'fas fa-file-invoice-dollar';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [
            $langs->transnoentities('FiscalYearInformation')
        ];

        $array['content'] = [];

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
        $array['title']      = $langs->transnoentities('getBPFPartF2');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'getBPFPartF2';
        $array['widgetName'] = 'getBPFPartF2';

        // Widget parameters
        $array['label']   = [$langs->transnoentities('BPFLabelPartF2')];
        $array['content'] = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . ($BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] * convertSecondToTime($BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations'], 'allhour')) . ' H';

        return $array;
    }

    public function getBPFPartF3(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('getBPFPartF3');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartF1';
        $array['widgetName'] = 'BPFTitlePartF1';

        // Widget parameters
        [$totalNbTrainees , $totalTrainingSessionDurations] = [0, 0];
        $BPFLabelsPartF1 = [
            'F1a' => 'C1', 'F1b' => 'C1a', 'F1c' => 'C1f', 'F1d' => 'C9',
            'F1e' => ['C1b', 'C1c', 'C1d', 'C1e', 'C1g', 'C1h', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C11'],
            'F1' => 'F1'
        ];
        foreach ($BPFLabelsPartF1 as $BPFLabelPartF1 => $BPFLabelPartC) {
            $array['label'][] = $langs->transnoentities('BPFLabelPart' . $BPFLabelPartF1);
            if ($BPFLabelPartF1 == 'F1e') {
                [$totalNbTraineesPartF1e , $totalTrainingSessionDurationsPartF1e] = [0, 0];
                foreach ($BPFLabelPartC as $BPFLabelPartF1e) {
                    $totalNbTraineesPartF1e              += $BPFInfos['BPFInfos'][$BPFLabelPartF1e]['NbTrainees'];
                    $totalTrainingSessionDurationsPartF1e += $BPFInfos['BPFInfos'][$BPFLabelPartF1e]['TrainingSessionDurations'];
                }
                $totalNbTrainees               += $totalNbTraineesPartF1e;
                $totalTrainingSessionDurations += $totalTrainingSessionDurationsPartF1e;
                $array['content'][]             = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNbTraineesPartF1e . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . ($totalNbTraineesPartF1e * convertSecondToTime($totalTrainingSessionDurationsPartF1e, 'allhour')) . ' H';
            } elseif ($BPFLabelPartF1 == 'F1') {
                $array['content'][] = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNbTrainees . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime($totalTrainingSessionDurations, 'allhour') . ' H';
            } else {
                $totalNbTrainees               += $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'];
                $totalTrainingSessionDurations += ($BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] * $BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations']);
                $array['content'][]             = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . ($BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] * convertSecondToTime($BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations'], 'allhour')) . ' H';
            }
        }

        return $array;
    }

    public function getBPFPartF4(array $BPFInfos): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('getBPFPartF4');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartF1';
        $array['widgetName'] = 'BPFTitlePartF1';

        // Widget parameters
        [$totalNbTrainees , $totalTrainingSessionDurations] = [0, 0];
        $BPFLabelsPartF1 = [
            'F1a' => 'C1', 'F1b' => 'C1a', 'F1c' => 'C1f', 'F1d' => 'C9',
            'F1e' => ['C1b', 'C1c', 'C1d', 'C1e', 'C1g', 'C1h', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C11'],
            'F1' => 'F1'
        ];
        foreach ($BPFLabelsPartF1 as $BPFLabelPartF1 => $BPFLabelPartC) {
            $array['label'][] = $langs->transnoentities('BPFLabelPart' . $BPFLabelPartF1);
            if ($BPFLabelPartF1 == 'F1e') {
                [$totalNbTraineesPartF1e , $totalTrainingSessionDurationsPartF1e] = [0, 0];
                foreach ($BPFLabelPartC as $BPFLabelPartF1e) {
                    $totalNbTraineesPartF1e              += $BPFInfos['BPFInfos'][$BPFLabelPartF1e]['NbTrainees'];
                    $totalTrainingSessionDurationsPartF1e += $BPFInfos['BPFInfos'][$BPFLabelPartF1e]['TrainingSessionDurations'];
                }
                $totalNbTrainees               += $totalNbTraineesPartF1e;
                $totalTrainingSessionDurations += $totalTrainingSessionDurationsPartF1e;
                $array['content'][]             = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNbTraineesPartF1e . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . ($totalNbTraineesPartF1e * convertSecondToTime($totalTrainingSessionDurationsPartF1e, 'allhour')) . ' H';
            } elseif ($BPFLabelPartF1 == 'F1') {
                $array['content'][] = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $totalNbTrainees . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . convertSecondToTime($totalTrainingSessionDurations, 'allhour') . ' H';
            } else {
                $totalNbTrainees               += $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'];
                $totalTrainingSessionDurations += ($BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] * $BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations']);
                $array['content'][]             = img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . ($BPFInfos['BPFInfos'][$BPFLabelPartC]['NbTrainees'] * convertSecondToTime($BPFInfos['BPFInfos'][$BPFLabelPartC]['TrainingSessionDurations'], 'allhour')) . ' H';
            }
        }

        return $array;
    }

    public function getBPFPartG(array $BPFInfos)
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartG');
        $array['picto']      = 'fas fa-user-graduate';
        $array['name']       = 'BPFTitlePartG';
        $array['widgetName'] = 'BPFTitlePartG';

        // Widget parameters
        $array['label']   = [$langs->transnoentities('BPFLabelPartG5')];
        $array['content'] = [img_picto('', 'fa-user-graduate', 'class="pictofixedwidth"') . $BPFInfos['BPFInfos']['C10']['NbTrainees'] . ' - ' . img_picto('', 'fa-clock', 'class="pictofixedwidth"') . ($BPFInfos['BPFInfos']['C10']['NbTrainees'] * convertSecondToTime($BPFInfos['BPFInfos']['C10']['TrainingSessionDurations'], 'allhour')) . ' H'];

        return $array;
    }

    public function getBPFPartH()
    {
        global $langs, $mysoc;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartH');
        $array['picto']      = 'fas fa-file-invoice-dollar';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [
            $langs->transnoentities('NameAndFirstName'),
            $langs->transnoentities('ManagementStatus'),
        ];

        $array['content'] = [
            $mysoc->managers . '  - WIP',
            'TODO'
        ];

        return $array;
    }

    public function getBPFPartFooter()
    {
        global $langs, $mysoc, $user;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('BPFTitlePartFooter');
        $array['picto']      = 'fas fa-info';
        $array['name']       = 'BPFTitlePartFooter';
        $array['widgetName'] = 'BPFTitlePartFooter';

        // Widget parameters
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
            'TODO'
        ];

        return $array;
    }
}

