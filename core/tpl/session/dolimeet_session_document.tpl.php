<?php
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

$project = new Project($db);
$contract = new Contrat($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->dolimeet->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'document', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->dolimeet->multidir_output[$conf->entity ?: 1]."/". $object->element ."/".get_exdir(0, 0, 0, 1, $object);
}

$object_type = $object->element;
$permissiontoread = $user->rights->dolimeet->$object_type->read;
$permissiontoadd = $user->rights->dolimeet->$object_type->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolimeet->$object_type->delete || ($permissiontoadd && isset($object->status));
$permissionnote = $user->rights->dolimeet->$object_type->write; // Used by the include of actions_setnotes.inc.php
//$upload_dir = $conf->dolimeet->multidir_output[$conf->entity];

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
if ($user->socid > 0) $socid = $user->socid;
if (empty($conf->dolimeet->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Documents" . ucfirst($object->element));
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($object->id) {
	/*
	 * Show tabs
	 */
	$prepareHead = $object->element . 'PrepareHead';
	$head = $prepareHead($object);

	print dol_get_fiche_head($head, 'document', $langs->trans('Document'), -1, $object->picto);

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/'. $object->element .'/'. $object->element .'_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	// Project
	$project->fetch($object->fk_project);
	$morehtmlref = '- ' . $object->label;
	$morehtmlref .= '<div class="refidno">';
	$morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1);
	$morehtmlref .= '</tr>';
	$morehtmlref .=  '</td><br>';
	$morehtmlref .= '</div>';

	if ($object->element == 'trainingsession') {
		$contract->fetch($object->fk_contrat);
		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans('Contract') . ' : ' . $contract->getNomUrl(1);
		$morehtmlref .= '</tr>';
		$morehtmlref .= '</td><br>';
		$morehtmlref .= '</div>';
	}	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';


	print dol_get_fiche_end();

	$modulepart = 'dolimeet';
	//todo:perms
	//$permissiontoadd = $user->rights->'. $object->element .'->dolimeet->write;
	$permissiontoadd = 1;
	//$permtoedit = $user->rights->'. $object->element .'->dolimeet->write;
	$permtoedit = 1;
	$param = '&id='.$object->id;

	//$relativepathwithnofile='dolimeet/' . dol_sanitizeFileName($object->id).'/';
	$relativepathwithnofile = $object->element . '/'.dol_sanitizeFileName($object->ref).'/';

	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	print '</div>';

} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
