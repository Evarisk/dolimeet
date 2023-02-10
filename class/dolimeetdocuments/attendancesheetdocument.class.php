<?php
/* Copyright (C) 2022-2023 EVARISK <dev@evarisk.com>
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
 */

/**
 * \file        class/dolimeetdocuments/attendancesheetdocument.class.php
 * \ingroup     dolimeet
 * \brief       This file is a class file for AttendancesheetDocument
 */

require_once __DIR__ . '/../dolimeetdocuments.class.php';

/**
 * Class for AttendancesheetDocument
 */
class AttendancesheetDocument extends DoliSIRHDocuments
{
	/**
	 * @var string Element type of object.
	 */
	public $element = 'attendancesheetdocument';

	/**
	 * @var string String with name of icon for attendancesheetdocument. Must be the part after the 'object_' into object_attendancesheetdocument.png
	 */
	public $picto = 'attendancesheetdocument@dolimeet';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);
	}
}
