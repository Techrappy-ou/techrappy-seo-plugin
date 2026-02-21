/**
 * Techrappy SEO — Admin JavaScript
 *
 * Handles the "Analyser avec IA" button in the post edit meta box.
 * Uses the TechrappySEO global object injected via wp_localize_script().
 *
 * @package TechrappySEO
 */

( function ( $, data ) {
	'use strict';

	/**
	 * Handle click on the "Analyser avec IA" button.
	 */
	$( document ).on( 'click', '#techrappy-seo-analyze-btn', function () {
		var $btn     = $( this );
		var $spinner = $( '.techrappy-seo-spinner' );
		var postId   = $btn.data( 'post-id' );

		if ( ! postId ) {
			return;
		}

		// Show loading state.
		$btn.prop( 'disabled', true ).text( data.i18n.analyzing );
		$spinner.addClass( 'is-active' );

		$.ajax( {
			url:    data.ajaxUrl,
			method: 'POST',
			data:   {
				action:   'techrappy_seo_analyze',
				post_id:  postId,
				_nonce:   data.nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					// Reload to display updated score and suggestions.
					window.location.reload();
				} else {
					alert( response.data || data.i18n.error );
				}
			},
			error: function () {
				alert( data.i18n.error );
			},
			complete: function () {
				$btn.prop( 'disabled', false ).text(
					$btn.data( 'original-text' ) || 'Analyser avec IA'
				);
				$spinner.removeClass( 'is-active' );
			},
		} );
	} );

	// Store original button text on page load.
	$( '#techrappy-seo-analyze-btn' ).each( function () {
		$( this ).data( 'original-text', $( this ).text() );
	} );

} )( jQuery, window.TechrappySEO || {} );
