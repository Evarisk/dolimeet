<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       view/session/session_card.php
 *		\ingroup    dolimeet
 *		\brief      Page to create/edit/view session
 */

// Load DoliMeet environment
if (file_exists('../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Get module parameters
$objectType = GETPOST('object_type', 'alpha');

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

require_once __DIR__ . '/../../lib/dolimeet_' . $objectType . '.lib.php';
require_once __DIR__ . '/../../lib/dolimeet_functions.lib.php';

require_once __DIR__ . '/../../class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../class/saturnesignature.class.php';
require_once __DIR__ . '/../../core/modules/dolimeet/session/mod_' . $objectType . '_standard.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'card'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$classname        = ucfirst($objectType);
$object           = new $classname($db);
$sessiondocument  = new SessionDocument($db, $objectType);
$signatory        = new SaturneSignature($db);
$extrafields      = new ExtraFields($db);
$thirdparty       = new Societe($db);
$contact          = new Contact($db);
$contract         = new Contrat($db);
$mod              = 'DOLIMEET_'. strtoupper($objectType) .'_ADDON';
$refMod           = new $conf->global->$mod();

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$objectType . 'card', 'sessioncard', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alpha');
$search = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

$upload_dir = $conf->dolimeet->multidir_output[$object->entity ?? 1];

// Security check - Protection if external user
$permissiontoread   = $user->rights->dolimeet->$objectType->read || $user->rights->dolimeet->assignedtome->$objectType;
$permissiontoadd    = $user->rights->dolimeet->$objectType->write;
$permissiontodelete = $user->rights->dolimeet->$objectType->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
saturne_check_access($permissiontoread, null, true);

/*
 * Actions
 */

$parameters = [];
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/dolimeet/view/session/session_list.php', 1) . '?object_type=' . $object->element;

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/dolimeet/view/session/session_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__') . '&object_type=' . $object->element;
            }
        }
    }

    // Action clone object
    if ($action == 'confirm_clone' && $confirm == 'yes') {
        $options['label']      = GETPOST('clone_label');
        $options['attendants'] = GETPOST('clone_attendants');
        $result = $object->createFromClone($user, $object->id, $options);
        if ($result > 0) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $result . '&object_type=' . $object->element);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = '';
        }
    }

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    $conf->global->MAIN_DISABLE_PDF_AUTOUPDATE = 1;

    if ($action == 'add' && $permissiontoadd) {
        $durationhour = GETPOST('durationhour');
        $durationmin  = GETPOST('durationmin');
        if (empty($durationhour)) {
            $_POST['durationhour'] = 0;
        }
        if (empty($durationmin)) {
            $_POST['durationmin'] = 0;
        }
    }

    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

    // Action to build doc
    if (($action == 'builddoc' || GETPOST('forcebuilddoc')) && $permissiontoadd) {
        $outputlangs = $langs;
        $newlang = '';

        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
            $newlang = GETPOST('lang_id', 'aZ09');
        }
        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        // To be sure vars is defined
        if (empty($hidedetails)){
            $hidedetails = 0;
        }
        if (empty($hidedesc)) {
            $hidedesc = 0;
        }
        if (empty($hideref)) {
            $hideref = 0;
        }
        if (empty($moreparams)) {
            $moreparams = null;
        }

        if (GETPOST('forcebuilddoc')) {
            $model  = '';
            $modellist = saturne_get_list_of_models($db, $object->element . 'document');
            if (!empty($modellist)) {
                asort($modellist);
                $modellist = array_filter($modellist, 'saturne_remove_index');
                if (is_array($modellist)) {
                    $models = array_keys($modellist);
                }
            }
        } else {
            $model = GETPOST('model', 'alpha');
        }

        $moreparams['object'] = $object;
        $moreparams['user']   = $user;

        if ($object->status < $object::STATUS_LOCKED) {
            $moreparams['specimen'] = 1;
            $moreparams['zone']     = 'private';
        } else {
            $moreparams['specimen'] = 0;
        }

        if (preg_match('/completioncertificate/', (!empty($models) ? $models[1] : $model))) {
            $signatoriesArray = $signatory->fetchSignatories($object->id, $object->type);
            if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
                foreach ($signatoriesArray as $objectSignatory) {
                    if ($objectSignatory->role == 'Trainee' && $objectSignatory->attendance != $objectSignatory::ATTENDANCE_ABSENT) {
                        $moreparams['attendant'] = $objectSignatory;
                        $result = $sessiondocument->generateDocument((!empty($models) ? $models[1] : $model), $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
                        if ($result <= 0) {
                            setEventMessages($sessiondocument->error, $object->errors, 'errors');
                            $action = '';
                        }
                    }
                }
                if (empty($donotredirect)) {
                    setEventMessages($langs->trans('FileGenerated') . ' - ' . $sessiondocument->last_main_doc, []);
                    $urltoredirect = $_SERVER['REQUEST_URI'];
                    $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
                    $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
                    if (!GETPOST('forcebuilddoc')){
                        header('Location: ' . $urltoredirect . '#builddoc');
                        exit;
                    }
                }
            }
        }
        $result = $sessiondocument->generateDocument((!empty($models) ? $models[0] : $model), $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        if ($result <= 0) {
            setEventMessages($sessiondocument->error, $sessiondocument->errors, 'errors');
            $action = '';
        } elseif (empty($donotredirect)) {
            setEventMessages($langs->trans('FileGenerated') . ' - ' . $sessiondocument->last_main_doc, []);
            $urltoredirect = $_SERVER['REQUEST_URI'];
            $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
            $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
            $urltoredirect = preg_replace('/forcebuilddoc=1&?/', '', $urltoredirect); // To avoid infinite loop
            header('Location: ' . $urltoredirect . '#builddoc');
            exit;
        }
    }

    // Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    if ($action == 'remove_file') {
        if (!empty($upload_dir)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            $langs->load('other');
            $filetodelete = GETPOST('file', 'alpha');
            $file = $upload_dir . '/' . $filetodelete;
            $ret = dol_delete_file($file, 0, 0, 0, $object);
            if ($ret) {
                setEventMessages($langs->trans('FileWasRemoved', $filetodelete), []);
            } else {
                setEventMessages($langs->trans('ErrorFailToDeleteFile', $filetodelete), [], 'errors');
            }

            // Make a redirect to avoid to keep the remove_file into the url that create side effects
            $urltoredirect = $_SERVER['REQUEST_URI'];
            $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
            $urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

            header('Location: ' . $urltoredirect);
            exit;
        } else {
            setEventMessages('BugFoundVarUploaddirnotDefined', [], 'errors');
        }
    }

    // Actions to send emails
    $triggersendname = strtoupper($object->element) . '_SENTBYMAIL';
    $autocopy        = 'MAIN_MAIL_AUTOCOPY_' . strtoupper($object->element) . '_TO';
    $paramname2      = 'object_type';
    $paramval2       = $object->element;
    include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

    require_once __DIR__ . '/../../core/tpl/signature/signature_action_workflow.tpl.php';
}

/*
 * View
 */

$title    = $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_DoliMeet';

saturne_header(0, '', $title, $help_url);

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
        exit;
    }

    print load_fiche_titre($langs->trans('New' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?object_type=' . $object->element . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    $now = dol_getdate(dol_now());

    $_POST['date_startyear']  = $now['year'];
    $_POST['date_startmonth'] = $now['mon'];
    $_POST['date_startday']   = $now['mday'];
    $_POST['date_starthour']  = $now['hours'];
    $_POST['date_startmin']   = $now['minutes'];

    if ($object->element == 'meeting') {
        $_POST['date_endyear']  = $now['year'];
        $_POST['date_endmonth'] = $now['mon'];
        $_POST['date_endday']   = $now['mday'];
        $_POST['date_endhour']  = $now['hours'] + 1;
        $_POST['date_endmin']   = $now['minutes'];
    }

    $fromtype = GETPOSTISSET('fromtype') ? GETPOST('fromtype', 'alpha') : ''; // element type
    $fromid   = GETPOSTISSET('fromid') ? GETPOST('fromid', 'int') : 0;        //element id

    if (!empty($fromtype)) {
        switch ($fromtype) {
            case 'thirdparty' :
                $_POST['fk_soc'] = $fromid;
                break;
            case 'project' :
                $_POST['fk_project'] = $fromid;
                break;
            case 'contrat' :
                $_POST['fk_contrat'] = $fromid;
                $contract->fetch($fromid);
                if ($contract->fk_project > 0) {
                    $_POST['fk_project'] = $contract->fk_project;
                }
                if ($contract->fk_soc > 0) {
                    $_POST['fk_soc'] = $contract->fk_soc;
                }
                break;
        }
    }

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Categories
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories($objectType, '', 'parent', 64, 0, 1);
        print img_picto('', 'category') . $form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');

    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans('Modify' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?object_type=' . $object->element . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">';

    // Common attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

    // Tags-Categories
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories($objectType, '', 'parent', 64, 0, 1);
        $c = new Categorie($db);
        $cats = $c->containing($object->id, 'session');
        $arrayselected = [];
        if (is_array($cats)) {
            foreach ($cats as $cat) {
                $arrayselected[] = $cat->id;
            }
        }
        print img_picto('', 'category') . $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    // Other attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $res = $object->fetch_optionals();
    $linkback = '<a href="' . dol_buildpath('/dolimeet/view/session/session_list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->element . '">' . $langs->trans('BackToList') . '</a>';
    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object, 'ref', $linkback);

    $formconfirm = '';

    // setDraft confirmation
    if (($action == 'draft' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ReOpenObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmReOpenObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_setdraft', '', 'yes', 'actionButtonInProgress', 350, 600);
    }
    // setPendingSignature confirmation
    if (($action == 'pending_signature' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ValidateObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmValidateObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_validate', '', 'yes', 'actionButtonPendingSignature', 350, 600);
    }
    // setLocked confirmation
    if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
    }

    // Clone confirmation
    if (($action == 'clone' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile))) || ( ! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        // Define confirmation messages
        $formquestionclone = [
            ['type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans('NewLabelForClone', $langs->transnoentities('The' . ucfirst($object->element))), 'value' => $langs->trans('CopyOf') . ' ' . $object->ref, 'size' => 24],
            ['type' => 'radio', 'name' => 'clone_attendants', 'label' => $langs->trans('CloneAttendants'), 'values' => [0 => $langs->trans('Attendants'), 1 => $langs->trans('AttendantsFromContract'), 2 => $langs->trans('None')], 'default' => 0]
        ];
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'actionButtonClone', 350, 600);
    }

    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
    }

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formconfirm];
    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) {
        $formconfirm .= $hookmanager->resPrint;
    } elseif ($reshook > 0) {
        $formconfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formconfirm;

    switch ($object->element) {
        case 'meeting' :
            $attendantsRole = ['Contributor', 'Responsible'];
            break;
        case 'trainingsession' :
            $attendantsRole = ['Trainee', 'SessionTrainer'];
            break;
        case 'audit' :
            $attendantsRole = ['Auditor'];
            break;
        default :
            $attendantsRole = ['Attendant'];
    }

    $mesg              = '';
    $nbAttendantByRole = [];
    $nbAttendants      = 0;
    foreach ($attendantsRole as $attendantRole) {
        $signatories = $signatory->fetchSignatory($attendantRole, $object->id, $object->element);
        if (is_array($signatories) && !empty($signatories)) {
            foreach ($signatories as $objectSignatory) {
                if ($objectSignatory->role == $attendantRole) {
                    $nbAttendantByRole[$attendantRole]++;
                }
            }
        } else {
            $nbAttendantByRole[$attendantRole] = 0;
        }
        if ($nbAttendantByRole[$attendantRole] == 0) {
            $mesg .= $langs->trans('NoAttendant', $langs->trans($attendantRole), $langs->transnoentities('The' . ucfirst($object->element))) . '<br>';
        }
    }

    if (!in_array(0, $nbAttendantByRole)) {
        $nbAttendants = 1;
    }

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    $keyforbreak = 'content';
    unset($object->fields['label']);      // Hide field already shown in banner
    unset($object->fields['fk_project']); // Hide field already shown in banner
    unset($object->fields['fk_soc']);     // Hide field already shown in banner
    unset($object->fields['fk_contrat']); // Hide field already shown in banner

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Categories
    if ($conf->categorie->enabled) {
        print '<tr><td class="valignmiddle">' . $langs->trans('Categories') . '</td><td>';
        print $form->showCategories($object->id, 'session', 1);
        print '</td></tr>';
    }

    // Other attributes. Fields from hook formObjectOptions and Extrafields.
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    // Buttons for actions
    if ($action != 'presend') {
        print '<div class="tabsAction">';
        $parameters = [];
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook) && $permissiontoadd) {
            // Modify
            if ($object->status == $object::STATUS_DRAFT) {
                print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=edit' . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</span>';
            }

            // Validate
            if ($object->status == $object::STATUS_DRAFT && $nbAttendants > 0) {
                print '<span class="butAction" id="actionButtonPendingSignature"><i class="fas fa-check"></i> ' . $langs->trans('Validate') . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) . '<br>' . $mesg) . '"><i class="fas fa-check"></i> ' . $langs->trans('Validate') . '</span>';
            }

            // ReOpen
            if ($object->status == $object::STATUS_VALIDATED) {
                print '<span class="butAction" id="actionButtonInProgress"><i class="fas fa-lock-open"></i> ' . $langs->trans('ReOpenDoli') . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-lock-open"></i> ' . $langs->trans('ReOpenDoli') . '</span>';
            }

            // Sign
            if ($object->status == $object::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/custom/dolimeet/view/saturne_attendants.php?id=' . $object->id . '&module_name=DoliMeet&object_type=' . $object->element, 3) . '"><i class="fas fa-signature"></i> ' . $langs->trans('Sign') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-signature"></i> ' . $langs->trans('Sign') . '</span>';
            }

            // Lock
            if ($object->status == $object::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element) && $nbAttendants > 0) {
                print '<span class="butAction" id="actionButtonLock"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AllSignatoriesMustHaveSigned', $langs->transnoentities('The' . ucfirst($object->element))) . '<br>' . $mesg) . '"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
            }

            // Send mail
            if ($object->status == $object::STATUS_LOCKED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                $signatoriesArray = $signatory->fetchSignatory('Trainee', $object->id, $object->type);
                if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
                    $nbTrainee = count($signatoriesArray);
                } else {
                    $nbTrainee = 0;
                }
                $filelist = dol_dir_list($upload_dir . '/' . $object->element . 'document' . '/' . $object->ref, 'files', 0, '', '', 'date', SORT_DESC);
                if (!empty($filelist) && is_array($filelist)) {
                    $filetype = ['attendancesheetdocument' => 0, 'completioncertificatedocument' => 0];
                    foreach ($filelist as $file) {
                        if (!strstr($file['name'], 'specimen')) {
                            if (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('attendancesheetdocument'))) && $filetype['attendancesheetdocument'] == 0) {
                                $filetype['attendancesheetdocument'] = 1;
                            } elseif (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('completioncertificatedocument'))) && $filetype['completioncertificatedocument'] < $nbTrainee) {
                                $filetype['completioncertificatedocument']++;
                            }
                        }
                    }
                    if ($filetype['attendancesheetdocument'] == 1 && $filetype['completioncertificatedocument'] == $nbTrainee) {
                        $forcebuilddoc = 0;
                    } else {
                        $forcebuilddoc = 1;
                    }
                } else {
                    $forcebuilddoc = 1;
                }
                print '<a class="butAction" id="actionButtonSign" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=presend&forcebuilddoc=' . $forcebuilddoc . '&mode=init#formmailbeforetitle' . '"><i class="fas fa-paper-plane"></i> ' .  $langs->trans('SendMail') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToSendEmail', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-paper-plane"></i> ' . $langs->trans('SendMail') . '</span>';
            }

            // Archive
            if ($object->status == $object::STATUS_LOCKED) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=confirm_archive&token=' . newToken() . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</span>';
            }

            // Clone
            print '<span class="butAction" id="actionButtonClone"><i class="fas fa-clone"></i> ' . $langs->trans('Clone') . '</span>';

            // Delete (need delete permission, or if draft, just need create/modify permission)
            print dolGetButtonAction('<i class="fas fa-trash"></i> ' . $langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
        }
        print '</div>';
    }

    if ($action != 'presend') {
        if ($object->element == 'trainingsession') {
            print '<div class="fichecenter"><div class="fichehalfleft">';
            print '<a href="#builddoc"></a>'; // ancre

            // Documents
            $objref = dol_sanitizeFileName($object->ref);
            $dir_files = $object->element . 'document/' . $objref;
            $filedir = $upload_dir . '/' . $dir_files;
            $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element;

            print saturne_show_documents('dolimeet:' . ucfirst($object->element) . 'Document', $dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, 0, '', '', $langs->defaultlang, 0, $object, 0, 'remove_file', ($object->status > $object::STATUS_DRAFT && $nbAttendants > 0), $langs->trans('ObjectMustBeValidatedToGenerate', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) . '<br>' . $mesg);
        }

        print '</div><div class="fichehalfright">';

        $MAXEVENT = 10;

        if ($user->rights->dolimeet->$objectType->read) {
            $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliMeet&object_type=' . $object->element);
        } else {
            $morehtmlcenter = '';
        }
        // List of actions on element
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, $object->element . '@' . $object->module, '', 1, '', $MAXEVENT, '', $morehtmlcenter);

        print '</div></div>';
    }

    // Presend form
    if ($action == 'presend') {
        $langs->load('mails');

        $ref = dol_sanitizeFileName($object->ref);
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        $signatoriesArray = $signatory->fetchSignatory('Trainee', $object->id, $object->type);
        if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
            $nbTrainee = count($signatoriesArray);
        } else {
            $nbTrainee = 0;
        }
        $filelist = dol_dir_list($upload_dir . '/' . $object->element . 'document' . '/' . $ref, 'files', 0, '', '', 'date', SORT_DESC);
        if (!empty($filelist) && is_array($filelist)) {
            $filetype = ['attendancesheetdocument' => 0, 'completioncertificatedocument' => 0];
            foreach ($filelist as $file) {
                if (!strstr($file['name'], 'specimen')) {
                    if (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('attendancesheetdocument'))) && $filetype['attendancesheetdocument'] == 0) {
                        $files[] = $file['fullname'];
                        $filetype['attendancesheetdocument'] = 1;
                    } elseif (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('completioncertificatedocument'))) && $filetype['completioncertificatedocument'] < $nbTrainee) {
                        $files[] = $file['fullname'];
                        $filetype['completioncertificatedocument']++;
                    }
                }
            }
        }

        // Define output language
        $outputlangs = $langs;
        $newlang = '';
        if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
            $newlang = $object->thirdparty->default_lang;
            if (GETPOST('lang_id', 'aZ09')) {
                $newlang = GETPOST('lang_id', 'aZ09');
            }
        }

        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
        print '<div class="clearboth"></div>';
        print '<br>';
        print load_fiche_titre($langs->trans('SendMail'), '', $object->picto);

        print dol_get_fiche_head();

        // Create form for email
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
        $formmail = new FormMail($db);

        $formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
        $formmail->fromtype = (GETPOST('fromtype') ?GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

        if ($formmail->fromtype === 'user') {
            $formmail->fromid = $user->id;
        }

        $formmail->withfrom = 1;

        // Define $liste, a list of recipients with email inside <>.
        $liste = [];
        if (!empty($object->socid) && $object->socid > 0 && !is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
            $object->fetch_thirdparty();
        }
        if (is_object($object->thirdparty)) {
            foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
                $liste[$key] = $value;
            }
        }
        if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
            $listeuser = [];
            $fuserdest = new User($db);

            $result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, ['customsql' => "t.statut = 1 AND t.employee = 1 AND t.email IS NOT NULL AND t.email <> ''"], 'AND', true);
            if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
                foreach ($fuserdest->users as $uuserdest) {
                    $listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
                }
            } elseif ($result < 0) {
                setEventMessages(null, $fuserdest->errors, 'errors');
            }
            if (count($listeuser) > 0) {
                $formmail->withtouser = $listeuser;
                $formmail->withtoccuser = $listeuser;
            }
        }

        //$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
        if (!isset($arrayoffamiliestoexclude)) {
            $arrayoffamiliestoexclude = null;
        }

        // Make substitution in email content
        if ($object) {
            // First we set ->substit (useless, it will be erased later) and ->substit_lines
            $formmail->setSubstitFromObject($object, $langs);
        }
        $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
        $substitutionarray['__TYPE__']    = $langs->trans(ucfirst($object->element));
        $substitutionarray['__THETYPE__'] = $langs->trans('The'. ucfirst($object->element));
        $parameters = ['mode' => 'formemail'];
        complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

        // Find all external contact addresses
        $tmpobject  = $object;
        $contactarr = [];
        $contactarr = $tmpobject->liste_contact(-1, 'external');

        if (is_array($contactarr) && count($contactarr) > 0) {
            require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            $contactstatic = new Contact($db);
            $tmpcompany = new Societe($db);

            foreach ($contactarr as $contact) {
                $contactstatic->fetch($contact['id']);
                // Complete substitution array
                $substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
                $substitutionarray['__CONTACT_LASTNAME_'.$contact['code'].'__'] = $contactstatic->lastname;
                $substitutionarray['__CONTACT_FIRSTNAME_'.$contact['code'].'__'] = $contactstatic->firstname;
                $substitutionarray['__CONTACT_TITLE_'.$contact['code'].'__'] = $contactstatic->getCivilityLabel();

                // Complete $liste with the $contact
                if (empty($liste[$contact['id']])) {	// If this contact id not already into the $liste
                    $contacttoshow = '';
                    if (isset($object->thirdparty) && is_object($object->thirdparty)) {
                        if ($contactstatic->fk_soc != $object->thirdparty->id) {
                            $tmpcompany->fetch($contactstatic->fk_soc);
                            if ($tmpcompany->id > 0) {
                                $contacttoshow .= $tmpcompany->name.': ';
                            }
                        }
                    }
                    $contacttoshow .= $contactstatic->getFullName($outputlangs, 1);
                    $contacttoshow .= ' <' .($contactstatic->email ?: $langs->transnoentitiesnoconv('NoEMail')) . '>';
                    $liste[$contact['id']] = $contacttoshow;
                }
            }
        }

        $formmail->withto              = $liste;
        $formmail->withtofree          = (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1');
        $formmail->withtocc            = $liste;
        $formmail->withtoccc           = getDolGlobalString('MAIN_EMAIL_USECCC');
        $formmail->withtopic           = $outputlangs->trans('SendMailSubject', '__REF__');;
        $formmail->withfile            = 2;
        $formmail->withbody            = 1;
        $formmail->withdeliveryreceipt = 1;
        $formmail->withcancel          = 1;

        // Array of substitutions
        $formmail->substit = $substitutionarray;

        // Array of other parameters
        $formmail->param['action']    = 'send';
        $formmail->param['models']    = 'saturne';
        $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
        $formmail->param['id']        = $object->id;
        $formmail->param['returnurl'] = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element;
        $formmail->param['fileinit']  = $files;

        // Show form
        print $formmail->get_form();

        print dol_get_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
