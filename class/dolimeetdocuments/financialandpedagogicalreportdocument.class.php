<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    class/dolimeetdocuments/financialandpedagogicalreportdocument.class.php
 * \ingroup dolimeet
 * \brief   This file is a class file for FinancialAndPedagogicalReportDocument
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedocuments.class.php';

/**
 * Class for FinancialAndPedagogicalReportDocument
 */
class FinancialAndPedagogicalReportDocument extends SaturneDocuments
{
    /**
     * @var string Module name
     */
    public $module = 'dolimeet';

    /**
     * @var string Element type of object
     */
    public $element = 'financialandpedagogicalreportdocument';

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Load dashboard info
     *
     * @return array
     * @throws Exception
     */
    public function loadDashboard(): array
    {
        $getTrainingOrganizationInfos = $this->getTrainingOrganizationInfos();
        $getGeneralInfos = $this->getGeneralInfos();

        $array['widgets'] = [
            $getTrainingOrganizationInfos,
            $getGeneralInfos
        ];

        return $array;
    }

    public function getTrainingOrganizationInfos(): array
    {
        global $langs, $mysoc;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('TrainingOrganizationInfos');
        $array['picto']      = 'fas fa-info';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [
            $langs->transnoentities('TrainingOrganizationNumber'),
            $langs->transnoentities('JuridicalStatus'),
            $langs->transnoentities('ProfId2ShortFR'),
            $langs->transnoentities('ProfId3ShortFR'),
            $langs->transnoentities('CompanyName'),
            $langs->transnoentities('Address'),
            $langs->transnoentities('Phone'),
            $langs->transnoentities('Email')
        ];

        $array['content'] = [
            getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'),
            getFormeJuridiqueLabel($mysoc->forme_juridique_code),
            $mysoc->idprof2,
            $mysoc->idprof3,
            $mysoc->name,
            $mysoc->address,
            $mysoc->phone,
            $mysoc->email
        ];

        return $array;
    }

    public function getGeneralInfos(): array
    {
        global $langs;

        // Widget Title parameters
        $array['title']      = $langs->transnoentities('GeneralInfos');
        $array['picto']      = 'fas fa-info';
        $array['name']       = 'ControlsRepartition';
        $array['widgetName'] = 'informations';

        // Widget parameters
        $array['label'] = [
            $langs->transnoentities('FiscalYearInformation')
        ];

        $firstDayOfCurrentYear = dol_get_first_day(date('Y'), getDolGlobalString('SOCIETE_FISCAL_MONTH_START'));
        $lastDayOfCurrentYear  = dol_time_plus_duree($firstDayOfCurrentYear, 1, 'y');
        $lastDayOfCurrentYear  = dol_time_plus_duree($lastDayOfCurrentYear, -1, 'd');

        $array['content'] = [
            dol_print_date($firstDayOfCurrentYear, 'day') . ' - ' . dol_print_date($lastDayOfCurrentYear, 'day')
        ];

        return $array;
    }
}

