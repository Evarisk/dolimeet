<?php
/* Copyright (C) 2021 EVARISK <dev@evarisk.com>
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
 * \file        class/audit.class.php
 * \ingroup     dolimeet
 * \brief       This file is a CRUD class file for Document (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once __DIR__ . '/dolimeetsignature.class.php';
require_once __DIR__ . '/session.class.php';

/**
 * Class for Audit
 */
class Audit extends Session
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'dolimeet';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'audit';

	/**
	 * @var string String with name of icon for document. Must be the part after the 'object_' into object_document.png
	 */
	public $picto = 'audit@dolimeet';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->type = 'audit';

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']        = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}
}

/**
 * Class AuditSignature
 */

class AuditSignature extends DolimeetSignature
{
	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */

	public $object_type = 'audit';

	/**
	 * @var array Context element object
	 */
	public $context = array();

	/**
	 * @var string String with name of icon for document. Must be the part after the 'object_' into object_document.png
	 */
	public $picto = 'audit@dolimeet';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']        = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @param $auditid
	 * @return    mixed                New object created, <0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, $fromid, $auditid)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);
		unset($object->signature);
		unset($object->signature_date);
		unset($object->last_email_sent_date);

		// Clear fields
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'fk_object')) {
			$object->fk_object = $auditid;
		}
		if (property_exists($object, 'status')) {
			$object->status = 1;
		}
		if (property_exists($object, 'signature_url')) {
			$object->signature_url = generate_random_id(16);
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result                             = $object->createCommon($user);
		unset($object->context['createfromclone']);

		// End
		if ( ! $error) {
			$this->db->commit();
			return $result;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
