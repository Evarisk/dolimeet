<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *   	\file       view/meeting/meeting_attendants.php
 *		\ingroup    dolimeet
 *		\brief      Page to add/edit/view meeting_signature
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

require_once __DIR__ . '/class/meeting.class.php';
require_once __DIR__ . '/lib/dolimeet_meeting.lib.php';
require_once __DIR__ . '/lib/dolimeet_function.lib.php';

global $db, $langs;

// Load translation files required by the page
$langs->loadLangs(array("dolimeet@dolimeet", "other"));

// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'meetingsignature'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object            = new Meeting($db);
$signatory         = new MeetingSignature($db);
$usertmp           = new User($db);
$contact           = new Contact($db);
$form              = new Form($db);
$project           = new Project($db);
$thirdparty        = new Societe($db);

$object->fetch($id);

$hookmanager->initHooks(array('meetingsignature', 'globalcard')); // Note that conf->hooks_modules contains array

//Security check
$permissiontoread   = $user->rights->dolimeet->meeting->read;
$permissiontoadd    = $user->rights->dolimeet->meeting->write;
$permissiontodelete = $user->rights->dolimeet->meeting->delete;

if ( ! $permissiontoread) accessforbidden();

/*
/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($backtopage) || ($cancel && empty($id))) {
	if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
		$backtopage = dol_buildpath('/dolimeet/meeting_attendants.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
	}
}

// Action to add internal attendant
if ($action == 'addSocietyAttendant') {
	$error = 0;
	$object->fetch($id);
	$attendant_id = GETPOST('user_attendant');

	if ( ! $error) {
		$result = $signatory->setSignatory($object->id, 'meeting', 'user', array($attendant_id), 'MEETING_SOCIETY_ATTENDANT', 1);
		if ($result > 0) {
			$usertmp = $user;
			$usertmp->fetch($attendant_id);
			setEventMessages($langs->trans('AddAttendantMessage') . ' ' . $usertmp->firstname . ' ' . $usertmp->lastname, array());
			// Creation attendant OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Creation attendant KO
			if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
}

// Action to add external attendant
if ($action == 'addExternalAttendant') {
	$error = 0;
	$object->fetch($id);
	$extintervenant_id = GETPOST('external_attendant');

	if ( ! $error) {
		$result = $signatory->setSignatory($object->id, 'meeting', 'socpeople', array($extintervenant_id), 'MEETING_EXTERNAL_ATTENDANT', 1);
		if ($result > 0) {
			$contact->fetch($extintervenant_id);
			setEventMessages($langs->trans('AddAttendantMessage') . ' ' . $contact->firstname . ' ' . $contact->lastname, array());
			// Creation attendant OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Creation attendant KO
			if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
}

// Action to add record
if ($action == 'addSignature') {
	$signatoryID  = GETPOST('signatoryID');
	$data = json_decode(file_get_contents('php://input'), true);

	$signatory->fetch($signatoryID);
	$signatory->signature      = $data['signature'];
	$signatory->signature_date = dol_now('tzuser');

	if ( ! $error) {
		$result = $signatory->update($user, false);

		if ($result > 0) {
			// Creation signature OK
			$signatory->setSigned($user, 0);
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Creation signature KO
			if ( ! empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else setEventMessages($signatory->error, null, 'errors');
		}
	}
}

// Action to set status STATUS_ABSENT
if ($action == 'setAbsent') {
	$signatoryID = GETPOST('signatoryID');

	$signatory->fetch($signatoryID);

	if ( ! $error) {
		$result = $signatory->setAbsent($user, 0);
		if ($result > 0) {
			// set absent OK
			setEventMessages($langs->trans('Attendant') . ' ' . $signatory->firstname . ' ' . $signatory->lastname . ' ' . $langs->trans('SetAbsentAttendant'), array());
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// set absent KO
			if ( ! empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
			else setEventMessages($signatory->error, null, 'errors');
		}
	}
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
			$url  = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $signatory->signature_url  . '&type=' . $object->element, 3);

			$message = $langs->trans('SignatureEmailMessage') . ' ' . $url;
			$subject = $langs->trans('SignatureEmailSubject') . ' ' . $object->ref;

			// Create form object
			// Send mail (substitutionarray must be done just before this)
			$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', '', '', 'mail');

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
						header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
						exit;
					} else {
						$langs->load("other");
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
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
			dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
		}
	} else {
		// Mail sent KO
		if ( ! empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
		else setEventMessages($signatory->error, null, 'errors');
	}
}

// Action to delete attendant
if ($action == 'deleteAttendant') {
	$signatoryToDeleteID = GETPOST('signatoryID');
	$signatory->fetch($signatoryToDeleteID);

	if ( ! $error) {
		$result = $signatory->setDeleted($user, 0);
		if ($result > 0) {
			setEventMessages($langs->trans('DeleteAttendantMessage') . ' ' . $signatory->firstname . ' ' . $signatory->lastname, array());
			// Deletion attendant OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Deletion attendant KO
			if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	} else {
		$action = 'create';
	}
}

/*
 *  View
 */

$formcompany = new FormCompany($db);
$title    = $langs->trans("MeetingAttendants");
$help_url = '';
$morejs   = array("/dolimeet/js/signature-pad.min.js", "/dolimeet/js/dolimeet.js.php");
$morecss  = array("/dolimeet/css/dolimeet.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

if ( ! empty($object->id)) $res = $object->fetch_optionals();

// Object card
// ------------------------------------------------------------

$head = meetingPrepareHead($object);
print dol_get_fiche_head($head, 'attendants', $langs->trans("Meeting"), -1, "dolimeet@dolimeet");

$width = 80; $cssclass = 'photoref';
dol_strlen($object->label) ? $morehtmlref = '<span>' . ' - ' . $object->label . '</span>' : '';
$morehtmlref                             .= '<div class="refidno">';
// External Society -- Société extérieure
// Project
$project->fetch($object->fk_project);
$morehtmlref .= '<br>' . $langs->trans('Project') . ' : ' . $project->getNomUrl(1);
$morehtmlref .= '</div>';

//$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('dolimeet', $conf->dolimeet->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';

dol_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

print dol_get_fiche_end(); ?>

<?php if ( $object->status == 1 ) : ?>
<div class="wpeo-notice notice-warning">
	<div class="notice-content">
		<div class="notice-title"><?php echo $langs->trans('DisclaimerSignatureTitle') ?></div>
		<div class="notice-subtitle"><?php echo $langs->trans("MeetingMustBeValidatedToSign") ?></div>
	</div>
	<a class="butAction" style="width = 100%;margin-right:0" href="<?php echo DOL_URL_ROOT ?>/custom/dolimeet/view/meeting/meeting_card.php?id=<?php echo $id ?>"><?php echo $langs->trans("GoToValidate") ?></a>;
</div>
<?php endif; ?>
<div class="noticeSignatureSuccess wpeo-notice notice-success hidden">
	<div class="all-notice-content">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('AddSignatureSuccess') ?></div>
			<div class="notice-subtitle"><?php echo $langs->trans("AddSignatureSuccessText") . GETPOST('signature_id')?></div>
		</div>
		<?php
		if ($signatory->checkSignatoriesSignatures($object->id, 'meeting')) {
			print '<a class="butAction" style="width = 100%;margin-right:0" href="' . DOL_URL_ROOT . '/custom/dolimeet/view/meeting/meeting_card.php?id=' . $id . '">' . $langs->trans("GoToLock") . '</a>';
		}
		?>
	</div>
</div>
<?php

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {

	//Society attendants -- Participants de la société
	$society_intervenants = $signatory->fetchSignatory('MEETING_SOCIETY_ATTENDANT', $object->id, 'meeting');

	print load_fiche_titre($langs->trans("Attendants"), '', '');

	print '<table class="border centpercent tableforfield">';

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Role") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureLink") . '</td>';
	print '<td class="center">' . $langs->trans("SendMailDate") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureDate") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("ActionsSignature") . '</td>';
	print '<td class="center">' . $langs->trans("Signature") . '</td>';
	print '</tr>';
//
//	$contacts          = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "" ));
//	$contacts_no_email = array();
//	if (is_array($contacts) && ! empty($contacts) && $contacts > 0) {
//		foreach ($contacts as $element_id) {
//			$contacts_no_email[$element_id->id] = $element_id->id;
//		}
//	}
	$already_added_users = array();
	$j = 1;
	if (is_array($society_intervenants) && ! empty($society_intervenants) && $society_intervenants > 0) {
		foreach ($society_intervenants as $element) {
			$usertmp = $user;
			$usertmp->fetch($element->element_id);
			print '<tr class="oddeven"><td class="minwidth200">';
			print $usertmp->getNomUrl(1);
			print '</td><td>';
			print $langs->trans("ExtSocietyIntervenant") . ' ' . $j;
			print '</td><td class="center">';
			if ($object->status == 2) {
				$signatureUrl = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&type=' . $object->element, 3);
				print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
			} else {
				print '-';
			}

			print '</td><td class="center">';
			print dol_print_date($element->last_email_sent_date, 'dayhour');
			print '</td><td class="center">';
			print dol_print_date($element->signature_date, 'dayhour');
			print '</td><td class="center">';
			print $element->getLibStatut(5);
			print '</td>';
			print '<td class="center">';
			if ($permissiontoadd) {
				require __DIR__ . "/core/tpl/signature/dolimeet_signature_action_view.tpl.php";
			}
			print '</td>';
			if ($element->signature != $langs->transnoentities("FileGenerated") && $permissiontoadd) {
				print '<td class="center">';
				require __DIR__ . "/core/tpl/signature/dolimeet_signature_view.tpl.php";
				print '</td>';
			}
			print '</tr>';
			$already_added_users[$element->element_id] = $element->element_id;
			$j++;
		}
	} else {
		print '<tr><td>';
		print $langs->trans('NoSocietyAttendants');
		print '</td></tr>';
	}

	if ($permissiontoadd) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addSocietyAttendant">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

		if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

		//Intervenants extérieurs
		print '<tr class="oddeven"><td class="maxwidth200">';
		print $form->select_dolusers('', 'user_attendant', 1, $already_added_users);
		print '</td>';
		print '<td>' . $langs->trans("ExtSocietyIntervenants") . '</td>';
		print '<td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '<button type="submit" class="wpeo-button button-blue " name="addline" id="addline"><i class="fas fa-plus"></i>  ' . $langs->trans('Add') . '</button>';
		print '<td class="center">';
		print '-';
		print '</td>';
		print '</tr>';
		print '</table>' . "\n";
		print '</form>';
	}

	//External Society Intervenants -- Intervenants Société extérieure
	$thirdparty->fetch($object->fk_soc);
	$ext_society_intervenants = $signatory->fetchSignatory('MEETING_EXTERNAL_ATTENDANT', $object->id, 'meeting');

	print load_fiche_titre($langs->trans("ExternalIntervenants"), '', '');

	print '<table class="border centpercent tableforfield">';

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Thirdparty") . '</td>';
	print '<td>' . $langs->trans("ContactLinked") . '</td>';
	print '<td>' . $langs->trans("Role") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureLink") . '</td>';
	print '<td class="center">' . $langs->trans("SendMailDate") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureDate") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("ActionsSignature") . '</td>';
	print '<td class="center">' . $langs->trans("Signature") . '</td>';
	print '</tr>';
//
//	$contacts          = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "" ));
//	$contacts_no_email = array();
//	if (is_array($contacts) && ! empty($contacts) && $contacts > 0) {
//		foreach ($contacts as $element_id) {
//			$contacts_no_email[$element_id->id] = $element_id->id;
//		}
//	}

	$already_selected_intervenants = array();
	$j                                           = 1;
	if (is_array($ext_society_intervenants) && ! empty($ext_society_intervenants) && $ext_society_intervenants > 0) {
		foreach ($ext_society_intervenants as $element) {
			$contact->fetch($element->element_id);
			print '<tr class="oddeven"><td class="minwidth200">';
			$thirdparty->fetch($contact->fk_soc);
			print $thirdparty->getNomUrl(1);
			print '</td><td>';
			print $contact->getNomUrl(1);
			print '</td><td>';
			print $langs->trans("ExtSocietyIntervenant") . ' ' . $j;
			print '</td><td class="center">';
			if ($object->status == 2) {
				$signatureUrl = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&type=' . $object->element, 3);
				print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
			} else {
				print '-';
			}

			print '</td><td class="center">';
			print dol_print_date($element->last_email_sent_date, 'dayhour');
			print '</td><td class="center">';
			print dol_print_date($element->signature_date, 'dayhour');
			print '</td><td class="center">';
			print $element->getLibStatut(5);
			print '</td>';
			print '<td class="center">';
			if ($permissiontoadd) {
				require __DIR__ . "/core/tpl/signature/dolimeet_signature_action_view.tpl.php";
			}
			print '</td>';
			if ($element->signature != $langs->transnoentities("FileGenerated") && $permissiontoadd) {
				print '<td class="center">';
				require __DIR__ . "/core/tpl/signature/dolimeet_signature_view.tpl.php";
				print '</td>';
			}
			print '</tr>';
			$already_selected_intervenants[$element->element_id] = $element->element_id;
			$j++;
		}
	} else {
		print '<tr><td>';
		print $langs->trans('NoExternalAttendants');
		print '</td></tr>';
	}

	if ($permissiontoadd) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addExternalAttendant">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

		if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

		//Intervenants extérieurs
		$ext_society = $object->fk_soc;
		if ($ext_society < 1) {
			$ext_society = new StdClass();
		}

		print '<tr class="oddeven">';
		print '<td class="tagtd nowrap noborderbottom">';
		$selectedCompany = GETPOSTISSET("newcompany") ? GETPOST("newcompany", 'int') : (empty($object->socid) ?  0 : $object->socid);
		$selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', '', 0, '', 'minwidth300imp');
		print '</td>';
		print '<td class="tagtd noborderbottom minwidth500imp">';
		print img_object('', 'contact', 'class="pictofixedwidth"').$form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), '', 'external_attendant', 3, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
		$nbofcontacts = $form->num;
		$newcardbutton = '';
		if (!empty(GETPOST('newcompany')) && GETPOST('newcompany') > 1 && $user->rights->societe->creer) {
			$newcardbutton .= '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$selectedCompany.'&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.'&newcompany=' . GETPOST('newcompany')).'" title="'.$langs->trans('NewContact').'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
		}
		print $newcardbutton;
		print '</td>';
		print '<td>' . $langs->trans("ExtSocietyIntervenants") . '</td>';
		print '<td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '<button type="submit" class="wpeo-button button-blue " name="addline" id="addline"><i class="fas fa-plus"></i>  ' . $langs->trans('Add') . '</button>';
		print '<td class="center">';
		print '-';
		print '</td>';
		print '</tr>';
		print '</table>' . "\n";
		print '</form>';
		print '</div>';

	}
}

// End of page
llxFooter();
$db->close();
