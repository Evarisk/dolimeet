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
 * \file    class/dolimeetsurveyreminder.class.php
 * \ingroup dolimeet
 * \brief   Scheduled reminder of the satisfaction surveys nobody answered
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../saturne/class/saturnemail.class.php';

/**
 * Class for the scheduled satisfaction survey reminder
 */
class DoliMeetSurveyReminder
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Output of the run, read back by the scheduled job list
     */
    public string $output = '';

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var string[] Array of error strings
     */
    public array $errors = [];

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Chase every satisfaction survey that was handed over and never answered
     *
     * Only a survey whose link was actually sent is chased, and only once the configured delay has run
     * out since that last mail. A contact who never answers stops being chased after the configured
     * number of reminders, so an unreachable address is not mailed forever.
     *
     * @return int 0 if OK, < 0 if KO
     * @throws Exception
     */
    public function sendSatisfactionSurveyReminders(): int
    {
        global $conf, $langs, $user;

        $langs->loadLangs(['dolimeet@dolimeet', 'mails']);

        if (!isModEnabled('digiquali') || version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '<')) {
            $this->output = $langs->transnoentities('SurveyReminderDigiQualiRequired');
            return 0;
        }

        // Load Dolibarr libraries
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

        // Load DigiQuali libraries
        require_once __DIR__ . '/../../digiquali/class/survey.class.php';

        $emailFrom = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
        if (!dol_strlen($emailFrom)) {
            $this->error = $langs->transnoentities('ErrorSetupEmail');
            return -1;
        }

        // A delay of zero would mean chasing on every run, it falls back on the default. A maximum of
        // zero means something though: no reminder at all, which is how the feature is turned off
        $delay = getDolGlobalInt('DOLIMEET_SATISFACTION_SURVEY_REMINDER_DELAY') ?: 7;
        $max   = isset($conf->global->DOLIMEET_SATISFACTION_SURVEY_REMINDER_MAX) ? getDolGlobalInt('DOLIMEET_SATISFACTION_SURVEY_REMINDER_MAX') : 3;

        if ($max <= 0) {
            $this->output = $langs->transnoentities('SurveyReminderDisabled');
            return 0;
        }

        // Every role answers its own questionnaire: the sheet a survey was built on names the role it belongs
        // to. The roles are read from the configuration itself, so a role added there is chased too
        $rolesBySheet = [];
        foreach ($conf->global as $constName => $constValue) {
            if (preg_match('/^DOLIMEET_(.+)_SATISFACTION_SURVEY_SHEET$/', $constName, $constMatches) && (int) $constValue > 0) {
                $rolesBySheet[(int) $constValue] = dol_strtolower($constMatches[1]);
            }
        }

        if (empty($rolesBySheet)) {
            $this->output = $langs->transnoentities('SurveyReminderNoSheetConfigured');
            return 0;
        }

        $signatory  = new SaturneSignature($this->db, 'digiquali', 'survey');
        $limitDate  = dol_time_plus_duree(dol_now(), -$delay, 'd');
        $morefilter = "t.module_name = 'digiquali' AND t.object_type = 'survey' AND t.status > 0";
        $morefilter .= ' AND t.status <> ' . SaturneSignature::STATUS_SIGNED;
        $morefilter .= " AND t.last_email_sent_date IS NOT NULL AND t.last_email_sent_date < '" . $this->db->idate($limitDate) . "'";

        $pendingSignatories = $signatory->fetchAll('ASC', 'rowid', 0, 0, ['customsql' => $morefilter]);
        if (!is_array($pendingSignatories) || empty($pendingSignatories)) {
            $this->output = $langs->transnoentities('SurveyReminderNothingToSend');
            return 0;
        }

        $survey       = new Survey($this->db);
        $mailTemplate = new SaturneMail($this->db);
        $contracts    = [];
        $nbSent       = 0;
        $nbSkipped    = 0;

        foreach ($pendingSignatories as $pendingSignatory) {
            if ($survey->fetch($pendingSignatory->fk_object) <= 0) {
                $nbSkipped++;
                continue;
            }

            $surveyRole = $rolesBySheet[$survey->fk_sheet] ?? '';
            if (empty($surveyRole)) {
                $nbSkipped++;
                continue;
            }

            $templateID = getDolGlobalInt('DOLIMEET_' . dol_strtoupper($surveyRole) . '_SATISFACTION_SURVEY_REMINDER_EMAIL_TEMPLATE');
            if ($templateID <= 0 || $mailTemplate->fetch($templateID) <= 0) {
                $nbSkipped++;
                continue;
            }

            // A survey is chased at most $max times: one reminder per delay, and past that many delays
            // since it was created it is left alone rather than mailed forever
            if (dol_time_plus_duree($survey->date_creation, $max * $delay, 'd') < dol_now()) {
                $nbSkipped++;
                continue;
            }

            $sendTo = $this->getSignatoryEmail($pendingSignatory);
            if (!dol_strlen($sendTo)) {
                $nbSkipped++;
                continue;
            }

            $contractID = $this->getSurveyContractID($survey);
            if ($contractID <= 0) {
                $nbSkipped++;
                continue;
            }

            if (!isset($contracts[$contractID])) {
                $contract = new Contrat($this->db);
                if ($contract->fetch($contractID) <= 0) {
                    $nbSkipped++;
                    continue;
                }
                $contract->fetch_optionals();
                $contracts[$contractID] = $contract;
            }

            $result = $this->sendReminder($contracts[$contractID], $survey, $pendingSignatory, $mailTemplate, $sendTo, $emailFrom);
            if ($result < 0) {
                $nbSkipped++;
                continue;
            }

            $nbSent++;
        }

        $this->output = $langs->transnoentities('SurveyReminderResult', $nbSent, $nbSkipped);

        return 0;
    }

    /**
     * Send one reminder and record it on the signatory
     *
     * @param  Contrat          $contract     Contract the survey belongs to
     * @param  Survey           $survey       Survey waiting for an answer
     * @param  SaturneSignature $signatory    Signatory that has to answer
     * @param  SaturneMail      $mailTemplate Mail model configured for the role
     * @param  string           $sendTo       Recipient address
     * @param  string           $emailFrom    Sender address
     * @return int                            < 0 if KO, > 0 if OK
     */
    protected function sendReminder(Contrat $contract, Survey $survey, SaturneSignature $signatory, SaturneMail $mailTemplate, string $sendTo, string $emailFrom): int
    {
        global $conf, $langs, $user;

        $substitutions = getCommonSubstitutionArray($langs, 0, null, $contract);
        $substitutions['__DOLIMEET_SURVEY_REF__']  = $survey->ref;
        $substitutions['__DOLIMEET_SURVEY_LINK__'] = dol_buildpath('custom/digiquali/public/public_answer.php?track_id=' . $survey->track_id . '&object_type=' . $survey->element . '&document_type=SurveyDocument&entity=' . $conf->entity, 3);
        complete_substitutions_array($substitutions, $langs, $contract);

        $subject = make_substitutions($mailTemplate->topic, $substitutions);
        $message = make_substitutions($mailTemplate->content, $substitutions);

        $mailFile = new CMailFile($subject, $sendTo, $emailFrom, $message, [], [], [], '', '', 0, -1, '', '', '', '', 'mail');
        if ($mailFile->error) {
            $this->errors[] = $mailFile->error;
            return -1;
        }

        if (!$mailFile->sendfile()) {
            $this->errors[] = $mailFile->error;
            return -1;
        }

        $signatory->last_email_sent_date = dol_now();
        $signatory->update($user, true);

        return 1;
    }

    /**
     * Read the address a signatory is reachable at, falling back on their user or contact card
     *
     * @param  SaturneSignature $signatory Signatory to reach
     * @return string                      Email address, empty when the signatory has none
     */
    protected function getSignatoryEmail(SaturneSignature $signatory): string
    {
        if (dol_strlen($signatory->email)) {
            return $signatory->email;
        }

        if ($signatory->element_type == 'user') {
            $signatoryUser = new User($this->db);
            if ($signatoryUser->fetch($signatory->element_id) > 0) {
                return $signatoryUser->email ?? '';
            }
        } elseif ($signatory->element_type == 'socpeople') {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

            $signatoryContact = new Contact($this->db);
            if ($signatoryContact->fetch($signatory->element_id) > 0) {
                return $signatoryContact->email ?? '';
            }
        }

        return '';
    }

    /**
     * Read the contract a survey was created for
     *
     * @param  Survey $survey Survey to read
     * @return int            ID of the contract, 0 when the survey is not linked to one
     */
    protected function getSurveyContractID(Survey $survey): int
    {
        $survey->linkedObjectsIds = [];
        $survey->fetchObjectLinked(null, 'contrat', $survey->id, $survey->table_element);

        if (empty($survey->linkedObjectsIds['contrat'])) {
            return 0;
        }

        return (int) reset($survey->linkedObjectsIds['contrat']);
    }
}
