<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 *	\file       core/modules/dolimeet/attendancesheet/doc_attendancesheet_odt.modules.php
 *	\ingroup    dolimeet
 *	\brief      File of class to build ODT documents for dolimeet
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/../modules_session.php';
/**
 *	Class to build documents using ODF templates generator
 */
class doc_attendancesheet_odt extends ModelePDFSession
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
	public $phpmin = array(5, 5);

	/**
	 * @var string Dolibarr version of the loaded document
	 */
	public $version = 'dolibarr';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;


		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = $langs->trans('AttendanceSheetDoliMeetTemplate');
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'DOLIMEET_ATTENDANCESHEET_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		// Recupere emetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
	}

	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	public function info($langs)
	{
		global $conf, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("errors", "companies"));

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="DOLIMEET_ATTENDANCESHEET_ADDON_ODT_PATH">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DOLIMEET_ATTENDANCESHEET_ADDON_ODT_PATH)));
		$listoffiles = array();
		foreach ($listofdir as $key=>$tmpdir)
		{
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if (!is_dir($tmpdir)) $texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else
			{
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
			}
		}

		// Scan directories
		$nbofiles = count($listoffiles);
		if (!empty($conf->global->DOLIMEET_ATTENDANCESHEET_ADDON_ODT_PATH))
		{
			$texte .= $langs->trans("DoliMeetNumberOfModelFilesFound").': <b>';
			$texte .= count($listoffiles);
			$texte .= '</b>';
		}

		if ($nbofiles)
		{
			$texte .= '<div id="div_'.get_class($this).'" class="hidden">';
			foreach ($listoffiles as $file)
			{
				$texte .= $file['name'].'<br>';
			}
			$texte .= '</div>';
		}

		$texte .= '</td>';
		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		ControlDocument	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($objectDocument, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $object)
	{
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc, $db;

		$object = $object['object'];

		if (empty($srctemplatepath))
		{
			dol_syslog("doc_attendancesheet_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));

		if (!is_object($outputlangs)) $outputlangs = $langs;
		$outputlangs->charset_output = 'UTF-8';

		$outputlangs->loadLangs(array("main", "dict", "companies", "dolimeet@dolimeet"));


		$dir = $conf->dolimeet->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/'. $object->type .'/'. $object->ref;

		if (!file_exists($dir))
		{
			if (dol_mkdir($dir) < 0)
			{
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir))
		{
			$filename = preg_split('/attendancesheet\//' , $srctemplatepath);
			$filename = preg_replace('/template_/','', $filename[1]);

			$date = dol_print_date(dol_now(),'dayxcard');

			$filename = $date . '_attendancesheet_' . $object->ref . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

			dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
			$file = $dir.'/'.$filename;

			dol_mkdir($conf->dolimeet->dir_temp);

			// Make substitution
			$substitutionarray = array();
			complete_substitutions_array($substitutionarray, $langs, $object);
			// Call the ODTSubstitution hook
			$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$substitutionarray);
			$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Open and load template
			require_once ODTPHP_PATH.'odf.php';
			try {
				$odfHandler = new odf(
					$srctemplatepath,
					array(
						'PATH_TO_TMP'	  => $conf->dolimeet->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}'
					)
				);
			}
			catch (Exception $e)
			{
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}

			$tempdir = $conf->dolimeet->multidir_output[isset($object->entity) ? $object->entity : 1] . '/temp/';

			//Define substitution array
			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
			//$array_object = $this->get_substitutionarray_object($object, $outputlangs);
			$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_soc['mycompany_logo'] = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_soc);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			$filearray = dol_dir_list($conf->dolimeet->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref . '/thumbs/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);
			if (count($filearray)) {
				$image = array_shift($filearray);
				$tmparray['photoDefault'] = $image['fullname'];
			}else {
				$nophoto = '/public/theme/common/nophoto.png';
				$tmparray['photoDefault'] = DOL_DOCUMENT_ROOT.$nophoto;
			}

			$usertmp    = new User($db);
			$contract = new Contrat($db);
			$project = new Project($db);
			$signatorytmp = new DolimeetSignature($db);
			$signatorytmp = $signatorytmp->fetchSignatory('TRAININGSESSION_SESSION_TRAINER', $object->id, $object->type);
			if (is_array($signatorytmp) && !empty($signatorytmp)) {
				$signatorytmp = array_shift($signatorytmp);
			}

			$contract->fetch($object->fk_contrat);
			$project->fetch($object->fk_project);

			$tmparray['mycompany_name']     = $conf->global->MAIN_INFO_SOCIETE_NOM;
			$tmparray['Adress']     = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
			$tmparray['CONTRACT']     = $contract->ref;
			$tmparray['PROJECT']     = $project->ref;
			$tmparray['DATESESSION']     = dol_print_date($object->date_start, 'dayhour');
			$tmparray['DSSESSION']     = dol_print_date($object->date_start, 'dayhour');
			$tmparray['DESESSION']     = dol_print_date($object->date_end, 'dayhour');
			$tmparray['DURATION']     = $object->duration;

			$tmparray['intervenant_name'] = $signatorytmp->firstname . ' ' . $signatorytmp->lastname;
			if (dol_strlen($signatorytmp->signature) > 0) {
				$encoded_image = explode(",", $signatorytmp->signature)[1];
				$decoded_image = base64_decode($encoded_image);
				file_put_contents($tempdir . "signature.png", $decoded_image);
				$tmparray['intervenant_signature'] = $tempdir . "signature.png";
			} else {
				$tmparray['intervenant_signature'] = '';
			}

			foreach ($tmparray as $key=>$value)
			{
				try {
					if (($key == 'intervenant_signature' || preg_match('/logo$/', $key)) && is_file($value)) // Image
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

			// Replace tags of lines
			try
			{
				$foundtagforlines = 1;
				if ($foundtagforlines) {
					if ( ! empty( $object ) ) {
						$listlines = $odfHandler->setSegment('attendants');
						$signatory = new DolimeetSignature($db);
						$signatoriesList = $signatory->fetchSignatories($object->id, $object->type);
						if ( ! empty($signatoriesList) && is_array($signatoriesList)) {
							$k = 1;
							foreach ($signatoriesList as $objectSignatory) {
								if ($objectSignatory->role != "TRAININGSESSION_SESSION_TRAINER") {
									$tmparray['attendant_number'] = $k;
									$tmparray['attendant_lastname'] = $objectSignatory->lastname;
									$tmparray['attendant_firstname'] = $objectSignatory->firstname;
									if (dol_strlen($objectSignatory->signature) > 0) {
										$encoded_image = explode(",", $objectSignatory->signature)[1];
										$decoded_image = base64_decode($encoded_image);
										file_put_contents($tempdir . "signature" . $k . ".png", $decoded_image);
										$tmparray['attendant_signature'] = $tempdir . "signature" . $k . ".png";
									} else {
										$tmparray['attendant_signature'] = '';
									}
									foreach ($tmparray as $key=>$value)
									{
										try {
											if ($key == 'attendant_signature' && is_file($value)) { // Image
												$list     = getimagesize($value);

												$newWidth = 200;
												if ($list[0]) {
													$ratio     = $newWidth / $list[0];
													$newHeight = $ratio * $list[1];
													dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
												}
												$listlines->setImage($key, $value);
											} else if (empty($value)) {
												$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
											} else if (!is_array($value)) {
												$listlines->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
											}
										}
										catch (OdfException $e)
										{
											dol_syslog($e->getMessage(), LOG_INFO);
										}
									}
									$listlines->merge();
									dol_delete_file($tempdir . "signature" . $k . ".png");
									$k++;
								}

							}
							$odfHandler->mergeSegment($listlines);

						}
					}
				}

			}
			catch (OdfException $e)
			{
				$this->error = $e->getMessage();
				dol_syslog($this->error, LOG_WARNING);
				return -1;
			}
			// Replace labels translated
			$tmparray = $outputlangs->get_translations_for_substitutions();

			foreach ($tmparray as $key=>$value)
			{
				try {
					if ($key == 'attendant_lastname') {
						echo '<pre>'; print_r( $value ); echo '</pre>'; exit;

					}
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				}
				catch (OdfException $e)
				{
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			// Call the beforeODTSave hook
			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Write new file
			if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
				try {
					$odfHandler->exportAsAttachedPDF($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}
			else {
				try {
					$odfHandler->saveToDisk($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}

			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

			$odfHandler = null; // Destroy object

			$this->result = array('fullpath'=>$file);
			dol_delete_file($tempdir . "signature.png");

			return 1; // Success
		}
		else
		{
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}

		return -1;
	}
}
