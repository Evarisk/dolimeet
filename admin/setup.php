<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/setup.php
 * \ingroup dolimeet
 * \brief   DoliMeet setup page
 */

// Load DoliMeet environment
if (file_exists('../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../dolimeet.main.inc.php';
} elseif (file_exists('../../dolimeet.main.inc.php')) {
    require_once __DIR__ . '/../../dolimeet.main.inc.php';
} else {
    die('Include of dolimeet main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load DoliMeet libraries
require_once __DIR__ . '/../lib/dolimeet.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$value      = GETPOST('value', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize view objects
$form = new Form($db);

// Security check - Protection if external user
$permissionToRead = $user->rights->dolimeet->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'update') {
    $responsibleId        = GETPOST('session_trainer_responsible_id');
    $satisfactionSurveyId = GETPOST('satisfaction_survey_model');

    if ($responsibleId != $conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE) {
        dolibarr_set_const($db, 'DOLIMEET_SESSION_TRAINER_RESPONSIBLE', $responsibleId, 'integer', 0, '', $conf->entity);
        $userTmp = new User($db);
        $userTmp->fetch($responsibleId);
        setEventMessages($langs->trans('SessionTrainerResponsibleIdSet', $user->getFullName($langs)), []);
    }

    if ($satisfactionSurveyId != $conf->global->DOLIMEET_SATISFACTION_SURVEY_SHEET) {
        dolibarr_set_const($db, 'DOLIMEET_SATISFACTION_SURVEY_SHEET', $satisfactionSurveyId, 'integer', 0, '', $conf->entity);
        setEventMessages($langs->trans('SatisfactionSurveySet'), []);
    }
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'DoliMeet');
$help_url = 'FR:Module_DoliMeet';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'dolimeet_color@dolimeet');

// Configuration header
$head = dolimeet_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'dolimeet_color@dolimeet');

print load_fiche_titre($langs->trans('SessionTypes'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('Meeting');
print '</td><td>';
print $langs->trans('EnableObjectDescription', $langs->transnoentities('Meeting'));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLIMEET_MEETING_MENU_ENABLED');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('TrainingSession');
print '</td><td>';
print $langs->trans('EnableObjectDescription', $langs->trans('TrainingSession'));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLIMEET_TRAININGSESSION_MENU_ENABLED');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('Audit');
print '</td><td>';
print $langs->trans('EnableObjectDescription', $langs->trans('Audit'));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLIMEET_AUDIT_MENU_ENABLED');
print '</td>';
print '</tr>';

print load_fiche_titre($langs->trans('TrainingSessions'), '', '');

print '<form method="POST" action="'. $_SERVER['PHP_SELF'] .'">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('SessionTrainerResponsible');
print '</td><td>';
print $langs->transnoentities('SessionTrainerResponsibleDesc');
print '</td>';

print '<td class="center">';
print $form->select_dolusers($conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE, 'session_trainer_responsible_id', 1);
print '<td class="center"><input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '">';
print '</td></tr>';

if (isModEnabled('digiquali')) {
    require_once __DIR__ . '/../../digiquali/class/sheet.class.php';

    $sheet = new Sheet($db);

    print '<tr class="oddeven"><td>';
    print $langs->trans('SatisfactionSurvey');
    print '</td><td>';
    print $langs->transnoentities('SatisfactionSurveyDesc');
    print '</td>';

    print '<td class="center">';
    print $sheet->selectSheetList($conf->global->DOLIMEET_SATISFACTION_SURVEY_SHEET, 'satisfaction_survey_model');
    print '<td class="center"><input type="submit" class="button" name="save" value="'. $langs->trans('Save') . '">';
    print '</td></tr>';
}

print '</form>';

$db->close();
llxFooter();
