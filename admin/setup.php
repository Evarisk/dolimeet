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
if (isModEnabled('fckeditor')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
}

// Load DoliMeet libraries
require_once __DIR__ . '/../lib/dolimeet.lib.php';
require_once __DIR__ . '/../lib/dolimeet_function.lib.php';

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

$formationServices = get_formation_service();

// Security check - Protection if external user
$permissionToRead = $user->rights->dolimeet->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'set_session_trainer_responsible') {
    $responsibleId = GETPOST('session_trainer_responsible_id');
    if ($responsibleId != $conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE) {
        dolibarr_set_const($db, 'DOLIMEET_SESSION_TRAINER_RESPONSIBLE', $responsibleId, 'integer', 0, '', $conf->entity);
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'set_satisfaction_survey') {
    $satisfactionSurveys = ['customer', 'billing', 'trainee', 'sessiontrainer', 'opco'];
    foreach ($satisfactionSurveys as $satisfactionSurvey) {
        $satisfactionSurveyID = GETPOST($satisfactionSurvey . '_satisfaction_survey_model');
        $confName             = 'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_SHEET';
        if ($satisfactionSurveyID != $conf->global->$confName) {
            dolibarr_set_const($db, $confName, $satisfactionSurveyID, 'integer', 0, '', $conf->entity);
        }
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_formation_service') {
    foreach ($formationServices as $formationService) {
        $formationServiceID = GETPOST($formationService['name'], 'int');
        if ($formationServiceID > 0) {
            dolibarr_set_const($db, $formationService['code'], $formationServiceID, 'integer', 0, '', $conf->entity);
        }
    }

    $traningSessionLocation = GETPOST('training_session_location');
    if ($traningSessionLocation != getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION')) {
        dolibarr_set_const($db, 'DOLIMEET_TRAININGSESSION_LOCATION', $traningSessionLocation, 'chaine', 0, '', $conf->entity);
    }

    if (isModEnabled('fckeditor')) {
        $formationProjectLabel = GETPOST('DOLIMEET_FORMATION_PROJECT_LABEL', 'none');
        if ($formationProjectLabel != getDolGlobalString('DOLIMEET_FORMATION_PROJECT_LABEL')) {
            dolibarr_set_const($db, 'DOLIMEET_FORMATION_PROJECT_LABEL', $formationProjectLabel, 'chaine', 0, '', $conf->entity);
        }
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

print '</table>';

// Formation
print load_fiche_titre($langs->transnoentities('Formation'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="formation_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_formation_service">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Name') . '</td>';
print '<td>' . $langs->transnoentities('Service') . '</td>';
print '</tr>';

// FormationServices
foreach ($formationServices as $formationService) {
    print '<tr class="oddeven"><td>' . $langs->transnoentities($formationService['name']) . '</td><td>';
    print img_picto('', 'service', 'class="pictofixedwidth"');
    $formationServiceCode = $formationService['code'];
    $form->select_produits((GETPOSTISSET($formationService['name']) ? GETPOST($formationService['name'], 'int') : $conf->global->$formationServiceCode), $formationService['name'], 1, 0, 1, -1, 2, '', '', '', '', '1', 0, 'minwidth300 maxwidth400 widthcentpercentminusxx', 1);
    print ' <a href="' . DOL_URL_ROOT . '/product/card.php?action=create&type=1&ref=' . $formationService['ref'] . '&label=' . $langs->transnoentities($formationService['name']) . '&statut_buy=0&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?' . $formationService['name'] . '=&#95;&#95;ID&#95;&#95;') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities('AddProduct') . '"></span></a>';
    print '</td></tr>';
}

// Training session location
print '<tr class="oddeven"><td>' . $langs->transnoentities('TrainingSessionLocation') . '</td><td>';
print '<input type="radio" id="TrainingSessionLocationCompany" name="training_session_location" value="TrainingSessionLocationCompany"' . (getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION') == 'TrainingSessionLocationCompany' ? 'checked' : '') . '/><label for="TrainingSessionLocationCompany">' . img_picto('', 'company', 'class="paddingright"') . $langs->transnoentities('TrainingSessionLocationCompany') . '</label><br>';
print '<input type="radio" id="TrainingSessionLocationThirdParty" name="training_session_location" value="TrainingSessionLocationThirdParty"' . (getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION') == 'TrainingSessionLocationThirdParty' ? 'checked' : '') . '/><label for="TrainingSessionLocationThirdParty">' . img_picto('', 'company', 'class="paddingright"') . $langs->transnoentities('TrainingSessionLocationThirdParty') . '</label><br>';
print '<input type="radio" id="TrainingSessionLocationOther" name="training_session_location" value="TrainingSessionLocationOther"' . (getDolGlobalString('DOLIMEET_TRAININGSESSION_LOCATION') == 'TrainingSessionLocationOther' ? 'checked' : '') . '/><label for="TrainingSessionLocationOther">' . img_picto('', 'fontawesome_fa-font_fas', 'class="paddingright"') . $langs->transnoentities('TrainingSessionLocationOther') . '</label>';
print '</td></tr>';

if (isModEnabled('fckeditor')) {
    $substitutionArray = getCommonSubstitutionArray($langs);
    complete_substitutions_array($substitutionArray, $langs);

    // Substitution array/string
    $helpForSubstitution = '';
    if (is_array($substitutionArray) && count($substitutionArray)) {
        $helpForSubstitution .= $langs->trans('AvailableVariables') . ' : <br>';
    }
    foreach ($substitutionArray as $key => $val) {
        $helpForSubstitution .= $key . ' -> '. $langs->trans(dol_string_nohtmltag(dolGetFirstLineOfText($val))) . '<br>';
    }

    // Formation project label
    $formationProjectLabel = $langs->transnoentities(getDolGlobalString('DOLIMEET_FORMATION_PROJECT_LABEL')) ?: $langs->transnoentities('FormationProjectLabel');
    print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->transnoentities('FormationProjectLabelTitle'), $helpForSubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
    print '</td><td>';
    $dolEditor = new DolEditor('DOLIMEET_FORMATION_PROJECT_LABEL', $formationProjectLabel, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
    $dolEditor->Create();
    print '</td></tr>';
}

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
print img_picto($langs->trans('User'), 'user', 'class="pictofixedwidth"') . $form->select_dolusers($conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE, 'session_trainer_responsible_id', 1, null, 0, '', '', '0', 0, 0, '', 0, '','minwidth400 maxwidth500');
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

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

    $satisfactionSurveys = ['customer', 'billing', 'trainee', 'sessiontrainer', 'opco'];
    foreach ($satisfactionSurveys as $satisfactionSurvey) {
        print '<tr class="oddeven"><td>';
        print $langs->trans(ucfirst($satisfactionSurvey) . 'SatisfactionSurvey');
        print '</td><td>';
        print $langs->transnoentities(ucfirst($satisfactionSurvey) . 'SatisfactionSurveyDescription');
        print '</td>';

        print '<td class="minwidth400 maxwidth500">';
        $confName = 'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_SHEET';
        print img_picto($langs->trans('Sheet'), $sheet->picto, 'class="pictofixedwidth"') . $sheet->selectSheetList($conf->global->$confName, $satisfactionSurvey . '_satisfaction_survey_model', 's.type = "survey" AND s.status = ' . Sheet::STATUS_LOCKED, '1', 0, 0, [], '', 0, 0, 'minwidth400 maxwidth500');
        print '</td></tr>';
    }

    print '</table>';
    print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
    print '</form>';
}

$db->close();
llxFooter();
