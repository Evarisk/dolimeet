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
 * Load list of objects in memory from the database.
 *
 * @param  string      $sortorder    Sort Order
 * @param  string      $sortfield    Sort field
 * @param  int         $limit        limit
 * @param  int         $offset       Offset
 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
 * @param  string      $filtermode   Filter mode (AND or OR)
 * @return array|int                 int <0 if KO, array of pages if OK
 * @throws Exception
 */
function fetchAllSocPeople($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
{
	global $db;

	dol_syslog(__METHOD__, LOG_DEBUG);

	$records = array();
	$errors  = array();

	$sql  = "SELECT c.rowid, c.entity, c.fk_soc, c.ref_ext, c.civility as civility_code, c.lastname, c.firstname,";
	$sql .= " c.address, c.statut, c.zip, c.town,";
	$sql .= " c.fk_pays as country_id,";
	$sql .= " c.fk_departement as state_id,";
	$sql .= " c.birthday,";
	$sql .= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email,";
	$sql .= " c.socialnetworks,";
	$sql .= " c.photo,";
	$sql .= " c.priv, c.note_private, c.note_public, c.default_lang, c.canvas,";
	$sql .= " c.fk_prospectcontactlevel, c.fk_stcommcontact, st.libelle as stcomm, st.picto as stcomm_picto,";
	$sql .= " c.import_key,";
	$sql .= " c.datec as date_creation, c.tms as date_modification,";
	$sql .= " co.label as country, co.code as country_code,";
	$sql .= " d.nom as state, d.code_departement as state_code,";
	$sql .= " u.rowid as user_id, u.login as user_login,";
	$sql .= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
	$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co ON c.fk_pays = co.rowid";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_departements as d ON c.fk_departement = d.rowid";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON c.rowid = u.fk_socpeople";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid";
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_stcommcontact as st ON c.fk_stcommcontact = st.id';
	$sql .= " WHERE c.entity IN (" . getEntity('socpeople') . ")";
	// Manage filter
	$sqlwhere = array();
	if (count($filter) > 0) {
		foreach ($filter as $key => $value) {
			if ($key == 't.rowid') {
				$sqlwhere[] = $key . '=' . $value;
			} elseif (strpos($key, 'date') !== false) {
				$sqlwhere[] = $key . ' = \'' . $db->idate($value) . '\'';
			} elseif ($key == 'customsql') {
				$sqlwhere[] = $value;
			} else {
				$sqlwhere[] = $key . ' LIKE \'%' . $db->escape($value) . '%\'';
			}
		}
	}
	if (count($sqlwhere) > 0) {
		$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
	}

	if ( ! empty($sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
	}
	if ( ! empty($limit)) {
		$sql .= ' ' . $db->plimit($limit, $offset);
	}
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i   = 0;
		while ($i < ($limit ? min($limit, $num) : $num)) {
			$obj = $db->fetch_object($resql);

			$record = new Contact($db);
			$record->setVarsFromFetchObj($obj);

			$records[$record->id] = $record;

			$i++;
		}
		$db->free($resql);

		return $records;
	} else {
		$errors[] = 'Error ' . $db->lasterror();
		dol_syslog(__METHOD__ . ' ' . join(',', $errors), LOG_ERR);

		return -1;
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
