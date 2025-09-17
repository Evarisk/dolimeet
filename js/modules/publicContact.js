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
 * \file    js/modules/publicContact.js
 * \ingroup dolimeet
 * \brief   JavaScript public contact file
 */

'use strict';

/**
 * Init public contact JS
 *
 * @since   21.0.0
 * @version 21.0.0
 */
window.dolimeet.publicContact = {};

/**
 * Public contact init
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.dolimeet.publicContact.init = function init() {
  window.dolimeet.publicContact.event();
};

/**
 * Public contact event initialization. Binds all necessary event listeners
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.dolimeet.publicContact.event = function initializeEvents() {
  $(document).on('click', '#addContact', window.dolimeet.publicContact.addContactRow);
  $(document).on('click', '.remove-btn', window.dolimeet.publicContact.removeContactRow);
};

/**
 * Add contact row
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.dolimeet.publicContact.addContactRow = function addContactRow() {
  const newRow = `
    <div class="contact-row">
        <input type="text" name="firstname[]" placeholder="Prénom" required>
        <input type="text" name="lastname[]" placeholder="Nom" required>
        <input type="email" name="email[]" placeholder="Email" required>
        <button type="button" class="wpeo-button button-grey remove-btn"><i class="fas fa-times"></i></button>
    </div>`;
  $('#contactsList').append(newRow);
};

/**
 * Remove contact row
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.dolimeet.publicContact.removeContactRow = function removeContactRow() {
  const $this       = $(this);
  const $contactRow = $('#contactsList .contact-row');

  if ($contactRow.length > 1) {
    $this.closest('.contact-row').remove();
  }
};
