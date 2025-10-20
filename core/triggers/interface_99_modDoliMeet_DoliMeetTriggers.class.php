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
     * Function called when a Dolibarr business event is done
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param  string       $action Event action code
     * @param  CommonObject $object Object
     * @param  User         $user   Object user
     * @param  Translate    $langs  Object langs
     * @param  Conf         $conf   Object conf
     * @return int                  0 < if KO, 0 if no triggered ran, >0 if OK
     * @throws Exception
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
    {
        if (!isModEnabled('dolimeet')) {
            return 0; // If module is not enabled, we do nothing
        }

        // Data and type of action are stored into $object and $action
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

        $actionComm = new ActionComm($this->db);

        $triggerType = dol_ucfirst(dol_strtolower(explode('_', $action)[1]));

        $actionComm->code        = 'AC_' . $action;
        $actionComm->type_code   = 'AC_OTH_AUTO';
        $actionComm->fk_element  = $object->id;
        $actionComm->elementtype = $object->element . '@' . $object->module;
        $actionComm->label       = $langs->transnoentities('Object' . $triggerType . 'Trigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
        $actionComm->datep       = dol_now();
        $actionComm->userownerid = $user->id;
        $actionComm->percentage  = -1;

        if (getDolGlobalInt('DOLIMEET_ADVANCED_TRIGGER') && !empty($object->fields)) {
            $actionComm->note_private = method_exists($object, 'getTriggerDescription') ? $object->getTriggerDescription($object) : '';
        }

        $objects      = ['MEETING', 'TRAININGSESSION', 'AUDIT'];
        $triggerTypes = ['CREATE', 'MODIFY', 'DELETE', 'VALIDATE', 'UNVALIDATE', 'LOCK', 'ARCHIVE', 'SENTBYMAIL'];
        $extraActions = [];

        $actions = array_merge(
            array_merge(...array_map(fn($s) => array_map(fn($p) => "{$p}_{$s}", $objects), $triggerTypes)),
            $extraActions
        );

        if (in_array($action, $actions, true)) {
            $actionComm->create($user);
        }

        switch ($action) {
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
                break;

            case 'TRAININGSESSION_VALIDATE' :
                if ($object->fk_contrat > 0) {
                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $contract = new Contrat($this->db);

                    $contract->fetch($object->fk_contrat);
                    $contract->fetchObjectLinked(null, 'propal', $contract->id, 'contrat');

                    if (!empty($contract->linkedObjects['propal']) && count($contract->linkedObjects['propal']) == 1) {
                        $propal = array_shift($contract->linkedObjects['propal']);
                        set_public_note($contract, $propal);
                    }
                }
                break;

            case 'CONTRAT_ADD_CONTACT' :
                if (isset($object->array_options['options_trainingsession_type']) && !empty($object->array_options['options_trainingsession_type'])) {
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $contactCode = '';
                    if ($object->context['createformpubliccontact'] ?? '' === 'createformpubliccontact') {
                        $contactID     = $object->contact_id;
                        $contactSource = 'external';
                        $contactCode   = 'TRAINEE';
                    } elseif (GETPOST('userid')) {
                        $contactID     = GETPOST('userid');
                        $contactSource = 'internal';
                    } else {
                        $contactID     = GETPOST('contactid');
                        $contactSource = 'external';
                    }

                    if (empty($contactCode)) {
                        $contactTypeID = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
                        $contactCode   = dol_getIdFromCode($this->db, $contactTypeID, 'c_type_contact', 'rowid', 'code');
                    }

                    if (isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
                        $contactsCodeWanted = ['SESSIONTRAINER', 'TRAINEE', 'CUSTOMER', 'BILLING'];
                        if (in_array($contactCode, $contactsCodeWanted) && !empty($contactID)) {
                            set_satisfaction_survey($object, $contactCode, $contactID, $contactSource);
                        }
                    }

                    if ($contactCode == 'TRAINEE') {
                        $object->fetchObjectLinked(null, 'propal', $object->id, 'contrat');
                        if (isset($object->linkedObjects['propal']) && !empty($object->linkedObjects['propal']) && count($object->linkedObjects['propal']) == 1) {
                            $propal = array_shift($object->linkedObjects['propal']);
                            set_public_note($object, $propal, '');
                        }
                    }

                    if ($contactCode == 'TRAINEE' || $contactCode == 'SESSIONTRAINER') {
                        require_once __DIR__ . '/../../class/trainingsession.class.php';
                        require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

                        $trainingSession  = new Trainingsession($this->db);
                        $signatory        = new SaturneSignature($this->db, 'dolimeet', $trainingSession->element);

                        $trainingSessions = $trainingSession->fetchAll('', '', 0, 0, ['customsql' => 't.status = ' . Session::STATUS_DRAFT . ' AND t.fk_contrat = ' . $object->id]);
                        if (is_array($trainingSessions) && !empty($trainingSessions)) {
                            foreach ($trainingSessions as $trainingSession) {
                                $signatory->setSignatory($trainingSession->id,  $trainingSession->element, (($contactSource == 'internal') ? 'user' : 'socpeople'), [$contactID], (($contactCode == 'TRAINEE') ? 'Trainee' : 'SessionTrainer'), 1);
                            }
                        }
                    }
                }
                break;

            case 'PROPAL_CREATE' :
                if (GETPOST('options_trainingsession_type', 'int') > 0) {

                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $product    = new Product($this->db);
                    $propalLine = new PropaleLigne($this->db);

                    // Add formation services on propal (by default FOR_ADM_GP1, FOR_ADM_LA1)
                    $error             = 0;
                    $formationServices = get_formation_service();
                    foreach ($formationServices as $formationService) {
                        if ($formationService['ref'] == 'FOR_ADM_CF1' || $formationService['ref'] == 'FOR_ADM_RI1') {
                            continue;
                        }
                        $confName = $formationService['code'];
                        $result   = $product->fetch(getDolGlobalInt($confName));

                        if ($result > 0) {
                            $propalLine->fk_propal      = $object->id;
                            $propalLine->fk_parent_line = 0;
                            $propalLine->fk_product     = $product->id;
                            $propalLine->product_label  = $product->label;
                            $propalLine->desc           = $product->description;
                            $propalLine->tva_tx         = $product->tva_tx;
                            $propalLine->qty            = 1;
                            $propalLine->rang           = $formationService['position'];
                            $propalLine->product_type   = 1;

                            $object->addline($propalLine->desc, $propalLine->subprice, $propalLine->qty, $propalLine->tva_tx, 0.0, 0.0, $propalLine->fk_product, 0.0, 'HT', 0.0, 0, $propalLine->product_type, $propalLine->rang, 0, 0, 0, 0, $propalLine->product_label, '', '', 0, null);
                        } else {
                            $error++;
                        }
                    }

                    if ($error > 0) {
                        setEventMessages('ErrorMissingFormationServiceConfig', [], 'errors');
                        return -1;
                    }
                }
                break;

            case 'CONTRACT_CREATE' :
                if (GETPOST('options_trainingsession_type', 'int') > 0) {
                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $propal       = new Propal($this->db);
                    $product      = new Product($this->db);
                    $contratLigne = new ContratLigne($this->db);
                    $project      = new Project($this->db);

                    $langs->load('propal');

                    // Add formation services on contract (by default FOR_ADM_CF1, FOR_ADM_RI1)
                    $formationServices = get_formation_service();
                    foreach ($formationServices as $formationService) {
                        if ($formationService['ref'] == 'FOR_ADM_CF1' || $formationService['ref'] == 'FOR_ADM_RI1') {
                            $confName = $formationService['code'];
                            $result   = $product->fetch(getDolGlobalInt($confName));

                            if ($result > 0) {
                                $contratLigne->description = $product->description;
                                $contratLigne->subprice    = $product->price;
                                $contratLigne->qty         = 1;
                                $contratLigne->tva_tx      = $product->tva_tx;
                                $contratLigne->fk_product  = $product->id;
                                $contratLigne->rang        = $formationService['position'];

                                $object->addline($contratLigne->description, $contratLigne->subprice, $contratLigne->qty, $contratLigne->tva_tx, 0.0, 0.0, $contratLigne->fk_product, 0.0, '', '', 'HT', 0.0, 0, null, 0, [], null, $contratLigne->rang);
                            }
                        }
                    }

                    // Create training session from propal
                    if (isset($object->linked_objects['propal']) && !empty($object->linked_objects['propal'])) {
                        // Load DoliMeet libraries
                        require_once __DIR__ . '/../../class/trainingsession.class.php';
                        require_once __DIR__ . '/../../lib/dolimeet_trainingsession.lib.php';

                        $trainingSession = new Trainingsession($this->db);

                        $productIds = trainingsession_function_lib1();
                        if (!is_array($productIds) || empty($productIds)) {
                            setEventMessages($langs->transnoentities('ErrorMissingFormationServiceConfig'), [], 'errors');
                            return -1;
                        }

                        $propal->fetch($object->linked_objects['propal']);
                        if (!is_array($propal->lines) || empty($propal->lines)) {
                            setEventMessages($langs->transnoentities('Error1'), [], 'errors');
                            return -1;
                        }
                        foreach ($propal->lines as $line) {
                            if (!in_array($line->fk_product, array_keys($productIds))) {
                                continue;
                            }

                            $trainingSessions = $trainingSession->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = 1 AND t.model = 1 AND t.element_type = \'service\' AND t.fk_element = ' . $line->fk_product]);
                            if (!is_array($trainingSessions) || empty($trainingSessions)) {
                                setEventMessages($langs->transnoentities('Error2'), [], 'errors');
                                return -1;
                            }

                            foreach ($trainingSessions as $trainingSession) {
                                $trainingSession->ref           = '';
                                $trainingSession->date_creation = dol_now();
                                $trainingSession->status        = Session::STATUS_DRAFT;
                                $trainingSession->element_type  = null;
                                $trainingSession->fk_element    = '';
                                $trainingSession->model         = false;
                                $trainingSession->position      = null;
                                $trainingSession->fk_soc        = $object->socid;
                                $trainingSession->fk_project    = $object->fk_project;
                                $trainingSession->fk_contrat    = $object->id;

                                $trainingSession->create($user);
                                $trainingSession->validate($user);
                            }
                        }

                        set_public_note($object, $propal, 'CONTRACT_CREATE');
                    }

                    $object->validate($user);
                }
                break;
        }

        return 0;
    }
}
