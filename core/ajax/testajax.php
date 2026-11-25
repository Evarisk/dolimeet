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
 * \file    core/ajax/testajax.php
 * \ingroup dolimeet
 * \brief   Switch "Mandatory signature" of a contact of an object (llx_element_contact.mandatory_signature), set from the
 *          contacts tab of the object
 */

ob_start();

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
    define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode, whatever the setup of the CSRF protection
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
if (file_exists('../../../../main.inc.php')) {
    require '../../../../main.inc.php';
} elseif (file_exists('../../../../../main.inc.php')) {
    require '../../../../../main.inc.php';
} else {
    die('Include of dolimeet main fails');
}
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

global $db, $user;

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage');
$value = GETPOST('value', 'int');

$rowid = GETPOSTINT('rowid');

// The line links a contact to an object, whose type is the one of the contact type: the object is found back to check
// the rights on it, as the core does for its own switches (core/ajax/objectonoff.php)
$object = 0;
$resql  = $db->query('SELECT ec.element_id, tc.element FROM ' . $db->prefix() . 'element_contact as ec INNER JOIN ' . $db->prefix() . 'c_type_contact as tc ON tc.rowid = ec.fk_c_type_contact WHERE ec.rowid = ' . ((int) $rowid));
if ($resql && ($line = $db->fetch_object($resql))) {
    $object = fetchObjectByElement((int) $line->element_id, (string) $line->element);
}
if (!is_object($object)) {
    httponly_accessforbidden('Contact line not found.');
}

// An external user only reaches the objects of its own third party
if (!empty($user->socid) && !empty($object->socid) && $user->socid != $object->socid) {
    httponly_accessforbidden('Access on object not allowed for this external user.');
}

// Right to edit the object, checked by restrictedArea() because $action is 'set'
$usesublevelpermission = ($object->module != $object->element ? $object->element : '');
if ($usesublevelpermission && !$user->hasRight($object->module, $object->element)) {
    $usesublevelpermission = '';
}
restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission);

/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering new values
if (($action == 'set')) {	// Rights on the object of the line already checked above
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
