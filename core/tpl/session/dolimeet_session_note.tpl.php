<?php
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

$project = new Project($db);
$contract = new Contrat($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->dolimeet->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'note', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->dolimeet->multidir_output[$object->entity]."/".$object->id;
}

$object_type = $object->element;
$permissiontoread = $user->rights->dolimeet->$object_type->read;
$permissiontoadd = $user->rights->dolimeet->$object_type->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolimeet->$object_type->delete || ($permissiontoadd && isset($object->status));
$permissionnote = $user->rights->dolimeet->$object_type->write; // Used by the include of actions_setnotes.inc.php
$upload_dir = $conf->dolimeet->multidir_output[$conf->entity];

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
if ($user->socid > 0) $socid = $user->socid;
if (empty($conf->dolimeet->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
$title        = $langs->trans("Note" . ucfirst($object->element));

llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

    $head = sessionPrepareHead($object);;

	print dol_get_fiche_head($head, 'note', $langs->trans('Notes'), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/dolimeet/view/'. $object->element .'/'. $object->element .'_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


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
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


	$cssclass = "titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
