import './kraken.scss';
const $ = window.jQuery;

$( document ).on( 'click', '.kraken-stats-action-show-details', function ( e ) {
	e.preventDefault();

	const $el = $( this );
	$el.next().toggleClass( 'is-visible' );
} );

$( document ).on( 'click', '.kraken-stats-action-reset-image', function ( e ) {
	e.preventDefault();

	const reset = window.confirm( window.kraken_options.texts.reset_image );

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
				$el.parents( '.kraken-stats-media-column' ).replaceWith(
					response.data.html
				);
			} else {
				window.alert( window.kraken_options.texts.error_reset );
			}
		},
		error() {
			window.alert( window.kraken_options.texts.error_reset );
		},
	} );
} );

$( document ).on( 'click', '.kraken-button-optimize-image', function ( e ) {
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
				$el.parents( '.kraken-stats-media-column' ).replaceWith(
					response.data.stats_html
				);
			} else {
				window.alert( window.kraken_options.texts.error_reset );
				$spinner.removeClass( 'is-active' );
			}
		},
		error() {
			window.alert( window.kraken_options.texts.error_reset );
			$spinner.removeClass( 'is-active' );
		},
	} );
} );

$( document ).on( 'click', '.kraken-button-bulk-optimize', function ( e ) {
	e.preventDefault();

	const $el = $( this );
	const pages = parseInt( $el.data( 'pages' ), 10 );
	const ids = $el.data( 'ids' );

	$( '.kraken-bulk-actions' ).addClass( 'is-active' );
	$( '.kraken-button-bulk-optimize .spinner' ).addClass( 'is-active' );

	bulkOptimizeImages( ids, pages );
} );

function bulkOptimizeImages( ids, pages, page = 1, optimized = 0 ) {
	const bulkAsyncLimit = parseInt(
		window.kraken_options.bulk_async_limit,
		10
	);

	let concurentRequests = 0;
	let needsMorePages = false;

	const checkBulkRequest = () => {
		while ( ids.length > 0 && concurentRequests < bulkAsyncLimit ) {
			concurentRequests++;
			const id = ids.shift();
			if ( undefined !== id ) {
				optimizeImage( id );
			}
			checkBulkRequest();
		}

		if ( ids.length === 0 && concurentRequests === 0 ) {
			if ( page < pages ) {
				page++;
				needsMorePages = true;
				getUnoptimizedImages( optimized, pages, page );
			}
		}
	};

	const optimizeImage = ( id ) => {
		$.ajax( {
			type: 'POST',
			url: window.kraken_options.ajax_url,
			data: {
				action: 'kraken_optimize_image',
				id,
				nonce: window.kraken_options.nonce,
			},
			success( response ) {
				concurentRequests--;

				if ( response.success ) {
					optimized++;

					$( '.kraken-bulk-table tbody' )?.append(
						$( response.data.bulk_stats_html )
					);

					$( 'tr#post-' + response.data.id )
						.find( '.kraken-stats-media-column' )
						?.replaceWith( response.data.stats_html );

					$( '.kraken-bulk-actions .optimized' ).text( optimized );

					checkBulkRequest();
					maybeShowMessage();
				} else if ( response.data.type === 'nonce' ) {
					window.alert( window.kraken_options.texts.error_reset );
				}
			},
			error() {
				concurentRequests--;
				checkBulkRequest();
				maybeShowMessage();
			},
		} );
	};

	const maybeShowMessage = () => {
		if ( ids.length === 0 && ! needsMorePages && concurentRequests === 0 ) {
			$( '.kraken-bulk-actions' ).removeClass( 'is-active' );
			$( '.kraken-button-bulk-optimize .spinner' ).removeClass(
				'is-active'
			);

			const total = parseInt(
				$( '.kraken-button-bulk-optimize' ).data( 'total' ),
				10
			);

			const optimizedText = window.kraken_options.texts.images_optimized
				.replace( '%1$s', optimized )
				.replace( '%2$s', total );

			$( '.kraken-bulk-images' )
				.empty()
				.append( '<p>' + optimizedText + '</p>' );
		}
	};

	checkBulkRequest();
}

function getUnoptimizedImages( optimized, pages, page ) {
	$.ajax( {
		type: 'POST',
		url: window.kraken_options.ajax_url,
		data: {
			action: 'kraken_get_unoptimized_images',
			nonce: window.kraken_options.nonce,
		},
		success( response ) {
			const data = response.data;
			if ( data.ids.length > 0 ) {
				bulkOptimizeImages( data.ids, pages, page, optimized );
			}
		},
		error() {},
	} );
}

$( document ).on( 'submit', '#posts-filter', function ( e ) {
	const formData = new FormData( e.target );
	const action = formData.get( 'action' );
	const action2 = formData.get( 'action2' );
	const media = formData.getAll( 'media[]' );

	if ( action === 'kraken_bulk' || action2 === 'kraken_bulk' ) {
		e.preventDefault();
		$( '.kraken-modal' ).addClass( 'is-active' );

		if ( media.length ) {
			$( '.kraken-bulk-images-info' ).text(
				window.kraken_options.texts.images_to_optimize.replace(
					'%s',
					media.length
				)
			);
			$( '.kraken-bulk-actions' ).removeAttr( 'hidden' );
			$( '.kraken-bulk-actions .total' ).text( media.length );
			$( '.kraken-button-bulk-optimize' ).attr(
				'data-ids',
				JSON.stringify( media )
			);
			$( '.kraken-button-bulk-optimize' ).attr(
				'data-total',
				media.length
			);
		}
	}
} );

$( document ).on( 'click', '.kraken-bulk-close-modal', function () {
	$( '.kraken-modal' ).removeClass( 'is-active' );
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
	context.arc(
		center,
		center,
		radius,
		startPoint * 270,
		startPoint * ( 270 + deegre )
	);
	context.stroke();
}

drawCircle( $( '.kraken-progress-circle' ), 120 );
