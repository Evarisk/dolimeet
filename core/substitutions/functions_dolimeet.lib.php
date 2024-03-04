<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file    core/substitutions/functions_dolimeet.lib.php
 * \ingroup functions_dolimeet
 * \brief   File of functions to substitutions array
 */

/** Function called to complete substitution array (before generating on ODT, or a personalized email)
 * functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * is inside directory htdocs/core/substitutions
 *
 * @param  array              $substitutionarray Array with substitution key => val
 * @param  Translate          $langs             Output langs
 * @param  Object|string|null $object            Object to use to get values
 * @return void                                  The entry parameter $substitutionarray is modified
 * @throws Exception
 */
function dolimeet_completesubstitutionarray(array &$substitutionarray, Translate $langs, $object)
{
    global $conf, $db, $user;

    if ($object->element == 'contrat') {
        // Load Saturne libraries
        require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';
        require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';

        // Load DoliMeet libraries
        require_once __DIR__ . '/../../class/session.class.php';

        saturne_load_langs();

        $session   = new Session($db);
        $signatory = new SaturneSignature($db, 'dolimeet', 'trainingsession');

        $sessions = $session->fetchAll('ASC', 'date_start', 0, 0, ['customsql' => 't.fk_contrat = ' . $object->id . " AND t.type = 'trainingsession'"]);
        if (is_array($sessions) && !empty($sessions)) {
            foreach ($sessions as $session) {
                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<strong>' . $session->ref . ' - ' . $session->label . '</strong>';
                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<ul><li>' . $langs->transnoentities('DateAndTime') . ' : ' . dol_strtolower($langs->transnoentities('From')) . ' ' . dol_print_date($session->date_start, 'day', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('At')) . ' ' . dol_print_date($session->date_start, 'hour', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('To')) . ' ' . dol_print_date($session->date_end, 'day', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('At')) . ' ' . dol_print_date($session->date_end, 'hour', 'tzuserrel') . ' (' . dol_strtolower($langs->transnoentities('Duration')) . ' : ' . (($session->duration > 0) ? convertSecondToTime($session->duration, 'allhourmin') : '00:00') . ')' . '</li>';
                $signatoriesByRole = $signatory->fetchSignatory('', $session->id, $session->type);
                if (is_array($signatoriesByRole) && !empty($signatoriesByRole)) {
                    foreach ($signatoriesByRole as $signatoryRole => $signatories) {
                        if (is_array($signatories) && !empty($signatories)) {
                            $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<li>' . $langs->transnoentities($signatoryRole) . '(s) :';
                            foreach ($signatories as $signatory) {
                                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<ul><li>' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname . ' - <strong>' . $signatory->getLibStatut(5) . (($signatory->status == SaturneSignature::STATUS_REGISTERED) ? ' - ' . $langs->transnoentities('PendingSignature') : '') . '</strong>';
                                if ($signatoryRole != 'SessionTrainer') {
                                    $signatureUrl = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url . '&entity=' . $conf->entity . '&module_name=dolimeet&object_type=' . $session->type . '&document_type=AttendanceSheetDocument', 3);
                                    $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= ' - <a href=' . $signatureUrl . ' target="_blank">' . $langs->transnoentities('SignAttendanceSheetOnline') . '</a>';
                                }
                                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '</li></ul>';
                            }
                            $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '</li>';
                        }
                    }
                }
                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '</ul>';
            }
        }

        $substitutionarray['__DOLIMEET_CONTRACT_LABEL__']                    = $object->array_options['options_label'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_START__']    = $object->array_options['options_trainingsession_start'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_END__']      = $object->array_options['options_trainingsession_end'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_TYPE__']     = $object->array_options['options_trainingsession_type'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_LOCATION__'] = $object->array_options['options_trainingsession_location'];

        if (isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
            // Load DigiQuali libraries
            require_once __DIR__ . '/../../../digiquali/class/survey.class.php';

            $survey = new Survey($db);

            $confEmailTemplateSatisfactionSurvey = json_decode(getDolGlobalString('DOLIMEET_EMAIL_TEMPLATE_SATISFACTION_SURVEY'), true);
            $emailModelSelected = GETPOST('modelmailselected', 'int');
            $key                = array_search($emailModelSelected, $confEmailTemplateSatisfactionSurvey);

            if ($key !== false) {
                $contacts = array_merge($object->liste_contact(-1, 'internal', 0, dol_strtoupper($key)), $object->liste_contact(-1, 'external', 0, dol_strtoupper($key)));
            }

            $object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
            if (!empty($contacts)) {
                foreach ($contacts as $contact) {
                    if (isset($object->linkedObjectsIds['digiquali_survey']) && !empty($object->linkedObjectsIds['digiquali_survey'])) {
                        $surveyIDs = $object->linkedObjectsIds['digiquali_survey'];
                        arsort($surveyIDs);
                        foreach ($surveyIDs as $surveyID) {
                            $confName = 'DOLIMEET_' . $contact['code'] . '_SATISFACTION_SURVEY_SHEET';
                            $filter   = ' AND e.fk_sheet = ' . $conf->global->$confName;
                            if (getDolGlobalInt($confName) > 0) {
                                if ($signatory->checkSignatoryHasObject($surveyID, $survey->table_element, $contact['id'], $contact['source'] == 'internal' ? 'user' : 'socpeople', $filter)) {
                                    $survey->fetch($surveyID);
                                    $signatory->fetch($signatory->id);
                                    $substitutionarray['__DOLIMEET_CONTRACT_SURVEY_INFOS__'] .= '<br><strong>' . dol_strtoupper($signatory->lastname) . ' ' . ucfirst($signatory->firstname) . '</strong>';
                                    if (is_array($sessions) && !empty($sessions)) {
                                        foreach ($sessions as $session) {
                                            $substitutionarray['__DOLIMEET_CONTRACT_SURVEY_INFOS__'] .= '<ul><li>' . $session->ref . ' - ' . $session->label . '</li>';
                                            $substitutionarray['__DOLIMEET_CONTRACT_SURVEY_INFOS__'] .= '<li>' . $langs->transnoentities('DateAndTime') . ' : ' . dol_strtolower($langs->transnoentities('From')) . ' ' . dol_print_date($session->date_start, 'day', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('At')) . ' ' . dol_print_date($session->date_start, 'hour', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('To')) . ' ' . dol_print_date($session->date_end, 'day', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('At')) . ' ' . dol_print_date($session->date_end, 'hour', 'tzuserrel') . ' (' . dol_strtolower($langs->transnoentities('Duration')) . ' : ' . (($session->duration > 0) ? convertSecondToTime($session->duration, 'allhourmin') : '00:00') . ')' . '</li></ul>';
                                        }
                                        $publicAnswerUrl = dol_buildpath('custom/digiquali/public/public_answer.php?track_id=' . $survey->track_id . '&object_type=' . $survey->element . '&document_type=SurveyDocument&entity=' . $conf->entity, 3);
                                        $substitutionarray['__DOLIMEET_CONTRACT_SURVEY_INFOS__'] .= '<a href=' . $publicAnswerUrl . ' target="_blank">' . $langs->transnoentities('FillSatisfactionSurvey', dol_strtolower($langs->transnoentities(ucfirst(dol_strtolower($contact['code']))))) . '</a><br>';
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
