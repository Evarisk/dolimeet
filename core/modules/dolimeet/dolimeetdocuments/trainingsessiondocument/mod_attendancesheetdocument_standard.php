<?php
/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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
 *	\file       core/modules/dolimeet/dolimeetdocumets/trainingsessiondocument/mod_attendancesheetdocument_standard.php
 * \ingroup     dolimeet
 *	\brief      File of class to manage TrainingSessionDocument AttendanceSheetDocument numbering rules standard
 */

/**
 * 	Class to manage attendancesheetdocument numbering rules Standard
 */
class mod_attendancesheetdocument_standard
{
	/**
	 * @var string Dolibarr version of the loaded document
	 */
	public string $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Document prefix
	 */
	public string $prefix = 'ASD';

	/**
	 * @var string Model name
	 */
	public string $name = 'Loge';

	/**
	 * @var string Error code (or message)
	 */
	public string $error = '';

	/**
	 * Returns the description of the numbering model
	 *
	 * @return string Text with description
	 */
	public function info(): string
    {
		global $langs;
		$langs->load('dolimeet@dolimeet');
		return $langs->trans('DoliMeetTrainingSessionDocumentAttendancesheetDocumentStandardModel', $this->prefix);
	}

	/**
	 * Return if a module can be used or not
	 *
	 * @return bool true if module can be used
	 */
	public function isEnabled(): bool
    {
		return true;
	}

	/**
	 * Return an example of numbering
	 *
	 * @return string Example
	 */
	public function getExample(): string
	{
		return $this->prefix . '0501-0001';
	}

    /**
     *  Checks if the numbers already in the database do not
     *  cause conflicts that would prevent this document working.
     *
     *  @param  Object  $object	Object we need next value for
     *  @return bool            false if are conflict, true if ok
     */
    public function canBeActivated(object $object): bool
    {
        global $conf, $langs, $db;

        $coyymm = ''; $max = '';

        $posindice = strlen($this->prefix) + 6;
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posindice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolimeet_dolimeetdocuments';
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "____-%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
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
     * @param  Object    $object  Object we need next value for
     * @return string             Value if KO, <0 if KO
     * @throws Exception
     */
    public function getNextValue(object $object)
    {
        global $db, $conf;

        // first we get the max value
        $posindice = strlen($this->prefix) + 6;
        $sql = 'SELECT MAX(CAST(SUBSTRING(ref FROM ' . $posindice . ') AS SIGNED)) as max';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'dolimeet_dolimeetdocuments';
        $sql .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "____-%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= ' AND entity = ' . $conf->entity;
        }

        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $max = intval($obj->max);
            } else {
                $max = 0;
            }
        } else {
            dol_syslog('mod_attendancesheetdocument_standard::getNextValue', LOG_DEBUG);
            return -1;
        }

        $date = !empty($object->date_creation) ? $object->date_creation : dol_now();
        $yymm = strftime('%y%m', $date);

        if ($max >= (pow(10, 4) - 1)) {
            $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
        } else {
            $num = sprintf('%04s', $max + 1);
        }

        dol_syslog('mod_attendancesheetdocument_standard::getNextValue return ' . $this->prefix.$yymm. '-' .$num);
        return $this->prefix . $yymm . '-' . $num;
    }

    /**
     *	Returns version of document
     *
     *	@return string Value
     */
    public function getVersion(): string
    {
        global $langs;
        $langs->load('admin');

        if ($this->version == 'development') {
            return $langs->trans('VersionDevelopment');
        }
        if ($this->version == 'experimental') {
            return $langs->trans('VersionExperimental');
        }
        if ($this->version == 'dolibarr') {
            return DOL_VERSION;
        }
        if ($this->version) {
            return $this->version;
        }
        return $langs->trans('NotAvailable');
    }
}