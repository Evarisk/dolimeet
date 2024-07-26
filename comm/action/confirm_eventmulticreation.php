<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023 Florian HENRY <florian.henry@scopen.fr>
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
 *    \file       /dolipad/comm/action/confirrm_eventmulticreation.php
 *        \ingroup    dolipad
 *        \brief      manage MultiEvent Création
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
//if (! defined('NOSESSION'))     		     define('NOSESSION', '1');				    // Disable session


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
dol_include_once('/buildingmanagement/class/buildingmanagementhelpers.class.php');

$event = new ActionComm($db);

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($event->table_element);


$action = GETPOST('action', 'aZ09');

$create_from_element_id = GETPOST('create_from_element_id', 'int');
$create_from_element_type = GETPOST('create_from_element_type', 'alpha');

if ($create_from_element_type == "fichinter") {
	require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
	$inter = new Fichinter($db);
	$result = $inter->fetch($create_from_element_id);
	if ($result < 0) {
		setEventMessages($inter->error, $inter->errors, 'errors');
	}
	if (empty($label) && !empty($inter->id)) {
		$label = $inter->description;
	}
}

if (empty(isModEnabled('dolipad'))) accessforbidden();
if (empty($user->hasRight('agenda', 'myactions', 'read'))) {
	accessforbidden();
}
$result = restrictedArea($user, 'agenda', 0, '', 'myactions');

$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

// Load translation files required by the page
$langs->loadLangs(array('users', 'agenda', 'other', 'commercial'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('dolipad_multievt_confirm'));

$eventArray = _getAndSortEventArray($create_from_element_type, $create_from_element_id);

$allEmployeesArray = array();
$userGroupTech = new UserGroup($db);
$userGroupTech->id = getDolGlobalInt('DOLIPAD_USERGROUP_TECH');
$resultUsersTech = $userGroupTech->listUsersForGroup('u.statut=1');
if (!is_array($resultUsersTech) && $resultUsersTech < 0) {
	setEventMessages($userGroupTech->error, $userGroupTech->errors, 'errors');
} else {
	if (!empty($resultUsersTech)) {
		foreach ($resultUsersTech as $userTech) {
			$allEmployeesArray[$userTech->id] = $userTech->getFullName($langs);
		}
	}
}

/*
 * Actions
 */
$parameters = array();
$parameters['eventArray'] = $eventArray;
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	$error = 0;
	if ($action == 'update') {
		$eventArray = _getAndSortEventArray($create_from_element_type, $create_from_element_id);
		foreach ($eventArray as $index => $dataEvent) {
			$evtUpd = new ActionComm($db);
			$evtUpd->fetch($dataEvent->id);

			$cactioncomm = new CActionComm($db);
			$resultFetch = $cactioncomm->fetch(GETPOST("actioncode", 'alpha'));
			if ($resultFetch < 0) {
				setEventMessages($cactioncomm->error, $cactioncomm->errors, 'errors');
			}
			$evtUpd->type_id = $cactioncomm->id;
			$evtUpd->type_code = $cactioncomm->code;
			$evtUpd->label = GETPOST('label_' . $evtUpd->id, 'alpha');

			$evtUpd->datep = dol_mktime(
				GETPOST('eventdtstart_' . $evtUpd->id . 'hour', 'int'),
				GETPOST('eventdtstart_' . $evtUpd->id . 'min', 'int'),
				GETPOST('eventdtstart_' . $evtUpd->id . 'sec', 'int'),
				GETPOST('eventdtstart_' . $evtUpd->id . 'month', 'int'),
				GETPOST('eventdtstart_' . $evtUpd->id . 'day', 'int'),
				GETPOST('eventdtstart_' . $evtUpd->id . 'year', 'int'));
			$evtUpd->datef = dol_mktime(
				GETPOST('eventdtend_' . $evtUpd->id . 'hour', 'int'),
				GETPOST('eventdtend_' . $evtUpd->id . 'min', 'int'),
				GETPOST('eventdtend_' . $evtUpd->id . 'sec', 'int'),
				GETPOST('eventdtend_' . $evtUpd->id . 'month', 'int'),
				GETPOST('eventdtend_' . $evtUpd->id . 'day', 'int'),
				GETPOST('eventdtend_' . $evtUpd->id . 'year', 'int'));
			$evtUpd->userownerid = GETPOST('usrownerid_' . $evtUpd->id, 'int');
			$userAssign = GETPOST('employees_' . $evtUpd->id, 'array');
			$evtUpd->userassigned = array();
			if (!empty($userAssign)) {
				foreach ($userAssign as $userIdAssign) {
					$evtUpd->userassigned[$userIdAssign] = array('id' => $userIdAssign);
				}
			}
			$evtUpd->array_options['options_dolipad_note_tech'] = GETPOST('dolipad_note_tech_' . $evtUpd->id, 'alpha');
			if (GETPOST('dolipad_tech_close_force_' . $evtUpd->id)) {
				$evtUpd->array_options['options_dolipad_tech_close'] = GETPOST('close_technik_' . $evtUpd->id, 'int');
				$evtUpd->array_options['options_dolipad_tech_close_force'] = 1;
			} else {
				$evtUpd->array_options['options_dolipad_tech_close_force'] = 0;
			}
			if (GETPOST('dolipad_is_intercombi_' . $evtUpd->id)) {
				$evtUpd->array_options['options_dolipad_is_intercombi'] = 1;
				$evtUpd->array_options['options_dolipad_intercombi_place'] = GETPOST('dolipad_intercombi_place_' . $evtUpd->id, 'int');;
			} else {
				$evtUpd->array_options['options_dolipad_is_intercombi'] = 0;
				$evtUpd->array_options['options_dolipad_intercombi_place'] = 0;
			}
			if (GETPOST('dolipad_is_baes_' . $evtUpd->id)) {
				$evtUpd->array_options['options_dolipad_is_baes'] = 1;
			} else {
				$evtUpd->array_options['options_dolipad_is_baes'] = 0;
			}

			$resultUpd = $evtUpd->update($user);
			if ($resultUpd < 0) {
				setEventMessages($evtUpd->error, $evtUpd->errors, 'errors');
			}
		}
		//We resort the event array arrocding date to apply close technic "all first lines are Partiel last is Total) if not force to something else
		$eventArray = _getAndSortEventArray($create_from_element_type, $create_from_element_id);
		$evtCntUseForCloseTechnik = 0;
		foreach ($eventArray as $index => $dataEvent) {
			$evtCntUseForCloseTechnik++;
			$evtUpd = new ActionComm($db);
			$evtUpd->fetch($dataEvent->id);
			$force_close = GETPOST('dolipad_tech_close_force_' . $evtUpd->id);
			if (empty($force_close)) {
				$evtUpd->array_options['options_dolipad_tech_close'] = ($evtCntUseForCloseTechnik == count($eventArray) ? 2 : 1);
			}

			$resultUpd = $evtUpd->update($user);
			if ($resultUpd < 0) {
				setEventMessages($evtUpd->error, $evtUpd->errors, 'errors');
			}
		}

		if (GETPOSTISSET('endplannif')) {
			$_SESSION["multi_evt"] = array();
			if ($create_from_element_type == "fichinter") {
				header("Location: " . dol_buildpath('fichinter/card.php', 1) . '?id=' . $create_from_element_id);
				exit;
			} else {
				header("Location: " . dol_buildpath('dolipad/comm/action/peruser_eventcreation.php', 1));
				exit;
			}
		}
	}
}

$reshook = $hookmanager->executeHooks('beforeConfirmMultiEvtCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$form = new Form($db);
$formactions = new FormActions($db);

$help_url = '';
llxHeader('', $langs->trans("DlpdMultiEvtCreation"), $help_url, '', 0, 0, array(), array());

$placesInterCombi = [];
if (class_exists('BuildingManagementHelpers')) {
	$bmHelpers = new BuildingManagementHelpers($db);
	$resultInfoBuilding = $bmHelpers->getResidenceAndBuilding($create_from_element_type, $create_from_element_id);
	if (!is_array($resultInfoBuilding) && $resultInfoBuilding < 0) {
		setEventMessages($bmHelpers->error, $bmHelpers->errors, 'errors');
	} else {
		if (!empty($resultInfoBuilding['residenceid'])) {
			dol_include_once('/dolipad/class/dolipadintercombiplace.class.php');
			$combiPlace = new DolipadInterCombiPlace($db);
			dol_include_once('/dolipad/class/dolipadintercombilisting.class.php');
			$listing = new DolipadInterCombiListing($db);
			$sqlListRes = 'SELECT p.rowid,p.label FROM ' . $db->prefix() . $combiPlace->table_element . ' as p';
			$sqlListRes .= ' INNER JOIN ' . $db->prefix() . $listing->table_element . ' as l ON l.fk_dolipadintercombiplace=p.rowid ';
			$sqlListRes .= ' AND l.status=' . $listing::STATUS_ONPLAN;
			$sqlListRes .= ' WHERE p.element_id=' . $resultInfoBuilding['residenceid'] . ' AND p.element_type=\'residence\'';
			$resqlListRes = $db->query($sqlListRes);
			if (!$resqlListRes) {
				setEventMessage($db->lasterror, 'errors');
			} else {
				while ($objListRes = $db->fetch_object($resqlListRes)) {
					$placesInterCombi[$objListRes->rowid] = $objListRes->label;
				}
			}
		}
	}
}

print '<script type="text/javascript">
	$(document).ready(function() {
		$(".chckboxForceEvt").change(function() {
			manage_force_method();
		});

		function manage_force_method() {
			$(".chckboxForceEvt").each(function () {
				//console.log($(this).data("evtid"),$(this).prop("checked"));
                if ($(this).prop("checked")) {
					$("#close_technik_"+$(this).data("evtid")).prop("disabled",false);
                } else {
                    $("#close_technik_"+$(this).data("evtid")).prop("disabled",true);
                }
			});
		}

        ';
if (!empty($placesInterCombi)) {
	print '
        $(".chckboxIsInterCombi").change(function() {
			manage_is_intercombi();
		});

        function manage_is_intercombi() {
            $(".chckboxIsInterCombi").each(function () {
				//console.log($(this).data("evtid"),$(this).prop("checked"));
                if ($(this).prop("checked")) {
					$("#dolipad_intercombi_place_"+$(this).data("evtid")).prop("disabled",false);
					$("#dolipad_intercombi_place_"+$(this).data("evtid")).show();
                } else {
                    $("#dolipad_intercombi_place_"+$(this).data("evtid")).prop("disabled",true);
                    $("#dolipad_intercombi_place_"+$(this).data("evtid")).hide();
                }
			});
        }

        $(".chckboxIsInterCombi").each(function () {
             if (!$(this).prop("checked")) {
                 //console.log($("#dolipad_intercombi_place_"+$(this).data("evtid")));
                 $("#dolipad_intercombi_place_"+$(this).data("evtid")).hide();
             }
        });
        ';
}
print '
	});
 </script>';


if (empty(getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_INTER_MULTI')) && $create_from_element_type == "fichinter") {
	setEventMessages(null, $langs->trans('DlpdConfNotCompleteEventCreationMulti'), 'errors');
}

//$param .= "&maxprint=" . urlencode($maxprint);

print load_fiche_titre($langs->trans("DlpdMultiEvtCreation"), '', 'title_agenda');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

if (!empty($create_from_element_id)) {
	print '<input type="hidden" name="create_from_element_id" id="create_from_element_id" value="' . $create_from_element_id . '">';
}
if (!empty($create_from_element_type)) {
	print '<input type="hidden" name="create_from_element_type" id="create_from_element_type" value="' . $create_from_element_type . '">';
}

print '<table class="border centpercent tableforfieldedit">' . "\n";

if (!empty(getDolGlobalString('DOLIPAD_USERGROUP_TECH'))) {
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">' . $langs->trans("Type") . '</span></b></td><td>';
	$default = getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_INTER_MULTI');
	print img_picto($langs->trans("ActionType"), 'square', 'class="fawidth30 inline-block" style="color: #ddd;"');
	print $formactions->select_type_actions(GETPOSTISSET("actioncode") ? GETPOST("actioncode", 'aZ09') : $default, "actioncode", "systemauto", 0, -1, 0, 1);
	print '</td></tr>';
}

print '</table>' . "\n";

print '<table class="tagtable liste listwithfilterbefore">' . "\n";
print '<tr class="liste_titre">';
print '<td class="liste_titre">';
print $langs->trans('Ref');
print '</td>';
print '<td class="liste_titre ' . (empty(getDolGlobalString('AGENDA_USE_EVENT_TYPE')) ? ' fieldrequired' : '') . '">';
print $langs->trans('Label');
print '</td>';
print '<td class="liste_titre">';
print $langs->trans("DateActionStart");
print '</td>';
print '<td class="liste_titre">';
print $langs->trans("DateActionEnd");
print '</td>';
print '<td class="liste_titre">';
print $langs->trans("Owner");
print '</td>';
print '<td class="liste_titre">';
print $langs->trans("DlpdTeam");
print '</td>';
print '<td class="liste_titre">';
print $langs->trans("DlpdNoteTech");
print '</td>';
print '<td class="liste_titre">';
print $langs->trans("DlpdTechCloseForce") . '/' . $langs->trans("DlpdTechClose");
print '</td>';
if (!empty($placesInterCombi)) {
	print '<td class="liste_titre">';
	print $langs->trans("DlpdInterCombi");
	print '</td>';
}
print '<td class="liste_titre">';
print $langs->trans("DlpdBAES");
print '</td>';
print '</tr>';


$evtCntUseForCloseTechnik = 0;

//TODO may be use to manage disabled user selection in employees selector for now not manage
//$multievtselectuser='multievtselectuser';
$multievtselectuser = '';

foreach ($eventArray as $index => $dataEvent) {
	$evtCntUseForCloseTechnik++;

	print '<tr class="oddeven">';

	print '<td class="nowraponall">';
	print $dataEvent->getNomUrl(1, -1);
	print '</td>';

	print '<td><input type="text" id="label_' . $dataEvent->id . '" name="label_' . $dataEvent->id . '" value="' . $dataEvent->label . '"></td>';

	print '<td class="center nowraponall">';
	print $form->selectDate($dataEvent->datep, "eventdtstart_" . $dataEvent->id, 1, 1);
	print '</td>';

	print '<td class="center nowraponall">';
	print $form->selectDate($dataEvent->datef, "eventdtend_" . $dataEvent->id, 1, 1);
	print '</td>';

	print '<td class="tdoverflowmax150">';
	print $form->select_dolusers($dataEvent->userownerid, 'usrownerid_' . $dataEvent->id, 0, null, 0, array_keys($allEmployeesArray), '', 0, 0, 0, '', 0, '', $multievtselectuser);
	print '</td>';

	print '<td>';
	$usersTeam = [];
	if (!empty($dataEvent->userassigned)) {
		foreach ($dataEvent->userassigned as $userAssignId => $dataUsrAssign) {
			if ($dataUsrAssign['id'] != $dataEvent->userownerid) {
				$usersTeam[$dataUsrAssign['id']] = $dataUsrAssign['id'];
			}
		}
	}
	print $form->multiselectarray('employees_' . $dataEvent->id, $allEmployeesArray, $usersTeam, '', 0, '', 0, '100%');
	print '</td>';

	print '<td>';
	print '<input type="text" id="dolipad_note_tech_' . $dataEvent->id . '" name="dolipad_note_tech_' . $dataEvent->id . '" class="" value="' . $dataEvent->array_options['options_dolipad_note_tech'] . '">';
	print '</td>';

	print '<td>';
	$checked = '';
	if (!empty($dataEvent->array_options['options_dolipad_tech_close_force'])) {
		$checked = 'checked="checked"';
		$defaultValue = $dataEvent->array_options['options_dolipad_tech_close'];
	} else {
		$defaultValue = ($evtCntUseForCloseTechnik == count($eventArray) ? 2 : 1);
	}
	print '<input type="checkbox" ' . $checked . ' class="flat chckboxForceEvt" data-evtid="' . $dataEvent->id . '" id="dolipad_tech_close_force_' . $dataEvent->id . '" name="dolipad_tech_close_force_' . $dataEvent->id . '" value="1"/>';


	print $form->selectarray("close_technik_" . $dataEvent->id, $extrafields->attributes[$dataEvent->table_element]['param']['dolipad_tech_close']['options'], $defaultValue, 0, 0, 0, '', 0, 0, (empty($dataEvent->array_options['options_dolipad_tech_close_force'])), '', 'minwidth200');
	print '</td>';

	if (!empty($placesInterCombi)) {
		print '<td>';
		$checked = '';
		if (!empty($dataEvent->array_options['options_dolipad_is_intercombi'])) {
			$checked = 'checked="checked"';
			$defaultValue = $dataEvent->array_options['dolipad_intercombi_place'];
		} else {
			$placesInterCombiKeys = array_keys($placesInterCombi);
			$defaultValue = reset($placesInterCombiKeys);
		}
		print '<input type="checkbox" ' . $checked . ' class="flat chckboxIsInterCombi" data-evtid="' . $dataEvent->id . '" id="dolipad_is_intercombi_' . $dataEvent->id . '" name="dolipad_is_intercombi_' . $dataEvent->id . '" value="1"/>';
		print $form->selectarray("dolipad_intercombi_place_" . $dataEvent->id, $placesInterCombi, $defaultValue, 0, 0, 0, '', 0, 0, (empty($dataEvent->array_options['options_dolipad_is_intercombi'])), '', 'minwidth200 ', 0);
		print '</td>';
	}

	print '<td>';
	$checked = '';
	if (!empty($dataEvent->array_options['options_dolipad_is_baes'])) {
		$checked = 'checked="checked"';
	}
	print '<input type="checkbox" ' . $checked . ' class="flat chckboxIsBaes" data-evtid="' . $dataEvent->id . '" id="dolipad_is_baes_' . $dataEvent->id . '" name="dolipad_is_baes_' . $dataEvent->id . '" value="1"/>';
	print '</td>';

	print '</tr>';
}

print '</table>';

print dol_get_fiche_end();
print '<div class="center">';
print'<input type="submit" class="button button-save" name="saveplannif" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
print'<input type="submit" class="button button-end" name="endplannif" value="' . dol_escape_htmltag($langs->trans('DlpdFinnishMultiEvt')) . '">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();


/**
 * Sort Method for event display
 * @param $evt1 ActionComm Evt
 * @param $evt2 ActionComm Evt
 * @return int
 */
function _sortByDateStart($evt1, $evt2)
{
	if ($evt1->datep == $evt2->datep) {
		return 0;
	}
	return ($evt1->datep < $evt2->datep) ? -1 : 1;
}

/**
 * Get Event data from session
 * @param $create_from_element_type String Element type
 * @param $create_from_element_id Int Element Id
 * @return array
 */
function _getAndSortEventArray($create_from_element_type = '', $create_from_element_id = 0)
{
	global $db;

	$eventArray = [];

	foreach ($_SESSION["multi_evt"] as $idEvent) {
		$event = new ActionComm($db);
		$resultFetch = $event->fetch($idEvent);
		if ($resultFetch < 0) {
			setEventMessages($event->error, $event->errors, 'errors');
		}
		if (!empty($create_from_element_type) && !empty($create_from_element_id)) {
			if ($create_from_element_type == $event->elementtype
				&& $create_from_element_id == $event->elementid) {
				$eventArray[] = $event;
			}
		} else {
			$eventArray[] = $event;
		}
	}

	usort($eventArray, "_sortByDateStart");

	return $eventArray;
}
