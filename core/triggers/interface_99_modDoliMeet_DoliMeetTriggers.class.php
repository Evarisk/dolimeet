<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
		if (empty($conf->dolimeet->enabled)) {
            return 0; // If module is not enabled, we do nothing
        }

		// Data and type of action are stored into $object and $action
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $now = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype = $object->type . '@dolimeet';
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->datep       = $now;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;

        switch ($action) {
			// Meeting
			case 'SESSION_CREATE' :
				$actioncomm->code  = 'AC_' . strtoupper($object->type) . '_CREATE';
				$actioncomm->label = $langs->trans(ucfirst($object->type) . 'CreateTrigger');

				$actioncomm->create($user);
				break;
			case 'SESSION_MODIFY' :
				$actioncomm->code  = 'AC_' . strtoupper($object->type) . '_MODIFY';
				$actioncomm->label = $langs->trans(ucfirst($object->type) . 'ModifyTrigger');

				$actioncomm->create($user);
				break;

			case 'SESSION_DELETE' :
				$actioncomm->code  = 'AC_ ' . strtoupper($object->type) . '_DELETE';
				$actioncomm->label = $langs->trans(ucfirst($object->type) . 'DeleteTrigger');

				$actioncomm->create($user);
				break;

			case 'SESSION_ADDATTENDANT' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_SESSION_ADDATTENDANT';
				$actioncomm->label       = $langs->transnoentities('AddAttendantTrigger', $object->firstname . ' ' . $object->lastname);

                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_SIGNED' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_DOLIMEETSIGNATURE_SIGNED';
				$actioncomm->label       = $langs->transnoentities($object->role . 'Signed') . ' : ' . $object->firstname . ' ' . $object->lastname;

                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_PENDING_SIGNATURE' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_DOLIMEETSIGNATURE_PENDING_SIGNATURE';
				$actioncomm->label       = $langs->transnoentities('DolimeetSignaturePendingSignatureTrigger') . ' : ' . $object->firstname . ' ' . $object->lastname;

				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
				}

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_ABSENT' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_DOLIMEETSIGNATURE_ABSENT';
				$actioncomm->label       = $langs->transnoentities('DolimeetSignatureAbsentTrigger') . ' : ' . $object->firstname . ' ' . $object->lastname;

				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
				}

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_DELETED' :
				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_DOLIMEETSIGNATURE_DELETED';
				$actioncomm->label       = $langs->transnoentities('DolimeetSignatureDeletedTrigger') . ' : ' . $object->firstname . ' ' . $object->lastname;
                if ($object->element_type == 'socpeople') {
                    $actioncomm->socpeopleassigned = [$object->element_id => $object->element_id];
                }

				$actioncomm->create($user);
				break;
        }

		return 0;
	}
}
