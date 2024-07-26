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
 *    \file       /dolipad/comm/action/peruser_eventcreation.php
 *        \ingroup    dolipad
 *        \brief      Copy of peruser comm/action/peruser.php to manage 4 case per days cell and manage event creation on cell selection
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
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';


if (!getDolGlobalInt('AGENDA_MAX_EVENTS_DAY_VIEW')) {
	$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW = 3;
}

$action = GETPOST('action', 'aZ09');

$filter = GETPOST("search_filter", 'alpha', 3) ? GETPOST("search_filter", 'alpha', 3) : GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("search_filtert", "int", 3) ? GETPOST("search_filtert", "int", 3) : GETPOST("filtert", "int", 3);
$usergroup = GETPOST("search_usergroup", "int", 3) ? GETPOST("search_usergroup", "int", 3) : GETPOST("usergroup", "int", 3);
//if (! ($usergroup > 0) && ! ($filtert > 0)) $filtert = $user->id;
//$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday","int"):1;
$showbirthday = 0;


$create_from_element_id = GETPOST('create_from_element_id', 'int');
$create_from_element_type = GETPOST('create_from_element_type', 'alpha');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "a.datec";
}

$socid = GETPOST("search_socid", "int") ? GETPOST("search_socid", "int") : GETPOST("socid", "int");
if ($user->socid) {
	$socid = $user->socid;
}
if ($socid < 0) {
	$socid = '';
}

if (empty(isModEnabled("dolimeet"))) accessforbidden();
$canedit = 1;
if (empty($user->hasRight('agenda', 'myactions', 'read'))) {
	accessforbidden();
}
if (empty($user->hasRight('agenda', 'allactions', 'read'))) {
	$canedit = 0;
}
if (empty($user->hasRight('agenda', 'allactions', 'read')) || $filter == 'mine') {  // If no permission to see all, we show only affected to me
	$filtert = $user->id;
}

$mode = 'show_peruser';
$resourceid = GETPOST("search_resourceid", "int") ? GETPOST("search_resourceid", "int") : GETPOST("resourceid", "int");
$year = GETPOST("year", "int") ? GETPOST("year", "int") : date("Y");
$month = GETPOST("month", "int") ? GETPOST("month", "int") : date("m");
$week = GETPOST("week", "int") ? GETPOST("week", "int") : date("W");
$day = GETPOST("day", "int") ? GETPOST("day", "int") : date("d");
$pid = GETPOST("search_projectid", "int", 3) ? GETPOST("search_projectid", "int", 3) : GETPOST("projectid", "int", 3);
$status = GETPOSTISSET("search_status") ? GETPOST("search_status", 'aZ09') : GETPOST("status", 'aZ09'); // status may be 0, 50, 100, 'todo', 'na' or -1
$type = GETPOST("search_type", 'alpha') ? GETPOST("search_type", 'alpha') : GETPOST("type", 'alpha');
$maxprint = ((GETPOST("maxprint", 'int') != '') ? GETPOST("maxprint", 'int') : getDolGlobalInt('AGENDA_MAX_EVENTS_DAY_VIEW'));
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
// Set actioncode (this code must be same for setting actioncode into peruser, listacton and index)
if (GETPOST('search_actioncode', 'array:aZ09')) {
	$actioncode = GETPOST('search_actioncode', 'array:aZ09', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("search_actioncode", "alpha", 3) ? GETPOST("search_actioncode", "alpha", 3) : (GETPOST("search_actioncode", "alpha") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE'));
}
if ($actioncode == '' && empty($actioncodearray)) {
	$actioncode = getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE');
}
$multievt = GETPOST('multievt', 'int');
$clean_session_multievt = GETPOST('clean_session_multievt', 'int');
if (!empty($multievt) && !empty($clean_session_multievt)) {
	$_SESSION["multi_evt"] = array();
}

$dateselect = dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'int'), GETPOST('dateselectday', 'int'), GETPOST('dateselectyear', 'int'));
if ($dateselect > 0) {
	$day = GETPOST('dateselectday', 'int');
	$month = GETPOST('dateselectmonth', 'int');
	$year = GETPOST('dateselectyear', 'int');
}

$tmp = !getDolGlobalString('MAIN_DEFAULT_WORKING_HOURS') ? '9-18' : getDolGlobalString('MAIN_DEFAULT_WORKING_HOURS');
$tmp = str_replace(' ', '', $tmp); // FIX 7533
$tmparray = explode('-', $tmp);
$begin_h = GETPOST('begin_h', 'int') != '' ? GETPOST('begin_h', 'int') : ($tmparray[0] != '' ? $tmparray[0] : 9);
$end_h = GETPOST('end_h', 'int') ? GETPOST('end_h', 'int') : ($tmparray[1] != '' ? $tmparray[1] : 18);
if ($begin_h < 0 || $begin_h > 23) {
	$begin_h = 9;
}
if ($end_h < 1 || $end_h > 24) {
	$end_h = 18;
}
if ($end_h <= $begin_h) {
	$end_h = $begin_h + 1;
}

$tmp = !getDolGlobalString('MAIN_DEFAULT_WORKING_DAYS') ? '1-5' : getDolGlobalString('MAIN_DEFAULT_WORKING_DAYS');
$tmp = str_replace(' ', '', $tmp); // FIX 7533
$tmparray = explode('-', $tmp);
$begin_d = GETPOST('begin_d', 'int') ? GETPOST('begin_d', 'int') : ($tmparray[0] != '' ? $tmparray[0] : 1);
$end_d = GETPOST('end_d', 'int') ? GETPOST('end_d', 'int') : ($tmparray[1] != '' ? $tmparray[1] : 5);
if ($begin_d < 1 || $begin_d > 7) {
	$begin_d = 1;
}
if ($end_d < 1 || $end_d > 7) {
	$end_d = 7;
}
if ($end_d < $begin_d) {
	$end_d = $begin_d + 1;
}

if ($status == '' && !GETPOSTISSET('search_status')) {
	$status = getDolGlobalString('AGENDA_DEFAULT_FILTER_STATUS');
}

if (empty($mode) && !GETPOSTISSET('mode')) {
	$mode = (!getDolGlobalString('AGENDA_DEFAULT_VIEW') ? 'show_month' : getDolGlobalString('AGENDA_DEFAULT_VIEW'));
}

if (GETPOST('viewcal', 'alpha') && $mode != 'show_day' && $mode != 'show_week' && $mode != 'show_peruser') {
	$mode = 'show_month';
	$day = '';
} // View by month
if (GETPOST('viewweek', 'alpha') || $mode == 'show_week') {
	$mode = 'show_week';
	$week = ($week ? $week : date("W"));
	$day = ($day ? $day : date("d"));
} // View by week
if (GETPOST('viewday', 'alpha') || $mode == 'show_day') {
	$mode = 'show_day';
	$day = ($day ? $day : date("d"));
} // View by day

// Load translation files required by the page
$langs->loadLangs(array('users', 'agenda', 'other', 'commercial'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('dolimeet_agenda'));

$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}


/*
 * Actions
 */


if ($action == 'confirm_delete_action') {
	$event = new ActionComm($db);
	$res = $event->fetch(GETPOST('actionid', 'int'));
	if ($res < 0) {
		setEventMessages($event->error, $event->errors, 'errors');
	} else {
		$permissiondelete = ($user->hasRight('agenda', 'allactions', 'delete') ||
			(($event->authorid == $user->id || $event->userownerid == $user->id)
				&& $user->hasRight('agenda', 'myactions', 'delete')));
		if ($permissiondelete) {
			$event->fetch_optionals();
			$event->fetch_userassigned();
			$event->oldcopy = clone $event;
			$result = $event->delete();
			if ($result < 0) {
				setEventMessages($event->error, $event->errors, 'errors');
			}
			$queryStrParam[] = 'token=' . newToken();
			if (getDolGlobalString('DOLIPAD_USERGROUP_TECH')) {
				$queryStrParam[] = "usergroup=" . getDolGlobalString('DOLIPAD_USERGROUP_TECH');
			}
			if (GETPOST('create_from_element_id', 'int')) {
				$queryStrParam[] = 'create_from_element_id=' . GETPOST('create_from_element_id', 'int');
			}
			if (GETPOST('create_from_element_type', 'alpha')) {
				$queryStrParam[] = 'create_from_element_type=' . GETPOST('create_from_element_type', 'alpha');
			}
			header('Location:' . dol_buildpath('/dolipad/comm/action/peruser_eventcreation.php', 2) . '?' . implode('&', $queryStrParam));
			exit();
		}
	}
}


/*
 * View
 */

$parameters = array(
	'socid' => $socid,
	'status' => $status,
	'year' => $year,
	'month' => $month,
	'day' => $day,
	'type' => $type,
	'maxprint' => $maxprint,
	'filter' => $filter,
	'filtert' => $filtert,
	'showbirthday' => $showbirthday,
	'canedit' => $canedit,
	'optioncss' => $optioncss,
	'actioncode' => $actioncode,
	'pid' => $pid,
	'resourceid' => $resourceid,
	'usergroup' => $usergroup,
);
$reshook = $hookmanager->executeHooks('beforeAgendaPerUser', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$form = new Form($db);
$companystatic = new Societe($db);
$formactions = new FormActions($db);

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda';
llxHeader('', $langs->trans("Agenda"), $help_url, '', 0, 0, array(), array(dol_buildpath('/dolimeet/css/dolimeet_eventcreation.css', 2)));

$now = dol_now();
$nowarray = dol_getdate($now);
$nowyear = $nowarray['year'];
$nowmonth = $nowarray['mon'];
$nowday = $nowarray['mday'];


// Define list of all external calendars (global setup)
$listofextcals = array();

$prev = dol_get_first_day_week($day, $month, $year);
$first_day = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year = $prev['first_year'];

$week = $prev['week'];

$day = (int) $day;
$next = dol_get_next_week($day, $week, $month, $year);
$next_year = $next['year'];
$next_month = $next['month'];
$next_day = $next['day'];

$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

$tmpday = $first_day;
//print 'xx'.$prev_year.'-'.$prev_month.'-'.$prev_day;
//print 'xx'.$next_year.'-'.$next_month.'-'.$next_day;

$title = $langs->trans("DoneAndToDoActions");
if ($status == 'done') {
	$title = $langs->trans("DoneActions");
}
if ($status == 'todo') {
	$title = $langs->trans("ToDoActions");
}

$param = '';
if ($actioncode || GETPOSTISSET('search_actioncode')) {
	if (is_array($actioncode)) {
		foreach ($actioncode as $str_action) {
			$param .= "&search_actioncode[]=" . urlencode($str_action);
		}
	} else {
		$param .= "&search_actioncode=" . urlencode($actioncode);
	}
}
if ($resourceid > 0) {
	$param .= "&search_resourceid=" . urlencode($resourceid);
}
if ($status || GETPOSTISSET('status')) {
	$param .= "&search_status=" . urlencode($status);
}
if ($filter) {
	$param .= "&search_filter=" . urlencode($filter);
}
if ($filtert) {
	$param .= "&search_filtert=" . urlencode($filtert);
}
if ($usergroup > 0) {
	$param .= "&search_usergroup=" . urlencode($usergroup);
}
if ($socid > 0) {
	$param .= "&search_socid=" . urlencode($socid);
}
if ($showbirthday) {
	$param .= "&search_showbirthday=1";
}
if ($pid) {
	$param .= "&search_projectid=" . urlencode($pid);
}
if ($type) {
	$param .= "&search_type=" . urlencode($type);
}
if ($mode != 'show_peruser') {
	$param .= '&mode=' . urlencode($mode);
}
if ($begin_h != '') {
	$param .= '&begin_h=' . urlencode($begin_h);
}
if ($end_h != '') {
	$param .= '&end_h=' . urlencode($end_h);
}
if ($begin_d != '') {
	$param .= '&begin_d=' . urlencode($begin_d);
}
if ($end_d != '') {
	$param .= '&end_d=' . urlencode($end_d);
}
if (!empty($create_from_element_id)) {
	$param .= '&create_from_element_id=' . urlencode($create_from_element_id);
}
if (!empty($create_from_element_type)) {
	$param .= '&create_from_element_type=' . urlencode($create_from_element_type);
}
if (!empty($multievt)) {
	$param .= '&multievt=' . urlencode($multievt);
}
$param .= "&maxprint=" . urlencode($maxprint);

$paramnoactionodate = $param;

$prev = dol_get_first_day_week($day, $month, $year);
//print "day=".$day." month=".$month." year=".$year;
//var_dump($prev); exit;
$prev_year = $prev['prev_year'];
$prev_month = $prev['prev_month'];
$prev_day = $prev['prev_day'];
$first_day = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year = $prev['first_year'];

$week = $prev['week'];

$day = (int) $day;
$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
$next_year = $next['year'];
$next_month = $next['month'];
$next_day = $next['day'];

// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year, 'gmt');

$nb_weeks_to_show = (getDolGlobalString('AGENDA_NB_WEEKS_IN_VIEW_PER_USER')) ? ((int) getDolGlobalString('AGENDA_NB_WEEKS_IN_VIEW_PER_USER') * 7) : 7;
$lastdaytoshow = dol_time_plus_duree($firstdaytoshow, $nb_weeks_to_show, 'd');
//print $firstday.'-'.$first_month.'-'.$first_year;
//print dol_print_date($firstdaytoshow,'dayhour');
//print dol_print_date($lastdaytoshow,'dayhour');

$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year, 'gmt'));

$tmpday = $first_day;
$picto = 'calendarweek';

$nav = "<a href=\"?year=" . $prev_year . "&amp;month=" . $prev_month . "&amp;day=" . $prev_day . $param . "\"><i class=\"fa fa-chevron-left\" title=\"" . dol_escape_htmltag($langs->trans("Previous")) . "\"></i></a> &nbsp; \n";
$nav .= " <span id=\"month_name\">" . dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y") . ", " . $langs->trans("Week") . " " . $week;
$nav .= " </span>\n";
$nav .= " &nbsp; <a href=\"?year=" . $next_year . "&amp;month=" . $next_month . "&amp;day=" . $next_day . $param . "\"><i class=\"fa fa-chevron-right\" title=\"" . dol_escape_htmltag($langs->trans("Next")) . "\"></i></a>\n";
if (empty($conf->dol_optimize_smallscreen)) {
	$nav .= " &nbsp; <a href=\"?year=" . $nowyear . "&amp;month=" . $nowmonth . "&amp;day=" . $nowday . $param . "\">" . $langs->trans("Today") . "</a> ";
}
$nav .= $form->selectDate($dateselect, 'dateselect', 0, 0, 1, '', 1, 0);
$nav .= ' <button type="submit" class="liste_titre button_search" name="button_search_x" value="x"><span class="fa fa-search"></span></button>';

// Must be after the nav definition
$param .= '&year=' . urlencode($year) . '&month=' . urlencode($month) . ($day ? '&day=' . urlencode($day) : '');
//print 'x'.$param;


$paramnoaction = preg_replace('/action=[a-z_]+/', '', $param);

$head = calendars_prepare_head($paramnoaction);
echo "<div class=\"div-table-responsive liste_titre liste_titre_bydiv centpercent dragStyleEventLoading title \">" . $langs->trans('DlpdLoading') . "</div>";
if (!getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_INTER') && $create_from_element_type == "fichinter" && empty($multievt)) {
	setEventMessages(null, $langs->trans('DlpdConfNotCompleteEventCreationSimple'), 'errors');
}
if (!getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_INTER_MULTI') && $create_from_element_type == "fichinter" && !empty($multievt)) {
	setEventMessages(null, $langs->trans('DlpdConfNotCompleteEventCreationMulti'), 'errors');
}


print '<form method="POST" id="searchFormList" class="listactionsfilter" action="' . $_SERVER["PHP_SELF"] . '" style="display:none">' . "\n";

if (!empty($create_from_element_id)) {
	print '<input type="hidden" name="create_from_element_id" id="create_from_element_id" value="' . $create_from_element_id . '">';
}
if (!empty($create_from_element_type)) {
	print '<input type="hidden" name="create_from_element_type" id="create_from_element_type" value="' . $create_from_element_type . '">';
}
if (!empty($multievt)) {
	print '<input type="hidden" name="multievt" id="multievt" value="' . $multievt . '">';
}

$showextcals = $listofextcals;
// Legend
if ($conf->use_javascript_ajax) {
	$s = '';
	$s .= '<script type="text/javascript">' . "\n";
	$s .= 'jQuery(document).ready(function () {' . "\n";
	$s .= 'jQuery("#check_mytasks").click(function() { jQuery(".family_mytasks").toggle(); jQuery(".family_other").toggle(); });' . "\n";
	$s .= 'jQuery("#check_birthday").click(function() { jQuery(".family_birthday").toggle(); });' . "\n";
	$s .= 'jQuery(".family_birthday").toggle();' . "\n";
	if ($mode == "show_week" || $mode == "show_month" || empty($mode)) {
		$s .= 'jQuery( "td.sortable" ).sortable({connectWith: ".sortable",placeholder: "ui-state-highlight",items: "div:not(.unsortable)", receive: function( event, ui ) {';
	}
	$s .= '});' . "\n";
	$s .= '</script>' . "\n";
	if (!empty($conf->use_javascript_ajax)) {
		$s .= '<div class="nowrap clear float"><input type="checkbox" id="check_mytasks" name="check_mytasks" checked disabled> ' . $langs->trans("LocalAgenda") . ' &nbsp; </div>';
		if (is_array($showextcals) && count($showextcals) > 0) {
			foreach ($showextcals as $val) {
				$htmlname = md5($val['name']);
				$s .= '<script type="text/javascript">' . "\n";
				$s .= 'jQuery(document).ready(function () {' . "\n";
				$s .= '		jQuery("#check_ext' . $htmlname . '").click(function() {';
				$s .= ' 		/* alert("' . $htmlname . '"); */';
				$s .= ' 		jQuery(".family_ext' . $htmlname . '").toggle();';
				$s .= '		});' . "\n";
				$s .= '});' . "\n";
				$s .= '</script>' . "\n";
				$s .= '<div class="nowrap float"><input type="checkbox" id="check_ext' . $htmlname . '" name="check_ext' . $htmlname . '" checked> ' . $val ['name'] . ' &nbsp; </div>';
			}
		}

		//$s.='<div class="nowrap float"><input type="checkbox" id="check_birthday" name="check_birthday"> '.$langs->trans("AgendaShowBirthdayEvents").' &nbsp; </div>';

		// Calendars from hooks
		$parameters = array();
		$object = null;
		$reshook = $hookmanager->executeHooks('addCalendarChoice', $parameters, $object, $action);
		if (empty($reshook)) {
			$s .= $hookmanager->resPrint;
		} elseif ($reshook > 1) {
			$s = $hookmanager->resPrint;
		}
	}
}

$massactionbutton = '';

$viewmode = '';
$viewmode .= '<a class="btnTitle reposition" href="' . DOL_URL_ROOT . '/comm/action/list.php?mode=show_list&restore_lastsearch_values=1' . $paramnoactionodate . '">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("List"), 'object_list', 'class="imgforviewmode pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans("ViewList") . '</span></a>';

$viewmode .= '<a class="btnTitle reposition" href="' . DOL_URL_ROOT . '/comm/action/index.php?mode=show_month&year=' . dol_print_date($object->datep, '%Y') . '&month=' . dol_print_date($object->datep, '%m') . '&day=' . dol_print_date($object->datep, '%d') . $paramnoactionodate . '">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewCal"), 'object_calendarmonth', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans("ViewCal") . '</span></a>';

$viewmode .= '<a class="btnTitle reposition" href="' . DOL_URL_ROOT . '/comm/action/index.php?mode=show_week&year=' . dol_print_date($object->datep, '%Y') . '&month=' . dol_print_date($object->datep, '%m') . '&day=' . dol_print_date($object->datep, '%d') . $paramnoactionodate . '">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans("ViewWeek") . '</span></a>';

$viewmode .= '<a class="btnTitle reposition" href="' . DOL_URL_ROOT . '/comm/action/index.php?mode=show_day&year=' . dol_print_date($object->datep, '%Y') . '&month=' . dol_print_date($object->datep, '%m') . '&day=' . dol_print_date($object->datep, '%d') . $paramnoactionodate . '">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans("ViewDay") . '</span></a>';

$viewmode .= '<a class="btnTitle btnTitleSelected reposition marginrightonly" href="' . DOL_URL_ROOT . '/comm/action/peruser.php?mode=show_peruser&year=' . dol_print_date($object->datep, '%Y') . '&month=' . dol_print_date($object->datep, '%m') . '&day=' . dol_print_date($object->datep, '%d') . $paramnoactionodate . '">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans("ViewPerUser") . '</span></a>';

$viewmode .= '<span class="marginrightonly"></span>';

// Add more views from hooks
$parameters = array();
$object = null;
$reshook = $hookmanager->executeHooks('addCalendarView', $parameters, $object, $action);
if (empty($reshook)) {
	$viewmode .= $hookmanager->resPrint;
} elseif ($reshook > 1) {
	$viewmode = $hookmanager->resPrint;
}


$newparam = '';
$newcardbutton = '';
if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
	$tmpforcreatebutton = dol_getdate(dol_now(), true);

	$newparam .= '&month=' . urlencode(str_pad($month, 2, "0", STR_PAD_LEFT)) . '&year=' . urlencode($tmpforcreatebutton['year']);
	if ($begin_h !== '') {
		$newparam .= '&begin_h=' . urlencode($begin_h);
	}
	if ($end_h !== '') {
		$newparam .= '&end_h=' . urlencode($end_h);
	}
	if ($begin_d !== '') {
		$newparam .= '&begin_d=' . urlencode($begin_d);
	}
	if ($end_d !== '') {
		$newparam .= '&end_d=' . urlencode($end_d);
	}

	//$param='month='.$monthshown.'&year='.$year;
	$hourminsec = '100000';
	$newcardbutton .= dolGetButtonTitle($langs->trans("AddAction"), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/comm/action/card.php?action=create&datep=' . sprintf("%04d%02d%02d", $tmpforcreatebutton['year'], $tmpforcreatebutton['mon'], $tmpforcreatebutton['mday']) . $hourminsec . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . ($newparam ? '?' . $newparam : '')));
}

$num = '';

print_barre_liste($langs->trans("Agenda"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, -1, 'object_action', 0, $nav . '<span class="marginleftonly"></span>' . $newcardbutton, '', $limit, 1, 0, 1, $viewmode);

$link = '';
//print load_fiche_titre('', $link.' &nbsp; &nbsp; '.$nav.' '.$newcardbutton, '');

// Local calendar
$newtitle = '<div class="nowrap clear inline-block minheight30">';
$newtitle .= '<input type="checkbox" id="check_mytasks" name="check_mytasks" checked disabled> ' . $langs->trans("LocalAgenda") . ' &nbsp; ';
$newtitle .= '</div>';
//$newtitle=$langs->trans($title);

$s = $newtitle;

print $s;

print '<div class="liste_titre liste_titre_bydiv centpercent">';
if (empty($search_status)) {
	$search_status = '';
}
print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid);
print '</div>';


// Get event in an array
$eventarray = array();


// DEFAULT CALENDAR + AUTOEVENT CALENDAR + CONFERENCEBOOTH CALENDAR
$sql = 'SELECT';
if ($usergroup > 0) {
	//$sql .= " DISTINCT";
}
$sql .= ' a.id, a.label,';
$sql .= ' a.datep,';
$sql .= ' a.datep2,';
$sql .= ' a.percent,';
$sql .= ' a.fk_user_author,a.fk_user_action,';
$sql .= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql .= ' a.fk_soc, a.fk_contact, a.fk_element, a.elementtype, a.fk_project,';
$sql .= ' ca.code, ca.libelle as type_label, ca.color, ca.type as type_type, ca.picto as type_picto';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_actioncomm as ca, ' . MAIN_DB_PREFIX . "actioncomm as a";
if (empty($user->hasRight('societe', 'client', 'voir')) && !$socid) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
}
// We must filter on resource table
if ($resourceid > 0) {
	$sql .= ", " . MAIN_DB_PREFIX . "element_resources as r";
}
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "actioncomm_resources as ar";
	$sql .= " ON ar.fk_actioncomm = a.id AND ar.element_type='user'";
	if ($filtert > 0) {
		$sql .= " AND ar.fk_element = " . $filtert;
	}
	// User from usergroup are filtered after (on display) and this part of the query is just ultra low perf
	// I've check the index, it seems ok, but still ultra low....
	/*if ($usergroup > 0) {
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
		$sql .= " AND ugu.fk_usergroup = " . ((int) $usergroup);
	}*/
}

$sql .= ' WHERE a.fk_action = ca.id';

$sql .= ' AND a.entity IN (' . getEntity('agenda') . ')';
// Condition on actioncode
if (!empty($actioncode)) {
	if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " AND ca.type != 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " AND ca.type = 'systemauto'";
		} else {
			if ($actioncode == 'AC_OTH') {
				$sql .= " AND ca.type != 'systemauto'";
			}
			if ($actioncode == 'AC_OTH_AUTO') {
				$sql .= " AND ca.type = 'systemauto'";
			}
		}
	} else {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " AND ca.type != 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " AND ca.type = 'systemauto'";
		} else {
			if (is_array($actioncode)) {
				$sql .= " AND ca.code IN (" . $db->sanitize("'" . implode("','", $actioncode) . "'", 1) . ")";
			} else {
				$sql .= " AND ca.code IN (" . $db->sanitize("'" . implode("','", explode(',', $actioncode)) . "'", 1) . ")";
			}
		}
	}
}
if ($resourceid > 0) {
	$sql .= " AND r.element_type = 'action' AND r.element_id = a.id AND r.resource_id = " . ((int) $resourceid);
}
if ($pid) {
	$sql .= " AND a.fk_project = " . ((int) $pid);
}
if (empty($user->hasRight('societe', 'client', 'voir')) && !$socid) {
	$sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = " . ((int) $user->id) . ")";
}
if ($socid > 0) {
	$sql .= ' AND a.fk_soc = ' . ((int) $socid);
}

if ($mode == 'show_day') {
	$sql .= " AND (";
	$sql .= " (a.datep BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel')) . "'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel')) . "')";
	$sql .= " OR ";
	$sql .= " (a.datep2 BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel')) . "'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel')) . "')";
	$sql .= " OR ";
	$sql .= " (a.datep < '" . $db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel')) . "'";
	$sql .= " AND a.datep2 > '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel')) . "')";
	$sql .= ')';
} else {
	// To limit array
	$sql .= " AND (";
	$sql .= " (a.datep BETWEEN '" . $db->idate($firstdaytoshow - (60 * 60 * 24 * 2)) . "'"; // Start 2 day before $firstdaytoshow
	$sql .= " AND '" . $db->idate($lastdaytoshow + (60 * 60 * 24 * 2)) . "')"; // End 2 day after $lastdaytoshow
	$sql .= " OR ";
	$sql .= " (a.datep2 BETWEEN '" . $db->idate($firstdaytoshow - (60 * 60 * 24 * 2)) . "'";
	$sql .= " AND '" . $db->idate($lastdaytoshow + (60 * 60 * 24 * 2)) . "')";
	$sql .= " OR ";
	$sql .= " (a.datep < '" . $db->idate($firstdaytoshow - (60 * 60 * 24 * 2)) . "'";
	$sql .= " AND a.datep2 > '" . $db->idate($lastdaytoshow + (60 * 60 * 24 * 2)) . "')";
	$sql .= ')';
}
if ($type) {
	$sql .= " AND ca.id = " . ((int) $type);
}
if ($status == '0') {
	$sql .= " AND a.percent = 0";
}
if ($status == 'na') {
	// Not applicable
	$sql .= " AND a.percent = -1";
}
if ($status == '50') {
	// Running already started
	$sql .= " AND (a.percent > 0 AND a.percent < 100)";
}
if ($status == 'done' || $status == '100') {
	$sql .= " AND (a.percent = 100)";
}
if ($status == 'todo') {
	$sql .= " AND (a.percent >= 0 AND a.percent < 100)";
}
// Sort on date
$sql .= ' ORDER BY fk_user_action, datep'; //fk_user_action

dol_syslog("comm/action/peruser.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		// Discard auto action if option is on
		if (getDolGlobalString('AGENDA_ALWAYS_HIDE_AUTO') && $obj->code == 'AC_OTH_AUTO') {
			$i++;
			continue;
		}

		$datep = $db->jdate($obj->datep);
		$datep2 = $db->jdate($obj->datep2);

		// Create a new object action
		$event = new ActionComm($db);
		$event->id = $obj->id;
		$event->datep = $datep; // datep and datef are GMT date
		$event->datef = $datep2;
		$event->type_code = $obj->code;
		$event->type_color = $obj->color;
		$event->label = $obj->label;
		$event->percentage = $obj->percent;
		$event->authorid = $obj->fk_user_author; // user id of creator
		$event->userownerid = $obj->fk_user_action; // user id of owner
		$event->priority = $obj->priority;
		$event->fulldayevent = $obj->fulldayevent;
		$event->location = $obj->location;
		$event->transparency = $obj->transparency;

		$event->fk_project = $obj->fk_project;

		$event->socid = $obj->fk_soc;
		$event->contact_id = $obj->fk_contact;

		$event->fk_element = $obj->fk_element;
		$event->elementtype = $obj->elementtype;

		// Defined date_start_in_calendar and date_end_in_calendar property
		// They are date start and end of action but modified to not be outside calendar view.
		if ($event->percentage <= 0) {
			$event->date_start_in_calendar = $datep;
			if ($datep2 != '' && $datep2 >= $datep) {
				$event->date_end_in_calendar = $datep2;
			} else {
				$event->date_end_in_calendar = $datep;
			}
		} else {
			$event->date_start_in_calendar = $datep;
			if ($datep2 != '' && $datep2 >= $datep) {
				$event->date_end_in_calendar = $datep2;
			} else {
				$event->date_end_in_calendar = $datep;
			}
		}
		// Define ponctual property
		if ($event->date_start_in_calendar == $event->date_end_in_calendar) {
			$event->ponctuel = 1;
		}

		// Check values
		if ($event->date_end_in_calendar < $firstdaytoshow ||
			$event->date_start_in_calendar >= $lastdaytoshow) {
			// This record is out of visible range
			unset($event);
		} else {
			//print $i.' - '.dol_print_date($this->date_start_in_calendar, 'dayhour').' - '.dol_print_date($this->date_end_in_calendar, 'dayhour').'<br>'."\n";
			$event->fetch_userassigned(); // This load $event->userassigned

			if ($event->date_start_in_calendar < $firstdaytoshow) {
				$event->date_start_in_calendar = $firstdaytoshow;
			}
			if ($event->date_end_in_calendar >= $lastdaytoshow) {
				$event->date_end_in_calendar = ($lastdaytoshow - 1);
			}

			// Add an entry in actionarray for each day
			$daycursor = $event->date_start_in_calendar;
			$annee = dol_print_date($daycursor, '%Y', 'tzuserrel');
			$mois = dol_print_date($daycursor, '%m', 'tzuserrel');
			$jour = dol_print_date($daycursor, '%d', 'tzuserrel');
			//print $daycursor.' '.dol_print_date($daycursor, 'dayhour', 'gmt').' '.$event->id.' -> '.$annee.'-'.$mois.'-'.$jour.'<br>';

			// Loop on each day covered by action to prepare an index to show on calendar
			$loop = true;
			$j = 0;
			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');
			do {
				//if ($event->id==408) print 'daykey='.$daykey.' '.$event->datep.' '.$event->datef.'<br>';

				$eventarray[$daykey][] = $event;
				$j++;

				$daykey += 60 * 60 * 24;
				if ($daykey > $event->date_end_in_calendar) {
					$loop = false;
				}
			} while ($loop);

			//print 'Event '.$i.' id='.$event->id.' (start='.dol_print_date($event->datep).'-end='.dol_print_date($event->datef);
			//print ' startincalendar='.dol_print_date($event->date_start_in_calendar).'-endincalendar='.dol_print_date($event->date_end_in_calendar).') was added in '.$j.' different index key of array<br>';
		}
		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

$maxnbofchar = 18;
$cachethirdparties = array();
$cachecontacts = array();
$cacheusers = array();

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/theme_vars.inc.php";
if (is_readable($color_file)) {
	include $color_file;
}
if (!is_array($theme_datacolor)) {
	$theme_datacolor = array(array(120, 130, 150), array(200, 160, 180), array(190, 190, 220));
}


$newparam = $param; // newparam is for birthday links
$newparam = preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam); // To avoid replacement when replace day= is done
$newparam = preg_replace('/mode=show_month&?/i', '', $newparam);
$newparam = preg_replace('/mode=show_week&?/i', '', $newparam);
$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam); // Restore correct parameter
$newparam .= '&viewweek=1';

echo '<input type="hidden" name="actionmove" value="mupdate">';
echo '<input type="hidden" name="backtopage" value="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?' . dol_escape_htmltag($_SERVER['QUERY_STRING']) . '">';
echo '<input type="hidden" name="newdate" id="newdate">';


// Line header with list of days

//print "begin_d=".$begin_d." end_d=".$end_d;

$currentdaytoshow = $firstdaytoshow;
echo '<div class="div-table-responsive">';

while ($currentdaytoshow < $lastdaytoshow) {
	echo '<table class="centpercent noborder nocellnopadd cal_month dragStyleEvent">';

	echo '<tr class="liste_titre">';
	echo '<td class="nopaddingtopimp nopaddingbottomimp nowraponsmartphone">';

	if ($canedit && $mode == 'show_peruser') {
		// Filter on hours
		print img_picto('', 'clock', 'class="fawidth30 inline-block paddingleft"');
		print '<span class="hideonsmartphone" title="' . $langs->trans("VisibleTimeRange") . '">' . $langs->trans("Hours") . '</span>';
		print "\n" . '<div class="ui-grid-a inline-block"><div class="ui-block-a nowraponall">';
		print '<input type="number" class="short" name="begin_h" value="' . $begin_h . '" min="0" max="23">';
		if (empty($conf->dol_use_jmobile)) {
			print ' - ';
		} else {
			print '</div><div class="ui-block-b">';
		}
		print '<input type="number" class="short" name="end_h" value="' . $end_h . '" min="1" max="24">';
		if (empty($conf->dol_use_jmobile)) {
			print ' ' . $langs->trans("H");
		}
		print '</div></div>';

		print '<br>';

		// Filter on days
		print img_picto('', 'clock', 'class="fawidth30 inline-block paddingleft"');
		print '<span class="hideonsmartphone" title="' . $langs->trans("VisibleDaysRange") . '">' . $langs->trans("DaysOfWeek") . '</span>';
		print "\n" . '<div class="ui-grid-a  inline-block"><div class="ui-block-a nowraponall">';
		print '<input type="number" class="short" name="begin_d" value="' . $begin_d . '" min="1" max="7">';
		if (empty($conf->dol_use_jmobile)) {
			print ' - ';
		} else {
			print '</div><div class="ui-block-b">';
		}
		print '<input type="number" class="short" name="end_d" value="' . $end_d . '" min="1" max="7">';
		print '</div></div>';
	}

	print '</td>';
	$i = 0; // 0 = sunday,
	while ($i < 7) {
		if (($i + 1) < $begin_d || ($i + 1) > $end_d) {
			$i++;
			continue;
		}
		echo '<td align="center" colspan="' . ($end_h - $begin_h) . '">';
		echo '<span class="bold spandayofweek">' . $langs->trans("Day" . (($i + (getDolGlobalInt('MAIN_START_WEEK') ? getDolGlobalInt('MAIN_START_WEEK') : 1)) % 7)) . '</span>';
		print "<br>";
		if ($i) {
			print dol_print_date(dol_time_plus_duree($currentdaytoshow, $i, 'd'), 'day');
		} else {
			print dol_print_date($currentdaytoshow, 'day');
		}
		echo "</td>\n";
		$i++;
	}
	echo "</tr>\n";

	echo '<tr class="liste_titre">';
	echo '<td></td>';
	$i = 0;
	while ($i < 7) {
		if (($i + 1) < $begin_d || ($i + 1) > $end_d) {
			$i++;
			continue;
		}
		for ($h = $begin_h; $h < $end_h; $h++) {
			echo '<td class="center">';
			print '<small>' . sprintf("%02d", $h) . '</small>';
			print "</td>";
		}
		echo "</td>\n";
		$i++;
	}
	echo "</tr>\n";


	// Define $usernames
	$usernames = array(); //init
	$usernamesid = array();
	/* Use this to have list of users only if users have events */
	if (getDolGlobalString('AGENDA_SHOWOWNERONLY_ONPERUSERVIEW')) {
		foreach ($eventarray as $daykey => $notused) {
			// Get all assigned users for each event
			foreach ($eventarray[$daykey] as $index => $event) {
				$event->fetch_userassigned();
				$listofuserid = $event->userassigned;
				foreach ($listofuserid as $userid => $tmp) {
					if (!in_array($userid, $usernamesid)) {
						$usernamesid[$userid] = $userid;
					}
				}
			}
		}
	} else {
		/* Use this list to have for all users */
		$sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut, u.login, u.admin, u.entity";
		$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
		if (!empty($conf->multicompany->enabled) && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug";
			$sql .= " ON ug.fk_user = u.rowid ";
			$sql .= " AND ug.entity IN (" . getEntity('usergroup') . ")";
			if ($usergroup > 0) {
				$sql .= " AND ug.fk_usergroup = " . ((int) $usergroup);
			}
		} else {
			if ($usergroup > 0) {
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug ON u.rowid = ug.fk_user AND ug.fk_usergroup = " . ((int) $usergroup);
			}
			$sql .= " WHERE u.entity IN (" . getEntity('user') . ")";
		}
		$sql .= " AND u.statut = 1";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					$usernamesid[$obj->rowid] = $obj->rowid;
					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
	}
	//var_dump($usernamesid);
	foreach ($usernamesid as $id) {
		$tmpuser = new User($db);
		$result = $tmpuser->fetch($id);
		$usernames[] = $tmpuser;
	}

	// Load array of colors by type
	$colorsbytype = array();
	$labelbytype = array();
	$sql = "SELECT code, color, libelle as label FROM " . MAIN_DB_PREFIX . "c_actioncomm ORDER BY position";
	$resql = $db->query($sql);
	while ($obj = $db->fetch_object($resql)) {
		$colorsbytype[$obj->code] = $obj->color;
		$labelbytype[$obj->code] = $obj->label;
	}

	// Loop on each user to show calendar
	$todayarray = dol_getdate($now, 'fast');
	$sav = $tmpday;
	$showheader = true;
	$var = false;
	foreach ($usernames as $username) {
		$var = !$var;
		echo '<tr>';
		echo '<td data-useridrow="' . $username->id . '" class="tdoverflowmax100 cal_current_month cal_peruserviewname' . ($var ? ' cal_impair' : '') . '">';
		print $username->getNomUrl(-1, '', 0, 0, 20, 1, '');
		print '</td>';
		$tmpday = $sav;

		// Lopp on each day of week
		$i = 0;
		for ($iter_day = 0; $iter_day < 8; $iter_day++) {
			if (($i + 1) < $begin_d || ($i + 1) > $end_d) {
				$i++;
				continue;
			}

			// Show days of the current week
			$curtime = dol_time_plus_duree($currentdaytoshow, $iter_day, 'd');
			$tmparray = dol_getdate($curtime, 'fast');
			$tmpday = $tmparray['mday'];
			$tmpmonth = $tmparray['mon'];
			$tmpyear = $tmparray['year'];
			//var_dump($curtime.' '.$tmpday.' '.$tmpmonth.' '.$tmpyear);

			$style = 'cal_current_month';
			if ($iter_day == 6) {
				$style .= ' cal_other_month';
			}
			$today = 0;
			if ($todayarray['mday'] == $tmpday && $todayarray['mon'] == $tmpmonth && $todayarray['year'] == $tmpyear) {
				$today = 1;
			}
			if ($today) {
				$style = 'cal_today_peruser';
			}

			show_day_events2($username, $tmpday, $tmpmonth, $tmpyear, 0, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, $showheader, $colorsbytype, $var);

			$i++;
		}
		echo "</tr>\n";
		$showheader = false;
	}

	echo "</table>\n";
	echo "<br>";

	$currentdaytoshow = dol_time_plus_duree($currentdaytoshow, 7, 'd');
}

echo '</div>';

if (getDolGlobalString('AGENDA_USE_EVENT_TYPE') && getDolGlobalString('AGENDA_USE_COLOR_PER_EVENT_TYPE')) {
	$langs->load("commercial");
	print '<br>' . $langs->trans("Legend") . ': <br>';
	foreach ($colorsbytype as $code => $color) {
		if ($color) {
			print '<div style="float: left; padding: 2px; margin-right: 6px;"><div style="' . ($color ? 'background: #' . $color . ';' : '') . 'width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
			print $langs->trans("Action" . $code) != "Action" . $code ? $langs->trans("Action" . $code) : $labelbytype[$code];
			//print $code;
			print '</div>';
		}
	}
	//$color=sprintf("%02x%02x%02x",$theme_datacolor[0][0],$theme_datacolor[0][1],$theme_datacolor[0][2]);
	print '<div style="float: left; padding: 2px; margin-right: 6px;"><div class="peruser_busy" style="width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
	print $langs->trans("Other");
	print '</div>';
	/* TODO Show this if at least one cumulated event
	print '<div style="float: left; padding: 2px; margin-right: 6px;"><div style="background: #222222; width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
	print $langs->trans("SeveralEvents");
	print '</div>';
	*/
}

print "\n" . '</form>';

print '<div style="display: none" id="eventCreation" title="' . (empty($multievt) ? $langs->trans("DlpdEventCreationHelper") : $langs->trans("DlpdEventCreationHelperMulti")) . '">';

print '<div>';
print '	<span id="eventCreationUserName">';
print '	</span>';
print '	<span id="sBtnAddEmployee">';
print dolGetButtonTitle($langs->trans("ActionAffectedTo"), '', 'fa fa-plus-circle', '', 'btnAddEmpoyee');
print '	</span>';
print '	<div id="employeeSelectList" style="display: none">';
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
print $form->multiselectarray('employees', $allEmployeesArray, array(), '', 0, '', 0, '100%');
print '	</div>';

print '</div>';
if ($create_from_element_type == 'fichinter' && !empty($create_from_element_id) && getDolGlobalInt('AGENDA_USE_EVENT_TYPE') && empty($multievt)) {
	print '<input type="hidden" name="actioncode" id="actioncode" value="' . getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_INTER') . '"/>';
} elseif ($create_from_element_type == 'fichinter' && !empty($create_from_element_id) && getDolGlobalInt('AGENDA_USE_EVENT_TYPE') && !empty($multievt)) {
		print '<input type="hidden" name="actioncode" id="actioncode" value="' . getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_INTER_MULTI') . '"/>';
} elseif (getDolGlobalInt('AGENDA_USE_EVENT_TYPE')) {
	print $langs->trans("Type") . ' ' . img_picto($langs->trans("ActionType"), 'square', 'class="fawidth30 inline-block" style="color: #ddd;"');
	print $formactions->select_type_actions(getDolGlobalString('DOLIPAD_TYPE_EVENT_CREATION_DEFAULT'), "actioncode", "systemauto", 0, 1, 0, 1);
} else {
	print '<input type="hidden" name="actioncode" id="actioncode" value="' . dol_getIdFromCode($db, 'AC_OTH', 'c_actioncomm') . '">';
}

print '<div id="eventCreationDateStart">';
print $langs->trans('DateActionStart');
print $form->selectDate("", "eventcreationdtstart", 1, 1);
print '</div>';

print '<div id="eventCreationDateEnd">';
print $langs->trans('DateActionEnd');
print $form->selectDate("", "eventcreationdtend", 1, 1);
print '</div>';

print '<input type="hidden" id="eventCreationUserId" />';
if (!empty($multievt)) {
	print '<div id="lstMultiEvt" style="display:none">';
	print '<span>' . $langs->trans('DpldEvAlreadyCreated') . '</span>';
	print '<ul id="lstMultiEvtDet"></ul>';
	print '</div>';
}

print '</div>';

print "\n";

?>
	<script type="text/javascript">
		$(document).ready(function () {

			$('div.dragStyleEventLoading').hide();
			$('#searchFormList').show();

			let multiEvt = <?php echo (int) $multievt?>;
			let UserData = [];
			<?php foreach ($usernames as $userdata) {?>
			UserData[<?php echo $userdata->id;?>] = "<?php echo $userdata->getFullname($langs);?>";
			<?php }?>

			let slotSelected = Array();

			function assignSlotSelected() {
				$('.selectedtimeslot').each(function () {
					let ref = $(this).data('timeslot');
					let res = ref.split("_");
					let year = res[2];
					let month = res[3];
					let day = res[4];
					let hour = res[5];
					let min = res[6];
					let dt = new Date(year, month - 1, day, hour, min);
					slotSelected.push({dt: dt, tmstp: $(this).data('timeslotstmp')});
				});
			}

			function designTimeSlot(userId, tmpStmpSt, tmpStmpEnd) {
				//Color all timeslot because mouseover don't always catch the event
				for (let i = tmpStmpSt; i < tmpStmpEnd; i = i + 900) {
					let objClass = '.u' + userId + '-t' + i;
					//console.log(objClass, $(objClass));
					$(objClass).addClass('selectedtimeslot');
					$(objClass).on("click", function () {
						if ($(this).hasClass('selectedtimeslot')) {
							//We are in resize mode
							//console.log('selectedtimeslot.click',$(this),isEventOnCreationProcess,isMouseDown);
							isEventOnCreationProcess = false;
							isEventOnResizeProcess = true;
							$("#eventCreation").dialog("close");
						}
					});
				}
			}

			function designTimeSlotMulti(userIds) {
				//Build TimeSlots array
				let timeSlots = [];
				$('.selectedtimeslot').each(function () {
					timeSlots.push($(this).data('timeslotstmp'));
				})
				$('.selectedtimeslot').addClass('selectedtimeslotmulti').removeClass('selectedtimeslot');
				//For each users and time slots update class or background clors
				userIds.forEach(function (userId) {
					timeSlots.forEach(function (timeSlot) {
						let objSelector = 'td[data-timeslotstmp="' + timeSlot + '"][data-userid="' + userId + '"]';
						if ($(objSelector).css('background-color') !== 'rgba(0, 0, 0, 0)') {
							$(objSelector).css('background-color', '#000000');
						} else {
							$(objSelector).addClass('selectedtimeslotmulti');
						}
					});
				});
			}

			function cleanTimeSlot() {
				//Unaffected Click event craated by designTimeSlot
				// and style class that use to know what is the timeslots selected
				$('.selectedtimeslot').off('click').removeClass('selectedtimeslot');
				slotSelected = Array();
			}

			function manageTimeSlot(showEventCreationBox = true) {

				assignSlotSelected();
				//console.log('manageTimeSlot',slotSelected.length,showEventCreationBox);
				if (slotSelected.length > 0) {
					let userId = $('#eventCreationUserId').val();
					slotSelected.sort((i, j) => Number(i.tmstp) - Number(j.tmstp))
					let firstTmStpm = Number(slotSelected[0].tmstp);
					let lastTmStpm = Number(slotSelected[slotSelected.length - 1].tmstp);

					designTimeSlot(userId, firstTmStpm, lastTmStpm);

					let firstTimeSlot = slotSelected[0].dt;
					$("#eventcreationdtstarthour").val((firstTimeSlot.getHours() < 10 ? '0' : '') + firstTimeSlot.getHours());
					$("#eventcreationdtstartmin").val((firstTimeSlot.getMinutes() < 10 ? '0' : '') + firstTimeSlot.getMinutes());
					$("#eventcreationdtstartday").val(firstTimeSlot.getDate());
					$("#eventcreationdtstartmonth").val(firstTimeSlot.getMonth() + 1);
					$("#eventcreationdtstartyear").val(firstTimeSlot.getFullYear());
					$("#eventcreationdtstart").datepicker("setDate", firstTimeSlot);


					let lastTimeSlot = slotSelected[slotSelected.length - 1].dt;
					lastTimeSlot.setMinutes(lastTimeSlot.getMinutes() + 15);
					$("#eventcreationdtendhour").val((lastTimeSlot.getHours() < 10 ? '0' : '') + lastTimeSlot.getHours());
					$("#eventcreationdtendmin").val((lastTimeSlot.getMinutes() < 10 ? '0' : '') + lastTimeSlot.getMinutes());
					$("#eventcreationdtendday").val(lastTimeSlot.getDate());
					$("#eventcreationdtendmonth").val(lastTimeSlot.getMonth() + 1);
					$("#eventcreationdtendyear").val(lastTimeSlot.getFullYear());
					$("#eventcreationdtend").datepicker("setDate", lastTimeSlot);

					if (showEventCreationBox) {
						$('#eventCreationUserName').text(UserData[userId]);
						$("#sBtnAddEmployee").show();
						$('#employees option').prop('disabled', false);
						$('#employees option[value=' + userId + ']').prop('disabled', true);
						$('#eventCreationDateStart').show();
						$('#eventCreationDateEnd').show();
						createDialogCreate();
					}
				}
			}

			function createDialogCreate() {
				$("#eventCreation").dialog(
					{
						autoOpen: true,
						resizable: false,
						position: {my: "right bottom", at: "right bottom"},
						height: 250,
						width: <?php echo (empty($multievt)?'400':'470') ?>,
						modal: false,
						closeOnEscape: true,
						buttons: {
							"<?php
							if (empty($multievt)) {
								echo dol_escape_js($langs->transnoentities("AddAction"));
							} else {
								echo dol_escape_js($langs->transnoentities("DlpdValidateAction"));
							}
							?>": async function () {
								// await is actually optional here
								// checkAvailability() return a Promise either way.
								let conflictEvts = await checkAvailability();
								$(this).dialog("close");
								if (conflictEvts.length > 0) {
									let conflictEvtsHTML = '';
									let confirmHeight = Math.min((conflictEvts.length * 50) + 200, 700);
									//console.log(conflictEvts.length * 220,confirmHeight)
									conflictEvts.forEach(conflictEvt => (conflictEvtsHTML += conflictEvt + '<br><br>'));
									$('#confirmeventcreationtext').html('<BR>' + conflictEvtsHTML);
									$("#confirmeventcreation").dialog(
										{
											autoOpen: true,
											resizable: false,
											height: confirmHeight,
											width: "700",
											modal: true,
											closeOnEscape: false,
											buttons: {
												"<?php echo dol_escape_js($langs->transnoentities("Yes"));?>": function () {
													$(this).dialog("close");
													createEvent();
												},
												"<?php echo dol_escape_js($langs->transnoentities("No"))?>": function () {
													$(this).dialog("close");
													cancelEvtCreation();
												}
											}
										}
									);
								} else {
									$(this).dialog("close");
									createEvent();
								}
							},
							"<?php echo dol_escape_js($langs->transnoentities("Cancel"))?>": function () {
								cancelEvtCreation();
								$(this).dialog("close");
							}
						}
					}
				);
			}

			function cancelEvtCreation() {
				cleanTimeSlot();

				$('#employees option[value=' + $('#eventCreationUserId').val() + ']').prop('disabled', false);
				$('#employees').val('');
				$('#employees').change();
				$("#employeeSelectList").hide();
				$("#sBtnAddEmployee").show();

				isMouseDown = false;
				isEventOnCreationProcess = false;
				isEventOnResizeProcess = false;
				$('#eventCreationUserId').val('');
			}

			$("#eventcreationdtstarthour").change(function () {
				reDesignTimeSlot()
			});
			$("#eventcreationdtstartmin").change(function () {
				reDesignTimeSlot()
			});
			$("#eventcreationdtendhour").change(function () {
				reDesignTimeSlot()
			});
			$("#eventcreationdtendmin").change(function () {
				reDesignTimeSlot()
			});

			$("#eventcreationdtstart").change(function () {
				setTimeout(() => reDesignTimeSlot(), 300);
			});
			$("#eventcreationdtend").change(function () {
				setTimeout(() => reDesignTimeSlot(), 300);
			});

			function reDesignTimeSlot() {
				let startDt = new Date(
					$("#eventcreationdtstartyear").val(),
					$("#eventcreationdtstartmonth").val() - 1,
					$("#eventcreationdtstartday").val(),
					$("#eventcreationdtstarthour").val(),
					$("#eventcreationdtstartmin").val())
				let endDt = new Date(
					$("#eventcreationdtendyear").val(),
					$("#eventcreationdtendmonth").val() - 1,
					$("#eventcreationdtendday").val(),
					$("#eventcreationdtendhour").val(),
					$("#eventcreationdtendmin").val())

				if (endDt < startDt) {
					$.jnotify("<?php echo $langs->trans('DlpdErrorBeforeLast') ?>",
						"error",
						true,
						{
							remove: function () {
							}
						});
					return false;
				}

				let userId = $('#eventCreationUserId').val();

				//round to the nearest 15 min
				let ms = 1000 * 60 * 15; // convert minutes to ms
				startDt = new Date(Math.round(startDt.getTime() / ms) * ms);

				let firstTmStpm = Number(startDt.getTime() / 1000);
				let lastTmStpm = Number(endDt.getTime() / 1000);

				//remove all timeslot selected to redesign all
				cleanTimeSlot();

				//console.log(startDt,firstTmStpm,endDt,lastTmStpm);
				designTimeSlot(userId, firstTmStpm, lastTmStpm);

				assignSlotSelected();
			}

			//On Click on an Empty timeslot it will create a Drag Style area selection
			//to create a proposal event creation
			let isMouseDown = false;
			let isEventOnCreationProcess = false;
			let isEventOnResizeProcess = false;

			$(".onclickdesignevent")
				.on("mousedown", function (event) {
					//console.log('onclickdesignevent.mousedown',isEventOnCreationProcess,isMouseDown);
					if (event.which === 1 && !isEventOnCreationProcess) {

						//We do not want selected busy slot
						if ($(this).hasClass("peruser_busy")) {
							return false;
						}

						//In this case we are in a restart schedule
						// by clicking on already selected timeslot
						if ($('#eventCreationUserId').val() !== ''
							&& Number($(this).data("userid")) !== Number($('#eventCreationUserId').val())) {
							return false;
						}

						isMouseDown = true;

						// we store the current user selection
						$('#eventCreationUserId').val($(this).data("userid"));
						//console.log('Affect createUserId',$('#eventCreationUserId').val());

						// we allow only one selection per dragStyle
						isEventOnCreationProcess = true;

						//This class will be used to collected all slots selected
						$(this).addClass("selectedtimeslot");

						// prevent text selection
						return false;

					}
				})
				.on("mouseover", function (event) {
					/*console.log('dragStyleEvent.mouseover',
					$(this).data("userid"),
					$('#eventCreationUserId').val());*/
					if (event.which === 1
						&& isMouseDown
						&& Number($('#eventCreationUserId').val()) === Number($(this).data("userid"))) {
						//console.log('onclickdesignevent.mouseover',isEventOnCreationProcess,isMouseDown);
						if ($(this).hasClass("peruser_busy")) {
							isMouseDown = false;
							// prevent text selection
							return false;
						}
						//This is just for the drag style event display
						// the real event start / end is manage on mouseup
						$(this).addClass("selectedtimeslot");
					}
				});
			// On mouse up where ever it is (at least on planning area)
			// we display the "form" to change date or assign other users
			$('table.dragStyleEvent').on("mouseup", function (event) {
				isMouseDown = false;
				/*console.log('dragStyleEvent.mouseup',$('.selectedtimeslot').length,
					isEventOnCreationProcess,
					isEventOnResizeProcess,
					$(this), event.target,
					$('#eventCreationUserId').val());*/
				if ($('.selectedtimeslot').length > 0) {
					if (Number($('#eventCreationUserId').val()) !== Number($(event.target).data("userid")) && isEventOnResizeProcess) {
						manageTimeSlot(false);
					} else {
						isEventOnCreationProcess = true;
						manageTimeSlot(isEventOnCreationProcess);
						isEventOnResizeProcess = false;
					}
				}
			});

			$("#btnAddEmpoyee").click(function () {
				$("#employeeSelectList").show();
				$("#sBtnAddEmployee").hide();
			});

			function createEvent() {
				$('div.dragStyleEventLoading').show();
				$('#searchFormList').hide();
				$('#eventCreation').hide();
				$.ajax({
					type: "POST",
					url: "<?php echo dol_buildpath('/dolimeet/ajax/event_creation.php', 2);?>",
					data: {
						createFromElementId: '<?php echo $create_from_element_id ?>',
						createFromElementType: '<?php echo $create_from_element_type; ?>',
						typeEvent: $('#actioncode').val(),
						userOwnerId: $('#eventCreationUserId').val(),
						userAffected: JSON.stringify($('#employees').val()),
						dtStYear: $("#eventcreationdtstartyear").val(),
						dtStMonth: $("#eventcreationdtstartmonth").val(),
						dtStDay: $("#eventcreationdtstartday").val(),
						dtStHour: $("#eventcreationdtstarthour").val(),
						dtStMin: $("#eventcreationdtstartmin").val(),
						dtEndYear: $("#eventcreationdtendyear").val(),
						dtEndMonth: $("#eventcreationdtendmonth").val(),
						dtEndDay: $("#eventcreationdtendday").val(),
						dtEndHour: $("#eventcreationdtendhour").val(),
						dtEndMin: $("#eventcreationdtendmin").val(),
						action: 'createEvent',
						token: '<?php echo currentToken();?>',
						multiEvt: multiEvt
					},
					dataType: "json"
				}).done(function (response) {
					//console.log(response);
					if (response[0]) {
						if (multiEvt == 0) {
							$(location).attr("href", "<?php echo dol_buildpath('/comm/action/card.php', 2)?>?comefrom=peruser_eventcreation&action=edit&id=" + response[0] + "&token=<?php echo newToken()?>");
						} else {
							$.ajax({
								type: "POST",
								url: "<?php echo dol_buildpath('/dolimeet/ajax/event_creation.php', 2);?>",
								data: {
									action: 'getMultiEvtData',
									token: '<?php echo currentToken();?>',
								},
								dataType: "json"
							}).done(function (responseDetail) {
								$('div.dragStyleEventLoading').hide();
								$('#searchFormList').show();
								$('#lstMultiEvtDet').empty();
								//Allow create event for another user
								$('#eventCreationUserId').val('');
								$('#employees').val('');
								$('#employees').change();
								$('#employeeSelectList').hide();
								$('#eventCreationUserName').text('');
								//$("#employeeSelectList").hide();
								displayMultiEventDialog(responseDetail,true);
							}).fail(function (responseDetail) {
								console.log("Error in ajax call");
								console.log(responseDetail);
								setTimeout(function () {
									$.jnotify(responseDetail.responseJSON,
										"error",
										true,
										{
											remove: function () {
											}
										});
								}, 500);
							});
						}
					}
				}).fail(function (response) {
					console.log("Error in ajax call");
					console.log(response);
					setTimeout(function () {
						$.jnotify(response.responseJSON,
							"error",
							true,
							{
								remove: function () {
								}
							});
					}, 500);
				});
			}

			function displayMultiEventDialog(responseDetail, designSlot) {
				if (Object.keys(responseDetail).length > 0) {
					$("#sBtnAddEmployee").hide();
					//$('#eventCreationUserName').text(UserData[$('#eventCreationUserId').val()]);
					let idUserAssignList = [];
					$.each(responseDetail, function (idEvent, dataEvent) {
						let nameUserAssignList = [];
						idUserAssignList = [];
						$.each(dataEvent.usersAssign, function (idUserAssign, nameUserAssign) {
							nameUserAssignList.push(nameUserAssign);
							idUserAssignList.push(idUserAssign);
						});
						let eventTxt = dataEvent.userOwner +': '+ dataEvent.dated + '-' + dataEvent.datef;
						if (idUserAssignList.length > 0) {
							eventTxt += '-' + nameUserAssignList.join(',');
						}
						$('#lstMultiEvtDet').append($('<li>').text(eventTxt));
					});
					$('#eventCreationDateStart').hide();
					$('#eventCreationDateEnd').hide();
					if (designSlot==true) {
						designTimeSlotMulti(idUserAssignList);
					}
					$('td[data-useridrow="' + $('#eventCreationUserId').val() + '"]').removeClass('cal_impair').css('background-color', '#25B2C1');
					createDialogCreate();
					$('#lstMultiEvt').show();
					$("#eventCreation").dialog("option", "height", 250 + Object.keys(responseDetail).length * 20)
					let newBtn = Object.assign($("#eventCreation").dialog("option", "buttons"),
						[
							{
								text: '<?php echo dol_escape_js($langs->transnoentities("DlpdManageMultiEvt"))?>',
								click: function () {
									$(location).attr("href", "<?php echo dol_buildpath('/dolipad/comm/action/confirm_eventmulticreation.php', 2)?>?create_from_element_type=<?php echo $create_from_element_type;?>&create_from_element_id=<?php echo $create_from_element_id;?>&token=<?php echo newToken()?>");
								}
							}
						]);
					$('#eventCreation').dialog("option", "buttons", newBtn);
					$('button.ui-button.ui-corner-all.ui-widget:contains(<?php echo dol_escape_js($langs->transnoentities("Cancel"))?>)').hide();
					$('button.ui-button.ui-corner-all.ui-widget:contains(<?php echo dol_escape_js($langs->transnoentities("DlpdValidateAction"))?>)').hide();
				}
				isEventOnCreationProcess = false;
				isEventOnResizeProcess = false;
				slotSelected = Array();
			}


			//When select other emplyee we do a ajax request to know if other user are
			// already booked at the same times
			function checkAvailability() {
				return new Promise((resolve) => {
					$.ajax({
						type: "POST",
						url: "<?php echo dol_buildpath('/dolimeet/ajax/event_creation.php', 2);?>",
						data: {
							createFromElementId: '<?php echo $create_from_element_id ?>',
							createFromElementType: '<?php echo $create_from_element_type; ?>',
							userOwnerId: $('#eventCreationUserId').val(),
							userAffected: JSON.stringify($('#employees').val()),
							dtStYear: $("#eventcreationdtstartyear").val(),
							dtStMonth: $("#eventcreationdtstartmonth").val(),
							dtStDay: $("#eventcreationdtstartday").val(),
							dtStHour: $("#eventcreationdtstarthour").val(),
							dtStMin: $("#eventcreationdtstartmin").val(),
							dtEndYear: $("#eventcreationdtendyear").val(),
							dtEndMonth: $("#eventcreationdtendmonth").val(),
							dtEndDay: $("#eventcreationdtendday").val(),
							dtEndHour: $("#eventcreationdtendhour").val(),
							dtEndMin: $("#eventcreationdtendmin").val(),
							action: 'checkAvailability',
							token: '<?php echo currentToken();?>'
						},
						dataType: "json"
					}).done(function (response) {
						//console.log('checkAvailability()');
						//console.log(response);
						resolve(response);
					}).fail(function (response) {
						console.log("Error in ajax call");
						console.log(response);
						setTimeout(function () {
							$.jnotify(response.responseJSON,
								"error",
								true,
								{
									remove: function () {
									}
								});
						}, 500);
					});
				});
			}

			//When click on a "busy" timeslot it will open list or card to this event(s)
			$(".onclickopenref").click(function () {
				let ref = $(this).data('timeslot');
				let res = ref.split("_");
				let userid = res[1];
				let year = res[2];
				let month = res[3];
				let day = res[4];
				let ids = res[7];
				if (ids.indexOf(",") > -1) {
					/* There is several events */
					url = "<?php echo DOL_URL_ROOT ?>/comm/action/list.php?mode=show_list&filtert=" + userid + "&dateselectyear=" + year + "&dateselectmonth=" + month + "&dateselectday=" + day;
					window.location.href = url;
				} else {
					/* One event */
					url = "<?php echo DOL_URL_ROOT ?>/comm/action/card.php?action=view&id=" + ids
					window.location.href = url;
				}
			});

			if (multiEvt) {
				$.ajax({
					type: "POST",
					url: "<?php echo dol_buildpath('/dolimeet/ajax/event_creation.php', 2);?>",
					data: {
						action: 'getMultiEvtData',
						token: '<?php echo currentToken();?>',
					},
					dataType: "json"
				}).done(function (responseDetail) {
					displayMultiEventDialog(responseDetail,false);
				}).fail(function (responseDetail) {
					console.log("Error in ajax call");
					console.log(responseDetail);
					setTimeout(function () {
						$.jnotify(responseDetail.responseJSON,
							"error",
							true,
							{
								remove: function () {
								}
							});
					}, 500);
				});
			}
		});
	</script>


	<div id="confirmeventcreation" title="<?php echo $langs->trans("AddAction"); ?>" style="display: none;">
		<div id="confirmeventcreationtext"></div>
		<div style="font-weight: bold"><?php echo $langs->trans("ConfirmAddAction"); ?></div>
	</div>
<?php
// End of page
llxFooter();
$db->close();


/**
 * Show event line of a particular day for a user
 *
 * @param User $username Login
 * @param int $day Day
 * @param int $month Month
 * @param int $year Year
 * @param int $monthshown Current month shown in calendar view
 * @param string $style Style to use for this day
 * @param array $eventarray Array of events
 * @param int $maxprint Nb of actions to show each day on month view (0 means no limit)
 * @param int $maxnbofchar Nb of characters to show for event line
 * @param string $newparam Parameters on current URL
 * @param int $showinfo Add extended information (used by day view)
 * @param int $minheight Minimum height for each event. 60px by default.
 * @param boolean $showheader Show header
 * @param array $colorsbytype Array with colors by type
 * @param bool $var true or false for alternat style on tr/td
 * @return    void
 */
function show_day_events2($username, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint = 0, $maxnbofchar = 16, $newparam = '', $showinfo = 0, $minheight = 60, $showheader = false, $colorsbytype = array(), $var = false)
{
	global $db;
	global $user, $conf, $langs, $hookmanager, $action;
	global $filter, $filtert, $status, $actioncode; // Filters used into search form
	global $theme_datacolor; // Array with a list of different we can use (come from theme)
	global $cachethirdparties, $cachecontacts, $cacheusers, $cacheprojects, $colorindexused;
	global $begin_h, $end_h;

	$cases1 = array(); // Color first half hour
	$cases2 = array(); // Color second half hour
	$cases3 = array(); // Color third half hour
	$cases4 = array(); // Color 4th half hour

	$i = 0;
	$numother = 0;
	$numbirthday = 0;
	$numical = 0;
	$numicals = array();
	//$ymd = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $day);

	$colorindexused[$user->id] = 0; // Color index for current user (user->id) is always 0
	$nextindextouse = count($colorindexused); // At first run this is 0, so first user has 0, next 1, ...
	//if ($username->id && $day==1) var_dump($eventarray);

	// We are in a particular day for $username, now we scan all events
	foreach ($eventarray as $daykey => $notused) {
		$annee = dol_print_date($daykey, '%Y');
		$mois = dol_print_date($daykey, '%m');
		$jour = dol_print_date($daykey, '%d');

		if ($day == $jour && $month == $mois && $year == $annee) {    // Is it the day we are looking for when calling function ?
			// Scan all event for this date
			foreach ($eventarray[$daykey] as $index => $event) {
				//print $daykey.' '.dol_print_date($daykey, 'dayhour', 'gmt').' '.$year.'-'.$month.'-'.$day.' -> '.$event->id.' '.$index.' '.$annee.'-'.$mois.'-'.$jour."<br>\n";
				//var_dump($event);

				$keysofuserassigned = array_keys($event->userassigned);
				$ponct = ($event->date_start_in_calendar == $event->date_end_in_calendar);

				if (!in_array($username->id, $keysofuserassigned)) {
					continue; // We discard record if event is from another user than user we want to show
				}
				//if ($username->id != $event->userownerid) continue;	// We discard record if event is from another user than user we want to show

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formatEvent', $parameters, $event, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				// Define $color (Hex string like '0088FF') and $cssclass of event
				$color = -1;
				$cssclass = '';
				$colorindex = -1;
				if (in_array($user->id, $keysofuserassigned)) {
					$cssclass = 'family_mytasks';

					if (empty($cacheusers[$event->userownerid])) {
						$newuser = new User($db);
						$newuser->fetch($event->userownerid);
						$cacheusers[$event->userownerid] = $newuser;
					}
					//var_dump($cacheusers[$event->userownerid]->color);

					// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
					if (!empty($cacheusers[$event->userownerid]->color)) {
						$color = $cacheusers[$event->userownerid]->color;
					}

					if (getDolGlobalString('AGENDA_USE_COLOR_PER_EVENT_TYPE')) {
						$color = $event->type_color;
					}
				} elseif ($event->type_code == 'ICALEVENT') {
					$numical++;
					if (!empty($event->icalname)) {
						if (!isset($numicals[dol_string_nospecial($event->icalname)])) {
							$numicals[dol_string_nospecial($event->icalname)] = 0;
						}
						$numicals[dol_string_nospecial($event->icalname)]++;
					}

					$color = $event->icalcolor;
					$cssclass = (!empty($event->icalname) ? 'family_ext' . md5($event->icalname) : 'family_other unsortable');
				} elseif ($event->type_code == 'BIRTHDAY') {
					$numbirthday++;
					$colorindex = 2;
					$cssclass = 'family_birthday unsortable';
					$color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
				} else {
					$numother++;
					$color = ($event->icalcolor ? $event->icalcolor : -1);
					$cssclass = (!empty($event->icalname) ? 'family_ext' . md5($event->icalname) : 'family_other');

					if (empty($cacheusers[$event->userownerid])) {
						$newuser = new User($db);
						$newuser->fetch($event->userownerid);
						$cacheusers[$event->userownerid] = $newuser;
					}
					//var_dump($cacheusers[$event->userownerid]->color);

					// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
					if (!empty($cacheusers[$event->userownerid]->color)) {
						$color = $cacheusers[$event->userownerid]->color;
					}

					if (getDolGlobalString('AGENDA_USE_COLOR_PER_EVENT_TYPE')) {
						$color = $event->type_color;
					}
				}

				if ($color < 0) {    // Color was not set on user card. Set color according to color index.
					// Define color index if not yet defined
					$idusertouse = ($event->userownerid ? $event->userownerid : 0);
					if (isset($colorindexused[$idusertouse])) {
						$colorindex = $colorindexused[$idusertouse]; // Color already assigned to this user
					} else {
						$colorindex = $nextindextouse;
						$colorindexused[$idusertouse] = $colorindex;
						if (!empty($theme_datacolor[$nextindextouse + 1])) {
							$nextindextouse++; // Prepare to use next color
						}
					}
					// Define color
					$color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
				}

				// Define all rects with event (cases1 is first quarter hour, cases2 is second quarter hour, cases3 is second thirds hour, cases4 is 4th quarter hour)
				for ($h = $begin_h; $h < $end_h; $h++) {
					//if ($username->id == 1 && $day==1) print 'h='.$h;
					$newcolor = ''; //init
					if (empty($event->fulldayevent)) {
						$a = dol_mktime((int) $h, 0, 0, $month, $day, $year, 'tzuserrel', 0);
						$b = dol_mktime((int) $h, 15, 0, $month, $day, $year, 'tzuserrel', 0);
						$b1 = dol_mktime((int) $h, 30, 0, $month, $day, $year, 'tzuserrel', 0);
						$b2 = dol_mktime((int) $h, 45, 0, $month, $day, $year, 'tzuserrel', 0);
						$c = dol_mktime((int) $h + 1, 0, 0, $month, $day, $year, 'tzuserrel', 0);

						$dateendtouse = $event->date_end_in_calendar;
						if ($dateendtouse == $event->date_start_in_calendar) {
							$dateendtouse++;
						}

						//print dol_print_date($event->date_start_in_calendar,'dayhour').'-'.dol_print_date($a,'dayhour').'-'.dol_print_date($b,'dayhour').'<br>';

						if ($event->date_start_in_calendar < $b && $dateendtouse > $a) {
							$busy = $event->transparency;
							$cases1[$h][$event->id]['busy'] = $busy;
							$cases1[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzuserrel');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar) {
								$tmpa = dol_getdate($event->date_start_in_calendar, true);
								$tmpb = dol_getdate($event->date_end_in_calendar, true);
								if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
									$cases1[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'hour', 'tzuserrel');
								} else {
									$cases1[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzuserrel');
								}
							}
							if ($event->label) {
								$cases1[$h][$event->id]['string'] .= ' - ' . $event->label;
							}
							$cases1[$h][$event->id]['typecode'] = $event->type_code;
							$cases1[$h][$event->id]['color'] = $color;
							if ($event->fk_project > 0) {
								if (empty($cacheprojects[$event->fk_project])) {
									$tmpproj = new Project($db);
									$tmpproj->fetch($event->fk_project);
									$cacheprojects[$event->fk_project] = $tmpproj;
								}
								$cases1[$h][$event->id]['string'] .= ', ' . $langs->trans("Project") . ': ' . $cacheprojects[$event->fk_project]->ref . ' - ' . $cacheprojects[$event->fk_project]->title;
							}
							if ($event->socid > 0) {
								if (empty($cachethirdparties[$event->socid])) {
									$tmpthirdparty = new Societe($db);
									$tmpthirdparty->fetch($event->socid);
									$cachethirdparties[$event->socid] = $tmpthirdparty;
								}
								$cases1[$h][$event->id]['string'] .= ', ' . $cachethirdparties[$event->socid]->name;
							}
							if ($event->contact_id > 0) {
								if (empty($cachecontacts[$event->contact_id])) {
									$tmpcontact = new Contact($db);
									$tmpcontact->fetch($event->contact_id);
									$cachecontacts[$event->contact_id] = $tmpcontact;
								}
								$cases1[$h][$event->id]['string'] .= ', ' . $cachecontacts[$event->contact_id]->getFullName($langs);
							}
						}
						if ($event->date_start_in_calendar < $b1 && $dateendtouse > $b) {
							$busy = $event->transparency;
							$cases2[$h][$event->id]['busy'] = $busy;
							$cases2[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzuserrel');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar) {
								$tmpa = dol_getdate($event->date_start_in_calendar, true);
								$tmpb = dol_getdate($event->date_end_in_calendar, true);
								if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
									$cases2[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'hour', 'tzuserrel');
								} else {
									$cases2[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzuserrel');
								}
							}
							if ($event->label) {
								$cases2[$h][$event->id]['string'] .= ' - ' . $event->label;
							}
							$cases2[$h][$event->id]['typecode'] = $event->type_code;
							$cases2[$h][$event->id]['color'] = $color;
							if ($event->fk_project > 0) {
								if (empty($cacheprojects[$event->fk_project])) {
									$tmpproj = new Project($db);
									$tmpproj->fetch($event->fk_project);
									$cacheprojects[$event->fk_project] = $tmpproj;
								}
								$cases2[$h][$event->id]['string'] .= ', ' . $langs->trans("Project") . ': ' . $cacheprojects[$event->fk_project]->ref . ' - ' . $cacheprojects[$event->fk_project]->title;
							}
							if ($event->socid > 0) {
								if (empty($cachethirdparties[$event->socid])) {
									$tmpthirdparty = new Societe($db);
									$tmpthirdparty->fetch($event->socid);
									$cachethirdparties[$event->socid] = $tmpthirdparty;
								}
								$cases2[$h][$event->id]['string'] .= ', ' . $cachethirdparties[$event->socid]->name;
							}
							if ($event->contact_id > 0) {
								if (empty($cachecontacts[$event->contact_id])) {
									$tmpcontact = new Contact($db);
									$tmpcontact->fetch($event->contact_id);
									$cachecontacts[$event->contact_id] = $tmpcontact;
								}
								$cases2[$h][$event->id]['string'] .= ', ' . $cachecontacts[$event->contact_id]->getFullName($langs);
							}
						}
						if ($event->date_start_in_calendar < $b2 && $dateendtouse > $b1) {
							$busy = $event->transparency;
							$cases3[$h][$event->id]['busy'] = $busy;
							$cases3[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzuserrel');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar) {
								$tmpa = dol_getdate($event->date_start_in_calendar, true);
								$tmpb = dol_getdate($event->date_end_in_calendar, true);
								if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
									$cases3[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'hour', 'tzuserrel');
								} else {
									$cases3[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzuserrel');
								}
							}
							if ($event->label) {
								$cases3[$h][$event->id]['string'] .= ' - ' . $event->label;
							}
							$cases3[$h][$event->id]['typecode'] = $event->type_code;
							$cases3[$h][$event->id]['color'] = $color;
							if ($event->fk_project > 0) {
								if (empty($cacheprojects[$event->fk_project])) {
									$tmpproj = new Project($db);
									$tmpproj->fetch($event->fk_project);
									$cacheprojects[$event->fk_project] = $tmpproj;
								}
								$cases3[$h][$event->id]['string'] .= ', ' . $langs->trans("Project") . ': ' . $cacheprojects[$event->fk_project]->ref . ' - ' . $cacheprojects[$event->fk_project]->title;
							}
							if ($event->socid > 0) {
								if (empty($cachethirdparties[$event->socid])) {
									$tmpthirdparty = new Societe($db);
									$tmpthirdparty->fetch($event->socid);
									$cachethirdparties[$event->socid] = $tmpthirdparty;
								}
								$cases3[$h][$event->id]['string'] .= ', ' . $cachethirdparties[$event->socid]->name;
							}
							if ($event->contact_id > 0) {
								if (empty($cachecontacts[$event->contact_id])) {
									$tmpcontact = new Contact($db);
									$tmpcontact->fetch($event->contact_id);
									$cachecontacts[$event->contact_id] = $tmpcontact;
								}
								$cases2[$h][$event->id]['string'] .= ', ' . $cachecontacts[$event->contact_id]->getFullName($langs);
							}
						}
						if ($event->date_start_in_calendar < $c && $dateendtouse > $b2) {
							$busy = $event->transparency;
							$cases4[$h][$event->id]['busy'] = $busy;
							$cases4[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzuserrel');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar) {
								$tmpa = dol_getdate($event->date_start_in_calendar, true);
								$tmpb = dol_getdate($event->date_end_in_calendar, true);
								if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
									$cases4[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'hour', 'tzuserrel');
								} else {
									$cases4[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzuserrel');
								}
							}
							if ($event->label) {
								$cases4[$h][$event->id]['string'] .= ' - ' . $event->label;
							}
							$cases4[$h][$event->id]['typecode'] = $event->type_code;
							$cases4[$h][$event->id]['color'] = $color;
							if ($event->fk_project > 0) {
								if (empty($cacheprojects[$event->fk_project])) {
									$tmpproj = new Project($db);
									$tmpproj->fetch($event->fk_project);
									$cacheprojects[$event->fk_project] = $tmpproj;
								}
								$cases4[$h][$event->id]['string'] .= ', ' . $langs->trans("Project") . ': ' . $cacheprojects[$event->fk_project]->ref . ' - ' . $cacheprojects[$event->fk_project]->title;
							}
							if ($event->socid > 0) {
								if (empty($cachethirdparties[$event->socid])) {
									$tmpthirdparty = new Societe($db);
									$tmpthirdparty->fetch($event->socid);
									$cachethirdparties[$event->socid] = $tmpthirdparty;
								}
								$cases4[$h][$event->id]['string'] .= ', ' . $cachethirdparties[$event->socid]->name;
							}
							if ($event->contact_id > 0) {
								if (empty($cachecontacts[$event->contact_id])) {
									$tmpcontact = new Contact($db);
									$tmpcontact->fetch($event->contact_id);
									$cachecontacts[$event->contact_id] = $tmpcontact;
								}
								$cases4[$h][$event->id]['string'] .= ', ' . $cachecontacts[$event->contact_id]->getFullName($langs);
							}
						}
					} else {
						$busy = $event->transparency;
						$cases1[$h][$event->id]['busy'] = $busy;
						$cases2[$h][$event->id]['busy'] = $busy;
						$cases3[$h][$event->id]['busy'] = $busy;
						$cases4[$h][$event->id]['busy'] = $busy;
						$cases1[$h][$event->id]['string'] = $event->label;
						$cases2[$h][$event->id]['string'] = $event->label;
						$cases3[$h][$event->id]['string'] = $event->label;
						$cases4[$h][$event->id]['string'] = $event->label;
						$cases1[$h][$event->id]['typecode'] = $event->type_code;
						$cases2[$h][$event->id]['typecode'] = $event->type_code;
						$cases3[$h][$event->id]['typecode'] = $event->type_code;
						$cases4[$h][$event->id]['typecode'] = $event->type_code;
						$cases1[$h][$event->id]['color'] = $color;
						$cases2[$h][$event->id]['color'] = $color;
						$cases3[$h][$event->id]['color'] = $color;
						$cases4[$h][$event->id]['color'] = $color;
					}
				}
				$i++;
			}

			break; // We found the date we were looking for. No need to search anymore.
		}
	}

	// Now output $casesX
	for ($h = $begin_h; $h < $end_h; $h++) {
		$color1 = '';
		$color2 = '';
		$color3 = '';
		$color4 = '';
		$style1 = '';
		$style2 = '';
		$style3 = '';
		$style4 = '';
		$string1 = '&nbsp;';
		$string2 = '&nbsp;';
		$string3 = '&nbsp;';
		$string4 = '&nbsp;';
		$title1 = '';
		$title2 = '';
		$title3 = '';
		$title4 = '';
		if (isset($cases1[$h]) && $cases1[$h] != '') {
			//$title1.=count($cases1[$h]).' '.(count($cases1[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases1[$h]) > 1) {
				$title1 .= count($cases1[$h]) . ' ' . (count($cases1[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			}
			$string1 = '&nbsp;';
			if (!getDolGlobalString('AGENDA_NO_TRANSPARENT_ON_NOT_BUSY')) {
				$style1 = 'peruser_notbusy';
			} else {
				$style1 = 'peruser_busy';
			}
			foreach ($cases1[$h] as $id => $ev) {
				if ($ev['busy']) {
					$style1 = 'peruser_busy';
				}
			}
		}
		if (isset($cases2[$h]) && $cases2[$h] != '') {
			//$title2.=count($cases2[$h]).' '.(count($cases2[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases2[$h]) > 1) {
				$title2 .= count($cases2[$h]) . ' ' . (count($cases2[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			}
			$string2 = '&nbsp;';
			if (!getDolGlobalString('AGENDA_NO_TRANSPARENT_ON_NOT_BUSY')) {
				$style2 = 'peruser_notbusy';
			} else {
				$style2 = 'peruser_busy';
			}
			foreach ($cases2[$h] as $id => $ev) {
				if ($ev['busy']) {
					$style2 = 'peruser_busy';
				}
			}
		}
		if (isset($cases3[$h]) && $cases3[$h] != '') {
			//$title3.=count($cases3[$h]).' '.(count($cases3[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases3[$h]) > 1) {
				$title3 .= count($cases3[$h]) . ' ' . (count($cases3[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			}
			$string3 = '&nbsp;';
			if (!getDolGlobalString('AGENDA_NO_TRANSPARENT_ON_NOT_BUSY')) {
				$style3 = 'peruser_notbusy';
			} else {
				$style3 = 'peruser_busy';
			}
			foreach ($cases3[$h] as $id => $ev) {
				if ($ev['busy']) {
					$style3 = 'peruser_busy';
				}
			}
		}
		if (isset($cases4[$h]) && $cases4[$h] != '') {
			//$title4.=count($cases3[$h]).' '.(count($cases3[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases4[$h]) > 1) {
				$title4 .= count($cases4[$h]) . ' ' . (count($cases4[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			}
			$string4 = '&nbsp;';
			if (!getDolGlobalString('AGENDA_NO_TRANSPARENT_ON_NOT_BUSY')) {
				$style4 = 'peruser_notbusy';
			} else {
				$style4 = 'peruser_busy';
			}
			foreach ($cases4[$h] as $id => $ev) {
				if ($ev['busy']) {
					$style4 = 'peruser_busy';
				}
			}
		}

		$ids1 = '';
		$ids2 = '';
		$ids3 = '';
		$ids4 = '';
		if (!empty($cases1[$h]) && is_array($cases1[$h]) && count($cases1[$h]) && array_keys($cases1[$h])) {
			$ids1 = join(',', array_keys($cases1[$h]));
		}
		if (!empty($cases2[$h]) && is_array($cases2[$h]) && count($cases2[$h]) && array_keys($cases2[$h])) {
			$ids2 = join(',', array_keys($cases2[$h]));
		}
		if (!empty($cases3[$h]) && is_array($cases3[$h]) && count($cases3[$h]) && array_keys($cases3[$h])) {
			$ids3 = join(',', array_keys($cases3[$h]));
		}
		if (!empty($cases4[$h]) && is_array($cases4[$h]) && count($cases4[$h]) && array_keys($cases4[$h])) {
			$ids4 = join(',', array_keys($cases4[$h]));
		}

		if ($h == $begin_h) {
			echo '<td class="' . $style . '_peruserleft cal_peruser' . ($var ? ' cal_impair ' . $style . '_impair' : '') . '">';
		} else {
			echo '<td class="' . $style . ' cal_peruser' . ($var ? ' cal_impair ' . $style . '_impair' : '') . '">';
		}


		if (!empty($cases1[$h]) && is_array($cases1[$h]) && count($cases1[$h]) == 1) {
			$output = array_slice($cases1[$h], 0, 1);
			$title1 = $langs->trans("Ref") . ' ' . $ids1 . ($title1 ? ' - ' . $title1 : '');
			if ($output[0]['string']) {
				$title1 .= ($title1 ? ' - ' : '') . $output[0]['string'];
			}
			if ($output[0]['color']) {
				$color1 = $output[0]['color'];
			}
		} elseif (!empty($cases1[$h]) && is_array($cases1[$h]) && count($cases1[$h]) > 1) {
			$title1 = $langs->trans("Ref") . ' ' . $ids1 . ($title1 ? ' - ' . $title1 : '');
			$color1 = '222222';
		}


		if (!empty($cases2[$h]) && is_array($cases2[$h]) && count($cases2[$h]) == 1) {
			$output = array_slice($cases2[$h], 0, 1);
			$title2 = $langs->trans("Ref") . ' ' . $ids2 . ($title2 ? ' - ' . $title2 : '');
			if ($output[0]['string']) {
				$title2 .= ($title2 ? ' - ' : '') . $output[0]['string'];
			}
			if ($output[0]['color']) {
				$color2 = $output[0]['color'];
			}
		} elseif (!empty($cases2[$h]) && is_array($cases2[$h]) && count($cases2[$h]) > 1) {
			$title2 = $langs->trans("Ref") . ' ' . $ids2 . ($title2 ? ' - ' . $title2 : '');
			$color2 = '222222';
		}


		if (!empty($cases3[$h]) && is_array($cases3[$h]) && count($cases3[$h]) == 1) {
			$output = array_slice($cases3[$h], 0, 1);
			$title3 = $langs->trans("Ref") . ' ' . $ids3 . ($title3 ? ' - ' . $title3 : '');
			if ($output[0]['string']) {
				$title3 .= ($title3 ? ' - ' : '') . $output[0]['string'];
			}
			if ($output[0]['color']) {
				$color3 = $output[0]['color'];
			}
		} elseif (!empty($cases3[$h]) && is_array($cases3[$h]) && count($cases3[$h]) > 1) {
			$title3 = $langs->trans("Ref") . ' ' . $ids3 . ($title3 ? ' - ' . $title3 : '');
			$color3 = '222222';
		}


		if (!empty($cases4[$h]) && is_array($cases4[$h]) && count($cases4[$h]) == 1) {
			$output = array_slice($cases4[$h], 0, 1);
			$title4 = $langs->trans("Ref") . ' ' . $ids3 . ($title4 ? ' - ' . $title4 : '');
			if ($output[0]['string']) {
				$title4 .= ($title4 ? ' - ' : '') . $output[0]['string'];
			}
			if ($output[0]['color']) {
				$color4 = $output[0]['color'];
			}
		} elseif (!empty($cases4[$h]) && is_array($cases4[$h]) && count($cases4[$h]) > 1) {
			$title4 = $langs->trans("Ref") . ' ' . $ids4 . ($title4 ? ' - ' . $title4 : '');
			$color4 = '222222';
		}

		$date_info = sprintf("%04d", $year) . '_' . sprintf("%02d", $month) . '_' . sprintf("%02d", $day) . '_' . sprintf("%02d", $h);

		print '<table class="nobordernopadding">';
		print '<tr>';
		print '<td data-userid="' . $username->id . '"';
		if ($style1 == 'peruser_notbusy') {
			print 'style="border: 1px solid #' . ($color1 ? $color1 : "888") . ' !important" ';
		} elseif ($color1) {
			print ($color1 ? 'style="background: #' . $color1 . ';"' : '');
		}
		$tmstmp = dol_mktime($h, 0, 0, $month, $day, $year, 'tzuser');
		print 'class=" u' . $username->id . '-t' . $tmstmp . ' ';
		print ($style1 ? $style1 . ' onclickopenref ' : ' onclickdesignevent ');
		print ' center' . ($title2 ? ' classfortooltip' : '') . ($title1 ? ' cursorpointer' : '') . '" data-timeslotstmp="' . $tmstmp . '" data-timeslot="ref_' . $username->id . '_' . $date_info . '_00_' . ($ids1 ? $ids1 : 'none') . '"' . ($title1 ? ' title="' . $title1 . '"' : '') . '>';
		print $string1;
		print '</td>';

		print '<td data-userid="' . $username->id . '"';
		if ($style2 == 'peruser_notbusy') {
			print 'style="border: 1px solid #' . ($color2 ? $color2 : "888") . ' !important" ';
		} elseif ($color2) {
			print ($color2 ? 'style="background: #' . $color2 . ';"' : '');
		}

		$tmstmp = dol_mktime($h, 15, 0, $month, $day, $year, 'tzuser');
		print 'class=" u' . $username->id . '-t' . $tmstmp . ' ';
		print ($style2 ? $style2 . ' onclickopenref ' : ' onclickdesignevent ');
		print ' center' . ($title2 ? ' classfortooltip' : '') . ($title1 ? ' cursorpointer' : '') . '" data-timeslotstmp="' . $tmstmp . '" data-timeslot="ref_' . $username->id . '_' . $date_info . '_15_' . ($ids2 ? $ids2 : 'none') . '"' . ($title2 ? ' title="' . $title2 . '"' : '') . '>';
		print $string2;
		print '</td>';

		print '<td data-userid="' . $username->id . '"';
		if ($style3 == 'peruser_notbusy') {
			print 'style="border: 1px solid #' . ($color3 ? $color3 : "888") . ' !important" ';
		} elseif ($color3) {
			print ($color3 ? 'style="background: #' . $color3 . ';"' : '');
		}
		$tmstmp = dol_mktime($h, 30, 0, $month, $day, $year, 'tzuser');
		print 'class=" u' . $username->id . '-t' . $tmstmp . ' ';
		print ($style3 ? $style3 . ' onclickopenref ' : ' onclickdesignevent ');
		print ' center' . ($title2 ? ' classfortooltip' : '') . ($title3 ? ' cursorpointer' : '') . '" data-timeslotstmp="' . $tmstmp . '" data-timeslot="ref_' . $username->id . '_' . $date_info . '_30_' . ($ids3 ? $ids3 : 'none') . '"' . ($title3 ? ' title="' . $title3 . '"' : '') . '>';
		print $string3;
		print '</td>';

		print '<td data-userid="' . $username->id . '"';
		if ($style4 == 'peruser_notbusy') {
			print 'style="border: 1px solid #' . ($color4 ? $color4 : "888") . ' !important" ';
		} elseif ($color4) {
			print ($color4 ? 'style="background: #' . $color4 . ';"' : '');
		}
		$tmstmp = dol_mktime($h, 45, 0, $month, $day, $year, 'tzuser');
		print 'class=" u' . $username->id . '-t' . $tmstmp . ' ';
		print ($style4 ? $style4 . ' onclickopenref ' : ' onclickdesignevent ');
		print ' center' . ($title3 ? ' classfortooltip' : '') . ($title4 ? ' cursorpointer' : '') . '" data-timeslotstmp="' . $tmstmp . '" data-timeslot="ref_' . $username->id . '_' . $date_info . '_45_' . ($ids4 ? $ids4 : 'none') . '"' . ($title4 ? ' title="' . $title4 . '"' : '') . '>';
		print $string4;
		print '</td>';

		print '</tr>';
		print '</table>';
		print '</td>';
	}
}
