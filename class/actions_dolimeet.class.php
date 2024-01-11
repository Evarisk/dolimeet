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
        if (preg_match('/contacttpl:contactdao/', $parameters['context']) && preg_match('/contractcard/', $parameters['context']) && isModEnabled('digiquali')) {
            global $object;

            // Load Saturne libraries
            require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

            // Load DigiQuali libraries
            require_once __DIR__ . '/../../digiquali/class/control.class.php';

            $control   = new Control($this->db);
            $signatory = new SaturneSignature($db, 'digiquali', $control->element);

            $contacts = array_merge($object->liste_contact(-1, 'internal'), $object->liste_contact(-1));

            $object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);

            if (isset($object->linkedObjectsIds['digiquali_control']) && !empty($object->linkedObjectsIds['digiquali_control'])) {
                $outputLine = [];
                $controlIDs = $object->linkedObjectsIds['digiquali_control'];

                foreach ($controlIDs as $controlID) {
                    foreach ($contacts as $contact) {
                        $outputLine[$contact['rowid']] = '<td class="tdoverflowmax200">';
                        if ($contact['code'] == 'TRAINEE' || $contact['code'] == 'SESSIONTRAINER') {
                            if ($signatory->checkSignatoryHasObject($controlID, $control->table_element, $contact['id'], $contact['source'] == 'internal' ? 'user' : 'socpeople')) {
                                $control->fetch($controlID);
                                $outputLine[$contact['rowid']] .= $control->getNomUrl(1);
                            } else {
                                $outputLine[$contact['rowid']] .= img_picto($langs->trans('Control'), $control->picto, 'class="pictofixedwidth"');
                                $outputLine[$contact['rowid']] .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_satisfaction_survey&contact_code=' . $contact['code'] . '&contact_id=' . $contact['id'] . '&contact_source=' . $contact['source'] . '&token=' . newToken() . '">';
                                $outputLine[$contact['rowid']] .= img_picto($langs->trans('SetSatisfactionSurvey'), 'fontawesome_fa-plus-circle_fas_#444') . '</a>';
                            }
                        }
                        $outputLine[$contact['rowid']] .= '</td>';
                    }
                }
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
        global $conf, $db;

        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'admincompany') {
            if ($action == 'update') {
                dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER', GETPOST('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'), 'chaine', 0, '', $conf->entity);
            }
        }

        if (preg_match('/contractcard/', $parameters['context']) && isModEnabled('digiquali')) {
            if ($action == 'set_satisfaction_survey') {
                require_once __DIR__ . '/../lib/dolimeet_function.lib.php';

                $object->fetch(GETPOST('id'));

                set_satisfaction_survey($object, GETPOST('contact_code'), GETPOST('contact_id'), GETPOST('contact_source'));

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
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
        }
        return 0; // or return 1 to replace standard code.
    }

    /**
     *  Overloading the showFilesList function : replacing the parent's function with the one below.
     *
     * @param  array        $parameters Hook metadatas (context, etc...).
     * @param  CommonObject $object     Current object.
     * @return int                      0 < on error, 0 on success, 1 to replace standard code.
     */
    public function showFilesList(array $parameters, $object): int
    {
        global $conf, $db, $langs;

        if (preg_match('/contractcard/', $parameters['context']) && preg_match('/document/', $_SERVER['PHP_SELF'])) {
            global $dolibarr_main_url_root, $form, $formfile, $maxheightmini;

            if (!is_object($form)) {
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
                $form = new Form($db);
            }
            if (!is_object($formfile)) {
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
                $formfile = new FormFile($db);
            }

            $sortfield    = GETPOSTISSET('sortfield') ? GETPOST('sortfield') : '';
            $sortorder    = GETPOSTISSET('sortoder') ? GETPOST('sortoder') : '';
            $permonobject = empty($conf->global->MAIN_UPLOAD_DOC) ? 0 : 1;

            //change the ref
            $upload_dir        = $conf->dolimeet->multidir_output[$object->entity ?? 1];
            $documentTypeArray = ['trainingsession', 'attendancesheet', 'completioncertificate'];

            require_once __DIR__ . '/session.class.php';
            $session = new Session($db);
            $sessions = $session->fetchAll('', '', 0, 0, ['']);

            // May be needed to add .pdf in filter later
            foreach ($documentTypeArray as $documentTypeName) {
                foreach ($sessions as $session) {
                    $filearray = dol_dir_list($upload_dir . '/' . $documentTypeName . 'document/' . $session->ref, 'files', 0, '', '', 'date', SORT_DESC);
                    $parameters['filearray'] = array_merge($parameters['filearray'], $filearray);
                }
            }

            if (!str_contains($parameters['param'], '&id=') && isset($object->id)) {
                $parameters['param'] .= '&id='.$object->id;
            }
            $relativepathwihtoutslashend = preg_replace('/\/$/', '', $parameters['relativepath']);
            if ($relativepathwihtoutslashend) {
                $parameters['param'] .= '&file='.urlencode($relativepathwihtoutslashend);
            }

            // Show list of existing files
            if ((empty($parameters['useinecm']) || $parameters['useinecm'] == 6) && $parameters['title'] != 'none') {
                print load_fiche_titre($parameters['title'] ?: $langs->trans("AttachedFiles"), '', 'file-upload', 0, '', 'table-list-of-attached-files');
            }
            if (empty($url)) {
                $url = $_SERVER["PHP_SELF"];
            }

            print '<!-- html.formfile::list_of_documents -->'."\n";
            print '<div class="div-table-responsive-no-min">';
            print '<table id="tablelines" class="centpercent liste noborder nobottom">'."\n";

            // Get list of files stored into database for same relative directory
            if ($parameters['relativedir']) {
                if ($sortfield && $sortorder) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
                    $parameters['filearray'] = dol_sort_array($parameters['filearray'], $sortfield, $sortorder);
                }
            }

            print '<tr class="liste_titre nodrag nodrop">';
            //print $url.' sortfield='.$sortfield.' sortorder='.$sortorder;
            print_liste_field_titre('Documents2', $url, "name", "", $parameters['param'], '', $sortfield, $sortorder, 'left ');
            print_liste_field_titre('Size', $url, "size", "", $parameters['param'], '', $sortfield, $sortorder, 'right ');
            print_liste_field_titre('Date', $url, "date", "", $parameters['param'], '', $sortfield, $sortorder, 'center ');
            if (empty($parameters['useinecm']) || $parameters['useinecm'] == 4 || $parameters['useinecm'] == 5 || $parameters['useinecm'] == 6) {
                print_liste_field_titre('', $url, "", "", $parameters['param'], '', $sortfield, $sortorder, 'center '); // Preview
            }
            // Shared or not - Hash of file
            print_liste_field_titre('');
            // Action button
            print_liste_field_titre('');
            if (empty($disablemove) && count($parameters['filearray']) > 1) {
                print_liste_field_titre('');
            }
            print "</tr>\n";

            $nboffiles = count($parameters['filearray']);
            if ($nboffiles > 0) {
                include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
            }

            $i = 0;
            $nboflines = 0;
            $lastrowid = 0;
            foreach ($parameters['filearray'] as $key => $file) {      // filearray must be only files here
                if ($file['name'] != '.' && $file['name'] != '..' && !preg_match('/\.meta$/i', $file['name'])) {
                    if (array_key_exists('rowid', $parameters['filearray'][$key]) && $parameters['filearray'][$key]['rowid'] > 0) {
                        $lastrowid = $parameters['filearray'][$key]['rowid'];
                    }
                    $filepath = $parameters['relativepath'].$file['name'];
                    $nboflines++;
                    print '<!-- Line list_of_documents '.$key.' relativepath = '.$parameters['relativepath'].' -->'."\n";
                    // Do we have entry into database ?
                    print '<!-- In database: position='.(array_key_exists('position', $parameters['filearray'][$key]) ? $parameters['filearray'][$key]['position'] : 0).' -->'."\n";
                    print '<tr class="oddeven" id="row-'.((array_key_exists('rowid', $parameters['filearray'][$key]) && $parameters['filearray'][$key]['rowid'] > 0) ? $parameters['filearray'][$key]['rowid'] : 'AFTER'.$lastrowid.'POS'.($i + 1)).'">';

                    // File name
                    print '<td class="minwith200 tdoverflowmax500">';

                    // Show file name with link to download
                    //print "XX".$file['name'];	//$file['name'] must be utf8
                    print '<a class="paddingright valignmiddle" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$parameters['modulepart'];
                    if ($parameters['forcedownload']) {
                        print '&attachment=1';
                    }
                    if (!empty($object->entity)) {
                        print '&entity='.$object->entity;
                    }
                    print '&file='.urlencode($filepath).'">';
                    print img_mime($file['name'], $file['name'].' ('.dol_print_size($file['size'], 0, 0).')', 'inline-block valignmiddle paddingright');
                    $filenametoshow = preg_replace('/\.noexe$/', '', $file['name']);
                    print dol_escape_htmltag(dol_trunc($filenametoshow, 200));
                    print '</a>';

                    // Preview link
                    print $formfile->showPreview($file, $parameters['modulepart'], $filepath, 0, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));
                    print "</td>\n";

                    // Size
                    $sizetoshow = dol_print_size($file['size'], 1, 1);
                    $sizetoshowbytes = dol_print_size($file['size'], 0, 1);
                    print '<td class="right nowraponall">';
                    if ($sizetoshow == $sizetoshowbytes) {
                        print $sizetoshow;
                    } else {
                        print $form->textwithpicto($sizetoshow, $sizetoshowbytes, -1);
                    }
                    print '</td>';

                    // Date
                    print '<td class="center nowraponall">'.dol_print_date($file['date'], "dayhour", "tzuser").'</td>';

                    // Preview
                    if (empty($parameters['useinecm']) || $parameters['useinecm'] == 4 || $parameters['useinecm'] == 5 || $parameters['useinecm'] == 6) {
                        $fileinfo = pathinfo($file['name']);
                        print '<td class="center">';
                        if (image_format_supported($file['name']) >= 0) {
                            if ($parameters['useinecm'] == 5 || $parameters['useinecm'] == 6) {
                                $smallfile = getImageFileNameForSize($file['name'], ''); // There is no thumb for ECM module and Media filemanager, so we use true image. TODO Change this it is slow on image dir.
                            } else {
                                $smallfile = getImageFileNameForSize($file['name'], '_small'); // For new thumbs using same ext (in lower case however) than original
                            }
                            if (!dol_is_file($file['path'].'/'.$smallfile)) {
                                $smallfile = getImageFileNameForSize($file['name'], '_small', '.png'); // For backward compatibility of old thumbs that were created with filename in lower case and with .png extension
                            }
                            //print $file['path'].'/'.$smallfile.'<br>';

                            $urlforhref = getAdvancedPreviewUrl($parameters['modulepart'], $parameters['relativepath'].$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));
                            if (empty($urlforhref)) {
                                $urlforhref = DOL_URL_ROOT.'/viewimage.php?modulepart='.$parameters['modulepart'].'&entity='.(!empty($object->entity) ? $object->entity : $conf->entity).'&file='.urlencode($parameters['relativepath'].$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
                                print '<a href="'.$urlforhref.'" class="aphoto" target="_blank" rel="noopener noreferrer">';
                            } else {
                                print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
                            }
                            print '<img class="photo maxwidth200 shadow valignmiddle" height="'.(($parameters['useinecm'] == 4 || $parameters['useinecm'] == 5 || $parameters['useinecm'] == 6) ? '20' : $maxheightmini).'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$parameters['modulepart'].'&entity='.(!empty($object->entity) ? $object->entity : $conf->entity).'&file='.urlencode($parameters['relativepath'].$smallfile).'" title="">';
                            print '</a>';
                        } else {
                            print '&nbsp;';
                        }
                        print '</td>';
                    }

                    // Shared or not - Hash of file
                    print '<td class="center">';
                    if ($parameters['relativedir'] && $parameters['filearray'][$key]['rowid'] > 0) {	// only if we are in a mode where a scan of dir were done and we have id of file in ECM table
                        if ($file['share']) {
                            // Define $urlwithroot
                            $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
                            $urlwithroot       = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
                            $paramlink         = '';
                            if (!empty($file['share'])) {
                                $paramlink .= ($paramlink ? '&' : '').'hashp='.$file['share']; // Hash for public share
                            }

                            $fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');

                            print '<a href="'.$fulllink.'" target="_blank" rel="noopener">'.img_picto($langs->trans("FileSharedViaALink"), 'globe').'</a> ';
                            print '<input type="text" class="quatrevingtpercent minwidth200imp nopadding small" id="downloadlink'.$parameters['filearray'][$key]['rowid'].'" name="downloadexternallink" title="'.dol_escape_htmltag($langs->trans("FileSharedViaALink")).'" value="'.dol_escape_htmltag($fulllink).'">';
                        }
                    }
                    print '</td>';

                    // Delete or view link
                    // ($param must start with &)
                    print '<td class="valignmiddle right actionbuttons nowraponall"><!-- action on files -->';
                    if ($parameters['useinecm'] == 1 || $parameters['useinecm'] == 5) {	// ECM manual tree only
                        // $section is inside $param
                        $newparam = preg_replace('/&file=.*$/', '', $parameters['param']); // We don't need param file=
                        $backtopage = DOL_URL_ROOT.'/ecm/index.php?&section_dir='.urlencode($parameters['relativepath']).$newparam;
                        print '<a class="editfielda editfilelink" href="'.DOL_URL_ROOT.'/ecm/file_card.php?urlfile='.urlencode($file['name']).$parameters['param'].'&backtopage='.urlencode($backtopage).'" rel="'.urlencode($file['name']).'">'.img_edit('default', 0, 'class="paddingrightonly"').'</a>';
                    }

                    // Output link to delete file
                    if ($permonobject) {
                        $useajax = 1;
                        if (!empty($conf->dol_use_jmobile)) {
                            $useajax = 0;
                        }
                        if (empty($conf->use_javascript_ajax)) {
                            $useajax = 0;
                        }
                        if (!empty($conf->global->MAIN_ECM_DISABLE_JS)) {
                            $useajax = 0;
                        }
                        print '<a href="'.((($parameters['useinecm'] && $parameters['useinecm'] != 6) && $useajax) ? '#' : ($url.'?action=deletefile&token='.newToken().'&urlfile='.urlencode($filepath).$parameters['param'])).'" class="reposition deletefilelink" rel="'.$filepath.'">'.img_delete().'</a>';
                    }
                    print "</td>";

                    if (empty($disablemove) && count($parameters['filearray']) > 1) {
                        if ($nboffiles > 1 && $conf->browser->layout != 'phone') {
                            print '<td class="linecolmove tdlineupdown center">';
                            if ($i > 0) {
                                print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=up&rowid='.$object->id.'">'.img_up('default', 0, 'imgupforline').'</a>';
                            }
                            if ($i < ($nboffiles - 1)) {
                                print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=down&rowid='.$object->id.'">'.img_down('default', 0, 'imgdownforline').'</a>';
                            }
                            print '</td>';
                        } else {
                            print '<td'.(($conf->browser->layout != 'phone') ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'>';
                            print '</td>';
                        }
                    }
                    print "</tr>\n";

                    $i++;
                }
            }

            if ($nboffiles == 0) {
                $colspan = '6';
                if (empty($disablemove) && count($parameters['filearray']) > 1) {
                    $colspan++; // 6 columns or 7
                }
                print '<tr class="oddeven"><td colspan="'.$colspan.'">';
                if (empty($parameters['textifempty'])) {
                    print '<span class="opacitymedium">'.$langs->trans("NoFileFound").'</span>';
                } else {
                    print '<span class="opacitymedium">'.$parameters['textifempty'].'</span>';
                }
                print '</td></tr>';
            }

            print "</table>";
            print '</div>';

            print ajax_autoselect('downloadlink');

            return $nboffiles ?? 1;
        }

        return 0; // or return 1 to replace standard code.
    }
}
