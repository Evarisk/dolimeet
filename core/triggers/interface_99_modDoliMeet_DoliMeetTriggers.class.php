<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    core/triggers/interface_99_modDoliMeet_DoliMeetTriggers.class.php
 * \ingroup dolimeet
 * \brief   DoliMeet trigger.
 */

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * Class of triggers for DoliMeet module.
 */
class InterfaceDoliMeetTriggers extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler.
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);

        $this->name        = preg_replace('/^Interface/i', '', get_class($this));
        $this->family      = 'demo';
        $this->description = 'DoliMeet triggers.';
        $this->version     = '1.4.0';
        $this->picto       = 'dolimeet@dolimeet';
    }

    /**
     * Trigger name.
     *
     * @return string Name of trigger file.
     */
    public function getName(): string
    {
        return parent::getName();
    }

    /**
     * Trigger description.
     *
     * @return string Description of trigger file.
     */
    public function getDesc(): string
    {
        return parent::getDesc();
    }

    /**
     * Function called when a Dolibarr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers.
     *
     * @param  string       $action Event action code.
     * @param  CommonObject $object Object.
     * @param  User         $user   Object user.
     * @param  Translate    $langs  Object langs.
     * @param  Conf         $conf   Object conf.
     * @return int                  0 < if KO, 0 if no triggered ran, >0 if OK.
     * @throws Exception
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
    {
        if (!isModEnabled('dolimeet')) {
            return 0; // If module is not enabled, we do nothing.
        }

        // Data and type of action are stored into $object and $action.
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $now        = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype = $object->element . '@dolimeet';
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->datep       = $now;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;

        if (getDolGlobalInt('DOLIMEET_ADVANCED_TRIGGER') && !empty($object->fields)) {
            $actioncomm->note_private = method_exists($object, 'getTriggerDescription') ? $object->getTriggerDescription($object) : '';
        }

        switch ($action) {
            // CREATE.
            case 'MEETING_CREATE' :
            case 'AUDIT_CREATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'TRAININGSESSION_CREATE' :
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

                require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

                $contract  = new Contrat($this->db);
                $signatory = new SaturneSignature($this->db, 'dolimeet', 'trainingsession');

                $contract->fetch($object->fk_contrat);

                $attendantInternalSessionTrainerArray = $contract->liste_contact(-1, 'internal', 0, 'SESSIONTRAINER');
                $attendantInternalTraineeArray        = $contract->liste_contact(-1, 'internal', 0, 'TRAINEE');
                $attendantExternalSessionTrainerArray = $contract->liste_contact(-1, 'external', 0, 'SESSIONTRAINER');
                $attendantExternalTraineeArray        = $contract->liste_contact(-1, 'external', 0, 'TRAINEE');

                $attendantArray = array_merge(
                    (is_array($attendantInternalSessionTrainerArray) ? $attendantInternalSessionTrainerArray : []),
                    (is_array($attendantInternalTraineeArray) ? $attendantInternalTraineeArray : []),
                    (is_array($attendantExternalSessionTrainerArray) ? $attendantExternalSessionTrainerArray : []),
                    (is_array($attendantExternalTraineeArray) ? $attendantExternalTraineeArray : [])
                );

                foreach ($attendantArray as $attendant) {
                    if ($attendant['code'] == 'TRAINEE') {
                        $attendantRole = 'Trainee';
                    } else {
                        $attendantRole = 'SessionTrainer';
                    }
                    $signatory->setSignatory($object->id, $object->element, (($attendant['source'] == 'internal') ? 'user' : 'socpeople'), [$attendant['id']], $attendantRole, 1);
                }

                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // MODIFY.
            case 'MEETING_MODIFY' :
            case 'TRAININGSESSION_MODIFY' :
            case 'AUDIT_MODIFY' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_MODIFY';
                $actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // DELETE.
            case 'MEETING_DELETE' :
            case 'TRAININGSESSION_DELETE' :
            case 'AUDIT_DELETE' :
                $actioncomm->code  = 'AC_ ' . strtoupper($object->element) . '_DELETE';
                $actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // VALIDATE.
            case 'MEETING_VALIDATE' :
            case 'TRAININGSESSION_VALIDATE' :
            case 'AUDIT_VALIDATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_VALIDATE';
                $actioncomm->label = $langs->trans('ObjectValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // UNVALIDATE.
            case 'MEETING_UNVALIDATE' :
            case 'TRAININGSESSION_UNVALIDATE' :
            case 'AUDIT_UNVALIDATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_UNVALIDATE';
                $actioncomm->label = $langs->trans('ObjectUnValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // LOCK.
            case 'MEETING_LOCK' :
            case 'TRAININGSESSION_LOCK' :
            case 'AUDIT_LOCK' :
                $actioncomm->code          = 'AC_' . strtoupper($object->element) . '_LOCK';
                $actioncomm->label         = $langs->trans('ObjectLockedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->note_private .= $langs->trans('Status') . ' : ' . $langs->trans('Locked') . '</br>';
                $actioncomm->create($user);
                break;

            // ARCHIVE.
            case 'MEETING_ARCHIVE' :
            case 'TRAININGSESSION_ARCHIVE' :
            case 'AUDIT_ARCHIVE' :
                $actioncomm->code          = 'AC_' . strtoupper($object->element) . '_ARCHIVE';
                $actioncomm->label         = $langs->trans('ObjectArchivedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->note_private .= $langs->trans('Status') . ' : ' . $langs->trans('Archived') . '</br>';
                $actioncomm->create($user);
                break;

            // SENTBYMAIL.
            case 'MEETING_SENTBYMAIL' :
            case 'TRAININGSESSION_SENTBYMAIL' :
            case 'AUDIT_SENTBYMAIL' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_SENTBYMAIL';
                $actioncomm->label = $langs->trans('ObjectSentByMailTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            case 'CONTRAT_ADD_CONTACT' :
                if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type']) && isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    if (GETPOST('userid')) {
                        $contactID     = GETPOST('userid');
                        $contactSource = 'internal';
                    } else {
                        $contactID     = GETPOST('contactid');
                        $contactSource = 'external';
                    }
                    $contactTypeID      = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
                    $contactCode        = dol_getIdFromCode($this->db, $contactTypeID, 'c_type_contact', 'rowid', 'code');
                    $contactsCodeWanted = ['CUSTOMER', 'BILLING', 'TRAINEE', 'SESSIONTRAINER', 'OPCO'];

                    if (in_array($contactCode, $contactsCodeWanted) && !empty($contactID)) {
                        set_satisfaction_survey($object, $contactCode, $contactID, $contactSource);
                    }
                }
                break;

            case 'PROJECT_CREATE' :
            case 'PROJECT_MODIFY' :
                if (is_array(GETPOST('options_trainingsession_service', 'array')) && !empty(GETPOST('options_trainingsession_service', 'array'))) {
                    if (dol_strlen($object->note_public) == 0) {
                        $durations = 0;
                        if (isset($object->array_options['options_trainingsession_service']) && !empty($object->array_options['options_trainingsession_service'])) {
                            // Load DoliMeet libraries
                            require_once __DIR__ . '/../../class/trainingsession.class.php';

                            $trainingSession = new TrainingSession($this->db);

                            $object->array_options['options_trainingsession_service'] = explode(',', $object->array_options['options_trainingsession_service']);
                            foreach ($object->array_options['options_trainingsession_service'] as $trainingSessionServiceId) {
                                $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.element_type = "service" AND t.fk_element = ' . $trainingSessionServiceId]);
                                if (is_array($trainingSessions) && !empty($trainingSessions)) {
                                    foreach ($trainingSessions as $trainingSession) {
                                        $durations += $trainingSession->duration;
                                    }
                                }
                            }
                        }

                        $object->note_public  = '<br />' . $langs->transnoentities('FormationInfoTitle') . '<br />';
                        $object->note_public .= $langs->transnoentities('FormationTitle') . ' : ' . $object->title . '<br />';
                        $object->note_public .= $langs->transnoentities('TrainingSessionType') . ' : ' . $langs->transnoentities(getDictionaryValue('c_trainingsession_type', 'ref', $object->array_options['options_trainingsession_type'])) . '<br />';
                        $object->note_public .= $langs->transnoentities('TrainingSessionDurations') . ' : <strong>' . convertSecondToTime($durations) . '</strong>' . ' ' . dol_strtolower($langs->transnoentities('Hours')) . '<br />';
                        $object->note_public .= $langs->transnoentities('TrainingSessionLocation') . ' : ' . (dol_strlen($object->array_options['options_trainingsession_location']) > 0  ? $object->array_options['options_trainingsession_location'] : $langs->transnoentities('NoData')) . '<br />';
                        $object->note_public .= $langs->transnoentities('TrainingSessionNbTrainees') . ' : ' . (dol_strlen($object->array_options['options_trainingsession_nb_trainees']) > 0 ? $object->array_options['options_trainingsession_nb_trainees'] : $langs->transnoentities('NoData')) . '<br /><br />';

                        if (isset($object->array_options['options_trainingsession_service']) && !empty($object->array_options['options_trainingsession_service'])) {
                            // Load DoliMeet libraries
                            require_once __DIR__ . '/../../class/trainingsession.class.php';

                            $trainingSession = new TrainingSession($this->db);

                            foreach ($object->array_options['options_trainingsession_service'] as $trainingSessionServiceId) {
                                $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.element_type = "service" AND t.fk_element = ' . $trainingSessionServiceId]);
                                if (is_array($trainingSessions) && !empty($trainingSessions)) {
                                    $object->note_public .= $langs->transnoentities('FormationInfoTitleTest') . '<br />';
                                    $object->note_public .= $langs->transnoentities('TrainingSessionsInclusiveWriting', count($trainingSessions)) . ' : ' . '<br />';
                                    foreach ($trainingSessions as $trainingSession) {
                                        $object->note_public .= dol_print_date($trainingSession->date_start, 'day') . ' - <strong>' . $trainingSession->fields['status']['arrayofkeyval'][4] . '</strong> - ' . $trainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($trainingSession->date_start, 'hour', 'tzuser') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($trainingSession->date_end, 'hour', 'tzuser') . '</strong><br />';
                                    }
                                }
                            }
                        }

    //                    $substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object);
    //                    complete_substitutions_array($substitutionArray, $langs, $object);
    //                    $formationProjectPublicNote = make_substitutions($langs->transnoentities(getDolGlobalString('DOLIMEET_FORMATION_PROJECT_PUBLIC_NOTE')), $substitutionArray);
                        $object->note_public .= '<br />' . (dol_strlen($formationProjectPublicNote) > 0 ? $langs->transnoentities($formationProjectPublicNote) : $langs->transnoentities('FormationPublicNoteTraineeList'));

                        $object->setValueFrom('note_public', $object->note_public);
                    }
                }
                break;

            case 'PROPAL_CREATE' :
                if (is_array(GETPOST('options_trainingsession_service', 'array')) && !empty(GETPOST('options_trainingsession_service', 'array'))) {
                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $project    = new Project($this->db);
                    $product    = new Product($this->db);
                    $propalLine = new PropaleLigne($this->db);

                    // Add formation services on propal (by default LA1, RA1)
                    $formationServices = get_formation_service();
                    foreach ($formationServices as $formationService) {
                        if ($formationService['ref'] == 'F0') {
                            continue;
                        }
                        $confName = $formationService['code'];
                        $result   = $product->fetch($conf->global->$confName);

                        if ($result > 0) {
                            $propalLine->fk_propal      = $object->id;
                            $propalLine->fk_parent_line = 0;
                            $propalLine->fk_product     = $product->id;
                            $propalLine->product_label  = $product->label;
                            $propalLine->product_desc   = $product->description;
                            $propalLine->tva_tx         = 20;
                            $propalLine->date_creation  = $object->db->idate(dol_now());
                            $propalLine->qty            = 1;
                            $propalLine->rang           = $formationService['position'];
                            $propalLine->product_type   = 1;
                            $propalLine->insert($user);
                        }
                    }

                    // Add formation services on propal value stored in project options training session service
                    if (is_array(GETPOST('options_trainingsession_service', 'array')) && !empty(GETPOST('options_trainingsession_service', 'array'))) {
                        $propalLinePosition = 10;
                        foreach (GETPOST('options_trainingsession_service', 'array') as $trainingSessionServiceId) {
                            $product->fetch($trainingSessionServiceId);

                            $propalLine->fk_propal      = $object->id;
                            $propalLine->fk_parent_line = 0;
                            $propalLine->fk_product     = $product->id;
                            $propalLine->product_label  = $product->label;
                            $propalLine->product_desc   = $product->description;
                            $propalLine->tva_tx         = $product->tva_tx;
                            $propalLine->subprice       = $product->price;
                            $propalLine->date_creation  = $object->db->idate(dol_now());
                            $propalLine->qty            = 1;
                            $propalLine->rang           = $propalLinePosition++;
                            $propalLine->product_type   = 1;

                            $object->addline($propalLine->product_desc, $propalLine->subprice, $propalLine->qty, $propalLine->tva_tx, 0.0, 0.0, $propalLine->fk_product, 0.0, 'HT', 0.0, 0, $propalLine->product_type, $propalLine->rang, 0, 0, 0, 0, $propalLine->product_label, '', '', 0, null);
                        }
                    }

                    // Add project data on propal
                    $result = $project->fetch($object->fk_project);
                    if ($result > 0) {
                        // Add contact on propal form project
                        $externalContacts = $project->liste_contact();
                        $internalContacts = $project->liste_contact(-1, 'internal');
                        if ((is_array($externalContacts) && !empty($externalContacts)) || (is_array($internalContacts) && !empty($internalContacts))) {
                            $contacts = array_merge($externalContacts, $internalContacts);
                            foreach ($contacts as $contact) {
                                $object->add_contact($contact['id'], $contact['code'], $contact['source']);
                            }
                        }

                        // Add public note form project
                        $object->note_public = dol_strlen($object->note_public) > 0 ? $object->note_public . '<br /><br />' . $project->note_public : $project->note_public;
                        $object->setValueFrom('note_public', $object->note_public);
                    }
                }
                break;

            case 'CONTRACT_CREATE' :
                if (GETPOST('options_trainingsession_type', 'int') > 0) {
                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $this->db->begin();

                    $propal          = new Propal($this->db);
                    $product         = new Product($this->db);
                    $contratLigne    = new ContratLigne($this->db);
                    $project         = new Project($this->db);

                    $langs->load('propal');

                    // Add formation services on contract (by default F0)
                    $formationServices = get_formation_service();
                    foreach ($formationServices as $formationService) {
                        if ($formationService['ref'] == 'F0') {
                            $confName = $formationService['code'];
                            $result   = $product->fetch($conf->global->$confName);

                            if ($result > 0) {
                                $contratLigne->fk_contrat = $object->id;
                                $contratLigne->fk_product = $product->id;
                                $contratLigne->tva_tx     = 20;
                                $contratLigne->qty        = 1;
                                $contratLigne->rang       = 1;

                                // Mandatory because default value is empty but required
                                $contratLigne->remise_percent  = 0;
                                $contratLigne->subprice        = 0;
                                $contratLigne->total_ht        = 0;
                                $contratLigne->total_tva       = 0;
                                $contratLigne->total_localtax1 = 0;
                                $contratLigne->total_localtax2 = 0;
                                $contratLigne->total_ttc       = 0;
                                $contratLigne->price_ht        = 0;
                                $contratLigne->remise          = 0;

                                $contratLigne->insert($user);
                            }
                        }
                    }

                    $durations = 0;
                    if (isset($object->array_options['options_trainingsession_service']) && !empty($object->array_options['options_trainingsession_service'])) {
                        // Load DoliMeet libraries
                        require_once __DIR__ . '/../../class/trainingsession.class.php';

                        $trainingSession = new TrainingSession($this->db);

                        $object->array_options['options_trainingsession_service'] = explode(',', $object->array_options['options_trainingsession_service']);
                        foreach ($object->array_options['options_trainingsession_service'] as $trainingSessionServiceId) {
                            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.element_type = "service" AND t.fk_element = ' . $trainingSessionServiceId]);
                            if (is_array($trainingSessions) && !empty($trainingSessions)) {
                                foreach ($trainingSessions as $trainingSession) {
                                    $durations += $trainingSession->duration;
                                }
                            }
                        }
                    }

                    $object->note_public  = '<br />' . $langs->transnoentities('FormationInfoTitle') . '<br />';

                    $project->fetch($object->fk_project);
                    $object->note_public .= $langs->transnoentities('FormationTitle') . ' : ' . $project->title . '<br />';

                    $object->note_public .= $langs->transnoentities('TrainingSessionType') . ' : ' . $langs->transnoentities(getDictionaryValue('c_trainingsession_type', 'ref', $object->array_options['options_trainingsession_type'])) . '<br />';
                    $object->note_public .= $langs->transnoentities('TrainingSessionDurations') . ' : <strong>' . convertSecondToTime($durations) . '</strong>' . ' ' . dol_strtolower($langs->transnoentities('Hours')) . '<br />';
                    $object->note_public .= $langs->transnoentities('TrainingSessionLocation') . ' : ' . (dol_strlen($object->array_options['options_trainingsession_location']) > 0  ? $object->array_options['options_trainingsession_location'] : $langs->transnoentities('NoData')) . '<br /><br />';

                    $durations = 0;
                    if (isset($object->linked_objects['propal']) && !empty($object->linked_objects['propal'])) {
                        // Load DoliMeet libraries
                        require_once __DIR__ . '/../../class/trainingsession.class.php';

                        $trainingSession = new TrainingSession($this->db);

                        $propal->fetch($object->linked_objects['propal']);
                        if (strpos($propal->array_options['options_trainingsession_service'], ',') === false) {
                            $propal->array_options['options_trainingsession_service'] = explode(',', $propal->array_options['options_trainingsession_service']);
                        }
                        foreach ($propal->array_options['options_trainingsession_service'] as $trainingSessionServiceId) {
                            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.element_type = "service" AND t.fk_element = ' . $trainingSessionServiceId]);
                            if (is_array($trainingSessions) && !empty($trainingSessions)) {
                                $object->note_public .= $langs->transnoentities('FormationInfoTitleTest') . '<br />';
                                $object->note_public .= $langs->transnoentities('TrainingSessionsInclusiveWriting', count($trainingSessions)) . ' : ' . '<br />';
                                foreach ($trainingSessions as $trainingSession) {
                                    $trainingSession->ref           = '';
                                    $trainingSession->date_creation = dol_now();
                                    $trainingSession->status        = Session::STATUS_DRAFT;
                                    $trainingSession->modele        = false;
                                    $trainingSession->fk_soc        = $object->socid;
                                    $trainingSession->fk_project    = $object->fk_project;
                                    $trainingSession->fk_contrat    = $object->id;

                                    $trainingSession->create($user);

                                    $object->note_public .= dol_print_date($trainingSession->date_start, 'day') . ' - <strong>' . $trainingSession->fields['status']['arrayofkeyval'][$trainingSession->status] . '</strong> - ' . $trainingSession->label . ' : ' . $langs->transnoentities('HourStart') . ' : <strong>' . dol_print_date($trainingSession->date_start, 'hour', 'tzuser') . '</strong> - ' . $langs->transnoentities('HourEnd') . ' : <strong>' . dol_print_date($trainingSession->date_end, 'hour', 'tzuser') . '</strong><br />';
                                    $durations += $trainingSession->duration;
                                }
                            }
                        }
                    }

                    $internalTrainee = $object->liste_contact(-1, 'internal', 0, 'TRAINEE');
                    $externalTrainee = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
                    if ((is_array($internalTrainee) && !empty($internalTrainee)) || (is_array($externalTrainee) && !empty($externalTrainee))) {
                        $object->note_public .= $langs->transnoentities('PublicNoteTraineeList') . '<ul>';
                        $contacts = array_merge($internalTrainee, $externalTrainee);
                        foreach ($contacts as $contact) {
                            $object->note_public .= '<li>' . dol_strtoupper($contact['lastname']) . (dol_strlen($contact['firstname']) > 0 ? ', ' . ucfirst($contact['firstname']) : '') . (dol_strlen($contact['email']) > 0 ? ', ' . $contact['email'] : '') . '</li>';
                        }
                        $object->note_public .= '</ul>';
                    } else {
                        //                    $substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object);
                        //                    complete_substitutions_array($substitutionArray, $langs, $object);
                        //                    $formationProjectPublicNote = make_substitutions($langs->transnoentities(getDolGlobalString('DOLIMEET_FORMATION_PROJECT_PUBLIC_NOTE')), $substitutionArray);
                        $object->note_public .= '<br />' . (dol_strlen($formationProjectPublicNote) > 0 ? $langs->transnoentities($formationProjectPublicNote) : $langs->transnoentities('FormationPublicNoteTraineeList'));
                    }

                    if ($propal->id > 0) {
                        $object->note_public .= '<strong>' . $langs->transnoentities('Proposal') . ' : ' . $propal->ref . '</strong><ul>';
                        $object->note_public .= '<li>' . $langs->transnoentities('AmountHT') . ' : ' . price($propal->total_ht, 0, '', 1, -1, -1, 'auto') . '</li>';
                        $object->note_public .= '<li>' . $langs->transnoentities('AmountVAT') . ' : ' . price($propal->total_tva, 0, '', 1, -1, -1, 'auto') . '</li>';
                        $object->note_public .= '<li>' . $langs->transnoentities('AmountTTC') . ' : ' . price($propal->total_ttc, 0, '', 1, -1, -1, 'auto') . '</li></ul>';
                    } else {
                        $object->note_public .= $langs->transnoentities('ObjectNotFound', $langs->transnoentities('Proposal'));
                    }

                    $object->setValueFrom('note_public', $object->note_public);
                }
                break;

            case 'CONTRACT_MODIFY' :
                break;
        }

        return 0;
    }
}
