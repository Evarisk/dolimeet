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
 * \file    view/session/session_card.php
 * \ingroup dolimeet
 * \brief   Page to create/edit/view session.
 */

// Load DoliMeet environment.
if (file_exists('../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../dolimeet.main.inc.php';
} elseif (file_exists('../../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Get module parameters.
$objectType = GETPOST('object_type', 'alpha');

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

// Load DoliMeet libraries.
require_once __DIR__ . '/../../lib/dolimeet_' . $objectType . '.lib.php';
require_once __DIR__ . '/../../class/' . $objectType . '.class.php';

// Global variables definitions.
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page.
saturne_load_langs();

// Get parameters.
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextPage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'card'; // To manage different context of search.
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects.
$className   = ucfirst($objectType);
$object      = new $className($db);
$document    = new SessionDocument($db, $object->element . 'document');
$signatory   = new SaturneSignature($db, 'dolimeet', $object->element);
$extrafields = new ExtraFields($db);
$contact     = new Contact($db);
$contract    = new Contrat($db);

// Initialize view objects.
$form = new Form($db);

$hookmanager->initHooks([$objectType . 'card', 'sessioncard', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array.

// Fetch optionals attributes and labels.
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias.
$searchAll = GETPOST('search_all', 'alpha');
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object.
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

$upload_dir = $conf->dolimeet->multidir_output[$object->entity ?? 1];

// Security check - Protection if external user.
$permissionToRead   = $user->rights->dolimeet->$objectType->read || $user->rights->dolimeet->assignedtome->$objectType;
$permissiontoadd    = $user->rights->dolimeet->$objectType->write;
$permissiontodelete = $user->rights->dolimeet->$objectType->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
saturne_check_access($permissionToRead, null, true);

/*
 * Actions.
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks.
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
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

    // Action clone object.
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

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen.

    if ($action == 'add' && $permissiontoadd) {
        $durationHour = GETPOST('durationhour');
        $durationMin  = GETPOST('durationmin');
        if (empty($durationHour)) {
            $_POST['durationhour'] = 0;
        }
        if (empty($durationMin)) {
            $_POST['durationmin'] = 0;
        }
    }

    require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

    // Actions save_project.
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/edit_project_action.tpl.php';

    // Actions builddoc, forcebuilddoc, remove_file.
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

    // Action to generate pdf from odt file.
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    // Action confirm_lock, confirm_archive.
    require_once __DIR__ . '/../../../saturne/core/tpl/signature/signature_action_workflow.tpl.php';

    // Actions to send emails.
    $triggersendname = strtoupper($object->element) . '_SENTBYMAIL';
    $autocopy        = 'MAIN_MAIL_AUTOCOPY_' . strtoupper($object->element) . '_TO';
    $paramname2      = 'object_type';
    $paramval2       = $object->element;
    include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

/*
 * View.
 */

$title    = $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_DoliMeet';

saturne_header(0, '', $title, $help_url);

// Part to create.
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
        exit;
    }

    print load_fiche_titre($langs->trans('New' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" id="session_form" action="' . $_SERVER['PHP_SELF'] . '?object_type=' . $object->element . '">';
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

    if ($_POST['fk_soc'] == -1) {
        $_POST['fk_soc'] = 0;
    }

    if (!GETPOSTISSET('date_start')) {
        $_POST['date_startyear']  = $now['year'];
        $_POST['date_startmonth'] = $now['mon'];
        $_POST['date_startday']   = $now['mday'];
        $_POST['date_starthour']  = $now['hours'];
        $_POST['date_startmin']   = $now['minutes'];
    }

    if ($object->element == 'meeting' && !GETPOSTISSET('date_end')) {
        $_POST['date_endyear']  = $now['year'];
        $_POST['date_endmonth'] = $now['mon'];
        $_POST['date_endday']   = $now['mday'];
        $_POST['date_endhour']  = $now['hours'] + 1;
        $_POST['date_endmin']   = $now['minutes'];
    }

    $fromType = GETPOSTISSET('fromtype') ? GETPOST('fromtype', 'alpha') : ''; // element type.
    $fromID   = GETPOSTISSET('fromid') ? GETPOST('fromid', 'int') : 0;        //element id.

    if (GETPOST('fk_soc')) {
        $object->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php:0:(fk_soc:=:' . GETPOST('fk_soc') . ')';
        $object->fields['fk_contrat']['type'] = 'integer:Contrat:contrat/class/contrat.class.php:0:(fk_soc:=:' . GETPOST('fk_soc') . ')';

    }

    if (!empty($fromType)) {
        switch ($fromType) {
            case 'thirdparty' :
                $_POST['fk_soc'] = $fromID;
                break;
            case 'project' :
                $_POST['fk_project'] = $fromID;
                break;
            case 'contrat' :
                $_POST['fk_contrat'] = $fromID;
                $contract->fetch($fromID);
                if ($contract->fk_project > 0) {
                    $_POST['fk_project'] = $contract->fk_project;
                }
                if ($contract->fk_soc > 0) {
                    $_POST['fk_soc'] = $contract->fk_soc;
                }
                break;
        }
    }

    $conf->tzuserinputkey = 'tzuser';

    // Common attributes.
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Categories.
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cateArbo = $form->select_all_categories($object->element, '', 'parent', 64, 0, 1);
        print img_picto('', 'category') . $form::multiselectarray('categories', $cateArbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    // Other attributes.
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');

    print '</form>';
}

// Part to edit record.
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans('Modify' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" id="session_form" action="' . $_SERVER['PHP_SELF'] . '?object_type=' . $object->element . '">';
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

    if ($_POST['fk_soc'] == -1) {
        $_POST['fk_soc'] = 0;
    }

    if (GETPOST('fk_soc')) {
        $object->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php:0:(fk_soc:=:' . GETPOST('fk_soc') . ')';
        $object->fields['fk_contrat']['type'] = 'integer:Contrat:contrat/class/contrat.class.php:0:(fk_soc:=:' . GETPOST('fk_soc') . ')';
    }

    $conf->tzuserinputkey = 'tzuser';

    // Common attributes.
    require_once DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

    // Tags-Categories.
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $cateArbo      = $form->select_all_categories($object->element, '', 'parent', 64, 0, 1);
        $categorie     = new Categorie($db);
        $cats          = $categorie->containing($object->id, 'session');
        $arraySelected = [];
        if (is_array($cats)) {
            foreach ($cats as $cat) {
                $arraySelected[] = $cat->id;
            }
        }
        print img_picto('', 'category') . $form::multiselectarray('categories', $cateArbo, $arraySelected, '', 0, 'quatrevingtpercent widthcentpercentminusx');
        print '</td></tr>';
    }

    // Other attributes.
    require_once DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record.
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $res = $object->fetch_optionals();

    $linkback = '<a href="' . dol_buildpath('/dolimeet/view/session/session_list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->element . '">' . $langs->trans('BackToList') . '</a>';
    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object, 'ref', $linkback);

    if ($object->fk_contrat > 0) {
        $contract->fetch($object->fk_contrat);
    }

    $formConfirm = '';

    // Draft confirmation.
    if (($action == 'draft' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ReOpenObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmReOpenObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_setdraft', '', 'yes', 'actionButtonInProgress', 350, 600);
    }
    // Pending signature confirmation.
    if (($action == 'pending_signature' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ValidateObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmValidateObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_validate', '', 'yes', 'actionButtonPendingSignature', 350, 600);
    }
    // Lock confirmation.
    if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
    }

    // Clone confirmation.
    if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        // Define confirmation messages.
        $formQuestionClone = [
            ['type' => 'text',  'name' => 'clone_label',      'label' => $langs->trans('NewLabelForClone', $langs->transnoentities('The' . ucfirst($object->element))), 'value' => $langs->trans('CopyOf') . ' ' . $object->ref, 'size' => 24],
            ['type' => 'radio', 'name' => 'clone_attendants', 'label' => $langs->trans('CloneAttendants'), 'values' => [0 => $langs->trans('Attendants'), 1 => $langs->trans('AttendantsFromContract'), 2 => $langs->trans('None')], 'default' => 0]
        ];
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', $formQuestionClone, 'yes', 'actionButtonClone', 350, 600);
    }

    // Confirmation to delete.
    if ($action == 'delete') {
        $formConfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
    }

    // Call Hook formConfirm.
    $parameters = ['formConfirm' => $formConfirm];
    $resHook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook.
    if (empty($resHook)) {
        $formConfirm .= $hookmanager->resPrint;
    } elseif ($resHook > 0) {
        $formConfirm = $hookmanager->resPrint;
    }

    // Print form confirm.
    print $formConfirm;

    if ($conf->browser->layout == 'phone') {
        $onPhone = 1;
    } else {
        $onPhone = 0;
    }

    switch ($object->element) {
        case 'meeting' :
            $attendantsRole = ['Contributor', 'Responsible'];
            $documentType   = 'MeetingDocument';
            break;
        case 'trainingsession' :
            $attendantsRole = ['Trainee', 'SessionTrainer'];
            $documentType   = 'AttendanceSheetDocument';
            break;
        case 'audit' :
            $attendantsRole = ['Auditee', 'Auditor'];
            $documentType   = 'AuditDocument';
            break;
        default :
            $attendantsRole = ['Attendant'];
            $documentType   = '';
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
    unset($object->fields['label']);      // Hide field already shown in banner.
    unset($object->fields['fk_project']); // Hide field already shown in banner.
    unset($object->fields['fk_soc']);     // Hide field already shown in banner.
    unset($object->fields['fk_contrat']); // Hide field already shown in banner.

    // Common attributes.
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Categories
    if ($conf->categorie->enabled) {
        print '<tr><td class="valignmiddle">' . $langs->trans('Categories') . '</td><td>';
        print $form->showCategories($object->id, 'session', 1);
        print '</td></tr>';
    }

    if ($objectType == 'trainingsession' && isModEnabled('digiquali') && $object->fk_contrat > 0) {
        $contract->fetchObjectLinked('digiquali_control');

        print '<tr><td class="valignmiddle">' . $langs->trans('SatisfactionSurvey') . '</td><td>';
        print '<a onclick="preventDefault()" target="_blank" href="../../../digiquali/view/control/control_card?action=create&fromtype=contrat&fromid=' . $object->fk_contrat . '&fk_sheet=' . $conf->global->DOLIMEET_SATISFACTION_SURVEY_SHEET . '"><button class="butAction">' . $langs->trans('Create') . '</button></a>';
        print '</td></tr>';
    }

    // Other attributes. Fields from hook formObjectOptions and Extrafields.
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    // Buttons for actions.
    if ($action != 'presend') {
        print '<div class="tabsAction">';
        $parameters = [];
        $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook.
        if ($resHook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($resHook) && $permissiontoadd) {
            // Modify.
            $displayButton = $onPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
            if ($object->status == $object::STATUS_DRAFT) {
                print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=edit' . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

            // Validate.
            $displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
            if ($object->status == $object::STATUS_DRAFT && $nbAttendants > 0) {
                print '<span class="butAction" id="actionButtonPendingSignature">' . $displayButton . '</span>';
            } elseif ($object->status < $object::STATUS_DRAFT) {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) . '<br>' . $mesg) . '">' . $displayButton . '</span>';
            }

            // ReOpen.
            $displayButton = $onPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('ReOpenDoli');
            if ($object->status == $object::STATUS_VALIDATED) {
                print '<span class="butAction" id="actionButtonInProgress">' . $langs->trans('ReOpenDoli') . '</span>';
            } elseif ($object->status > $object::STATUS_VALIDATED) {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $langs->trans('ReOpenDoli') . '</span>';
            }

            // Sign
            $displayButton = $onPhone ? '<i class="fas fa-signature fa-2x"></i>' : '<i class="fas fa-signature"></i>' . ' ' . $langs->trans('Sign');
            if ($object->status == $object::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/custom/saturne/view/saturne_attendants.php?id=' . $object->id . '&module_name=DoliMeet&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=simple', 3) . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

            // Lock.
            $displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('Lock');
            if ($object->status == $object::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element) && $nbAttendants > 0) {
                print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AllSignatoriesMustHaveSigned', $langs->transnoentities('The' . ucfirst($object->element))) . '<br>' . $mesg) . '">' . $displayButton . '</span>';
            }

            // Send mail.
            $displayButton = $onPhone ? '<i class="fas fa-paper-plane fa-2x"></i>' : '<i class="fas fa-paper-plane"></i>' . ' ' . $langs->trans('SendMail') . ' ';
            if ($object->status == $object::STATUS_LOCKED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                $signatoriesArray = $signatory->fetchSignatory('Trainee', $object->id, $object->type);
                if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
                    $nbTrainee = count($signatoriesArray);
                } else {
                    $nbTrainee = 0;
                }
                $fileList = dol_dir_list($upload_dir . '/' . $object->element . 'document' . '/' . $object->ref, 'files', 0, '', '', 'date', SORT_DESC);
                if (!empty($fileList) && is_array($fileList)) {
                    $fileType = ['attendancesheetdocument' => 0, 'completioncertificatedocument' => 0];
                    foreach ($fileList as $file) {
                        if (!strstr($file['name'], 'specimen')) {
                            if (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('attendancesheetdocument'))) && $fileType['attendancesheetdocument'] == 0) {
                                $fileType['attendancesheetdocument'] = 1;
                            } elseif (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('completioncertificatedocument'))) && $fileType['completioncertificatedocument'] < $nbTrainee) {
                                $fileType['completioncertificatedocument']++;
                            }
                        }
                    }
                    if ($fileType['attendancesheetdocument'] == 1 && $fileType['completioncertificatedocument'] == $nbTrainee) {
                        $forceBuildDoc = 0;
                    } else {
                        $forceBuildDoc = 1;
                    }
                } else {
                    $forceBuildDoc = 1;
                }
                print '<a class="butAction" id="actionButtonSign" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=presend&forcebuilddoc=' . $forceBuildDoc . '&mode=init#formmailbeforetitle' . '">' .  $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToSendEmail', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

            // Archive.
            $displayButton = $onPhone ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->trans('Archive');
            if ($object->status == $object::STATUS_LOCKED) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=confirm_archive&token=' . newToken() . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

            // Clone.
            $displayButton = $onPhone ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->trans('ToClone');
            print '<span class="butAction" id="actionButtonClone">' . $displayButton . '</span>';

            // Delete (need delete permission, or if draft, just need create/modify permission).
            $displayButton = $onPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
            print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
        }
        print '</div>';
    }

    if ($action != 'presend') {
        if ($object->element == 'trainingsession') {
            print '<div class="fichecenter"><div class="fichehalfleft">';
            print '<a href="#builddoc"></a>'; // ancre.

            // Documents.
            $objRef    = dol_sanitizeFileName($object->ref);
            $dirFiles  = $object->element . 'document/' . $objRef;
            $fileDir   = $upload_dir . '/' . $dirFiles;
            $urlSource = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element;

            print saturne_show_documents('dolimeet:' . ucfirst($object->element) . 'Document', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, 0, '', '', $langs->defaultlang, 0, $object, 0, 'remove_file', ($object->status > $object::STATUS_DRAFT && $nbAttendants > 0), $langs->trans('ObjectMustBeValidatedToGenerate', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) . '<br>' . $mesg);
        }

        print '</div><div class="fichehalfright">';

        $moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DoliMeet&object_type=' . $object->element);

        // List of actions on element.
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formActions = new FormActions($db);
        $formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

        print '</div></div>';
    }

    // Presend form.
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
        $fileList = dol_dir_list($upload_dir . '/' . $object->element . 'document' . '/' . $ref, 'files', 0, '', '', 'date', SORT_DESC);
        if (!empty($fileList) && is_array($fileList)) {
            $fileType = ['attendancesheetdocument' => 0, 'completioncertificatedocument' => 0];
            foreach ($fileList as $file) {
                if (!strstr($file['name'], 'specimen')) {
                    if (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('attendancesheetdocument'))) && $fileType['attendancesheetdocument'] == 0) {
                        $files[] = $file['fullname'];
                        $fileType['attendancesheetdocument'] = 1;
                    } elseif (strstr($file['name'], str_replace(' ', '_', $langs->transnoentities('completioncertificatedocument'))) && $fileType['completioncertificatedocument'] < $nbTrainee) {
                        $files[] = $file['fullname'];
                        $fileType['completioncertificatedocument']++;
                    }
                }
            }
        }

        // Define output language.
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

        // Create form for email.
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
                // Complete substitution array.
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

        // Array of substitutions.
        $formmail->substit = $substitutionarray;

        // Array of other parameters.
        $formmail->param['action']    = 'send';
        $formmail->param['models']    = 'saturne';
        $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
        $formmail->param['id']        = $object->id;
        $formmail->param['returnurl'] = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element;
        $formmail->param['fileinit']  = $files;

        // Show form.
        print $formmail->get_form();

        print dol_get_fiche_end();
    }
}

// End of page.
llxFooter();
$db->close();
