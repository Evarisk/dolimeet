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
	public DoliDB $db;

	/**
	 * @var string Error code (or message)
	 */
	public string $error = '';

	/**
	 * @var array Errors
	 */
	public array $errors = [];

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public array $results = [];

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public string $resprints;

	/**
	 * Constructor
	 *
	 *  @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the constructCategory function : replacing the parent's function with the one below
	 *
	 * @param  array $parameters Hook metadatas (context, etc...)
	 * @return int               0 < on error, 0 on success, 1 to replace standard code
	 */
	public function constructCategory(array $parameters): int
    {
        // Do something only for the current context
		if ($parameters['currentcontext'] == 'category') {
			$tags = [
				'meeting' => [
					'id' => 436304001,
					'code' => 'meeting',
					'obj_class' => 'Meeting',
					'obj_table' => 'dolimeet_session',
                ],
				'trainingsession' => [
					'id' => 436304002,
					'code' => 'trainingsession',
					'obj_class' => 'TrainingSession',
					'obj_table' => 'dolimeet_session',
                ],
				'audit' => [
					'id' => 436304003,
					'code' => 'audit',
					'obj_class' => 'Audit',
					'obj_table' => 'dolimeet_session',
                ],
            ];
            $this->results = $tags;
        }

        return 0; // or return 1 to replace standard code
	}

    /**
     * Overloading the printCommonFooter function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
	public function printCommonFooter(array $parameters): int
    {
		global $langs, $db, $conf;

        // Do something only for the current context
		if ($parameters['currentcontext'] == 'projectOverview') {
			require_once DOL_DOCUMENT_ROOT . '/custom/dolimeet/class/session.class.php';

            $pictopath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
            $picto = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

			$session = new Session($db, 'session');
			$linkedSessions = $session->fetchAll('','',0,0, ['fk_project' => GETPOST('id')]);

			$outputline = '<table><tr class="titre"><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">' . $picto . $langs->transnoentities('SessionListOnProject') . '</div></td></tr></table>';
			$outputline .= '<table><div class="div-table-responsive-no-min"><table class="liste formdoc noborder centpercent"><tbody>';
			$outputline .= '<tr class="liste_titre">';
			$outputline .= '<td class="float">' . $langs->transnoentities('Type') . '</td>';
			$outputline .= '<td class="float">' . $langs->transnoentities('Ref') . '</td>';
			$outputline .= '<td class="float">' . $langs->transnoentities('Date') . '</td>';
            $outputline .= '<td class="float">' . $langs->transnoentities('Status') . '</td>';
			$outputline .= '</tr>';

			if (!empty($linkedSessions)) {
				foreach($linkedSessions as $linkedSession) {
					$outputline .= '<tr><td>';
					$outputline .= $langs->trans(ucfirst($linkedSession->type));
					$outputline .= '</td><td>';
					$outputline .= $linkedSession->getNomUrl();
					$outputline .= '</td><td>';
					$outputline .= dol_print_date($linkedSession->date_start, 'dayhour') . ' - ' . dol_print_date($linkedSession->date_end, 'dayhour');
                    $outputline .= '</td><td>';
                    $outputline .= $linkedSession->getLibStatut(5);
					$outputline .= '</td></tr>';
				}
			} else {
                $outputline .= '<tr><td colspan="4">';
                $outputline .= '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
                $outputline .= '</td></tr>';
            }
			$outputline .= '</tbody></table></div>';
			?>
			<script>
				jQuery('.fiche').append(<?php echo json_encode($outputline); ?>)
			</script>
			<?php
		}

        // Do something only for the current context
		if ($parameters['currentcontext'] == 'admincompany') {
			$form      = new Form($db);
			$pictopath = dol_buildpath('/dolimeet/img/dolimeet_color.png', 1);
            $picto = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');
			$trainingOrganizationNumberInput = '<input name="MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER" id="MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER" value="'. $conf->global->MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER .'">';
			?>
			<script>
				let trainingOrganizationNumberInput = $('<tr class="oddeven"><td><label for="training_organization_number"><?php print $picto . $form->textwithpicto($langs->trans('TrainingOrganizationNumber'), $langs->trans('TrainingOrganizationNumberTooltip'));?></label></td>');
				trainingOrganizationNumberInput.append('<td>' + <?php echo json_encode($trainingOrganizationNumberInput) ; ?> + '</td></tr>');

				let element = $('table:nth-child(1) .oddeven:last-child');
				element.after(trainingOrganizationNumberInput);
			</script>
			<?php
		}

        return 0; // or return 1 to replace standard code
	}


	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
	 */
	public function doActions(array $parameters, $object, string $action): int
    {
		global $conf, $db;

        // Do something only for the current context
		if ($parameters['currentcontext'] == 'admincompany') {
			if ($action == 'update') {
				dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER', GETPOST('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'), 'chaine', 0, '', $conf->entity);
			}
		}

        return 0; // or return 1 to replace standard code
	}

    /**
     *  Overloading the saturneBannerTab function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneBannerTab(array $parameters, CommonObject $object): int
    {
        global $db, $langs;
        
        // Do something only for the current context
        if ($parameters['currentcontext'] == 'saturnecard') {
            if (isModEnabled('contrat')) {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                if (GETPOST('object_type') == 'trainingsession') {
                    if (!empty($object->fk_contrat)) {
                        $contract = new Contrat($db);
                        $contract->fetch($object->fk_contrat);
                        $morehtmlref = $langs->trans('Contract') . ' : ' . $contract->getNomUrl(1) . '<br>';
                    } else {
                        $morehtmlref = $langs->trans('Contract') . ' : ' . '<br>';
                    }
                    $this->resprints = $morehtmlref;
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }
}