import '../../css/src/style.scss';
const $ = window.jQuery;

$( document ).on( 'click', '.kraken-stats-action-show-details', function( e ) {
	e.preventDefault();

	const $el = $( this );
	$el.next().toggleClass( 'is-visible' );
} );

$( document ).on( 'click', '.kraken-stats-action-popup-close', function( e ) {
	e.preventDefault();
	$( this ).parent().removeClass( 'is-visible' );
} );

$( document ).on( 'click', '.kraken-stats-action-reset-image', function( e ) {
	e.preventDefault();

	const reset = confirm( window.kraken_options.texts.reset_image );

	if ( ! reset ) {
		return;
	}

	const $el = $( this );
	const id = $el.data( 'id' );
	const $spinner = $el.find( '.spinner' );

	$spinner.addClass( 'is-active' );

	$.ajax( {
		type: 'POST',
		url: window.kraken_options.ajax_url,
		data: {
			action: 'kraken_reset_image',
			id,
			nonce: window.kraken_options.nonce,
		},
		success( response ) {
			if ( response.success ) {
				$el.parents( 'tr' ).find( '.column-kraken-original-size' ).text( response.data.size );
				$el.parents( '.kraken-stats-media-column' ).replaceWith( response.data.html );
			} else {
				alert( window.kraken_options.texts.error_reset );
			}
		},
		error() {
			alert( window.kraken_options.texts.error_reset );
		},
	} );
} );

$( document ).on( 'click', '.kraken-button-optimize-image', function( e ) {
	e.preventDefault();

	const $el = $( this );
	const $spinner = $el.find( '.spinner' );

	$spinner.addClass( 'is-active' );
} );
