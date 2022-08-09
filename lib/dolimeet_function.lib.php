<?php
/* Copyright (C) 2021	Noe Sellam	<noe.sellam@epitech.eu>
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
 * \file    lib/betterform.lib.php
 * \ingroup doliletter
 * \brief   unified form function
 */


function fetchAllAny($objecttype, $sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
	global $conf, $db;

	$objecttype = is_string($objecttype) ?: get_class($objecttype);
	$type = new $objecttype($db);
	$records = array();
	$sql = 'SELECT ';
	$sql .= $type->getFieldList();
	$sql .= ' FROM '.MAIN_DB_PREFIX.$type->table_element;
	if (isset($type->ismultientitymanaged) && $type->ismultientitymanaged == 1) $sql .= ' WHERE entity IN ('.getEntity($type->table_element).')';
	else $sql .= ' WHERE 1 = 1';

	// Manage filter
	$sqlwhere = array();
	if (count($filter) > 0) {
		foreach ($filter as $key => $value) {
			if ($key == 'rowid') {
				$sqlwhere[] = $key.'='.$value;
			} elseif (in_array($type->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
				$sqlwhere[] = $key.' = \''.$type->db->idate($value).'\'';
			} elseif ($key == 'customsql') {
				$sqlwhere[] = $value;
			} elseif (strpos($value, '%') === false) {
				$sqlwhere[] = $key.' IN ('.$type->db->sanitize($type->db->escape($value)).')';
			} else {
				$sqlwhere[] = $key.' LIKE \'%'.$type->db->escape($value).'%\'';
			}
		}
	}
	if (count($sqlwhere) > 0) {
		$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
	}

	if (!empty($sortfield)) {
		$sql .= $type->db->order($sortfield, $sortorder);
	}
	if (!empty($limit)) {
		$sql .= ' '.$type->db->plimit($limit, $offset);
	}

	$resql = $type->db->query($sql);

	if ($resql) {
		$num = $type->db->num_rows($resql);
		$i = 0;
		while ($i < ($limit ? min($limit, $num) : $num))
		{
			$obj = $type->db->fetch_object($resql);

			$record = new Facture($db);
			$record->setVarsFromFetchObj($obj);

			$records[$record->id] = $record;

			$i++;
		}
		$type->db->free($resql);

		return $records;
	} else {
		$type->errors[] = 'Error '.$type->db->lasterror();
		dol_syslog(__METHOD__.' '.join(',', $type->errors), LOG_ERR);

		return -1;
	}
}

/**
 * Prints form to select objects of a given type
 *
 * @param $objecttype object of the type to select from
 * @param string $sortorder Sort Order
 * @param string $sortfield Sort field
 * @param int $limit limit
 * @param int $offset Offset
 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
 * @param string $filtermode Filter mode (AND or OR)
 * @param string $htmlname html form name
 * @param string $htmlid html form id
 * @param array $notid array of int for ids of element not to print (eg. all but thoses ids to be printed
 * @param boolean $multiplechoices wether to allow multiple choices or not
 * @return int                 int <0 if KO, else returns string containing form
 */

function selectForm($objecttype, $htmlname = 'form[]', $htmlid ='', $notid = array(), $sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = array(), $filtermode = 'AND', $multiplechoices = true) {
	//$error = 0;
	$str = '';
	if ($notid === null){
		$notid = array();
	}
	//print '<div> debug  ';
	if ($htmlid == '' && preg_match( '/\[]/', $htmlname))
		$htmlid == substr($htmlname, 0, -2);
	$records =fetchAllAny($objecttype);

	$str .=('<select class="minwidth200" data-select2-id="'.$htmlname.'" name="' . $htmlname . '">');
	foreach ($records as $line) {
		if (!in_array($line->id, $notid)) {
			$str .= '<option data-select2-id="'.$line->id.$line->ref.'" value="' . $line->id . '">' . $line->ref . '</option>';
		}
	}
	$str .= '</select>';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
	$str .= ajax_combobox('select_'.$htmlname);
	return $str;
}

/**
 *      Return a string to show the box with list of available documents for object.
 *      This also set the property $this->numoffiles
 *
 * @param      string				$modulepart         Module the files are related to ('propal', 'facture', 'facture_fourn', 'mymodule', 'mymodule:nameofsubmodule', 'mymodule_temp', ...)
 * @param      string				$modulesubdir       Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
 * @param      string				$filedir            Directory to scan
 * @param      string				$urlsource          Url of origin page (for return)
 * @param      int|string[]        $genallowed         Generation is allowed (1/0 or array list of templates)
 * @param      int					$delallowed         Remove is allowed (1/0)
 * @param      string				$modelselected      Model to preselect by default
 * @param      int					$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
 * @param		int					$noform				Do not output html form tags
 * @param		string				$param				More param on http links
 * @param		string				$title				Title to show on top of form. Example: '' (Default to "Documents") or 'none'
 * @param		string				$buttonlabel		Label on submit button
 * @param		string				$morepicto			Add more HTML content into cell with picto
 * @param      Object              $object             Object when method is called from an object card.
 * @param		int					$hideifempty		Hide section of generated files if there is no file
 * @param      string              $removeaction       (optional) The action to remove a file
 * @param      bool                 $active             (optional) To show gen button disabled
 * @param      string              $tooltiptext       (optional) Tooltip text when gen button disabled
 * @return		string              					Output string with HTML array of documents (might be empty string)
 */
function dolilettershowdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $noform = 0, $param = '', $title = '', $buttonlabel = '', $morepicto = '', $object = null, $hideifempty = 0, $removeaction = 'remove_file', $active = true, $tooltiptext = '')
{

	global $db, $langs, $conf, $hookmanager, $form;

	if ( ! is_object($form)) $form = new Form($db);

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	// Add entity in $param if not already exists
	if ( ! preg_match('/entity\=[0-9]+/', $param)) {
		$param .= ($param ? '&' : '') . 'entity=' . ( ! empty($object->entity) ? $object->entity : $conf->entity);
	}

	$hookmanager->initHooks(array('formfile'));

	// Get list of files
	$file_list = null;
	if ( ! empty($filedir)) {
		$filter = '(\.odt|\.zip|\.pdf)';
		if ($modulepart == 'doliletter:AcknowledgementReceipt' || $modulepart == 'doliletter') {
			$filter = '(\.jpg|\.jpeg|\.png|\.odt|\.zip|\.pdf)';
		}
		$file_list = dol_dir_list($filedir, 'files', 0, $filter, '', 'date', SORT_DESC, 1);
	}

	if ($hideifempty && empty($file_list)) return '';

	$out         = '';
	$forname     = 'builddoc';
	$headershown = 0;
	$showempty   = 0;

	$out .= "\n" . '<!-- Start show_document -->' . "\n";

	$titletoshow                       = $langs->trans("Documents");
	if ( ! empty($title)) $titletoshow = ($title == 'none' ? '' : $title);

	$submodulepart = $modulepart;
	// modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'
	$tmp = explode(':', $modulepart);
	if ( ! empty($tmp[1])) {
		$modulepart    = $tmp[0];
		$submodulepart = $tmp[1];
	}

	// Show table
	if ($genallowed) {
		// For normalized external modules.
		$file = dol_buildpath('/' . $modulepart . '/core/modules/' . $modulepart . '/modules_' . strtolower($submodulepart) . '.php', 0);

		include_once $file;

		$class = 'ModelePDF' . $submodulepart;

		if (class_exists($class)) {
			if (preg_match('/specimen/', $param)) {
				$type      = strtolower($class) . 'specimen';
				include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
				$modellist = getListOfModels($db, $type, 0);
			} else {
				$modellist = call_user_func($class . '::liste_modeles', $db, 100);
			}
		} else {
			dol_print_error($db, "Bad value for modulepart '" . $modulepart . "' in showdocuments");
			return -1;
		}

		// Set headershown to avoid to have table opened a second time later
		$headershown = 1;

		if (empty($buttonlabel)) $buttonlabel = $langs->trans('Generate');

		if ($conf->browser->layout == 'phone') $urlsource .= '#' . $forname . '_form'; // So we switch to form after a generation
		if (empty($noform)) $out                          .= '<form action="' . $urlsource . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc') . '" id="' . $forname . '_form" method="post">';
		$out                                              .= '<input type="hidden" name="action" value="builddoc">';
		$out                                              .= '<input type="hidden" name="token" value="' . newToken() . '">';

		$out .= load_fiche_titre($titletoshow, '', '');
		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="liste formdoc noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		$addcolumforpicto = ($delallowed || $morepicto);
		$colspan          = (3 + ($addcolumforpicto ? 1 : 0)); $colspanmore = 0;

		$out .= '<th colspan="' . $colspan . '" class="formdoc liste_titre maxwidthonsmartphone center">';

		// Model
		if ( ! empty($modellist)) {
			asort($modellist);
			$out      .= '<span class="hideonsmartphone">' . $langs->trans('Model') . ' </span>';
			$modellist = array_filter($modellist, 'remove_index');
			if (is_array($modellist) && count($modellist) == 1) {    // If there is only one element
				$arraykeys                = array_keys($modellist);
				$arrayvalues              = preg_replace('/template_/', '', array_values($modellist)[0]);
				$modellist[$arraykeys[0]] = $arrayvalues;
				$modelselected            = $arraykeys[0];
			}
			$morecss                                        = 'maxwidth200';
			if ($conf->browser->layout == 'phone') $morecss = 'maxwidth100';
			$out                                           .= $form::selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);

			if ($conf->use_javascript_ajax) {
				$out .= ajax_combobox('model');
			}
		} else {
			$out .= '<div class="float">' . $langs->trans("Files") . '</div>';
		}

		// Button
		if ($active) {
			$genbutton  = '<input class="button buttongen" id="' . $forname . '_generatebutton" name="' . $forname . '_generatebutton"';
			$genbutton .= ' type="submit" value="' . $buttonlabel . '"';
		} else {
			$genbutton  = '<input class="button buttongen disabled" name="' . $forname . '_generatebutton" style="cursor: not-allowed"';
			$genbutton .= '  value="' . $buttonlabel . '"';
		}

		if ( ! $allowgenifempty && ! is_array($modellist) && empty($modellist)) $genbutton .= ' disabled';
		$genbutton                                                                         .= '>';
		if ($allowgenifempty && ! is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') {
			$langs->load("errors");
			$genbutton .= ' ' . img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
		}
		if ( ! $allowgenifempty && ! is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton = '';
		if (empty($modellist) && ! $showempty && $modulepart != 'unpaid') $genbutton                                                                      = '';
		$out                                                                                                                                             .= $genbutton;
		if ( ! $active) {
			$htmltooltip  = '';
			$htmltooltip .= $tooltiptext;

			$out .= '<span class="center">';
			$out .= $form->textwithpicto($langs->trans('Help'), $htmltooltip, 1, 0);
			$out .= '</span>';
		}

		$out .= '</th>';

		if ( ! empty($hookmanager->hooks['formfile'])) {
			foreach ($hookmanager->hooks['formfile'] as $module) {
				if (method_exists($module, 'formBuilddocLineOptions')) {
					$colspanmore++;
					$out .= '<th></th>';
				}
			}
		}
		$out .= '</tr>';

		// Execute hooks
		$parameters = array('colspan' => ($colspan + $colspanmore), 'socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart);
		if (is_object($hookmanager)) {
			$hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
			$out    .= $hookmanager->resPrint;
		}
	}

	// Get list of files
	if ( ! empty($filedir)) {
		$link_list = array();
		$addcolumforpicto = ($delallowed || $morepicto);
		$colspan          = (3 + ($addcolumforpicto ? 1 : 0)); $colspanmore = 0;
		if (is_object($object) && $object->id > 0) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
			$link      = new Link($db);
			$sortfield = $sortorder = null;
			$link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
		}

		$out .= '<!-- html.formfile::showdocuments -->' . "\n";

		// Show title of array if not already shown
		if (( ! empty($file_list) || ! empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
			&& ! $headershown) {
			$headershown = 1;
			$out        .= '<div class="titre">' . $titletoshow . '</div>' . "\n";
			$out        .= '<div class="div-table-responsive-no-min">';
			$out        .= '<table class="noborder centpercent" id="' . $modulepart . '_table">' . "\n";
		}

		// Loop on each file found
		if (is_array($file_list)) {
			foreach ($file_list as $file) {
				// Define relative path for download link (depends on module)
				$relativepath                    = $file["name"]; // Cas general
				if ($modulesubdir) $relativepath = $modulesubdir . "/" . $file["name"]; // Cas propal, facture...

				$out .= '<tr class="oddeven">';

				$documenturl                                                      = DOL_URL_ROOT . '/document.php';
				if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper

				// Show file name with link to download
				$out .= '<td class="minwidth200">';
				$out .= '<a class="documentdownload paddingright" href="' . $documenturl . '?modulepart=' . $modulepart . '&amp;file=' . urlencode($relativepath) . ($param ? '&' . $param : '') . '"';

				$mime                                  = dol_mimetype($relativepath, '', 0);
				if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
				$out                                  .= '>';
				$out                                  .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]);
				$out                                  .= dol_trunc($file["name"], 150);
				$out                                  .= '</a>' . "\n";

				// Preview
				if (!empty($conf->use_javascript_ajax) && ($conf->browser->layout != 'phone')) {
					$tmparray = getAdvancedPreviewUrl($modulepart, $relativepath, 1, '&entity='.$entity);
					if ($tmparray && $tmparray['url']) {
						$out .= '<a href="'.$tmparray['url'].'"'.($tmparray['css'] ? ' class="'.$tmparray['css'].'"' : '').($tmparray['mime'] ? ' mime="'.$tmparray['mime'].'"' : '').($tmparray['target'] ? ' target="'.$tmparray['target'].'"' : '').'>';
						//$out.= img_picto('','detail');
						$out .= '<i class="fa fa-search-plus paddingright" style="color: gray"></i>';
						$out .= '</a>';
					}
				}

				$out .= '</td>';



				// Show file size
				$size = ( ! empty($file['size']) ? $file['size'] : dol_filesize($filedir . "/" . $file["name"]));
				$out .= '<td class="nowrap right">' . dol_print_size($size, 1, 1) . '</td>';

				// Show file date
				$date = ( ! empty($file['date']) ? $file['date'] : dol_filemtime($filedir . "/" . $file["name"]));
				$out .= '<td class="nowrap right">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';

				if ($delallowed || $morepicto) {
					$out .= '<td class="right nowraponall">';
					if ($delallowed) {
						$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
						$out         .= '<a href="' . $tmpurlsource . ((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;') . 'action=' . $removeaction . '&amp;file=' . urlencode($relativepath);
						$out         .= ($param ? '&amp;' . $param : '');
						$out         .= '">' . img_picto($langs->trans("Delete"), 'delete') . '</a>';
					}
					if ($morepicto) {
						$morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
						$out      .= $morepicto;
					}
					$out .= '</td>';
				}

				if (is_object($hookmanager)) {
					$parameters = array('colspan' => ($colspan + $colspanmore), 'socid' => (isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id' => (isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart' => $modulepart, 'relativepath' => $relativepath);
					$res        = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
					if (empty($res)) {
						$out .= $hookmanager->resPrint; // Complete line
						$out .= '</tr>';
					} else {
						$out = $hookmanager->resPrint; // Replace all $out
					}
				}
			}
		}
		// Loop on each link found
		//      if (is_array($link_list))
		//      {
		//          $colspan = 2;
		//
		//          foreach ($link_list as $file)
		//          {
		//              $out .= '<tr class="oddeven">';
		//              $out .= '<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
		//              $out .= '<a data-ajax="false" href="'.$file->url.'" target="_blank">';
		//              $out .= $file->label;
		//              $out .= '</a>';
		//              $out .= '</td>';
		//              $out .= '<td class="right">';
		//              $out .= dol_print_date($file->datea, 'dayhour');
		//              $out .= '</td>';
		//              if ($delallowed || $printer || $morepicto) $out .= '<td></td>';
		//              $out .= '</tr>'."\n";
		//          }
		//      }

		if (count($file_list) == 0 && count($link_list) == 0 && $headershown) {
			$out .= '<tr><td colspan="' . (3 + ($addcolumforpicto ? 1 : 0)) . '" class="opacitymedium">' . $langs->trans("None") . '</td></tr>' . "\n";
		}
	}

	if ($headershown) {
		// Affiche pied du tableau
		$out .= "</table>\n";
		$out .= "</div>\n";
		if ($genallowed) {
			if (empty($noform)) $out .= '</form>' . "\n";
		}
	}
	$out .= '<!-- End show_document -->' . "\n";

	return $out;
}

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
//
///**
// *	Fetch array of objects linked to current object (object of enabled modules only). Links are loaded into
// *		this->linkedObjectsIds array +
// *		this->linkedObjects array if $loadalsoobjects = 1
// *  Possible usage for parameters:
// *  - all parameters empty -> we look all link to current object (current object can be source or target)
// *  - source id+type -> will get target list linked to source
// *  - target id+type -> will get source list linked to target
// *  - source id+type + target type -> will get target list of the type
// *  - target id+type + target source -> will get source list of the type
// *
// *	@param	int		$sourceid			Object source id (if not defined, id of object)
// *	@param  string	$sourcetype			Object source type (if not defined, element name of object)
// *	@param  int		$targetid			Object target id (if not defined, id of object)
// *	@param  string	$targettype			Object target type (if not defined, elemennt name of object)
// *	@param  string	$clause				'OR' or 'AND' clause used when both source id and target id are provided
// *  @param  int		$alsosametype		0=Return only links to object that differs from source type. 1=Include also link to objects of same type.
// *  @param  string	$orderby			SQL 'ORDER BY' clause
// *  @param	int		$loadalsoobjects	Load also array this->linkedObjects (Use 0 to increase performances)
// *	@return int							<0 if KO, >0 if OK
// *  @see	add_object_linked(), updateObjectLinked(), deleteObjectLinked()
// */
//function fetchObjectLinked($objectLinked, $sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
//{
//	global $conf;
//
//	$objectLinked->linkedObjectsIds = array();
//	$objectLinked->linkedObjects = array();
//
//	$justsource = false;
//	$justtarget = false;
//	$withtargettype = false;
//	$withsourcetype = false;
//
//	if (!empty($sourceid) && !empty($sourcetype) && empty($targetid)) {
//		$justsource = true; // the source (id and type) is a search criteria
//		if (!empty($targettype)) {
//			$withtargettype = true;
//		}
//	}
//	if (!empty($targetid) && !empty($targettype) && empty($sourceid)) {
//		$justtarget = true; // the target (id and type) is a search criteria
//		if (!empty($sourcetype)) {
//			$withsourcetype = true;
//		}
//	}
//
//	$sourceid = (!empty($sourceid) ? $sourceid : $objectLinked->id);
//	$targetid = (!empty($targetid) ? $targetid : $objectLinked->id);
//	$sourcetype = (!empty($sourcetype) ? $sourcetype : $objectLinked->element);
//	$targettype = (!empty($targettype) ? $targettype : $objectLinked->element);
//
//	/*if (empty($sourceid) && empty($targetid))
//	 {
//	 dol_syslog('Bad usage of function. No source nor target id defined (nor as parameter nor as object id)', LOG_ERR);
//	 return -1;
//	 }*/
//
//	// Links between objects are stored in table element_element
//	$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
//	$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
//	$sql .= " WHERE ";
//	if ($justsource || $justtarget) {
//		if ($justsource) {
//			$sql .= "fk_source = ".((int) $sourceid)." AND sourcetype = '".$objectLinked->db->escape($sourcetype)."'";
//			if ($withtargettype) {
//				$sql .= " AND targettype = '".$objectLinked->db->escape($targettype)."'";
//			}
//		} elseif ($justtarget) {
//			$sql .= "fk_target = ".((int) $targetid)." AND targettype = '".$objectLinked->db->escape($targettype)."'";
//			if ($withsourcetype) {
//				$sql .= " AND sourcetype = '".$objectLinked->db->escape($sourcetype)."'";
//			}
//		}
//	} else {
//		$sql .= "(fk_source = ".((int) $sourceid)." AND sourcetype = '".$objectLinked->db->escape($sourcetype)."')";
//		$sql .= " ".$clause." (fk_target = ".((int) $targetid)." AND targettype = '".$objectLinked->db->escape($targettype)."')";
//	}
//	$sql .= ' ORDER BY '.$orderby;
//
//	dol_syslog(get_class($objectLinked)."::fetchObjectLink", LOG_DEBUG);
//	$resql = $objectLinked->db->query($sql);
//
//	if ($resql) {
//		$num = $objectLinked->db->num_rows($resql);
//		$i = 0;
//		while ($i < $num) {
//			$obj = $objectLinked->db->fetch_object($resql);
//			if ($justsource || $justtarget) {
//				if ($justsource) {
//					$objectLinked->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
//				} elseif ($justtarget) {
//					$objectLinked->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
//				}
//			} else {
//				if ($obj->fk_source == $sourceid && $obj->sourcetype == $sourcetype) {
//					$objectLinked->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
//				}
//				if ($obj->fk_target == $targetid && $obj->targettype == $targettype) {
//					$objectLinked->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
//				}
//			}
//
//			$i++;
//		}
//
//		if (!empty($objectLinked->linkedObjectsIds)) {
//			$tmparray = $objectLinked->linkedObjectsIds;
//			foreach ($tmparray as $objecttype => $objectids) {       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
//				// Parse element/subelement (ex: project_task, cabinetmed_consultation, ...)
//				$module = $element = $subelement = $objecttype;
//				$regs = array();
//				if ($objecttype != 'supplier_proposal' && $objecttype != 'order_supplier' && $objecttype != 'invoice_supplier'
//					&& preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
//					$module = $element = $regs[1];
//
//					$subelement = $regs[2];
//				}
//
//				$classpath = $element.'/class';
//				// To work with non standard classpath or module name
//				if ($objecttype == 'facture') {
//					$classpath = 'compta/facture/class';
//				} elseif ($objecttype == 'facturerec') {
//					$classpath = 'compta/facture/class';
//					$module = 'facture';
//				} elseif ($objecttype == 'propal') {
//					$classpath = 'comm/propal/class';
//				} elseif ($objecttype == 'supplier_proposal') {
//					$classpath = 'supplier_proposal/class';
//				} elseif ($objecttype == 'shipping') {
//					$classpath = 'expedition/class';
//					$subelement = 'expedition';
//					$module = 'expedition_bon';
//				} elseif ($objecttype == 'delivery') {
//					$classpath = 'delivery/class';
//					$subelement = 'delivery';
//					$module = 'delivery_note';
//				} elseif ($objecttype == 'invoice_supplier' || $objecttype == 'order_supplier') {
//					$classpath = 'fourn/class';
//					$module = 'fournisseur';
//				} elseif ($objecttype == 'fichinter') {
//					$classpath = 'fichinter/class';
//					$subelement = 'fichinter';
//					$module = 'ficheinter';
//				} elseif ($objecttype == 'subscription') {
//					$classpath = 'adherents/class';
//					$module = 'adherent';
//				} elseif ($objecttype == 'contact') {
//					$module = 'societe';
//				}
//				// Set classfile
//				$classfile = strtolower($subelement);
//				$classname = ucfirst($subelement);
//
//				if ($objecttype == 'order') {
//					$classfile = 'commande';
//					$classname = 'Commande';
//				} elseif ($objecttype == 'invoice_supplier') {
//					$classfile = 'fournisseur.facture';
//					$classname = 'FactureFournisseur';
//				} elseif ($objecttype == 'order_supplier') {
//					$classfile = 'fournisseur.commande';
//					$classname = 'CommandeFournisseur';
//				} elseif ($objecttype == 'supplier_proposal') {
//					$classfile = 'supplier_proposal';
//					$classname = 'SupplierProposal';
//				} elseif ($objecttype == 'facturerec') {
//					$classfile = 'facture-rec';
//					$classname = 'FactureRec';
//				} elseif ($objecttype == 'subscription') {
//					$classfile = 'subscription';
//					$classname = 'Subscription';
//				} elseif ($objecttype == 'project' || $objecttype == 'projet') {
//					$classpath = 'projet/class';
//					$classfile = 'project';
//					$classname = 'Project';
//				} elseif ($objecttype == 'conferenceorboothattendee') {
//					$classpath = 'eventorganization/class';
//					$classfile = 'conferenceorboothattendee';
//					$classname = 'ConferenceOrBoothAttendee';
//					$module = 'eventorganization';
//				} elseif ($objecttype == 'conferenceorbooth') {
//					$classpath = 'eventorganization/class';
//					$classfile = 'conferenceorbooth';
//					$classname = 'ConferenceOrBooth';
//					$module = 'eventorganization';
//				} elseif ($objecttype == 'envelope') {
//					$classpath = 'custom/doliletter/class';
//					$classfile = 'envelope';
//					$classname = 'envelope';
//					$module = 'doliletter';
//				}
//
//				// Here $module, $classfile and $classname are set
//				if ($conf->$module->enabled && (($element != $objectLinked->element) || $alsosametype)) {
//					if ($loadalsoobjects) {
//						dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
//						//print '/'.$classpath.'/'.$classfile.'.class.php '.class_exists($classname);
//						if (class_exists($classname)) {
//							foreach ($objectids as $i => $objectid) {	// $i is rowid into llx_element_element
//								$object = new $classname($objectLinked->db);
//								$ret = $object->fetch($objectid);
//								if ($ret >= 0) {
//									$objectLinked->linkedObjects[$objecttype][$i] = $object;
//								}
//							}
//						}
//					}
//				} else {
//					unset($objectLinked->linkedObjectsIds[$objecttype]);
//				}
//			}
//		}
//		return $objectLinked;
//	} else {
//		dol_print_error($objectLinked->db);
//		return -1;
//	}
//}
