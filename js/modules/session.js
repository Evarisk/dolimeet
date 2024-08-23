/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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

'use strict';

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
  $(document).on('change', '#modele', window.dolimeet.session.test);
  $(document).on('change', '#element_type', window.dolimeet.session.test2);
  $('#date_start, #date_starthour, #date_startmin, #date_end, #date_endhour, #date_endmin').change(function () {
    setTimeout(function () {
      window.dolimeet.session.getDiffTimestamp();
    }, 100);
  });
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


/**
 * Session reload field
 *
 * @memberof DoliMeet_Session
 *
 * @since   1.5.0
 * @version 1.5.0
 *
 * @returns {void}
 */
window.dolimeet.session.test = function() {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let field          = this.checked ? 'on' : 'off';

  $.ajax({
    url: document.URL + querySeparator + 'modele=' + field + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.fiche').replaceWith($(resp).find('.fiche'));
    },
    error: function() {}
  });
};

/**
 * Reload specific field element_type and fk_element
 *
 * @memberof Saturne_Utils
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @returns {void}
 */
window.dolimeet.session.test2 = function() {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let field          = $(this).val();
  let modeleField    = $('#modele').is(':checked') ? 'on' : 'off';

  window.saturne.loader.display($('.field_element_type'));
  window.saturne.loader.display($('.field_fk_element'));

  $.ajax({
    url: document.URL + querySeparator + 'modele=' + modeleField + '&element_type=' + field + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.field_element_type').replaceWith($(resp).find('.field_element_type'));
      $('.field_fk_element').replaceWith($(resp).find('.field_fk_element'));
    },
    error: function() {}
  });
};

/**
 * Reload specific field element_type and fk_element
 *
 * @memberof Saturne_Utils
 *
 * @since   1.2.0
 * @version 1.2.0
 *
 * @returns {void}
 */
window.dolimeet.session.getDiffTimestamp = function() {
  let dayap   = $('#date_startday').val();
  let monthap = $('#date_startmonth').val();
  let yearap  = $('#date_startyear').val();
  let hourap  = $('#date_starthour').val() > 0 ? $('#date_starthour').val() : 0;
  let minap   = $('#date_startmin').val() > 0 ? $('#date_startmin').val() : 0;

  let dayp2   = $('#date_endday').val();
  let monthp2 = $('#date_endmonth').val();
  let yearp2  = $('#date_endyear').val();
  let hourp2  = $('#date_endhour').val() > 0 ? $('#date_endhour').val() : 0;
  let minp2   = $('#date_endmin').val() > 0 ? $('#date_endmin').val() : 0;

  if (yearap != '' && monthap != '' && dayap != '' && yearp2 != '' && monthp2 != '' && dayp2 != '') {
    let dateap = new Date(yearap, monthap - 1, dayap, hourap, minap);
    let datep2 = new Date(yearp2, monthp2 - 1, dayp2, hourp2, minp2);

    let diffTimeStamp      = (datep2.getTime() - dateap.getTime()) / 3600000;
    let diffTimeStampInMin = ((datep2.getTime() - dateap.getTime()) / 60000);
    if (diffTimeStamp > 0) {
      $('input[name="durationhour"]').val((diffTimeStampInMin - (diffTimeStampInMin % 60)) / 60);
      $('input[name="durationmin"]').val(Math.abs((diffTimeStampInMin % 60)));
    } else {
      $('input[name="durationhour"]').val(0);
      $('input[name="durationmin"]').val(0);
    }
  }
};
