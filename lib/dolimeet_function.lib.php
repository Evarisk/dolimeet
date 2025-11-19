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
    global $db, $user;

    // Load DigiQuali libraries
    require_once __DIR__ . '/../../digiquali/class/survey.class.php';
    require_once __DIR__ . '/../../digiquali/lib/digiquali_sheet.lib.php';

    $survey = new Survey($db);

    $confName                  = 'DOLIMEET_' . dol_strtoupper($contactCode) . '_SATISFACTION_SURVEY_SHEET';
    $satisfactionSurveySheetId = getDolGlobalInt($confName);
    if ($satisfactionSurveySheetId <= 0) {
        setEventMessages('MissingSatisfactionSurveyConfig', [], 'errors');
        return;
    }

    $survey->ref          = $survey->getNextNumRef();
    $survey->fk_sheet     = $satisfactionSurveySheetId;
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
            'position' => 1,
            'code'     => 'DOLIMEET_SERVICE_TRAINING_CONTRACT',
            'ref'      => 'FOR_ADM_CF1',
            'name'     => 'TrainingContract'
        ],
        [
            'position' => 30,
            'code'     => 'DOLIMEET_SERVICE_PRACTICAL_GUIDE',
            'ref'      => 'FOR_ADM_GP1',
            'name'     => 'PracticalGuide'
        ],
        [
            'position' => 40,
            'code'     => 'DOLIMEET_SERVICE_WELCOME_BOOKLET',
            'ref'      => 'FOR_ADM_LA1',
            'name'     => 'WelcomeBooklet'
        ],
        [
            'position' => 50,
            'code'     => 'DOLIMEET_SERVICE_RULES_OF_PROCEDURE',
            'ref'      => 'FOR_ADM_RI1',
            'name'     => 'RulesOfProcedure'
        ]
    ];
}

/**
 * Set public note on project/propal/contract
 *
 * @param CommonObject $object  Object
 * @param Propal|null  $propal  Propal object (optional)
 *
 * @throws Exception
 */
function set_public_note(CommonObject $object, Propal $propal = null, $triggerKey = '')
{
    global $conf, $db, $langs;

    require_once __DIR__ . '/../class/trainingsession.class.php';
    require_once __DIR__ . '/../lib/dolimeet_trainingsession.lib.php';

    $trainingSession = new Trainingsession($db);

    $productIds = trainingsession_function_lib1();
    if (!is_array($productIds) || empty($productIds)) {
        setEventMessages($langs->transnoentities('Error3'), [], 'errors');
        return -1;
    }

    $object->fetch_lines();
    if (!is_array($object->lines) || empty($object->lines)) {
        setEventMessages($langs->transnoentities('Error1'), [], 'errors');
        return -1;
    }

    $formationTitle  = '';
    $nbTrainees      = 0;
    $durations       = 0;
    $publicNotePart2 = '';
    if ($triggerKey == 'CONTRACT_CREATE') {
        foreach ($propal->lines as $line) {
            if (!in_array($line->fk_product, array_keys($productIds))) {
                continue;
            }

            if ($object->element === 'contrat') {
                $filter = 't.fk_contrat = ' . $object->id;
            } else {
                $filter = 't.status = 1 AND t.model = 1 AND t.element_type = \'service\' AND t.fk_element = ' . (int) $line->fk_product;
            }

            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => $filter]);
            if (!is_array($trainingSessions) || empty($trainingSessions)) {
                continue;
            }

            $formationTitle .= $line->product_label;

            $nbTrainees += count($trainingSessions);
            foreach ($trainingSessions as $trainingSession) {
                $durations += $trainingSession->duration;
                if ($object->element == 'contrat') {
                    $publicNotePart2Date = dol_print_date($trainingSession->date_start, 'day', 'tzuserrel') . ' - <strong>' . $langs->transnoentities('Validated') . '</strong>';
                } else {
                    $publicNotePart2Date = 'JJ/MM/AAAA - <strong>' . $langs->transnoentities('ToBePlanned') . '</strong>';
                }
                $publicNotePart2 .= $publicNotePart2Date . ' - ' . $trainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($trainingSession->date_start, 'hour', 'tzuserrel') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($trainingSession->date_end, 'hour', 'tzuserrel') . '</strong><br />';
            }
        }
    } else {
        foreach ($object->lines as $line) {
            if (!in_array($line->fk_product, array_keys($productIds))) {
                continue;
            }

            if ($object->element === 'contrat') {
                $filter = 't.fk_contrat = ' . $object->id;
            } else {
                $filter = 't.status = 1 AND t.model = 1 AND t.element_type = \'service\' AND t.fk_element = ' . (int) $line->fk_product;
            }

            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => $filter]);
            if (!is_array($trainingSessions) || empty($trainingSessions)) {
                continue;
            }

            $formationTitle .= $line->label;

            $nbTrainees += count($trainingSessions);
            foreach ($trainingSessions as $trainingSession) {
                $durations += $trainingSession->duration;
                if ($object->element == 'contrat') {
                    $publicNotePart2Date = dol_print_date($trainingSession->date_start, 'day', 'tzuserrel') . ' - <strong>' . $langs->transnoentities('Validated') . '</strong>';
                } else {
                    $publicNotePart2Date = 'JJ/MM/AAAA - <strong>' . $langs->transnoentities('ToBePlanned') . '</strong>';
                }
                $publicNotePart2 .= $publicNotePart2Date . ' - ' . $trainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($trainingSession->date_start, 'hour', 'tzuserrel') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($trainingSession->date_end, 'hour', 'tzuserrel') . '</strong><br />';
            }
        }
    }

    // Part 1 - General information
    $object->note_public  = $langs->transnoentities('FormationInfoTitle') . '<br />';
    $object->note_public .= $langs->transnoentities('FormationTitle') . ' : ' . $formationTitle . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionType') . ' : ' . $langs->transnoentities(getDictionaryValue('c_trainingsession_type', 'ref', $object->array_options['options_trainingsession_type'])) . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionDurations') . ' : <strong>' . convertSecondToTime($durations, 'allhourmin') . '</strong>' . ' ' . dol_strtolower($langs->transnoentities('Hours')) . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionLocation') . ' : ' . (dol_strlen($object->array_options['options_trainingsession_location']) > 0  ? $object->array_options['options_trainingsession_location'] : $langs->transnoentities('NoData')) . '<br />';

    // Part 2 - Training sessions
    $object->note_public .= '<br />' . $langs->transnoentities('TrainingSessionTitle') . '<br />';
    $object->note_public .= $langs->transnoentities('TrainingSessionsInclusiveWriting', $nbTrainees) . ' : ' . '<br />';
    $object->note_public .= $publicNotePart2;

    // Part 3 - Trainee list
    $internalTrainee = $object->liste_contact(-1, 'internal', 0, 'TRAINEE');
    $externalTrainee = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
    if ((is_array($internalTrainee) && !empty($internalTrainee)) || (is_array($externalTrainee) && !empty($externalTrainee))) {
        $object->note_public .= '<br />' . $langs->transnoentities('PublicNoteTraineeList') . '<br />';
        $contacts = array_merge($internalTrainee, $externalTrainee);
        $object->note_public .= $langs->transnoentities('TrainingSessionNbTrainees') . ' : ' . count($contacts) . '<br /><ul>';
        foreach ($contacts as $contact) {
            //@todo option pour le mail
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

    $result_update = $object->update_note(dol_html_entity_decode($object->note_public, ENT_QUOTES | ENT_HTML5, 'UTF-8', 1), '_public');

    if ($result_update < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    } elseif (in_array($object->table_element, array('supplier_proposal', 'propal', 'commande_fournisseur', 'commande', 'facture_fourn', 'facture', 'contrat'))) {
        // Define output language
        if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
            $outputlangs = $langs;
            $newlang = '';
            if (getDolGlobalInt('MAIN_MULTILANGS') /* && empty($newlang) */ && GETPOST('lang_id', 'aZ09')) {
                $newlang = GETPOST('lang_id', 'aZ09');
            }
            if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
                if (!is_object($object->thirdparty)) {
                    $object->fetch_thirdparty();
                }
                $newlang = $object->thirdparty->default_lang;
            }
            if (!empty($newlang)) {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($newlang);
            }
            $model = $object->model_pdf;
            $hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
            $hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
            $hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

            //see #21072: Update a public note with a "document model not found" is not really a problem : the PDF is not created/updated
            //but the note is saved, so just add a notification will be enough

            $resultGenDoc = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);

            if ($resultGenDoc < 0) {
                setEventMessages($object->error, $object->errors, 'warnings');
            }
        }
    }
}

function get_formation_label(CommonObject $object): string
{
    global $langs;

    require_once __DIR__ . '/dolimeet_trainingsession.lib.php';

    $formationLabel = '';

    $productIds = trainingsession_function_lib1();
    if (!is_array($productIds) || empty($productIds)) {
        setEventMessages($langs->transnoentities('Error3'), [], 'errors');
        return -1;
    }

    foreach ($object->lines as $line) {
        if (!in_array($line->fk_product, array_keys($productIds))) {
            continue;
        }

        $formationLabel .= $line->product_label;
    }

    return $formationLabel;
}
