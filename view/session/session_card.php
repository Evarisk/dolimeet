<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once __DIR__ . '/../../lib/dolimeet_' . $objectType . '.lib.php';
require_once __DIR__ . '/../../lib/dolimeet_functions.lib.php';

require_once __DIR__ . '/../../class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../core/modules/dolimeet/mod_' . $objectType . '_standard.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(['dolimeet@dolimeet', 'other@saturne']);

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'card'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname        = ucfirst($objectType);
$object           = new $classname($db);
$sessiondocument  = new SessionDocument($db, $objectType);
//$signatory        = new Signature($db);
$extrafields      = new ExtraFields($db);
$thirdparty       = new Societe($db);
$contact          = new Contact($db);
$mod              = 'DOLIMEET_'. strtoupper($object->element) .'_ADDON';
$refMod           = new $conf->global->$mod();

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$object->element . 'card', 'saturnecard', 'globalcard']); // Note that conf->hooks_modules contains array

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

// @todo a finir
$upload_dir = $conf->dolimeet->multidir_output[$object->entity ?? 1];

// Security check - Protection if external user
$permissiontoread   = $user->rights->dolimeet->$objectType->read;
$permissiontoadd    = $user->rights->dolimeet->$objectType->write;
$permissiontodelete = $user->rights->dolimeet->$objectType->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
if (empty($conf->dolimeet->enabled) || !$permissiontoread) {
    accessforbidden();
}

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

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

    // Action to build doc
    if ($action == 'builddoc' && $permissiontoadd) {
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

        $model = GETPOST('model', 'alpha');

        $moreparams['object'] = $object;
        $moreparams['user']   = $user;

        // @todo pertinence ??
        if (preg_match('/completioncertificate/', GETPOST('model'))) {
            $signatoriesList = $signatory->fetchSignatories($object->id, $object->type);
            if (!empty($signatoriesList)) {
                foreach ($signatoriesList as $objectSignatory) {
                    if ($objectSignatory->role != 'TRAININGSESSION_SESSION_TRAINER') {
                        $moreparams['attendant'] = $objectSignatory;
                        $result = $sessiondocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
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
                    header('Location: ' . $urltoredirect . '#builddoc');
                    exit;
                }
            }
        }

        $result = $sessiondocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        if ($result <= 0) {
            setEventMessages($sessiondocument->error, $sessiondocument->errors, 'errors');
            $action = '';
        } elseif (empty($donotredirect)) {
            setEventMessages($langs->trans('FileGenerated') . ' - ' . $sessiondocument->last_main_doc, []);
            $urltoredirect = $_SERVER['REQUEST_URI'];
            $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
            $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
            header('Location: ' . $urltoredirect . '#builddoc');
            exit;
        }
    }

    if ($action == 'remove_file') {
        if (!empty($upload_dir)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            $langs->load('other');
            $filetodelete = GETPOST('file', 'alpha');
            $file = $upload_dir . '/' . $filetodelete;
            $ret = dol_delete_file($file, 0, 0, 0, $object);
            if ($ret) setEventMessages($langs->trans('FileWasRemoved', $filetodelete), null, 'mesgs');
            else setEventMessages($langs->trans('ErrorFailToDeleteFile', $filetodelete), null, 'errors');

            // Make a redirect to avoid to keep the remove_file into the url that create side effects
            $urltoredirect = $_SERVER['REQUEST_URI'];
            $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
            $urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

            header('Location: ' . $urltoredirect);
            exit;
        } else {
            setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
        }
    }

    // Actions to send emails
    $triggersendname = 'DOLIMEET_' . strtoupper($object->element) . '_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_' . strtoupper($object->element) . '_TO';
    $trackid = $object->element . $object->id;

    // Action clone object
    if ($action == 'confirm_clone' && $confirm == 'yes') {
        $options = array();
        $object->ref = $refMod->getNextValue($object);
        $result = $object->createFromClone($user, $object->id, $options);

        if ($result > 0) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $result);
            exit;
        }
    }

    // Action to set status STATUS_ARCHIVED
    if ($action == 'setArchived' && $permissiontoadd) {
        $object->fetch($id);
        if (!$error) {
            $result = $object->setArchived($user);
            if ($result > 0) {
                // Set Archived OK
                $urltogo = str_replace('__ID__', $result, $backtopage);
                $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
                header('Location: ' . $urltogo);
                exit;
            } elseif (!empty($object->errors)) { // Set Archived KO
                setEventMessages('', $object->errors, 'errors');
            } else {
                setEventMessages($object->error, [], 'errors');
            }
        }
    }
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

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Categories
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cate_arbo = $form->select_all_categories('session', '', 'parent', 64, 0, 1);
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
        $cate_arbo = $form->select_all_categories($object->element, '', 'parent', 64, 0, 1);
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
    saturne_fiche_head($object, 'card', $title);
    saturne_banner_tab($object);

    $formconfirm = '';

    // setDraft confirmation
    if (($action == 'setDraft' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ReOpenObject', ucfirst($object->element)), $langs->trans('ConfirmReOpenObject', ucfirst($object->element), $object->ref), 'confirm_setdraft', '', 'yes', 'actionButtonInProgress', 350, 600);
    }
    // setPendingSignature confirmation
    if (($action == 'setPendingSignature' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ValidateObject', ucfirst($object->element)), $langs->trans('ConfirmValidateObject', ucfirst($object->element), $object->ref), 'confirm_validate', '', 'yes', 'actionButtonPendingSignature', 350, 600);
    }
    // setLocked confirmation
    if (($action == 'setLocked' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('LockObject', ucfirst($object->element)), $langs->trans('ConfirmLockObject', ucfirst($object->element), $object->ref), 'confirm_setLocked', '', 'yes', 'actionButtonLock', 350, 600);
    }
    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('DeleteObject', ucfirst($object->element)), $langs->trans('ConfirmDeleteObject', ucfirst($object->element)), 'confirm_delete', '', 'yes', 1);
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

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    // @todo inspiration tickt message
//    print '<tr><td class="titlefield">';
//    print $langs->trans('Content');
//    print '</td>';
//    print '<td>';
//    print '<div class="longmessagecut" style="min-height: 150px">';
//    print dol_htmlentitiesbr($object->content); //wrap -> middle?
//    print '</div>';
//    print '</td></tr>';

//    if ($object->type == 'trainingsession') {
//        $duration_hours = floor($object->duration / 60);
//        $duration_minutes = ($object->duration % 60);
//
//        print '<tr><td class="titlefield">';
//        print $langs->trans('Duration');
//        print '</td>';
//        print '<td>';
//        print $duration_hours . ' ' . $langs->trans('Hour(s)') . ' ' . $duration_minutes . ' ' . $langs->trans('Minute(s)');
//        print '</td></tr>';
//    }

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
                print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=edit' . '">' . $langs->trans('Modify') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft')) . '">' . $langs->trans('Modify') . '</span>';
            }

            // Validate
            if ($object->status == $object::STATUS_DRAFT) {
                print '<span class="butAction" id="actionButtonPendingSignature"  href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=setPendingSignature' . '">' . $langs->trans('Validate') . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraftToValidate')) . '">' . $langs->trans('Validate') . '</span>';
            }

            // ReOpen
            if ($object->status == $object::STATUS_VALIDATED) {
                print '<span class="butAction" id="actionButtonInProgress" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=setDraft' . '">' . $langs->trans('ReOpenDoli') . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated')) . '">' . $langs->trans('ReOpenDoli') . '</span>';
            }

            // Sign
//            if ($object->status == $object::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
//                print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/custom/dolisirh/view/timesheet/timesheet_attendants.php?id=' . $object->id, 3) . '">' . $langs->trans('Sign') . '</a>';
//            } else {
//                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign')) . '">' . $langs->trans('Sign') . '</span>';
//            }

            // Lock
//            if ($object->status == $object::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
//                print '<span class="butAction" id="actionButtonLock">' . $langs->trans('Lock') . '</span>';
//            } else {
//                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AllSignatoriesMustHaveSigned')) . '">' . $langs->trans('Lock') . '</span>';
//            }

            // Clone
            // Check digi

            // Send
            //@TODO changer le send to
            //print '<a class="' . ($object->status == $object::STATUS_LOCKED ? 'butAction' : 'butActionRefused classfortooltip') . '" id="actionButtonSign" title="' . dol_escape_htmltag($langs->trans("ObjectMustBeLockedToSendEmail")) . '" href="' . ($object->status == $object::STATUS_LOCKED ? ($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle&sendto=' . $allLinks['LabourInspectorSociety']->id[0]) : '#') . '">' . $langs->trans('SendMail') . '</a>';

            // Archive
            if ($object->status == $object::STATUS_LOCKED  && !empty(dol_dir_list($upload_dir . '/timesheetdocument/' . dol_sanitizeFileName($object->ref)))) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=setArchived' . '">' . $langs->trans('Archive') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedGenerated')) . '">' . $langs->trans('Archive') . '</span>';
            }

            // Delete (need delete permission, or if draft, just need create/modify permission)
            print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
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

            //$defaultmodel = 'controldocument_odt';
            //$title = $langs->trans('WorkUnitDocument');

            print saturne_show_documents('dolimeet:' . ucfirst($object->element) . 'Document', $dir_files, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, 0, '', '', $langs->defaultlang, 0, $object, 0, 'remove_file', (($object->status > $object::STATUS_DRAFT) ? 1 : 0), $langs->trans('ObjectMustBeValidatedToGenerated'));
        }

        print '</div><div class="fichehalfright">';

        $MAXEVENT = 10;

        $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliMeet&object_type=' . $object->element);

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, $object->element . '@' . $object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

        print '</div></div>';
    }
}

// End of page
llxFooter();
$db->close();