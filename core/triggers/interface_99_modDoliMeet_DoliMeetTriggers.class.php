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
        $this->version     = '1.3.0';
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
        }
        return 0;
    }
}
