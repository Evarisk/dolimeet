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
 * \file        class/session.class.php
 * \ingroup     dolimeet
 * \brief       This file is a CRUD class file for Session (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

//require_once __DIR__ . '/../../saturne/class/signature.class.php';
require_once __DIR__ . '/dolimeetdocuments.class.php';

/**
 * Class for Session
 */
class Session extends CommonObject
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
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public int $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public int $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for session. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'session@dolimeet' if picto is file 'img/object_session.png'.
     */
    public string $picto = '';

    /**
     * @var array Label status of const.
     */
    public array $labelStatus;

    /**
     * @var array Label status short of const.
     */
    public array $labelStatusShort;

	public const STATUS_DELETED = -1;
    public const STATUS_DRAFT = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED = 2;
    public const STATUS_ARCHIVED = 3;

    /**
     *  'type' field format:
     *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *  	'select' (list of values are in 'options'),
     *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *  	'chkbxlst:...',
     *  	'varchar(x)',
     *  	'text', 'text:none', 'html',
     *   	'double(24,8)', 'real', 'price',
     *  	'date', 'datetime', 'timestamp', 'duration',
     *  	'boolean', 'checkbox', 'radio', 'array',
     *  	'mail', 'phone', 'url', 'password', 'ip'
     *		Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
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
     *	'validate' is 1 if you need to validate with $this->validateField()
     *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public array $fields = [
		'rowid'          => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
		'ref'            => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
		'ref_ext'        => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
		'entity'         => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
		'date_creation'  => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
		'tms'            => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0],
		'import_key'     => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
		'status'         => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 190,  'notnull' => 1, 'visible' => 2, 'default' => 0, 'index' => 1, 'validate' => 1, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'ValidatePendingSignature', 2 => 'Locked', 3 => 'Archived']],
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
		'fk_soc'         => ['type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company',  'enabled' => '$conf->societe->enabled', 'position' => 80,  'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'societe.rowid'],
		'fk_project'     => ['type' => 'integer:Project:projet/class/project.class.php:1',  'label' => 'Project',    'picto' => 'project',  'enabled' => '$conf->project->enabled', 'position' => 90, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'projet.rowid'],
		'fk_contrat'     => ['type' => 'integer:Contrat:contrat/class/contrat.class.php:1', 'label' => 'Contract',   'picto' => 'contract', 'enabled' => '$conf->contrat->enabled', 'position' => 100, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'contrat.rowid'],
    ];

    /**
     * @var int ID
     */
	public int $rowid;

    /**
     * @var string Ref
     */
	public $ref;

    /**
     * @var string Ref ext
     */
	public $ref_ext;

    /**
     * @var int Entity
     */
	public $entity;

    /**
     * @var int|string Creation date
     */
	public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string Label
     */
    public string $label;

    /**
     * @var int|string Start date
     */
    public $date_start;

    /**
     * @var int|string End date
     */
    public $date_end;

    /**
     * @var string Content
     */
    public string $content;

    /**
     * @var string Object type
     */
    public string $type;

    /**
     * @var int|null|string Duration
     */
    public $duration;

    /**
     * @var string Public note
     */
    public $note_public;

    /**
     * @var string Private note
     */
    public $note_private;

    /**
     * @var string Last document name
     */
    public $last_main_doc;

    /**
     * @var string Pdf model name
     */
    public $model_pdf;

    /**
     * @var int User ID
     */
    public int $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public ?int $fk_user_modif;

    /**
     * @var int|string  ThirdParty ID
     */
    public $fk_soc;

    /**
     * @var int Project ID
     */
    public $fk_project;

    /**
     * @var int|string Contract ID
     */
    public $fk_contrat;

    /**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db, $objectType = '')
    {
		global $conf, $langs;

		$this->db = $db;
        $this->type = $objectType;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}

        switch ($objectType) {
            case 'trainingsession':
                $this->picto = 'fontawesome_fa-people-arrows_fas_#d35968';
                break;
            case 'meeting':
                $this->picto = 'fontawesome_fa-comments_fas_#d35968';
                unset($this->fields['duration']);
                unset($this->fields['fk_contrat']);
                break;
            case 'audit':
                $this->picto = 'fontawesome_fa-tasks_fas_#d35968';
                unset($this->fields['duration']);
                unset($this->fields['fk_contrat']);
                break;
            default :
                $this->picto = 'dolimeet_color@dolimeet';
                break;
        }
	}

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             0 < if KO, ID of created object if OK
     */
	public function create(User $user, bool $notrigger = false): int
    {
		return $this->createCommon($user, $notrigger);
	}

    /**
     * Load object in memory from the database
     *
     * @param  int|string       $id  ID object
     * @param  string|null $ref Ref
     * @return int              0 < if KO, 0 if not found, >0 if OK
     */
	public function fetch($id, string $ref = null): int
    {
        return $this->fetchCommon($id, $ref);
	}

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder  Sort Order
     * @param  string      $sortfield  Sort field
     * @param  int         $limit      Limit
     * @param  int         $offset     Offset
     * @param  array       $filter     Filter array. Example array('field'=>'value', 'customurl'=>...)
     * @param  string      $filtermode Filter mode (AND/OR)
     * @return int|array               0 < if KO, array of pages if OK
     * @throws Exception
     */
	public function fetchAll(string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
    {
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = [];

		$sql = 'SELECT ';
		$sql .= $this->getFieldList('t');
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = [];
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				} elseif (in_array($this->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key.' IN ('.$this->db->sanitize($this->db->escape($value)).')';
				} else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db, $this->type);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             0 < if KO, >0 if OK
     */
	public function update(User $user, bool $notrigger = false): int
    {
		return $this->updateCommon($user, $notrigger);
	}

    /**
     * Delete object in database
     *
     * @param  User $user      User that deletes
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             0 < if KO, >0 if OK
     */
	public function delete(User $user, bool $notrigger = false): int
    {
        return $this->deleteCommon($user, $notrigger);
	}

    /**
     * Validate object
     *
     * @param  User      $user      User making status change
     * @param  int       $notrigger 1=Does not execute triggers, 0= execute triggers
     * @return int                  0 < if OK, 0=Nothing done, >0 if KO
     * @throws Exception
     */
    public function validate(User $user, int $notrigger = 0): int
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        // Protection
        if ($this->status == self::STATUS_VALIDATED) {
            dol_syslog(get_class($this) . '::validate action abandonned: already validated', LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        // Define new ref
        if ((preg_match('/^\(?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happen, but when it occurs, the test save life
            $num = $this->getNextNumRef();
        } else {
            $num = $this->ref;
        }
        $this->newref = $num;

        if (!empty($num)) {
            // Validate
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element;
            $sql .= " SET ref = '" . $this->db->escape($num)."',";
            $sql .= ' status = ' . self::STATUS_VALIDATED;
            $sql .= ' WHERE rowid = ' . ($this->id);

            dol_syslog(get_class($this) . '::validate()', LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (!$resql) {
                dol_print_error($this->db);
                $this->error = $this->db->lasterror();
                $error++;
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger(strtoupper($this->type) . '_VALIDATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

        if (!$error) {
            $this->oldref = $this->ref;

            // Rename directory if dir was a temporary ref
            if (preg_match('/^\(?PROV/i', $this->ref)) {
                // Now we rename also files into index
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('".$this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1).")), filepath = 'session/" . $this->db->escape($this->newref) . "'";
                $sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'session/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++; $this->error = $this->db->lasterror();
                }

                // We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
                $oldref = dol_sanitizeFileName($this->ref);
                $newref = dol_sanitizeFileName($num);
                $dirsource = $conf->dolimeet->dir_output . '/' . $this->type . '/' . $oldref;
                $dirdest = $conf->dolimeet->dir_output . '/' . $this->type . '/' . $newref;
                if (!$error && file_exists($dirsource)) {
                    dol_syslog(get_class($this) . '::validate() rename dir ' . $dirsource . ' into ' . $dirdest);

                    if (@rename($dirsource, $dirdest)) {
                        dol_syslog('Rename ok');
                        // Rename docs starting with $oldref with $newref
                        $listoffiles = dol_dir_list($conf->dolimeet->dir_output . '/' . $this->type . '/' . $newref, 'files', 1, '^'.preg_quote($oldref, '/'));
                        foreach ($listoffiles as $fileentry) {
                            $dirsource = $fileentry['name'];
                            $dirdest   = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
                            $dirsource = $fileentry['path'] . '/' .$dirsource;
                            $dirdest   = $fileentry['path'] . '/' . $dirdest;
                            @rename($dirsource, $dirdest);
                        }
                    }
                }
            }
        }

        // Set new ref and current status
        if (!$error) {
            $this->ref = $num;
            $this->status = self::STATUS_VALIDATED;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *    Set draft status
     *
     * @param  User      $user      Object user that modify
     * @param  int       $notrigger 1=Does not execute triggers, 0=Execute triggers
     * @return int                  0 < if KO, >0 if OK
     * @throws Exception
     */
    public function setDraft(User $user, int $notrigger = 0): int
    {
        // Protection
        if ($this->status <= self::STATUS_DRAFT) {
            return 0;
        }

        $signatory = new SaturneSignature($this->db);
        $signatory->deleteSignatoriesSignatures($this->id, $this->type);
        return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, strtoupper($this->type) . '_UNVALIDATE');
    }

    /**
     *	Set locked status
     *
     *	@param  User $user	    Object user that modify
     *  @param  int  $notrigger 1=Does not execute triggers, 0=Execute triggers
     *	@return	int				0 < if KO, 0=Nothing done, >0 if OK
     */
    public function setLocked(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, self::STATUS_LOCKED, $notrigger, strtoupper($this->type) . '_LOCKED');
    }

    /**
     *	Set archived status
     *
     *	@param  User $user	    Object user that modify
     *  @param  int  $notrigger 1=Does not execute triggers, 0=Execute triggers
     *	@return	int			    0 < if KO, >0 if OK
     */
    public function setArchived(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, self::STATUS_ARCHIVED, $notrigger, strtoupper($this->type) . '_ARCHIVED');
    }

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto              Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                 On what the link point to ('nolink', ...)
     *  @param  int     $notooltip              1=Disable tooltip
     *  @param  string  $morecss                Add more css on link
     *  @param  int     $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                          String with URL
     */
	public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1): string
    {
		global $conf, $langs;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

        $result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans(ucfirst($this->type)) . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ' : </b> ' . $this->ref;

		$url = dol_buildpath('/' . $this->module . '/view/session/session_card.php', 1) . '?id=' . $this->id . '&object_type=' . $this->type;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
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
				$label = $langs->trans('ShowSession');
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' .$morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'. $url . '" target="_blank"';
		}
		$linkstart .= $linkclose . '>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

        $result .= $linkstart;

//        if (empty($this->showphoto_on_popup)) {
//            if ($withpicto > 0) {
//                $result .= img_object(($notooltip ? '' : $label), ($this->picto ?: 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
//            }
//        } elseif ($withpicto > 0) {
//            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
//
//            list($class, $module) = explode('@', $this->picto);
//            $upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
//            $filearray = dol_dir_list($upload_dir, 'files');
//            $filename = $filearray[0]['name'];
//            if (!empty($filename)) {
//                $pospoint = strpos($filearray[0]['name'], '.');
//
//                $pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
//                if (empty($conf->global->{strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS'})) {
//                    $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
//                } else {
//                    $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
//                }
//
//                $result .= '</div>';
//            } else {
//                $result .= img_object(($notooltip ? '' : $label), ($this->picto ?: 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
//            }
//        }

		if ($withpicto > 0) {
            $result .= img_picto('', $this->picto) . ' ';
        }

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;

		global $action, $hookmanager;
		$hookmanager->initHooks(['sessiondao']);
		$parameters = ['id' => $this->id, 'getnomurl' => $result];
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

    /**
     *  Return the label of the status
     *
     *  @param  int     $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string        Label of status
     */
	public function getLibStatut(int $mode = 0): string
    {
		return $this->LibStatut($this->status, $mode);
	}

    /**
     *  Return the status
     *
     *  @param  int    $status Id status
     *  @param  int    $mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return string         Label of status
     */
	public function LibStatut(int $status, int $mode = 0): string
    {
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load('dolimeet@dolimeet');
            $this->labelStatus[self::STATUS_DELETED]  = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatus[self::STATUS_DRAFT]    = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
			$this->labelStatus[self::STATUS_LOCKED]   = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED] = $langs->transnoentitiesnoconv('Archived');

            $this->labelStatusShort[self::STATUS_DELETED]  = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_DRAFT]    = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
            $this->labelStatusShort[self::STATUS_LOCKED]   = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED] = $langs->transnoentitiesnoconv('Archived');
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
     *	Load the info information in the object
     *
     *	@param  int   $id ID of object
     *	@return	void
     */
	public function info(int $id): void
    {
		$sql = 'SELECT t.rowid, t.date_creation as datec, t.tms as datem,';
		$sql .= ' t.fk_user_creat, t.fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = ' . $id;

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

                $this->user_creation_id = $obj->fk_user_creat;
                $this->user_modification_id = $obj->fk_user_modif;
                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

    /**
     * Initialise object with example values
     * ID must be 0 if object instance is a specimen
     *
     * @return void
     */
	public function initAsSpecimen(): void
    {
		$this->initAsSpecimenCommon();
	}

    /**
     * Returns the reference to the following non-used object depending on the active numbering module.
     *
     *  @return string Object free reference
     */
    public function getNextNumRef(): string
    {
        global $langs, $conf;
        $langs->load('dolimeet@dolimeet');

        $mod = 'DOLIMEET_' . $this->type . '_ADDON';
        if (empty($conf->global->$mod)) {
            $conf->global->$mod = 'mod_' . $this->type . '_standard';
        }

        if (!empty($conf->global->$mod)) {
            $mybool = false;

            $file = $conf->global->$mod . '.php';
            $classname = $conf->global->$mod;

            // Include file with class
            $dirmodels = array_merge(['/'], $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {
                $dir = dol_buildpath($reldir . 'core/modules/dolimeet/session/');

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir.$file;
            }

            if ($mybool === false) {
                dol_print_error('', 'Failed to include file ' .$file);
                return '';
            }

            if (class_exists($classname)) {
                $obj = new $classname();
                $numref = $obj->getNextValue($this);

                if ($numref != '' && $numref != '-1') {
                    return $numref;
                } else {
                    $this->error = $obj->error;
                    //dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
                    return '';
                }
            } else {
                print $langs->trans('Error') . ' ' . $langs->trans('ClassNotFound') . ' ' . $classname;
                return '';
            }
        } else {
            print $langs->trans('ErrorNumberingModuleNotSetup', $this->element);
            return '';
        }
    }

    /**
     * Sets object to supplied categories.
     *
     * Deletes object from existing categories not supplied.
     * Adds it to non-existing supplied categories.
     * Existing categories are left untouched.
     *
     * @param  int|int[] $categories Category or categories IDs
     * @return int                   0 < if KO, >0 if OK
     */
	public function setCategories($categories): int
    {
		return parent::setCategoriesCommon($categories, 'session');
	}

    /**
     * Clone an object into another one
     *
     * @param  User      $user    User that creates
     * @param  int       $fromid  ID of object to clone
     * @param  array     $options Options array
     * @return int                New object created, <0 if KO
     * @throws Exception
     */
    public function createFromClone(User $user, int $fromid, array $options): int
    {
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $conf, $langs;
		$error = 0;

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
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
			$object->status = 0;
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result                             = $object->create($user);

		if ($result > 0) {
			if (!empty($options['attendants'])) {
                // Load signatory from source object
                $signatory   = new SaturneSignature($this->db);
                $signatories = $signatory->fetchSignatory('', $fromid, $this->type);
                if (is_array($signatories) && !empty($signatories)) {
                    foreach ($signatories as $arrayRole) {
                        foreach ($arrayRole as $signatoryRole) {
                            $signatory->createFromClone($user, $signatoryRole->id, $result);
                        }
                    }
                }
			}
		} else {
			$error++;
			$this->error  = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $result;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}

/**
 * Class for SessionDocument
 */
class SessionDocument extends DoliMeetDocuments
{
    /**
     * @var string Element type of object.
     */
    public $element = 'sessiondocument';

    /**
     * @var string Name of icon for sessiondocument. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'sessiondocument@dolimeet' if picto is file 'img/object_sessiondocument.png'.
     */
    public string $picto = '';

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db, $objectType)
    {
        $this->element = $objectType;
        $this->type    = $objectType;

        parent::__construct($db);

        switch ($objectType) {
            case 'trainingsession':
                $this->picto = 'fontawesome_fa-people-arrows_fas_#d35968';
                break;
            case 'meeting':
                $this->picto = 'fontawesome_fa-comments_fas_#d35968';
                break;
            case 'audit':
                $this->picto = 'fontawesome_fa-tasks_fas_#d35968';
                break;
            default :
                $this->picto = 'dolimeet_color@dolimeet';
                break;
        }
    }
}
