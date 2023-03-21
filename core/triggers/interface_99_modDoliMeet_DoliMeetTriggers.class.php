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

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for DoliMeet module
 */
class InterfaceDoliMeetTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
        parent::__construct($db);

		$this->family      = 'demo';
		$this->description = 'DoliMeet triggers.';
		$this->version     = '1.0.2';
		$this->picto       = 'dolimeet@dolimeet';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName(): string
    {
        return parent::getName();
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc(): string
    {
        return parent::getDesc();
	}

    /**
     * Function called when a Dolibarr business event is done.
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
        $now = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype = $object->element . '@dolimeet';
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->datep       = $now;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;

        switch ($action) {
			case 'MEETING_CREATE' :
            case 'AUDIT_CREATE' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
				$actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
				$actioncomm->create($user);
				break;

            case 'TRAININGSESSION_CREATE' :
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

                require_once __DIR__ . '/../../class/saturnesignature.class.php';

                $contract  = new Contrat($this->db);
                $signatory = new SaturneSignature($this->db);

                $contract->fetch($object->fk_contrat);

                $contactInternalSessionTrainerArray = $contract->liste_contact(-1, 'internal', 0, 'SESSIONTRAINER');
                $contactInternalTraineeArray        = $contract->liste_contact(-1, 'internal', 0, 'TRAINEE');
                $contactExternalSessionTrainerArray = $contract->liste_contact(-1, 'external', 0, 'SESSIONTRAINER');
                $contactExternalTraineeArray        = $contract->liste_contact(-1, 'external', 0, 'TRAINEE');

                $contactArray = array_merge(
                    (is_array($contactInternalSessionTrainerArray) ? $contactInternalSessionTrainerArray : []),
                    (is_array($contactInternalTraineeArray) ? $contactInternalTraineeArray : []),
                    (is_array($contactExternalSessionTrainerArray) ? $contactExternalSessionTrainerArray : []),
                    (is_array($contactExternalTraineeArray) ? $contactExternalTraineeArray : [])
                );

                foreach ($contactArray as $contact) {
                    if ($contact['code'] == 'TRAINEE') {
                        $attendantRole = 'Trainee';
                    } else {
                        $attendantRole = 'SessionTrainer';
                    }
                    $signatory->setSignatory($object->id, $object->element, (($contact['source'] == 'internal') ? 'user' : 'socpeople'), [$contact['id']], $attendantRole, 1);
                }

                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_CREATE';
                $actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'MEETING_MODIFY' :
            case 'TRAININGSESSION_MODIFY' :
            case 'AUDIT_MODIFY' :
				$actioncomm->code  = 'AC_' . strtoupper($object->element) . '_MODIFY';
				$actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
				$actioncomm->create($user);
				break;

            case 'MEETING_DELETE' :
            case 'TRAININGSESSION_DELETE' :
            case 'AUDIT_DELETE' :
				$actioncomm->code  = 'AC_ ' . strtoupper($object->element) . '_DELETE';
				$actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
				$actioncomm->create($user);
				break;

            case 'MEETING_VALIDATE' :
            case 'TRAININGSESSION_VALIDATE' :
            case 'AUDIT_VALIDATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_VALIDATE';
                $actioncomm->label = $langs->trans('ObjectValidateTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'MEETING_UNVALIDATE' :
            case 'TRAININGSESSION_UNVALIDATE' :
            case 'AUDIT_UNVALIDATE' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_UNVALIDATE';
                $actioncomm->label = $langs->trans('ObjectUnValidateTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'MEETING_LOCKED' :
            case 'TRAININGSESSION_LOCKED' :
            case 'AUDIT_LOCKED' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_LOCKED';
                $actioncomm->label = $langs->trans('ObjectLockedTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
                $actioncomm->create($user);
                break;

            case 'MEETING_ARCHIVED' :
            case 'TRAININGSESSION_ARCHIVED' :
            case 'AUDIT_ARCHIVED' :
                $actioncomm->code  = 'AC_' . strtoupper($object->element) . '_ARCHIVED';
                $actioncomm->label = $langs->trans('ObjectArchivedTrigger', $langs->transnoentities('The' . ucfirst($object->element)));
                $actioncomm->create($user);
                break;

			case 'SATURNESIGNATURE_ADDATTENDANT' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_SATURNESIGNATURE_ADDATTENDANT';
				$actioncomm->label       = $langs->transnoentities('AddAttendantTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
				$actioncomm->create($user);
				break;
			case 'SATURNESIGNATURE_SIGNED' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_SATURNESIGNATURE_SIGNED';
				$actioncomm->label       = $langs->transnoentities('SignedTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
				$actioncomm->create($user);
                break;
            case 'SATURNESIGNATURE_SIGNED_PUBLIC' :
                $actioncomm->elementtype = $object->object_type . '@dolimeet';
                $actioncomm->code        = 'AC_SATURNESIGNATURE_SIGNED_PUBLIC';
                $actioncomm->label       = $langs->transnoentities('SignedTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->userownerid = $object->element_id;
                $actioncomm->create($user);
                break;
			case 'SATURNESIGNATURE_PENDING_SIGNATURE' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_SATURNESIGNATURE_PENDING_SIGNATURE';
				$actioncomm->label       = $langs->transnoentities('PendingSignatureTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
				}
                $actioncomm->fk_element = $object->fk_object;
				$actioncomm->create($user);
				break;
            case 'SATURNESIGNATURE_DELAY' :
                $actioncomm->elementtype = $object->object_type . '@dolimeet';
                $actioncomm->code        = 'AC_SATURNESIGNATURE_DELAY';
                $actioncomm->label       = $langs->transnoentities('DelayTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
                $actioncomm->create($user);
                break;
			case 'SATURNESIGNATURE_ABSENT' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_SATURNESIGNATURE_ABSENT';
				$actioncomm->label       = $langs->transnoentities('AbsentTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
				}
                $actioncomm->fk_element = $object->fk_object;
				$actioncomm->create($user);
				break;
			case 'SATURNESIGNATURE_DELETED' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_SATURNESIGNATURE_DELETED';
				$actioncomm->label       = $langs->transnoentities('DeletedTrigger', $langs->trans($object->role) . ' ' . strtoupper($object->lastname) . ' ' . $object->firstname);
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }
                $actioncomm->fk_element = $object->fk_object;
				$actioncomm->create($user);
				break;
        }
		return 0;
	}
}