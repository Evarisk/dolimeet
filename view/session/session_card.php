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
 *   	\file       view/session_card.php
 *		\ingroup    dolimeet
 *		\brief      Page to create/edit/view session
 */

// Load DoliMeet environment
if (file_exists('../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

require_once __DIR__ . '/../../class/audit.class.php';
require_once __DIR__ . '/../../core/modules/dolimeet/mod_audit_standard.php';
require_once __DIR__ . '/../../lib/dolimeet_session.lib.php';
require_once __DIR__ . '/../../lib/dolimeet.lib.php';
require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

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
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'auditcard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$objectType  = GETPOST('object_type', 'alpha');


// Initialize technical objects
$object      = new Session($db, $objectType);
$signatory   = new Signature($db);
$extrafields = new ExtraFields($db);
$usertmp     = new User($db);
$project     = new Project($db);
$thirdparty  = new Societe($db);
$contact     = new Contact($db);
$ecmfile     = new EcmFiles($db);
//$mod         = 'DOLIMEET_'. strtoupper($objectType) .'_ADDON';
//$refMod      = new $conf->global->$mod();

// Initialize view objects
$form        = new Form($db);
$formother   = new FormOther($db);
$formfile    = new FormFile($db);
$formproject = new FormProjets($db);

$hookmanager->initHooks([$objectType . 'card', 'globalcard']); // Note that conf->hooks_modules contains array

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
$upload_dir = $conf->dolimeet->multidir_output[isset($object->entity) ? $object->entity : 1];

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

    $backurlforlist = dol_buildpath('/dolimeet/view/session/session_list.php', 1) . '?object_type=' . $objectType;

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/dolimeet/view/session/session_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__') . '&object_type=' . $objectType;
            }
        }
    }

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

//    // Action to update record
//    if ($action == 'update' && $permissiontoadd) {
//        $society_id = GETPOST('fk_soc');
//        $content = GETPOST('content', 'restricthtml');
//        $label = GETPOST('label');
//        $contrat_id = GETPOST('fk_contrat');
//        $project_id = GETPOST('fk_project');
//        $date_start = dol_mktime(GETPOST('date_starthour', 'int'), GETPOST('date_startmin', 'int'), 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
//        $date_end = dol_mktime(GETPOST('date_endhour', 'int'), GETPOST('date_endmin', 'int'), 0, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));
//        $durationh = GETPOST('durationh') ?: 0;
//        $durationm = GETPOST('durationm') ?: 0;
//
//        $duration_minutes = $durationh * 60 + $durationm;
//
//        $object->label = $label;
//        $object->fk_soc = $society_id;
//        $object->content = $content;
//        $object->fk_contrat = $contrat_id;
//        $object->fk_project = $project_id;
//        $object->date_start = $date_start;
//        $object->date_end = $date_end;
//        $object->duration = $duration_minutes;
//
//        $object->fk_user_creat = $user->id ? $user->id : 1;
//        if (!$error) {
//            $result = $object->update($user, false);
//            if ($result > 0) {
//                $categories = GETPOST('categories', 'array');
//                $object->setCategories($categories);
//                $urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
//                $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
//                header('Location: ' . $urltogo);
//                exit;
//            } else {
//                if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
//                else  setEventMessages($object->error, null, 'errors');
//            }
//        } else {
//            $action = 'edit';
//        }
//    }

    if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes') {
        $object->setStatusCommon($user, -1);
        $urltogo = DOL_URL_ROOT . '/custom/dolimeet/view/' . $objectType . '/' . $objectType . '_list.php';
        header('Location: ' . $urltogo);
        exit;
    }

    // Action to build doc
    if ($action == 'builddoc' && $permissiontoadd) {
        $outputlangs = $langs;
        $newlang = '';

        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        // To be sure vars is defined
        if (empty($hidedetails)) $hidedetails = 0;
        if (empty($hidedesc)) $hidedesc = 0;
        if (empty($hideref)) $hideref = 0;
        if (empty($moreparams)) $moreparams = null;

        $model = GETPOST('model', 'alpha');

        $moreparams['object'] = $object;
        $moreparams['user'] = $user;

        if (preg_match('/completioncertificate/', GETPOST('model'))) {
            $signatoriesList = $signatory->fetchSignatories($object->id, $object->type);
            if (!empty($signatoriesList)) {
                foreach ($signatoriesList as $objectSignatory) {
                    if ($objectSignatory->role != 'TRAININGSESSION_SESSION_TRAINER') {
                        $moreparams['attendant'] = $objectSignatory;
                        $result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
                        if ($result <= 0) {
                            setEventMessages($object->error, $object->errors, 'errors');
                            $action = '';
                        }
                    }
                }
                if (empty($donotredirect)) {
                    setEventMessages($langs->trans('FileGenerated') . ' - ' . $object->last_main_doc, null);
                    $urltoredirect = $_SERVER['REQUEST_URI'];
                    $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
                    $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
                    header('Location: ' . $urltoredirect . '#builddoc');
                    exit;
                }
            }
        }

        $result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        if ($result <= 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = '';
        } else {
            if (empty($donotredirect)) {
                setEventMessages($langs->trans('FileGenerated') . ' - ' . $object->last_main_doc, null);
                $urltoredirect = $_SERVER['REQUEST_URI'];
                $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
                $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
                header('Location: ' . $urltoredirect . '#builddoc');
                exit;
            }
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
    $triggersendname = 'DOLIMEET_' . strtoupper($objectType) . '_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_' . strtoupper($objectType) . '_TO';
    $trackid = $objectType . $object->id;

    /*
     * Send mail
     */
    if (($action == 'send' || $action == 'relance') && !$_POST['addfile'] && !$_POST['removAll'] && !$_POST['removedfile'] && !$_POST['cancel'] && !$_POST['modelselected']) {
        if (empty($trackid)) {
            $trackid = GETPOST('trackid', 'aZ09');
        }

        $subject = '';
        $actionmsg = '';
        $actionmsg2 = '';

        $langs->load('mails');

        if (is_object($object)) {
            $result = $object->fetch($id);

            $sendtosocid = 0; // Id of related thirdparty
            if (method_exists($object, 'fetch_thirdparty') && !in_array($objectType, array('member', 'user', 'expensereport', 'societe', 'contact'))) {
                $resultthirdparty = $object->fetch_thirdparty();
                $thirdparty = $object->thirdparty;
                if (is_object($thirdparty)) {
                    $sendtosocid = $thirdparty->id;
                }
            } elseif ($objectType == 'member' || $objectType == 'user') {
                $thirdparty = $object;
                if ($object->socid > 0) {
                    $sendtosocid = $object->socid;
                }
            } elseif ($objectType == 'expensereport') {
                $tmpuser = new User($db);
                $tmpuser->fetch($object->fk_user_author);
                $thirdparty = $tmpuser;
                if ($object->socid > 0) {
                    $sendtosocid = $object->socid;
                }
            } elseif ($objectType == 'societe') {
                $thirdparty = $object;
                if (is_object($thirdparty) && $thirdparty->id > 0) {
                    $sendtosocid = $thirdparty->id;
                }
            } elseif ($objectType == 'contact') {
                $contact = $object;
                if ($contact->id > 0) {
                    $contact->fetch_thirdparty();
                    $thirdparty = $contact->thirdparty;
                    if (is_object($thirdparty) && $thirdparty->id > 0) {
                        $sendtosocid = $thirdparty->id;
                    }
                }
            } else {
                dol_print_error('', "Use actions_sendmails.in.php for an element/object '" . $objectType . "' that is not supported");
            }

            if (is_object($hookmanager)) {
                $parameters = array();
                $reshook = $hookmanager->executeHooks('initSendToSocid', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
            }
        } else {
            $thirdparty = $mysoc;
        }

        if ($result > 0) {
            $sendto = '';
            $sendtocc = '';
            $sendtobcc = '';
            $sendtoid = array();
            $sendtouserid = array();
            $sendtoccuserid = array();

            // Define $sendto
            $receiver = $_POST['receiver'];
            if (!is_array($receiver)) {
                if ($receiver == '-1') {
                    $receiver = array();
                } else {
                    $receiver = array($receiver);
                }
            }

            $tmparray = array();
            if (trim($_POST['sendto'])) {
                // Recipients are provided into free text field
                $tmparray[] = trim($_POST['sendto']);
            }

            if (trim($_POST['tomail'])) {
                // Recipients are provided into free hidden text field
                $tmparray[] = trim($_POST['tomail']);
            }

            if (count($receiver) > 0) {
                // Recipient was provided from combo list
                foreach ($receiver as $key => $val) {
                    if ($val == 'thirdparty') { // Key selected means current third party ('thirdparty' may be used for current member or current user too)
                        $tmparray[] = dol_string_nospecial($thirdparty->getFullName($langs), ' ', array(',')) . ' <' . $thirdparty->email . '>';
                    } elseif ($val == 'contact') { // Key selected means current contact
                        $tmparray[] = dol_string_nospecial($contact->getFullName($langs), ' ', array(',')) . ' <' . $contact->email . '>';
                        $sendtoid[] = $contact->id;
                    } elseif ($val) {    // $val is the Id of a contact
                        $tmparray[] = $thirdparty->contact_get_property((int)$val, 'email');
                        $sendtoid[] = ((int)$val);
                    }
                }
            }

            if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
                $receiveruser = $_POST['receiveruser'];
                if (is_array($receiveruser) && count($receiveruser) > 0) {
                    $fuserdest = new User($db);
                    foreach ($receiveruser as $key => $val) {
                        $tmparray[] = $fuserdest->user_get_property($val, 'email');
                        $sendtouserid[] = $val;
                    }
                }
            }

            $sendto = implode(',', $tmparray);

            // Define $sendtocc
            $receivercc = $_POST['receivercc'];
            if (!is_array($receivercc)) {
                if ($receivercc == '-1') {
                    $receivercc = array();
                } else {
                    $receivercc = array($receivercc);
                }
            }
            $tmparray = array();
            if (trim($_POST['sendtocc'])) {
                $tmparray[] = trim($_POST['sendtocc']);
            }
            if (count($receivercc) > 0) {
                foreach ($receivercc as $key => $val) {
                    if ($val == 'thirdparty') {    // Key selected means current thirdparty (may be usd for current member or current user too)
                        // Recipient was provided from combo list
                        $tmparray[] = dol_string_nospecial($thirdparty->name, ' ', array(',')) . ' <' . $thirdparty->email . '>';
                    } elseif ($val == 'contact') {    // Key selected means current contact
                        // Recipient was provided from combo list
                        $tmparray[] = dol_string_nospecial($contact->name, ' ', array(',')) . ' <' . $contact->email . '>';
                        //$sendtoid[] = $contact->id;  TODO Add also id of contact in CC ?
                    } elseif ($val) {                // $val is the Id of a contact
                        $tmparray[] = $thirdparty->contact_get_property((int)$val, 'email');
                        //$sendtoid[] = ((int) $val);  TODO Add also id of contact in CC ?
                    }
                }
            }
            if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
                $receiverccuser = $_POST['receiverccuser'];

                if (is_array($receiverccuser) && count($receiverccuser) > 0) {
                    $fuserdest = new User($db);
                    foreach ($receiverccuser as $key => $val) {
                        $tmparray[] = $fuserdest->user_get_property($val, 'email');
                        $sendtoccuserid[] = $val;
                    }
                }
            }
            $sendtocc = implode(',', $tmparray);

            if (dol_strlen($sendto)) {
                // Define $urlwithroot
                $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
                $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT; // This is to use external domain name found into config file
                //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

                require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

                $langs->load('commercial');

                $reg = array();
                $fromtype = GETPOST('fromtype', 'alpha');
                if ($fromtype === 'robot') {
                    $from = dol_string_nospecial($conf->global->MAIN_MAIL_EMAIL_FROM, ' ', array(',')) . ' <' . $conf->global->MAIN_MAIL_EMAIL_FROM . '>';
                } elseif ($fromtype === 'user') {
                    $from = dol_string_nospecial($user->getFullName($langs), ' ', array(',')) . ' <' . $user->email . '>';
                } elseif ($fromtype === 'company') {
                    $from = dol_string_nospecial($conf->global->MAIN_INFO_SOCIETE_NOM, ' ', array(',')) . ' <' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '>';
                } elseif (preg_match('/user_aliases_(\d+)/', $fromtype, $reg)) {
                    $tmp = explode(',', $user->email_aliases);
                    $from = trim($tmp[($reg[1] - 1)]);
                } elseif (preg_match('/global_aliases_(\d+)/', $fromtype, $reg)) {
                    $tmp = explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
                    $from = trim($tmp[($reg[1] - 1)]);
                } elseif (preg_match('/senderprofile_(\d+)_(\d+)/', $fromtype, $reg)) {
                    $sql = 'SELECT rowid, label, email FROM ' . MAIN_DB_PREFIX . 'c_email_senderprofile';
                    $sql .= ' WHERE rowid = ' . (int)$reg[1];
                    $resql = $db->query($sql);
                    $obj = $db->fetch_object($resql);
                    if ($obj) {
                        $from = dol_string_nospecial($obj->label, ' ', array(',')) . ' <' . $obj->email . '>';
                    }
                } else {
                    $from = dol_string_nospecial($_POST['fromname'], ' ', array(',')) . ' <' . $_POST['frommail'] . '>';
                }

                $replyto = dol_string_nospecial($_POST['replytoname'], ' ', array(',')) . ' <' . $_POST['replytomail'] . '>';
                $message = GETPOST('message', 'restricthtml');
                $subject = GETPOST('subject', 'restricthtml');

                // Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
                // <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
                // become
                // <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
                $message = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1' . $urlwithroot . '/viewimage.php\2modulepart=medias\3file=\4\5', $message);

                $sendtobcc = GETPOST('sendtoccc');
                // Autocomplete the $sendtobcc
                // $autocopy can be MAIN_MAIL_AUTOCOPY_PROPOSAL_TO, MAIN_MAIL_AUTOCOPY_ORDER_TO, MAIN_MAIL_AUTOCOPY_INVOICE_TO, MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO...
                if (!empty($autocopy)) {
                    $sendtobcc .= (empty($conf->global->$autocopy) ? '' : (($sendtobcc ? ', ' : '') . $conf->global->$autocopy));
                }

                $deliveryreceipt = $_POST['deliveryreceipt'];

                if ($action == 'send' || $action == 'relance') {
                    $actionmsg2 = $langs->transnoentities('MailSentBy') . ' ' . CMailFile::getValidAddress($from, 4, 0, 1) . ' ' . $langs->transnoentities('To') . ' ' . CMailFile::getValidAddress($sendto, 4, 0, 1);
                    if ($message) {
                        $actionmsg = $langs->transnoentities('MailFrom') . ': ' . dol_escape_htmltag($from);
                        $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTo') . ': ' . dol_escape_htmltag($sendto));
                        if ($sendtocc) {
                            $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ': ' . dol_escape_htmltag($sendtocc));
                        }
                        $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ': ' . $subject);
                        $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ':');
                        $actionmsg = dol_concatdesc($actionmsg, $message);
                    }
                }

                // Create form object
                include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                $formmail = new FormMail($db);
                $formmail->trackid = $trackid; // $trackid must be defined

                $attachedfiles = $formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Make substitution in email content
                $substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
                $substitutionarray['__EMAIL__'] = $sendto;
                $substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . urlencode($object->thirdparty->tag) . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';

                $parameters = array('mode' => 'formemail');
                complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

                $subject = make_substitutions($subject, $substitutionarray);
                $message = make_substitutions($message, $substitutionarray);

                if (is_object($object) && method_exists($object, 'makeSubstitution')) {
                    $subject = $object->makeSubstitution($subject);
                    $message = $object->makeSubstitution($message);
                }

                // Send mail (substitutionarray must be done just before this)
                if (empty($sendcontext)) {
                    $sendcontext = 'standard';
                }
                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);

                if ($mailfile->error) {
                    setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                    $action = 'presend';
                } else {
                    $result = $mailfile->sendfile();
                    if ($result) {
                        // Initialisation of datas of object to call trigger
                        if (is_object($object)) {
                            if (empty($actiontypecode)) {
                                $actiontypecode = 'AC_OTH_AUTO'; // Event insert into agenda automatically
                            }

                            $object->socid = $sendtosocid; // To link to a company
                            $object->sendtoid = $sendtoid; // To link to contact-addresses. This is an array.
                            $object->actiontypecode = $actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                            $object->actionmsg = $actionmsg; // Long text (@todo Replace this with $message, we already have details of email in dedicated properties)
                            $object->actionmsg2 = $actionmsg2; // Short text ($langs->transnoentities('MailSentBy')...);

                            $object->trackid = $trackid;
                            $object->fk_element = $object->id;
                            $objectTypetype = $objectType;
                            if (is_array($attachedfiles) && count($attachedfiles) > 0) {
                                $object->attachedfiles = $attachedfiles;
                            }
                            if (is_array($sendtouserid) && count($sendtouserid) > 0 && !empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
                                $object->sendtouserid = $sendtouserid;
                            }

                            $object->email_msgid = $mailfile->msgid; // @todo Set msgid into $mailfile after sending
                            $object->email_from = $from;
                            $object->email_subject = $subject;
                            $object->email_to = $sendto;
                            $object->email_tocc = $sendtocc;
                            $object->email_tobcc = $sendtobcc;
                            $object->email_msgid = $mailfile->msgid;
                            $object->message = $message;

                            // Call of triggers (you should have set $triggersendname to execute trigger. $trigger_name is deprecated)
                            if (!empty($triggersendname) || !empty($trigger_name)) {
                                // Call trigger
                                $result = $object->call_trigger(empty($triggersendname) ? $trigger_name : $triggersendname, $user);
                                if ($result < 0) {
                                    $error++;
                                }
                                // End call triggers
                                if ($error) {
                                    setEventMessages($object->error, $object->errors, 'errors');
                                } else {
                                    $object->setStatusCommon($user, 4);
                                    $signatory->fetchSignatory('E_RECEIVER', $id);
                                    $signatory->last_email_sent_date = dol_now();
                                    $signatory->update($user);
                                }
                            }
                            // End call of triggers
                        }

                        // Redirect here
                        // This avoid sending mail twice if going out and then back to page
                        $mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));
                        setEventMessages($mesg, null, 'mesgs');

                        $moreparam = '';
                        if (isset($paramname2) || isset($paramval2)) {
                            $moreparam .= '&' . ($paramname2 ? $paramname2 : 'mid') . '=' . $paramval2;
                        }
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . ($paramname ? $paramname : 'id') . '=' . (is_object($object) ? $object->id : '') . $moreparam);
                        exit;
                    } else {
                        $langs->load('other');
                        $mesg = '<div class="error">';
                        if ($mailfile->error) {
                            $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                            $mesg .= '<br>' . $mailfile->error;
                        } else {
                            $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                            if (!empty($conf->global->MAIN_DISABLE_ALL_MAILS)) {
                                $mesg .= '<br>Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                            } else {
                                $mesg .= '<br>Unkown Error, please refers to your administrator';
                            }
                        }
                        $mesg .= '</div>';

                        setEventMessages($mesg, null, 'warnings');
                        $action = 'presend';
                    }
                }
            } else {
                $langs->load('errors');
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailTo')), null, 'warnings');
                dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
                $action = 'presend';
            }
        } else {
            $langs->load('errors');
            setEventMessages($langs->trans('ErrorFailedToReadObject', $objectType), null, 'errors');
            dol_syslog('Failed to read data of object id=' . $object->id . ' element=' . $objectType);
            $action = 'presend';
        }
    }

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
}

/*
 * View
 */

$title    = $langs->trans(ucfirst($objectType));
$help_url = '';
// @todo changer
//$morejs  = ['/dolimeet/js/dolimeet.js.php'];
//$morecss = ['/dolimeet/css/dolimeet.css'];

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
        exit;
    }

    print load_fiche_titre($langs->trans('New' . ucfirst($objectType)), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?object_type=' . $objectType . '">';
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
    print load_fiche_titre($langs->trans('Modify' . ucfirst($objectType)), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
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

//    //Project -- Projet
//    print '<tr class="oddeven"><td><label for="Project">' . $langs->trans('ProjectLinked') . '</label></td><td>';
//    $numprojet = $formproject->select_projects(GETPOST('fk_soc') ?: -1, $object->fk_project, 'fk_project', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, 'minwidth300');
//    print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans('AddProject') . '"></span></a>';
//    print '</td></tr>';

//    //Contract -- Contrat
//    if ($objectType == 'trainingsession') {
//        print '<tr class="oddeven"><td><label for="Contract">' . $langs->trans('ContractLinked') . '</label></td><td class="minwidth500">';
//        $numcontrat = $formcontract->select_contract(GETPOST('fk_soc') ?: -1, $object->fk_contrat, 'fk_contrat', 0, 1, 1);
//        print ' <a href="' . DOL_URL_ROOT . '/contrat/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans('AddContract') . '"></span></a>';
//        print '</td></tr>';
//
//        //Duration - Durée
//        print '<tr class="oddeven"><td><label for="Duration">' . $langs->trans('DurationH') . '</label></td><td>';
//        $duration_hours = floor($object->duration / 60);
//        $duration_minutes = ($object->duration % 60);
//        print '<input type=number name="durationh" id="durationh" value="' . $duration_hours . '">';
//        print '<input type=number name="durationm" id="durationm" value="' . $duration_minutes . '">';
//        print '</td></tr>';
//    }

//	//Society -- Société
//	print '<tr><td class="fieldrequired">'.$langs->trans("Society").'</td><td>';
//	$events = array();
//	$events[1] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'contact', 'params' => array('add-customer-contact' => 'disabled'));
//	print $form->select_company($object->fk_soc, 'fk_soc', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
//	print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
//	print '</td></tr>';

//    //Content -- Contenu
//    print '<tr class="content_field"><td><label for="content">' . $langs->trans('Content') . '</label></td><td>';
//    $doleditor = new DolEditor('content', $object->content, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
//    $doleditor->Create();
//    print '</td></tr>';

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
    $objectParentType = $object->element;
    saturne_banner_tab($object, 'card', $title);

    $formconfirm = '';

    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteMyObject'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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
    print '<tr><td class="titlefield">';
    print $langs->trans('Content');
    print '</td>';
    print '<td>';
    print '<div class="longmessagecut" style="min-height: 150px">';
    print dol_htmlentitiesbr($object->content); //wrap -> middle?
    print '</div>';
    print '</td></tr>';

    if ($object->type == 'trainingsession') {
        $duration_hours = floor($object->duration / 60);
        $duration_minutes = ($object->duration % 60);

        print '<tr><td class="titlefield">';
        print $langs->trans('Duration');
        print '</td>';
        print '<td>';
        print $duration_hours . ' ' . $langs->trans('Hour(s)') . ' ' . $duration_minutes . ' ' . $langs->trans('Minute(s)');
        print '</td></tr>';
    }

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

        if (empty($reshook)) {
            // Send
            $class = 'ModelePDFSession';
            $modellist = call_user_func($class . '::liste_modeles', $db, 100, $object->type);
            if (!empty($modellist)) {
                asort($modellist);

                $modellist = array_filter($modellist, 'remove_index');

                if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
                {
                    $arraykeys = array_keys($modellist);
                    $arrayvalues = preg_replace('/template_/', '', array_values($modellist)[0]);

                    $modellist[$arraykeys[0]] = $arrayvalues;
                    $modelselected = $arraykeys[0];
                }
            }
            if ($permissiontoadd) {
                $button_edit = '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken() . '"' . '>' . $langs->trans('Modify') . '</a>' . "\n";
                $button_edit_with_confirm = '<span class="butAction" id="actionButtonInProgress">' . $langs->trans('Modify') . '</span>' . "\n";
                $button_edit_disabled = '<a class="butActionRefused classfortooltip"  title="' . $langs->trans('CantEditAlreadySigned') . '"' . '>' . $langs->trans('Modify') . '</a>' . "\n";
                print $button_edit;
//			print '<a class="'. ($object->status == 0 ? 'butAction" id="actionButtonSign" href="' . DOL_URL_ROOT . '/custom/dolimeet/'. $objectType .'_signature.php'.'?id='.$object->id.'&mode=init&token='.newToken().'"' : 'butActionRefused classfortooltip" title="'. $langs->trans('AlreadySigned').'"')  .' >' . $langs->trans("Sign") . '</a>' . "\n";
//			print '<span class="' . ($object->status == 1 ? 'butAction"  id="actionButtonLock"' : 'butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans(ucfirst($objectType)."MustBeSigned")) . '"') . '>' . $langs->trans("Lock") . '</span>';
//			print '<a class="'. ($object->status == 2 || $object->status == 4 ? 'butAction" id="actionButtonSendMail" href="' . $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&model='.$modelselected.'&token='.newToken().'"'  : 'butActionRefused classfortooltip" title="'. $langs->trans('MustBeSignedBeforeSending').'"') . ' >' . $langs->trans("SendMail") . '</a>' . "\n";
                print '<span class="butAction" id="actionButtonClone" title="" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone' . '">' . $langs->trans('ToClone') . '</span>';
            } else {
                print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans('NotEnoughPermissions')) . '">' . $langs->trans('Modify') . '</a>' . "\n";
                print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans('NotEnoughPermissions')) . '">' . $langs->trans('Sign') . '</a>' . "\n";
                print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans('NotEnoughPermissions')) . '">' . $langs->trans('SendLetter') . '</a>' . "\n";
            }
            if ($permissiontodelete) {
                print '<a class="butActionDelete" id="actionButtonSendMail" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken() . '">' . $langs->trans('Delete') . '</a>' . "\n";
            } else {
                print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans('NotEnoughPermissions')) . '">' . $langs->trans('Delete') . '</a>' . "\n";
            }
        }
        print '</div>';
    }

    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a href="#builddoc"></a>'; // ancre

        // Documents
        $objref = dol_sanitizeFileName($object->ref);
        $relativepath = $objref . '/' . $objref . '.pdf';
        $filedir = $conf->dolimeet->dir_output . '/' . $objectType . '/' . $objref;

        $generated_files = dol_dir_list($filedir . '/', 'files');
        $document_generated = 0;
        foreach ($generated_files as $generated_file) {
            if (!preg_match('/specimen/', $generated_file['name'])) {
                $document_generated += 1;
            }
        }
        $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $objectType;

        print dolimeetshowdocuments('dolimeet:' . $object->type, $objectType . '/' . $objref, $filedir, $urlsource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, $langs->trans('LinkedDocuments'), 0, '', '', '', $langs->defaultlang, 1);

        // Show links to link elements
        // @todo a test
        $linktoelem = $form->showLinkToObjectBlock($object, null, ['session']);
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

        print '</div><div class="fichehalfright">';

        $MAXEVENT = 10;

        // @todo a surement problème lien
        $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/dolimeet/view/' . $objectType . '/' . $objectType . '_agenda.php', 1) . '?id=' . $object->id);

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