<?php

/**
 * Return string with full online Url to accept and sign a quote
 *
 * @param   string			$type		Type of URL ('proposal', ...)
 * @param	string			$ref		Ref of object
 * @param   CommonObject 	$obj  		object (needed to make multicompany good links)
 * @param	string			$mode		Mode
 * @return	string						Url string
 */
function showOnlineSignatureUrl2($type, $ref, $obj = null, $mode = '', $contact = [])
{
    global $langs;

    // Load translation files required by the page
    $langs->loadLangs(array("payment", "stripe"));

    $servicename = 'Online';

    $out = '';
    if ($mode != 'short') {
        $out .= img_picto('', 'globe', 'class="pictofixedwidth"');
    }
    $contactObject = new Contact($obj->db);
    $contactObject->fetch($contact['id']);
    $out .= '<span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlineSignature", $servicename) . '</span>' . ' - ' . $contactObject->getNomUrl(1) . '<br>';
    $url = getOnlineSignatureUrl2(0, $type, $ref, 1, $obj, $contact);
    $out .= '<div class="urllink">';
    if ($url == $langs->trans("FeatureOnlineSignDisabled")) {
        $out .= $url;
    } else {
        $out .= '<input type="text" id="onlinesignatureurl" class="'.($mode == 'short' ? 'centpercentminusx' : 'quatrevingtpercentminusx').'" value="'.$url.'">';
    }
    $out .= '<a class="" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
    $out .= '</div>';
    $out .= ajax_autoselect("onlinesignatureurl", '');
    return $out;
}

/**
 * Return string with full Url
 *
 * @param   int				$mode				0=True url, 1=Url formatted with colors
 * @param   string			$type				Type of URL ('proposal', ...)
 * @param	string			$ref				Ref of object
 * @param   int     		$localorexternal  	0=Url for browser, 1=Url for external access
 * @param   CommonObject  	$obj  				object (needed to make multicompany good links)
 * @return	string								Url string
 */
function getOnlineSignatureUrl2($mode, $type, $ref = '', $localorexternal = 1, $obj = null, $contact = [])
{
    global $dolibarr_main_url_root;

    if (empty($obj)) {
        // For compatibility with 15.0 -> 19.0
        global $object;
        if (empty($object)) {
            $obj = new stdClass();
        } else {
            dol_syslog(__FUNCTION__." using global object is deprecated, please give obj as argument", LOG_WARNING);
            $obj = $object;
        }
    }

    $out = '';

    // Define $urlwithroot
    $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
    $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
    //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

    $urltouse = DOL_MAIN_URL_ROOT;
    if ($localorexternal) {
        $urltouse = $urlwithroot;
    }

    $securekeyseed = '';

    if ($type == 'proposal') {
        $securekeyseed = getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN');
        if (strpos($securekeyseed, "\0") !== false) {
            // String contains a null character that can't be encoded. Return an error to avoid fatal error later.
            return 'Invalid parameter PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN. Contains a null character.';
        }

        $out = $urltouse.'/public/onlinesign/newonlinesign.php?source=proposal&ref='.($mode ? '<span style="color: #666666">' : '');
        if ($mode == 1) {
            $out .= 'proposal_ref';
        }
        if ($mode == 0) {
            $out .= urlencode($ref);
        }
        $out .= ($mode ? '</span>' : '');
        if ($mode == 1) {
            $out .= "hash('".$securekeyseed."' + '".$type."' + proposal_ref)";
        } else {
            $out .= '&securekey='.dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : $obj->entity) : ''), '0');
        }
        /*
        if ($mode == 1) {
            $out .= '&hashp=<span style="color: #666666">hash_of_file</span>';
        } else {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $propaltmp = new Propal($db);
            $res = $propaltmp->fetch(0, $ref);
            if ($res <= 0) {
                return 'FailedToGetProposal';
            }

            include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
            $ecmfile = new EcmFiles($db);

            $ecmfile->fetch(0, '', $propaltmp->last_main_doc);

            $hashp = $ecmfile->share;
            if (empty($hashp)) {
                $out = $langs->trans("FeatureOnlineSignDisabled");
                return $out;
            } else {
                $out .= '&hashp='.$hashp;
            }
        }*/
    } elseif ($type == 'contract') {
        $securekeyseed = getDolGlobalString('CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN');
        if (strpos($securekeyseed, "\0") !== false) {
            // String contains a null character that can't be encoded. Return an error to avoid fatal error later.
            return 'Invalid parameter CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN. Contains a null character.';
        }

        $out = $urltouse.'/custom/dolimeet/core/dolibarr/newonlinesign.php?source=contract&ref='.($mode ? '<span style="color: #666666">' : '');
        if ($mode == 1) {
            $out .= 'contract_ref';
        }
        if ($mode == 0) {
            $out .= urlencode($ref);
        }
        $out .= ($mode ? '</span>' : '');
        if ($mode == 1) {
            $out .= "hash('".$securekeyseed."' + '".$type."' + contract_ref)";
        } else {
            $out .= '&securekey='.dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : (int) $obj->entity) : ''), '0');
        }
        $out .= '&contactid='.(empty($contact) ? '' : (int) $contact['rowid']);
    } elseif ($type == 'fichinter') {
        $securekeyseed = getDolGlobalString('FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN');
        if (strpos($securekeyseed, "\0") !== false) {
            // String contains a null character that can't be encoded. Return an error to avoid fatal error later.
            return 'Invalid parameter FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN. Contains a null character.';
        }

        $out = $urltouse.'/public/onlinesign/newonlinesign.php?source=fichinter&ref='.($mode ? '<span style="color: #666666">' : '');
        if ($mode == 1) {
            $out .= 'fichinter_ref';
        }
        if ($mode == 0) {
            $out .= urlencode($ref);
        }
        $out .= ($mode ? '</span>' : '');
        if ($mode == 1) {
            $out .= "hash('".$securekeyseed."' + '".$type."' + fichinter_ref)";
        } else {
            $out .= '&securekey='.dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : (int) $obj->entity) : ''), '0');
        }
    } else {	// For example $type = 'societe_rib'
        $securekeyseed = getDolGlobalString(dol_strtoupper($type).'_ONLINE_SIGNATURE_SECURITY_TOKEN');
        if (strpos($securekeyseed, "\0") !== false) {
            // String contains a null character that can't be encoded. Return an error to avoid fatal error later.
            return 'Invalid parameter '.dol_strtoupper($type).'_ONLINE_SIGNATURE_SECURITY_TOKEN. Contains a null character.';
        }

        $out = $urltouse.'/public/onlinesign/newonlinesign.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
        if ($mode == 1) {
            $out .= $type.'_ref';
        }
        if ($mode == 0) {
            $out .= urlencode($ref);
        }
        $out .= ($mode ? '</span>' : '');
        if ($mode == 1) {
            $out .= "hash('".$securekeyseed."' + '".$type."' + $type + '_ref)";
        } else {
            $out .= '&securekey='.dol_hash($securekeyseed.$type.$ref.(!isModEnabled('multicompany') ? '' : $obj->entity), '0');
        }
    }

    // For multicompany
    if (!empty($out) && isModEnabled('multicompany')) {
        $out .= "&entity=".(empty($obj->entity) ? '' : (int) $obj->entity); // Check the entity because we may have the same reference in several entities
    }

    return $out;
}

// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 *    Get array of all contacts for an object
 *
 *    @param	int			$statusoflink	Status of links to get (-1=all). Not used.
 *    @param	'external'|'thirdparty'|'internal'		$source			Source of contact: 'external' or 'thirdparty' (llx_socpeople) or 'internal' (llx_user)
 *    @param	int<0,1>	$list       	0:Returned array contains all properties, 1:Return array contains just id
 *    @param    string      $code       	Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
 *    @param	int			$status			Status of user or company
 *    @param	int[]		$arrayoftcids	Array with ID of type of contacts. If we provide this, we can filter on ec.fk_c_type_contact IN ($arrayoftcids) to avoid a link on c_type_contact table (faster).
 *    @return array<int,array{parentId:int,source:string,socid:int,id:int,nom:string,civility:string,lastname:string,firstname:string,email:string,login:string,photo:string,gender:string,statuscontact:int,rowid:int,code:string,libelle:string,status:int,fk_c_type_contact:int}>|int<-1,-1>        	Array of contacts, -1 if error
 */
function listeContact($object, $statusoflink = -1, $source = 'external', $list = 0, $code = '', $status = -1, $arrayoftcids = array())
{
    // phpcs:enable
    global $langs;

    $tab = array();

    $sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, ec.fk_c_type_contact, ec.mandatory_signature"; // This field contains id of llx_socpeople or id of llx_user
    if ($source == 'internal') {
        $sql .= ", '-1' as socid, t.statut as statuscontact, t.login, t.photo, t.gender, t.fk_country as country_id";
    }
    if ($source == 'external' || $source == 'thirdparty') {
        $sql .= ", t.fk_soc as socid, t.statut as statuscontact, t.fk_pays as country_id";
    }
    $sql .= ", t.civility as civility, t.lastname as lastname, t.firstname, t.email, t.address, t.zip, t.town";
    if (empty($arrayoftcids)) {
        $sql .= ", tc.source, tc.element, tc.code, tc.libelle as type_label, co.label as country";
    }
    $sql .= " FROM";
    if (empty($arrayoftcids)) {
        $sql .= " ".$object->db->prefix()."c_type_contact as tc,";
    }
    $sql .= " ".$object->db->prefix()."element_contact as ec";
    if ($source == 'internal') {	// internal contact (user)
        $sql .= " LEFT JOIN ".$object->db->prefix()."user as t on ec.fk_socpeople = t.rowid";
        $sql .= " LEFT JOIN ".$object->db->prefix()."c_country as co ON co.rowid = t.fk_country";
    }
    if ($source == 'external' || $source == 'thirdparty') {	// external contact (socpeople)
        $sql .= " LEFT JOIN ".$object->db->prefix()."socpeople as t on ec.fk_socpeople = t.rowid";
        $sql .= " LEFT JOIN ".$object->db->prefix()."c_country as co ON co.rowid = t.fk_pays";
    }
    $sql .= " WHERE ec.element_id = ".((int) $object->id);
    if (empty($arrayoftcids)) {
        $sql .= " AND ec.fk_c_type_contact = tc.rowid";
        $sql .= " AND tc.element = '".$object->db->escape($object->element)."'";
        if ($code) {
            $sql .= " AND tc.code = '".$object->db->escape($code)."'";
        }
        if ($source == 'internal') {
            $sql .= " AND tc.source = 'internal'";
        }
        if ($source == 'external' || $source == 'thirdparty') {
            $sql .= " AND tc.source = 'external'";
        }
        $sql .= " AND tc.active = 1";
    } else {
        $sql .= " AND ec.fk_c_type_contact IN (".$object->db->sanitize(implode(',', $arrayoftcids)).")";
    }
    if ($status >= 0) {
        $sql .= " AND t.statut = ".((int) $status);	// t is llx_user or llx_socpeople
    }
    if ($statusoflink >= 0) {
        $sql .= " AND ec.statut = ".((int) $statusoflink);
    }
    $sql .= " ORDER BY t.lastname ASC";

    dol_syslog(get_class($object)."::liste_contact", LOG_DEBUG);
    $resql = $object->db->query($sql);
    if ($resql) {
        $num = $object->db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $obj = $object->db->fetch_object($resql);

            if (!$list) {
                $transkey = "TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
                $libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->type_label);
                $tab[$i] = array(
                    'parentId' => $object->id,
                    'source' => $obj->source,
                    'socid' => $obj->socid,
                    'id' => $obj->id,
                    'nom' => $obj->lastname, // For backward compatibility
                    'civility' => $obj->civility,
                    'lastname' => $obj->lastname,
                    'firstname' => $obj->firstname,
                    'email' => $obj->email,
                    'address' => $obj->address,
                    'zip' => $obj->zip,
                    'town' => $obj->town,
                    'country_id' => $obj->country_id,
                    'country' => $obj->country,
                    'login' => (empty($obj->login) ? '' : $obj->login),
                    'photo' => (empty($obj->photo) ? '' : $obj->photo),
                    'gender' => (empty($obj->gender) ? '' : $obj->gender),
                    'statuscontact' => $obj->statuscontact,
                    'rowid' => $obj->rowid,
                    'code' => $obj->code,
                    'libelle' => $libelle_type,
                    'status' => (int) $obj->statuslink,
                    'fk_c_type_contact' => $obj->fk_c_type_contact,
                    'mandatory_signature' => $obj->mandatory_signature
                );
            } else {
                $tab[$i] = $obj->id;
            }

            $i++;
        }

        return $tab;
    } else {
        $object->error = $object->db->lasterror();
        dol_print_error($object->db);
        return -1;
    }
}
