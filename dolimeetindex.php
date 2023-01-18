<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
 *	\file       dolimeetindex.php
 *	\ingroup    dolimeet
 *	\brief      Home page of dolimeet top menu
 */

// Load Dolibarr environment
if (file_exists('../../main.inc.php')) {
    require_once __DIR__ . '/../../main.inc.php';
} elseif (file_exists('../../../main.inc.php')) {
    require_once '../../../main.inc.php';
} else {
    die('Include of main fails');
}

// Libraries
require_once __DIR__ . '/core/modules/modDoliMeet.class.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(['dolimeet@dolimeet']);

// Initialize technical objects
$modDoliMeet = new modDoliMeet($db);

// Security check
$permissiontoread = $user->rights->dolimeet->read;
if (empty($conf->dolimeet->enabled) || !$permissiontoread) {
    accessforbidden();
}

/*
 * View
 */

$help_url = 'FR:Module_DoliMeet';
$title    = $langs->trans('DoliMeetArea');
$morejs   = ['/dolimeet/js/dolimeet.js'];
$morecss  = ['/dolimeet/css/dolimeet.css'];

llxHeader('', $title . ' ' . $modDoliMeet->version, $help_url, '', 0, 0, $morejs, $morecss);

print load_fiche_titre($title . ' ' . $modDoliMeet->version, '', 'dolimeet_color.png@dolimeet');

// End of page
llxFooter();
$db->close();
