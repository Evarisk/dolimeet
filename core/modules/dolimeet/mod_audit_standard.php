<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
 *  \file       core/modules/dolimeet/mod_audit_standard.php
 *  \ingroup    dolimeet
 *  \brief      File of class to manage audit numbering rules standard
 */

require_once __DIR__ . '/modules_session.php';

/**
 *	Class to manage customer audit numbering rules standard
 */
class mod_audit_standard extends ModeleNumRefSession
{
	/**
	 * Dolibarr version of the loaded audit
	 * @var string
	 */
	public string $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

    /**
     * @var string document prefix
     */
	public string $prefix = 'AU';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

    /**
     * @var string model name
     */
	public string $name = 'Audit';

	/**
	 * Return description of numbering module
	 *
	 * @return string Text with description
	 */
	public function info(): string
    {
		global $langs;
        $langs->load('dolimeet@dolimeet');
		return $langs->trans('DolimeetAuditStandardModel', $this->prefix);
	}

	/**
	 * Return an example of numbering
	 *
	 * @return string Example
	 */
	public function getExample(): string
    {
		return $this->prefix . '2208-0001';
	}

	/**
	 * Checks if the numbers already in the database do not
	 * cause conflicts that would prevent this numbering working.
	 *
	 * @param  Object $object Object we need next value for
	 * @return bool           False if conflicted, true if ok
	 */
	public function canBeActivated($object) {
		global $conf, $langs, $db;

		$coyymm = ''; $max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' .$posindice. ') AS SIGNED)) as max';
		$sql .= ' FROM ' .MAIN_DB_PREFIX. 'dolimeet_session';
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."_______-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= ' AND entity = ' .$conf->entity;
		}

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$coyymm = substr($row[0], 0, 6); $max = $row[0];
			}
		}
		if ($coyymm && !preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm)) {
			$langs->load('errors');
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

    /**
     * Return next free value
     *
     * @param  Object      $object Object we need next value for
     * @return int|string          Value if KO, <0 if KO
     * @throws Exception
     */
	public function getNextValue($object)
    {
		global $db, $conf;

		// first we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' .$posindice. ') AS SIGNED)) as max';
		$sql .= ' FROM ' .MAIN_DB_PREFIX. 'dolimeet_session';
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= ' AND entity = ' .$conf->entity;
		}

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max = 0;
		}
		else
		{
			dol_syslog('mod_audit_standard::getNextValue', LOG_DEBUG);
			return -1;
		}

		$date = empty($object->date_creation) ?dol_now() : $object->date_creation;

		$yymm = strftime('%y%m', $date);

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf('%04s', $max + 1);
		}

		dol_syslog('mod_audit_standard::getNextValue return ' .$this->prefix.$yymm. '-' .$num);
		return $this->prefix.$yymm. '-' .$num;
	}
}
