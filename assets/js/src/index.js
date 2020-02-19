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
	const pages = $el.data( 'pages' );
	const page = 1;
	const optimized = 0;
	const ids = $el.data( 'ids' );

	$el.parents( '.kraken-bulk-actions' ).addClass( 'is-active' );
	$spinner.addClass( 'is-active' );

	optimizeImageAjaxCallback( $el, ids, optimized, pages, page );
} );

function optimizeImageAjaxCallback( $el, ids, optimized, pages, page ) {
	const $table = $el.parents( '.kraken-bulk-optimizer' ).find( '.kraken-bulk-table tbody' );
	const id = ids.shift();
	const $spinner = $el.find( '.spinner' );

	if ( undefined === id ) {
		if ( page < pages ) {
			page = page + 1;
			getUnoptimizedImagesPages( $el, optimized, pages, page );
		} else {
			$spinner.removeClass( 'is-active' );
		}

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

			optimizeImageAjaxCallback( $el, ids, optimized, pages, page );
		},
		error() {
			$( '.optimized' ).text( optimized );
			optimizeImageAjaxCallback( $el, ids, optimized, pages, page );
		},
	} );
}

function getUnoptimizedImagesPages( $el, optimized, pages, page ) {
	$.ajax( {
		type: 'POST',
		url: window.kraken_options.ajax_url,
		data: {
			action: 'kraken_get_bulk_pages',
			paged: page,
			nonce: window.kraken_options.nonce,
		},
		success( response ) {
			const data = response.data;
			if ( data.ids.length > 0 ) {
				optimizeImageAjaxCallback( $el, data.ids, optimized, pages, page );
			}
		},
		error() {
		},
	} );
}

$( document ).on( 'click', '.kraken-bulk-close-modal', function( e ) {
	e.preventDefault();

	$( this ).parents( '.kraken-modal' ).removeClass( 'is-active' );
} );

function drawCircle( $el, width ) {
	if ( $el.length === 0 ) {
		return false;
	}

	const canvas = $el.find( 'canvas' )[ 0 ];
	const context = canvas.getContext( '2d' );
	const startPoint = Math.PI / 180;
	const lineWidth = 10;
	const percent = $el.data( 'percent' );
	const color = $el.data( 'color' );
	const onePercent = 360 / 100;
	const radius = ( width - lineWidth ) / 2;
	const center = width / 2;
	const deegre = onePercent * percent;

	context.strokeStyle = color;
	context.lineWidth = lineWidth;
	context.clearRect( 0, 0, width, width );
	context.beginPath();
	context.arc( center, center, radius, startPoint * 270, startPoint * ( 270 + deegre ) );
	context.stroke();
}

drawCircle( $( '.kraken-progress-circle' ), 120 );
