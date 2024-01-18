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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/session.js
 * \ingroup dolimeet
 * \brief   JavaScript session file for module DoliMeet
 */

/**
 * Init session JS
 *
 * @memberof DoliMeet_Session
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @type {Object}
 */
window.dolimeet.session = {};

/**
 * Session init
 *
 * @memberof DoliMeet_Session
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @returns {void}
 */
window.dolimeet.session.init = function() {
    window.dolimeet.session.event();
};

/**
 * Session event
 *
 * @memberof DoliMeet_Session
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @returns {void}
 */
window.dolimeet.session.event = function() {
    $(document).on('change', '#fk_soc', window.dolimeet.session.reloadField);
};

/**
 * Session reload field
 *
 * @memberof DoliMeet_Session
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @returns {void}
 */
window.dolimeet.session.reloadField = function() {
  let form     = document.getElementById('session_form');
  let formData = new FormData(form);

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  let field = formData.get('fk_soc');
  if (field == -1) {
    field = 0;
  }

  let actionPost = ''

  if (!document.URL.match('action=')) {
    let action = formData.get('action')
    if (action == 'add') {
      actionPost = '&action=create'
    } else if (action == 'update') {
      actionPost = '&action=edit'
    }
  }

  $.ajax({
    url: document.URL + querySeparator + "fk_soc=" + field + "&token=" + token + actionPost,
    type: "POST",
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.field_fk_project').replaceWith($(resp).find('.field_fk_project'));
      $('.field_fk_contrat').replaceWith($(resp).find('.field_fk_contrat'));
    },
    error: function() {}
  });
};
