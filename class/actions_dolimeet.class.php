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
     * @var string|null String displayed by executeHook() immediately after return.
     */
    public ?string $resprints;

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
        if (preg_match('/category|sessioncard/', $parameters['context'])) {
            $tags = [
                'meeting'       => [
                    'id'        => 436304001,
                    'code'      => 'meeting',
                    'obj_class' => 'Meeting',
                    'obj_table' => 'dolimeet_session',
                    'label'     => 'Meeting'
                ],
                'trainingsession' => [
                    'id'          => 436304002,
                    'code'        => 'trainingsession',
                    'obj_class'   => 'Trainingsession',
                    'obj_table'   => 'dolimeet_session',
                    'label'       => 'TrainingSession'
                ],
                'audit'         => [
                    'id'        => 436304003,
                    'code'      => 'audit',
                    'obj_class' => 'Audit',
                    'obj_table' => 'dolimeet_session',
                    'label'     => 'Audit'
                ],
                'session'       => [
                    'id'        => 436304004,
                    'code'      => 'session',
                    'obj_class' => 'Session',
                    'obj_table' => 'dolimeet_session',
                    'label'     => 'Session'
                ],
            ];
            $this->results = $tags;
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the getElementProperties function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function getElementProperties(array $parameters): int
    {
        if (strpos($parameters['context'], 'category') !== false) {
            $objectElements = ['meeting', 'trainingsession', 'audit', 'session'];
            if (in_array($parameters['elementType'], $objectElements)) {
                $out = [
                    'module'        => 'dolimeet',
                    'table_element' => 'dolimeet_session',
                    'classpath'     => 'custom/dolimeet/class',
                ];
                $this->results = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (preg_match('/productcard|contractcard|contractcontactcard/', $parameters['context'])) {
            $resourcesRequired = [
                'css' => '/custom/saturne/css/saturne.min.css',
                'js'  => '/custom/saturne/js/saturne.min.js'
            ];

            $out  = '<!-- Includes CSS added by module saturne -->';
            $out .= '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($resourcesRequired['css'], 1) . '">';
            $out .= '<!-- Includes JS added by module saturne -->';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['js'], 1) . '"></script>';

            $this->resprints = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadatas (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function formObjectOptions(array $parameters, $object, $action): int
    {
        global $conf, $extrafields, $form, $langs;

        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        $objectsMetadata = saturne_get_objects_metadata();
        foreach($objectsMetadata as $objectMetadata) {
            if ($objectMetadata['link_name'] != $object->element) {
                continue;
            }
            if (strpos($parameters['context'], $objectMetadata['hook_name_card']) !== false) {
                $pictoPath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
                $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
                $extraFieldsNames = ['trainingsession_type', 'trainingsession_location', 'trainingsession_opco_financing', 'syllabus'];
                foreach ($extraFieldsNames as $extraFieldsName) {
                    if (isset($extrafields->attributes[$object->table_element]['label'][$extraFieldsName])) {
                        $extrafields->attributes[$object->table_element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->table_element]['label'][$extraFieldsName]);
                    }
                }
            }
        }

        if (strpos($parameters['context'], 'propalcard') !== false) {
            if (empty(GETPOST('options_trainingsession_type', 'int'))) {
                $extrafields->attributes['propal']['hidden']['trainingsession_location'] = 1;
            } ?>

            <script>
                $(document).on('change', '#options_trainingsession_type', function() {
                    var type = $(this).val();
                    if (type > 0) {
                        $('#options_trainingsession_location').closest('tr').show();
                    } else {
                        $('#options_trainingsession_location').closest('tr').hide();
                    }
                });
            </script>
            <?php
        }

        if (strpos($parameters['context'], 'productcard')) {
            global $extrafields, $object;

            if ($object->type == $object::TYPE_PRODUCT) {
                $extrafields->attributes['product']['list']['syllabus'] = 0;
            }

            if (!empty($object->array_options['options_syllabus'])) {
                $out = '<div class="longmessagecut">';
                $out .= dolPrintHTML($object->array_options['options_syllabus']);
                $out .= '</div>';
                $object->array_options['options_syllabus'] = $out;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadatas (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListOption(array $parameters): int
    {
        global $extrafields, $langs, $object;

        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        $objectsMetadata = saturne_get_objects_metadata();
        foreach($objectsMetadata as $objectMetadata) {
            if ($objectMetadata['link_name'] != $object->element) {
                continue;
            }
            if (strpos($parameters['context'], $objectMetadata['hook_name_list']) !== false) {
                $pictoPath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
                $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
                $extraFieldsNames = ['trainingsession_type', 'trainingsession_location', 'trainingsession_opco_financing', 'syllabus'];
                foreach ($extraFieldsNames as $extraFieldsName) {
                    if (isset($extrafields->attributes[$object->table_element]['label'][$extraFieldsName])) {
                        $extrafields->attributes[$object->table_element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->table_element]['label'][$extraFieldsName]);
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
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
        if (preg_match('/contacttpl/', $parameters['context']) && preg_match('/contractcontactcard/', $parameters['context']) && isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
            global $object;

            if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type'])) {
                // Load Saturne libraries
                require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';
                require_once __DIR__ . '/../../saturne/lib/saturne_functions.lib.php';

                // Load DigiQuali libraries
                require_once __DIR__ . '/../../digiquali/class/survey.class.php';

                saturne_load_langs();

                $survey    = new Survey($this->db);
                $signatory = new SaturneSignature($db, 'digiquali', $survey->element);

                $contacts           = array_merge($object->liste_contact(-1, 'internal'), $object->liste_contact(-1));
                $contactsCodeWanted = ['SESSIONTRAINER', 'TRAINEE', 'CUSTOMER', 'BILLING'];

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
                                            $outputLine[$contact['rowid']] .= $survey->getNomUrl(1) . ' ' .  $signatory->getLibStatut(3);
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
                                        $outputLine[$contact['rowid']] .= $form->textwithpicto($langs->trans('ClickHere'), $langs->trans('NeedToSetSatisfactionSurvey', dol_strtolower($langs->trans(ucfirst(dol_strtolower($contact['code']))))), 1, 'warning') . '</a>';
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

        if (preg_match('/contacttpl/', $parameters['context'])) {
            global $object;

            $contacts = array_merge($object->liste_contact(-1, 'internal'), $object->liste_contact(-1));
            if (!empty($contacts)) {
                $outputLine = [];
                foreach ($contacts as $contact) {
                    $outputLine[$contact['rowid']]  = '<td class="tdoverflowmax200">';
                    if ($contact['mandatory_signature'] == 1) {
                        $outputLine[$contact['rowid']] .= '<a class="reposition" href="' . DOL_URL_ROOT . '/custom/dolimeet/core/ajax/testajax.php?action=set&token=' . newToken() . '&rowid=' . ((int) $contact['rowid']) . '&value=0&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id) . '">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</a>';
                    } else {
                        $outputLine[$contact['rowid']] .= '<a class="reposition" href="' . DOL_URL_ROOT . '/custom/dolimeet/core/ajax/testajax.php?action=set&token=' . newToken() . '&rowid=' . ((int) $contact['rowid']) . '&value=1&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id) . '">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</a>';
                    }
                    $outputLine[$contact['rowid']] .= '</td>';
                }

                $outputLineHeader = '<th class="wrapcolumntitle liste_titre" title="' . $langs->transnoentities('MandatorySignature') . '">' . $langs->transnoentities('MandatorySignature') . '</th>';

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

                saturne_load_langs();

                // Handle saturne_show_documents for completion certificate document generation
                $upload_dir = $conf->dolimeet->multidir_output[$object->entity ?? 1];
                $objRef     = dol_sanitizeFileName($object->ref);
                $dirFiles   = 'completioncertificatedocument/' . $objRef;
                $fileDir    = $upload_dir . '/' . $dirFiles;
                $urlSource  = $_SERVER['PHP_SELF'] . '?id=' . $object->id;

                $html = saturne_show_documents('dolimeet:CompletioncertificateDocument', $dirFiles, $fileDir, $urlSource, $user->rights->contrat->creer, $user->rights->contrat->supprimer, '', 1, 0, 0, 0, 0, '', 0, $langs->defaultlang, '', $object, 0, 'remove_file', (($object->statut > Contrat::STATUS_DRAFT && getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE') > 0) ? 1 : 0), $langs->trans('DefineSessionTrainerResponsible') . '<br>' . $langs->trans('ObjectMustBeValidatedToGenerate', ucfirst($langs->transnoentities(ucfirst($object->element))))); ?>

                <script>
                    jQuery('.fichehalfleft .div-table-responsive-no-min').first().append(<?php echo json_encode($html); ?>);
                </script>
                <?php
            }

            if ($object->statut != Contrat::STATUS_DRAFT && getDolGlobalString('CONTRACT_ALLOW_ONLINESIGN')) {
                require_once __DIR__ . '/../lib/dolibarr_lib.php';

                $contacts = array_merge($object->liste_contact(-1, 'internal'), $object->liste_contact(-1));
                if (!empty($contacts)) {
                    $outputLine = '';
                    foreach ($contacts as $contact) {
                        if ($contact['mandatory_signature'] == 1) {
                            $outputLine .= showOnlineSignatureUrl2('contract', $object->ref, $object, '', $contact);
                        }
                    }

                    ?>
                    <script>
                        // Target the second-to-last th element
                        var targetTh = $('.urllink');
                        targetTh.after(<?php echo json_encode($outputLine); ?>)
                    </script>
                    <?php
                }
            }
        }

        if (strpos($parameters['context'], 'propalcard') !== false) {
            global $object;

            if ($object->array_options['options_trainingsession_type'] == 1) {
                require_once __DIR__ . '/../lib/dolimeet_trainingsession.lib.php';

                $productIds = trainingsession_function_lib1();
                $out = Form::selectarray('idprod', $productIds, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100imp maxwidth500 widthcentpercentminusxx');
                ?>
                <script>
                    $(document).ready(function() {
                        $('#idprod').replaceWith(<?php echo json_encode($out); ?>);
                    });
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
        global $conf, $langs, $user;

        // Do something only for the current context.
        if ($parameters['currentcontext'] == 'admincompany') {
            if ($action == 'update') {
                dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER', GETPOST('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'), 'chaine', 0, '', $conf->entity);
            }
        }

        if (strpos($parameters['context'], 'contractcontactcard') !== false) {
            if ($action == 'set_satisfaction_survey' && isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
                require_once __DIR__ . '/../lib/dolimeet_function.lib.php';

                $object->fetch(GETPOST('id'));

                set_satisfaction_survey($object, GETPOST('contact_code'), GETPOST('contact_id'), GETPOST('contact_source'));

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                exit;
            }
        }

        if (strpos($parameters['context'], 'contractcard') !== false) {
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

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the beforePDFCreation function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadata (context, etc...)
     * @param  object $object     The object to process
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function beforePDFCreation(array $parameters): int
    {
        global $conf;

        if ($parameters['object']->element == 'contrat' && isset($parameters['object']->array_options['options_trainingsession_type']) && !empty($parameters['object']->array_options['options_trainingsession_type'])) {
            $conf->global->CONTRACT_HIDE_QTY_ON_PDF          = 1;
            $conf->global->CONTRACT_HIDE_PRICE_ON_PDF        = 1;
            $conf->global->CONTRACT_HIDE_PLANNED_DATE_ON_PDF = 1;
            $conf->global->CONTRACT_HIDE_REAL_DATE_ON_PDF    = 1;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the AddSignature function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadata (context, etc...)
     * @param  object $object     The object to process
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function AddSignature(array $parameters, $object): int
    {
        if (!GETPOSTISSET('contactid')) {
            return 0;
        }

        global $langs, $sourcefile, $online_sign_name, $upload_dir, $filename, $newpdffilename, $user;

        // We build the new PDF
        $pdf = pdf_getInstance();
        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($langs));

        if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
            $pdf->SetCompression(false);
        }

        //$pdf->Open();
        $last_main_doc_file = $object->last_main_doc;

        if (preg_match('/\.pdf/i', $last_main_doc_file)) {
            $ref_pdf = pathinfo($last_main_doc_file, PATHINFO_FILENAME); // Retrieves the name of external or internal PDF
            $sourcefile = $upload_dir . $ref_pdf . ".pdf";
        }

        $pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

        $param = array();
        $param['online_sign_name'] = $online_sign_name;
        $param['pathtoimage'] = $upload_dir . $filename;

        $s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
        for ($i = 1; $i < ($pagecount + 1); $i++) {
            try {
                $tppl = $pdf->importPage($i);
                $s = $pdf->getTemplatesize($tppl);
                $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                $pdf->useTemplate($tppl);

                if (getDolGlobalString("CONTRACT_SIGNATURE_ON_ALL_PAGES")) {
                    // A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
                    // TODO Get position of box from PDF template

                    if (getDolGlobalString("CONTRACT_SIGNATURE_XFORIMGSTART")) {
                        $param['xforimgstart'] = getDolGlobalString("CONTRACT_SIGNATURE_XFORIMGSTART");
                    } else {
                        $param['xforimgstart'] = (empty($s['w']) ? 110 : $s['w'] / 2 - 0);
                    }
                    if (getDolGlobalString("CONTRACT_SIGNATURE_YFORIMGSTART")) {
                        $param['yforimgstart'] = getDolGlobalString("CONTRACT_SIGNATURE_YFORIMGSTART");
                    } else {
                        $param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 62);
                    }
                    if (getDolGlobalString("CONTRACT_SIGNATURE_WFORIMG")) {
                        $param['wforimg'] = getDolGlobalString("CONTRACT_SIGNATURE_WFORIMG");
                    } else {
                        $param['wforimg'] = $s['w'] - ($param['xforimgstart'] + 16);
                    }

                    dolPrintSignatureImage($pdf, $langs, $param);
                }
            } catch (Exception $e) {
                dol_syslog("Error when manipulating some PDF by onlineSign: " . $e->getMessage(), LOG_ERR);
                $response = $e->getMessage();
                $error++;
            }
        }

        if (!getDolGlobalString("CONTRACT_SIGNATURE_ON_ALL_PAGES")) {
            // A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
            // TODO Get position of box from PDF template

            $param['xforimgstart'] = (empty($s['w']) ? 110 : 15);
            $param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 62);
            $param['wforimg'] = 89;

            dolPrintSignatureImage($pdf, $langs, $param);
        }

        //$pdf->Close();
        $pdf->Output($newpdffilename, "F");

        // Index the new file and update the last_main_doc property of object.
        $object->indexFile($newpdffilename, 1);

        $object->setSignedStatus($user, Contrat::$SIGNED_STATUSES['STATUS_SIGNED_ALL'], 0, 'CONTRACT_MODIFY');

        return 1; // or return 1 to replace standard code
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
        require_once __DIR__ . '/../lib/dolimeet_trainingsession.lib.php';

        $trainingSession = new Trainingsession($this->db);

        $linkableObjectTypes['dolimeet_trainsess'] = [
            'langs'          => 'Trainingsession',
            'langfile'       => 'dolimeet@dolimeet',
            'picto'          => $trainingSession->picto,
            'className'      => 'Trainingsession',
            'name_field'     => 'ref',
            'post_name'      => 'fk_trainingsession',
            'link_name'      => 'dolimeet_trainsess',
            'tab_type'       => 'trainingsession',
            'hook_name_list' => 'trainingsessionlist',
            'hook_name_card' => 'trainingsessioncard',
            'create_url'     => 'custom/dolimeet/view/trainingsession/session_card.php?action=create&object_type=trainingsession',
            'class_path'     => 'custom/dolimeet/class/trainingsession.class.php'
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

        if (preg_match('/contractcard|contractcontactcard/', $parameters['context'])) {
            if (!empty($object->array_options['options_trainingsession_type'])) {
                $contactRoles = [
                    'sessiontrainer' => [
                        'source'           => 'both',
                        'notice'           => 'warning',
                        'color'            => '#e9ad4f',
                        'picto'            => 'user-tie',
                        'tradForNotFound'  => 'ObjectNotFound',
                        'tradFound'        => 'TradFoundSessionTrainer',
                        'numberOfContacts' => 0
                    ],
                    'trainee' => [
                        'source'           => 'both',
                        'notice'           => 'warning',
                        'color'            => '#e9ad4f',
                        'picto'            => 'user-graduate',
                        'tradForNotFound'  => 'ObjectNotFound',
                        'tradFound'        => 'TradFoundTrainee',
                        'numberOfContacts' => 0
                    ],
                    'billing' => [
                        'source'           => 'external',
                        'notice'           => 'warning',
                        'color'            => '#e9ad4f',
                        'picto'            => 'file-invoice-dollar',
                        'tradForNotFound'  => 'BillingTypeContactObjectNotFound',
                        'tradFound'        => 'TradFoundBilling',
                        'numberOfContacts' => 0
                    ],
                    'customer' => [
                        'source'           => 'external',
                        'notice'           => 'warning',
                        'color'            => '#e9ad4f',
                        'picto'            => 'building',
                        'tradForNotFound'  => 'CustomerTypeContactObjectNotFound',
                        'tradFound'        => 'TradFoundCustomer',
                        'numberOfContacts' => 0
                    ],
                ];
                foreach ($contactRoles as $contactRole => $contactInfos) {
                    $contacts = [];
                    if ($contactInfos['source'] == 'both') {
                        $internalContacts = $object->liste_contact(-1, 'internal', 0, dol_strtoupper($contactRole));
                        $externalContacts = $object->liste_contact(-1, 'external', 0, dol_strtoupper($contactRole));
                        if ((is_array($internalContacts) && !empty($internalContacts)) || (is_array($externalContacts) && !empty($externalContacts))) {
                            $contacts = array_merge($internalContacts, $externalContacts);
                        }
                    } else {
                        $contacts = $object->liste_contact(-1, $contactInfos['source'], 0, dol_strtoupper($contactRole));
                    }
                    $contactInfos['numberOfContacts'] = is_array($contacts) ? count($contacts) : 0;
                    if ($contactInfos['numberOfContacts'] > 0) {
                        $contactInfos['color'] = '#47e58e';
                    }
                    if ($object->array_options['options_trainingsession_opco_financing'] == 1 && $contactRole == 'opco') {
                        $contactInfos['notice'] = 'warning';
                    }
                    $contactsNoticeByRoles[$contactInfos['notice']][$contactRole] = $contactInfos;
                }

                $form = new Form($this->db);

                $moreHtmlStatus = '';
                if (!empty($contactsNoticeByRoles)) {
                    foreach ($contactsNoticeByRoles as $contactNoticeType => $contactRoles) {
                        $moreHtmlStatus .= '<div class="wpeo-notice">';
                        $moreHtmlStatus .= '<div class="notice-content">';
                        $moreHtmlStatus .= '<div class="notice-subtitle">';
                        foreach ($contactRoles as $contactRole => $role) {
                            if ($object->array_options['options_trainingsession_opco_financing'] == 0 && $contactRole == 'opco') {
                                $moreHtmlStatus .= $langs->transnoentities('OpcoInfo', $langs->transnoentities(ucfirst($contactRole))) . '<br>';
                                continue;
                            }
                            $moreHtmlStatus .= '<span class="marginrightonly" style="display: inline-block; position: relative;">';
                            $moreHtmlStatus .= '<span>' . $form->textwithpicto(img_picto('', 'fontawesome_fa-' . $role['picto'] . '_fas_' . $role['color'] . '_2em' ), $langs->transnoentities(($role['numberOfContacts'] > 0 ? $role['tradFound'] : $role['tradForNotFound']), $langs->transnoentities(ucfirst($contactRole)))) . '</span>';
                            $moreHtmlStatus .= '<span class="" style="position: absolute; top: -5px; right: 3px;">' . $role['numberOfContacts'] . '</span>';
                            $moreHtmlStatus .= '</span>';
                        }
                        $moreHtmlStatus .= '</div></div></div>';
                    }
                }

                $moreHtmlStatus .= '<a href="' . dol_buildpath('custom/dolimeet/public/contact/add_contact.php', 3) . '?id=' . $object->id . '&token=' . currentToken() . '" target="_blank" class="butAction no-load">Interface Publique</a>';

                $this->resprints = $moreHtmlStatus;
            }
        }

        if (strpos($parameters['context'], 'productcard') !== false) {
            $categorieIDs = $object->getCategoriesCommon('product');
            if (is_array($categorieIDs) && !empty($categorieIDs)) {
                $error = 0;
                foreach ($categorieIDs as $categoryID) {
                    if ($categoryID == getDolGlobalInt('DOLIMEET_FORMATION_MAIN_CATEGORY')) {
                        if ($object->duration_value <= 0) {
                            $error++;
                            $this->errors[] = $langs->transnoentities('ErrorDuration');
                        }

                        if (isset($object->array_options['options_syllabus']) && dol_strlen($object->array_options['options_syllabus']) <= 0) {
                            $error++;
                            $this->errors[] = $langs->transnoentities('ErrorSyllabus');
                        }

                        require_once __DIR__ . '/trainingsession.class.php';

                        $trainingSession  = new Trainingsession($this->db);
                        $trainingSessions = $trainingSession->fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0 AND t.model = 1 AND t.fk_element = ' . $object->id]);
                        if (is_array($trainingSessions) && !empty($trainingSessions)) {
                            $durations = 0;
                            foreach ($trainingSessions as $trainingSession) {
                                $durations += $trainingSession->duration;
                                if ($trainingSession->status == 0) {
                                    $error++;
                                    $this->errors[] = $langs->transnoentities('ErrorStatus', $trainingSession->ref);
                                }
                            }

                            if ($durations != (convertDurationtoHour($object->duration_value, $object->duration_unit)) * 3600) {
                                $error++;
                                $this->errors[] = $langs->transnoentities('ErrrorDurationNotMatching');
                            }
                        } else {
                            $error++;
                            $this->errors[] = $langs->transnoentities('ObjectNotFound', $langs->transnoentities(ucfirst($trainingSession->element)));
                        }

                        $moreHtmlStatus  = '<div class="wpeo-notice notice-' . ($error == 0 ? 'success' : 'error') . '">';
                        $moreHtmlStatus .= '<div class="notice-content">';
                        $moreHtmlStatus .= '<div class="notice-title">';
                        if ($error > 0) {
                            foreach ($this->errors as $error) {
                                $moreHtmlStatus .= $error . '<br>';
                            }
                        } else {
                            $moreHtmlStatus .= $langs->transnoentities('ServiceReadyToBeUsed');
                        }
                        $moreHtmlStatus .= '</div></div></div>';
                        $this->resprints = $moreHtmlStatus;

                        break;
                    }
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

        if (strpos($parameters['context'], 'main') !== false) {
            $nbSessions = 0;
            require_once __DIR__ . '/session.class.php';
            $session = new Session($db);
            switch ($parameters['object']->element ?? '') {
                case 'societe' :
                    $objectElement = 'soc';
                    break;
                case 'contract' :
                    $objectElement = 'contrat';
                    break;
                case 'product' :
                    $objectElement = $parameters['object']->element;
                    $filter        = 't.status >= 0 AND t.model = 1 AND t.element_type = "service" AND t.fk_element = ' . $parameters['object']->id;
                    break;
                default :
                    $objectElement = $parameters['object']->element ?? '';
                    break;
            }
            $filter  = $filter ?? 't.status >= 0 AND t.fk_' . $objectElement . ' = ' . ($parameters['object']->id ?? 0);
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
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListFrom function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadata (context, etc...)
     * @param  object|null $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListFrom(array $parameters, ?object $object): int
    {
        global $conf;

        if (preg_match('/meetinglist|trainingsessionlist|auditlist/', $parameters['context'])) {
//            $sql = ' LEFT JOIN ' . $this->db->prefix() . 'element_element AS ee ON (t.rowid = ee.fk_target AND ee.targettype = "' . $object->module . '_' . $object->element . '")';
//            $this->resprints = $sql;

//            if (dol_strlen($fromType) > 0 && !in_array($fromType, $linkedObjectsArray) && !in_array($fromType, $signatoryObjectsArray) && $fromType != 'product') {
//                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as e on (e.fk_source = ' . $fromID . ' AND e.sourcetype="' . $fromType . '" AND e.targettype = "saturne_' . $objectType . '")';
//            }

            //            if (!$user->rights->dolimeet->$objectType->read && $user->rights->dolimeet->assignedtome->$objectType) {
//                if (!empty($user->contact_id)) {
//                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'saturne_object_signature as search_assignedtome on ((search_assignedtome.element_id = ' . $user->contact_id . ' AND search_assignedtome.element_type="socpeople") OR (search_assignedtome.element_id = ' . $user->id . ' AND search_assignedtome.element_type="user") AND search_assignedtome.status > 0)';
//                } else {
//                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'saturne_object_signature as search_assignedtome on (search_assignedtome.element_id = ' . $user->id . ' AND search_assignedtome.element_type="user" AND search_assignedtome.status > 0)';
//                }
//            }

            if (!empty($conf->cache['signatoriesInDictionary'])) {
                $signatoriesInDictionary = $conf->cache['signatoriesInDictionary'];
                $sql = '';
                foreach ($signatoriesInDictionary as $role) {
                    if (!empty($parameters['search'][$role->ref])) {
                        $sql = "
                        LEFT JOIN " . $this->db->prefix() . "saturne_object_signature as sos
                            ON sos.fk_object = t.rowid
                            AND sos.status > 0
                            AND sos.module_name = 'dolimeet'
                            AND (sos.element_type = 'user' OR sos.element_type = 'socpeople')";
                    }
                }
                $this->resprints = $sql;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListWhere function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListSearch(array $parameters): int
    {
        global $conf;

        if (preg_match('/meetinglist|trainingsessionlist|auditlist/', $parameters['context'])) {
            if (!empty($conf->cache['signatoriesInDictionary'])) {
                $signatoriesInDictionary = $conf->cache['signatoriesInDictionary'];
                $signatoriesRoles        = array_column(array_map(fn($obj) => (array) $obj, $signatoriesInDictionary), 'ref');
                if (!in_array($parameters['key'], $signatoriesRoles)) {
                    return 0;
                }

                $sql = '';
                if ((int) $parameters['val'] > 0) {
                    $sql = ' AND sos.role = "' . $parameters['key'] . '" AND sos.element_id = ' . $parameters['val'];
                }
                $this->resprints = $sql;
                $conf->global->MAIN_DISABLE_FULL_SCANLIST = 1;
                return 1; // or return 1 to replace standard code
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListWhere function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadata (context, etc...)
     * @param  object|null $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListWhere(array $parameters, ?object $object): int
    {
        if (preg_match('/meetinglist|trainingsessionlist|auditlist/', $parameters['context'])) {
            if (!empty($object) && $object instanceof Session && $object->element != 'session') {
                $sql = " AND t.type = '" . $object->element . "'";
                $this->resprints = $sql;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneSetVarsFromFetchObj function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object $object    Current object
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneSetVarsFromFetchObj(array $parameters, object $object): int
    {
        global $conf;

        if (preg_match('/meetinglist|trainingsessionlist|auditlist/', $parameters['context'])) {
            // Load Saturne libraries
            require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

            $signatory = new SaturneSignature($this->db, 'dolimeet', $object->element);

            $filter      = ['customsql' => 'fk_object = ' . $object->id . ' AND status > 0 AND object_type = "' . $object->element . '"'];
            $signatories = $signatory->fetchAll('', 'role', 0, 0, $filter);

            $conf->cache['signatories'] = $signatories;
            $conf->cache['contact']     = [];
            $conf->cache['user']        = [];
            $conf->cache['thirdparty']  = [];
            if (is_array($signatories) && !empty($signatories)) {
                foreach ($signatories as $signatory) {
                    // fetch user or contact depending on the element type of the signatory
                    $userTmp    = new User($this->db);
                    $contact    = new Contact($this->db);
                    $thirdparty = new Societe($this->db);
                    if ($signatory->element_type == 'user') {
                        $userTmp->fetch($signatory->element_id);
                        // fetch contact if user has one linked
                        if ($userTmp->contact_id > 0) {
                            $contact->fetch($userTmp->contact_id);
                        }
                        $conf->cache['user'][$signatory->role][$signatory->id] = $userTmp;
                    } elseif ($signatory->element_type == 'socpeople') {
                        $contact->fetch($signatory->element_id);
                    }
                    if (!empty($contact->fk_soc)) {
                        $thirdparty->fetch($contact->fk_soc);
                        $conf->cache['contact'][$signatory->role][$signatory->id] = $contact;
                        $conf->cache['thirdparty'][$signatory->id]                = $thirdparty;
                    }
                }
            }
        }

        if (strpos($parameters['context'], 'sessionlist') !== false) {
            switch ($object->type) {
                case 'meeting':
                    $object->picto  = 'fontawesome_fa-comments_fas_#d35968';
                    break;
                case 'trainingsession':
                    $object->picto  = 'fontawesome_fa-people-arrows_fas_#d35968';
                    break;
                case 'audit':
                    $object->picto  = 'fontawesome_fa-tasks_fas_#d35968';
                    break;
                default :
                    $object->picto  = 'dolimeet_color@dolimeet';
                    break;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturnePrintFieldListLoopObject function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object $object    Current object
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function saturnePrintFieldListLoopObject(array $parameters, object $object): int
    {
        global $conf, $langs;

        if (preg_match('/meetinglist|trainingsessionlist|auditlist/', $parameters['context'])) {
            $out = [];

            $signatoriesInDictionary = $conf->cache['signatoriesInDictionary'];
            if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
                $users       = $conf->cache['user'];
                $contacts    = $conf->cache['contact'];
                $signatories = $conf->cache['signatories'];
                foreach ($signatoriesInDictionary as $signatoryInDictionary) {
                    if ($parameters['key'] == $signatoryInDictionary->ref) {
                        if (is_array($signatories) && !empty($signatories)) {
                            $out[$parameters['key']] = '';
                            foreach ($signatories as $signatory) {
                                if ($signatory->role != $signatoryInDictionary->ref) {
                                    continue;
                                }
                                switch ($signatory->attendance) {
                                    case 1:
                                        break;
                                        $cssButton = '#0d8aff';
                                        $userIcon  = 'fa-user-clock';
                                        break;
                                    case 2:
                                        $cssButton = '#e05353';
                                        $userIcon  = 'fa-user-slash';
                                        break;
                                    default:
                                        $cssButton = '#47e58e';
                                        $userIcon  = 'fa-user';
                                        break;
                                }
                                if (is_array($users[$signatory->role]) && !empty($users[$signatory->role]) && $users[$signatory->role][$signatory->id] instanceof User) {
                                    $out[$parameters['key']] .= $users[$signatory->role][$signatory->id]->getNomUrl(1, '', 0, 0, 24, 1);
                                }
                                if (is_array($contacts[$signatory->role]) && !empty($contacts[$signatory->role]) && $contacts[$signatory->role][$signatory->id] instanceof Contact) {
                                    $out[$parameters['key']] .= $contacts[$signatory->role][$signatory->id]->getNomUrl(1);
                                }
                                if ((is_array($users[$signatory->role]) && !empty($users[$signatory->role])) || (is_array($contacts[$signatory->role]) && !empty($contacts[$signatory->role]))) {
                                    $out[$parameters['key']] .= ' ' . $signatory->getLibStatut(3);
                                    $out[$parameters['key']] .= ' <i class="fas ' . $userIcon . '" style="color: ' . $cssButton . '"></i><br>';
                                }
                            }
                        }
                    }
                }
            }

            if ($parameters['key'] == 'society_attendants') {
                $thirdparties = $conf->cache['thirdparty'];
                if (is_array($thirdparties) && !empty($thirdparties)) {
                    $alreadyAddedThirdParties = [];
                    foreach ($thirdparties as $thirdparty) {
                        if (!empty($thirdparty->id) && !in_array($thirdparty->id, $alreadyAddedThirdParties)) {
                            $out[$parameters['key']] .= $thirdparty->getNomUrl(1) . '<br>';
                        }
                        $alreadyAddedThirdParties[] = $thirdparty->id;
                    }
                }
            }

            $this->results = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneIndex function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return void
     */
    public function saturneIndex(array $parameters)
    {
        global $conf, $langs, $moduleName;

        if (strpos($parameters['context'], 'dolimeetindex') !== false) {
            $error          = 0;
            $formationConfs = [
                'integer' => [
                    'DOLIMEET_SERVICE_TRAINING_CONTRACT',
                    'DOLIMEET_SERVICE_WELCOME_BOOKLET',
                    'DOLIMEET_SERVICE_RULES_OF_PROCEDURE',
                    'DOLIMEET_FORMATION_MAIN_CATEGORY',
                    'DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT'
                ],
                'chaine' => [
                    'DOLIMEET_TRAININGSESSION_MORNING_START_HOUR',
                    'DOLIMEET_TRAININGSESSION_MORNING_END_HOUR',
                    'DOLIMEET_TRAININGSESSION_AFTERNOON_START_HOUR',
                    'DOLIMEET_TRAININGSESSION_AFTERNOON_END_HOUR',
                    'DOLIMEET_TRAININGSESSION_LOCATION'
                ]
            ];
            foreach ($formationConfs as $confType => $formationConfsByType) {
                foreach ($formationConfsByType as $formationConf) {

                    $confValue = dolibarr_get_const($this->db, $formationConf, $conf->entity);
                    switch ($confType) {
                        case 'integer':
                            if ((int)$confValue <= 0) {
                                $error++;
                            }
                            break;
                        case 'chaine':
                            if (dol_strlen($confValue) <= 0) {
                                $error++;
                            }
                            break;
                    }
                }
            }

            if ($error > 0) {
                $out  = '<div class="wpeo-notice notice-error">';
                $out .= '<div class="notice-content">';
                $out .= '<div class="notice-title"><strong>' . $langs->trans('SetupDefaultDataNotCreated', $moduleName) . '</strong></div>';
                $out .= '<div class="notice-subtitle"><strong>' . $langs->trans('HowToSetupDefaultData', $moduleName) . ' <a href="admin/setup.php">' . $langs->trans('ConfigDefaultData', $moduleName) . '</a></strong></div>';
                $out .= '</div>';
                $out .= '</div>';

                $this->resprints = $out;
            }
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

                $session = new Session($this->db);

                foreach (['internal', 'external'] as $source) {
                    $contactList[$source] = $object->liste_contact(-1, $source, 0, 'TRAINEE');
                }

                $sessions = $session->fetchAll('', '', 0, 0, ['customsql' => 't.fk_contrat = ' . $object->id . ' AND t.status >= 1']);
                if (is_array($sessions) && !empty($sessions)) {
                    $signatory = new SaturneSignature($this->db, 'dolimeet', $object->element);

                    $durations  = 0;
                    $nbSessions = count($sessions);
                    $contactIds = [];
                    foreach ($sessions as $session) {
                        $absentSignatories = $signatory->fetchSignatories($session->id, 'trainingsession', 't.role = "Trainee"' . ' AND t.attendance = ' . SaturneSignature::ATTENDANCE_ABSENT);
                        if (is_array($absentSignatories) && !empty($absentSignatories)) {
                            foreach ($absentSignatories as $signatory) {
                                $type       = ($signatory->element_type == 'user' ? 'internal' : 'external');
                                $contactIds = array_column($contactList[$type], 'id');
                                if (in_array($signatory->element_id, $contactIds)) {
                                    $absentTrainees[$signatory->element_id]['lastname']  = $signatory->lastname;
                                    $absentTrainees[$signatory->element_id]['firstname'] = $signatory->firstname;
                                    $absentTrainees[$signatory->element_id]['type']      = $type;
                                    $absentTrainees[$signatory->element_id]['nbAbsence']++;
                                }
                            }
                        }
                        $durations += $session->duration;
                    }

                    if (!empty($absentTrainees)) {
                        foreach ($absentTrainees as $absentId => $absentTrainee) {
                            if (($absentTrainee['nbAbsence'] / $nbSessions * 100) > getDolGlobalInt('DOLIMEET_TRAININGSESSION_ABSENCE_RATE')) {
                                $key = array_search($absentId, $contactIds);
                                unset($contactList[$absentTrainee['type']][$key]);
                                setEventMessages($langs->trans('NoCertificateBecauseAbsent', $absentTrainee['lastname'], $absentTrainee['firstname']), [], 'warnings');
                            }
                        }
                    }

                    if (!empty($contactList)) {
                        $document                              = new CompletioncertificateDocument($this->db);
                        $parameters['moreparams']['attendant'] = new stdClass();

                        $parameters['moreparams']['object']             = $object;
                        $parameters['moreparams']['object']->element    = 'trainingsession';
                        $parameters['moreparams']['object']->fk_contrat = $object->id;
                        $parameters['moreparams']['object']->date_start = current($sessions)->date_start;
                        $parameters['moreparams']['object']->date_end   = end($sessions)->date_end;
                        $parameters['moreparams']['object']->duration   = $durations;
                        foreach ($contactList as $contactType) {
                            foreach($contactType as $contact) {
                                $parameters['moreparams']['attendant']->firstname    = $contact['firstname'];
                                $parameters['moreparams']['attendant']->lastname     = $contact['lastname'];
                                $parameters['moreparams']['attendant']->element_type = ($contact['source'] == 'external' ? 'socpeople' : 'user');
                                $parameters['moreparams']['attendant']->element_id   = $contact['id'];

                                $document->element = 'trainingsessiondocument';

                                $result = $document->generateDocument((!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']), $parameters['outputlangs'], $parameters['hidedetails'], $parameters['hidedesc'], $parameters['hideref'], $parameters['moreparams']);
                                // Need to reset $document->error because commonGenerateDocument call unwanted function dol_delete_preview
                                if ($document->error == 'ErrorObjectNoSupportedByFunction') {
                                    $document->error = '';
                                }
                                if ($result <= 0) {
                                    setEventMessages($document->error, $document->errors, 'errors');
                                }

                                $documentType = explode('_odt', (!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']));
                                if ($document->element != $documentType[0]) {
                                    $document->element = $documentType[0];
                                }

                                setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart=dolimeet&file=' . urlencode($document->element . '/' . $object->ref . '/' . $document->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $document->last_main_doc, []);
                            }
                        }

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
        }

        return 0; // or return 1 to replace standard code.
    }
}
