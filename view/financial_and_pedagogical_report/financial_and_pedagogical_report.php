<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    view/financial_and_pedagogical_report/financial_and_pedagogical_report.php
 * \ingroup dolimeet
 * \brief   Page to view financial_and_pedagogical_report
 */

// Load DoliMeet environment
if (!file_exists('../../dolimeet.main.inc.php')) {
    die('Include of dolimeet main fails');
}
require_once __DIR__ . '/../../dolimeet.main.inc.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Global variables definitions
global $db, $langs, $user;

// Initialize technical objects
$saturneDashboard = new SaturneDashboard($db, 'dolimeet');

// Permissions
$permissionToRead = $user->hasRight('dolimeet', 'adminpage', 'read');

// Security check
saturne_check_access($permissionToRead);

/*
 * View
 */

$title = $langs->transnoentities('FinancialAndPedagogicalReport');
saturne_header(0,'', $title, $helpUrl ?? '', '', 0, 0, [], [], '', 'mod-dolimeet bodyforlist');

$years       = [];
$currentYear = date('Y', dol_now());
$showYears   = (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
for ($i = 0; $i <= $showYears; $i++) {
    $years[] = $currentYear - $i;
}

$moreHtmlRight  = img_picto($langs->trans('Filter') . ' ' . $langs->trans('Year'), 'title_agenda', 'class="pictofixedwidth"') . Form::selectarray('search_year', $years, $currentYear, 0,0, 1, '', 0, 0, 0, '', 'maxwidth100');
$moreHtmlRight .= '<div class="wpeo-button button-square-30 select-dataset-dashboard-info" style="color: #ffffff !important;"><i class="button-icon fas fa-redo"></i></div>';

print load_fiche_titre($title, $moreHtmlRight, 'dolimeet_color.png@dolimeet');

print '<div class="fichecenter">';

$moreParams = ['LoadFinancialAndPedagogicalReportDocument' => ['all']];
$saturneDashboard->show_dashboard($moreParams);

print '</div>';

// End of page
llxFooter();
$db->close();
