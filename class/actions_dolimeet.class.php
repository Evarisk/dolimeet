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
 * \file    class/actions_dolimeet.class.php
 * \ingroup dolimeet
 * \brief   DoliMeet hook overload.
 */

/**
 * Class ActionsDolimeet.
 */
class ActionsDolimeet
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message).
     */
    public string $error = '';

    /**
     * @var array Errors.
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse.
     */
    public array $results = [];

    /**
     * @var string String displayed by executeHook() immediately after return.
     */
    public string $resprints;

    /**
     * Constructor
     *
     *  @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the constructCategory function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function constructCategory(array $parameters): int
    {
        // Do something only for the current context.
        if (in_array($parameters['currentcontext'], ['category', 'sessioncard'])) {
            $tags = [
                'meeting'       => [
                    'id'        => 436304001,
                    'code'      => 'meeting',
                    'obj_class' => 'Meeting',
                    'obj_table' => 'dolimeet_session'
                ],
                'trainingsession' => [
                    'id'          => 436304002,
                    'code'        => 'trainingsession',
                    'obj_class'   => 'Trainingsession',
                    'obj_table'   => 'dolimeet_session'
                ],
                'audit'         => [
                    'id'        => 436304003,
                    'code'      => 'audit',
                    'obj_class' => 'Audit',
                    'obj_table' => 'dolimeet_session'
                ],
                'session'       => [
                    'id'        => 436304004,
                    'code'      => 'session',
                    'obj_class' => 'Session',
                    'obj_table' => 'dolimeet_session'
                ],
            ];
            $this->results = $tags;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the printCommonFooter function : replacing the parent's function with the one below.
     *
     * @param  array     $parameters Hook metadatas (context, etc...).
     * @return int                   0 < on error, 0 on success, 1 to replace standard code.
     * @throws Exception
     */
    public function printCommonFooter(array $parameters): int
    {
        global $conf, $db, $form, $langs ,$user;

        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'projectOverview') {
            require_once __DIR__ . '/../../saturne/lib/saturne_functions.lib.php';
            require_once __DIR__ . '/session.class.php';

            $pictoPath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
            $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');

            $session = new Session($db, 'session');
            $project = new Project($db);

            $project->fetch(GETPOST('id'), GETPOST('ref'));
            $linkedSessions = $session->fetchAll('','',0,0, ['fk_project' => $project->id]);

            saturne_load_langs();

            $outputLine = '<table><tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">' . $picto . $langs->transnoentities('SessionListOnProject') . '</div></td></tr></table>';
            $outputLine .= '<table><div class="div-table-responsive-no-min"><table class="liste formdoc noborder centpercent"><tbody>';
            $outputLine .= '<tr class="liste_titre">';
            $outputLine .= '<td class="float">' . $langs->transnoentities('Type') . '</td>';
            $outputLine .= '<td class="float">' . $langs->transnoentities('Ref') . '</td>';
            $outputLine .= '<td class="float">' . $langs->transnoentities('Date') . '</td>';
            $outputLine .= '<td class="float">' . $langs->transnoentities('Status') . '</td>';
            $outputLine .= '</tr>';

            if (is_array($linkedSessions) && !empty($linkedSessions)) {
                foreach($linkedSessions as $linkedSession) {
                    switch ($linkedSession->type) {
                        case 'trainingsession':
                            $linkedSession->picto = 'fontawesome_fa-people-arrows_fas_#d35968';
                            break;
                        case 'meeting':
                            $linkedSession->picto = 'fontawesome_fa-comments_fas_#d35968';
                            break;
                        case 'audit':
                            $linkedSession->picto = 'fontawesome_fa-tasks_fas_#d35968';
                            break;
                        default :
                            $linkedSession->picto = 'dolimeet_color@dolimeet';
                            break;
                    }

                    $outputLine .= '<tr><td>';
                    $outputLine .= $langs->trans(ucfirst($linkedSession->type));
                    $outputLine .= '</td><td>';
                    $outputLine .= $linkedSession->getNomUrl(1);
                    $outputLine .= '</td><td>';
                    $outputLine .= dol_print_date($linkedSession->date_start, 'dayhour') . ' - ' . dol_print_date($linkedSession->date_end, 'dayhour');
                    $outputLine .= '</td><td>';
                    $outputLine .= $linkedSession->getLibStatut(5);
                    $outputLine .= '</td></tr>';
                }
            } else {
                $outputLine .= '<tr><td colspan="4">';
                $outputLine .= '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
                $outputLine .= '</td></tr>';
            }
            $outputLine .= '</tbody></table></div>';
            ?>
            <script>
                jQuery('.fiche').append(<?php echo json_encode($outputLine); ?>)
            </script>
            <?php
        }

        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'admincompany') {
            $form      = new Form($db);
            $pictoPath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
            $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
            $trainingOrganizationNumberInput = '<input name="MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER" id="MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER" value="'. $conf->global->MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER .'">';
            ?>
            <script>
                let trainingOrganizationNumberInput = $('<tr class="oddeven"><td><label for="training_organization_number"><?php print $picto . $form->textwithpicto($langs->trans('TrainingOrganizationNumber'), $langs->trans('TrainingOrganizationNumberTooltip'));?></label></td>');
                trainingOrganizationNumberInput.append('<td>' + <?php echo json_encode($trainingOrganizationNumberInput) ; ?> + '</td></tr>');

                let element = $('table:nth-child(1) .oddeven:last-child');
                element.after(trainingOrganizationNumberInput);
            </script>
            <?php
        }

        // Do something only for the current context
        if (preg_match('/contacttpl/', $parameters['context']) && preg_match('/contractcard/', $parameters['context']) && isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
            global $object;

            if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type'])) {
                // Load Saturne libraries
                require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';
                require_once __DIR__ . '/../../saturne/lib/saturne_functions.lib.php';

                // Load DigiQuali libraries
                require_once __DIR__ . '/../../digiquali/class/survey.class.php';

                saturne_load_langs();

                $survey   = new Survey($this->db);
                $signatory = new SaturneSignature($db, 'digiquali', $survey->element);

                $contacts           = array_merge($object->liste_contact(-1, 'internal'), $object->liste_contact(-1));
                $contactsCodeWanted = ['CUSTOMER', 'BILLING', 'TRAINEE', 'SESSIONTRAINER', 'OPCO'];

                $object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);

                if (!empty($contacts)) {
                    $outputLine = [];
                    foreach ($contacts as $contact) {
                        $outputLine[$contact['rowid']] = '<td class="tdoverflowmax200">';
                        if (in_array($contact['code'], $contactsCodeWanted)) {
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
                                            $outputLine[$contact['rowid']] = '<td class="tdoverflowmax200">';
                                            $outputLine[$contact['rowid']] .= $survey->getNomUrl(1) . ' - ' .  $signatory->getLibStatut(3);
                                            $outputLine[$contact['rowid']] .= '</td>';
                                            break;
                                        } else {
                                            $outputLine[$contact['rowid']] = '<td class="tdoverflowmax200">';
                                            $outputLine[$contact['rowid']] .= img_picto($langs->trans('Survey'), $survey->picto, 'class="pictofixedwidth"');
                                            $outputLine[$contact['rowid']] .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_satisfaction_survey&contact_code=' . $contact['code'] . '&contact_id=' . $contact['id'] . '&contact_source=' . $contact['source'] . '&token=' . newToken() . '">';
                                            $outputLine[$contact['rowid']] .= img_picto($langs->trans('SetSatisfactionSurvey'), 'fontawesome_fa-plus-circle_fas_#444') . '</a>';
                                            $outputLine[$contact['rowid']] .= '</td>';
                                        }
                                    } else {
                                        $outputLine[$contact['rowid']] = '<td class="tdoverflowmax200">';
                                        $outputLine[$contact['rowid']] .= '<a href="' . dol_buildpath('/custom/dolimeet/admin/setup.php', 1) . '">';
                                        $outputLine[$contact['rowid']] .= $form->textwithpicto($langs->trans('ClickHere'), $langs->trans('NeedToSetSatisfactionSurvey', $contact['code']), 1, 'warning') . '</a>';
                                        $outputLine[$contact['rowid']] .= '</td>';
                                    }
                                }
                            } else {
                                $outputLine[$contact['rowid']] = '<td class="tdoverflowmax200">';
                                $outputLine[$contact['rowid']] .= img_picto($langs->trans('Survey'), $survey->picto, 'class="pictofixedwidth"');
                                $outputLine[$contact['rowid']] .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_satisfaction_survey&contact_code=' . $contact['code'] . '&contact_id=' . $contact['id'] . '&contact_source=' . $contact['source'] . '&token=' . newToken() . '">';
                                $outputLine[$contact['rowid']] .= img_picto($langs->trans('SetSatisfactionSurvey'), 'fontawesome_fa-plus-circle_fas_#444') . '</a>';
                                $outputLine[$contact['rowid']] .= '</td>';
                            }
                        }
                        $outputLine[$contact['rowid']] .= '</td>';
                    }

                    $outputLineHeader = '<th class="wrapcolumntitle liste_titre" title="' . $langs->transnoentities('SatisfactionSurvey') . '">' . $langs->transnoentities('SatisfactionSurvey') . '</th>';

                    $jsonData = json_encode($outputLine);
                    ?>
                    <script>
                        // Target the second-to-last th element
                        var targetTh = $('table.tagtable th:nth-last-child(2)');
                        targetTh.before(<?php echo json_encode($outputLineHeader); ?>)

                        function fillTable(data) {
                            $('.oddeven').each(function() {
                                var id       = $(this).data('rowid');
                                var targetTd = $(this).find('td:nth-last-child(2)');
                                targetTd.before(data[id]);
                            });
                        }

                        var tableData = <?php echo $jsonData; ?>;
                        fillTable(tableData);
                    </script>
                    <?php
                }
            }
        }

        // Do something only for the current context
        if (preg_match('/categoryindex/', $parameters['context'])) {
            print '<script src="../custom/dolimeet/js/dolimeet.js"></script>';
        } elseif (preg_match('/categorycard/', $parameters['context']) && preg_match('/viewcat.php/', $_SERVER['PHP_SELF'])) {
            require_once __DIR__ . '/../../saturne/lib/object.lib.php';

            $id   = GETPOST('id');
            $type = GETPOST('type');

            if ($type == 'meeting' || $type == 'audit' || $type == 'trainingsession') {
                // Load variable for pagination
                $limit     = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
                $sortfield = GETPOST('sortfield', 'aZ09comma');
                $sortorder = GETPOST('sortorder', 'aZ09comma');
                $page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
                if (empty($page) || $page == -1) {
                    $page = 0;
                }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
                $offset = $limit * $page;

                require_once __DIR__ . '/' . $type . '.class.php';

                $classname = ucfirst($type);
                $object    = new $classname($this->db);

                $sessions      = $object->fetchAll('', '', 0, 0, ['customsql' => 't.type = ' . "'" . $type . "'"]);
                $sessionArrays = [];
                if (is_array($sessions) && !empty($sessions)) {
                    foreach ($sessions as $session) {
                        $sessionArrays[$session->id] = $session->ref;
                    }
                }

                $category = new Categorie($this->db);
                $category->fetch($id);

                $sessionCategories = $category->getObjectsInCateg('session', 0, $limit, $offset);
                $out = '<br>';

                $out .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=' . $type . '">';
                $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
                $out .= '<input type="hidden" name="action" value="addintocategory">';

                $out .= '<table class="noborder centpercent">';
                $out .= '<tr class="liste_titre"><td>';
                $out .= $langs->trans('AddObjectIntoCategory') . ' ';
                $out .= $form::selectarray('element_id', $sessionArrays, '', 1);
                $out .= '<input type="submit" class="button buttongen" value="' . $langs->trans('ClassifyInCategory') . '"></td>';
                $out .= '</tr>';
                $out .= '</table>';
                $out .= '</form>';

                $out .= '<br>';

                $out .= load_fiche_titre($langs->transnoentities($classname), '', 'object_' . $object->picto);
                $out .= '<table class="noborder centpercent">';
                $out .= '<tr class="liste_titre"><td colspan="3">' . $langs->trans('Ref') . '</td></tr>';

                if (is_array($sessionCategories) && !empty($sessionCategories)) {
                    // Form to add record into a category
                    if (count($sessionCategories) > 0) {
                        $i = 0;
                        foreach ($sessionCategories as $session) {
                            $i++;
                            if ($i > $limit) break;

                            $out .= '<tr class="oddeven">';
                            $out .= '<td class="nowrap">';
                            $session->picto   = $object->picto;
                            $session->element = $type;
                            $out .= $session->getNomUrl(1);
                            $out .= '</td>';
                            // Link to delete from category
                            $out .= '<td class="right">';
                            if ($user->rights->categorie->creer) {
                                $out .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=delintocategory&id=' . $id . '&type=' . $type . '&element_id=' . $session->id . '&token=' . newToken() . '">';
                                $out .= $langs->trans('DeleteFromCat');
                                $out .= img_picto($langs->trans('DeleteFromCat'), 'unlink', '', false, 0, 0, '', 'paddingleft');
                                $out .= '</a>';
                            }
                            $out .= '</td>';
                            $out .= '</tr>';
                        }
                    } else {
                        $out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">' . $langs->trans('ThisCategoryHasNoItems') . '</td></tr>';
                    }
                } else {
                    $out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">' . $langs->trans('ThisCategoryHasNoItems') . '</td></tr>';
                }

                $out .= '</table>'; ?>

                <script>
                    jQuery('.fichecenter').last().after(<?php echo json_encode($out); ?>)
                </script>
                <?php
            }
        }

        if (strpos($parameters['context'], 'contractcard') !== false) {
            global $object;

            if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type'])) {
                // Load Saturne libraries
                require_once __DIR__ . '/../../saturne/lib/documents.lib.php';
                require_once __DIR__ . '/../../saturne/lib/saturne_functions.lib.php';
                require_once __DIR__ . '/../../saturne/core/modules/saturne/modules_saturne.php';

                // Load DoliMeet libraries
                require_once __DIR__ . '/session.class.php';

                saturne_load_langs();

                $pictoPath = dol_buildpath('/custom/dolimeet/img/dolimeet_color.png', 1);
                $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');

                // Handle consistency of contract trainingsession dates
                $session = new Session($this->db);

                $filter = ' AND t.status >= 0 AND t.type = "trainingsession" AND t.fk_contrat = ' . $object->id . ' ORDER BY t.date_start ASC';
                $session->fetch('', '', $filter);

                $out = img_picto('', 'check', 'class="marginleftonly"');
                if ($session->date_start != $object->array_options['options_trainingsession_start']) {
                    $out = $form->textwithpicto('', $langs->trans('TrainingSessionStartErrorMatchingDate', $session->ref), 1, 'warning');
                } ?>
                <script>
                    jQuery('.contrat_extras_trainingsession_start').prepend(<?php echo json_encode($picto); ?>);
                    jQuery('.contrat_extras_trainingsession_start').append(<?php echo json_encode($out); ?>);
                </script>
                <?php

                $filter = ' AND t.status >= 0 AND t.type = "trainingsession" AND t.fk_contrat = ' . $object->id . ' ORDER BY t.date_end DESC';
                $session->fetch('', '', $filter);

                $out = img_picto('', 'check', 'class="marginleftonly"');
                if ($session->date_end != $object->array_options['options_trainingsession_end']) {
                    $out = $form->textwithpicto('', $langs->trans('TrainingSessionEndErrorMatchingDate', $session->ref), 1, 'warning');
                } ?>
                <script>
                    jQuery('.contrat_extras_trainingsession_end').prepend(<?php echo json_encode($picto); ?>);
                    jQuery('.contrat_extras_trainingsession_end').append(<?php echo json_encode($out); ?>);
                </script>
                <?php

                // Handle session durations
                $sessionDurations = 0;
                $filter           = 't.status >= 0 AND t.type = "trainingsession" AND t.fk_contrat = ' . $object->id;
                $sessions         = $session->fetchAll('', '', 0, 0, ['customsql' => $filter]);
                if (is_array($sessions) && !empty($sessions)) {
                    foreach ($sessions as $session) {
                        $sessionDurations += $session->duration;
                    }
                    $out  = '<tr class="trextrafields_collapse_' . $object->id . '"><td class="titlefield">' . $langs->transnoentities('TrainingSessionDurations') . '</td>';
                    $out .= '<td id="' . $object->element . '_extras_trainingsession_durations_' . $object->id . '" class="valuefield ' . $object->element . '_extras_trainingsession_durations">' . $picto . ($sessionDurations > 0 ? convertSecondToTime($sessionDurations, 'allhourmin') : '00:00') . '</td></tr>';
                    ?>
                    <script>
                        jQuery('.contrat_extras_trainingsession_location').closest('.trextrafields_collapse_' + <?php echo $object->id; ?>).after(<?php echo json_encode($out); ?>);
                    </script>
                    <?php
                }

                // Handle picto before extrafields
                ?>
                <script>
                    jQuery('.contrat_extras_label').prepend(<?php echo json_encode($picto); ?>);
                    jQuery('.contrat_extras_trainingsession_type').prepend(<?php echo json_encode($picto); ?>);
                    jQuery('.contrat_extras_trainingsession_location').prepend(<?php echo json_encode($picto); ?>);
                </script>
                <?php

                // Handle saturne_show_documents for completion certificate document generation
                if ($session->id > 0) {
                    print '<link rel="stylesheet" type="text/css" href="../custom/saturne/css/saturne.min.css">';

                    $upload_dir = $conf->dolimeet->multidir_output[$object->entity ?? 1];
                    $objRef     = dol_sanitizeFileName($object->ref);
                    $dirFiles   = 'completioncertificatedocument/' . $objRef;
                    $fileDir    = $upload_dir . '/' . $dirFiles;
                    $urlSource  = $_SERVER['PHP_SELF'] . '?id=' . $object->id;

                    $html = saturne_show_documents('dolimeet:CompletioncertificateDocument', $dirFiles, $fileDir, $urlSource, $user->rights->contrat->creer, $user->rights->contrat->supprimer, '', 1, 0, 0, 0, 0, '', 0, $langs->defaultlang, '', $object, 0, 'remove_file', (($object->statut > Contrat::STATUS_DRAFT && getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE') > 0) ? 1 : 0), $langs->trans('DefineSessionTrainerResponsible') . '<br>' . $langs->trans('ObjectMustBeValidatedToGenerate', ucfirst($langs->transnoentities(ucfirst($object->element))))); ?>

                    <script src="../custom/saturne/js/saturne.min.js"></script>
                    <script>
                        jQuery('.fichehalfleft .div-table-responsive-no-min').first().append(<?php echo json_encode($html); ?>);
                    </script>
                    <?php
                }
            }
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadata (context, etc...)
     * @param  object $object     The object to process
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons(array $parameters, $object): int
    {
        global $langs, $user;

        if (preg_match('/categorycard/', $parameters['context'])) {
            $id        = GETPOST('id');
            $elementId = GETPOST('element_id');
            $type      = GETPOST('type');
            if ($id > 0 && $elementId > 0 && ($user->rights->dolimeet->$type->write)) {
                require_once __DIR__ . '/' . $type . '.class.php';

                $classname = ucfirst($type);
                $session   = new $classname($this->db);

                $session->fetch($elementId);

                if (GETPOST('action') == 'addintocategory') {
                    $result = $object->add_type($session, 'session');
                    if ($result >= 0) {
                        setEventMessages($langs->trans('WasAddedSuccessfully', $session->ref), []);
                    } elseif ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        setEventMessages($langs->trans('ObjectAlreadyLinkedToCategory'), [], 'warnings');
                    } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                } elseif (GETPOST('action') == 'delintocategory') {
                    $result = $object->del_type($session, 'session');
                    if ($result < 0) {
                        dol_print_error('', $object->error);
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the doActions function : replacing the parent's function with the one below.
     *
     * @param  array        $parameters Hook metadatas (context, etc...).
     * @param  CommonObject $object     Current object.
     * @param  string       $action     Current action.
     * @return int                      0 < on error, 0 on success, 1 to replace standard code.
     */
    public function doActions(array $parameters, $object, string $action): int
    {
        global $conf, $langs, $user;

        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'admincompany') {
            if ($action == 'update') {
                dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER', GETPOST('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'), 'chaine', 0, '', $conf->entity);
            }
        }

        if (strpos($parameters['context'], 'contractcard') !== false) {
            if ($action == 'set_satisfaction_survey' && isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
                require_once __DIR__ . '/../lib/dolimeet_function.lib.php';

                $object->fetch(GETPOST('id'));

                set_satisfaction_survey($object, GETPOST('contact_code'), GETPOST('contact_id'), GETPOST('contact_source'));

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                exit;
            }

            if ($action == 'builddoc' && strstr(GETPOST('model'), 'completioncertificatedocument_odt')) {
                require_once __DIR__ . '/dolimeetdocuments/completioncertificatedocument.class.php';

                $document = new CompletioncertificateDocument($this->db);
                $document->element = 'trainingsessiondocument';

                $moduleNameLowerCase = 'dolimeet';
                $permissiontoadd     = $user->rights->dolimeet->trainingsession->write;

                require_once __DIR__ . '/../../saturne/core/tpl/documents/documents_action.tpl.php';
                $action = '';
            }

            if ($action == 'pdfGeneration') {
                $moduleName          = 'DoliMeet';
                $moduleNameLowerCase = strtolower($moduleName);
                $upload_dir          = $conf->dolimeet->multidir_output[$conf->entity ?? 1];

                // Action to generate pdf from odt file
                require_once __DIR__ . '/../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

                $urlToRedirect = $_SERVER['REQUEST_URI'];
                $urlToRedirect = preg_replace('/#pdfGeneration$/', '', $urlToRedirect);
                $urlToRedirect = preg_replace('/action=pdfGeneration&?/', '', $urlToRedirect); // To avoid infinite loop

                header('Location: ' . $urlToRedirect);
                exit;
            }
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the extendSheetLinkableObjectsList function : replacing the parent's function with the one below.
     *
     * @param  array $linkableObjectTypes  Array of linkable objects.
     * @return int                         0 < on error, 0 on success, 1 to replace standard code.
     */
    public function extendSheetLinkableObjectsList(array $linkableObjectTypes): int
    {
        require_once __DIR__ . '/../class/trainingsession.class.php';

        $trainingSession = new Trainingsession($this->db);

        $linkableObjectTypes['dolimeet_trainsess'] = [
            'langs'      => 'Trainingsession',
            'langfile'   => 'dolimeet@dolimeet',
            'picto'      => $trainingSession->picto,
            'className'  => 'Trainingsession',
            'post_name'  => 'fk_trainingsession',
            'link_name'  => 'dolimeet_trainsess',
            'name_field' => 'ref',
            'create_url' => 'custom/dolimeet/view/trainingsession/session_card.php?action=create&object_type=trainingsession',
            'class_path' => 'custom/dolimeet/class/trainingsession.class.php',
            'lib_path'   => 'custom/dolimeet/lib/dolimeet_trainingsession.lib.php',
        ];
        $this->results = $linkableObjectTypes;

        return 1;
    }

    /**
     *  Overloading the moreHtmlStatus function : replacing the parent's function with the one below.
     *
     * @param  array        $parameters Hook metadatas (context, etc...).
     * @param  CommonObject $object     Current object.
     * @return int                      0 < on error, 0 on success, 1 to replace standard code.
     */
    public function moreHtmlStatus(array $parameters, CommonObject $object): int
    {
        global $langs;

        // Do something only for the current context.
        if (strpos($parameters['context'], 'contractcard') !== false) {
            if (isModEnabled('contrat')) {
                $error = 0;
                $attendantInternalSessionTrainerArray = $object->liste_contact(-1, 'internal', 0, 'SESSIONTRAINER');
                $attendantInternalTraineeArray        = $object->liste_contact(-1, 'internal', 0, 'TRAINEE');
                $attendantExternalSessionTrainerArray = $object->liste_contact(-1, 'external', 0, 'SESSIONTRAINER');
                $attendantExternalTraineeArray        = $object->liste_contact(-1, 'external', 0, 'TRAINEE');

                if ((is_array($attendantInternalSessionTrainerArray) && empty($attendantInternalSessionTrainerArray)) && (is_array($attendantExternalSessionTrainerArray) && empty($attendantExternalSessionTrainerArray))) {
                    $error++;
                }
                if ((is_array($attendantInternalTraineeArray) && empty($attendantInternalTraineeArray)) && (is_array($attendantExternalTraineeArray) && empty($attendantExternalTraineeArray))) {
                    $error++;
                }

                if (!empty($object->array_options['options_trainingsession_type']) && $error > 0) {
                    $moreHtmlStatus = '<br><br><div><i class="fas fa-3x fa-exclamation-triangle pictowarning"></i> ' . $langs->trans('DontForgotAddSessionTrainerAndTrainee') . '</div>';
                    $this->resprints = $moreHtmlStatus;
                }
            }
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the completeTabsHead function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function completeTabsHead(array $parameters): int
    {
        global $conf, $db;

        if (preg_match('/main/', $parameters['context'])) {
            $nbSessions = 0;
            require_once __DIR__ . '/session.class.php';
            $session = new Session($db);
            switch ($parameters['object']->element) {
                case 'societe' :
                    $objectElement = 'soc';
                    break;
                case 'contract' :
                    $objectElement = 'contrat';
                    break;
                default :
                    $objectElement = $parameters['object']->element;
                    break;
            }
            $filter  = 't.status >= 0 AND t.fk_' . $objectElement . ' = ' . $parameters['object']->id;
            $filter .= GETPOST('object_type') ? " AND t.type = '" . GETPOST('object_type') . "'" : '';
            $sessions = $session->fetchAll('', '', 0, 0, ['customsql' => $filter]);
            if (is_array($sessions) && !empty($sessions)) {
                $nbSessions = count($sessions);
            }
            if ($nbSessions > 0) {
                if (is_array($parameters['head']) && !empty($parameters['head'])) {
                    foreach ($parameters['head'] as $headKey => $tabsHead) {
                        if (is_array($tabsHead) && !empty($tabsHead)) {
                            if (isset($tabsHead[2]) && $tabsHead[2] === 'sessionList') {
                                $pictoPath = dol_buildpath('/custom/dolimeet/img/dolimeet_color.png', 1);
                                $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
                                $parameters['head'][$headKey][1]  = $conf->browser->layout == 'classic' ? $picto . 'DoliMeet' : $picto;
                                $parameters['head'][$headKey][1] .= '<span class="badge marginleftonlyshort">' . $nbSessions . '</span>';
                            }
                        }
                    }
                }
            }

            $this->results = $parameters;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the saturneBannerTab function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneBannerTab(array $parameters, CommonObject $object): int
    {
        global $db, $langs;

        // Do something only for the current context
        if (preg_match('/sessioncard|saturneglobal/', $parameters['context'])) {
            if (isModEnabled('contrat') && property_exists($object, 'fk_contrat')) {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

                $objectsMetadataContract = saturne_get_objects_metadata('contract');

                $moreParams['bannerElement'] = $objectsMetadataContract['link_name'];
                $moreParams['possibleKeys']  = ['fk_contrat'];
                $moreParams['className']     = $objectsMetadataContract['class_name'];
                $moreParams['title']         = $objectsMetadataContract['langs'];
                $moreParams['picto']         = $objectsMetadataContract['picto'];
                $this->results = ['', $moreParams];
            }
        }
        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the saturneAttendantsBackToCard function : replacing the parent's function with the one below.
     *
     * @param  array        $parameters Hook metadatas (context, etc...).
     * @param  CommonObject $object     Current object.
     * @return int                      0 < on error, 0 on success, 1 to replace standard code.
     */
    public function saturneAttendantsBackToCard(array $parameters, CommonObject $object): int
    {
        global $moduleNameLowerCase;

        // Do something only for the current context.
        if (preg_match('/meetingsignature|trainingsessionsignature|auditsignature/', $parameters['context'])) {
            $this->resprints = dol_buildpath('/custom/' . $moduleNameLowerCase . '/view/session/session_card.php?id=' . $object->id . '&object_type=' . $object->element, 1);
            return 1;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the saturneAdminDocumentData function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function saturneAdminDocumentData(array $parameters): int
    {
        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'dolimeetadmindocuments') {
            $types = [
                'AttendanceSheetDocument' => [
                    'documentType' => 'attendancesheetdocument',
                    'picto'        => 'fontawesome_fa-people-arrows_fas_#d35968'
                ],
                'CompletionCertificateDocument' => [
                    'documentType' => 'completioncertificatedocument',
                    'picto'        => 'fontawesome_fa-people-arrows_fas_#d35968'
                ]
            ];
            $this->results = $types;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the saturneAdminObjectConst function : replacing the parent's function with the one below.
     *
     * @param  array $parameters Hook metadatas (context, etc...).
     * @return int               0 < on error, 0 on success, 1 to replace standard code.
     */
    public function saturneAdminObjectConst(array $parameters): int
    {
        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'dolimeetadmindocuments') {
            $constArray['dolimeet'] = [
                'attendancesheetdocument' => [
                    'name'        => 'DisplayAttendanceAbsentInSignature',
                    'description' => 'DisplayAttendanceAbsentInSignatureDescription',
                    'code'        => 'DOLIMEET_ATTENDANCESHEETDOCUMENT_DISPLAY_ATTENDANCE_ABSENT_IN_SIGNATURE'
                ]
            ];
            $this->results = $constArray;
            return 1;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the saturneBuildDoc function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function saturneBuildDoc(array $parameters, CommonObject $object, string $action): int
    {
        global $conf, $langs;

        // Do something only for the current context
        if (strpos($parameters['context'], 'trainingsessioncard') !== false) {
            if (preg_match('/completioncertificate/', (!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']))) {
                $signatory = new SaturneSignature($this->db, 'dolimeet', $object->element);
                $document  = new SessionDocument($this->db, $object->element . 'document');

                $signatoriesArray = $signatory->fetchSignatories($object->id, $object->type);
                if (is_array($signatoriesArray) && !empty($signatoriesArray)) {
                    foreach ($signatoriesArray as $objectSignatory) {
                        if ($objectSignatory->role == 'Trainee' && $objectSignatory->attendance != $objectSignatory::ATTENDANCE_ABSENT) {
                            $parameters['moreparams']['attendant'] = $objectSignatory;
                            $result = $document->generateDocument((!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']), $parameters['outputlangs'], $parameters['hidedetails'], $parameters['hidedesc'], $parameters['hideref'], $parameters['moreparams']);
                            if ($result <= 0) {
                                setEventMessages($document->error, $document->errors, 'errors');
                                $action = '';
                            }
                        }
                    }
                    $documentType = explode('_odt', (!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']));
                    if ($document->element != $documentType[0]) {
                        $document->element = $documentType[0];
                    }
                    setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart=dolimeet&file=' . urlencode($document->element . '/' . $object->ref . '/' . $document->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $document->last_main_doc, []);
                    $urlToRedirect = $_SERVER['REQUEST_URI'];
                    $urlToRedirect = preg_replace('/#builddoc$/', '', $urlToRedirect);
                    $urlToRedirect = preg_replace('/action=builddoc&?/', '', $urlToRedirect); // To avoid infinite loop
                    if (!GETPOST('forcebuilddoc')){
                        header('Location: ' . $urlToRedirect . '#builddoc');
                        exit;
                    }
                }
            }
        } else if (strpos($parameters['context'], 'contractcard') !== false) {
            if (strpos((!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']), 'completioncertificate') !== false) {
                require_once __DIR__ . '/session.class.php';

                $session   = new Session($this->db);
                $document  = new CompletioncertificateDocument($this->db);
                $signatory = new SaturneSignature($this->db, 'dolimeet', $object->element);

                $duration = 0;
                $sessions = $session->fetchAll('', '', 0, 0, ['customsql' => 't.fk_contrat = ' . $object->id . ' AND t.status >= 0']);
                if (is_array($sessions) && !empty($sessions)) {
                    foreach ($sessions as $session) {
                        $duration += $session->duration;
                    }
                    $lastSession = end($sessions);
                    $dateEnd     = $lastSession->date_end;
                    if ($dateEnd != $object->array_options['options_trainingsession_end']) {
                        setEventMessages($langs->trans('TrainingSessionEndErrorMatchingDate', $lastSession->ref), [], 'warnings');
                    }
                }

                $contactList = [];
                foreach (['internal', 'external'] as $source) {
                    $contactList = array_merge($contactList, $object->liste_contact(-1, $source, 0, 'TRAINEE'));
                }

                $parameters['moreparams']['object']             = $object;
                $parameters['moreparams']['object']->element    = 'trainingsession';
                $parameters['moreparams']['object']->date_start = $object->array_options['options_trainingsession_start'];
                $parameters['moreparams']['object']->date_end   = $object->array_options['options_trainingsession_end'];
                $parameters['moreparams']['object']->duration   = $duration;
                $parameters['moreparams']['object']->fk_contrat = $object->id;

                if (!empty($contactList)) {
                    foreach ($contactList as $contact) {
                        $parameters['moreparams']['attendant']               = $signatory;
                        $parameters['moreparams']['attendant']->firstname    = $contact['firstname'];
                        $parameters['moreparams']['attendant']->lastname     = $contact['lastname'];
                        $parameters['moreparams']['attendant']->element_type = ($contact['source'] == 'external' ? 'socpeople' : 'user');
                        $parameters['moreparams']['attendant']->element_id   = $contact['id'];

                        $document->element = 'trainingsessiondocument';
                        $result = $document->generateDocument((!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']), $parameters['outputlangs'], $parameters['hidedetails'], $parameters['hidedesc'], $parameters['hideref'], $parameters['moreparams']);
                        if ($result <= 0) {
                            setEventMessages($document->error, $document->errors, 'errors');
                        }
                    }

                    $documentType = explode('_odt', (!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']));
                    if ($document->element != $documentType[0]) {
                        $document->element = $documentType[0];
                    }
                    setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart=dolimeet&file=' . urlencode($document->element . '/' . $object->ref . '/' . $document->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $document->last_main_doc, []);
                    $urlToRedirect = $_SERVER['REQUEST_URI'];
                    $urlToRedirect = preg_replace('/#builddoc$/', '', $urlToRedirect);
                    $urlToRedirect = preg_replace('/action=builddoc&?/', '', $urlToRedirect); // To avoid infinite loop
                    if (!GETPOST('forcebuilddoc')){
                        header('Location: ' . $urlToRedirect . '#builddoc');
                        exit;
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code.
    }
}
