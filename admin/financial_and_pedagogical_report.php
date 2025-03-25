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
 * \ingroup dolimeet
 * \brief   DoliMeet financial_and_pedagogical_report config page
 */

// Load DoliMeet environment
if (!file_exists('../dolimeet.main.inc.php')) {
    die('Include of dolimeet main fails');
}
require_once __DIR__ . '/../dolimeet.main.inc.php';

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load DoliMeet libraries
require_once __DIR__ . '/../lib/dolimeet.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize view objects
$form = new Form($db);

// Permissions
$permissionToRead = $user->hasRight('dolimeet', 'adminpage', 'read');

// Security check
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'generate_categories') {
    $tagParentID = saturne_create_category($langs->transnoentities('FinancialAndPedagogicalReportName'), 'product');
    $tags[]      = saturne_create_category($langs->transnoentities('ApprenticeshipContractName'), 'product', $tagParentID, '', '', $langs->transnoentities('ApprenticeshipContractDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('ProfessionalizationContractName'), 'product', $tagParentID, '', '', $langs->transnoentities('ProfessionalizationContractDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('PromotionOrReconversionContractName'), 'product', $tagParentID, '', '', $langs->transnoentities('PromotionOrReconversionContractDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('IndividualTrainingLeavesName'), 'product', $tagParentID, '', '', $langs->transnoentities('IndividualTrainingLeavesDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('CPFName'), 'product', $tagParentID, '', '', $langs->transnoentities('CPFDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('CSPName'), 'product', $tagParentID, '', '', $langs->transnoentities('CSPDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('SpecificDevicesForSelfEmployedName'), 'product', $tagParentID, '', '', $langs->transnoentities('SpecificDevicesForSelfEmployedDescription'));
    $tags[]      = saturne_create_category($langs->transnoentities('DevelopmentPlanName'), 'product', $tagParentID, '', '', $langs->transnoentities('DevelopmentPlanDescription'));

    dolibarr_set_const($db, 'DOLIMEET_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORY_ID', $tagParentID, 'integer', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLIMEET_FINANCIAL_AND_PEDAGOGICAL_REPORT_SUBCATEGORIES_ID', json_encode($tags), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLIMEET_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORIES_SET', 1, 'integer', 0, '', $conf->entity);

    setEventMessages('SavedConfig', []);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'DoliMeet');
$help_url = 'FR:Module_DoliMeet';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = dolimeet_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'dolimeet_color@dolimeet');

// Generate categories.
print load_fiche_titre($langs->trans('SheetCategories'), '', '', 0, 'sheetCategories');

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
print '</tr>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generate_categories">';

print '<tr><td>' . $langs->trans('GenerateCategories') . '</td>';
print '<td class="center">';
print getDolGlobalInt('DOLIMEET_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORIES_SET') ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
print '</td>';
print '<td class="center">';
print getDolGlobalInt('DOLIMEET_FINANCIAL_AND_PEDAGOGICAL_REPORT_CATEGORIES_SET') ? '<button class="butActionRefused">'.$langs->trans('Create') . '</button>' : '<input type="submit" class="button" value="'. $langs->trans('Create') . '">' ;
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans('CategoriesGeneration'));
print '</td></tr>';

print '</form>';
print '</table>';

// Page end
llxFooter();
$db->close();
