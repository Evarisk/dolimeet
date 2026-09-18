/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/contactList.js
 * \ingroup dolimeet
 * \brief   JavaScript public contact file
 */

'use strict';

/**
 * Init public contact JS
 *
 * @since   22.0.0
 * @version 22.0.0
 */
window.dolimeet.contactList = {};

/**
 * Public contact init
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.dolimeet.contactList.init = function init() {
  window.dolimeet.contactList.event();
};

/**
 * Public contact event initialization. Binds all necessary event listeners
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.dolimeet.contactList.event = function initializeEvents() {
};

/**
 * Function to insert data into contact list
 *
 * @since   22.0.0
 * @version 22.0.0
 *
 * @return {void}
 */
window.dolimeet.contactList.insertData = function(contactIds, formationLangTrans) {
    $table = $('.div-table-responsive')
    $listTitle = $table.find('tr.liste_titre').last()
    $tableLines = $table.find('tr.oddeven')

    $table.find('tr.liste_titre').first().find('td').eq(1).after('<td class="liste_titre"></td>')
    $listTitle.find('th').eq(1).after('<th>'+formationLangTrans+'</th>')

    $tableLines.each(function() {
        $this = $(this)
        let url = new URL($this.find('td a').eq(1)[0].href)
        let id = url.searchParams.get('id')

        let nb = contactIds[id] !== undefined ? contactIds[id] : 0

        $this.find('td').eq(1).after('<td class="tdoverflowmax150 ">'+ nb +'</td>')
    });
};