<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/dolimeet/dolimeetdocuments/trainingsessiondocument/doc_completioncertificatedocument_odt.modules.php
 * \ingroup dolimeet
 * \brief   File of class to build ODT documents for completion certificate document.
 */

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/lib/saturne_functions.lib.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

// Load DoliMeet libraries.
require_once __DIR__ . '/../completioncertificatedocument/mod_completioncertificatedocument_standard.php';

/**
 * Class to build documents using ODF templates generator.
 */
class doc_completioncertificatedocument_odt extends SaturneDocumentModel
{
    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public $phpmin = [7, 4];

    /**
     * @var string Dolibarr version of the loaded document.
     */
    public string $version = 'dolibarr';

    /**
     * @var string Module.
     */
    public string $module = 'dolimeet';

    /**
     * @var string Document type.
     */
    public string $document_type = 'completioncertificatedocument';

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->document_type);
    }

    /**
     * Return description of a module.
     *
     * @param  Translate $langs Lang object to use for output.
     * @return string           Description.
     */
    public function info(Translate $langs): string
    {
        return parent::info($langs);
    }

    /**
     * Function to build a document on disk.
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document.
     * @param  Translate        $outputLangs     Lang object to use for output.
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file.
     * @param  int              $hideDetails     Do not show line details.
     * @param  int              $hideDesc        Do not show desc.
     * @param  int              $hideRef         Do not show ref.
     * @param  array            $moreParam       More param (Object/user/etc).
     * @return int                               1 if OK, <=0 if KO.
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf, $langs;

        $object = $moreParam['object'];

        $userTmp   = new User($this->db);
        $signatory = new SaturneSignature($this->db, 'dolimeet', $object->element);

        if (!empty($moreParam['attendant'])) {
            $moreParam['documentName'] = strtoupper($moreParam['attendant']->lastname) . '_' . ucfirst($moreParam['attendant']->firstname) . '_';
        } else {
            $moreParam['documentName'] = '';
        }

        if (!empty($object->fk_contrat)) {
            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
            $contract = new Contrat($this->db);
            $contract->fetch($object->fk_contrat);
            $contract->fetch_optionals();
            if (!empty($contract->array_options['options_label'])) {
                $tmpArray['contract_label'] = $contract->array_options['options_label'];
            } else {
                $tmpArray['contract_label'] = $contract->ref;
            }
            $trainingSessionDict = saturne_fetch_dictionary('c_trainingsession_type');
            if (is_array($trainingSessionDict) && !empty($trainingSessionDict) && !empty($contract->array_options['options_trainingsession_type'])) {
                $tmpArray['action_nature'] = $langs->trans($trainingSessionDict[$contract->array_options['options_trainingsession_type']]->label);
            } else {
                $tmpArray['action_nature'] = '';
            }
        } else {
            $tmpArray['contract_label'] = '';
            $tmpArray['action_nature'] = '';
        }

        $tmpArray['date_start'] = dol_print_date($object->date_start, 'dayhour', 'tzuser');
        $tmpArray['date_end']   = dol_print_date($object->date_end, 'dayhour', 'tzuser');
        $tmpArray['duration']   = convertSecondToTime($object->duration);

        $tmpArray['attendant_fullname'] = strtoupper($moreParam['attendant']->lastname) . ' ' . ucfirst($moreParam['attendant']->firstname);
        if ($moreParam['attendant']->element_type == 'user') {
            $tmpArray['attendant_company_name'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
        } elseif ($moreParam['attendant']->element_type == 'socpeople') {
            $contact    = new Contact($this->db);
            $thirdparty = new Societe($this->db);
            $contact->fetch($moreParam['attendant']->element_id);
            $thirdparty->fetch($contact->fk_soc);
            $tmpArray['attendant_company_name'] = $thirdparty->name;
        } else {
            $tmpArray['attendant_company_name'] = '';
        }

        if (getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE') > 0) {
            $signatory = $signatory->fetchSignatory('UserSignature', $conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE, 'user');
        } else {
            setEventMessages($langs->trans('DefineSessionTrainerResponsible'), [], 'errors');
            return -1;
        }
        if(is_array($signatory) && !empty($signatory)) {
            $signatory = array_shift($signatory);
            $userTmp->fetch($signatory->element_id);
            $tmpArray['mycompany_owner_fullname'] = strtoupper($userTmp->lastname) .  ' ' . ucfirst($userTmp->firstname);
            $tmpArray['mycompany_owner_job']      = $userTmp->job;
            if (dol_strlen($signatory->signature) > 0 && $signatory->signature != $langs->transnoentities('FileGenerated')) {
                if ($moreParam['specimen'] == 0 || ($moreParam['specimen'] == 1 && $conf->global->DOLIMEET_SHOW_SIGNATURE_SPECIMEN == 1)) {
                    $tempDir      = $conf->dolimeet->multidir_output[$object->entity ?? 1] . '/temp/';
                    $encodedImage = explode(',', $signatory->signature)[1];
                    $decodedImage = base64_decode($encodedImage);
                    file_put_contents($tempDir . 'signature.png', $decodedImage);
                    $tmpArray['mycompany_owner_signature'] = $tempDir . 'signature.png';
                } else {
                    $tmpArray['mycompany_owner_signature'] = '';
                }
            } else {
                $tmpArray['mycompany_owner_signature'] = '';
            }
        } else {
            $tmpArray['mycompany_owner_fullname']  = '';
            $tmpArray['mycompany_owner_job']       = '';
            $tmpArray['mycompany_owner_signature'] = '';
        }

        $tmpArray['date_creation'] = dol_print_date(dol_now(), 'dayhour', 'tzuser');

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
