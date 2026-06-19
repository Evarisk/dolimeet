<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    admin/financial_and_pedagogical_report.php
 * \ingroup doliopi
 * \brief   Doliopi financial_and_pedagogical_report config page
 */

// Load Doliopi environment
if (file_exists('../doliopi.main.inc.php')) {
    require_once __DIR__ . '/../doliopi.main.inc.php';
} elseif (file_exists('../../doliopi.main.inc.php')) {
    require_once __DIR__ . '/../../doliopi.main.inc.php';
} else {
    die('Include of doliopi main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load Doliopi libraries
require_once __DIR__ . '/../lib/doliopi.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize view objects
$form = new Form($db);

// Permissions
$permissionToRead = $user->hasRight('doliopi', 'adminpage', 'read');

// Security check
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'generate_categories') {
    $tagParentID = saturne_create_category($langs->transnoentities('BPFTagC'), 'product', 0, '', '', $langs->transnoentities('BPFTagCDescription'));
    $BPFTags = ['C1', 'C1bis', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10', 'C11'];
    foreach ($BPFTags as $tag) {
        $tagIDs[$tag] = saturne_create_category($langs->transnoentities('BPFTag' . $tag), 'product', $tagParentID, '', '', $langs->transnoentities('BPFTag' . $tag . 'Description'));
    }

    $BPFSubTagsC1bis = ['C1a', 'C1b', 'C1c', 'C1d', 'C1e', 'C1f', 'C1g', 'C1h'];
    foreach ($BPFSubTagsC1bis as $tag) {
        $subTagIDs[$tag] = saturne_create_category($langs->transnoentities('BPFTag' . $tag), 'product', $tagIDs['C1bis'], '', '', $langs->transnoentities('BPFTag' . $tag . 'Description'));
    }
    $tagIDs['C1bis'] = ['C1bis' => $tagIDs['C1bis'], $subTagIDs];

    dolibarr_set_const($db, 'DOLIOPI_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORY_ID', $tagParentID, 'integer', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLIOPI_FINANCIAL_AND_PEDAGOGICAL_REPORT_SUBCATEGORIES_ID', json_encode($tagIDs), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLIOPI_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORIES_SET', 1, 'integer', 0, '', $conf->entity);

    setEventMessages('SavedConfig', []);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_organization_identification') {
    $trainingOrganizationNumber = GETPOST('training_organization_number', 'alpha');
    if ($trainingOrganizationNumber != getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER')) {
        dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER', $trainingOrganizationNumber, 'chaine', 0, '', $conf->entity);
    }

    $bpfAddressPublic = GETPOST('bpf_address_public', 'int');
    if ($bpfAddressPublic != getDolGlobalInt('DOLIOPI_BPF_ADDRESS_PUBLIC')) {
        dolibarr_set_const($db, 'DOLIOPI_BPF_ADDRESS_PUBLIC', $bpfAddressPublic, 'integer', 0, '', $conf->entity);
    }

    setEventMessages('SavedConfig', []);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_general_information') {
    $bpfRemoteTraining = GETPOST('bpf_remote_training', 'int');
    if ($bpfRemoteTraining != getDolGlobalInt('DOLIOPI_BPF_REMOTE_TRAINING')) {
        dolibarr_set_const($db, 'DOLIOPI_BPF_REMOTE_TRAINING', $bpfRemoteTraining, 'integer', 0, '', $conf->entity);
    }

    setEventMessages('SavedConfig', []);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_manager') {
    $bpfManagerStatus = GETPOST('bpf_manager_status', 'alpha');
    if ($bpfManagerStatus != getDolGlobalString('DOLIOPI_BPF_MANAGER_STATUS')) {
        dolibarr_set_const($db, 'DOLIOPI_BPF_MANAGER_STATUS', $bpfManagerStatus, 'chaine', 0, '', $conf->entity);
    }

    setEventMessages('SavedConfig', []);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Doliopi');
$help_url = 'FR:Module_Doliopi';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = doliopi_admin_prepare_head();
print dol_get_fiche_head($head, 'financial_and_pedagogical_report', $title, -1, 'doliopi_color@doliopi');

// Generate categories.
print load_fiche_titre($langs->trans('Config'), '', '');

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generate_categories">';

print '<tr><td>' . $form->textwithpicto($langs->trans('BPFCategories'), $langs->trans('BPFCategoriesDescription')) . '</td>';
print '<td class="center">';
print '<button class="butAction' . (getDolGlobalInt('DOLIOPI_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORIES_SET') ? 'Refused' : '') . '"' . (getDolGlobalInt('DOLIOPI_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORIES_SET') ? 'disabled' : '') . '>' . $langs->trans('Create') . '</button>';
print '</td></tr>';

print '</form>';
print '</table>';

// Organization identification (BPF Part A)
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_organization_identification">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('TrainingOrganizationNumber');
print '</td>';
print '<td class="minwidth400 maxwidth500">';
print '<input type="text" name="training_organization_number" value="' . dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER')) . '" class="minwidth300"/>';
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('BPFAddressPublic');
print '</td>';
print '<td class="minwidth400 maxwidth500">';
print $form->selectyesno('bpf_address_public', getDolGlobalInt('DOLIOPI_BPF_ADDRESS_PUBLIC'), 1);
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// General information (BPF Part B)
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_general_information">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('BPFRemoteTraining');
print '</td>';
print '<td class="minwidth400 maxwidth500">';
print $form->selectyesno('bpf_remote_training', getDolGlobalInt('DOLIOPI_BPF_REMOTE_TRAINING'), 1);
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Manager (BPF Part H)
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_manager">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('BPFManagerStatus');
print '</td>';
print '<td class="minwidth400 maxwidth500">';
print '<input type="text" name="bpf_manager_status" value="' . dol_escape_htmltag(getDolGlobalString('DOLIOPI_BPF_MANAGER_STATUS')) . '" class="minwidth300"/>';
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Page end
llxFooter();
$db->close();
