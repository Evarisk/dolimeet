<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load DoliMeet libraries
require_once __DIR__ . '/../lib/dolimeet.lib.php';
require_once __DIR__ . '/../lib/dolimeet_function.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['categories']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$value      = GETPOST('value', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize view objects
$form         = new Form($db);
$formOther    = new FormOther($db);
$formProjects = new FormProjets($db);
$extraFields  = new ExtraFields($db);

$formationServices = get_formation_service();

// Security check - Protection if external user
$permissionToRead = $user->rights->dolimeet->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'set_session_trainer_responsible') {
    $responsibleId = GETPOST('session_trainer_responsible_id');
    if ($responsibleId != getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE')) {
        dolibarr_set_const($db, 'DOLIMEET_SESSION_TRAINER_RESPONSIBLE', $responsibleId, 'integer', 0, '', $conf->entity);
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'set_satisfaction_survey') {
    $satisfactionSurveys = ['billing', 'customer', 'trainee', 'sessiontrainer'];
    foreach ($satisfactionSurveys as $satisfactionSurvey) {
        $satisfactionSurveyID = GETPOST($satisfactionSurvey . '_satisfaction_survey_model');
        $confName             = 'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_SHEET';
        if ($satisfactionSurveyID != getDolGlobalInt($confName)) {
            dolibarr_set_const($db, $confName, $satisfactionSurveyID, 'integer', 0, '', $conf->entity);
        }
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_formation_datas') {
    foreach ($formationServices as $formationService) {
        $formationServiceID = GETPOST($formationService['name'], 'int');
        if ($formationServiceID > 0 && $formationServiceID != getDolGlobalInt($formationService['code'])) {
            dolibarr_set_const($db, $formationService['code'], $formationServiceID, 'integer', 0, '', $conf->entity);
        }
    }

    $categoryID = GETPOST('formation_main_category', 'int');
    if ($categoryID != getDolGlobalInt('DOLIMEET_FORMATION_MAIN_CATEGORY')) {
        dolibarr_set_const($db, 'DOLIMEET_FORMATION_MAIN_CATEGORY', $categoryID, 'integer', 0, '', $conf->entity);
    }

    $trainingSessionTemplatesProject = GETPOST('training_session_templates_project', 'int');
    if ($trainingSessionTemplatesProject != getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT')) {
        dolibarr_set_const($db, 'DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT', $trainingSessionTemplatesProject, 'integer', 0, '', $conf->entity);
    }

    $timePeriods = [
        'morning_start_hour'   => 'DOLIMEET_TRAININGSESSION_MORNING_START_HOUR',
        'morning_end_hour'     => 'DOLIMEET_TRAININGSESSION_MORNING_END_HOUR',
        'afternoon_start_hour' => 'DOLIMEET_TRAININGSESSION_AFTERNOON_START_HOUR',
        'afternoon_end_hour'   => 'DOLIMEET_TRAININGSESSION_AFTERNOON_END_HOUR'
    ];
    foreach ($timePeriods as $postKey => $globalKey) {
        $timeInSeconds = GETPOST($postKey);
        if ($timeInSeconds != getDolGlobalString($globalKey)) {
            dolibarr_set_const($db, $globalKey, $timeInSeconds, 'chaine', 0, '', $conf->entity);
        }
    }

    $trainingSessionLocation = GETPOST('training_session_location');
    if ($trainingSessionLocation != getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION')) {
        dolibarr_set_const($db, 'DOLIMEET_TRAININGSESSION_LOCATION', $trainingSessionLocation, 'chaine', 0, '', $conf->entity);
    }

    $trainingSessionAbsenceRate = GETPOST('training_session_absence_rate', 'int');
    if ($trainingSessionAbsenceRate != getDolGlobalInt('DOLIMEET_TRAININGSESSION_ABSENCE_RATE')) {
        dolibarr_set_const($db, 'DOLIMEET_TRAININGSESSION_ABSENCE_RATE', $trainingSessionAbsenceRate, 'integer', 0, '', $conf->entity);
    }

    setEventMessage('SavedConfig');
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
$linkBack = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkBack, 'title_setup');

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
print ajax_constantonoff('DOLIMEET_TRAININGSESSION_MENU_ENABLED',  [], null, 0, 0, 1);
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

print '</table>';

if (getDolGlobalInt('DOLIMEET_TRAININGSESSION_MENU_ENABLED')) {
    // Formation
    print load_fiche_titre($langs->transnoentities('Formation'), '', '');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="formation_form">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_formation_datas">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('Name') . '</td>';
    print '<td>' . $langs->transnoentities('Value') . '</td>';
    print '</tr>';

    // Formation services
    foreach ($formationServices as $formationService) {
        print '<tr class="oddeven"><td>' . $langs->transnoentities($formationService['name']) . '</td><td>';
        print img_picto('', 'service', 'class="pictofixedwidth"');
        $formationServiceCode = $formationService['code'];
        $form->select_produits((GETPOSTISSET($formationService['name']) ? GETPOST($formationService['name'], 'int') : getDolGlobalInt($formationServiceCode)), $formationService['name'], 1, 0, 1, -1, 2, '', '', '', '', '1', 0, 'minwidth300 maxwidth400 widthcentpercentminusxx', 1);
        print ' <a href="' . DOL_URL_ROOT . '/product/card.php?action=create&type=1&ref=' . $formationService['ref'] . '&label=' . $langs->transnoentities($formationService['name']) . '&statut_buy=0&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?' . $formationService['name'] . '=&#95;&#95;ID&#95;&#95;') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities('AddProduct') . '"></span></a>';
        print '</td></tr>';
    }

    // Set default main category
    print '<tr class="oddeven"><td>' . $langs->transnoentities('FormationServiceMainCategory') . '</td><td>';
    print img_picto('', 'category', 'class="pictofixedwidth"');
    print $formOther->select_categories('product', getDolGlobalInt('DOLIMEET_FORMATION_MAIN_CATEGORY'), 'formation_main_category', 0, 1, 'minwidth300 maxwidth400 widthcentpercentminusx');
    print ' <a href="' . DOL_URL_ROOT . '/categories/card.php?action=create&type=product&label=' . $langs->transnoentities('Formation') . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities('CreateCat') . '"></span></a>';
    print '</td></tr>';

    // Training session templates project
    print '<tr class="oddeven"><td>' . $langs->transnoentities('TrainingSessionTemplates') . '</td><td>';
    print img_picto('', 'project', 'class="pictofixedwidth"');
    $formProjects->select_projects(-1, (GETPOSTISSET('training_session_templates_project') ? GETPOST('training_session_templates_project', 'int') : getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT')), 'training_session_templates_project', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'minwidth300 maxwidth400 widthcentpercentminusxx');
    print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?action=create&status=1&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?training_session_templates_project=&#95;&#95;ID&#95;&#95;') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities('AddProject') . '"></span></a>';
    print '</td></tr>';

    // Training session durations
    print '<tr class="oddeven"><td>' . $langs->transnoentities('TrainingSessionDurations') . '</td><td>';
    print $langs->transnoentities('MorningStartHour') . '<input type="time" class="marginleftonly" name="morning_start_hour" value="' . getDolGlobalString('DOLIMEET_TRAININGSESSION_MORNING_START_HOUR') . '" /><br>';
    print $langs->transnoentities('MorningEndHour') . '<input type="time" class="marginleftonly" name="morning_end_hour" value="' . getDolGlobalString('DOLIMEET_TRAININGSESSION_MORNING_END_HOUR') . '" /><br>';
    print $langs->transnoentities('AfternoonStartHour') . '<input type="time" class="marginleftonly" name="afternoon_start_hour" value="' . getDolGlobalString('DOLIMEET_TRAININGSESSION_AFTERNOON_START_HOUR') . '" /><br>';
    print $langs->transnoentities('AfternoonEndHour') . '<input type="time" class="marginleftonly" name="afternoon_end_hour" value="' . getDolGlobalString('DOLIMEET_TRAININGSESSION_AFTERNOON_END_HOUR') . '" /><br>';
    print '</td></tr>';

    // Training session location
    print '<tr class="oddeven"><td>' . $langs->transnoentities('TrainingSessionLocation') . '</td><td>';
    print '<input type="radio" id="TrainingSessionLocationCompany" name="training_session_location" value="TrainingSessionLocationCompany"' . (getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION') == 'TrainingSessionLocationCompany' ? 'checked' : '') . '/><label for="TrainingSessionLocationCompany">' . img_picto('', 'company', 'class="paddingright"') . $langs->transnoentities('TrainingSessionLocationCompany') . '</label><br>';
    print '<input type="radio" id="TrainingSessionLocationThirdParty" name="training_session_location" value="TrainingSessionLocationThirdParty"' . (getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION') == 'TrainingSessionLocationThirdParty' ? 'checked' : '') . '/><label for="TrainingSessionLocationThirdParty">' . img_picto('', 'company', 'class="paddingright"') . $langs->transnoentities('TrainingSessionLocationThirdParty') . '</label><br>';
    print '<input type="radio" id="TrainingSessionLocationOther" name="training_session_location" value="TrainingSessionLocationOther"' . (getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION') == 'TrainingSessionLocationOther' ? 'checked' : '') . '/><label for="TrainingSessionLocationOther">' . img_picto('', 'fontawesome_fa-font_fas', 'class="paddingright"') . $langs->transnoentities('TrainingSessionLocationOther') . '</label>';
    print '</td></tr>';

    // Training session absence rate
    print '<tr class="oddeven"><td>' . $langs->transnoentities('TrainingSessionAbsenceRate') . '</td><td>';
    print img_picto('', 'sort-numeric-down', 'class="pictofixedwidth"');
    print '<input type="number" name="training_session_absence_rate" min="0" max="100" value="' . getDolGlobalInt('DOLIMEET_TRAININGSESSION_ABSENCE_RATE') . '"/>';
    print '</td></tr>';

    print '</table>';
    print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
    print '</form>';

    print load_fiche_titre($langs->trans('TrainingSessions'), '', '');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="set_session_trainer_responsible">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Name') . '</td>';
    print '<td>' . $langs->trans('Description') . '</td>';
    print '<td>' . $langs->trans('Value') . '</td>';
    print '</tr>';

    print '<tr class="oddeven"><td>';
    print $langs->trans('SessionTrainerResponsible');
    print '</td><td>';
    print $langs->transnoentities('SessionTrainerResponsibleDesc');
    print '</td>';

    print '<td class="minwidth400 maxwidth500">';
    print img_picto($langs->trans('User'), 'user', 'class="pictofixedwidth"') . $form->select_dolusers(getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE'), 'session_trainer_responsible_id', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'minwidth400 maxwidth500');
    print '</td></tr>';

    print '</table>';
    print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
    print '</form>';
}

if (isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
    require_once __DIR__ . '/../../digiquali/class/sheet.class.php';

    $sheet = new Sheet($db);

    print load_fiche_titre($langs->trans('SatisfactionSurvey'), '', '');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="set_satisfaction_survey">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Name') . '</td>';
    print '<td>' . $langs->trans('Description') . '</td>';
    print '<td>' . $langs->trans('Value') . '</td>';
    print '</tr>';

    $satisfactionSurveys = ['billing', 'customer', 'trainee', 'sessiontrainer'];
    foreach ($satisfactionSurveys as $satisfactionSurvey) {
        print '<tr class="oddeven"><td>';
        print $langs->trans(ucfirst($satisfactionSurvey) . 'SatisfactionSurvey');
        print '</td><td>';
        print $langs->transnoentities(ucfirst($satisfactionSurvey) . 'SatisfactionSurveyDescription');
        print '</td>';

        print '<td class="minwidth400 maxwidth500">';
        $confName = 'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_SHEET';
        print img_picto($langs->trans('Sheet'), $sheet->picto, 'class="pictofixedwidth"') . $sheet->selectSheetList(getDolGlobalInt($confName), $satisfactionSurvey . '_satisfaction_survey_model', 's.type = "survey" AND s.status = ' . Sheet::STATUS_LOCKED, '1', 0, 0, [], '', 0, 0, 'minwidth400 maxwidth500');
        print '</td></tr>';
    }

    print '</table>';
    print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
    print '</form>';
}

$db->close();
llxFooter();
