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
 */

/**
 * 	\defgroup dolimeet     Module DoliMeet
 *  \brief    DoliMeet module descriptor.
 *
 *  \file     core/modules/modDoliMeet.class.php
 *  \ingroup  dolimeet
 *  \brief    Description and activation file for module DoliMeet.
 */

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module DoliMeet.
 */
class modDoliMeet extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions.
     *
     * @param DoliDB $db Database handler.
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
            require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
            saturne_load_langs(['dolimeet@dolimeet']);
        } else {
            $this->error++;
            $this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'DoliMeet', 'Saturne');
        }

        // ID for module (must be unique).
        $this->numero = 436304;

        // Key text used to identify module (for permissions, menus, etc...).
        $this->rights_class = 'dolimeet';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page.
        $this->family = '';

        // Module position in the family on 2 digits ('01', '10', '20', ...).
        $this->module_position = '';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this).
        $this->familyinfo = ['Evarisk' => ['position' => '01', 'label' => 'Evarisk']];
        // Module label (no space allowed), used if translation string 'ModulePriseoName' not found (Priseo is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModulePriseoDesc' not found (Priseo is name of module).
        $this->description = $langs->trans('DoliMeetDescription');
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = $langs->trans('DoliMeetDescriptionLong');

        // Author.
        $this->editor_name = 'Evarisk';
        $this->editor_url  = 'https://evarisk.com';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'.
        $this->version = '1.4.0';

        // Url to the file with your last numberversion of this module.
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where DOLIMEET is value of property name of module in uppercase).
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'.
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'.
        // To use a supported fa-xxx css style of font awesome, use this->picto='xxx'.
        $this->picto = 'dolimeet_color@dolimeet';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...).
        $this->module_parts = [
            // Set this to 1 if module has its own trigger directory (core/triggers).
            'triggers' => 1,
            // Set this to 1 if module has its own login method file (core/login).
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions).
            'substitutions' => 1,
            // Set this to 1 if module has its own menus handler directory (core/menus).
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl).
            'tpl' => 1,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode).
            'barcode' => 0,
            // Set this to 1 if module has its own models' directory (core/modules/xxx).
            'models' => 1,
            // Set this to 1 if module has its own printing directory (core/modules/printing).
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme).
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file.
            'css' => [],
            // Set this to relative path of js file if module must load a js on all pages.
            'js' => [],
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'.
            'hooks' => [
                'category',
                'categoryindex',
                'projectOverview',
                'printOverviewDetail',
                'admincompany',
                'sessioncard',
                'saturnepublicsignature',
                'contractcard',
                'get_sheet_linkable_objects',
                'dolimeetadmindocuments',
                'trainingsessioncard',
                'meetingsignature',
                'trainingsessionsignature',
                'auditsignature',
                'meetingnote',
                'trainingsessionnote',
                'auditnote',
                'meetingdocument',
                'trainingsessiondocument',
                'auditdocument',
                'globalcard',
                'sessionlist',
                'meetinglist',
                'trainingsessionlist',
                'auditlist',
                'contractlist'
            ],
            // Set this to 1 if features of module are opened to external users.
            'moduleforexternal' => 1,
        ];

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/dolimeet/temp","/dolimeet/subdir");
        $this->dirs = ['/dolimeet/temp', '/ecm/dolimeet/attendancesheetdocument/', '/ecm/dolimeet/completioncertificatedocument/'];

        // Config pages. Put here list of php page, stored into dolimeet/admin directory, to use to set up module.
        $this->config_page_url = ['setup.php@dolimeet'];

        // Dependencies.
        // A condition to hide module.
        $this->hidden = false;

        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...).
        $this->depends      = ['modCategorie', 'modContrat', 'modProjet', 'modFckeditor', 'modSaturne', 'modAgenda'];
        $this->requiredby   = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...).
        $this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...).

        // The language file dedicated to your module.
        $this->langfiles = ['dolimeet@dolimeet'];

        // Prerequisites.
        $this->phpmin                = [7, 4]; // Minimum version of PHP required by module.
        $this->need_dolibarr_version = [16, 0]; // Minimum version of Dolibarr required by module.

        // Messages at activation.
        $this->warnings_activation     = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...).
        $this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...).
        //$this->automatic_activation = array('FR'=>'DoliMeetWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true; // If true, can't be disabled.

        // Constants.
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive).
        // Example: $this->const=array(1 => array('DOLIMEET_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('DOLIMEET_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $i = 0;
        $this->const = [
            // CONST MEETING.
            $i++ => ['DOLIMEET_MEETING_ADDON', 'chaine', 'mod_meeting_standard', '', 0, 'current'],
            $i++ => ['DOLIMEET_MEETING_MENU_ENABLED', 'integer', 1, '', 0, 'current'],

            // CONST TRAININGSESSION.
            $i++ => ['DOLIMEET_TRAININGSESSION_ADDON', 'chaine', 'mod_trainingsession_standard', '', 0, 'current'],
            $i++ => ['DOLIMEET_TRAININGSESSION_MENU_ENABLED', 'integer', 1, '', 0, 'current'],

            // CONST AUDIT.
            $i++ => ['DOLIMEET_AUDIT_ADDON', 'chaine', 'mod_audit_standard', '', 0, 'current'],
            $i++ => ['DOLIMEET_AUDIT_MENU_ENABLED', 'integer', 1,'', 0, 'current'],

            // CONST DOLIMEET DOCUMENTS.
            $i++ => ['DOLIMEET_SHOW_SIGNATURE_SPECIMEN', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLIMEET_AUTOMATIC_PDF_GENERATION', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLIMEET_MANUAL_PDF_GENERATION', 'integer', 0, '', 0, 'current'],

            // CONST ATTENDANCESHEET DOCUMENT.
            $i++ => ['DOLIMEET_ATTENDANCESHEETDOCUMENT_ADDON', 'chaine', 'mod_attendancesheetdocument_standard', '', 0, 'current'],
            $i++ => ['DOLIMEET_ATTENDANCESHEETDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/dolimeet/documents/doctemplates/trainingsessiondocument/attendancesheetdocument/', '', 0, 'current'],
            $i++ => ['DOLIMEET_ATTENDANCESHEETDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/dolimeet/attendancesheetdocument/', '', 0, 'current'],
            $i++ => ['DOLIMEET_ATTENDANCESHEETDOCUMENT_DEFAULT_MODEL', 'chaine', 'attendancesheetdocument_odt', '', 0, 'current'],
            $i++ => ['DOLIMEET_ATTENDANCESHEETDOCUMENT_DISPLAY_ATTENDANCE_ABSENT_IN_SIGNATURE', 'integer', 0, '', 0, 'current'],

            // CONST COMPLETIONCERTIFICATE DOCUMENT.
            $i++ => ['DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON', 'chaine', 'mod_completioncertificatedocument_standard', '', 0, 'current'],
            $i++ => ['DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/dolimeet/documents/doctemplates/trainingsessiondocument/completioncertificatedocument/', '', 0, 'current'],
            $i++ => ['DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/dolimeet/completioncertificatedocument/', '', 0, 'current'],
            $i++ => ['DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_DEFAULT_MODEL', 'chaine', 'completioncertificatedocument_odt', '', 0, 'current'],

            // CONST CONFIGURATION.
            $i++ => ['MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER', 'chaine', '', '', 0, 'current'],

            // CONST MODULE.
            $i++ => ['DOLIMEET_VERSION','chaine', $this->version, '', 0, 'current'],
            $i++ => ['DOLIMEET_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
            $i++ => ['DOLIMEET_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DOLIMEET_EMAIL_TEMPLATE_SET', 'integer', 0, '', 0, 'current'],
            $i++ => ['DOLIMEET_EMAIL_TEMPLATE_SATISFACTION_SURVEY', 'integer', 0, '', 0, 'current'],

            // CONST GENERAL CONST.
            $i++ => ['CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST', 'integer', 1, '', 0, 'current'],
            $i   => ['MAIN_ODT_AS_PDF', 'chaine', 'libreoffice', '', 0, 'current']
        ];

        // Some keys to add into the overwriting translation tables.
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isset($conf->dolimeet) || !isset($conf->dolimeet->enabled)) {
            $conf->dolimeet = new stdClass();
            $conf->dolimeet->enabled = 0;
        }

        // Array to add new pages in new tabs.
        $this->tabs   = [];
        $pictoPath    = dol_buildpath('/custom/dolimeet/img/dolimeet_color.png', 1);
        $picto        = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
        $this->tabs[] = ['data' => 'thirdparty:+sessionList:' . $picto . 'DoliMeet' . ':dolimeet@dolimeet:$user->rights->dolimeet->session->read:/custom/dolimeet/view/session/session_list.php?fromtype=thirdparty&fromid=__ID__'];                        // To add a new tab identified by code tabname1.
        $this->tabs[] = ['data' => 'user:+sessionList:' . $picto . 'DoliMeet' . ':dolimeet@dolimeet:$user->rights->dolimeet->session->read:/custom/dolimeet/view/session/session_list.php?fromtype=user&fromid=__ID__'];                                    // To add a new tab identified by code tabname1.
        $this->tabs[] = ['data' => 'contact:+sessionList:' . $picto . 'DoliMeet' . ':dolimeet@dolimeet:$user->rights->dolimeet->session->read:/custom/dolimeet/view/session/session_list.php?fromtype=socpeople&fromid=__ID__'];                            // To add a new tab identified by code tabname1.
        $this->tabs[] = ['data' => 'project:+sessionList:' . $picto . 'DoliMeet' . ':dolimeet@dolimeet:$user->rights->dolimeet->session->read:/custom/dolimeet/view/session/session_list.php?fromtype=project&fromid=__ID__'];                              // To add a new tab identified by code tabname1.
        $this->tabs[] = ['data' => 'contract:+sessionList:' . $picto . 'DoliMeet' . ':dolimeet@dolimeet:$user->rights->dolimeet->session->read:/custom/dolimeet/view/session/session_list.php?fromtype=contrat&fromid=__ID__&object_type=trainingsession']; // To add a new tab identified by code tabname1.
        $this->tabs[] = ['data' => 'contract:+schedules:'. $picto . $langs->trans('Schedules') . ':dolimeet@dolimeet:$user->rights->contrat->lire:/custom/saturne/view/saturne_schedules.php?module_name=DoliMeet&element_type=contrat&id=__ID__'];     // To add a new tab identified by code tabname1.
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@dolimeet:$user->rights->othermodule->read:/dolimeet/mynewtab2.php?id=__ID__', // To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');

        // Dictionaries.
        $this->dictionaries = [
            'langs' => 'dolimeet@dolimeet',
            // List of tables we want to see into dictonary editor.
            'tabname' => [
                MAIN_DB_PREFIX . 'c_trainingsession_type',
                MAIN_DB_PREFIX . 'c_meeting_attendants_role',
                MAIN_DB_PREFIX . 'c_trainingsession_attendants_role',
                MAIN_DB_PREFIX . 'c_audit_attendants_role'
            ],
            // Label of tables.
            'tablib' => [
                'TrainingSessionType',
                'Meeting',
                'TrainingSession',
                'Audit'
            ],
            // Request to select fields.
            'tabsql' => [
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_trainingsession_type as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_meeting_attendants_role as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_trainingsession_attendants_role as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_audit_attendants_role as f'
            ],
            // Sort order.
            'tabsqlsort' => [
                'position ASC',
                'position ASC',
                'position ASC',
                'position ASC'
            ],
            // List of fields (result of select to show dictionary).
            'tabfield' => [
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields to edit a record).
            'tabfieldvalue' => [
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields for insert).
            'tabfieldinsert' => [
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // Name of columns with primary key (try to always name it 'rowid').
            'tabrowid' => [
                'rowid',
                'rowid',
                'rowid',
                'rowid'
            ],
            // Condition to show each dictionary.
            'tabcond' => [
                $conf->dolimeet->enabled,
                $conf->dolimeet->enabled,
                $conf->dolimeet->enabled,
                $conf->dolimeet->enabled
            ]
        ];

        // Boxes/Widgets.
        // Add here list of php file(s) stored in dolimeet/core/boxes that contains a class to show a widget.
        $this->boxes = [];

        // Cronjobs (List of cron jobs entries to add when module is enabled).
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week.
        $this->cronjobs = [];

        // Permissions provided by this module.
        $this->rights = [];
        $r = 0;

        /* DOLIMEET PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used).
        $this->rights[$r][1] = $langs->trans('LireModule', 'DoliMeet');   // Permission label.
        $this->rights[$r][4] = 'lire';                                                // In php code, permission will be checked by test if ($user->rights->dolimeet->session->read).
        $this->rights[$r][5] = 1;
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadModule', 'DoliMeet');
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = 1;
        $r++;

        /* SESSION PERMISSSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadObjects', $langs->trans('SessionsMin'));
        $this->rights[$r][4] = 'session';
        $this->rights[$r][5] = 'read';
        $r++;

        /* MEETING PERMISSSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects', $langs->transnoentities('Meetings'));
        $this->rights[$r][4] = 'meeting';
        $this->rights[$r][5] = 'read';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadMyObject', $langs->transnoentities('Meetings'));
        $this->rights[$r][4] = 'assignedtome';
        $this->rights[$r][5] = 'meeting';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('Meetings'));
        $this->rights[$r][4] = 'meeting';
        $this->rights[$r][5] = 'write';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('Meetings'));
        $this->rights[$r][4] = 'meeting';
        $this->rights[$r][5] = 'delete';
        $r++;

        /* TRAINING SESSION PERMISSSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadObjects', $langs->trans('Trainingsessions'));
        $this->rights[$r][4] = 'trainingsession';
        $this->rights[$r][5] = 'read';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadMyObject', $langs->trans('Trainingsessions'));
        $this->rights[$r][4] = 'assignedtome';
        $this->rights[$r][5] = 'trainingsession';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->trans('Trainingsessions'));
        $this->rights[$r][4] = 'trainingsession';
        $this->rights[$r][5] = 'write';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('DeleteObjects', $langs->trans('Trainingsessions'));
        $this->rights[$r][4] = 'trainingsession';
        $this->rights[$r][5] = 'delete';
        $r++;

        /* AUDIT PERMISSSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadObjects', $langs->trans('Audits'));
        $this->rights[$r][4] = 'audit';
        $this->rights[$r][5] = 'read';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadMyObject', $langs->trans('Audits'));
        $this->rights[$r][4] = 'assignedtome';
        $this->rights[$r][5] = 'audit';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->trans('Audits'));
        $this->rights[$r][4] = 'audit';
        $this->rights[$r][5] = 'write';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->trans('Audits'));
        $this->rights[$r][4] = 'audit';
        $this->rights[$r][5] = 'delete';
        $r++;

        /* ADMINPAGE PANEL ACCESS PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'DoliMeet');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'read';

        // Main menu entries to add.
        $this->menu = [];
        $r = 0;

        // Add here entries to declare new menus.
        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet',                        // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'     => 'top',                                         // This is a Top menu entry
            'titre'    => 'DoliMeet',
            'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
            'mainmenu' => 'dolimeet',
            'leftmenu' => '',
            'url'      => '/dolimeet/dolimeetindex.php',                 // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled',                    // Define condition to show or hide menu entry. Use '$conf->dolimeet->enabled' if entry must be visible if module is enabled.
            'perms'    => '$user->rights->dolimeet->lire',               // Use 'perms'=>'$user->rights->dolimeet->myobject->read' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 2,                                             // 0=Menu for internal users, 1=external users, 2=both
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet',
            'type'     => 'left',
            'titre'    => $langs->trans('Meeting'),
            'prefix'   => '<i class="fas fa-comments pictofixedwidth"></i>',
            'mainmenu' => 'dolimeet',
            'leftmenu' => 'meeting_list',
            'url'      => '/dolimeet/view/session/session_list.php?object_type=meeting',
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled && $conf->global->DOLIMEET_MEETING_MENU_ENABLED',
            'perms'    => '$user->rights->dolimeet->meeting->read || $user->rights->dolimeet->assignedtome->meeting',
            'target'   => '',
            'user'     => 2,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet,fk_leftmenu=meeting_list',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'dolimeet',
            'leftmenu' => 'meeting_tags',
            'url'      => '/categories/index.php?type=meeting',
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled && $conf->categorie->enabled && $conf->global->DOLIMEET_MEETING_MENU_ENABLED',
            'perms'    => '$user->rights->dolimeet->meeting->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet',
            'type'     => 'left',
            'titre'    => $langs->trans('TrainingSession'),
            'prefix'   => '<i class="fas fa-people-arrows pictofixedwidth"></i>',
            'mainmenu' => 'dolimeet',
            'leftmenu' => 'trainingsession_list',
            'url'      => '/dolimeet/view/session/session_list.php?object_type=trainingsession',
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled && $conf->global->DOLIMEET_TRAININGSESSION_MENU_ENABLED',
            'perms'    => '$user->rights->dolimeet->trainingsession->read || $user->rights->dolimeet->assignedtome->trainingsession',
            'target'   => '',
            'user'     => 2,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet,fk_leftmenu=trainingsession_list',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'dolimeet',
            'leftmenu' => 'trainingsession_tags',
            'url'      => '/categories/index.php?type=trainingsession',
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled && $conf->categorie->enabled && $conf->global->DOLIMEET_TRAININGSESSION_MENU_ENABLED',
            'perms'    => '$user->rights->dolimeet->trainingsession->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet',
            'type'     => 'left',
            'titre'    => $langs->trans('AuditReport'),
            'prefix'   => '<i class="fas fa-tasks pictofixedwidth"></i>',
            'mainmenu' => 'dolimeet',
            'leftmenu' => 'audit_list',
            'url'      => '/dolimeet/view/session/session_list.php?object_type=audit',
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled && $conf->global->DOLIMEET_AUDIT_MENU_ENABLED',
            'perms'    => '$user->rights->dolimeet->audit->read || $user->rights->dolimeet->assignedtome->audit',
            'target'   => '',
            'user'     => 2,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=dolimeet,fk_leftmenu=audit_list',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'dolimeet',
            'leftmenu' => 'audit_tags',
            'url'      => '/categories/index.php?type=audit',
            'langs'    => 'dolimeet@dolimeet',
            'position' => 1000 + $r,
            'enabled'  => '$conf->dolimeet->enabled && $conf->categorie->enabled && $conf->global->DOLIMEET_AUDIT_MENU_ENABLED',
            'perms'    => '$user->rights->dolimeet->audit->read',
            'target'   => '',
            'user'     => 0,
        ];
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories.
     *
     * @param  string     $options Options when enabling module ('', 'noboxes').
     * @return int                 1 if OK, 0 if KO.
     * @throws Exception
     */
    public function init($options = ''): int
    {
        global $conf, $langs, $user;

        // Permissions.
        $this->remove($options);

        $sql = [];
        // Load sql sub folders.
        $sqlFolder = scandir(__DIR__ . '/../../sql');
        foreach ($sqlFolder as $subFolder) {
            if (!preg_match('/\./', $subFolder)) {
                $this->_load_tables('/dolimeet/sql/' . $subFolder . '/');
            }
        }

        $result = $this->_load_tables('/dolimeet/sql/');
        if ($result < 0) {
            return -1;
        } // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default').

        dolibarr_set_const($this->db, 'DOLIMEET_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($this->db, 'DOLIMEET_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

        delDocumentModel('attendancesheetdocument_odt', 'trainingsessiondocument');
        delDocumentModel('completioncertificatedocument_odt', 'trainingsessiondocument');
        delDocumentModel('completioncertificatedocument_odt', 'completioncertificatedocument');

        addDocumentModel('attendancesheetdocument_odt', 'trainingsessiondocument', 'ODT templates', 'DOLIMEET_ATTENDANCESHEETDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('completioncertificatedocument_odt', 'trainingsessiondocument', 'ODT templates', 'DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('completioncertificatedocument_odt', 'completioncertificatedocument', 'ODT templates', 'DOLIMEET_COMPLETIONCERTIFICATEDOCUMENT_ADDON_ODT_PATH');

        if (getDolGlobalInt('DOLIMEET_EMAIL_TEMPLATE_SET') == 0 && isModEnabled('digiquali') && version_compare(getDolGlobalString('DIGIQUALI_VERSION'), '1.11.0', '>=')) {
            // Load Saturne libraries
            require_once __DIR__ . '/../../../saturne/class/saturnemail.class.php';

            $saturneMail = new SaturneMail($this->db, 'contrat');

            $position = 100;
            $satisfactionSurveys = ['customer', 'billing', 'trainee', 'sessiontrainer', 'opco'];
            foreach ($satisfactionSurveys as $satisfactionSurvey) {
                $saturneMail->entity        = 0;
                $saturneMail->type_template = 'contract';
                $saturneMail->lang          = 'fr_FR';
                $saturneMail->datec         = $this->db->idate(dol_now());
                $saturneMail->label         = $langs->transnoentities('SatisfactionSurveyLabel', $langs->transnoentities(ucfirst($satisfactionSurvey)));
                $saturneMail->position      = $position;
                $saturneMail->enabled       = "isModEnabled('contrat')";
                $saturneMail->topic         = $langs->transnoentities('SatisfactionSurveyTopic', dol_strtolower($langs->transnoentities(ucfirst($satisfactionSurvey))));
                $saturneMail->joinfiles     = 1;
                $saturneMail->content       = $langs->transnoentities('SatisfactionSurveyContent', dol_strtolower($langs->transnoentities(ucfirst($satisfactionSurvey))));

                $emailTemplateSatisfactionSurvey[$satisfactionSurvey] = $saturneMail->create($user);
                $position += 10;
            }

            dolibarr_set_const($this->db, 'DOLIMEET_EMAIL_TEMPLATE_SATISFACTION_SURVEY', json_encode($emailTemplateSatisfactionSurvey), 'chaine', 0, '', $conf->entity);
            dolibarr_set_const($this->db, 'DOLIMEET_EMAIL_TEMPLATE_SET', 1, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DOLIMEET_EMAIL_TEMPLATE_SET') == 1) {
            require_once __DIR__ . '/../../../saturne/class/saturnemail.class.php';

            $saturneMail = new SaturneMail($this->db);

            $saturneMail->entity        = 0;
            $saturneMail->type_template = 'contract';
            $saturneMail->lang          = 'fr_FR';
            $saturneMail->datec         = $this->db->idate(dol_now());
            $saturneMail->label         = $langs->transnoentities('CompletionCertificateDocumentLabel');
            $saturneMail->position      = 100;
            $saturneMail->enabled       = "isModEnabled('dolimeet')";
            $saturneMail->topic         = $langs->transnoentities('CompletionCertificateDocumentTopic');
            $saturneMail->joinfiles     = 1;
            $saturneMail->content       = $langs->transnoentities('CompletionCertificateDocumentContent');

            dolibarr_set_const($this->db, 'DOLIMEET_EMAIL_TEMPLATE_COMPLETION_CERTIFICATE', $saturneMail->create($user), 'chaine', 0, '', $conf->entity);
            dolibarr_set_const($this->db, 'DOLIMEET_EMAIL_TEMPLATE_SET', 2, 'integer', 0, '', $conf->entity);
        }

        // Create extrafields during init.
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extraFields = new ExtraFields($this->db);

        $extraFields->update('label', 'Label', 'varchar', 255, 'contrat', 0, 0, $this->numero . 1, '', '', '', 1, '', '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);
        $extraFields->addExtraField('label', 'Label', 'varchar', $this->numero . 1, 255, 'contrat', 0, 0, '', '', '', '', 1, '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);

        $extraFields->update('trainingsession_type', 'TrainingSessionType', 'sellist', '', 'contrat', 0, 0, $this->numero . 10, 'a:1:{s:7:"options";a:1:{s:34:"c_trainingsession_type:label:rowid";N;}}', '', '', 1, 'TrainingSessionTypeHelp', '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);
        $extraFields->addExtraField('trainingsession_type', 'TrainingSessionType', 'sellist', $this->numero . 10, '', 'contrat', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:34:"c_trainingsession_type:label:rowid";N;}}', '', '', 1, 'TrainingSessionTypeHelp', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);

        $extraFields->update('trainingsession_location', 'TrainingSessionLocation', 'varchar', 255, 'contrat', 0, 0, $this->numero . 20, '', '', '', 1, 'TrainingSessionLocationHelp', '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);
        $extraFields->addExtraField('trainingsession_location', 'TrainingSessionLocation', 'varchar', $this->numero . 20, 255, 'contrat', 0, 0, '', '', '', '', 1, 'TrainingSessionLocationHelp', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);

        $extraFields->update('trainingsession_start', 'TrainingSessionStart', 'datetime', '', 'contrat', 0, 0, $this->numero . 30, '', '', '', 1, 'TrainingSessionStartHelp', '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);
        $extraFields->addExtraField('trainingsession_start', 'TrainingSessionStart', 'datetime', $this->numero . 30, '', 'contrat', 0, 0, '', '', '', '', 1, 'TrainingSessionStartHelp', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')", 0, 0, ['css' => 'minwidth100 maxwidth300 widthcentpercentminusxx']);

        $extraFields->update('trainingsession_end', 'TrainingSessionEnd', 'datetime', '', 'contrat', 0, 0, $this->numero . 40, '', '', '', 1, 'TrainingSessionEndHelp', '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')");
        $extraFields->addExtraField('trainingsession_end', 'TrainingSessionEnd', 'datetime', $this->numero . 40, '', 'contrat', 0, 0, '', '', '', '', 'TrainingSessionEndHelp', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')");

        $extraFields->update('trainingsession_durations', 'TrainingSessionDurations', 'int', '', 'contrat', 0, 0, $this->numero . 50, '', '', '', 1, 'TrainingSessionDurationHelp', '', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')");
        $extraFields->addExtraField('trainingsession_durations', 'TrainingSessionDurations', 'int', $this->numero . 50, '', 'contrat', 0, 0, '', '', '', '', 1, 'TrainingSessionDurationHelp', '', 0, 'dolimeet@dolimeet', "isModEnabled('dolimeet') && isModEnabled('contrat')");

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted.
     *
     * @param  string $options Options when enabling module ('', 'noboxes').
     * @return int             1 if OK, 0 if KO.
     */
    public function remove($options = ''): int
    {
        $sql = [];
        return $this->_remove($sql, $options);
    }
}
