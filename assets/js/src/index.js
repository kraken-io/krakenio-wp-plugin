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
	const id = $el.data( 'id' );

	$spinner.addClass( 'is-active' );

	$.ajax( {
		type: 'POST',
		url: window.kraken_options.ajax_url,
		data: {
			action: 'kraken_optimize_image',
			id,
			type: 'single',
			nonce: window.kraken_options.nonce,
		},
		success( response ) {
			if ( response.success ) {
				$el.parents( 'tr' ).find( '.column-kraken-original-size' ).text( response.data.size );
				$el.parents( '.kraken-stats-media-column' ).replaceWith( response.data.html );
			} else {
				alert( window.kraken_options.texts.error_reset );
				$spinner.removeClass( 'is-active' );
			}
		},
		error() {
			alert( window.kraken_options.texts.error_reset );
			$spinner.removeClass( 'is-active' );
		},
	} );
} );

$( document ).on( 'click', '.kraken-button-bulk-optimize', function( e ) {
	e.preventDefault();

	const $el = $( this );
	const $spinner = $el.find( '.spinner' );
	const total = $el.data( 'total' );
	// const pages = $el.data( 'pages' );
	// const page = 1;
	const optimized = 0;
	const ids = $el.data( 'ids' );

	$el.parents( '.kraken-bulk-actions' ).addClass( 'is-active' );
	$spinner.addClass( 'is-active' );

	optimizeImageAjaxCallback( $el, ids, optimized, total );
} );

function optimizeImageAjaxCallback( $el, ids, optimized, total ) {
	const $table = $el.parents( '.kraken-bulk-optimizer' ).find( '.kraken-bulk-table tbody' );
	const id = ids.shift();
	const $spinner = $el.find( '.spinner' );

	if ( undefined === id ) {
		$spinner.removeClass( 'is-active' );
		return false;
	}

	optimized = optimized + 1;

	$.ajax( {
		type: 'POST',
		url: window.kraken_options.ajax_url,
		data: {
			action: 'kraken_optimize_image',
			id,
			type: 'bulk',
			nonce: window.kraken_options.nonce,
		},
		success( response ) {
			if ( response.success ) {
				$table.append( $( response.data.html ) );
			}
			$( '.optimized' ).text( optimized );

			optimizeImageAjaxCallback( $el, ids, optimized, total );
		},
		error() {
			$( '.optimized' ).text( optimized );
			optimizeImageAjaxCallback( $el, ids, optimized, total );
		},
	} );
}

$( document ).on( 'click', '.kraken-bulk-close-modal', function( e ) {
	e.preventDefault();

	$( this ).parents( '.kraken-modal' ).removeClass( 'is-active' );
} );
