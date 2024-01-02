<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/dolimeet_function.lib.php
 * \ingroup dolimeet
 * \brief   Library files with common functions for DoliMeet
 */

/**
 * Set satisfaction survey
 *
 * @param  CommonObject $object        Object
 * @param  string       $contactCode   Contact code from c_type_contact
 * @param  int          $contactID     Contact ID : user or socpeople
 * @param  string       $contactSource Contact source : internal or external
 * @throws Exception
 */
function set_satisfaction_survey(CommonObject $object, string $contactCode, int $contactID, string $contactSource)
{
    global $conf, $db, $user;

    // Load DigiQuali libraries
    require_once __DIR__ . '/../../digiquali/class/control.class.php';
    require_once __DIR__ . '/../../digiquali/lib/digiquali_sheet.lib.php';

    $control = new Control($db);

    $confName                    = 'DOLIMEET_' . dol_strtoupper($contactCode) . '_SATISFACTION_SURVEY_SHEET';
    $control->fk_sheet           = $conf->global->$confName;
    $control->fk_user_controller = $user->id;
    $_POST['fk_contract']        = $object->id;

    $controlID = $control->create($user);

    if ($controlID > 0) {
        // Load Saturne libraries
        require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

        $signatory = new SaturneSignature($db, 'digiquali', $control->element);
        $signatory->setSignatory($controlID, $control->element, $contactSource == 'internal' ? 'user' : 'socpeople', [$contactID], 'Attendant', 1);
    }
}
