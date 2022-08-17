<?php
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

$extrafields = new ExtraFields($db);
$project = new Project($db);
$contract = new Contrat($db);
$diroutputmassaction = $conf->dolimeet->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'agenda', 'globalcard')); // Note that conf->hooks_modules contains array
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
*  Actions
*/

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
// Cancel
if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
header("Location: ".$backtopage);
exit;
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
$actioncode = '';
$search_agenda_label = '';
}
}



/*
*	View
*/

$form = new Form($db);

if ($object->id > 0) {
$title = $langs->trans("Agenda" . ucfirst($object->element));
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = 'EN:Module_Agenda_En';
llxHeader('', $title, $help_url);

if (!empty($conf->notification->enabled)) {
$langs->load("mails");
}
$prepareHead = $object->element . 'PrepareHead';
$head = $prepareHead($object);

print dol_get_fiche_head($head, 'agenda', $langs->trans('Agenda'), -1, $object->picto);

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

$object->info($object->id);
dol_print_object_info($object, 1);

print '</div>';

print dol_get_fiche_end();

// Actions buttons

$objthirdparty = $object;
$objcon = new stdClass();

$out = '&origin='.urlencode($object->element.'@'.$object->module).'&originid='.urlencode($object->id);
$urlbacktopage = $_SERVER['PHP_SELF'].'?id='.$object->id;
$out .= '&backtopage='.urlencode($urlbacktopage);
$permok = $user->rights->agenda->myactions->create;
if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') {
			$out .= '&socid='.urlencode($objthirdparty->id);
		}
		$out .= (!empty($objcon->id) ? '&contactid='.urlencode($objcon->id) : '').'&percentage=-1';
		//$out.=$langs->trans("AddAnAction").' ';
//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
//$out.="</a>";
}


print '<div class="tabsAction">';

	if (!empty($conf->agenda->enabled)) {
	if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out.'">'.$langs->trans("AddAction").'</a>';
	} else {
	print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("AddAction").'</a>';
	}
	}

	print '</div>';

if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
$param = '&id='.$object->id.'&socid='.$socid;
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
$param .= '&limit='.urlencode($limit);
}

// List of all actions
$filters = array();
$filters['search_agenda_label'] = $search_agenda_label;

// TODO Replace this with same code than into list.php
show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
}
}

// End of page
llxFooter();
$db->close();
