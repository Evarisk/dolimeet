<?php
/* Copyright (C) 2015-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file      htdocs/core/ajax/objectonoff.php
 *       \brief     File to set status for an object. Called when ajax_object_onoff() is used.
 *       			This Ajax service is often called when option MAIN_DIRECT_STATUS_UPDATE is set.
 *       			TODO Rename into updatestatus.php
 */

ob_start();

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
if (!defined('NOREQUIRESOC')) {
    define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

global $db, $user;

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage');
$value = GETPOST('value', 'int');

$rowid = GETPOSTINT('rowid');

/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering new values
if (($action == 'set')) {	// Test on permission already done in header according to object and field.
    $contact = new Contact($db);
    $result = $contact->setValueFrom('mandatory_signature', $value, 'element_contact', $rowid, 'int', '', $user, '', '');

    if ($result < 0) {
        print $contact->error;
        http_response_code(500);
        exit;
    }

    if ($backtopage) {
        header('Location: ' . $backtopage);
        exit;
    }
}
