<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/session.class.php
 * \ingroup dolimeet
 * \brief   This file is a CRUD class file for Session (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';
require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../saturne/class/saturnedocuments.class.php';

/**
 * Class for Session.
 */
class Session extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public string $module = 'dolimeet';

    /**
     * @var string Element type of object.
     */
    public $element = 'session';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'dolimeet_session';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public int $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public int $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for session. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'session@dolimeet' if picto is file 'img/object_session.png'.
     */
    public string $picto = '';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     *  'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *   Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'picto' is code of a picto to show before value in forms
     *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *  'validate' is 1 if you need to validate with $this->validateField()
     *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public array $fields = [
        'rowid'          => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'            => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'        => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'         => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'  => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'            => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0],
        'import_key'     => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'         => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 190, 'notnull' => 1, 'visible' => 2, 'default' => 0, 'index' => 1, 'validate' => 1, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'ValidatePendingSignature', 2 => 'Locked', 3 => 'Archived']],
        'label'          => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'showoncombobox' => 2, 'validate' => 1, 'autofocusoncreate' => 1],
        'date_start'     => ['type' => 'datetime',     'label' => 'DateStart',        'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 1],
        'date_end'       => ['type' => 'datetime',     'label' => 'DateEnd',          'enabled' => 1, 'position' => 120, 'notnull' => 1, 'visible' => 1],
        'content'        => ['type' => 'html',         'label' => 'Content',          'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => 3, 'validate' => 1],
        'type'           => ['type' => 'varchar(128)', 'label' => 'Type',             'enabled' => 1, 'position' => 120, 'notnull' => 1, 'visible' => 0],
        'duration'       => ['type' => 'duration',     'label' => 'Duration',         'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 1],
        'note_public'    => ['type' => 'html',         'label' => 'NotePublic',       'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => 1],
        'note_private'   => ['type' => 'html',         'label' => 'NotePrivate',      'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => 1],
        'fk_user_creat'  => ['type' => 'integer:User:user/class/user.class.php',            'label' => 'UserAuthor', 'picto' => 'user',     'enabled' => 1,                         'position' => 170, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'  => ['type' => 'integer:User:user/class/user.class.php',            'label' => 'UserModif',  'picto' => 'user',     'enabled' => 1,                         'position' => 180, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_soc'         => ['type' => 'integer:Societe:societe/class/societe.class.php',   'label' => 'ThirdParty', 'picto' => 'company',  'enabled' => '$conf->societe->enabled', 'position' => 80,  'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'societe.rowid'],
        'fk_project'     => ['type' => 'integer:Project:projet/class/project.class.php',    'label' => 'Project',    'picto' => 'project',  'enabled' => '$conf->project->enabled', 'position' => 90,  'notnull' => 0,  'visible'=> 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'projet.rowid'],
        'fk_contrat'     => ['type' => 'integer:Contrat:contrat/class/contrat.class.php',   'label' => 'Contract',   'picto' => 'contract', 'enabled' => '$conf->contrat->enabled', 'position' => 100, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'contrat.rowid']
    ];

    /**
     * @var int ID.
     */
    public int $rowid;

    /**
     * @var string Ref.
     */
    public $ref;

    /**
     * @var string Ref ext.
     */
    public $ref_ext;

    /**
     * @var int Entity.
     */
    public $entity;

    /**
     * @var int|string Creation date.
     */
    public $date_creation;

    /**
     * @var int|string Timestamp.
     */
    public $tms;

    /**
     * @var string Import key.
     */
    public $import_key;

    /**
     * @var int Status.
     */
    public $status;

    /**
     * @var string Label.
     */
    public string $label;

    /**
     * @var int|string Start date.
     */
    public $date_start;

    /**
     * @var int|string End date.
     */
    public $date_end;

    /**
     * @var string Content.
     */
    public string $content;

    /**
     * @var string Object type.
     */
    public string $type;

    /**
     * @var int|null|string Duration.
     */
    public $duration;

    /**
     * @var string Public note.
     */
    public $note_public;

    /**
     * @var string Private note.
     */
    public $note_private;

    /**
     * @var int User ID.
     */
    public int $fk_user_creat;

    /**
     * @var int|null User ID.
     */
    public ?int $fk_user_modif;

    /**
     * @var int|string  ThirdParty ID.
     */
    public $fk_soc;

    /**
     * @var int Project ID.
     */
    public $fk_project;

    /**
     * @var int|string Contract ID.
     */
    public $fk_contrat;

    /**
     * Constructor.
     *
     * @param DoliDb $db         Database handler.
     * @param string $objectType Object element type.
     */
    public function __construct(DoliDB $db, string $objectType = 'session')
    {
        $this->type = $objectType;

        parent::__construct($db, $this->module, $objectType);

        switch ($objectType) {
            case 'trainingsession':
                $this->picto = 'fontawesome_fa-people-arrows_fas_#d35968';
                $this->fields['fk_project']['notnull'] = 1;
                $this->fields['fk_contrat']['notnull'] = 1;
                break;
            case 'meeting':
                $this->picto = 'fontawesome_fa-comments_fas_#d35968';
                unset($this->fields['duration']);
                break;
            case 'audit':
                $this->picto = 'fontawesome_fa-tasks_fas_#d35968';
                unset($this->fields['duration']);
                break;
            default :
                $this->picto = 'dolimeet_color@dolimeet';
                break;
        }
    }

    /**
     *  Return a link to the object card (with optionaly the picto).
     *
     *  @param  int     $withpicto              Include picto in link (0 = No picto, 1 = Include picto into link, 2 = Only picto).
     *  @param  string  $option                 On what the link point to ('nolink', ...).
     *  @param  int     $notooltip              1 = Disable tooltip.
     *  @param  string  $morecss                Add more css on link.
     *  @param  int     $save_lastsearch_value -1 = Auto, 0 = No save of lastsearch_values when clicking, 1 = Save lastsearch_values whenclicking.
     *  @return	string                          String with URL.
     */
    public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1): string
    {
        global $conf, $langs, $url;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips.
        }

        $result = '';

        $label = img_picto('', $this->picto) . ' <u>' . $langs->trans(ucfirst($this->element)) . '</u>';
        if (isset($this->status)) {
            $label .= ' ' . $this->getLibStatut(5);
        }
        $label .= '<br>';
        $label .= '<b>' . $langs->trans('Ref') . ' : </b> ' . $this->ref;

        $url = dol_buildpath('/' . $this->module . '/view/session/session_card.php', 1) . '?id=' . $this->id . '&object_type=' . $this->element;

        if ($option != 'nolink') {
            // Add param to save lastsearch_values or not.
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER['PHP_SELF'])) {
                $add_save_lastsearch_values = 1;
            }
            if ($add_save_lastsearch_values) {
                $url .= '&save_lastsearch_values=1';
            }
        }

        $linkclose = '';
        if (empty($notooltip)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans('Show' . ucfirst($this->element));
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }
            $linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
            $linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
        } else {
            $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
        }

        if ($option == 'nolink') {
            $linkstart = '<span';
        } else {
            $linkstart = '<a href="' . $url . '"';
        }
        if ($option == 'blank') {
            $linkstart .= 'target=_blank';
        }
        $linkstart .= $linkclose . '>';
        if ($option == 'nolink' || empty($url)) {
            $linkend = '</span>';
        } else {
            $linkend = '</a>';
        }

        $result .= $linkstart;

        if ($withpicto > 0) {
            $result .= img_picto('', $this->picto) . ' ';
        }

        if ($withpicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;

        global $action, $hookmanager;
        $hookmanager->initHooks([$this->element . 'dao']);
        $parameters = ['id' => $this->id, 'getnomurl' => $result];
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.
        if ($reshook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

        return $result;
    }

    /**
     * Set draft status.
     *
     * @param  User      $user      Object user that modify.
     * @param  int       $notrigger 1 = Does not execute triggers, 0 = Execute triggers.
     * @return int                  0 < if KO, >0 if OK.
     * @throws Exception
     */
    public function setDraft(User $user, int $notrigger = 0): int
    {
        $signatory = new SaturneSignature($this->db, 'dolimeet', $this->type);
        $signatory->deleteSignatoriesSignatures($this->id, $this->type);

        return parent::setDraft($user, $notrigger);
    }

    /**
     * Return the status.
     *
     * @param  int    $status ID status.
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
     * @return string         Label of status.
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;

            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
            $this->labelStatus[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');

            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
            $this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_DELETED) {
            $statusType = 'status0';
        }
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status3';
        }
        if ($status == self::STATUS_LOCKED || $status == self::STATUS_ARCHIVED) {
            $statusType = 'status8';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Sets object to supplied categories
     *
     * Deletes object from existing categories not supplied
     * Adds it to non-existing supplied categories
     * Existing categories are left untouched
     *
     * @param  int|int[] $categories Category or categories IDs
     * @return int                   0 < if KO, >0 if OK
     */
    public function setCategories($categories): int
    {
        return parent::setCategoriesCommon($categories, 'session');
    }

    /**
     * Clone an object into another one.
     *
     * @param  User      $user    User that creates
     * @param  int       $fromID  ID of object to clone.
     * @param  array     $options Options array.
     * @return int                New object created, <0 if KO.
     * @throws Exception
     */
    public function createFromClone(User $user, int $fromID, array $options): int
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $error = 0;

        $object = new self($this->db);
        $this->db->begin();

        // Load source object.
        $object->fetchCommon($fromID);

        // Reset some properties.
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);

        // Clear fields.
        if (property_exists($object, 'ref')) {
            $object->ref = '';
        }
        if (!empty($options['label'])) {
            if (property_exists($object, 'label')) {
                $object->label = $options['label'];
            }
        }
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }
        if (property_exists($object, 'status')) {
            $object->status = self::STATUS_DRAFT;
        }

        // Create clone
        $object->context = 'createfromclone';
        $sessionID       = $object->create($user);

        if ($sessionID > 0) {
            if ($options['attendants'] == 0) {
                // Load signatory from source object.
                $signatory   = new SaturneSignature($this->db);
                $signatories = $signatory->fetchSignatory('', $fromID, $this->type);
                if (is_array($signatories) && !empty($signatories)) {
                    foreach ($signatories as $arrayRole) {
                        foreach ($arrayRole as $signatoryRole) {
                            $signatory->createFromClone($user, $signatoryRole->id, $sessionID);
                        }
                    }
                }
            }

            if ($options['attendants'] == 1) {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

                $contract  = new Contrat($this->db);
                $signatory = new SaturneSignature($this->db);

                $contract->fetch($object->fk_contrat);

                $attendantInternalSessionTrainerArray = $contract->liste_contact(-1, 'internal', 0, 'SESSIONTRAINER');
                $attendantInternalTraineeArray        = $contract->liste_contact(-1, 'internal', 0, 'TRAINEE');
                $attendantExternalSessionTrainerArray = $contract->liste_contact(-1, 'external', 0, 'SESSIONTRAINER');
                $attendantExternalTraineeArray        = $contract->liste_contact(-1, 'external', 0, 'TRAINEE');

                $attendantArray = array_merge(
                    (is_array($attendantInternalSessionTrainerArray) ? $attendantInternalSessionTrainerArray : []),
                    (is_array($attendantInternalTraineeArray) ? $attendantInternalTraineeArray : []),
                    (is_array($attendantExternalSessionTrainerArray) ? $attendantExternalSessionTrainerArray : []),
                    (is_array($attendantExternalTraineeArray) ? $attendantExternalTraineeArray : [])
                );

                foreach ($attendantArray as $attendant) {
                    if ($attendant['code'] == 'TRAINEE') {
                        $attendantRole = 'Trainee';
                    } else {
                        $attendantRole = 'SessionTrainer';
                    }
                    $signatory->setSignatory($sessionID, $object->type, (($attendant['source'] == 'internal') ? 'user' : 'socpeople'), [$attendant['id']], $attendantRole, 1);
                }
            }
        } else {
            $error++;
            $this->error  = $object->error;
            $this->errors = $object->errors;
        }

        unset($object->context);

        // End.
        if (!$error) {
            $this->db->commit();
            return $sessionID;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
}

/**
 * Class for SessionDocument.
 */
class SessionDocument extends SaturneDocuments
{
    /**
     * @var string Module name.
     */
    public string $module = 'dolimeet';

    /**
     * @var string Element type of object.
     */
    public $element = 'sessiondocument';

    /**
     * Constructor.
     *
     * @param DoliDb $db Database handler.
     * @param string $objectType Object element type.
     */
    public function __construct(DoliDB $db, string $objectType = 'sessiondocument')
    {
        $this->element = $objectType;
        $this->type    = $objectType;

        parent::__construct($db, $this->module, $objectType);
    }
}

