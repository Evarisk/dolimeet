/* Copyright (C) 2021 EVARISK <dev@evarisk.com>
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
<?php
if ( ! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if ( ! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if ( ! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if ( ! defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if ( ! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if ( ! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if ( ! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res    = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/**
 * \file    js/digiriskdolibarr.js.php
 * \ingroup digiriskdolibarr
 * \brief   JavaScript file for module DigiriskDolibarr.
 */

/* Javascript library of module DigiriskDolibarr */

'use strict';
/**
 * @namespace EO_Framework_Init
 *
 * @author Eoxia <dev@evarisk.com>
 * @copyright 2015-2021 Eoxia
 */

if ( ! window.eoxiaJS ) {
	/**
	 * [eoxiaJS description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Object}
	 */
	window.eoxiaJS = {};

	/**
	 * [scriptsLoaded description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @type {Boolean}
	 */
	window.eoxiaJS.scriptsLoaded = false;
}

if ( ! window.eoxiaJS.scriptsLoaded ) {
	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.init = function() {
		window.eoxiaJS.load_list_script();
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.load_list_script = function() {
		if ( ! window.eoxiaJS.scriptsLoaded) {
			var key = undefined, slug = undefined;
			for ( key in window.eoxiaJS ) {

				if ( window.eoxiaJS[key].init ) {
					window.eoxiaJS[key].init();
				}

				for ( slug in window.eoxiaJS[key] ) {

					if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug].init ) {
						window.eoxiaJS[key][slug].init();
					}

				}
			}

			window.eoxiaJS.scriptsLoaded = true;
		}
	};

	/**
	 * [description]
	 *
	 * @memberof EO_Framework_Init
	 *
	 * @returns {void} [description]
	 */
	window.eoxiaJS.refresh = function() {
		var key = undefined;
		var slug = undefined;
		for ( key in window.eoxiaJS ) {
			if ( window.eoxiaJS[key].refresh ) {
				window.eoxiaJS[key].refresh();
			}

			for ( slug in window.eoxiaJS[key] ) {

				if ( window.eoxiaJS[key] && window.eoxiaJS[key][slug] && window.eoxiaJS[key][slug].refresh ) {
					window.eoxiaJS[key][slug].refresh();
				}
			}
		}
	};

	$( document ).ready( window.eoxiaJS.init );
}

/**
 * Initialise l'objet "modal" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.eoxiaJS.modal = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.init = function() {
	window.eoxiaJS.modal.event();
};

/**
 * La méthode contenant tous les événements pour la modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.eoxiaJS.modal.event = function() {
	$( document ).on( 'click', '.modal-close', window.eoxiaJS.modal.closeModal );
	$( document ).on( 'click', '.modal-open', window.eoxiaJS.modal.openModal );
	$( document ).on( 'click', '.modal-refresh', window.eoxiaJS.modal.refreshModal );
};

/**
 * Open Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.openModal = function ( event ) {
	let idSelected = $(this).attr('value');
	if (document.URL.match(/#/)) {
		var urlWithoutTag = document.URL.split(/#/)[0]
	} else {
		var urlWithoutTag = document.URL
	}
	history.pushState({ path:  document.URL}, '', urlWithoutTag);

	// Open modal evaluation.
	if ($(this).hasClass('risk-evaluation-add')) {
		$('#risk_evaluation_add'+idSelected).addClass('modal-active');
		$('.risk-evaluation-create'+idSelected).attr('value', idSelected);
	} else if ($(this).hasClass('risk-evaluation-list')) {
		$('#risk_evaluation_list' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-media-gallery')) {
		$('#media_gallery').addClass('modal-active');
		$('#media_gallery').attr('value', idSelected);
		$('#media_gallery').find('.type-from').attr('value', $(this).find('.type-from').val());
		$('#media_gallery').find('.wpeo-button').attr('value', idSelected);
		$('#media_gallery').find('.clicked-photo').attr('style', '')
		$('#media_gallery').find('.clicked-photo').removeClass('clicked-photo')
	} else if ($(this).hasClass('risk-evaluation-edit')) {
		$('#risk_evaluation_edit' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('evaluator-add')) {
		$('#evaluator_add' + idSelected).addClass('modal-active');
	} else if ($(this).hasClass('open-medias-linked') && $(this).hasClass('digirisk-element')) {
		$('#digirisk_element_medias_modal_' + idSelected).addClass('modal-active');
	}

	// Open modal risk.
	if ($(this).hasClass('risk-add')) {
		$('#risk_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risk-edit')) {
		$('#risk_edit' + idSelected).addClass('modal-active');
	}

	// Open modal riskassessment task.
	if ($(this).hasClass('riskassessment-task-add')) {
		$('#risk_assessment_task_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-edit')) {
		$('#risk_assessment_task_edit' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-list')) {
		$('#risk_assessment_task_list' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('riskassessment-task-timespent-edit')) {
		$('#risk_assessment_task_timespent_edit' + idSelected).addClass('modal-active');
	}

	// Open modal risksign.
	if ($(this).hasClass('risksign-add')) {
		$('#risksign_add' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risksign-edit')) {
		$('#risksign_edit' + idSelected).addClass('modal-active');
	}
	if ($(this).hasClass('risksign-photo')) {
		$(this).closest('.risksign-photo-container').find('#risksign_photo' + idSelected).addClass('modal-active');
	}

	// Open modal signature.
	if ($(this).hasClass('modal-signature-open')) {
		$('#modal-signature' + idSelected).addClass('modal-active');
		window.eoxiaJS.signature.modalSignatureOpened( $(this) );
	}

	$('.notice').addClass('hidden');
};

/**
 * Close Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.closeModal = function ( event ) {
	$(this).closest('.modal-active').removeClass('modal-active')
	$('.clicked-photo').attr('style', '');
	$('.clicked-photo').removeClass('clicked-photo');
	$('.notice').addClass('hidden');
};

/**
 * Refresh Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.eoxiaJS.modal.refreshModal = function ( event ) {
	window.location.reload();
};


// Dropdown
/**
 * [dropdown description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @type {Object}
 */
window.eoxiaJS.dropdown = {};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @returns {void} [description]
 */
window.eoxiaJS.dropdown.init = function() {
	window.eoxiaJS.dropdown.event();
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @returns {void} [description]
 */
window.eoxiaJS.dropdown.event = function() {
	$( document ).on( 'keyup', window.eoxiaJS.dropdown.keyup );
	$( document ).on( 'keypress', window.eoxiaJS.dropdown.keypress );
	$( document ).on( 'click', '.wpeo-dropdown:not(.dropdown-active) .dropdown-toggle:not(.disabled)', window.eoxiaJS.dropdown.open );
	$( document ).on( 'click', '.wpeo-dropdown.dropdown-active .dropdown-content', function(e) { e.stopPropagation() } );
	$( document ).on( 'click', '.wpeo-dropdown.dropdown-active:not(.dropdown-force-display) .dropdown-content .dropdown-item', window.eoxiaJS.dropdown.close  );
	$( document ).on( 'click', '.wpeo-dropdown.dropdown-active', function ( e ) { window.eoxiaJS.dropdown.close( e ); e.stopPropagation(); } );
	$( document ).on( 'click', 'body', window.eoxiaJS.dropdown.close );
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.keyup = function( event ) {
	if ( 27 === event.keyCode ) {
		window.eoxiaJS.dropdown.close();
	}
}

/**
 * Do a barrel roll!
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.keypress = function( event ) {

	let currentString  = localStorage.currentString ? localStorage.currentString : ''
	let keypressNumber = localStorage.keypressNumber ? +localStorage.keypressNumber : 0

	currentString += event.keyCode
	keypressNumber += +1

	localStorage.setItem('currentString', currentString)
	localStorage.setItem('keypressNumber', keypressNumber)

	if (keypressNumber > 9) {
		localStorage.setItem('currentString', '')
		localStorage.setItem('keypressNumber', 0)
	}

	if (currentString === '9897114114101108114111108108') {
		var a="-webkit-",
			b='transform:rotate(1turn);',
			c='transition:4s;';

		document.head.innerHTML += '<style>body{' + a + b + a + c + b + c
	}
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.open = function( event ) {
	var triggeredElement = $( this );
	var angleElement = triggeredElement.find('[data-fa-i2svg]');
	var callbackData = {};
	var key = undefined;

	window.eoxiaJS.dropdown.close( event, $( this ) );

	if ( triggeredElement.attr( 'data-action' ) ) {
		window.eoxiaJS.loader.display( triggeredElement );

		triggeredElement.get_data( function( data ) {
			for ( key in callbackData ) {
				if ( ! data[key] ) {
					data[key] = callbackData[key];
				}
			}

			window.eoxiaJS.request.send( triggeredElement, data, function( element, response ) {
				triggeredElement.closest( '.wpeo-dropdown' ).find( '.dropdown-content' ).html( response.data.view );

				triggeredElement.closest( '.wpeo-dropdown' ).addClass( 'dropdown-active' );

				/* Toggle Button Icon */
				if ( angleElement ) {
					window.eoxiaJS.dropdown.toggleAngleClass( angleElement );
				}
			} );
		} );
	} else {
		triggeredElement.closest( '.wpeo-dropdown' ).addClass( 'dropdown-active' );

		/* Toggle Button Icon */
		if ( angleElement ) {
			window.eoxiaJS.dropdown.toggleAngleClass( angleElement );
		}
	}

	event.stopPropagation();
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {void} event [description]
 * @returns {void}       [description]
 */
window.eoxiaJS.dropdown.close = function( event ) {
	var _element = $( this );
	$( '.wpeo-dropdown.dropdown-active:not(.no-close)' ).each( function() {
		var toggle = $( this );
		var triggerObj = {
			close: true
		};

		_element.trigger( 'dropdown-before-close', [ toggle, _element, triggerObj ] );

		if ( triggerObj.close ) {
			toggle.removeClass( 'dropdown-active' );

			/* Toggle Button Icon */
			var angleElement = $( this ).find('.dropdown-toggle').find('[data-fa-i2svg]');
			if ( angleElement ) {
				window.eoxiaJS.dropdown.toggleAngleClass( angleElement );
			}
		} else {
			return;
		}
	});
};

/**
 * [description]
 *
 * @memberof EO_Framework_Dropdown
 *
 * @param  {jQuery} button [description]
 * @returns {void}        [description]
 */
window.eoxiaJS.dropdown.toggleAngleClass = function( button ) {
	if ( button.hasClass('fa-caret-down') || button.hasClass('fa-caret-up') ) {
		button.toggleClass('fa-caret-down').toggleClass('fa-caret-up');
	}
	else if ( button.hasClass('fa-caret-circle-down') || button.hasClass('fa-caret-circle-up') ) {
		button.toggleClass('fa-caret-circle-down').toggleClass('fa-caret-circle-up');
	}
	else if ( button.hasClass('fa-angle-down') || button.hasClass('fa-angle-up') ) {
		button.toggleClass('fa-angle-down').toggleClass('fa-angle-up');
	}
	else if ( button.hasClass('fa-chevron-circle-down') || button.hasClass('fa-chevron-circle-up') ) {
		button.toggleClass('fa-chevron-circle-down').toggleClass('fa-chevron-circle-up');
	}
};


/**
 * Initialise l'objet "signature" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature = {};

/**
 * Initialise le canvas signature
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature.canvas;

/**
 * Initialise le boutton signature
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.eoxiaJS.signature.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.init = function() {
	window.eoxiaJS.signature.event();
};

/**
 * La méthode contenant tous les événements pour la signature.
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.event = function() {
	$( document ).on( 'click', '.signature-erase', window.eoxiaJS.signature.clearCanvas );
	$( document ).on( 'click', '.signature-validate', window.eoxiaJS.signature.createSignature );
	$( document ).on( 'click', '.auto-download', window.eoxiaJS.signature.autoDownloadSpecimen );
};

/**
 * Open modal signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.modalSignatureOpened = function( triggeredElement ) {
	window.eoxiaJS.signature.buttonSignature = triggeredElement;

	var ratio =  Math.max( window.devicePixelRatio || 1, 1 );

	window.eoxiaJS.signature.canvas = document.querySelector('#modal-signature' + triggeredElement.attr('value') + ' canvas' );

	window.eoxiaJS.signature.canvas.signaturePad = new SignaturePad( window.eoxiaJS.signature.canvas, {
		penColor: "rgb(0, 0, 0)"
	} );

	window.eoxiaJS.signature.canvas.width = window.eoxiaJS.signature.canvas.offsetWidth * ratio;
	window.eoxiaJS.signature.canvas.height = window.eoxiaJS.signature.canvas.offsetHeight * ratio;
	window.eoxiaJS.signature.canvas.getContext( "2d" ).scale( ratio, ratio );
	window.eoxiaJS.signature.canvas.signaturePad.clear();

	var signature_data = $( '#signature_data' + triggeredElement.attr('value') ).val();
	window.eoxiaJS.signature.canvas.signaturePad.fromDataURL(signature_data);
};

/**
 * Action Clear sign
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.clearCanvas = function( event ) {
	var canvas = $( this ).closest( '.modal-signature' ).find( 'canvas' );
	canvas[0].signaturePad.clear();
};

/**
 * Action create signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.createSignature = function() {
	let elementSignatory = $(this).attr('value');
	let elementRedirect  = '';
	let elementCode = '';
	let elementZone  = $(this).find('#zone' + elementSignatory).attr('value');
	let elementConfCAPTCHA  = $('#confCAPTCHA').val();
	let actionContainerSuccess = $('.noticeSignatureSuccess');
	var signatoryIDPost = '';
	if (elementSignatory !== 0) {
		signatoryIDPost = '&signatoryID=' + elementSignatory;
	}

	if ( ! $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].signaturePad.isEmpty() ) {
		var signature = $(this).closest( '.wpeo-modal' ).find( 'canvas' )[0].toDataURL();
	}

	let token = $('.modal-signature').find('input[name="token"]').val();

	var url = '';
	var type = '';
	url = document.URL + '&action=addSignature' + signatoryIDPost + '&token=' + token;
	type = "POST"

	$.ajax({
		url: url,
		type: type,
		processData: false,
		contentType: 'application/octet-stream',
		data: JSON.stringify({
			signature: signature,
			code: elementCode
		}),
		success: function( resp ) {
			if (elementZone == "private") {
				actionContainerSuccess.html($(resp).find('.noticeSignatureSuccess .all-notice-content'));
				actionContainerSuccess.removeClass('hidden');
				$('.signatures-container').html($(resp).find('.signatures-container'));
			} else {
				window.location.reload();
			}
		},
		error: function ( ) {
			alert('Error')
		}
	});
};

/**
 * Download signature
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.download = function(fileUrl, filename) {
	var a = document.createElement("a");
	a.href = fileUrl;
	a.setAttribute("download", filename);
	a.click();
}

/**
 * Auto Download signature specimen
 *
 * @since   9.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.eoxiaJS.signature.autoDownloadSpecimen = function( event ) {
	let element = $(this).closest('.file-generation')
	let token = $('.digirisk-signature-container').find('input[name="token"]').val();
	let url = document.URL + '&action=builddoc&token=' + token
	$.ajax({
		url: url,
		type: "POST",
		success: function ( ) {
			let filename = element.find('.specimen-name').attr('value')
			let path = element.find('.specimen-path').attr('value')

			window.eoxiaJS.signature.download(path + filename, filename);
			$.ajax({
				url: document.URL + '&action=remove_file&token=' + token,
				type: "POST",
				success: function ( ) {
				},
				error: function ( ) {
				}
			});
		},
		error: function ( ) {
		}
	});
};
