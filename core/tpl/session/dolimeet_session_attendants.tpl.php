<?php

$width = 80; $cssclass = 'photoref';
dol_strlen($object->label) ? $morehtmlref = '<span>' . ' - ' . $object->label . '</span>' : '';
$morehtmlref                             .= '<div class="refidno">';

// Project

//$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('dolimeet', $conf->dolimeet->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';

dol_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

print dol_get_fiche_end(); ?>

<?php if ( $object->status == 1 ) : ?>
<!--	<div class="wpeo-notice notice-warning">-->
<!--		<div class="notice-content">-->
<!--			<div class="notice-title">--><?php //echo $langs->trans('DisclaimerSignatureTitle') ?><!--</div>-->
<!--			<div class="notice-subtitle">--><?php //echo $langs->trans(ucfirst($object->element)."MustBeValidatedToSign") ?><!--</div>-->
<!--		</div>-->
<!--		<a class="butAction" style="width = 100%;margin-right:0" href="--><?php //echo DOL_URL_ROOT ?><!--/custom/dolimeet/view/--><?php //echo $object->element ?><!--/--><?php //echo $object->element ?><!--_card.php?id=--><?php //echo $id ?><!--">--><?php //echo $langs->trans("GoToValidate") ?><!--</a>;-->
<!--	</div>-->
<?php endif; ?>
<!--	<div class="noticeSignatureSuccess wpeo-notice notice-success hidden">-->
<!--		<div class="all-notice-content">-->
<!--			<div class="notice-content">-->
<!--				<div class="notice-title">--><?php //echo $langs->trans('AddSignatureSuccess') ?><!--</div>-->
<!--				<div class="notice-subtitle">--><?php //echo $langs->trans("AddSignatureSuccessText") . GETPOST('signature_id')?><!--</div>-->
<!--			</div>-->
<!--			--><?php
//			if ($signatory->checkSignatoriesSignatures($object->id, $object->element)) {
//				print '<a class="butAction" style="width = 100%;margin-right:0" href="' . DOL_URL_ROOT . '/custom/dolimeet/view/'. $object->element .'/'. $object->element .'_card.php?id=' . $id . '">' . $langs->trans("GoToLock") . '</a>';
//			}
//			?>
<!--		</div>-->
<!--	</div>-->
<?php

// Part to show record
if ((empty($action) || ($action != 'create' && $action != 'edit'))) {

	//Society attendants -- Participants de la société
	$society_intervenants = $signatory->fetchSignatory(strtoupper($object->element).'_SOCIETY_ATTENDANT', $object->id, $object->element);
	$society_trainer = $signatory->fetchSignatory(strtoupper($object->element).'_SESSION_TRAINER', $object->id, $object->element);

	$society_intervenants = array_merge($society_intervenants, $society_trainer);

	print load_fiche_titre($langs->trans("Attendants"), '', '');

	print '<table class="border centpercent tableforfield">';

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Role") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureLink") . '</td>';
	print '<td class="center">' . $langs->trans("SendMailDate") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureDate") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("ActionsSignature") . '</td>';
	print '<td class="center">' . $langs->trans("Signature") . '</td>';
	print '</tr>';

	$already_added_users = array();
	$j = 1;
	if (is_array($society_intervenants) && ! empty($society_intervenants) && $society_intervenants > 0) {
		foreach ($society_intervenants as $element) {
			$usertmp = $user;
			$usertmp->fetch($element->element_id);
			print '<tr class="oddeven"><td class="minwidth200">';
			print $usertmp->getNomUrl(1);
			print '</td><td>';
			print $langs->trans($element->role);
			print '</td><td class="center">';
//			if ($object->status == 2) {
				$signatureUrl = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&type=' . $object->element, 3);
				print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
//			} else {
//				print '-';
//			}

			print '</td><td class="center">';
			print dol_print_date($element->last_email_sent_date, 'dayhour');
			print '</td><td class="center">';
			print dol_print_date($element->signature_date, 'dayhour');
			print '</td><td class="center">';
			print $element->getLibStatut(5);
			print '</td>';
			print '<td class="center">';
			if ($permissiontoadd) {
				require __DIR__ . "/../signature/dolimeet_signature_action_view.tpl.php";
			}
			print '</td>';
			if ($element->signature != $langs->transnoentities("FileGenerated") && $permissiontoadd) {
				print '<td class="center">';
				require __DIR__ . "/../signature/dolimeet_signature_view.tpl.php";
				print '</td>';
			}
			print '</tr>';
			$already_added_users[$element->element_id] = $element->element_id;
			$j++;
		}
	} else {
		print '<tr><td>';
		print $langs->trans('NoSocietyAttendants');
		print '</td></tr>';
	}

	if ($permissiontoadd) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addSocietyAttendant">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

		if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

		//Intervenants extérieurs
		print '<tr class="oddeven"><td class="maxwidth200">';
		print $form->select_dolusers('', 'user_attendant', 1, $already_added_users);
		print '</td>';
		print '<td>';
		print '<select id="attendantRole" name="attendantRole">';
		print '<option value="society_attendant">' . $langs->trans("SocietyAttendant") . '</option>';
		print '<option value="session_trainer">' . $langs->trans("SessionTrainer") . '</option>';
		print '</select>';
		print ajax_combobox('attendantRole');
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '<button type="submit" class="wpeo-button button-blue " name="addline" id="addline"><i class="fas fa-plus"></i>  ' . $langs->trans('Add') . '</button>';
		print '<td class="center">';
		print '-';
		print '</td>';
		print '</tr>';
		print '</table>' . "\n";
		print '</form>';
	}

	//External Society Intervenants -- Intervenants Société extérieure
	$thirdparty->fetch($object->fk_soc);
	$ext_society_intervenants = $signatory->fetchSignatory(strtoupper($object->element).'_EXTERNAL_ATTENDANT', $object->id, $object->element);

	print load_fiche_titre($langs->trans("ExternalAttendants"), '', '');

	print '<table class="border centpercent tableforfield">';

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Thirdparty") . '</td>';
	print '<td>' . $langs->trans("ContactLinked") . '</td>';
	print '<td>' . $langs->trans("Role") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureLink") . '</td>';
	print '<td class="center">' . $langs->trans("SendMailDate") . '</td>';
	print '<td class="center">' . $langs->trans("SignatureDate") . '</td>';
	print '<td class="center">' . $langs->trans("Status") . '</td>';
	print '<td class="center">' . $langs->trans("ActionsSignature") . '</td>';
	print '<td class="center">' . $langs->trans("Signature") . '</td>';
	print '</tr>';

	$already_selected_intervenants = array();
	$j                                           = 1;
	if (is_array($ext_society_intervenants) && ! empty($ext_society_intervenants) && $ext_society_intervenants > 0) {
		foreach ($ext_society_intervenants as $element) {
			$contact->fetch($element->element_id);
			print '<tr class="oddeven"><td class="minwidth200">';
			$thirdparty->fetch($contact->fk_soc);
			print $thirdparty->getNomUrl(1);
			print '</td><td>';
			print $contact->getNomUrl(1);
			print '</td><td>';
			print $langs->trans("ExtSocietyIntervenant");
			print '</td><td class="center">';
//			if ($object->status == 2) {
				$signatureUrl = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&type=' . $object->element, 3);
				print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
//			} else {
//				print '-';
//			}

			print '</td><td class="center">';
			print dol_print_date($element->last_email_sent_date, 'dayhour');
			print '</td><td class="center">';
			print dol_print_date($element->signature_date, 'dayhour');
			print '</td><td class="center">';
			print $element->getLibStatut(5);
			print '</td>';
			print '<td class="center">';
			if ($permissiontoadd) {
				require __DIR__ . "/../signature/dolimeet_signature_action_view.tpl.php";
			}
			print '</td>';
			if ($element->signature != $langs->transnoentities("FileGenerated") && $permissiontoadd) {
				print '<td class="center">';
				require __DIR__ . "/../signature/dolimeet_signature_view.tpl.php";
				print '</td>';
			}
			print '</tr>';
			$already_selected_intervenants[$element->element_id] = $element->element_id;
			$j++;
		}
	} else {
		print '<tr><td>';
		print $langs->trans('NoExternalAttendants');
		print '</td></tr>';
	}

	if ($permissiontoadd) {
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="addExternalAttendant">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

		if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

		//Intervenants extérieurs
		$ext_society = $object->fk_soc;
		if ($ext_society < 1) {
			$ext_society = new StdClass();
		}

		print '<tr class="oddeven">';
		print '<td class="tagtd nowrap noborderbottom">';
		$selectedCompany = GETPOSTISSET("newcompany") ? GETPOST("newcompany", 'int') : (empty($object->socid) ?  0 : $object->socid);
		$formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', '', 0, '', 'minwidth300imp');

		print '</td>';
		print '<td class="tagtd noborderbottom minwidth500imp">';
		print img_object('', 'contact', 'class="pictofixedwidth"').$form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), '', 'external_attendant', 1, $already_selected_intervenants, '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
		$nbofcontacts = $form->num;
		$newcardbutton = '';
		if (!empty(GETPOST('newcompany')) && GETPOST('newcompany') > 1 && $user->rights->societe->creer) {
			$newcardbutton .= '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$selectedCompany.'&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.'&newcompany=' . GETPOST('newcompany')).'" title="'.$langs->trans('NewContact').'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
		}
		print $newcardbutton;
		print '</td>';
		print '<td>' . $langs->trans("ExtSocietyIntervenant") . '</td>';
		print '<td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '-';
		print '</td><td class="center">';
		print '<button type="submit" class="wpeo-button button-blue " name="addline" id="addline"><i class="fas fa-plus"></i>  ' . $langs->trans('Add') . '</button>';
		print '<td class="center">';
		print '-';
		print '</td>';
		print '</tr>';
		print '</table>' . "\n";
		print '</form>';
		print '</div>';

	}
}

// End of page
llxFooter();
$db->close();
