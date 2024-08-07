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

            case 'PROPAL_CREATE' :
                if (GETPOSTISSET('options_trainingsession_type') && GETPOST('options_trainingsession_type', 'int') > 0) {
                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $project    = new Project($this->db);
                    $product    = new Product($this->db);
                    $propalLine = new PropaleLigne($this->db);

                    // Add formation services On propal (by default LA1, RA1)
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

                    if (GETPOSTISSET('options_trainingsession_service') && GETPOST('options_trainingsession_service', 'int') > 0) {
                        $product->fetch(GETPOST('options_trainingsession_service', 'int'));

                        $propalLine->fk_propal      = $object->id;
                        $propalLine->fk_parent_line = 0;
                        $propalLine->fk_product     = $product->id;
                        $propalLine->product_label  = $product->label;
                        $propalLine->product_desc   = $product->description;
                        $propalLine->tva_tx         = $product->tva_tx;
                        $propalLine->subprice       = $product->price;
                        $propalLine->date_creation  = $object->db->idate(dol_now());
                        $propalLine->qty            = 1;
                        $propalLine->rang           = 20;
                        $propalLine->product_type   = 1;

                        $object->addline($propalLine->product_desc, $propalLine->subprice, $propalLine->qty, $propalLine->tva_tx, 0.0, 0.0, $propalLine->fk_product, 0.0, 'HT', 0.0, 0, $propalLine->product_type, $propalLine->rang, 0, 0, 0, 0, $propalLine->product_label, '', '', 0, null);
                    }

                    $result = $project->fetch($object->fk_project);
                    if ($result > 0) {
                        $externalContacts = $project->liste_contact();
                        $internalContacts = $project->liste_contact(-1, 'internal');
                        if ((is_array($externalContacts) && !empty($externalContacts)) || (is_array($internalContacts) && !empty($internalContacts))) {
                            $contacts = array_merge($externalContacts, $internalContacts);
                            foreach ($contacts as $contact) {
                                $object->add_contact($contact['id'], $contact['code'], $contact['source']);
                            }
                        }
                    }
                }
                break;

            case 'CONTRACT_CREATE' :
                if (GETPOSTISSET('options_trainingsession_type') && GETPOST('options_trainingsession_type', 'int') > 0) {
                    // Load DoliMeet libraries
                    require_once __DIR__ . '/../../lib/dolimeet_function.lib.php';

                    $this->db->begin();

                    $propal       = new Propal($this->db);
                    $product      = new Product($this->db);
                    $contratLigne = new ContratLigne($this->db);

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

                    if (isset($object->linked_objects['propal']) && !empty($object->linked_objects['propal'])) {
                        $propal->fetch($object->linked_objects['propal']);
                    }

                    $object->note_public  = '<strong>' . $object->array_options['options_label'] . '</strong><br />';
                    $object->note_public .= $langs->transnoentities('TrainingSessionsInclusiveWriting') . ' : ' . '<br />';
                    $object->note_public .= 'SF2407-0001 - Début 30/07/2024 09:00 Fin : 30/07/2024 17:00' . '<br /><br />';
                    $object->note_public .= $langs->transnoentities('TrainingSessionDurations') . ' : ' . '<strong>' . $object->array_options['options_trainingsession_durations'] . ' ' . $langs->transnoentities('Hours') . '</strong><br />';
                    $object->note_public .= $langs->transnoentities('TrainingSessionType') . ' : ' . $langs->transnoentities(getDictionaryValue('c_trainingsession_type', 'ref', $object->array_options['options_trainingsession_type'])) . '<br />';
                    $object->note_public .= $langs->transnoentities('TrainingSessionLocation') . ' : ' . $object->array_options['options_trainingsession_location'] . '<br /><br />';
                    $object->note_public .= '<strong>' . $langs->transnoentities('TraineesInclusiveWriting') . ' : ' . '</strong><ul>';

                    $internalTrainee = $object->liste_contact(-1, 'internal', 0, 'TRAINEE');
                    $externalTrainee = $object->liste_contact(-1, 'external', 0, 'TRAINEE');
                    if ((is_array($internalTrainee) && !empty($internalTrainee)) || (is_array($externalTrainee) && !empty($externalTrainee))) {
                        $contacts = array_merge($internalTrainee, $externalTrainee);
                        foreach ($contacts as $contact) {
                            $object->note_public .= '<li>' . ucfirst($contact['firstname']) . ' ' . dol_strtoupper($contact['lastname']) . ' </li>';
                        }
                    }
                    $object->note_public .= '</ul>';

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
        }

        return 0;
    }
}
