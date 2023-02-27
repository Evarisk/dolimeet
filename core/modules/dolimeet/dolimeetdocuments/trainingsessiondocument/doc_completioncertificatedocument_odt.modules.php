<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
 *	\file       core/modules/dolimeet/dolimeetdocuments/trainingsessiondocument/doc_completioncertificatedocument_odt.modules.php
 *	\ingroup    dolimeet
 *	\brief      File of class to build ODT documents for trainingsessiondocument
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/modules_trainingsessiondocument.php';
require_once __DIR__ . '/mod_completioncertificatedocument_standard.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_completioncertificatedocument_odt extends ModeleODTTrainingSessionDocument
{
    /**
     * Issuer
     * @var Societe
     */
    public $emetteur;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public array $phpmin = [7, 0];

    /**
     * @var string Dolibarr version of the loaded document
     */
    public string $version = 'dolibarr';

    /**
     *	Constructor
     *
     *  @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $langs, $mysoc;

        // Load translation files required by the page
        $langs->loadLangs(['main', 'companies']);

        $this->db = $db;
        $this->name        = $langs->trans('ODTDefaultTemplateName', $langs->transnoentities('CompletionCertificateDocuments'));
        $this->description = $langs->trans('DocumentModelOdt');
        $this->scandir     = 'DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

        // Page size for A4 format
        $this->type = 'odt';
        $this->page_largeur = 0;
        $this->page_hauteur = 0;
        $this->format = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 0;
        $this->marge_droite = 0;
        $this->marge_haute  = 0;
        $this->marge_basse  = 0;

        $this->option_logo      = 1; // Display logo
        $this->option_multilang = 1; // Available in several languages

        // Get source company
        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code){
            $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if not defined
        }
    }

    /**
     * Return description of a module
     *
     * @param  Translate $langs Lang object to use for output
     * @return string           Description
     */
    public function info(Translate $langs): string
    {
        global $conf, $langs;

        // Load translation files required by the page
        $langs->loadLangs(['errors', 'companies']);

        $texte = $this->description . ' . <br>';
        $texte .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="' . newToken() . '">';
        $texte .= '<input type="hidden" name="action" value="setModuleOptions">';
        $texte .= '<input type="hidden" name="param1" value="DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH">';
        $texte .= '<table class="nobordernopadding">';

        // List of directories area
        $texte .= '<tr><td>';
        $texttitle = $langs->trans('ListOfDirectories');
        $listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH)));
        $listoffiles = [];
        foreach ($listofdir as $key=>$tmpdir) {
            $tmpdir = trim($tmpdir);
            $tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
            $tmpdir = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $tmpdir);
            if (!$tmpdir) {
                unset($listofdir[$key]);
                continue;
            }
            if (!is_dir($tmpdir)) {
                $texttitle .= img_warning($langs->trans('ErrorDirNotFound', $tmpdir), 0);
            }
            else {
                $tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
                if (count($tmpfiles)) {
                    $listoffiles = array_merge($listoffiles, $tmpfiles);
                }
            }
        }

        // Scan directories
        $nbofiles = count($listoffiles);
        if (!empty($conf->global->DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH)) {
            $texte .= $langs->trans('NumberOfModelFilesFound') . ': <b>';
            $texte .= count($listoffiles);
            $texte .= '</b>';
        }

        if ($nbofiles) {
            $texte .= '<div id="div_' . get_class($this) . '" class="hidden">';
            foreach ($listoffiles as $file) {
                $texte .= $file['name'] . '<br>';
            }
            $texte .= '</div>';
        }

        $texte .= '</td>';
        $texte .= '</table>';
        $texte .= '</form>';

        return $texte;
    }

    /**
     * Function to build a document on disk using the generic odt module.
     *
     * @param  SessionDocument   $objectDocument  Object source to build document
     * @param  Translate         $outputlangs     Lang output object
     * @param  string            $srctemplatepath Full path of source filename for generator using a template file
     * @param  int               $hidedetails     Do not show line details
     * @param  int               $hidedesc        Do not show desc
     * @param  int               $hideref         Do not show ref
     * @param  array             $moreparam       More param (Object/user/etc)
     * @return int                                1 if OK, <=0 if KO
     * @throws SegmentException
     * @throws Exception
     */
    public function write_file(SessionDocument $objectDocument, Translate $outputlangs, string $srctemplatepath, int $hidedetails = 0, int $hidedesc = 0, int $hideref = 0, array $moreparam): int
    {
        global $action, $conf, $hookmanager, $langs, $mysoc;

        $object = $moreparam['object'];

        if (empty($srctemplatepath)) {
            dol_syslog('doc_completioncertificatedocument_odt::write_file parameter srctemplatepath empty', LOG_WARNING);
            return -1;
        }

        // Add odtgeneration hook
        if (!is_object($hookmanager)) {
            include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
            $hookmanager = new HookManager($this->db);
        }
        $hookmanager->initHooks(['odtgeneration']);

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }

        $outputlangs->charset_output = 'UTF-8';
        $outputlangs->loadLangs(['main', 'dict', 'companies', 'dolimeet@dolimeet']);

        $refModName          = new $conf->global->DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON($this->db);
        $objectDocumentRef   = $refModName->getNextValue($objectDocument);
        $objectDocument->ref = $objectDocumentRef;
        $objectDocumentID    = $objectDocument->create($moreparam['user'], true, $object);

        $objectDocument->fetch($objectDocumentID);

        $objectDocumentRef = dol_sanitizeFileName($objectDocument->ref);
        $dir = $conf->dolimeet->multidir_output[$object->entity ?? 1] . '/' . $object->type . 'document/' . $object->ref;
        if ($moreparam['specimen'] == 1 && $moreparam['zone'] == 'public') {
            $dir .= '/specimen';
        }

        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
                return -1;
            }
        }

        if (file_exists($dir)) {
            $newfile    = basename($srctemplatepath);
            $newfiletmp = preg_replace('/\.od([ts])/i', '', $newfile);
            $newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
            $societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

            if (!empty($moreparam['attendant'])) {
                $attandent = strtoupper($moreparam['attendant']->lastname) . '_' . $moreparam['attendant']->firstname;
            } else {
                $attandent = '';
            }

            $date = dol_print_date(dol_now(),'dayxcard');
            $newfiletmp = $date . '_' . $object->ref . '_' . $objectDocumentRef .'_' . $newfiletmp . '_' . $attandent . '_' . $societyname;
            if ($moreparam['specimen'] == 1) {
                $newfiletmp .= '_specimen';
            }

            // Get extension (ods or odt)
            $newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
            $filename      = $newfiletmp . '.' . $newfileformat;
            $file          = $dir . '/' . $filename;

            $objectDocument->last_main_doc = $filename;

            $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'dolimeet_dolimeetdocuments';
            $sql .= ' SET last_main_doc =' . (!empty($objectDocument->last_main_doc) ? "'" . $this->db->escape($objectDocument->last_main_doc) . "'" : 'null');
            $sql .= ' WHERE rowid = ' . $objectDocument->id;

            dol_syslog('dolimeet_dolimeetdocuments::Insert last main doc', LOG_DEBUG);
            $this->db->query($sql);

            dol_mkdir($conf->dolimeet->dir_temp);

            if (!is_writable($conf->dolimeet->dir_temp)) {
                $this->error = 'Failed to write in temp directory ' . $conf->dolimeet->dir_temp;
                dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
                return -1;
            }

            // Make substitution
            $substitutionarray = [];
            complete_substitutions_array($substitutionarray, $langs, $object);
            // Call the ODTSubstitution hook
            $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray];
            $reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

            // Open and load template
            require_once ODTPHP_PATH . 'odf.php';
            try {
                $odfHandler = new odf(
                    $srctemplatepath,
                    [
                        'PATH_TO_TMP'	  => $conf->dolimeet->dir_temp,
                        'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
                        'DELIMITER_LEFT'  => '{',
                        'DELIMITER_RIGHT' => '}'
                    ]
                );
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                dol_syslog($e->getMessage());
                return -1;
            }

            $tempdir = $conf->dolimeet->multidir_output[isset($object->entity) ? $object->entity : 1] . '/temp/';

            //Define substitution array
            $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
            $array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
            $array_soc['mycompany_logo'] = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

            $tmparray = array_merge($substitutionarray, $array_soc);
            complete_substitutions_array($tmparray, $outputlangs, $object);

            require_once __DIR__ . '/../../../../../class/saturnesignature.class.php';

            $signatory = new SaturneSignature($this->db);
            $signatory = $signatory->fetchSignatory('SessionTrainer', $object->id, $object->element);
            if (is_array($signatory) && !empty($signatory)) {
                $signatory = array_shift($signatory);
                $tmparray['trainer_fullname'] = strtoupper($signatory->lastname) . ' ' . $signatory->firstname;
                $tmparray['trainer_quality']  = $langs->trans($signatory->role);
            } else {
                $tmparray['trainer_fullname'] = '';
                $tmparray['trainer_quality']  =  '';
            }

            if (!empty($object->fk_contrat)) {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $contract = new Contrat($this->db);
                $contract->fetch($object->fk_contrat);
                $contract->fetch_optionals();
                $trainingsessionDict = fetch_dolimeet_dictionnary('c_trainingsession_type');
                if (is_array($trainingsessionDict) && !empty($trainingsessionDict) && !empty($contract->array_options['options_trainingsession_type'])) {
                    $tmparray['action_nature'] = $langs->trans($trainingsessionDict[$contract->array_options['options_trainingsession_type']]->label);
                } else {
                    $tmparray['action_nature'] = '';
                }
                $tmparray['contract_ref_label'] = $contract->ref;
                $tmparray['location'] = $contract->array_options['options_trainingsession_location'];
                if (!empty($contract->array_options['options_label'])) {
                    $tmparray['contract_ref_label'] .= ' - ' . $contract->array_options['options_label'];
                }
            } else {
                $tmparray['contract_ref_label'] = '';
                $tmparray['location'] = '';
                $tmparray['action_nature'] = '';
            }

            $tmparray['mycompany_name'] = $conf->global->MAIN_INFO_SOCIETE_NOM;

            $tmparray['date_start'] = dol_print_date($object->date_start, 'dayhour');
            $tmparray['date_end']   = dol_print_date($object->date_end, 'dayhour');
            $tmparray['duration']   = convertSecondToTime($object->duration);

            $tmparray['attendant_fullname'] = strtoupper($moreparam['attendant']->lastname) . ' ' . $moreparam['attendant']->firstname;
            if ($moreparam['attendant']->element_type == 'user') {
                $tmparray['attendant_company_name'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
            } elseif ($moreparam['attendant']->element_type == 'socpeople') {
                $contact = new Contact($this->db);
                $thirdparty = new Societe($this->db);
                $contact->fetch($moreparam['attendant']->element_id);
                $thirdparty->fetch($contact->fk_soc);
                $tmparray['attendant_company_name'] = $thirdparty->name;
            } else {
                $tmparray['attendant_fullname']     = '';
                $tmparray['attendant_company_name'] = '';
            }

            if (dol_strlen($signatory->signature) > 0 && $signatory->signature != $langs->transnoentities('FileGenerated') && $conf->global->DOLIMEET_SHOW_SIGNATURE_SPECIMEN == 1) {
                $encodedImage = explode(',', $signatory->signature)[1];
                $decodedImage = base64_decode($encodedImage);
                file_put_contents($tempdir . 'signature.png', $decodedImage);
                $tmparray['trainer_signature'] = $tempdir . 'signature.png';
            } else {
                $tmparray['trainer_signature'] = '';
            }

            $tmparray['date_creation'] = dol_print_date(dol_now(), 'dayhour', 'tzuser');

            foreach ($tmparray as $key => $value) {
                try {
                    if ($key == 'trainer_signature') { // Image
                        if (file_exists($value)) {
                            $list = getimagesize($value);
                            $newWidth = 350;
                            if ($list[0]) {
                                $ratio = $newWidth / $list[0];
                                $newHeight = $ratio * $list[1];
                                dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
                            }
                            $odfHandler->setImage($key, $value);
                        } else {
                            $odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                        }
                    } elseif (preg_match('/logo$/', $key)) {
                        if (file_exists($value)) {
                            $odfHandler->setImage($key, $value);
                        } else {
                            $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
                        }
                    } elseif (empty($value)) { // Text
                        $odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                    } else {
                        $odfHandler->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                    }
                } catch (OdfException $e) {
                    dol_syslog($e->getMessage());
                }
            }

            // Replace labels translated
            $tmparray = $outputlangs->get_translations_for_substitutions();

            foreach ($tmparray as $key => $value) {
                try {
                    $odfHandler->setVars($key, $value, true, 'UTF-8');
                } catch (OdfException $e) {
                    dol_syslog($e->getMessage());
                }
            }

            // Call the beforeODTSave hook
            $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
            $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

            $fileInfos = pathinfo($filename);
            $pdfName   = $fileInfos['filename'] . '.pdf';

            // Write new file
            if (!empty($conf->global->MAIN_ODT_AS_PDF) && $conf->global->DOLIMEET_AUTOMATIC_PDF_GENERATION > 0) {
                try {
                    $odfHandler->exportAsAttachedPDF($file);

                    global $moduleNameLowerCase;
                    $documentUrl = DOL_URL_ROOT . '/document.php';
                    setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode('completioncertificatedocument/' . $object->ref . '/' . $pdfName) . '&entity='. $conf->entity .'"' . '>' . $pdfName  . '</a>', []);
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    dol_syslog($e->getMessage());
                    setEventMessages($langs->transnoentities('FileCouldNotBeGeneratedInPDF') . '<br>' . $langs->transnoentities('CheckDocumentationToEnablePDFGeneration'), [], 'errors');
                }
            } else {
                try {
                    $odfHandler->saveToDisk($file);
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    dol_syslog($e->getMessage());
                    return -1;
                }
            }

            $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
            $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

            if (!empty($conf->global->MAIN_UMASK)) {
                @chmod($file, octdec($conf->global->MAIN_UMASK));
            }

            $odfHandler = null; // Destroy object

            $this->result = ['fullpath' => $file];
            dol_delete_file($tempdir . 'signature.png');

            return 1; // Success
        } else {
            $this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
            return -1;
        }

        return -1;
    }
}