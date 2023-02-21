<?php
/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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
 *  \file       view/saturne_attendants.php
 *  \ingroup    saturne
 *  \brief      Tab of attendants on generic element
 */

// Load DoliMeet environment
if (file_exists('../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Get module parameters
$moduleName = GETPOST('module_name', 'alpha');
$objectType = GETPOST('object_type', 'alpha');

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
if (isModEnabled('societe')) {
    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
}

require_once __DIR__ . '/../class/saturnesignature.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'signature'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname = ucfirst($objectType);
$object    = new $classname($db);
$signatory = new SaturneSignature($db);
$usertmp   = new User($db);
if (isModEnabled('societe')) {
    $thirdparty = new Societe($db);
    $contact    = new Contact($db);
}

// Initialize view objects
$form        = new Form($db);
$formcompany = new FormCompany($db);

$hookmanager->initHooks([$objectType . 'signature', $object->element . 'signature', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
$permissiontoread   = $user->rights->$moduleNameLowerCase->$objectType->read || $user->rights->$moduleNameLowerCase->assigntome->$objectType;
$permissiontoadd    = $user->rights->$moduleNameLowerCase->$objectType->write;
$permissiontodelete = $user->rights->$moduleNameLowerCase->$objectType->delete;
saturne_check_access($permissiontoread);

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
    if ($cancel && !empty($backtopage)) {
        header('Location: ' . $backtopage);
        exit;
    }

    // Action to add attendant
    if ($action == 'add_attendant') {
        $attendantRole        = GETPOST('attendant_role');
        $attendantTypeUser    = GETPOST('attendant' . $attendantRole . 'user');
        $attendantTypeContact = GETPOST('attendant' . $attendantRole . 'contact');

        if ($attendantTypeUser < 0 && $attendantTypeContact < 0) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('Attendant')), [], 'errors');
        }

        $result = $signatory->setSignatory($object->id, $object->element, ($attendantTypeUser > 0 ? 'user' : 'socpeople'), [($attendantTypeUser > 0 ? $attendantTypeUser : $attendantTypeContact)], $attendantRole, 1);

        if ($result > 0) {
            // Creation attendant OK
            if ($attendantTypeUser > 0) {
                $usertmp = $user;
                $usertmp->fetch($attendantTypeUser);
                setEventMessages($langs->trans('AddAttendantMessage', $langs->trans($attendantRole) . ' ' . $usertmp->getFullName($langs, 1)), []);
            } else {
                $contact->fetch($attendantTypeContact);
                setEventMessages($langs->trans('AddAttendantMessage', $langs->trans($attendantRole) . ' ' . $contact->getFullName($langs, 1)), []);
            }
        } elseif (!empty($signatory->errors)) {
            // Creation attendant KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }

    // Action to add signature
    if ($action == 'add_signature') {
        $data        = json_decode(file_get_contents('php://input'), true);
        $signatoryID = GETPOST('signatoryID');

        $signatory->fetch($signatoryID);
        $signatory->signature      = $data['signature'];
        $signatory->signature_date = dol_now('tzuser');

        $result = $signatory->update($user);

        if ($result > 0) {
            // Creation signature OK
            $signatory->setSigned($user);
            setEventMessages($langs->trans('SignAttendantMessage', $langs->trans($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), []);
        } elseif (!empty($signatory->errors)) {
            // Creation signature KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }

    // Action to set status STATUS_ABSENT
    if ($action == 'set_absent') {
        $signatoryID = GETPOST('signatoryID');

        $signatory->fetch($signatoryID);

        $result = $signatory->setAbsent($user, 0);

        if ($result > 0) {
            // set absent OK
            setEventMessages($langs->trans('AbsentAttendantMessage', $langs->trans($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), []);
        } elseif (!empty($signatory->errors)) {
            // Creation absent KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }

    // Action to send Email
    if ($action == 'send') {
        $signatoryID = GETPOST('signatoryID');
        $signatory->fetch($signatoryID);

        if ( ! $error) {
            $langs->load('mails');

            if (!dol_strlen($signatory->email)) {
                if ($signatory->element_type == 'user') {
                    $usertmp = $user;
                    $usertmp->fetch($signatory->element_id);
                    if (dol_strlen($usertmp->email)) {
                        $signatory->email = $usertmp->email;
                        $signatory->update($user, true);
                    }
                } else if ($signatory->element_type == 'socpeople') {
                    $contact->fetch($signatory->element_id);
                    if (dol_strlen($contact->email)) {
                        $signatory->email = $contact->email;
                        $signatory->update($user, true);
                    }
                }
            }

            $sendto = $signatory->email;

            if (dol_strlen($sendto) && ( ! empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

                $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
                $url  = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $signatory->signature_url  . '&type=' . $objectType, 3);

                $message = $langs->trans('SignatureEmailMessage') . ' ' . $url;
                $subject = $langs->trans('SignatureEmailSubject') . ' ' . $object->ref;

                // Create form object
                // Send mail (substitutionarray must be done just before this)
                $mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), '', '', 0, -1, '', '', '', '', 'mail');

                if ($mailfile->error) {
                    setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                } else {
                    if ( ! empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
                        $result = $mailfile->sendfile();
                        if ($result) {
                            $signatory->last_email_sent_date = dol_now('tzuser');
                            $signatory->update($user, true);
                            $signatory->setPending($user, false);
                            setEventMessages($langs->trans('SendEmailAt') . ' ' . $signatory->email, array());
                            // This avoid sending mail twice if going out and then back to page
                            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                            exit;
                        } else {
                            $langs->load('other');
                            $mesg = '<div class="error">';
                            if ($mailfile->error) {
                                $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                                $mesg .= '<br>' . $mailfile->error;
                            } else {
                                $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                            }
                            $mesg .= '</div>';
                            setEventMessages($mesg, null, 'warnings');
                        }
                    } else {
                        setEventMessages($langs->trans('ErrorSetupEmail'), '', 'errors');
                    }
                }
            } else {
                $langs->load('errors');
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailTo')), null, 'warnings');
                dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
            }
        } else {
            // Mail sent KO
            if ( ! empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
            else setEventMessages($signatory->error, null, 'errors');
        }
    }

    // Action to delete attendant
    if ($action == 'delete_attendant') {
        $signatoryToDeleteID = GETPOST('signatoryID');
        $signatory->fetch($signatoryToDeleteID);

        $result = $signatory->setDeleted($user, 0);

        if ($result > 0) {
            setEventMessages($langs->trans('DeleteAttendantMessage', $langs->trans($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), []);
        } elseif (!empty($signatory->errors)) {
            // Deletion attendant KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }
}

/*
*	View
*/

$title   = $langs->trans('Attendants') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_' . $moduleName;
$morejs  = ['/dolimeet/js/signature-pad.min.js'];

saturne_header(0,'', $title, $helpUrl, '', 0, 0, $morejs);

if ($id > 0 || !empty($ref) && empty($action)) {
    $object->fetch_optionals();

    saturne_get_fiche_head($object, 'attendants', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';

    if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) : ?>
        <div class="wpeo-notice notice-warning">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('BeCareful') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) ?></div>
            </div>
            <a class="butAction" href="<?php echo dol_buildpath('/custom/' . $moduleNameLowerCase . '/view/session/session_card.php?id=' . $id . '&object_type=' . $object->element, 1); ?>"><?php echo $langs->trans('GoToValidate', $langs->transnoentities('The' . ucfirst($object->element))) ?></a>;
        </div>
    <?php endif; ?>
        <div class="noticeSignatureSuccess wpeo-notice notice-success<?php echo (($signatory->checkSignatoriesSignatures($object->id, $object->element) && $object->status < $object::STATUS_LOCKED && $permissiontoadd) ? '' : ' hidden') ?>">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('AddSignatureSuccess') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans('AddSignatureSuccessText') . GETPOST('signature_id')?></div>
            </div>
            <?php if ($signatory->checkSignatoriesSignatures($object->id, $object->element) && $object->status < $object::STATUS_LOCKED && $permissiontoadd) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/custom/' . $moduleNameLowerCase . '/view/session/session_card.php?id=' . $id . '&object_type=' . $object->element . '">' . $langs->trans('GoToLock', $langs->transnoentities('The' . ucfirst($object->element))) . '</a>';
            } ?>
        </div>
    <?php
    print '</div>';

    print '<div class="signatures-container">';

    $zone = 'private';

    switch ($object->element) {
        case 'meeting' :
            $attendantsRole = ['Responsible', 'Contributor'];
            break;
        case 'trainingsession' :
            $attendantsRole = ['SessionTrainer', 'Trainee'];
            break;
        case 'audit' :
            $attendantsRole = ['Auditor'];
            break;
        default :
            $attendantsRole = ['Attendant'];
    }

    foreach ($attendantsRole as $attendantRole) {
        $signatories = $signatory->fetchSignatory($attendantRole, $object->id, $object->element);

        print load_fiche_titre($langs->trans('Attendants') . ' - ' . $langs->trans($attendantRole), '', '');

        if (is_array($signatories) && !empty($signatories) && $signatories > 0) {
            print '<table class="border centpercent tableforfield">';

            print '<tr class="liste_titre">';
            print '<td>' . img_picto('', 'company') . ' ' . $langs->trans('ThirdParty') . '</td>';
            print '<td>' . img_picto('', 'user') . ' ' . $langs->trans('User') . ' | ' . img_picto('', 'contact') . ' ' . $langs->trans('Contacts') . '</td>';
            print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
            print '<td>' . $langs->trans('SendMailDate') . '</td>';
            print '<td>' . $langs->trans('SignatureDate') . '</td>';
            print '<td class="center">' . $langs->trans('Status') . '</td>';
            print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
            print '<td class="center">' . $langs->trans('Signature') . '</td>';
            print '</tr>';

            $alreadyAddedUsers = [];
            foreach ($signatories as $element) {
                print '<tr class="oddeven"><td class="minwidth200">';
                if ($element->element_type == 'socpeople') {
                    $thirdparty->fetch($contact->fk_soc);
                    print $thirdparty->getNomUrl(1);
                }
                print '</td><td>';
                if ($element->element_type == 'user') {
                    $usertmp->fetch($element->element_id);
                    print $usertmp->getNomUrl(1);
                } else {
                    $contact->fetch($element->element_id);
                    print $contact->getNomUrl(1);
                }
                print '</td><td class="center">';
                if ($object->status == $object::STATUS_VALIDATED && $element->status != $element::STATUS_ABSENT && $permissiontoadd) {
                    $signatureUrl = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&object_type=' . $object->element, 3);
                    print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
                }
                print '</td><td>';
                print dol_print_date($element->last_email_sent_date, 'dayhour');
                print '</td><td>';
                print dol_print_date($element->signature_date, 'dayhour');
                print '</td><td class="center">';
                print $element->getLibStatut(5);
                print '</td>';
                print '<td class="center">';
                if ($permissiontoadd && $object->status < $object::STATUS_LOCKED) {
                    require __DIR__ . '/../core/tpl/signature/signature_action_view.tpl.php';
                }
                print '</td>';
                print '<td class="center">';
                if ($element->signature != $langs->transnoentities('FileGenerated') && $permissiontoread) {
                    require __DIR__ . '/../core/tpl/signature/signature_view.tpl.php';
                }
                print '</td>';
                print '</tr>';
                $alreadyAddedUsers[$element->element_id] = $element->element_id;
            }

            if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="add_attendant">';
                print '<input type="hidden" name="attendant_role" value="' . $attendantRole . '">';
                if (!empty($backtopage)) {
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                }

                print '<tr class="oddeven"><td>';
                $selectedCompany = GETPOSTISSET('newcompany' . $attendantRole) ? GETPOST('newcompany' . $attendantRole, 'int') : (empty($object->socid) ?  0 : $object->socid);
                $moreparam = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element);
                $moreparam .= '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreparam);
                $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany' . $attendantRole, '', 0, $moreparam, 'minwidth300imp');
                print '</td>';
                print '<td class=minwidth400">';
                if ($selectedCompany <= 0) {
                    print img_picto('', 'user', 'class="paddingright pictofixedwidth"') . $form->select_dolusers('', 'attendant' . $attendantRole . 'user', 1, '', 0, '', '', $conf->entity) . '<br>';
                }
                print img_object('', 'contact', 'class="pictofixedwidth"') . $form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), GETPOST('contactID'), 'attendant' . $attendantRole . 'contact', 1, $alreadyAddedUsers, '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth300');
                if (!empty($selectedCompany) && $selectedCompany > 0 && $user->rights->societe->creer) {
                    $newcardbutton = '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid=' . $selectedCompany . '&action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element) . '&newcompany' . $attendantRole . '=' . GETPOST('newcompany' . $attendantRole) . '&contactID=&#95;&#95;ID&#95;&#95;') . '" title="' . $langs->trans('NewContact') . '"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
                    print $newcardbutton;
                }
                print '</td><td class="center">';
                print '-';
                print '</td><td>';
                print '-';
                print '</td><td>';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i></button>';
                print '<td class="center">';
                print '-';
                print '</td></tr>';
                print '</table>';
                print '</form>';
            }
        } else {
            print '<div class="opacitymedium">' . $langs->trans('NoAttendants') . '</div>';

            if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
                print '<br><table class="border centpercent tableforfield">';

                print '<tr class="liste_titre">';
                print '<td>' . img_picto('', 'company') . ' ' . $langs->trans('ThirdParty') . '</td>';
                print '<td>' . img_picto('', 'user') . ' ' . $langs->trans('User') . ' | ' . img_picto('', 'contact') . ' ' . $langs->trans('Contacts') . '</td>';
                print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
                print '<td>' . $langs->trans('SendMailDate') . '</td>';
                print '<td>' . $langs->trans('SignatureDate') . '</td>';
                print '<td class="center">' . $langs->trans('Status') . '</td>';
                print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
                print '<td class="center">' . $langs->trans('Signature') . '</td>';
                print '</tr>';

                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="add_attendant">';
                print '<input type="hidden" name="attendant_role" value="' . $attendantRole . '">';
                if (!empty($backtopage)) {
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                }

                print '<tr class="oddeven"><td>';
                $selectedCompany = GETPOSTISSET('newcompany' . $attendantRole) ? GETPOST('newcompany' . $attendantRole, 'int') : (empty($object->socid) ?  0 : $object->socid);
                $moreparam = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element);
                $moreparam .= '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreparam);
                $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany' . $attendantRole, '', 0, $moreparam, 'minwidth300imp');
                print '</td>';
                print '<td class=minwidth400">';
                if ($selectedCompany <= 0) {
                    print img_picto('', 'user', 'class="paddingright pictofixedwidth"') . $form->select_dolusers('', 'attendant' . $attendantRole . 'user', 1, '', 0, '', '', $conf->entity) . '<br>';
                }
                print img_object('', 'contact', 'class="pictofixedwidth"') . $form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), GETPOST('contactID'), 'attendant' . $attendantRole . 'contact', 1, $alreadyAddedUsers, '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth300');
                if (!empty($selectedCompany) && $selectedCompany > 0 && $user->rights->societe->creer) {
                    $newcardbutton = '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid=' . $selectedCompany . '&action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element) . '&newcompany' . $attendantRole . '=' . GETPOST('newcompany' . $attendantRole) . '&contactID=&#95;&#95;ID&#95;&#95;') . '" title="' . $langs->trans('NewContact') . '"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
                    print $newcardbutton;
                }
                print '</td><td class="center">';
                print '-';
                print '</td><td>';
                print '-';
                print '</td><td>';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i></button>';
                print '<td class="center">';
                print '-';
                print '</td></tr>';
                print '</table>';
                print '</form>';
            }
        }
    }
    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
