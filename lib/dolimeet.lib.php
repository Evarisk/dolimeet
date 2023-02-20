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
 * \file    lib/dolimeet.lib.php
 * \ingroup dolimeet
 * \brief   Library files with common functions for DoliMeet
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function dolimeet_admin_prepare_head(): array
{
    // Global variables definitions
	global $langs, $conf;

    // Load translation files required by the page
	saturne_load_langs();

    // Initialize values
	$h = 0;
	$head = [];

    $head[$h][0] = dol_buildpath('/dolimeet/admin/dolimeetdocuments.php', 1);
    $head[$h][1] = '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('YourDocuments');
    $head[$h][2] = 'documents';
    $h++;

    $head[$h][0] = dol_buildpath('/dolimeet/admin/setup.php', 1);
    $head[$h][1] = '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('Settings');
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/dolimeet/admin/about.php', 1);
    $head[$h][1] = '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans('About');
    $head[$h][2] = 'about';
    $h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolimeet@dolimeet');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'dolimeet@dolimeet', 'remove');

	return $head;
}
