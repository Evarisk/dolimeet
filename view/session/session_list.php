<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    view/session/session_list.php
 * \ingroup dolimeet
 * \brief   List page for session
 */

// Load DoliMeet environment
if (file_exists('../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../dolimeet.main.inc.php';
} elseif (file_exists('../../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Get module parameters
$objectType = GETPOST('object_type', 'aZ') ?: 'session';

// Load Dolibarr libraries
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// load DoliMeet libraries
require_once __DIR__ . '/../../class/' . $objectType . '.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');                                                // The bulk action (combo box choice into lists)
$fromType   = GETPOST('fromtype', 'alpha');                                                  // Element type
$fromID     = GETPOSTINT('fromid');                                                                // Element id
$type       = GETPOST('type');

// Get list parameters
$toselect                                   = [];
[$confirm, $contextpage, $optioncss, $mode] = ['', '', '', ''];
$listParameters                             = saturne_load_list_parameters($objectType);
foreach ($listParameters as $listParameterKey => $listParameter) {
    $$listParameterKey = $listParameter;
}

// Get pagination parameters
[$limit, $page, $offset] = [0, 0, 0];
[$sortfield, $sortorder] = ['', ''];
$paginationParameters    = saturne_load_pagination_parameters();
foreach ($paginationParameters as $paginationParameterKey => $paginationParameter) {
    $$paginationParameterKey = $paginationParameter;
}

// Initialize technical objects
$classname   = ucfirst($objectType);
$object      = new $classname($db);
$extrafields = new ExtraFields($db);
if (isModEnabled('categorie')) {
    $categorie = new Categorie($db);
}
if (!empty($fromType)) {
    $objectMetadata = saturne_get_objects_metadata($fromType);
    $objectLinked   = $objectMetadata['object'];
}

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$contextpage]); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (isModEnabled('categorie')) {
    $searchCategories = GETPOST('search_category_' . $object->element . '_list', 'array');
}

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
    reset($object->fields); // Reset is required to avoid key() to return null
    $sortfield = 't.date_start';
}
if (!$sortorder) {
    $sortorder = 'DESC';
}

// Definition of custom fields for columns
$excludeFields                          = [];
$signatoriesInDictionary                = saturne_fetch_dictionary('c_' . $object->element . '_attendants_role');
$conf->cache['signatoriesInDictionary'] = $signatoriesInDictionary;
if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
    $customFieldsPosition = 111;
    foreach ($signatoriesInDictionary as $signatoryInDictionary) {
        $object->fields[$signatoryInDictionary->ref] = ['label' => $signatoryInDictionary->ref, 'enabled' => 1, 'position' => $customFieldsPosition++, 'visible' => 2, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx right'];
        $excludeFields[]                             = $signatoryInDictionary->ref;
    }
}

$object->fields['society_attendants'] = ['type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'SocietyAttendants', 'enabled' => 1, 'position' => 115, 'visible' => 2, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx'];

$excludeFields = array_merge($excludeFields, ['society_attendants']);

// Initialize array of search criterias
$searchAll = trim(GETPOST('search_all'));
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
    if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
        $search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
    }
    if (isset($val['type']) && $val['type'] == 'duration') {
        $search[$key . '_dtstart'] = GETPOSTINT('search_' . $key . '_dtstarthour') * 3600 + GETPOSTINT('search_' . $key . '_dtstartmin') * 60;
        $search[$key . '_dtend']   = GETPOSTINT('search_' . $key . '_dtendhour') * 3600 + GETPOSTINT('search_' . $key . '_dtendhour') * 60;
    }
}

if (!empty($fromType)) {
    switch ($fromType) {
        case 'thirdparty' :
            $search['fk_soc'] = $fromID;
            break;
        case 'project' :
            $search['fk_project'] = $fromID;
            break;
        case 'socpeople' :
            $search['fk_contact']                 = $fromID;
            $search['search_external_attendants'] = $fromID;
            break;
        case 'contrat' :
            $objectLinked->element = 'contract';
            $search['fk_contrat']  = $fromID;
            if ($object->element == 'trainingsession') {
                $sortfield = 't.date_start';
                $sortorder = 'ASC';
            }
            break;
        case 'user' :
            $search['search_internal_attendants'] = $fromID;
            break;
        case 'product' :
            $object->fields['fk_element']['enabled'] = 0;
            $object->fields['fk_contrat']['enabled'] = 0;
            $object->fields['fk_soc']['enabled']     = 0;
            $object->fields['position']['enabled']   = 1;
            $object->fields['position']['visible']  = 1;
            $search['model']                         = 1;
            $sortfield = 't.position';
            $sortorder = 'ASC';
            break;
    }

    $objectLinked->fetch($fromID);
}

// List of fields to search into when doing a "search in all"
$fieldsToSearchAll = [];
foreach ($object->fields as $key => $val) {
    if (!empty($val['searchall'])) {
        $fieldsToSearchAll['t.' . $key] = $val['label'];
    }
}

// Definition of array of fields for columns
foreach ($object->fields as $key => $val) {
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible']);
        $arrayfields['t.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0 || (!isset($val['showinpwa']) && $mode == 'pwa')) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'])),
            'position' => $val['position'],
            'help'     => $val['help'] ?? '',
        ];
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields    = dol_sort_array($arrayfields, 'position');

// Permissions
$permissionToRead   = $user->hasRight($object->module, $object->element, 'read') || $user->hasRight($object->module, 'assignedtome', $object->element);
$permissiontoadd    = $user->hasRight($object->module, $object->element, 'write');
$permissiontodelete = $user->hasRight($object->module, $object->element, 'delete');

// Security check
saturne_check_access($permissionToRead, $object, true);

/*
 * Actions
 */

$parameters = ['arrayfields' => &$arrayfields];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Selection of new fields
    require_once DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($object->fields as $key => $val) {
            $search[$key] = '';
            if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp', 'duration'])) {
                $search[$key.'_dtstart'] = '';
                $search[$key.'_dtend']   = '';
            }
        }
        $searchAll            = '';
        $toselect             = [];
        $search_array_options = [];
        $searchCategories     = [];
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
        $massaction = '';
    }

    // Mass actions
    $objectclass = 'Session';
    $objectlabel = 'Session';
    $uploaddir   = $conf->dolimeet->dir_output;

    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    // Mass actions archive
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/list_massactions.tpl.php';
}

/*
 * View
 */

if ($mode == 'pwa') {
    $conf->dol_hide_topmenu  = 1;
    $conf->dol_hide_leftmenu = 1;
}

$title   = $langs->trans(ucfirst($object->element) . 'List');
$helpUrl = 'FR:Module_DoliMeet';

saturne_header(0,'', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

//if (dol_strlen($fromType) > 0 && !in_array($fromType, $linkedObjectsArray) && !in_array($fromType, $signatoryObjectsArray) && $fromType != 'product') {
//    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as e on (e.fk_source = ' . $fromID . ' AND e.sourcetype="' . $fromType . '" AND e.targettype = "saturne_' . $objectType . '")';
//} elseif (is_array($signatoryObjectsArray) && in_array($fromType, $signatoryObjectsArray)) {
//    if ($fromType == 'thirdparty') {
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as c on (c.fk_soc = ' . $fromID . ')';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'saturne_object_signature as e on (e.element_id = c.rowid AND e.element_type="socpeople" AND e.status > 0)';
//    } else {
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'saturne_object_signature as e on (e.element_id = ' . $fromID . ' AND e.element_type="' . $fromType . '" AND e.status > 0)';
//    }
//}

//
//if ($fromType == 'product') {
//    $sql .= ' AND t.fk_element = ' . $fromID . ' AND t.element_type = "service"';
//} elseif (is_array($signatoryObjectsArray) && dol_strlen($fromType) > 0 && !in_array($fromType, $linkedObjectsArray) && !in_array($fromType, $signatoryObjectsArray)) {
//    $sql .= ' AND t.rowid = e.fk_target ';
//} elseif (is_array($signatoryObjectsArray) && in_array($fromType, $signatoryObjectsArray)) {
//    $sql .= ' AND t.rowid = e.fk_object ';
//}

//if ($searchSocietyAttendants > 0) {
//    $sql .= ' AND t.rowid = search_society_attendants.fk_object ';
//}
//if (!$user->rights->dolimeet->$objectType->read && $user->rights->dolimeet->assignedtome->$objectType) {
//    $sql .= ' AND t.rowid = search_assignedtome.fk_object ';
//}

if (!empty($fromType) && $fromID > 0) {
    saturne_get_fiche_head($objectLinked, 'sessionList', $langs->trans($object->element));

    $moreHtml                = '<a href="' . dol_buildpath($objectMetadata['list_url'], 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';
    $moreParams['bannerTab'] = '&fromtype='. $fromType . '&object_type=' . $objectType;
    if ($objectLinked instanceof Project && dol_strlen($objectLinked->title)) {
        $moreHtmlRef = $objectLinked->title . '<br>';
    }
    saturne_banner_tab($objectLinked, 'fromid', $moreHtml, 1, 'rowid', 'ref', $moreHtmlRef ?? '', false, $moreParams);

    $moreUrlParameters = '&fromtype=' . urlencode($fromType) . '&fromid=' . urlencode($fromID);
    if ($fromType == 'product') {
        $moreUrlParameters .= '&model=on&element_type=service&fk_project=' . getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT') . '&fk_element=' . $fromID;
        $formMoreParams     = ['model' => 'on', 'element_type' => 'service', 'fk_project' => getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT'), 'fk_element' => $fromID];
    }
    $formMoreParams = ['fromtype' => $fromType, 'fromid' => $fromID];
    $statusMode = 3;
}
if ($object->element != 'session') {
    $formMoreParams = ['object_type' => $object->element];
} ?>

<script>
    $(document).ready(function(){
        $('#object_type_select').on('change', function(){
            let value = $(this).val();
            let url = new URL(document.URL)
            let search_params = url.searchParams;
            search_params.set('object_type', value);
            url.search = search_params.toString();
            location.href = url.toString()
        });
    });
</script>

<?php
if ($object->element == 'session' || !empty($fromType) && $fromID > 0) {
    $objectTypes   = ['meeting' => $langs->trans('Meeting'), 'trainingsession' => $langs->trans('Trainingsession'), 'audit' => $langs->trans('Audit')];
    $newCardButton = $form->selectarray('object_type_select', $objectTypes, $object->element, ($fromType != 'contrat' ? $langs->trans('SelectSessionType') : ''));
}
//$helpText = ($objectType != 'session' ? '' : $langs->trans('SelectSessionType'));
//$url      = dol_buildpath('/dolimeet/view/session/session_card.php', 1) . '?action=create' . $moreUrlParameters;
//$moreUrlParameters .= '&object_type=' . urlencode($object->element);
//$status  = ($objectType != 'session' ? $permissiontoadd : -2);

////                if ($resource['label'] == 'InternalAttendants') {
////                    print '<td>';
////                    print $form->select_dolusers($fromType == 'user' ? $fromID : $searchInternalAttendants, 'search_internal_attendants', 1);
////                    print '</td>';
////                } elseif ($resource['label'] == 'ExternalAttendants') {
////                    print '<td>';
////                    print $form->selectcontacts(0, $fromType == 'socpeople' ? $fromID : $searchExternalAttendants, 'search_external_attendants', 1);
////                    print '</td>';
////                }
//                  if ($resource['label'] == 'SocietyAttendants') {
//                    print '<td class="liste_titre ' . $resource['css'] . '">';
//                    print $form->select_company($searchSocietyAttendants, 'search_society_attendants', '', 1);
//                    print '</td>';
//                  }

require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_header.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_input.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_title.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_loop_object.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_footer.tpl.php';

// End of page
llxFooter();
$db->close();
