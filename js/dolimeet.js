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
 * \file    js/dolimeet.js
 * \ingroup dolimeet
 * \brief   JavaScript file for module DoliMeet
 */

'use strict';

if (!window.dolimeet) {
  /**
   * Init DoliMeet JS
   *
   * @memberof DoliMeet_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @type {Object}
   */
  window.dolimeet = {};

  /**
   * Init scriptsLoaded DoliMeet
   *
   * @memberof DoliMeet_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @type {Boolean}
   */
  window.dolimeet.scriptsLoaded = false;
}

if (!window.dolimeet.scriptsLoaded) {
  /**
   * DoliMeet init
   *
   * @memberof DoliMeet_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.dolimeet.init = function() {
    window.dolimeet.load_list_script();
  };

  /**
   * Load all modules' init
   *
   * @memberof DoliMeet_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.dolimeet.load_list_script = function() {
    if (!window.dolimeet.scriptsLoaded) {
      let key = undefined, slug = undefined;
      for (key in window.dolimeet) {
        if (window.dolimeet[key].init) {
          window.dolimeet[key].init();
        }
        for (slug in window.dolimeet[key]) {
          if (window.dolimeet[key] && window.dolimeet[key][slug] && window.dolimeet[key][slug].init) {
            window.dolimeet[key][slug].init();
          }
        }
      }
      window.dolimeet.scriptsLoaded = true;
    }
  };

  /**
   * Refresh and reload all modules' init
   *
   * @memberof DoliMeet_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.dolimeet.refresh = function() {
    let key = undefined;
    let slug = undefined;
    for (key in window.dolimeet) {
      if (window.dolimeet[key].refresh) {
        window.dolimeet[key].refresh();
      }
      for (slug in window.dolimeet[key]) {
        if (window.dolimeet[key] && window.dolimeet[key][slug] && window.dolimeet[key][slug].refresh) {
          window.dolimeet[key][slug].refresh();
        }
      }
    }
  };
  $(document).ready(window.dolimeet.init);
}
