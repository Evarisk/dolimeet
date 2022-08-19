<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *   	\file       view/openinghours_card.php
 *		\ingroup    dolimeet
 *		\brief      Page to view Opening Hours
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

require_once './../class/openinghours.class.php';

$langs->loadLangs(array("dolimeet@dolimeet"));

$action = (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');

$socid                                          = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) $socid                        = $user->socid;
if (empty($socid) && $action == 'view') $action = 'create';

switch (GETPOST('element_type')) {
	case 'societe':
		$objectLinked = new Societe($db);
		break;
	case 'contrat':
		$objectLinked = new Contrat($db);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartyopeninghours', 'globalcard'));

// Security check

/*
/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$object               = new Openinghours($db);
	$object->element_type = GETPOST('element_type');
	$object->element_id   = GETPOST('id');
	$object->status       = 1;
	$object->monday       = GETPOST('monday', 'string');
	$object->tuesday      = GETPOST('tuesday', 'string');
	$object->wednesday    = GETPOST('wednesday', 'string');
	$object->thursday     = GETPOST('thursday', 'string');
	$object->friday       = GETPOST('friday', 'string');
	$object->saturday     = GETPOST('saturday', 'string');
	$object->sunday       = GETPOST('sunday', 'string');
	$object->create($user);
	setEventMessages($langs->trans('ThirdPartyOpeningHoursSave'), null, 'mesgs');
}


/*
 *  View
 */

$object = new Openinghours($db);

$morewhere  = ' AND element_id = ' . GETPOST('id');
$morewhere .= ' AND element_type = ' . "'" . GETPOST('element_type') . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

$title = $langs->trans("ThirdParty");
if ( ! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name . " - " . $langs->trans('OpeningHours');
$help_url = 'EN:Module_Third_Parties|FR:Module_DigiriskDolibarr#L.27onglet_Horaire_de_travail|ES:Empresas';

$morecss = array("/dolimeet/css/dolimeet.css");

llxHeader('', $title, $help_url, '', '', '', array(), $morecss);

// Object card
// ------------------------------------------------------------
$morehtmlref  = '<div class="refidno">';
$morehtmlref .= '</div>';

switch (GETPOST('element_type')) {
	case 'societe':
		break;
	case 'contrat':
		$prepareHead = 'contract_prepare_head';
}

$objectLinked->fetch(GETPOST('id'));
$head = $prepareHead($objectLinked);

print dol_get_fiche_head($head, 'openinghours', $langs->trans(ucfirst(GETPOST('element_type'))), 0, GETPOST('element_type'));
dol_banner_tab($objectLinked, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print dol_get_fiche_end();

print load_fiche_titre($langs->trans(ucfirst(GETPOST('element_type')) . "OpeningHours"), '', '');

//Show common fields

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . GETPOST('id') . '" >';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="' . GETPOST('id') . '">';
print '<input type="hidden" name="element_type" value="' . GETPOST('element_type') . '">';

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("Day") . '</th><th>' . $langs->trans("Value") . '</th></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Monday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="monday" id="monday" class="minwidth100" value="' . ($object->monday ?: GETPOST("monday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Tuesday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="tuesday" id="tuesday" class="minwidth100" value="' . ($object->tuesday ?: GETPOST("tuesday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Wednesday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="wednesday" id="wednesday" class="minwidth100" value="' . ($object->wednesday ?: GETPOST("wednesday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Thursday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="thursday" id="thursday" class="minwidth100" value="' . ($object->thursday ?: GETPOST("thursday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Friday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="friday" id="friday" class="minwidth100" value="' . ($object->friday ?: GETPOST("friday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Saturday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="saturday" id="saturday" class="minwidth100" value="' . ($object->saturday ?: GETPOST("saturday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Sunday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="sunday" id="sunday" class="minwidth100" value="' . ($object->sunday ?: GETPOST("sunday", 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>' . "\n";

print '</table>';


if ($status < 2 ) {
	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</div>';
}
print '<br>';

print '</form>';

// End of page
llxFooter();
$db->close();
