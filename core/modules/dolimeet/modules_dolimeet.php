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
 *  \file       core/modules/dolimeet/modules_dolimeet.php
 *  \ingroup    dolimeet
 *  \brief      File that contains parent class for parent class for dolimeet numbering models
 */

/**
 *  Parent class to manage numbering of Meeting
 */
abstract class ModeleNumRefDoliMeet
{
	/**
	 * @var string Error code (or message)
	 */
	public string $error = '';

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
	 * Returns the default description of the numbering template
	 *
	 * @return string Text with description
	 */
	public function info(): string
    {
		global $langs;
		$langs->load('dolimeet@dolimeet');
		return $langs->trans('NoDescription');
	}

	/**
	 * Returns an example of numbering
	 *
	 * @return string Example
	 */
	public function getExample(): string
    {
		global $langs;
		$langs->load('dolimeet@dolimeet');
		return $langs->trans('NoExample');
	}

    /**
     * Checks if the numbers already in the database do not
     * cause conflicts that would prevent this numbering working.
     *
     * @param  Object $object Object we need next value for
     * @return bool           False if conflicted, true if ok
     */
	public function canBeActivated($object): bool
    {
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
		global $langs;
		return $langs->trans('NotAvailable');
	}

	/**
	 * Returns version of numbering module
	 *
	 * @return string Value
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
