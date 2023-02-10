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
 * \file    lib/dolimeet_functions.lib.php
 * \ingroup dolimeet
 * \brief   Library files with common functions for DoliMeet
 */

/**
 * Show header for public page signature
 *
 * @param  string $title       Title
 * @param  string $head        Head array
 * @param  int    $disablejs   More content into html header
 * @param  int    $disablehead More content into html header
 * @param  array  $arrayofjs   Array of complementary js files
 * @param  array  $arrayofcss  Array of complementary css files
 * @return void
 */
function llxHeaderSignature($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = array(), $arrayofcss = array())
{
	global $conf, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, 1); // Show html headers

	if ( ! empty($conf->global->DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO)) {
		// Define logo and logosmall
		$logosmall = $mysoc->logo_small;
		$logo      = $mysoc->logo;
		// Define urllogo
		$urllogo = '';
		if ( ! empty($logosmall) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $logosmall)) {
			$urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/thumbs/' . $logosmall);
		} elseif ( ! empty($logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $logo)) {
			$urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/' . $logo);
		}
		// Output html code for logo
		if ($urllogo) {
			print '<div class="center signature-logo">';
			print '<img src="' . $urllogo . '">';
			print '</div>';
		}
		print '<div class="underbanner clearboth"></div>';
	}
}

/**
 *  Load dictionnary from database
 *
 * 	@param  int       $parent_id
 *	@param  int       $limit
 * 	@return array|int             <0 if KO, >0 if OK
 */
function fetchDictionnary($tablename)
{
	global $db;

	$sql  = 'SELECT t.rowid, t.entity, t.ref, t.label, t.description, t.active';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . $tablename . ' as t';
	$sql .= ' WHERE 1 = 1';
	$sql .= ' AND entity IN (0, ' . getEntity($tablename) . ')';

	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$records = array();
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$record = new stdClass();

			$record->id          = $obj->rowid;
			$record->entity      = $obj->entity;
			$record->ref         = $obj->ref;
			$record->label       = $obj->label;
			$record->description = $obj->description;
			$record->active      = $obj->active;

			$records[$record->id] = $record;

			$i++;
		}

		$db->free($resql);

		return $records;
	} else {
		return -1;
	}
}
