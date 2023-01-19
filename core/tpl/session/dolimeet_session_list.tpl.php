<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcontract.class.php';

// load session libraries
require_once __DIR__ . '/../../../lib/dolimeet_function.lib.php';
require_once __DIR__ . '/../../../class/session.class.php';
require_once __DIR__ . '/../../../class/trainingsession.class.php';
require_once __DIR__ . '/../../../class/meeting.class.php';
require_once __DIR__ . '/../../../class/audit.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');
global $user, $db, $user, $langs;
// Load translation files required by the page
$langs->loadLangs(array("dolimeet@dolimeet", "other", "bills", "projects", "orders", "companies", "contracts"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sessionlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$fromtype = GETPOST('fromtype', 'alpha'); // element type
$fromid = GETPOST('fromid', 'int'); //element id

$id = GETPOST('id', 'int');
$type = GETPOST('type');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new $session_type($db);
$extrafields = new ExtraFields($db);
$thirdparty = new Societe($db);
$contact = new Contact($db);
$sender = new User($db);
$project = new Project($db);
$formproject = new FormProjets($db);
$formcontrat = new FormContract($db);
if (empty($object->type)) {
	$object->type = 'session';
}
if (!$fromtype || !$fromid) {
	unset($object->fields['type']);
}
$diroutputmassaction = $conf->session->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('documentlist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	reset($object->fields);					// Reset is required to avoid key() to return null.
	$sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
}
if (!$sortorder) {
	$sortorder = "ASC";
}

if (!empty($fromtype)) {
	switch ($fromtype) {
		case 'facture' :
			$objectLinked = new Facture($db);
			$prehead = 'facture_prepare_head';
			break;
		case 'thirdparty' :
			$objectLinked = new Societe($db);
			$prehead = 'societe_prepare_head';
			break;
		case 'product' :
			$objectLinked = new Product($db);
			$prehead = 'product_prepare_head';
			break;
		case 'project' :
			$objectLinked = new Project($db);
			$prehead = 'project_prepare_head';
			break;
		case 'propal' :
			$objectLinked = new Propal($db);
			$prehead = 'propal_prepare_head';
			break;
		case 'order' :
			$objectLinked = new Commande($db);
			$prehead = 'commande_prepare_head';
			break;
		case 'socpeople' :
			$objectLinked = new Contact($db);
			$prehead = 'contact_prepare_head';
			break;
		case 'contrat' :
			$objectLinked = new Contrat($db);
			$prehead = 'contract_prepare_head';
			break;
		case 'user' :
			$objectLinked = new User($db);
			$prehead = 'user_prepare_head';
			break;
	}
	$objectLinked->fetch($fromid);
	$head = $prehead($objectLinked);
	$linkedObjectsArray = array('project', 'contrat');
	$signatoryObjectsArray = array('user', 'thirdparty', 'socpeople');
}

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
$search = array();

foreach ($object->fields as $key => $val) {
	if (GETPOST($key, 'alpha') !== '') {
		$search[$key] = GETPOST($key, 'alpha');
	}
	if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_'.$key.'_dtstartmonth', 'int'), GETPOST('search_'.$key.'_dtstartday', 'int'), GETPOST('search_'.$key.'_dtstartyear', 'int'));
		$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_'.$key.'_dtendmonth', 'int'), GETPOST('search_'.$key.'_dtendday', 'int'), GETPOST('search_'.$key.'_dtendyear', 'int'));
	}
}

if(!empty($fromtype)) {
	switch ($fromtype) {
//		case 'thirdparty':
//			$search['fk_soc'] = $fromid;
//			break;
//		case 'contact':
//			$search['fk_contact'] = $fromid;
//			break;
		case 'project':
			$search['fk_project'] = $fromid;
			break;
		case 'contrat':
			$search['fk_contrat'] = $fromid;
			break;
		case 'user':
			$search['search_society_attendants'] = $fromid;
			break;
		case 'socpeople':
			$search['search_external_attendants'] = $fromid;
			break;
		case 'thirdparty':
			$search['search_attendant_thirdparties'] = $fromid;
			break;
	}
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.'.$key] = $val['label'];
	}
}

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>($visible != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=> isset($val['help']) ? $val['help'] : ''
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$object_type = strtolower($session_type);
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

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($object->fields as $key => $val) {
			$search[$key] = '';
			if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$search[$key.'_dtstart'] = '';
				$search[$key.'_dtend'] = '';
			}
		}
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Session';
	$objectlabel = 'Session';
	$uploaddir = $conf->dolimeet->session->dir_output;
//	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/*
 * View
 */

$form = new Form($db);
$signatory = new DolimeetSignature($db);

$now = dol_now();

//$help_url="EN:Module_Audit|FR:Module_Audit_FR|ES:Módulo_Audit";
$help_url = '';
$title = $langs->trans(ucfirst($object->type) . 'List');
$morejs = array();
$morecss = array();


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT DISTINCT ';
$sql .= $object->getFieldList('t');
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key.', ' : '');
	}
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
}
if (dol_strlen($fromtype) > 0 && !in_array($fromtype, $linkedObjectsArray) && !in_array($fromtype, $signatoryObjectsArray)) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as e on (e.fk_source = ' .$fromid. ' AND e.sourcetype="' . $fromtype . '" AND e.targettype = "dolimeet_'. $object->type .'")';
} elseif (is_array($signatoryObjectsArray) && in_array($fromtype, $signatoryObjectsArray)) {
	if ($fromtype == 'thirdparty') {
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as c on (c.fk_soc = ' .$fromid. ')';
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'dolimeet_object_signature as e on (e.element_id = c.rowid AND e.element_type="socpeople" AND e.status > 0)';
	} else {
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'dolimeet_object_signature as e on (e.element_id = ' .$fromid. ' AND e.element_type="' . $fromtype . '" AND e.status > 0)';
	}
}

if (GETPOST('search_society_attendants') > 0) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'dolimeet_object_signature as search_society_attendants on (search_society_attendants.element_id = ' .GETPOST('search_society_attendants'). ' AND search_society_attendants.element_type="user" AND search_society_attendants.status > 0)';
}
if (GETPOST('search_external_attendants') > 0) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'dolimeet_object_signature as search_external_attendants on (search_external_attendants.element_id = ' .GETPOST('search_external_attendants'). ' AND search_external_attendants.element_type="socpeople" AND search_external_attendants.status > 0)';
}
if (GETPOST('search_attendant_thirdparties') > 0) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as cf on (cf.fk_soc = ' .GETPOST('search_attendant_thirdparties'). ')';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'dolimeet_object_signature as search_attendant_thirdparties on (search_attendant_thirdparties.element_id = cf.rowid AND search_attendant_thirdparties.element_type="socpeople" AND search_attendant_thirdparties.status > 0)';
}

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks("printFieldListFrom", $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " WHERE 1 = 1 ";

if ($object->ismultientitymanaged == 1) {
	$sql .= " AND t.entity IN (".getEntity($object->element).")";
}
$sql .= " AND t.status > -1";
if (is_array($signatoryObjectsArray) && dol_strlen($fromtype) > 0 && !in_array($fromtype, $linkedObjectsArray)  && !in_array($fromtype, $signatoryObjectsArray)) {
	$sql .= " AND t.rowid = e.fk_target ";
} else if (is_array($signatoryObjectsArray) && in_array($fromtype, $signatoryObjectsArray)) {
	$sql .= " AND t.rowid = e.fk_object ";
}

if (GETPOST('search_society_attendants') > 0) {
	$sql .= " AND t.rowid = search_society_attendants.fk_object ";
}
if (GETPOST('search_external_attendants') > 0) {
	$sql .= " AND t.rowid = search_external_attendants.fk_object ";
}
if (GETPOST('search_attendant_thirdparties') > 0) {
	$sql .= " AND t.rowid = search_attendant_thirdparties.fk_object ";
}

if ($object->type != 'session') {
	$sql .= " AND type = '". $object->type ."'";
}
foreach ($search as $key => $val) {
	if (array_key_exists($key, $object->fields)) {
		if ($key == 'status' && $search[$key] == -1) {
			continue;
		}
		if ($search[$key] < 1) {
			continue;
		}
		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
		if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
			if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
				$search[$key] = '';
			}
			$mode_search = 2;
		}
		if ($search[$key] != '') {
			$sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
	} else {
		if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
			$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
			if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
				if (preg_match('/_dtstart$/', $key)) {
					$sql .= " AND t.".$columnName." >= '".$db->idate($search[$key])."'";
				}
				if (preg_match('/_dtend$/', $key)) {
					$sql .= " AND t." . $columnName . " <= '" . $db->idate($search[$key]) . "'";
				}
			}
		}

	}
}

if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);

	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
	$num = $nbtotalofrecords;
} else {
	if ($limit) {
		$sql .= $db->plimit($limit + 1, $offset);
	}

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/'. $object->type .'/'. $object->type .'_card.php', 1).'?id='.$id);
	exit;
}

if ($object->type != 'trainingsession' && GETPOST('fromtype') != 'contrat') {
	unset($object->fields['fk_contrat']);
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', '');

require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
$contract = new Contrat($db);

if (!empty($fromtype)) {
	print dol_get_fiche_head($head, $object->type . 'List', $langs->trans($object->type), -1, $objectLinked->picto);
	dol_banner_tab($objectLinked, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
}

if ($fromid) {
	print '<div class="underbanner clearboth"></div>';
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) {
		foreach ($search[$key] as $skey) {
			$param .= '&search_'.$key.'[]='.urlencode($skey);
		}
	} else {
		$param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if ($permissiontodelete) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?fromtype='.$fromtype.'&fromid=' . $fromid.'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$fromurl = '';
if (!empty($fromtype)) {
	$fromurl = '&fromtype='.$fromtype.'&fromid='.$fromid;
}

if ($object->type !== 'session') {
	$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/dolimeet/view/'. strtolower($object->type) .'/'. strtolower($object->type) .'_card.php', 1).'?action=create'.$fromurl, '', $permissiontoadd);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);
// Add code for pre mass action (confirmation or email presend form)
$topicmail = "Send". $object->type ."Ref";
$modelmail = "document";

$objecttmp = new $object->type($db);
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
	$moreforfilter.= '</div>';*/

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
$moreforfilter .= $hookmanager->resPrint;
} else {
$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$arrayfields['SocietyAttendants']           = array('label' => 'SocietyAttendants', 'checked' => 1);
$arrayfields['ExternalAttendants']             = array('label' => 'ExternalAttendants', 'checked' => 1);
$arrayfields['AttendantThirdparties']  = array('label' => 'AttendantThirdparties', 'checked' => 1);

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$object->fields['Custom']['SOCIETY_ATTENDANTS']            = $arrayfields['SocietyAttendants'] ;
$object->fields['Custom']['EXTERNAL_ATTENDANT']              = $arrayfields['ExternalAttendants'];
$object->fields['Custom']['ATTENDANT_THIRDPARTIES']  = $arrayfields['AttendantThirdparties'];

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
		$cssforfield .= ($cssforfield ? ' ' : '').'right';
	}

	if (!empty($arrayfields['t.'.$key]['checked'])) {
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			print $form->selectarray('search_'.$key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
		} elseif ($key == 'fk_soc') {
			$thirdparty->fetch(0, $search['fk_soc']);
			print '<div class="nowrap">';
			print $form->select_company((!empty(GETPOST('fk_soc')) ? GETPOST('fk_soc') : (GETPOST('fromtype') == 'thirdparty' ? GETPOST('fromid') : '')), 'fk_soc', '', 'SelectThirdParty', 1, 0, array(), 0, 'maxwidth200');
			print '</div>';
		} elseif ($key == 'fk_project') {
			$project->fetch(0, $search['fk_project']);
			print $formproject->select_projects(0, ( ! empty(GETPOST('fk_project')) ? GETPOST('fk_project') :  (GETPOST('fromtype') == 'project' ? GETPOST('fromid') : '')), 'fk_project', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth200');
			print '<input class="input-hidden-fk_project" type="hidden" name="search_fk_project" value=""/>';
		}  elseif ($key == 'fk_contrat') {
			$contract->fetch(0, $search['fk_contrat']);
			$formcontrat->select_contract(-1, ( ! empty(GETPOST('fk_contrat')) ? GETPOST('fk_contrat') :  (GETPOST('fromtype') == 'contrat' ? GETPOST('fromid') : '')), 'fk_contrat', 0, 1, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth200');
			print '<input class="input-hidden-fk_contrat" type="hidden" name="search_fk_contrat" value=""/>';
		} elseif ($key == 'fk_contact') {
			$contact->fetch(0, $search['fk_contact']);
			print $form->selectcontacts(0, !empty(GETPOST('fk_contact')) ? GETPOST('fk_contact') : (GETPOST('fromtype') == 'contact' ? GETPOST('fromid') : ''), 'fk_contact', 1);
			print '<input class="input-hidden-fk_project" type="hidden" name="search_fk_contact" value=""/>';
		} elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
			print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', 'maxwidth125', 1);
		} elseif (!preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '').'">';
		} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			print '<div class="nowrap">';
			print $form->selectDate($search[$key.'_dtstart'] ? $search[$key.'_dtstart'] : '', "search_".$key."_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
			print '</div>';
			print '<div class="nowrap">';
			print $form->selectDate($search[$key.'_dtend'] ? $search[$key.'_dtend'] : '', "search_".$key."_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
			print '</div>';
		}
		print '</td>';
	} elseif ($key == 'Custom') {
		foreach ($val as $resource) {
			if ($resource['checked']) {
				if ($resource['label'] == 'SocietyAttendants') {
					print '<td>';
					print $form->select_dolusers($fromtype == 'user' ? $fromid : GETPOST('search_society_attendants'), 'search_society_attendants', 1);
					print '</td>';
				} else if ($resource['label'] == 'ExternalAttendants') {
					print '<td>';
					print $form->selectcontacts(0, $fromtype == 'socpeople' ? $fromid : GETPOST('search_external_attendants'), 'search_external_attendants', 1);
					print '</td>';
				} else if ($resource['label'] == 'AttendantThirdparties') {
					print '<td>';
					print $form->select_company($fromtype == 'thirdparty' ? $fromid : GETPOST('search_attendant_thirdparties'), 'search_attendant_thirdparties', '',1);
					print '</td>';
				}
			}
		}
	}

}
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
	foreach ($object->fields as $key => $val) {
		$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
		if ($key == 'status') {
			$cssforfield .= ($cssforfield ? ' ' : '').'center';
		} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '').'center';
		} elseif (in_array($val['type'], array('timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
			$cssforfield .= ($cssforfield ? ' ' : '').'right';
		}
		if (!empty($arrayfields['t.'.$key]['checked'])) {
			print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
		} elseif ($key == 'Custom') {
			foreach ($val as $resource) {
				if ($resource['checked']) {
					print '<td>';
					print $langs->trans($resource['label']);
					print '</td>';
				}
			}
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	print '</tr>'."\n";

	// Detect if we need a fetch on each output line
	$needToFetchEachLine = 0;
	if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
		foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
		if (preg_match('/\$object/', $val)) {
			$needToFetchEachLine++; // There is at least one compute field that use $object
		}
	}
}


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
$totalarray['nbfield'] = 0;
while ($i < ($limit ? min($num, $limit) : $num)) {

	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
	break; // Should not happen
	}

	// Store properties in $object
	$object->setVarsFromFetchObj($obj);

	// Show here line of result
	print '<tr class="oddeven">';
	foreach ($object->fields as $key => $val) {
			$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '').'center';
		} elseif ($key == 'status') {
			$cssforfield .= ($cssforfield ? ' ' : '').'center';
		}

		if (in_array($val['type'], array('timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		} elseif ($key == 'ref') {
			$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		}

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('rowid', 'status')) && empty($val['arrayofkeyval'])) {
			$cssforfield .= ($cssforfield ? ' ' : '').'right';
		}
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['t.'.$key]['checked'])) {
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '') .'>';
			if ($key == 'fk_soc') {
				if ($object->fk_soc > 0) {
					$thirdparty->fetch($obj->fk_soc);
					print $thirdparty->getNomUrl(1);
				}
			} elseif ($key == 'fk_contrat') {
				if ($obj->fk_contrat > 0) {
					$contract->fetch($obj->fk_contrat);
					print $contract->getNomUrl(1);
				}
			} elseif ($key == 'type') {
				print '<div class="nowrap">';
				print $langs->transnoentities(ucfirst($object->type));
				print '</div>';
			}
			else if ($key == 'fk_contact') {
				$contact->fetch($obj->fk_contact);
			print $contact->getNomUrl(1);
			}
				else if ($key == 'sender') {
				$sender->fetch($obj->sender);
				print $sender->getNomUrl();
			}
			else if ($key == 'fk_project') {
				if ($obj->fk_project > 0) {

					$project->fetch($obj->fk_project);
					print $project->getNomUrl(1);
				}
			}
			else if ($key == 'status') {
				print $object->getLibStatut(5);
			} else if ($key == 'rowid') {
				print $object->showOutputField($val, $key, $object->id, '');
			} else {
				print $object->showOutputField($val, $key, $object->$key, '');
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!empty($val['isameasure'])) {
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
				}
				if (!isset($totalarray['val'])) {
					$totalarray['val'] = array();
				}
				if (!isset($totalarray['val']['t.'.$key])) {
					$totalarray['val']['t.'.$key] = 0;
				}
				$totalarray['val']['t.'.$key] += $object->$key;
			}
		} else if ($key == 'Custom') {
			foreach ($val as $resource) {
				if ($resource['checked']) {
					if ($resource['label'] == 'SocietyAttendants') {
						$signatories = $signatory->fetchSignatory(strtoupper($object->type).'_SOCIETY_ATTENDANT',$object->id, $object->type);
						print '<td>';
						if (is_array($signatories) && !empty($signatories)) {
							foreach($signatories as $object_signatory) {
								$usertmp = $user;
								$usertmp->fetch($object_signatory->element_id);
								print $usertmp->getNomUrl(1);
								print '<br>';
							}
						}
						print '</td>';
					} elseif ($resource['label'] == 'ExternalAttendants') {
						$signatories = $signatory->fetchSignatory(strtoupper($object->type).'_EXTERNAL_ATTENDANT',$object->id, $object->type);
						print '<td>';
						if (is_array($signatories) && !empty($signatories)) {
							foreach($signatories as $object_signatory) {
								$contact->fetch($object_signatory->element_id);
								print $contact->getNomUrl(1);
								print '<br>';
							}
						}
						print '</td>';
					} elseif ($resource['label'] == 'AttendantThirdparties') {
						$signatories = $signatory->fetchSignatory(strtoupper($object->type).'_EXTERNAL_ATTENDANT',$object->id, $object->type);
						print '<td>';
						if (is_array($signatories) && !empty($signatories)) {
							foreach($signatories as $object_signatory) {
								$contact->fetch($object_signatory->element_id);
								$thirdparty->fetch($contact->fk_soc);
								print $thirdparty->getNomUrl(1);
								print '<br>';
							}
						}
						print '</td>';
					}
				}
			}
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected = 0;
		if (in_array($object->id, $arrayofselected)) {
		$selected = 1;
		}
		print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
	if (!$i) {
	$totalarray['nbfield']++;
	}

	print '</tr>'."\n";
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT . '/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
$colspan = 1;
foreach ($arrayfields as $key => $val) {
if (!empty($val['checked'])) {
$colspan++;
}
}
print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_' .$object->type, '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}
