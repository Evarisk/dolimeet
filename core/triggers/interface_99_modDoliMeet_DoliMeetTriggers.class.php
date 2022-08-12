<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "DoliMeet triggers.";
		$this->version = '1.0.2';
		$this->picto = 'dolimeet@dolimeet';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		//echo '<pre>'; print_r( $conf ); echo '</pre>'; exit;
		if (empty($conf->dolimeet->enabled)) return 0; // If module is not enabled, we do nothing

		// Data and type of action are stored into $object and $action
		switch ($action) {

			// Meeting
			case 'MEETING_CREATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'meeting@dolimeet';
				$actioncomm->code        = 'AC_MEETING_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('MeetingCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				break;

			case 'MEETING_MODIFY' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'meeting@dolimeet';
				$actioncomm->code        = 'AC_MEETING_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('MeetingModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'MEETING_DELETE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'meeting@dolimeet';
				$actioncomm->code        = 'AC_MEETING_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('MeetingDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			// TrainingSession
			case 'TRAININGSESSION_CREATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'trainingsession@dolimeet';
				$actioncomm->code        = 'AC_TRAININGSESSION_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('TrainingSessionCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				break;

			case 'TRAININGSESSION_MODIFY' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'trainingsession@dolimeet';
				$actioncomm->code        = 'AC_TRAININGSESSION_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('TrainingSessionModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'TRAININGSESSION_DELETE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'trainingsession@dolimeet';
				$actioncomm->code        = 'AC_TRAININGSESSION_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('TrainingSessionDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			// Audit
			case 'AUDIT_CREATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'audit@dolimeet';
				$actioncomm->code        = 'AC_AUDIT_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('AuditCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				break;

			case 'AUDIT_MODIFY' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'audit@dolimeet';
				$actioncomm->code        = 'AC_AUDIT_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('AuditModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'AUDIT_DELETE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'audit@dolimeet';
				$actioncomm->code        = 'AC_AUDIT_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('AuditDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;


			case 'SESSION_ADDATTENDANT' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@dolimeet';
				$actioncomm->code              = 'AC_SESSION_ADDATTENDANT';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('AddAttendantTrigger', $object->firstname . ' ' . $object->lastname);
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_SIGNED' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->object_type . '@dolimeet';
				$actioncomm->code        = 'AC_DOLIMEETSIGNATURE_SIGNED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';

				$actioncomm->label = $langs->transnoentities($object->role . 'Signed') . ' : ' . $object->firstname . ' ' . $object->lastname;

				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_PENDING_SIGNATURE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@dolimeet';
				$actioncomm->code              = 'AC_DOLIMEETSIGNATURE_PENDING_SIGNATURE';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('DolimeetSignaturePendingSignatureTrigger') . ' : ' . $object->firstname . ' ' . $object->lastname;
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_ABSENT' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@dolimeet';
				$actioncomm->code              = 'AC_DOLIMEETSIGNATURE_ABSENT';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('DigiriskSignatureAbsentTrigger') . ' : ' . $object->firstname . ' ' . $object->lastname;
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$actioncomm->create($user);
				break;

			case 'DOLIMEETSIGNATURE_DELETED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@dolimeet';
				$actioncomm->code              = 'AC_DOLIMEETSIGNATURE_DELETED';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('DigiriskSignatureDeletedTrigger') . ' : ' . $object->firstname . ' ' . $object->lastname;
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$actioncomm->create($user);
				break;

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}


		return 0;
	}
}
