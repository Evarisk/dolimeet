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

// Load DoliMeet environment
if (file_exists('dolimeet.main.inc.php')) {
    require_once __DIR__ . '/dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Global variables definitions
global $conf, $db, $langs, $moduleName, $moduleNameLowerCase, $user;

// Libraries
require_once __DIR__ . '/core/modules/mod' . $moduleName . '.class.php';

// Load translation files required by the page
$langs->loadLangs([$moduleNameLowerCase . '@' . $moduleNameLowerCase]);

// Initialize technical objects
$classname = 'mod' . $moduleName;
$modModule = new $classname($db);

// Security check
$permissiontoread = $user->rights->$moduleNameLowerCase->read;
if (empty($conf->$moduleNameLowerCase->enabled) || !$permissiontoread) {
    accessforbidden();
}

/*
 * View
 */

$title    = $langs->trans($moduleName . 'Area');
$helpUrl = 'FR:Module_' . $moduleName;
$morejs   = ['/' . $moduleNameLowerCase . '/js/' . $moduleNameLowerCase . '.js'];
$morecss  = ['/' . $moduleNameLowerCase . '/css/' . $moduleNameLowerCase . '.css'];

llxHeader('', $title . ' ' . $modModule->version, $helpUrl, '', 0, 0, $morejs, $morecss);

print load_fiche_titre($title . ' ' . $modModule->version, '', $moduleNameLowerCase . '_color.png@' . $moduleNameLowerCase);

// End of page
llxFooter();
$db->close();