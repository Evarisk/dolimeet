<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 Theo David <theodavid.perso@gmail.com>
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
 * 	\defgroup   dolimeet     Module DoliMeet
 *  \brief      DoliMeet module descriptor.
 *
 *  \file       htdocs/dolimeet/core/modules/modDoliMeet.class.php
 *  \ingroup    dolimeet
 *  \brief      Description and activation file for module DoliMeet
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DoliMeet
 */
class modDoliMeet extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;
		$this->numero = 500000; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		$this->rights_class = 'dolimeet';
		$this->family = "other";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "DoliMeetDescription";
		$this->descriptionlong = "DoliMeetDescription";
		$this->editor_name = 'Evarisk';
		$this->editor_url = 'https://www.evarisk.com';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'dolimeet@dolimeet';
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/dolimeet/css/dolimeet.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/dolimeet/js/dolimeet.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'category',
				'categoryindex',
				'projectOverview',
				'printOverviewDetail'
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/dolimeet/temp","/dolimeet/subdir");
		$this->dirs = array("/dolimeet/temp");

		// Config pages. Put here list of php page, stored into dolimeet/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@dolimeet");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array('modCategorie', 'modContrat', 'modProjet', 'modFckeditor', 'modAgenda');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("dolimeet@dolimeet");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'DoliMeetWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('DOLIMEET_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('DOLIMEET_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			1 => array('DOLIMEET_MEETING_ADDON','chaine', 'mod_meeting_standard','', $conf->entity),
			2 => array('DOLIMEET_MEETING_MENU_ENABLED','integer', 1,'', $conf->entity),

			3 => array('DOLIMEET_TRAININGSESSION_ADDON','chaine', 'mod_trainingsession_standard','', $conf->entity),
			4 => array('DOLIMEET_TRAININGSESSION_MENU_ENABLED','integer', 1,'', $conf->entity),

			5 => array('DOLIMEET_AUDIT_ADDON','chaine', 'mod_audit_standard','', $conf->entity),
			6 => array('DOLIMEET_AUDIT_MENU_ENABLED','integer', 1,'', $conf->entity),

			7 => array('DOLIMEET_ATTENDANCESHEET_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/dolimeet/documents/doctemplates/attendancesheet/', '', 0, 'current'),
			8 => array('DOLIMEET_COMPLETIONCERTIFICATE_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/dolimeet/documents/doctemplates/completioncertificate/', '', 0, 'current'),

		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->dolimeet) || !isset($conf->dolimeet->enabled)) {
			$conf->dolimeet = new stdClass();
			$conf->dolimeet->enabled = 0;
		}

		// Array to add new pages in new tabs
		$langs->load("dolimeet@dolimeet");
		$pictopath = dol_buildpath('/custom/dolimeet/img/dolimeet32px.png', 1);
		$picto = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoDolimeet');

		$this->tabs = array();
		$this->tabs[] = array('data' => 'thirdparty:+sessionList:'.$picto.$langs->trans('DoliMeet').':dolimeet@dolimeet:1:/custom/dolimeet/view/session/session_list.php?fromtype=thirdparty&fromid=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'user:+sessionList:'.$picto.$langs->trans('DoliMeet').':dolimeet@dolimeet:1:/custom/dolimeet/view/session/session_list.php?fromtype=user&fromid=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'contact:+sessionList:'.$picto.$langs->trans('DoliMeet').':dolimeet@dolimeet:1:/custom/dolimeet/view/session/session_list.php?fromtype=socpeople&fromid=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'project:+sessionList:'.$picto.$langs->trans('DoliMeet').':dolimeet@dolimeet:1:/custom/dolimeet/view/session/session_list.php?fromtype=project&fromid=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'contract:+sessionList:'.$picto.$langs->trans('DoliMeet').':dolimeet@dolimeet:1:/custom/dolimeet/view/session/session_list.php?fromtype=contrat&fromid=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'contract:+openinghours:'.$picto.$langs->trans('OpeningHours').':dolimeet@dolimeet:1:/custom/dolimeet/view/openinghours_card.php?element_type=contrat&id=__ID__'); // To add a new tab identified by code tabname1

		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'dolimeet@dolimeet',
			// List of tables we want to see into dictonnary editor
			'tabname' => array(
				MAIN_DB_PREFIX . "c_trainingsession_type"
			),
			// Label of tables
			'tablib' => array(
				"TrainingSession"
			),
			// Request to select fields
			'tabsql' => array(
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM ' . MAIN_DB_PREFIX . 'c_trainingsession_type as f'
			),
			// Sort order
			'tabsqlsort' => array(
				"label ASC"
			),
			// List of fields (result of select to show dictionary)
			'tabfield' => array(
				"ref,label,description"
			),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue' => array(
				"ref,label,description"
			),
			// List of fields (list of fields for insert)
			'tabfieldinsert' => array(
				"ref,label,description"
			),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid' => array(
				"rowid"
			),
			// Condition to show each dictionary
			'tabcond' => array(
				$conf->dolimeet->enabled,
			)
		);
		// Boxes/Widgets
		$this->boxes = array(
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array(
		);

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadDoliMeetSession'); // Permission label
		$this->rights[$r][4] = 'session';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolimeet->session->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('WriteDoliMeetSession'); // Permission label
		$this->rights[$r][4] = 'session';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolimeet->session->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteDoliMeetSession'); // Permission label
		$this->rights[$r][4] = 'session';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolimeet->session->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadDoliMeetMeeting'); // Permission label
		$this->rights[$r][4] = 'meeting';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolimeet->meeting->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('WriteDoliMeetMeeting'); // Permission label
		$this->rights[$r][4] = 'meeting';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolimeet->meeting->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteDoliMeetMeeting'); // Permission label
		$this->rights[$r][4] = 'meeting';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolimeet->meeting->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadDoliMeetTrainingSession'); // Permission label
		$this->rights[$r][4] = 'trainingsession';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolimeet->trainingsession->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('WriteDoliMeetTrainingSession'); // Permission label
		$this->rights[$r][4] = 'trainingsession';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolimeet->trainingsession->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteDoliMeetTrainingSession'); // Permission label
		$this->rights[$r][4] = 'trainingsession';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolimeet->meeting->delete)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadDoliMeetAudit'); // Permission label
		$this->rights[$r][4] = 'audit';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->dolimeet->audit->read)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('WriteDoliMeetAudit'); // Permission label
		$this->rights[$r][4] = 'audit';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->dolimeet->audit->write)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteDoliMeetAudit'); // Permission label
		$this->rights[$r][4] = 'audit';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->dolimeet->meeting->delete)
		$r++;		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'ModuleDoliMeetName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'',
			'url'=>'/dolimeet/dolimeetindex.php',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->dolimeet->enabled', // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->myobject->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'<i class="fas fa-list"></i> '. $langs->trans('MeetingList'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'meeting_list',
			'url'=>'/dolimeet/view/meeting/meeting_list.php',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->dolimeet->enabled && $conf->global->DOLIMEET_MEETING_MENU_ENABLED',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet,fk_leftmenu=meeting_list',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=> '<i class="fas fa-comments"></i> ' . $langs->trans('MeetingCreate'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'meeting_card',
			'url'=>'/dolimeet/view/meeting/meeting_card.php?action=create',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->dolimeet->enabled && $conf->global->DOLIMEET_MEETING_MENU_ENABLED',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolimeet,fk_leftmenu=meeting_list',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tags"></i>  ' . $langs->trans('Categories'),
			'mainmenu' => 'dolimeet',
			'leftmenu' => 'dolimeet_meeting',
			'url'      => '/categories/index.php?type=meeting',
			'langs'    => 'ticket',
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolimeet->enabled && $conf->categorie->enabled && $conf->global->DOLIMEET_MEETING_MENU_ENABLED',
			'perms'    => '1',
			'target'   => '',
			'user'     => 0,
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'<i class="fas fa-list"></i> '. $langs->trans('TrainingSessionList'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'trainingsession_list',
			'url'=>'/dolimeet/view/trainingsession/trainingsession_list.php',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->dolimeet->enabled && $conf->global->DOLIMEET_TRAININGSESSION_MENU_ENABLED',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet,fk_leftmenu=trainingsession_list',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=> '<i class="fas fa-people-arrows"></i> ' . $langs->trans('TrainingSessionCreate'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'trainingsession_card',
			'url'=>'/dolimeet/view/trainingsession/trainingsession_card.php?action=create',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->dolimeet->enabled && $conf->global->DOLIMEET_TRAININGSESSION_MENU_ENABLED',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolimeet,fk_leftmenu=trainingsession_list',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tags"></i>  ' . $langs->trans('Categories'),
			'mainmenu' => 'dolimeet',
			'leftmenu' => 'dolimeet_trainingsession',
			'url'      => '/categories/index.php?type=trainingsession',
			'langs'    => 'ticket',
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolimeet->enabled && $conf->categorie->enabled  && $conf->global->DOLIMEET_TRAININGSESSION_MENU_ENABLED',
			'perms'    => '1',
			'target'   => '',
			'user'     => 0,
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'<i class="fas fa-list"></i> '. $langs->trans('AuditList'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'audit_list',
			'url'=>'/dolimeet/view/audit/audit_list.php',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->dolimeet->enabled && $conf->global->DOLIMEET_AUDIT_MENU_ENABLED',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet,fk_leftmenu=audit_list',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=> '<i class="fas fa-tasks"></i> ' . $langs->trans('AuditCreate'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'audit_card',
			'url'=>'/dolimeet/view/audit/audit_card.php?action=create',
			'langs'=>'dolimeet@dolimeet', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100+$r,
			'enabled'=>'$conf->dolimeet->enabled && $conf->global->DOLIMEET_AUDIT_MENU_ENABLED',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1', // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolimeet,fk_leftmenu=audit_list',
			'type'     => 'left',
			'titre'    => '<i class="fas fa-tags"></i>  ' . $langs->trans('Categories'),
			'mainmenu' => 'dolimeet',
			'leftmenu' => 'dolimeet_audit',
			'url'      => '/categories/index.php?type=audit',
			'langs'    => 'ticket',
			'position' => 1100 + $r,
			'enabled'  => '$conf->dolimeet->enabled && $conf->categorie->enabled && $conf->global->DOLIMEET_AUDIT_MENU_ENABLED',
			'perms'    => '1',
			'target'   => '',
			'user'     => 0,
		);
		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=dolimeet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('ModuleConfiguration'),
			'prefix'   => '<i class="fas fa-cog"></i>  ',
			'mainmenu' => 'dolimeet',
			'leftmenu' => 'dolimeetconfig',
			'url'      => '/dolimeet/admin/setup.php',
			'langs'    => 'dolimeet@dolimeet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->dolimeet->enabled',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '1',			                // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'MyObject',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'myobject',
			'url'=>'/dolimeet/dolimeetindex.php',
			'langs'=>'dolimeet@dolimeet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->dolimeet->enabled',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->dolimeet->myobject->read',			                // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet,fk_leftmenu=myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List_MyObject',
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'dolimeet_myobject_list',
			'url'=>'/dolimeet/myobject_list.php',
			'langs'=>'dolimeet@dolimeet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->dolimeet->enabled',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->dolimeet->myobject->read',			                // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=dolimeet,fk_leftmenu=myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_MyObject',
			'mainmenu'=>'dolimeet',
			'leftmenu'=>'dolimeet_myobject_new',
			'url'=>'/dolimeet/myobject_card.php?action=create',
			'langs'=>'dolimeet@dolimeet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->dolimeet->enabled',  // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->dolimeet->myobject->write',			                // Use 'perms'=>'$user->rights->dolimeet->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		END MODULEBUILDER LEFTMENU MYOBJECT */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("dolimeet@dolimeet");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='myobject@dolimeet';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'MyObject'; $keyforclassfile='/dolimeet/class/myobject.class.php'; $keyforelement='myobject@dolimeet';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'MyObjectLine'; $keyforclassfile='/dolimeet/class/myobject.class.php'; $keyforelement='myobjectline@dolimeet'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@dolimeet';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='myobjectline'; $keyforaliasextra='extraline'; $keyforelement='myobjectline@dolimeet';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('myobjectline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'myobject_line as tl ON tl.fk_myobject = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		 $langs->load("dolimeet@dolimeet");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='myobject@dolimeet';
		 $keyforclass = 'MyObject'; $keyforclassfile='/dolimeet/class/myobject.class.php'; $keyforelement='myobject@dolimeet';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@dolimeet';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		//$result = $this->_load_tables('/install/mysql/tables/', 'dolimeet');
		$sql = array();
		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if ( ! preg_match('/\./', $subFolder)) {
				$this->_load_tables('/dolimeet/sql/' . $subFolder . '/');
			}
		}

		$this->_load_tables('/dolimeet/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		delDocumentModel('attendancesheet_odt', 'trainingsession');
		delDocumentModel('completioncertificate_odt', 'trainingsession');

		addDocumentModel('attendancesheet_odt', 'trainingsession', 'ODT templates', 'DOLIMEET_ATTENDANCESHEET_ADDON_ODT_PATH');
		addDocumentModel('completioncertificate_odt', 'trainingsession', 'ODT templates', 'DOLIMEET_COMPLETIONCERTIFICATE_ADDON_ODT_PATH');

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extra_fields = new ExtraFields($this->db);

		$extra_fields->update('label', $langs->transnoentities("Label"), 'varchar', '', 'contrat', 0, 0, 1040, '', '', '', 1);
		$extra_fields->addExtraField('label', $langs->transnoentities("Label"), 'varchar', 1040, '', 'contrat', 0, 0, '', '', '', '', 1);

		$extra_fields->update('trainingsession_start', $langs->transnoentities("TrainingSessionStart"), 'datetime', '', 'contrat', 0, 0, 1800, '', '', '', 1);
		$extra_fields->addExtraField('trainingsession_start', $langs->transnoentities("TrainingSessionStart"), 'datetime', 1040, '', 'contrat', 0, 0, '', '', '', '', 1);

		$extra_fields->update('trainingsession_end', $langs->transnoentities("TrainingSessionEnd"), 'datetime', '', 'contrat', 0, 0, 1810, '', '', '', 1);
		$extra_fields->addExtraField('trainingsession_end', $langs->transnoentities("TrainingSessionEnd"), 'datetime', 1040, '', 'contrat', 0, 0, '', '', '', '', 1);

		$extra_fields->update('trainingsession_type', $langs->transnoentities("TrainingSessionType"), 'sellist', '', 'contrat', 0, 0, 1830, 'a:1:{s:7:"options";a:1:{s:34:"c_trainingsession_type:label:rowid";N;}}', '', '', 1);
		$extra_fields->addExtraField('trainingsession_type', $langs->transnoentities("TrainingSessionType"), 'sellist', 1830, '', 'contrat', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:34:"c_trainingsession_type:label:rowid";N;}}', '', '', 1);

		$extra_fields->update('trainingsession_location', $langs->transnoentities("TrainingSessionLocation"), 'varchar', '', 'contrat', 0, 0, 1850, '', '', '', 1);
		$extra_fields->addExtraField('trainingsession_location', $langs->transnoentities("TrainingSessionLocation"), 'varchar', 1850, '', 'contrat', 0, 0, '', '', '', '', 1);

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('dolimeet');
		$myTmpObjects = array();
		$myTmpObjects['MyObject'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'MyObject') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_myobjects.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_myobjects.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, 0, 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
