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
 * \file    class/doliopidashboard.class.php
 * \ingroup doliopi
 * \brief   Class file for manage DoliopiDashboard
 */

/**
 * Class for DoliopiDashboard
 */
class DoliopiDashboard
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Load dashboard info
     *
     * @param  array     $moreParams    Parameters for load dashboard info
     * @return array     $dashboardData Return all dashboardData after load info
     * @throws Exception
     */
    public function load_dashboard(array $moreParams = []): array
    {
        $dashboardDatas = [
            ['type' => 'Session',                               'classPath' => '/session.class.php'],
            ['type' => 'FinancialAndPedagogicalReportDocument', 'classPath' => '/doliopidocuments/financialandpedagogicalreportdocument.class.php']
        ];
        foreach ($dashboardDatas as $dashboardData) {
            require_once __DIR__ . $dashboardData['classPath'];
            $className = new $dashboardData['type']($this->db);
            $array[$dashboardData['type']] = array_key_exists('Load' . $dashboardData['type'], $moreParams) && $moreParams['Load' . $dashboardData['type']] ? $className->loadDashboard($moreParams) : [];
        }

        return $array;
    }
}
