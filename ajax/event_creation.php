<?php
/* Copyright (C) 2022 Florian HENRY <florian.henry@scopen.fr>
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
 *       \file       dolipad/ajax/event_creation.php
 *       \brief      manage event_creation
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


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
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';

$action = GETPOST('action', 'aZ09');
$createFromElementId = GETPOST('createFromElementId', 'int');
$createFromElementType = GETPOST('createFromElementType', 'alpha');
$typeEvent = GETPOST('typeEvent', 'alpha');
$evtId = GETPOST('evtId', 'int');
$userAffected = GETPOST('userAffected', 'none');
$userOwnerId = GETPOST('userOwnerId', 'int');
$dtStYear = GETPOST('dtStYear', 'int');
$dtStMonth = GETPOST('dtStMonth', 'int');
$dtStDay = GETPOST('dtStDay', 'int');
$dtStHour = GETPOST('dtStHour', 'int');
$dtStMin = GETPOST('dtStMin', 'int');
$dtEndYear = GETPOST('dtEndYear', 'int');
$dtEndMonth = GETPOST('dtEndMonth', 'int');
$dtEndDay = GETPOST('dtEndDay', 'int');
$dtEndHour = GETPOST('dtEndHour', 'int');
$dtEndMin = GETPOST('dtEndMin', 'int');

$userAffected=json_decode(urldecode($userAffected), true);
$userAffectedIds=[];
if (!empty($userAffected)) {
	foreach ($userAffected as $valu) {
		if ($valu!==$userOwnerId) {
			$userAffectedIds[$valu] = array('id' => $valu, 'transparency' => 0);
		}
	}
}

$datep = dol_mktime($dtStHour, $dtStMin, 0, $dtStMonth, $dtStDay, $dtStYear);
$datef = dol_mktime($dtEndHour, $dtEndMin, 0, $dtEndMonth, $dtEndDay, $dtEndYear);

$langs->load('dolipad@dolipad');

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}
dol_syslog(__FILE__ . ' action=' . $action, LOG_DEBUG);

top_httphead();
$ret = [];
$errors = [];

$actionLog = ' Ajax baes  action:' . $action;
$actionLog .= ' createFromElementType:' . $createFromElementType;
$actionLog .= ' createFromElementId:' . $createFromElementId;
$actionLog .= ' typeEvent:' . $typeEvent;
$actionLog .= ' evtId:' . $evtId;
$actionLog .= ' $user->id:' . $user->id;
$actionLog .= ' userAffected:' . $userAffected;
$actionLog .= ' userOwnerId:' . $userOwnerId;


if ($action == 'createEvent') {
	$evtDescription='';
	$evtSocId='';

	if (empty($typeEvent)) {
		$typeEvent = dol_getIdFromCode($db, 'AC_OTH', 'c_actioncomm');
	}

	if ($createFromElementType=="fichinter") {
		$inter = new Fichinter($db);
		$result = $inter->fetch($createFromElementId);
		if ($result < 0) {
			setEventMessages($inter->error, $inter->errors, 'errors');
		}
		$result = $inter->fetch_thirdparty();
		if ($result < 0) {
			setEventMessages($inter->error, $inter->errors, 'errors');
		}

		if (!$user->hasRight('agenda', 'allactions', 'create') || empty($inter->id)) {
			print json_encode(array($langs->transnoentities('DlpdHowDoYouGetHere')));
			dol_syslog($actionLog . '$inter->id=' . $inter->id . ' Error=DlpdHowDoYouGetHere', LOG_ERR);
			print json_encode($errors);
			header('HTTP/1.1 500 Internal Server Error');
			exit();
		}

		$evtDescription=$inter->description;
		$evtSocId=$inter->socid;
	} else {
		$langs->load('commercial');
		$cactioncomm = new CActionComm($db);
		$result = $cactioncomm->fetch($typeEvent);
		if ($result<0) {
			$errors[] = $cactioncomm->error;
			dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
			print json_encode($errors);
			header('HTTP/1.1 500 Internal Server Error');
			exit();
		}
		$evtDescription=$langs->trans("Action".$cactioncomm->code);
	}

	$event = new ActionComm($db);
	$event->userownerid = $userOwnerId;
	$event->userassigned = $userAffectedIds;
	$event->label = $evtDescription;
	$event->type_code = $typeEvent;
	$event->percentage = 0;
	$event->socid = $evtSocId;
	$event->datep = $datep;
	$event->datef = $datef;
	$event->fk_element = $createFromElementId;
	$event->elementtype = $createFromElementType;
	$result = $event->create($user);
	if ($result < 0) {
		$errors = array_merge($errors, $event->errors);
		$errors[] = $event->error;
		dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
		print json_encode($errors);
		header('HTTP/1.1 500 Internal Server Error');
		exit();
	}
	$ret = [$event->id];
	$_SESSION["multi_evt"][]=$event->id;

	dol_include_once('/buildingmanagement/class/buildingmanagementhelpers.class.php');
	if (!empty($event->id) && class_exists('BuildingManagementHelpers') && $createFromElementType=="fichinter") {
		$bmHelpers = new BuildingManagementHelpers($db);
		$resultBuilding = $bmHelpers->getResidenceAndBuilding($inter->element, $inter->id);
		if (!is_array($result) && $result < 0) {
			$errors = array_merge($errors, $bmHelpers->errors);
			$errors[] = $bmHelpers->error;
			dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
			print json_encode($errors);
			header('HTTP/1.1 500 Internal Server Error');
			exit();
		}

		$result = $bmHelpers->setResidenceAndBuilding($event, $resultBuilding['residenceid'], $resultBuilding['buildingid']);
		if ($result < 0) {
			$errors = array_merge($errors, $bmHelpers->errors);
			$errors[] = $bmHelpers->error;
			dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
			print json_encode($errors);
			header('HTTP/1.1 500 Internal Server Error');
			exit();
		}
	}
}

if ($action=='checkAvailability') {
	if (!empty($userAffectedIds)) {
		$warningOccupy = [];

		foreach ($userAffectedIds as $uid => $data) {
			$sql = 'SELECT DISTINCT a.id as evtid, a.datep as dtst, a.datep2 as dtend, a.label as evtlabel';
			$sql .= ' FROM ' . $db->prefix() . 'actioncomm as a';
			$sql .= ' LEFT JOIN ' . $db->prefix() . 'actioncomm_resources as ar ON a.id=ar.fk_actioncomm';
			$sql .= ' WHERE ((a.fk_user_action=' . (int) $uid . ')';
			$sql .= ' 	OR (ar.element_type="user" AND ar.fk_element=' . (int) $uid . '))';
			$sql .= ' AND "' . $db->idate($datep) . '"<=a.datep2 ';
			$sql .= ' AND a.datep<="' . $db->idate($datef) . '"';
			$sql .= ' AND a.fk_action<>' . (int) dol_getIdFromCode($db, 'AC_OTH_AUTO', 'c_actioncomm');
			//In case where we were call
			if (!empty($evtId)) {
				$sql .= ' AND a.id<>' . (int) $evtId;
			}

			$resql = $db->query($sql);
			if (!$resql) {
				$errors = array_merge($errors, $db->lasterror);
				$errors[] = $db->lasterror;
				dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
				print json_encode($errors);
				header('HTTP/1.1 500 Internal Server Error');
				exit();
			} else {
				while ($obj = $db->fetch_object($resql)) {
					$usrTgt = new User($db);
					$usrTgt->fetch($uid);
					$evtTgt = new ActionComm($db);
					$evtTgt->fetch($obj->evtid);
					$warningOccupy[] = $langs->transnoentities('DldpUserOccupyByEvent', $usrTgt->getFullName($langs), $evtTgt->getNomUrl(0, 0, '', '', 0, 1));
				}
				$ret = $warningOccupy;
			}
		}
	}
}

if ($action=='getMultiEvtData') {
	if (!empty($_SESSION['multi_evt'])) {
		foreach ($_SESSION['multi_evt'] as $idEvent) {
			$event = new ActionComm($db);
			$resultFetch=$event->fetch($idEvent);
			if ($resultFetch<0) {
				$errors = array_merge($errors, $event->errors);
				$errors[] = $event->error;
				dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
				print json_encode($errors);
				header('HTTP/1.1 500 Internal Server Error');
				exit();
			}
			unset($event->db);
			$userOwner = new User($db);
			$resultFetch = $userOwner->fetch($event->userownerid);
			if ($resultFetch < 0) {
				$errors = array_merge($errors, $userOwner->errors);
				$errors[] = $userOwner->error;
				dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
				print json_encode($errors);
				header('HTTP/1.1 500 Internal Server Error');
				exit();
			}
			$ret[$event->id]=array(
				'id'=>$event->id,
				'dated'=>dol_print_date($event->datep, 'dayhour'),
				'datef'=>dol_print_date($event->datef, 'dayhour'),
				'usersAssign'=>array(),
				'userOwner'=>$userOwner->getFullName($langs)
			);
			if (!empty($event->userassigned)) {
				foreach ($event->userassigned as $userAssignId=>$dataUsrAssign) {
					if ($dataUsrAssign['id']!=$event->userownerid) {
						$userAssign = new User($db);
						$resultFetch = $userAssign->fetch($userAssignId);
						if ($resultFetch < 0) {
							$errors = array_merge($errors, $userAssign->errors);
							$errors[] = $userAssign->error;
							dol_syslog($actionLog . ' Error=' . var_export($errors, true), LOG_ERR);
							print json_encode($errors);
							header('HTTP/1.1 500 Internal Server Error');
							exit();
						}
						$ret[$event->id]['usersAssign'][$userAssign->id] = $userAssign->getFullName($langs);
					}
				}
			}
		}
	}
}

print json_encode($ret);
