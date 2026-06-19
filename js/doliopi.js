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
 * \file    js/doliopi.js
 * \ingroup doliopi
 * \brief   JavaScript file for module Doliopi
 */

'use strict';

if (!window.doliopi) {
  /**
   * Init Doliopi JS
   *
   * @memberof Doliopi_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @type {Object}
   */
  window.doliopi = {};

  /**
   * Init scriptsLoaded Doliopi
   *
   * @memberof Doliopi_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @type {Boolean}
   */
  window.doliopi.scriptsLoaded = false;
}

if (!window.doliopi.scriptsLoaded) {
  /**
   * Doliopi init
   *
   * @memberof Doliopi_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.doliopi.init = function() {
    window.doliopi.load_list_script();
  };

  /**
   * Load all modules' init
   *
   * @memberof Doliopi_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.doliopi.load_list_script = function() {
    if (!window.doliopi.scriptsLoaded) {
      let key = undefined, slug = undefined;
      for (key in window.doliopi) {
        if (window.doliopi[key].init) {
          window.doliopi[key].init();
        }
        for (slug in window.doliopi[key]) {
          if (window.doliopi[key] && window.doliopi[key][slug] && window.doliopi[key][slug].init) {
            window.doliopi[key][slug].init();
          }
        }
      }
      window.doliopi.scriptsLoaded = true;
    }
  };

  /**
   * Refresh and reload all modules' init
   *
   * @memberof Doliopi_Init
   *
   * @since   1.2.0
   * @version 1.2.0
   *
   * @returns {void}
   */
  window.doliopi.refresh = function() {
    let key = undefined;
    let slug = undefined;
    for (key in window.doliopi) {
      if (window.doliopi[key].refresh) {
        window.doliopi[key].refresh();
      }
      for (slug in window.doliopi[key]) {
        if (window.doliopi[key] && window.doliopi[key][slug] && window.doliopi[key][slug].refresh) {
          window.doliopi[key][slug].refresh();
        }
      }
    }
  };
  $(document).ready(window.doliopi.init);
}
