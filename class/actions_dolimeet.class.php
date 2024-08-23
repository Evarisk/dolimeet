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
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (preg_match('/projectcard|productcard/', $parameters['context'])) {
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
        global $extrafields, $langs;

        if (preg_match('/projectcard|propalcard|contractcard/', $parameters['context'])) {
            $pictoPath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
            $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');

            $extrafields->attributes['projet']['label']['trainingsession_type']        = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_type']);
            $extrafields->attributes['projet']['label']['trainingsession_service']     = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_service']);
            $extrafields->attributes['projet']['label']['trainingsession_location']    = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_location']);
            $extrafields->attributes['projet']['label']['trainingsession_nb_trainees'] = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_nb_trainees']);

            $extrafields->attributes['propal']['label']['trainingsession_type']      = $picto . $langs->transnoentities($extrafields->attributes['propal']['label']['trainingsession_type']);
            $extrafields->attributes['propal']['label']['trainingsession_service']   = $picto . $langs->transnoentities($extrafields->attributes['propal']['label']['trainingsession_service']);
            $extrafields->attributes['propal']['label']['trainingsession_location']  = $picto . $langs->transnoentities($extrafields->attributes['propal']['label']['trainingsession_location']);

            $extrafields->attributes['contrat']['label']['label']                     = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['label']);
            $extrafields->attributes['contrat']['label']['trainingsession_type']      = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_type']);
            $extrafields->attributes['contrat']['label']['trainingsession_location']  = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_location']);
            $extrafields->attributes['contrat']['label']['trainingsession_start']     = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_start']);
            $extrafields->attributes['contrat']['label']['trainingsession_end']       = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_end']);
            $extrafields->attributes['contrat']['label']['trainingsession_durations'] = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_durations']);

            // Initialize the param attribute for trainingsession_service
            if (isset($extrafields->attributes['propal']['param']['trainingsession_service']) || isset($extrafields->attributes['projet']['param']['trainingsession_service'])) {
                $filter  = 'product as p:ref|label:rowid::fk_product_type = 1 AND entity = $ENTITY$';
                $filter .= ' AND rowid IN (SELECT cp.fk_product FROM llx_categorie_product cp LEFT JOIN llx_categorie c ON cp.fk_categorie = c.rowid WHERE cp.fk_categorie = ' . getDolGlobalInt('DOLIMEET_FORMATION_MAIN_CATEGORY') . ')';
                $filter .= ' AND EXISTS (SELECT 1 FROM llx_dolimeet_session ds WHERE ds.fk_element = p.rowid AND ds.model = 1 AND ds.element_type = "service" AND ds.date_start IS NOT NULL AND ds.date_end IS NOT NULL AND ds.fk_project = ' .  getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT') . ' GROUP BY ds.fk_element HAVING SUM(ds.duration) = p.duration * 3600)';

                $extrafields->attributes['projet']['param']['trainingsession_service'] = ['options' => [$filter => '']];
                $extrafields->attributes['propal']['param']['trainingsession_service'] = ['options' => [$filter => '']];
            }
        }

        if (strpos($parameters['context'], 'propalcard') !== false) {
            if (empty(GETPOST('options_trainingsession_type', 'int'))) {
                $extrafields->attributes['propal']['hidden']['trainingsession_service']  = 1;
                $extrafields->attributes['propal']['hidden']['trainingsession_location'] = 1;
            }

            // Hide extrafields trainingsession_service for view mode
            if (empty($action)) {
                $extrafields->attributes['propal']['list']['trainingsession_service'] = 0;
            }

            ?>
            <script>
                $(document).on('change', '#options_trainingsession_type', function() {
                    var type = $(this).val();
                    if (type > 0) {
                        $('#options_trainingsession_service').closest('tr').show();
                        $('#options_trainingsession_location').closest('tr').show();
                    } else {
                        $('#options_trainingsession_service').closest('tr').hide();
                        $('#options_trainingsession_location').closest('tr').hide();
                    }
                });
            </script>
            <?php
        }

        if (strpos($parameters['context'], 'projectcard') !== false) {
            // Hide extrafields for create mode
            if (empty(GETPOST('options_trainingsession_type', 'int'))) {
                $extrafields->attributes['projet']['hidden']['trainingsession_service']     = 1;
                $extrafields->attributes['projet']['hidden']['trainingsession_location']    = 1;
                $extrafields->attributes['projet']['hidden']['trainingsession_nb_trainees'] = 1;
                $extrafields->attributes['projet']['hidden']['trainingsession_nb_trainees'] = 1;
            }

            // Show extrafields for update mode if options_trainingsession_type is set
            if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type'])) {
                $extrafields->attributes['projet']['hidden']['trainingsession_service']     = 0;
                $extrafields->attributes['projet']['hidden']['trainingsession_location']    = 0;
                $extrafields->attributes['projet']['hidden']['trainingsession_nb_trainees'] = 0;
            }

            // Disabled extrafields for view mode
            if (empty($object->array_options['options_trainingsession_type']) && $action != 'create' && $action != 'edit') {
                $extrafields->attributes['projet']['list']['trainingsession_type']        = 0;
                $extrafields->attributes['projet']['list']['trainingsession_service']     = 0;
                $extrafields->attributes['projet']['list']['trainingsession_location']    = 0;
                $extrafields->attributes['projet']['list']['trainingsession_nb_trainees'] = 0;
            }

            $out  = '<div class="wpeo-notice notice-error">';
            $out .= '<div class="notice-content left">';
            $out .= '<div class="notice-title">' . $langs->transnoentities('ErrorMissingTrainingSessionProjectInfo') . '</div><div class="notice-subtitle"><ul>';
            $out .= '<li class="notice-training-session-service">' . $langs->transnoentities('TrainingSessionService') . '</li>';
            $out .= '<li class="notice-third-party">' . $langs->transnoentities('ThirdParty') . '</li>';
            $out .= '<li class="notice-opportunity-status">' . $langs->transnoentities('OpportunityStatus') . '</li>';
            $out .= '</ul></div></div></div>';

            ?>
            <script>
                $(document).on('change', '#socid', function() {
                    var type = $(this).val();
                    if (type > 0) {
                        $('#options_trainingsession_type').removeAttr('disabled');
                    } else {
                        $('#options_trainingsession_type').attr('disabled', 'disabled');
                    }
                });

                $(document).on('change', '#options_trainingsession_type', function() {
                    var type = $(this).val();
                    if (type > 0) {
                        $('#options_trainingsession_service').closest('tr').show();
                        $('#options_trainingsession_location').closest('tr').show();
                        $('#options_trainingsession_nb_trainees').closest('tr').show();
                    } else {
                        $('#options_trainingsession_service').closest('tr').hide();
                        $('#options_trainingsession_location').closest('tr').hide();
                        $('#options_trainingsession_nb_trainees').closest('tr').hide();
                    }
                });

                $(document).on('change', '#options_trainingsession_service', function() {
                    let labelField = $('input[name="title"]');
                    labelField.val($(this).find('option:selected').text());
                    window.saturne.loader.display(labelField);
                    setTimeout(function() {
                        window.saturne.loader.remove(labelField);
                    }, 1000);
                });

                $(document).ready(function(){
                    let table = $('form table tr').eq(0);
                    $('.field_options_trainingsession_type').insertAfter(table);
                    $('.field_options_trainingsession_service').insertAfter('.field_options_trainingsession_type');
                    $('.field_options_trainingsession_location').insertAfter('.field_options_trainingsession_service');
                    $('.field_options_trainingsession_nb_trainees').insertAfter('.field_options_trainingsession_location');

                    function checkFields() {
                        let displayNotice = 0;
                        let notice        = $('.fiche').find('.wpeo-notice');
                        if ($('#options_trainingsession_type').val() > 0) {
                            let labelField = $('input[name="title"]');
                            console.log(labelField.val())
                            if (labelField.val().length <= 0) {
                                displayNotice++;
                            } else {
                                notice.find('.notice-label').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }

                            let thirdPartyField = $('#socid');
                            if (thirdPartyField.val() <= 0) {
                                displayNotice++;
                            } else {
                                notice.find('.notice-third-party').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }

                            let opportunityStatusField = $('#opp_status');
                            if (opportunityStatusField.val() <= 0) {
                                displayNotice++;
                            } else {
                                notice.find('.notice-opportunity-status').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }

                            let opportunityPercentField = $('#opp_percent');
                            if (opportunityPercentField.val().length <= 0) {
                                displayNotice++;
                            }

                            let trainingSessionServiceField = $('#options_trainingsession_service');
                            if (trainingSessionServiceField.val() <= 0) {
                                displayNotice++;
                            } else {
                                notice.find('.notice-training-session-service').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }

                            if (displayNotice > 0 && notice.length == 0) {
                                $('.button-save').prop('disabled', true);
                                $('.fiche').prepend(<?php echo json_encode($out); ?>);
                            } else if (displayNotice == 0) {
                                $('.button-save').prop('disabled', false);
                                notice.fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }
                        } else {
                            $('.button-save').prop('disabled', false);
                            notice.fadeOut(400, function() {
                                $(this).remove();
                            });
                        }
                    }

                    setInterval(checkFields, 1000);
                    if ($('#socid').val() < 0) {
                        $('#options_trainingsession_type').attr('disabled', 'disabled');
                    }
                });
            </script>
            <?php
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
        global $extrafields, $langs;

        if (preg_match('/projectlist|propallist|contractlist/', $parameters['context'])) {
            $pictoPath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
            $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');

            $extrafields->attributes['projet']['label']['trainingsession_type']        = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_type']);
            $extrafields->attributes['projet']['label']['trainingsession_service']     = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_service']);
            $extrafields->attributes['projet']['label']['trainingsession_location']    = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_location']);
            $extrafields->attributes['projet']['label']['trainingsession_nb_trainees'] = $picto . $langs->transnoentities($extrafields->attributes['projet']['label']['trainingsession_nb_trainees']);

            $extrafields->attributes['propal']['label']['trainingsession_type']      = $picto . $langs->transnoentities($extrafields->attributes['propal']['label']['trainingsession_type']);
            $extrafields->attributes['propal']['label']['trainingsession_service']   = $picto . $langs->transnoentities($extrafields->attributes['propal']['label']['trainingsession_service']);
            $extrafields->attributes['propal']['label']['trainingsession_location']  = $picto . $langs->transnoentities($extrafields->attributes['propal']['label']['trainingsession_location']);

            $extrafields->attributes['contrat']['label']['label']                     = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['label']);
            $extrafields->attributes['contrat']['label']['trainingsession_type']      = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_type']);
            $extrafields->attributes['contrat']['label']['trainingsession_location']  = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_location']);
            $extrafields->attributes['contrat']['label']['trainingsession_start']     = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_start']);
            $extrafields->attributes['contrat']['label']['trainingsession_end']       = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_end']);
            $extrafields->attributes['contrat']['label']['trainingsession_durations'] = $picto . $langs->transnoentities($extrafields->attributes['contrat']['label']['trainingsession_durations']);
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

                // Handle consistency of contract trainingsession dates
                $session = new Session($this->db);

                $filter = ' AND t.status >= 0 AND t.type = "trainingsession" AND t.fk_contrat = ' . $object->id . ' ORDER BY t.date_start ASC';
                $session->fetch('', '', $filter);

                $out = img_picto('', 'check', 'class="marginleftonly"');
                if ($session->date_start != $object->array_options['options_trainingsession_start']) {
                    $out = $form->textwithpicto('', $langs->trans('TrainingSessionStartErrorMatchingDate', $session->ref), 1, 'warning');
                } ?>
                <script>
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
                    jQuery('.contrat_extras_trainingsession_end').append(<?php echo json_encode($out); ?>);
                </script>
                <?php

                // Handle session durations
                $sessionDurations = 0;
                $filter           = 't.status >= 0 AND t.type = "trainingsession" AND t.fk_contrat = ' . $object->id;
                $sessions         = $session->fetchAll('', '', 0, 0, ['customsql' => $filter]);
                if (is_array($sessions) && !empty($sessions)) {
                    foreach ($sessions as $sessionSingle) {
                        $sessionDurations += $sessionSingle->duration;
                    }
                }
                if (GETPOST('action') == 'edit_extras' && GETPOST('attribute') == 'trainingsession_durations') {
                    $out = '<td id="' . $object->element . '_extras_trainingsession_durations_' . $object->id . '" class="valuefield ' . $object->element . '_extras_trainingsession_durations">';
                    $out .= '<form enctype="multipart/form-data" action="'. $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post" name="formextra">';
                    $out .= '<input type="hidden" name="action" value="update_extras">';
                    $out .= '<input type="hidden" name="attribute" value="trainingsession_durations">';
                    $out .= '<input type="hidden" name="token" value="'.newToken().'">';
                    $out .= '<input type="hidden" name="confirm" value="yes">';
                    $out .= $form->select_duration('duration', $object->array_options['options_trainingsession_durations'], 0, 'text', 0, 1);
                    $out .= '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">' . '</td></tr>';
                } else {
                    $out = '<td id="' . $object->element . '_extras_trainingsession_durations_' . $object->id . '" class="valuefield ' . $object->element . '_extras_trainingsession_durations">' . ($object->array_options['options_trainingsession_durations'] > 0 ? convertSecondToTime($object->array_options['options_trainingsession_durations'], 'allhourmin') : '00:00') . ' - ';
                    $out .= $langs->trans('CalculatedTotalSessionDuration') . ' ' . ($sessionDurations > 0 ? convertSecondToTime($sessionDurations, 'allhourmin') : '00:00');
                    if ($sessionDurations != $object->array_options['options_trainingsession_durations']) {
                        $out .= $form->textwithpicto('', $langs->trans('TrainingSessionDurationErrorMatching', $session->ref), 1, 'warning');
                    }
                    $out .= '</td></tr>';
                } ?>
                <script>
                    jQuery('.valuefield.contrat_extras_trainingsession_durations').replaceWith(<?php echo json_encode($out); ?>);
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

        if (strpos($parameters['context'], 'projectcard') !== false) {
            global $action, $object;

            if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type']) && $action != 'create' && $action != 'edit') {
                require_once __DIR__ . '/trainingsession.class.php';

                $trainingSession = new Trainingsession($this->db);
                $out                                                      = [];
                $object->array_options['options_trainingsession_service'] = explode(',', $object->array_options['options_trainingsession_service']);
                foreach ($object->array_options['options_trainingsession_service'] as $index => $trainingSessionServiceId) {
                    $trainingSession->fetch('', '', ' AND t.element_type = "service" AND t.fk_element = ' . $trainingSessionServiceId);
                    if ($object->array_options['options_trainingsession_service'][$index] == $trainingSession->fk_element) {
                        $out[$index] = ' <a href="' . dol_buildpath('custom/dolimeet/view/session/session_list.php?object_type=trainingsession&search_model=1&search_fk_project=' . getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT') . '&search_element_type=service&search_fk_element=' . $trainingSessionServiceId . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id), 1) . '"><span class="fas fa-eye valignmiddle" title="' . $langs->transnoentities('SeeTrainingSessionModel') . '"></span></a>';
                    }
                }
            } ?>

            <script>
                $('.project_extras_trainingsession_service .select2-container-multi-dolibarr .select2-choices-dolibarr li').each(function(index) {
                    $(this).append(<?php echo json_encode($out); ?>[index]);
                });
            </script>
            <?php
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

            if ($action == 'update_extras' && GETPOST('attribute') == 'trainingsession_durations') {
                $hours    = GETPOST('durationhour', 'int');
                $minutes  = GETPOST('durationmin', 'int');
                $duration = convertTime2Seconds($hours, $minutes);

                $object->array_options['options_trainingsession_durations'] = $duration;
                $object->updateExtrafield('trainingsession_durations');
                return 1;
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

        if (strpos($parameters['context'], 'productcard') !== false) {
            $categorieIDs = $object->getCategoriesCommon('product');
            if (is_array($categorieIDs) && !empty($categorieIDs)) {
                $error = 0;
                foreach ($categorieIDs as $categoryID) {
                    if ($categoryID == getDolGlobalInt('DOLIMEET_FORMATION_MAIN_CATEGORY')) {
                        if ($object->duration_value <= 0) {
                            $error++;
                            $this->errors[] = $langs->transnoentities('ErrorDuration');
                        } else {
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
                                if ($durations != convertTime2Seconds($object->duration)) {
                                    $error++;
                                    $this->errors[] = $langs->transnoentities('ErrrorDurationNotMatching');
                                }
                            } else {
                                $error++;
                                $this->errors[] = $langs->transnoentities('ObjectNotFound', $langs->transnoentities(ucfirst($trainingSession->element)));
                            }
                        }
                    }
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
            }
        }

        return 0; // or return 1 to replace standard code.
    }

    /**
     * Overloading the dolGetButtonAction function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function dolGetButtonAction(array $parameters, $object, string $action): int
    {
        global $langs;

        if (strpos($parameters['context'], 'projectcard') !== false) {
            $langs->load('propal');
            if ($parameters['html'] == $langs->trans('AddProp')) {
                $explodedCompiledAttributes       = explode('projectid', $parameters['compiledAttributes']);
                $parameters['compiledAttributes'] = $explodedCompiledAttributes[0] . 'options_trainingsession_type=' . $object->array_options['options_trainingsession_type'] . '&options_trainingsession_service=' . $object->array_options['options_trainingsession_service'] . '&options_trainingsession_location=' . $object->array_options['options_trainingsession_location'] . '&projectid' . $explodedCompiledAttributes[1];

                $this->resprints = '<' . $parameters['tag'] . ' ' . $parameters['compiledAttributes'] . '>' . dol_escape_htmltag($parameters['html']) . '</' . $parameters['tag'] . '>';

                return 1;
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
            switch ($parameters['object']->element) {
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
                    $objectElement = $parameters['object']->element;
                    break;
            }
            $filter  = $filter ?? 't.status >= 0 AND t.fk_' . $objectElement . ' = ' . $parameters['object']->id;
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

                $session   = new Session($this->db);
                $document  = new CompletioncertificateDocument($this->db);
                $signatory = new SaturneSignature($this->db, 'dolimeet', $object->element);

                $contactList   = [];
                $signedTrainee = [];
                $sessions      = $session->fetchAll('', '', 0, 0, ['customsql' => 't.fk_contrat = ' . $object->id . ' AND t.status >= 0']);
                // We retrieve internal & external user linked to the contract
                foreach (['internal', 'external'] as $source) {
                    $contactList[$source] = $object->liste_contact(-1, $source, 0, 'TRAINEE');
                    // We need our array keys to start with 1 for further logic
                    array_unshift($contactList[$source],'');
                    unset($contactList[$source][0]);
                }
                // Because of the structure of $contactList we need a second array where we will remove someone if he is present for ONE session
                $absentTrainee = $contactList;

                if (is_array($sessions) && !empty($sessions)) {
                    foreach ($sessions as $session) {
                        $signatories = $signatory->fetchSignatories($session->id, 'trainingsession', 'role = "Trainee"');
                        foreach ($signatories as $signatory) {
                            $type     = ($signatory->element_type == 'user' ? 'internal' : 'external');
                            $absentId = array_column($absentTrainee[$type], 'id');

                            // We search for the key in $contactList corresponding to the current $signatory->element_id
                            array_unshift($absentId,'');
                            unset($absentId[0]);
                            // array_search return false (0) if it doesn't find, that's why we need our $absentTrainee array to start by 1
                            $key = array_search($signatory->element_id, $absentId);

                            if ($signatory->attendance != SaturneSignature::ATTENDANCE_ABSENT) {
                                // If the $signatory is present then we will remove it from the $absentTrainee array
                                if ($key > 0) {
                                    unset($absentTrainee[$type][$key]);
                                }
                                $signedTrainee[$type][$signatory->element_id] += $session->duration;
                            }
                        }
                    }
                    $lastSession = end($sessions);
                    $dateEnd     = $lastSession->date_end;
                    if ($dateEnd != $object->array_options['options_trainingsession_end']) {
                        setEventMessages($langs->trans('TrainingSessionEndErrorMatchingDate', $lastSession->ref), [], 'warnings');
                    }
                }

                if (!empty($absentTrainee)) {
                    foreach ($absentTrainee as $absentType) {
                        foreach($absentType as $contact) {
                            setEventMessages($langs->trans('NoCertificateBecauseAbsent', $contact['lastname'], $contact['firstname']), [], 'warnings');
                        }
                    }
                }

                $parameters['moreparams']['object']             = $object;
                $parameters['moreparams']['object']->element    = 'trainingsession';
                $parameters['moreparams']['object']->date_start = $object->array_options['options_trainingsession_start'];
                $parameters['moreparams']['object']->date_end   = $object->array_options['options_trainingsession_end'];
                $parameters['moreparams']['object']->fk_contrat = $object->id;

                if (!empty($contactList) && !empty($signedTrainee)) {
                    foreach ($contactList as $contactType) {
                        foreach($contactType as $contact) {
                            if (is_array($signedTrainee[$contact['source']]) && array_key_exists($contact['id'], $signedTrainee[$contact['source']])) {
                                $parameters['moreparams']['attendant']               = $signatory;
                                $parameters['moreparams']['attendant']->firstname    = $contact['firstname'];
                                $parameters['moreparams']['attendant']->lastname     = $contact['lastname'];
                                $parameters['moreparams']['attendant']->element_type = ($contact['source'] == 'external' ? 'socpeople' : 'user');
                                $parameters['moreparams']['attendant']->element_id   = $contact['id'];
                                $parameters['moreparams']['object']->duration        = $signedTrainee[$contact['source']][$contact['id']];

                                $document->element = 'trainingsessiondocument';
                                $result = $document->generateDocument((!empty($parameters['models']) ? $parameters['models'][1] : $parameters['model']), $parameters['outputlangs'], $parameters['hidedetails'], $parameters['hidedesc'], $parameters['hideref'], $parameters['moreparams']);
                                if ($result <= 0) {
                                    setEventMessages($document->error, $document->errors, 'errors');
                                }
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
}
