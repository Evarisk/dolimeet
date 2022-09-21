<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * \file    class/actions_dolimeet.class.php
 * \ingroup dolimeet
 * \brief   DoliMeet hook overload.
 */

/**
 * Class ActionsDolimeet
 */
class ActionsDolimeet
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the constructCategory function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function constructCategory($parameters, &$object)
	{
		$error = 0; // Error counter

		if (in_array($parameters['currentcontext'], array('category', 'somecontext2'))) { // do something only for the context 'somecontext1' or 'somecontext2'
			$tags = array(
				'meeting' => array(
					'id' => 1050,
					'code' => 'meeting',
					'obj_class' => 'Meeting',
					'obj_table' => 'dolimeet_session',
				),
				'trainingsession' => array(
					'id' => 1051,
					'code' => 'trainingsession',
					'obj_class' => 'TrainingSession',
					'obj_table' => 'dolimeet_session',
				),
				'audit' => array(
					'id' => 1052,
					'code' => 'audit',
					'obj_class' => 'Audit',
					'obj_table' => 'dolimeet_session',
				),
			);
		}

		if (!$error) {
			$this->results = $tags;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @param $action
	 * @return int              < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function completeListOfReferent($parameters, $object, $action)
	{
		global $db, $conf, $langs;



		if (true) {
			$this->results   = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $langs, $db, $conf;
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'projectOverview') {
			require_once DOL_DOCUMENT_ROOT . '/custom/dolimeet/class/session.class.php';

			$session = new Session($db);
			$linkedSessions = $session->fetchAll('','','','',array("fk_project" => GETPOST('id')));

			$outputline = '<table><tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block"><img src="'. DOL_URL_ROOT .'/custom/dolimeet/img/dolimeet32px.png"> '. $langs->transnoentities('DoliMeetObjects') .'</div></td></tr></table>';
			$outputline .= '<table><div class="div-table-responsive-no-min"><table class="liste formdoc noborder centpercent"><tbody>';
			$outputline .= '<tr class="liste_titre">';
			$outputline .= '<td class="float">'. $langs->transnoentities('ObjectType') .'</td>&nbsp;';
			$outputline .= '<td class="float">'. $langs->transnoentities('Object') .'</td>&nbsp;';
			$outputline .= '<td class="float">'. $langs->transnoentities('Date') .'</td>&nbsp;';
			$outputline .= '</tr>';

			if (!empty($linkedSessions)) {
				foreach($linkedSessions as $linkedSession) {
					$outputline .= '<tr>';
					$outputline .= '<td>';
					$outputline .= $langs->trans(ucfirst($linkedSession->type));
					$outputline .= '</td>';
					$outputline .= '<td>';
					$outputline .= $linkedSession->getNomUrl();
					$outputline .= '</td>';
					$outputline .= '<td>';
					$outputline .= dol_print_date($linkedSession->date_start, 'dayhour') . ' - ' . dol_print_date($linkedSession->date_end, 'dayhour');
					$outputline .= '</td>';
					$outputline .= '</tr>';
				}
			}
			$outputline .= '</tbody></table></div>';
			?>
			<script>
				jQuery('.fiche').append(<?php echo json_encode($outputline); ?>)
			</script>
			<?php
		}

		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			$form      = new Form($db);
			$pictopath = dol_buildpath('/dolimeet/img/dolimeet32px.png', 1);
			$pictoDolimeet = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoDigirisk');
			$training_organization_number_input = '<input name="MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER" id="MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER" value="'. $conf->global->MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER .'">';
			?>
			<script>
				let trainingOrganizationNumberInput = $('<tr class="oddeven"><td><label for="training_organization_number"><?php print $pictoDolimeet . $form->textwithpicto($langs->trans('TrainingOrganizationNumber'), $langs->trans('TrainingOrganizationNumberTooltip'));?></label></td>');
				trainingOrganizationNumberInput.append('<td>' + <?php echo json_encode($training_organization_number_input) ; ?> + '</td></tr>');

				let element = $('table:nth-child(1) .oddeven:last-child');
				element.after(trainingOrganizationNumberInput);
			</script>
			<?php
		}

		if (preg_match('/categoryindex/', $parameters['context'])) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			print '<script src="../custom/dolimeet/js/dolimeet.js.php"></script>';
		}

		if (!$error) {
			$this->results   = array('myreturn' => 999);
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @param $action
	 * @return int              < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, $object, $action)
	{
		global $db, $conf;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($action == 'update') {
				dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER", GETPOST("MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER"), 'chaine', 0, '', $conf->entity);
			}
		}
	}

}
