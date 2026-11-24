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

// Load DoliMeet libraries
require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs(['users', 'errors']);

// Get parameters
$id        = GETPOSTINT('id');
$entity    = GETPOSTINT('entity');
$secureKey = GETPOST('securekey', 'aZ09');
$action    = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Contrat($db);
$user   = new User($db);

// The link carries the entity of the contract; without multicompany there is only one
if (!isModEnabled('multicompany') || $entity <= 0) {
    $entity = (int) $conf->entity;
}

// The secure key of the link is the only secret of this page: it is computed from the contract and its entity
// (dolimeet_get_public_contact_url()), so a contract ID alone, or a key moved to another contract, opens nothing.
// Same answer whatever fails, so that the page tells nothing about the contracts that exist
if (!dolimeet_check_public_link_key('contact', $id, $entity, $secureKey)) {
    http_response_code(403);
    accessforbidden('PublicAddContactInvalidLink', 0, 0, 1);
}

if ($entity != $conf->entity) {
    $conf->setEntityValues($db, $entity);
}

if ($object->fetch($id) <= 0 || (int) $object->entity !== $entity || empty($object->array_options['options_trainingsession_type'])) {
    http_response_code(403);
    accessforbidden('PublicAddContactInvalidLink', 0, 0, 1);
}
$object->fetchProject();

$publicInterfaceEnabled = getDolGlobalInt('DOLIMEET_CONTACT_PUBLIC_INTERFACE_ENABLED') > 0;

$publicInterfaceUserId = getDolGlobalInt('DOLIMEET_DEFAULT_PUBLIC_INTERFACE_USER');
if ($publicInterfaceUserId > 0) {
    $user->fetch($publicInterfaceUserId);
    $user->loadRights();
    $permissionToCreateContact = $user->hasRight('societe', 'contact', 'creer');
    if ($permissionToCreateContact == 0) {
        die($langs->transnoentities('MissingDefaultUserPublicInterfaceRightsConfig'));
    }
} else {
    die($langs->transnoentities('MissingDefaultUserPublicInterfaceConfig'));
}

// URL of this page with the parameters of its link, for the form and the redirection after adding
$pageUrl = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&entity=' . $entity . '&securekey=' . $secureKey;

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'add_contact' && $publicInterfaceEnabled && $permissionToCreateContact) {
        $error = 0;

        $firstNames = GETPOST('firstname', 'array');
        $lastNames  = GETPOST('lastname', 'array');
        $emails     = GETPOST('email', 'array');

        // Trainees already on the contract, by email: a row with one of them is not added twice
        $existingTrainees = [];
        $contacts         = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
        if (is_array($contacts)) {
            foreach ($contacts as $contactSingle) {
                $existingTrainees[dol_strtolower(trim((string) $contactSingle['email']))] = $contactSingle;
            }
        }

        $contactsAlreadyAdded = [];
        $contactsAdded        = [];
        foreach ($emails as $rowKey => $email) {
            $firstName = trim((string) ($firstNames[$rowKey] ?? ''));
            $lastName  = trim((string) ($lastNames[$rowKey] ?? ''));
            $email     = trim((string) $email);
            if ($firstName === '' && $lastName === '' && $email === '') {
                continue;
            }

            // Same checks as the form fields, which a request can skip
            if ($firstName === '' || $lastName === '') {
                $error++;
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities($firstName === '' ? 'FirstName' : 'LastName')) . ' (' . dol_escape_htmltag($email) . ')', [], 'errors');
                continue;
            }
            if (!isValidEmail($email)) {
                $error++;
                setEventMessages($langs->trans('ErrorBadEMail', dol_escape_htmltag($email)), [], 'errors');
                continue;
            }

            $emailKey = dol_strtolower($email);
            if (isset($existingTrainees[$emailKey])) {
                $contactsAlreadyAdded[] = dol_escape_htmltag($existingTrainees[$emailKey]['firstname'] . ' ' . $existingTrainees[$emailKey]['lastname'] . ' (' . $existingTrainees[$emailKey]['email'] . ')');
                continue;
            }

            $contact            = new Contact($db);
            $contact->firstname = $firstName;
            $contact->lastname  = $lastName;
            $contact->email     = $email;
            $contact->socid     = $object->socid;

            $result = $contact->create($user);
            if ($result > 0) {
                $object->restrictiononfksoc                 = 0; // We disable the restriction on fk_soc to be able to add contact linked to third party of contract
                $object->context['createformpubliccontact'] = 'createformpubliccontact';
                $object->contact_id                         = $result;

                $result = $object->add_contact($result, 'TRAINEE');
                if ($result < 0) {
                    $error++;
                    setEventMessages($object->error, $object->errors, 'errors');
                } else {
                    $existingTrainees[$emailKey] = ['firstname' => $firstName, 'lastname' => $lastName, 'email' => $email];
                    $contactsAdded[]             = dol_escape_htmltag($firstName . ' ' . $lastName . ' (' . $email . ')');
                }
            } else {
                $error++;
                setEventMessages($contact->error, $contact->errors, 'errors');
            }
        }

        if (!empty($contactsAlreadyAdded)) {
            setEventMessages($langs->trans('ContactsAlreadyAdded') . ' : <br>' . implode('<br>', $contactsAlreadyAdded), [], 'warnings');
        }

        if (!empty($contactsAdded)) {
            setEventMessages($langs->trans('ContactsAdded') . ' : <br>' . implode('<br>', $contactsAdded), []);
        }

        if (empty($error) && !empty($contactsAdded)) {
            header('Location: ' . $pageUrl);
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

saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'page-public-card'); ?>

<div class="public-card__container" data-public-interface="true">
    <?php if ($publicInterfaceEnabled) : ?>
        <div class="public-card__header">
            <div class="header-information">
                <div class="information-title">Convention de formation <?php echo dol_escape_htmltag($object->ref . (is_object($object->project) ? ' - ' . $object->project->ref . ' - ' . $object->project->title : '')); ?></div>
            </div>
        </div>

        <?php $contacts = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
        foreach ((is_array($contacts) ? $contacts : []) as $contactSingle) : ?>
            <div class="existing-contact">
                <span class="contact-name"><?php echo img_picto('', 'contact', 'class="pictofixedwith paddingright"') . dol_escape_htmltag($contactSingle['firstname'] . ' ' . $contactSingle['lastname']); ?></span>
                <span class="contact-email"><?php echo dol_escape_htmltag((string) $contactSingle['email']); ?></span>
            </div>
        <?php endforeach; ?>

        <form id="contactsForm" method="POST" action="<?php echo dol_escape_htmltag($pageUrl); ?>">
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
