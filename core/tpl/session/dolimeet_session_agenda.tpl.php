<?php

$extrafields = new ExtraFields($db);
$project = new Project($db);
$contract = new Contrat($db);

$hookmanager->initHooks([$object->element.'agenda', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || ! empty($ref)) {
    $upload_dir = $conf->dolimeet->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity] . '/' . $object->id;
}

$object_type = $object->element;

// Security check - Protection if external user
$permissiontoread = $user->rights->$module_name_lower->$object_type->read;
if (empty($conf->$module_name_lower->enabled) || !$permissiontoread) {
    accessforbidden();
}

/*
*  Actions
*/

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Cancel
    if ($cancel && ! empty($backtopage)) {
        header('Location: ' . $backtopage);
        exit;
    }

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        $actioncode          = '';
        $search_agenda_label = '';
    }
}

/*
*	View
*/

if ($object->id > 0) {
	$title    = $langs->trans('Agenda' . ucfirst($object->element));
	$help_url = 'FR:Module_' . $module_name;
    //@todo changement avec saturne
    $morejs   = ['/dolimeet/js/dolimeet.js'];
    $morecss  = ['/dolimeet/css/dolimeet.css'];

    llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

    // Configuration header
	$head = sessionPrepareHead($object);
	print dol_get_fiche_head($head, 'agenda', $title, -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/' . $module_name_lower . '/view/' . $object->element . '/' . $object->element . '_list.php', 1) . '?restore_lastsearch_values=1' . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = '<div class="refidno">';
	// Project
    if (!empty($conf->projet->enabled)) {
        if (!empty($object->fk_project)) {
            $project->fetch($object->fk_project);
            $morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1);
        } else {
            $morehtmlref .= '';
        }
    }

    // Contract @todo hook car spécifique a dolimeet
	if ($object->element == 'trainingsession') {
        if (!empty($object->fk_contrat)) {
            $contract->fetch($object->fk_contrat);
            $morehtmlref .= $langs->trans('Contract') . ' : ' . $contract->getNomUrl(1, '', 1);
        } else {
            $morehtmlref .= '';
        }
	}
    $morehtmlref .= '</div>';

    //@todo problème avec dolimeet
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();

	// Actions buttons
	$out = '&origin=' . urlencode($object->element . '@' . $object->module) . '&originid=' . $object->id;
	$urlbacktopage = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
	$out .= '&backtopage=' . urlencode($urlbacktopage);

	print '<div class="tabsAction">';
	if (isModEnabled('agenda')) {
		if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
			print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out . '">' . $langs->trans('AddAction') . '</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans('AddAction') . '</a>';
		}
	}
	print '</div>';

	if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
		$param = '&id='.$object->id;
		if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
			$param .= '&contextpage=' . urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit=' . urlencode($limit);
		}

        print load_fiche_titre($langs->trans('ActionsOn' . ucfirst($object->element)), '', '');

		// List of all actions
		$filters = [];
		$filters['search_agenda_label'] = $search_agenda_label;

		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
	}
}

// End of page
llxFooter();
$db->close();
