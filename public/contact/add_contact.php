<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    public/contact/add_contact.php
 * \ingroup dolimeet
 * \brief   Public page to create contact
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Load DoliMeet environment
if (file_exists('../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../dolimeet.main.inc.php';
} elseif (file_exists('../../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs(['users']);

// Get parameters
$id     = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object  = new Contrat($db);
$contact = new Contact($db);
$user    = new User($db);

$object->fetch($id);
$object->fetchProject();

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'add_contact') {
        $error = 0;

        $firstNames = GETPOST('firstname', 'array');
        $lastNames  = GETPOST('lastname', 'array');
        $emails     = GETPOST('email', 'array');

        $object->fetch($id);

        $nbContactsAlreadyAdded = 0;
        $contactsAlreadyAdded   = $langs->trans('ContactsAlreadyAdded') . ' : <br>';
        $contacts               = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
        foreach ($contacts as $contactSingle) {
            if (in_array($contactSingle['email'], $emails)) {
                $key = array_search($contactSingle['email'], $emails);
                unset($firstNames[$key]);
                unset($lastNames[$key]);
                unset($emails[$key]);
                $nbContactsAlreadyAdded++;
                $contactsAlreadyAdded .= $contactSingle['firstname'] . ' ' . $contactSingle['lastname'] . ' (' . $contactSingle['email'] . ')' . '<br>';
            }
        }

        if ($nbContactsAlreadyAdded > 0) {
            setEventMessages($contactsAlreadyAdded, null, 'warnings');
        }

        $nbContacts    = 0;
        $contactsAdded = $langs->trans('ContactsAdded') . ' : <br>';
        for ($i = 0; $i < count($firstNames); $i++) {
            $contact->firstname = $firstNames[$i];
            $contact->lastname  = $lastNames[$i];
            $contact->email     = $emails[$i];
            $contact->socid     = $object->socid;

            $result = $contact->create($user);
            if ($result > 0) {
                $object->restrictiononfksoc                 = 0; // We disable the restriction on fk_soc to be able to add contact linked to third party of contract
                $object->context['createformpubliccontact'] = 'createformpubliccontact';
                $object->contact_id                         = $result;
                $result = $object->add_contact($result, 'TRAINEE');
                $nbContacts++;
                $contactsAdded .= $contact->firstname . ' ' . $contact->lastname . ' (' . $contact->email . ')' . '<br>';
                if ($result < 0) {
                    $error++;
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            } else {
                $error++;
                setEventMessages($contact->error, $contact->errors, 'errors');
            }
        }

        if (empty($error) && $nbContacts > 0) {
            setEventMessages($contactsAdded, null);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
            exit;
        }
    }
}

/*
 * View
 */

$title = $langs->trans('PublicAddContact');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, [], [], '', 'page-public-card'); ?>

<div class="public-card__container" data-public-interface="true">
    <?php if (getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) : ?>
        <div class="public-card__header">
            <div class="header-information">
                <div class="information-title">Convention de formation <?php echo $object->ref . ' - ' . $object->project->ref . ' - ' . $object->project->title; ?></div>
            </div>
        </div>

        <?php $contacts = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
        foreach ($contacts as $contactSingle) : ?>
            <div class="existing-contact">
                <span class="contact-name"><?php echo img_picto('', 'contact', 'class="pictofixedwith paddingright"') . $contactSingle['firstname'] . ' ' . $contactSingle['lastname']; ?></span>
                <span class="contact-email"><?php echo $contactSingle['email']; ?></span>
            </div>
        <?php endforeach; ?>

        <form id="contactsForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $id; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="add_contact">
            <div id="contactsList">
                <div class="contact-row">
                    <input type="text" class="firstname" name="firstname[]" placeholder="<?php echo $langs->transnoentities('FirstName'); ?>" required>
                    <input type="text" class="lastname" name="lastname[]" placeholder="<?php echo $langs->transnoentities('LastName'); ?>" required>
                    <input type="email" class="email" name="email[]" placeholder="<?php echo $langs->transnoentities('Email'); ?>" required>
                </div>
            </div>

            <div class="public-card__footer">
                <button type="button" class="wpeo-button btn-add" id="addContact" style="width: 90%; margin-top: 10px;">+ Ajouter un contact</button>
                <button type="submit" class="wpeo-button no-load button-disable btn-save"><i class="fas fa-save"></i></button>
            </div>
        </form>
    <?php else :
        print '<div class="center">' . $langs->trans('PublicInterfaceForbidden', $langs->transnoentities('OfPublicVehicleLogBook')) . '</div>';
    endif; ?>
</div>
<?php
llxFooter('', 'public');
$db->close();
