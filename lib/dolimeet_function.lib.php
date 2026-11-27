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
 * A contact gets one survey per sheet on the object: when one, not deleted, is already there, nothing is created.
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
    dol_include_once('/digiquali/class/survey.class.php');
    dol_include_once('/digiquali/lib/digiquali_sheet.lib.php');

    // Load Saturne libraries
    dol_include_once('/saturne/class/saturnesignature.class.php');

    $survey        = new Survey($db);
    $signatoryType = $contactSource == 'internal' ? 'user' : 'socpeople';

    $confName                  = 'DOLIMEET_' . dol_strtoupper($contactCode) . '_SATISFACTION_SURVEY_SHEET';
    $satisfactionSurveySheetId = getDolGlobalInt($confName);
    if ($satisfactionSurveySheetId <= 0) {
        setEventMessages('MissingSatisfactionSurveyConfig', [], 'errors');
        return;
    }

    // A contact added again to the contract, a second click on the "+" button or the same sheet set for two roles of
    // the contact made one more survey each time: the survey this contact already has on this sheet is kept
    $object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
    foreach ($object->linkedObjectsIds['digiquali_survey'] ?? [] as $linkedSurveyID) {
        if (dolimeet_survey_has_contact((int) $linkedSurveyID, $satisfactionSurveySheetId, $contactID, $signatoryType)) {
            return;
        }
    }

    $survey->ref          = $survey->getNextNumRef();
    $survey->fk_sheet     = $satisfactionSurveySheetId;
    $_POST['fk_contract'] = $object->id;

    $surveyID = $survey->create($user);

    if ($surveyID > 0) {
        $signatory = new SaturneSignature($db, 'digiquali', $survey->element);
        $signatory->setSignatory($surveyID, $survey->element, $signatoryType, [$contactID], 'Attendant', 1);
    }
}

/**
 * Is this survey, built from this sheet and not deleted, the one of this contact?
 *
 * The signatories of the surveys made before DigiQuali 23.1.0 keep the DoliSMQ role ExtSocietyAttendant until the update
 * of DigiQuali converts it to Attendant: both roles count, otherwise the survey of the contact is not seen and one more is
 * made.
 *
 * @param  int    $surveyID      Survey ID
 * @param  int    $sheetID       Sheet the survey must be built from
 * @param  int    $contactID     Contact ID : user or socpeople
 * @param  string $signatoryType Signatory type : user or socpeople
 * @return bool                  True if the contact attends this survey
 * @throws Exception
 */
function dolimeet_survey_has_contact(int $surveyID, int $sheetID, int $contactID, string $signatoryType): bool
{
    global $db;

    static $signatory = null;

    // Load Saturne libraries
    dol_include_once('/saturne/class/saturnesignature.class.php');

    if ($signatory === null) {
        $signatory = new SaturneSignature($db, 'digiquali', 'survey');
    }

    // Not deleted: the deleted status of a DigiQuali survey (Survey::STATUS_DELETED) is -1
    $filter = ' AND e.fk_sheet = ' . $sheetID . ' AND e.status >= 0';
    foreach (['Attendant', 'ExtSocietyAttendant'] as $role) {
        if ($signatory->checkSignatoryHasObject($surveyID, 'digiquali_survey', $contactID, $signatoryType, $filter, $role) > 0) {
            return true;
        }
    }

    return false;
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
 * @param CommonObject $object           Object
 * @param Propal|null  $propal           Propal object (optional)
 * @param string       $triggerKey       Trigger key the note is rebuilt from
 * @param int          $excludeContactId Trainee contact id to exclude (e.g. on contact unlink, fired before the link is removed)
 *
 * @throws Exception
 */
function set_public_note(CommonObject $object, ?Propal $propal = null, $triggerKey = '', $excludeContactId = 0)
{
    global $conf, $db, $langs;

    require_once __DIR__ . '/../class/trainingsession.class.php';
    require_once __DIR__ . '/../lib/dolimeet_trainingsession.lib.php';

    $trainingSession = new Trainingsession($db);

    $productIds = trainingsession_function_lib1();
    if (!is_array($productIds) || empty($productIds)) {
        setEventMessages($langs->transnoentities('ErrorNoFormationServiceFound', dol_buildpath('/dolimeet/admin/setup.php', 1) . '#formation'), [], 'errors');
        return -1;
    }

    // On contract creation the formation lines come from the linked proposal: Dolibarr copies the proposal lines
    // onto the contract only AFTER the CONTRACT_CREATE trigger, so the contract has no line of its own yet.
    if ($triggerKey == 'CONTRACT_CREATE' && $propal !== null) {
        $lines = $propal->lines;
    } else {
        $object->fetch_lines();
        $lines = $object->lines;
    }
    if (!is_array($lines) || empty($lines)) {
        setEventMessages($langs->transnoentities('ErrorFormationNoteNoLine', $object->ref), [], 'errors');
        return -1;
    }

    $formationTitle  = '';
    $nbTrainees      = 0;
    $durations       = 0;
    $publicNotePart2 = '';

    if ($object->element === 'contrat') {
        // A contract owns its own (already cloned) sessions, all linked by fk_contrat. They must be fetched once,
        // independently of the number of formation lines, otherwise the same sessions are summed several times
        // (inflated durations and duplicated sessions in the note).
        foreach ($lines as $line) {
            if (in_array($line->fk_product, array_keys($productIds))) {
                $formationTitle .= dol_strlen($line->product_label) > 0 ? $line->product_label : $line->label;
            }
        }

        $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.fk_contrat = ' . (int) $object->id]);
        if (is_array($trainingSessions) && !empty($trainingSessions)) {
            $nbTrainees = count($trainingSessions);
            foreach ($trainingSessions as $contractTrainingSession) {
                $durations       += $contractTrainingSession->duration;
                $publicNotePart2 .= dol_print_date($contractTrainingSession->date_start, 'day', 'tzuserrel') . ' - <strong>' . $langs->transnoentities('Validated') . '</strong>' . ' - ' . $contractTrainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($contractTrainingSession->date_start, 'hour', 'tzuserrel') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($contractTrainingSession->date_end, 'hour', 'tzuserrel') . '</strong><br />';
            }
        }
    } else {
        foreach ($lines as $line) {
            if (!in_array($line->fk_product, array_keys($productIds))) {
                continue;
            }

            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.model = 1 AND t.element_type = \'service\' AND t.fk_element = ' . (int) $line->fk_product]);
            if (!is_array($trainingSessions) || empty($trainingSessions)) {
                continue;
            }

            $formationTitle .= dol_strlen($line->label) > 0 ? $line->label : $line->product_label;

            $nbTrainees += count($trainingSessions);
            foreach ($trainingSessions as $modelTrainingSession) {
                $durations       += $modelTrainingSession->duration;
                $publicNotePart2 .= 'JJ/MM/AAAA - <strong>' . $langs->transnoentities('ToBePlanned') . '</strong>' . ' - ' . $modelTrainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($modelTrainingSession->date_start, 'hour', 'tzuserrel') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($modelTrainingSession->date_end, 'hour', 'tzuserrel') . '</strong><br />';
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
    $contacts        = array_merge(
        (is_array($internalTrainee) ? $internalTrainee : []),
        (is_array($externalTrainee) ? $externalTrainee : [])
    );
    // On contact unlink the trigger fires before the link is removed: exclude the trainee being removed
    if ($excludeContactId > 0) {
        $contacts = array_filter($contacts, function ($contact) use ($excludeContactId) {
            return (int) $contact['id'] !== (int) $excludeContactId;
        });
    }
    if (!empty($contacts)) {
        $object->note_public .= '<br />' . $langs->transnoentities('PublicNoteTraineeList') . '<br />';
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

/**
 * Secret key of a DoliMeet public link
 *
 * Keyed hash (HMAC-SHA256) of the purpose of the link, the entity and the ID of the object, with the unique ID of the
 * instance as key: it cannot be worked out from the object ID, needs no storage, and each generation of the link
 * (button, email template substitution) gives the same one
 *
 * @param  string $purpose  Purpose of the link, so that the key of one public page does not open another one
 * @param  int    $objectId Object ID
 * @param  int    $entity   Entity of the object
 * @return string           Key, empty when the instance has no unique ID to derive it from
 */
function dolimeet_get_public_link_key(string $purpose, int $objectId, int $entity): string
{
    global $conf;

    $instanceKey = (string) ($conf->file->instance_unique_id ?? '');
    if ($objectId <= 0 || $entity <= 0 || $instanceKey === '') {
        return '';
    }

    return hash_hmac('sha256', 'dolimeet|' . $purpose . '|' . $entity . '|' . $objectId, $instanceKey);
}

/**
 * Whether the key given to a DoliMeet public page is the one of its object
 *
 * @param  string $purpose  Purpose of the link, as given to dolimeet_get_public_link_key()
 * @param  int    $objectId Object ID
 * @param  int    $entity   Entity of the object
 * @param  string $key      Key received by the public page
 * @return bool             True if the key opens this object
 */
function dolimeet_check_public_link_key(string $purpose, int $objectId, int $entity, string $key): bool
{
    $expectedKey = dolimeet_get_public_link_key($purpose, $objectId, $entity);

    return $expectedKey !== '' && hash_equals($expectedKey, $key);
}

/**
 * URL of the public page where the customer adds the trainees of a training contract
 *
 * @param  CommonObject $contract Training contract, fetched
 * @return string                 URL, empty when no key can be made
 */
function dolimeet_get_public_contact_url(CommonObject $contract): string
{
    global $conf;

    $entity = (int) (!empty($contract->entity) ? $contract->entity : $conf->entity);
    $key    = dolimeet_get_public_link_key('contact', (int) $contract->id, $entity);
    if ($key === '') {
        return '';
    }

    return dol_buildpath('/dolimeet/public/contact/add_contact.php', 3) . '?id=' . ((int) $contract->id) . '&entity=' . $entity . '&securekey=' . $key;
}

/**
 * Reference of a training contract already created from one of these proposals, other than the given contract
 *
 * A training contract is the one with a training session type (extrafield trainingsession_type). Its link to the
 * proposal is the one Contrat::create() adds from the origin: source propal, target contrat.
 *
 * @param  int|int[] $propalIds  Proposal ID(s) the contract is created from
 * @param  int       $contractId Contract being created, ignored
 * @return string                Reference of the first training contract found, empty when there is none
 */
function dolimeet_get_training_contract_from_propal($propalIds, int $contractId): string
{
    global $db;

    $propalIds = array_filter(array_map('intval', (array) $propalIds));
    if (empty($propalIds)) {
        return '';
    }

    $sql  = 'SELECT c.ref FROM ' . MAIN_DB_PREFIX . 'element_element AS ee';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'contrat AS c ON c.rowid = ee.fk_target';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'contrat_extrafields AS cef ON cef.fk_object = c.rowid';
    $sql .= " WHERE ee.sourcetype = 'propal' AND ee.targettype = 'contrat'";
    $sql .= ' AND ee.fk_source IN (' . $db->sanitize(implode(',', $propalIds)) . ')';
    $sql .= ' AND c.rowid <> ' . $contractId;
    $sql .= ' AND cef.trainingsession_type > 0';
    $sql .= ' ORDER BY c.rowid ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ' ' . $db->lasterror(), LOG_ERR);
        return '';
    }
    $obj = $db->fetch_object($resql);

    return $obj ? (string) $obj->ref : '';
}
