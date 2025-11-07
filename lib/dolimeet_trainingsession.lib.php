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
 * \file    lib/dolimeet_trainingsession.lib.php
 * \ingroup dolimeet
 * \brief   Library files with common functions for TrainingSession
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare training session pages header
 *
 * @param  Trainingsession $object TrainingSession
 * @return array           $head   Array of tabs
 * @throws Exception
 */
function trainingsession_prepare_head(Trainingsession $object): array
{
    $moreParams['parentType']         = 'session';
    $moreParams['documentType']       = 'AttendanceSheetDocument';
    $moreParams['attendantTableMode'] = 'simple';

    return saturne_object_prepare_head($object, [], $moreParams, true);
}

function trainingsession_function_lib1()
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    require_once __DIR__ . '/../../saturne/lib/object.lib.php';

    $mainCategory      = getDolGlobalInt('DOLIMEET_FORMATION_MAIN_CATEGORY');
    $variousCategory   = getDolGlobalInt('DOLIMEET_FORMATION_VARIOUS_MAIN_CATEGORY');
    $templateProjectId = getDolGlobalInt('DOLIMEET_TRAININGSESSION_TEMPLATES_PROJECT');

    if (empty($mainCategory) || empty($variousCategory) || empty($templateProjectId)) {
        return -1;
    }

    $filterMain = [
        'customsql' =>
            'fk_product_type = 1 AND entity = ' . $conf->entity .
            ' AND rowid IN (
                SELECT cp.fk_product
                FROM ' . MAIN_DB_PREFIX . 'categorie_product cp
                LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie c ON cp.fk_categorie = c.rowid
                WHERE cp.fk_categorie = ' . $mainCategory .
            ')' .
            ' AND rowid IN (
                SELECT ds.fk_element
                FROM ' . MAIN_DB_PREFIX . 'dolimeet_session ds
                WHERE ds.fk_element = t.rowid
                    AND ds.model = 1
                    AND ds.element_type = \'service\'
                    AND ds.date_start IS NOT NULL
                    AND ds.date_end IS NOT NULL
                    AND ds.fk_project = ' . $templateProjectId . '
                GROUP BY ds.fk_element
                HAVING SUM(ds.duration) = t.duration * 3600
            )'
    ];
    $products = saturne_fetch_all_object_type('Product', 'ASC', 'label', 0, 0, $filterMain) ?: [];

    $filterVarious = [
        'customsql' =>
            'fk_product_type = 1 AND entity = ' . $conf->entity .
            ' AND rowid IN (
                SELECT cp.fk_product
                FROM ' . MAIN_DB_PREFIX . 'categorie_product cp
                LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie c ON cp.fk_categorie = c.rowid
                WHERE cp.fk_categorie = ' . $variousCategory .
            ')'
    ];

    $variousProducts = saturne_fetch_all_object_type('Product', 'ASC', 'label', 0, 0, $filterVarious) ?: [];

    $productIds        = array_column($products, 'label', 'id');
    $variousProductIds = array_column($variousProducts, 'label', 'id');

    return $productIds + $variousProductIds;
}
