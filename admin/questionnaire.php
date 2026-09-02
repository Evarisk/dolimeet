<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    admin/questionnaire.php
 * \ingroup dolimeet
 * \brief   DoliMeet questionnaire config page
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
require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';

// Load DoliMeet libraries
require_once __DIR__ . '/../lib/dolimeet.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize view objects
$form = new Form($db);

// Security check - Protection if external user
$permissiontoread = $user->rights->dolimeet->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'set_satisfaction_survey') {
    $satisfactionSurveys = ['sessiontrainer', 'trainee', 'customer', 'billing'];
    foreach ($satisfactionSurveys as $satisfactionSurvey) {
        $satisfactionSurveyID = GETPOST($satisfactionSurvey . '_satisfaction_survey_model');
        $confName             = 'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_SHEET';
        if ($satisfactionSurveyID != getDolGlobalInt($confName)) {
            dolibarr_set_const($db, $confName, $satisfactionSurveyID, 'integer', 0, '', $conf->entity);
        }

        // One mail model to hand the questionnaire over, one to chase it: the survey is sent and
        // reminded per role, so each role names its own
        $mailTemplateConfNames = [
            'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_EMAIL_TEMPLATE'          => $satisfactionSurvey . '_satisfaction_survey_email_template',
            'DOLIMEET_' . dol_strtoupper($satisfactionSurvey) . '_SATISFACTION_SURVEY_REMINDER_EMAIL_TEMPLATE' => $satisfactionSurvey . '_satisfaction_survey_reminder_email_template'
        ];
        foreach ($mailTemplateConfNames as $mailTemplateConfName => $mailTemplateInputName) {
            $mailTemplateID = max(0, GETPOSTINT($mailTemplateInputName));
            if ($mailTemplateID != getDolGlobalInt($mailTemplateConfName)) {
                dolibarr_set_const($db, $mailTemplateConfName, $mailTemplateID, 'integer', 0, '', $conf->entity);
            }
        }
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'set_survey_reminder') {
    // A delay of zero would chase on every run, a maximum of zero is the way to turn the reminder off
    dolibarr_set_const($db, 'DOLIMEET_SATISFACTION_SURVEY_REMINDER_DELAY', max(1, GETPOSTINT('survey_reminder_delay')), 'integer', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLIMEET_SATISFACTION_SURVEY_REMINDER_MAX', max(0, GETPOSTINT('survey_reminder_max')), 'integer', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'DoliMeet');
$help_url = 'FR:Module_DoliMeet';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkBack = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = dolimeet_admin_prepare_head();
print dol_get_fiche_head($head, 'questionnaire', $title, -1, 'dolimeet_color@dolimeet');

if (!isModEnabled('digiquali') || version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '<')) {
    print '<div class="warning">' . $langs->trans('SurveyReminderDigiQualiRequired') . '</div>';

    print dol_get_fiche_end();

    llxFooter();
    $db->close();
    exit;
}

// Load DigiQuali libraries
require_once __DIR__ . '/../../digiquali/class/sheet.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturnemail.class.php';

$sheet = new Sheet($db);

print load_fiche_titre($langs->trans('SatisfactionSurvey'), '', '', 0, 'satisfaction_survey');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_satisfaction_survey">';

// The mail models are the contract ones, the same the contract mail form offers
$mailTemplates       = [];
$saturneMail         = new SaturneMail($db);
$mailTemplateRecords = $saturneMail->fetchAll('ASC', 'label', 0, 0, ['customsql' => "t.type_template = 'contract' AND t.entity IN (0, " . $conf->entity . ')']);
if (is_array($mailTemplateRecords)) {
    foreach ($mailTemplateRecords as $mailTemplateRecord) {
        $mailTemplates[$mailTemplateRecord->id] = $mailTemplateRecord->label;
    }
}

// Until a model is picked, the survey mail keeps the one the module registered on activation
$defaultMailTemplates = json_decode(getDolGlobalString('DOLIMEET_EMAIL_TEMPLATE_SATISFACTION_SURVEY'), true);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '<td>' . $langs->trans('SatisfactionSurveyEmailTemplate') . '</td>';
print '<td>' . $langs->trans('SatisfactionSurveyReminderEmailTemplate') . '</td>';
print '</tr>';

$satisfactionSurveys = [
    'sessiontrainer' => ['picto' => 'user-tie'],
    'trainee'        => ['picto' => 'user-graduate'],
    'customer'       => ['picto' => 'building'],
    'billing'        => ['picto' => 'file-invoice-dollar'],
];
foreach ($satisfactionSurveys as $satisfactionSurveyRole => $satisfactionSurvey) {
    print '<tr class="oddeven"><td>';
    print $form->textwithpicto(img_picto('', 'fontawesome_fa-' . $satisfactionSurvey['picto'] . '_fas', 'class="pictofixedwidth"') . $langs->trans(ucfirst($satisfactionSurveyRole) . 'SatisfactionSurvey'), $langs->transnoentities(ucfirst($satisfactionSurveyRole) . 'SatisfactionSurveyDescription'));
    print '</td>';
    print '<td class="minwidth400 maxwidth500">';
    $confName = 'DOLIMEET_' . dol_strtoupper($satisfactionSurveyRole) . '_SATISFACTION_SURVEY_SHEET';
    print img_picto($langs->trans('Sheet'), $sheet->picto, 'class="pictofixedwidth"') . $sheet->selectSheetList(getDolGlobalInt($confName), $satisfactionSurveyRole . '_satisfaction_survey_model', 's.type = "survey" AND s.status = ' . Sheet::STATUS_LOCKED, '1', 0, 0, [], '', 0, 0, 'minwidth400 maxwidth500');
    print '</td>';

    $mailTemplateConfName = 'DOLIMEET_' . dol_strtoupper($satisfactionSurveyRole) . '_SATISFACTION_SURVEY_EMAIL_TEMPLATE';
    $reminderConfName     = 'DOLIMEET_' . dol_strtoupper($satisfactionSurveyRole) . '_SATISFACTION_SURVEY_REMINDER_EMAIL_TEMPLATE';
    $selectedMailTemplate = max(0, getDolGlobalInt($mailTemplateConfName)) ?: (int) ($defaultMailTemplates[$satisfactionSurveyRole] ?? 0);

    print '<td class="minwidth200 maxwidth300">';
    print img_picto('', 'email', 'class="pictofixedwidth"') . $form->selectarray($satisfactionSurveyRole . '_satisfaction_survey_email_template', $mailTemplates, $selectedMailTemplate, 1, 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth300');
    print '</td>';

    print '<td class="minwidth200 maxwidth300">';
    print img_picto('', 'email', 'class="pictofixedwidth"') . $form->selectarray($satisfactionSurveyRole . '_satisfaction_survey_reminder_email_template', $mailTemplates, max(0, getDolGlobalInt($reminderConfName)), 1, 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth300');
    print '</td></tr>';
}

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

print load_fiche_titre($langs->trans('SurveyReminder'), '', '', 0, 'survey_reminder');

// The reminder only ever leaves once the scheduled job runs: say so where it is configured
$cronJob = new Cronjob($db);
$result  = $cronJob->fetch(0, 'DoliMeetSurveyReminder', 'sendSatisfactionSurveyReminders');
if ($result <= 0 || $cronJob->id <= 0) {
    print '<div class="warning">' . $langs->trans('SurveyReminderCronNotInstalled') . '</div>';
} elseif (empty($cronJob->status)) {
    print '<div class="warning">' . $langs->trans('SurveyReminderCronDisabled', '<a href="' . dol_buildpath('/cron/card.php', 1) . '?id=' . $cronJob->id . '">' . $langs->trans('SurveyReminderCronLink') . '</a>') . '</div>';
} else {
    print '<div class="info">' . $langs->trans('SurveyReminderCronEnabled', '<a href="' . dol_buildpath('/cron/card.php', 1) . '?id=' . $cronJob->id . '">' . $langs->trans('SurveyReminderCronLink') . '</a>') . '</div>';
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_survey_reminder">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans('SurveyReminderDelay') . '</td>';
print '<td><input type="number" min="1" class="width75" name="survey_reminder_delay" value="' . (getDolGlobalInt('DOLIMEET_SATISFACTION_SURVEY_REMINDER_DELAY') ?: 7) . '"></td></tr>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans('SurveyReminderMax'), $langs->transnoentities('SurveyReminderMaxHelp'));
print '</td>';
print '<td><input type="number" min="0" class="width75" name="survey_reminder_max" value="' . (isset($conf->global->DOLIMEET_SATISFACTION_SURVEY_REMINDER_MAX) ? getDolGlobalInt('DOLIMEET_SATISFACTION_SURVEY_REMINDER_MAX') : 3) . '"></td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
