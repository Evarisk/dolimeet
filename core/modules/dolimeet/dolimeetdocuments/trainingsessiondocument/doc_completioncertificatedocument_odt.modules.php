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
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

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
        $this->name        = $langs->trans('CompletionCertificateDocumentDoliMeetTemplate');
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
            $texte .= $langs->trans('DoliMeetNumberOfModelFilesFound') . ': <b>';
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
     * @param  Session           $object          Session Object
     * @return int                                1 if OK, <=0 if KO
     * @throws SegmentException
     * @throws Exception
     */
    public function write_file(SessionDocument $objectDocument, Translate $outputlangs, string $srctemplatepath, int $hidedetails = 0, int $hidedesc = 0, int $hideref = 0, Session $object): int
    {
        global $action, $conf, $hookmanager, $langs, $mysoc, $user;

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
        $objectDocumentID    = $objectDocument->create($user, true, $object);

        $objectDocument->fetch($objectDocumentID);

        $objectref = dol_sanitizeFileName($objectDocument->ref);
        $dir = $conf->dolimeet->multidir_output[$object->entity ?? 1] . '/'. $object->type .'/'. $object->ref;

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

            $date       = dol_print_date(dol_now(),'dayxcard');
            $newfiletmp = $objectref . '_' . $date . '_' . $newfiletmp . '_' . $conf->global->MAIN_INFO_SOCIETE_NOM;

            $objectDocument->last_main_doc = $newfiletmp;

            $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'dolimeet_dolimeetdocuments';
            $sql .= ' SET last_main_doc =' . (!empty($newfiletmp) ? "'" . $this->db->escape($newfiletmp) . "'" : 'null');
            $sql .= ' WHERE rowid = ' . $objectDocument->id;

            dol_syslog('dolimeet_dolimeetdocuments::Insert last main doc', LOG_DEBUG);
            $this->db->query($sql);

            // Get extension (ods or odt)
            $newfileformat = substr($newfile, strrpos($newfile, '.') + 1);

            $filename = $newfiletmp . '.' . $newfileformat;
            $file     = $dir . '/' . $filename;

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

            $filearray = dol_dir_list($conf->dolimeet->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref . '/thumbs/', 'files', 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);
            if (count($filearray)) {
                $image = array_shift($filearray);
                $tmparray['photoDefault'] = $image['fullname'];
            } else {
                $nophoto = '/public/theme/common/nophoto.png';
                $tmparray['photoDefault'] = DOL_DOCUMENT_ROOT.$nophoto;
            }

            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
            require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

            $project  = new Project($this->db);
            $contract = new Contrat($this->db);
            //$signatory = new Signature($db);
            /*$signatory = $signatory->fetchSignatory('TRAININGSESSION_SESSION_TRAINER', $object->id, $object->type);
            if (is_array($signatory) && !empty($signatory)) {
                $signatory = array_shift($signatory);
            }*/

            $contract->fetch($object->fk_contrat);
            $contract->fetch_optionals();
            $project->fetch($object->fk_project);

            $trainingsession_type_dict = fetchDictionnary('c_trainingsession_type');

            $tmparray['mycompany_name'] = $conf->global->MAIN_INFO_SOCIETE_NOM;

            $tmparray['date_start'] = dol_print_date($object->date_start, 'dayhour');
            $tmparray['date_end']   = dol_print_date($object->date_end, 'dayhour');
            $duration_hours = floor($object->duration / 60);
            $duration_minutes = $object->duration % 60;
            $duration_minutes = $duration_minutes < 10 ? 0 . $duration_minutes : $duration_minutes;
            $tmparray['duration']     = $duration_hours . 'h' . $duration_minutes;

            if (is_array($trainingsession_type_dict) && !empty($trainingsession_type_dict)) {
                $tmparray['action_nature'] = $langs->trans($trainingsession_type_dict[$contract->array_options['options_trainingsession_type']]->label);
            } else {
                $tmparray['action_nature'] = '';
            }

            $tmparray['attendant_fullname']     = $attendant->firstname . ' ' . $attendant->lastname;

            if ($attendant->element_type == 'user') {

                $tmparray['attendant_company_name']     = $conf->global->MAIN_INFO_SOCIETE_NOM;

            } else if ($attendant->element_type == 'socpeople') {
                $contact = new Contact($db);
                $thirdparty = new Societe($db);
                $contact->fetch($attendant->element_id);
                $thirdparty->fetch($contact->fk_soc);
                $tmparray['attendant_company_name']     = $thirdparty->name;
            }

            $tmparray['trainingsession_name']     = $contract->array_options['options_label'];
            $tmparray['trainingsession_company_name']     = $conf->global->MAIN_INFO_SOCIETE_NOM;

            $tmparray['sessiontrainer_fullname']     = $signatorytmp->firstname . ' ' . $signatorytmp->lastname;

            if (dol_strlen($signatorytmp->signature) > 0) {
                $encoded_image = explode(",", $signatorytmp->signature)[1];
                $decoded_image = base64_decode($encoded_image);
                file_put_contents($tempdir . "signature.png", $decoded_image);
                $tmparray['sessiontrainer_signature'] = $tempdir . "signature.png";
            } else {
                $tmparray['sessiontrainer_signature'] = '';
            }

            $tmparray['document_date']     = dol_print_date(dol_now('tzuser'), 'dayhour');
            $tmparray['location']     = $contract->array_options['options_trainingsession_location'];

            foreach ($tmparray as $key=>$value)
            {
                try {
                    if (($key == 'sessiontrainer_signature' || preg_match('/logo$/', $key)) && is_file($value)) // Image
                    {
                        $list     = getimagesize($value);
                        $newWidth = 200;
                        if ($list[0]) {
                            $ratio     = $newWidth / $list[0];
                            $newHeight = $ratio * $list[1];
                            dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
                        }
                        if (file_exists($value)) $odfHandler->setImage($key, $value);
                        else $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
                    }
                    else    // Text
                    {
                        if (empty($value)) {
                            $odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
                        } else {
                            $odfHandler->setVars($key, html_entity_decode($value,ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
                        }
                    }
                }
                catch (OdfException $e)
                {
                    dol_syslog($e->getMessage(), LOG_INFO);
                }
            }

            // Replace labels translated
            $tmparray = $outputlangs->get_translations_for_substitutions();

            foreach ($tmparray as $key => $value) {
                try {
                    $odfHandler->setVars($key, $value, true, 'UTF-8');
                }
                catch (OdfException $e) {
                    dol_syslog($e->getMessage());
                }
            }

            // Call the beforeODTSave hook
            $parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
            $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

            // Write new file
            if (empty($conf->global->MAIN_ODT_AS_PDF)) {
                try {
                    $odfHandler->exportAsAttachedPDF($file);
                } catch (Exception $e) {
                    $this->error = $e->getMessage();
                    dol_syslog($e->getMessage());
                    return -1;
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