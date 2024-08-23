<?php
/* Copyright (C) 2023-2024 EVARISK <technique@evarisk.com>
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
 * \file    lib/dolimeet_function.lib.php
 * \ingroup dolimeet
 * \brief   Library files with common functions for DoliMeet
 */

/**
 * Set satisfaction survey
 *
 * @param  CommonObject $object        Object
 * @param  string       $contactCode   Contact code from c_type_contact
 * @param  int          $contactID     Contact ID : user or socpeople
 * @param  string       $contactSource Contact source : internal or external
 * @throws Exception
 */
function set_satisfaction_survey(CommonObject $object, string $contactCode, int $contactID, string $contactSource)
{
    global $conf, $db, $user;

    // Load DigiQuali libraries
    require_once __DIR__ . '/../../digiquali/class/survey.class.php';
    require_once __DIR__ . '/../../digiquali/lib/digiquali_sheet.lib.php';

    $survey = new Survey($db);

    $confName             = 'DOLIMEET_' . dol_strtoupper($contactCode) . '_SATISFACTION_SURVEY_SHEET';
    $survey->fk_sheet     = $conf->global->$confName;
    $_POST['fk_contract'] = $object->id;

    $surveyID = $survey->create($user);

    if ($surveyID > 0) {
        // Load Saturne libraries
        require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

        $signatory = new SaturneSignature($db, 'digiquali', $survey->element);
        $signatory->setSignatory($surveyID, $survey->element, $contactSource == 'internal' ? 'user' : 'socpeople', [$contactID], 'Attendant', 1);
    }
}

/**
 * Get all formation service info
 *
 * @return array
 */
function get_formation_service(): array
{
    return [
        [
            'position' => 10,
            'code' => 'DOLIMEET_SERVICE_TRAINING_CONTRACT',
            'ref' => 'F0',
            'name' => 'TrainingContract',
        ],
        [
            'position' => 30,
            'code' => 'DOLIMEET_SERVICE_WELCOME_BOOKLET',
            'ref' => 'LA1',
            'name' => 'WelcomeBooklet',
        ],
        [
            'position' => 40,
            'code' => 'DOLIMEET_SERVICE_RULES_OF_PROCEDURE',
            'ref' => 'RA1',
            'name' => 'RulesOfProcedure',
        ]
    ];
}

/**
 * Set public note on project/propal/contract
 *
 * @param CommonObject $object  Object
 * @param Project|null $project Project object (optional)
 * @param Propal|null  $propal  Propal object (optional)
 *
 * @throws Exception
 */
function set_public_note(CommonObject $object, Project $project = null, Propal $propal = null)
{
    global $db, $langs;

    $durations       = 0;
    $publicNotePart2 = '';
    if (isset($project->array_options['options_trainingsession_service']) && !empty($project->array_options['options_trainingsession_service'])) {
        // Load DoliMeet libraries
        require_once __DIR__ . '/../class/trainingsession.class.php';

        $trainingSession = new Trainingsession($db);

        $project->array_options['options_trainingsession_service'] = explode(',', $project->array_options['options_trainingsession_service']);
        foreach ($project->array_options['options_trainingsession_service'] as $trainingSessionServiceId) {
            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.model = 1 AND t.element_type = "service" AND t.fk_element = ' . $trainingSessionServiceId]);
            if (is_array($trainingSessions) && !empty($trainingSessions)) {
                $publicNotePart2  = $langs->transnoentities('TrainingSessionTitle') . '<br />';
                $publicNotePart2 .= $langs->transnoentities('TrainingSessionsInclusiveWriting', count($trainingSessions)) . ' : ' . '<br />';
                foreach ($trainingSessions as $trainingSession) {
                    $durations += $trainingSession->duration;
                    $publicNotePart2 .= 'JJ/MM/AAAA - <strong>' . $langs->transnoentities('ToBePlanned') . '</strong> - ' . $trainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($trainingSession->date_start, 'hour', 'tzuser') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($trainingSession->date_end, 'hour', 'tzuser') . '</strong><br />';
                }
            }
        }
    }

    // Part 1 - General information
    $object->note_public  = '<br />' . $langs->transnoentities('FormationInfoTitle') . '<br />';
    $object->note_public .= $langs->transnoentities('FormationTitle') . ' : ' . $project->title . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionType') . ' : ' . $langs->transnoentities(getDictionaryValue('c_trainingsession_type', 'ref', $project->array_options['options_trainingsession_type'])) . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionDurations') . ' : <strong>' . convertSecondToTime($durations) . '</strong>' . ' ' . dol_strtolower($langs->transnoentities('Hours')) . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionLocation') . ' : ' . (dol_strlen($project->array_options['options_trainingsession_location']) > 0  ? $project->array_options['options_trainingsession_location'] : $langs->transnoentities('NoData')) . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionNbTrainees') . ' : ' . (dol_strlen($project->array_options['options_trainingsession_nb_trainees']) > 0 ? $project->array_options['options_trainingsession_nb_trainees'] : $langs->transnoentities('NoData')) . '<br /><br />';

    // Part 2 - Training sessions
    $object->note_public .= $publicNotePart2;

    // Part 3 - Trainee list
    $internalTrainee = $object->liste_contact(-1, 'internal', 0, 'TRAINEE');
    $externalTrainee = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
    if ((is_array($internalTrainee) && !empty($internalTrainee)) || (is_array($externalTrainee) && !empty($externalTrainee))) {
        $object->note_public .= '<br />' . $langs->transnoentities('PublicNoteTraineeList') . '<ul>';
        $contacts = array_merge($internalTrainee, $externalTrainee);
        foreach ($contacts as $contact) {
            $object->note_public .= '<li>' . dol_strtoupper($contact['lastname']) . (dol_strlen($contact['firstname']) > 0 ? ', ' . ucfirst($contact['firstname']) : '') . (dol_strlen($contact['email']) > 0 ? ', ' . $contact['email'] : '') . '</li>';
        }
        $object->note_public .= '</ul>';
    } else {
        $object->note_public .= '<br />' . $langs->transnoentities('FormationPublicNoteTraineeList');
    }

    // Part 4 - Proposal
    if ($propal->id > 0) {
        $object->note_public .= '<strong>' . $langs->transnoentities('Proposal') . ' : ' . $propal->ref . '</strong><ul>';
        $object->note_public .= '<li>' . $langs->transnoentities('AmountHT') . ' : ' . price($propal->total_ht, 0, '', 1, -1, -1, 'auto') . '</li>';
        $object->note_public .= '<li>' . $langs->transnoentities('AmountVAT') . ' : ' . price($propal->total_tva, 0, '', 1, -1, -1, 'auto') . '</li>';
        $object->note_public .= '<li>' . $langs->transnoentities('AmountTTC') . ' : ' . price($propal->total_ttc, 0, '', 1, -1, -1, 'auto') . '</li></ul>';
    }

    $object->setValueFrom('note_public', $object->note_public);
}

