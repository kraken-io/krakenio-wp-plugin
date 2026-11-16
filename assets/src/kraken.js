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
		// Show the optimizing spinner on this image in the Media Library (list
		// row + grid thumbnail) while the bulk request runs, just like uploads.
		krakenBulkMarkOptimizing( id );
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
				krakenBulkClearOptimizing( id );

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
				krakenBulkClearOptimizing( id );
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

// Summary panel "Bulk optimize" button: open the bulk modal pre-populated with
// the remaining unoptimized images, then the user confirms with "Krak 'em all".
$( document ).on( 'click', '.kraken-summary__bulk-button', function ( e ) {
	e.preventDefault();

	const $b = $( this );
	const ids = $b.data( 'ids' ) || [];
	const pages = parseInt( $b.data( 'pages' ), 10 ) || 1;
	const total = parseInt( $b.data( 'total' ), 10 ) || ids.length;

	const $modal = $( '.kraken-modal' );
	if ( ! $modal.length ) {
		return;
	}

	$modal.addClass( 'is-active' );
	$modal
		.find( '.kraken-bulk-images-info' )
		.text(
			window.kraken_options.texts.images_to_optimize.replace(
				'%s',
				total
			)
		);
	$modal.find( '.kraken-bulk-actions' ).removeAttr( 'hidden' );
	$modal.find( '.kraken-bulk-actions .total' ).text( total );
	$modal
		.find( '.kraken-button-bulk-optimize' )
		.attr( {
			'data-ids': JSON.stringify( ids ),
			'data-pages': pages,
			'data-total': total,
		} )
		.data( { ids, pages, total } );
} );

// Support tab: copy the environment diagnostics to the clipboard so the user
// can paste them into a forum post or email. There is no in-plugin send — the
// forum link and mailto: don't depend on the site's (often broken) mail config.
$( document ).on( 'click', '.kraken-support__copy', function () {
	const $btn = $( this );
	const text = $btn.attr( 'data-clipboard' ) || '';
	const $done = $btn.siblings( '.kraken-support__copied' );
	const flash = () => $done.text( window.kraken_options.texts.support_copied );

	if ( navigator.clipboard && navigator.clipboard.writeText ) {
		navigator.clipboard.writeText( text ).then( flash, flash );
	} else {
		// Legacy fallback: stage the text in a hidden field and execCommand.
		const $tmp = $( '<textarea>' )
			.val( text )
			.css( { position: 'fixed', top: '-1000px' } )
			.appendTo( 'body' );
		$tmp[ 0 ].select();
		try {
			document.execCommand( 'copy' );
			flash();
		} catch ( err ) {
			/* clipboard unavailable — the details are visible to copy by hand */
		}
		$tmp.remove();
	}
} );

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

/**
 * Overlay an "optimizing" indicator on attachment thumbnails while Kraken is
 * optimizing the image — in the media grid, the media modal, and the list table.
 *
 * The overlay is a REAL injected element (not a pseudo-element) so WP's own
 * ::before/::after on the media views — used for the square aspect ratio and
 * selection chrome — are never clobbered.
 */
const krakenOpts = window.kraken_options || {};
const krakenTile = krakenOpts.tile || '';
const krakenOptimizingText =
	( krakenOpts.texts && krakenOpts.texts.optimizing ) || 'Optimizing…';

// Branded overlay for the grid (spinner + Kraken.io label).
const KRAKEN_OVERLAY =
	'<div class="kraken-overlay kraken-overlay--branded">' +
	'<span class="kraken-spinner"></span>' +
	'<span class="kraken-overlay-label">' +
	( krakenTile
		? '<img class="kraken-overlay-logo" src="' + krakenTile + '" alt="" />'
		: '' ) +
	'<span class="kraken-overlay-brand">Kraken.io</span>' +
	'<span class="kraken-overlay-sub">' +
	krakenOptimizingText +
	'</span>' +
	'</span></div>';

// Minimal spinner for the (tiny) list-table thumbnail.
const KRAKEN_OVERLAY_SM =
	'<div class="kraken-overlay"><span class="kraken-spinner kraken-spinner--sm"></span></div>';

function krakenAddOverlay( $container, markup ) {
	if (
		$container.length &&
		! $container.children( '.kraken-overlay' ).length
	) {
		$container.append( markup );
	}
}

// Red "!" badge on a thumbnail whose optimization failed. Built via jQuery so
// the API message (used as the tooltip) is safely escaped. Clicking the
// thumbnail opens the detail modal, which shows the full "Failed with errors".
function krakenAddErrorBadge( $thumb, error ) {
	if (
		! error ||
		! $thumb.length ||
		$thumb.children( '.kraken-badge--error' ).length
	) {
		return;
	}
	$( '<span>' )
		.addClass( 'kraken-badge kraken-badge--error' )
		.attr( 'title', error )
		.text( '!' )
		.appendTo( $thumb );
}

// Bulk optimize: toggle the optimizing spinner on an image's Media Library list
// row and grid thumbnail while that image is being processed.
function krakenBulkMarkOptimizing( id ) {
	const $icon = $( '#post-' + id + ' .media-icon' ).first();
	if ( $icon.length ) {
		$icon.addClass( 'kraken-is-optimizing' );
		krakenAddOverlay( $icon, KRAKEN_OVERLAY_SM );
	}
	const $att = $( '.attachment[data-id="' + id + '"]' );
	if ( $att.length ) {
		$att.addClass( 'kraken-is-optimizing' );
		krakenAddOverlay( $att.find( '.thumbnail' ).first(), KRAKEN_OVERLAY );
	}
}

function krakenBulkClearOptimizing( id ) {
	$( '#post-' + id + ' .media-icon' )
		.removeClass( 'kraken-is-optimizing' )
		.find( '.kraken-overlay' )
		.remove();
	$( '.attachment[data-id="' + id + '"]' )
		.removeClass( 'kraken-is-optimizing' )
		.find( '.kraken-overlay' )
		.remove();
}

// Grid only: override Attachment.Library (the grid items), NOT the base
// Attachment — otherwise the indicator would also show on the single-image
// detail view (Attachment.Details) opened when you click a thumbnail.
// The model exposes `krakenOptimizing` (via wp_prepare_attachment_for_js).
( function ( wp ) {
	if (
		! wp ||
		! wp.media ||
		! wp.media.view ||
		! wp.media.view.Attachment ||
		! wp.media.view.Attachment.Library
	) {
		return;
	}

	const Library = wp.media.view.Attachment.Library;
	const originalRender = Library.prototype.render;

	Library.prototype.render = function () {
		originalRender.apply( this, arguments );

		const optimizing = this.model && this.model.get( 'krakenOptimizing' );
		const savings = this.model && this.model.get( 'krakenSavings' );
		const error = this.model && this.model.get( 'krakenError' );
		const $thumb = this.$el.find( '.thumbnail' ).first();

		this.$el.toggleClass( 'kraken-is-optimizing', !! optimizing );

		if ( optimizing ) {
			krakenAddOverlay( $thumb, KRAKEN_OVERLAY );
			// Defer: at render time this element isn't attached to the document
			// yet, so polling must start on the next tick once it is, otherwise
			// the collector finds nothing and the spinner never clears live.
			window.setTimeout( krakenStartPolling, 0 );
		} else {
			this.$el.find( '.kraken-overlay' ).remove();
			if ( error ) {
				// A failed image shows the error badge, not a savings badge.
				krakenAddErrorBadge( $thumb, error );
			} else if (
				savings &&
				$thumb.length &&
				! $thumb.children( '.kraken-badge' ).length
			) {
				// Savings badge on optimized thumbnails.
				$thumb.append(
					'<span class="kraken-badge">−' + savings + '</span>'
				);
			}
		}

		return this;
	};
} )( window.wp );

// List table: rows whose Kraken column reports "optimizing" get the overlay on
// their media thumbnail.
function krakenMarkOptimizingRows() {
	$( '.kraken-stats-optimizing' ).each( function () {
		const $icon = $( this ).closest( 'tr' ).find( '.media-icon' ).first();
		$icon.addClass( 'kraken-is-optimizing' );
		krakenAddOverlay( $icon, KRAKEN_OVERLAY_SM );
	} );
	krakenStartPolling();
}

$( krakenMarkOptimizingRows );
$( document ).on( 'kraken:updated', krakenMarkOptimizingRows );

/**
 * Summary panel: relocate it below the page title and wire up the
 * collapsible (accordion) settings details.
 */
$( function () {
	const $panel = $( '.kraken-summary--media' ).first();
	if ( ! $panel.length ) {
		return;
	}

	// Place the panel directly under the page title. The Media Library has
	// .wp-header-end / .wp-heading-inline; the Add New (media-new.php) screen has
	// neither, so fall back to the first <h1> inside .wrap.
	let $anchor = $( '.wp-header-end' ).first();
	if ( ! $anchor.length ) {
		$anchor = $( '.wp-heading-inline' ).first();
	}
	if ( ! $anchor.length ) {
		$anchor = $( '.wrap > h1' ).first();
	}

	if ( $anchor.length ) {
		$anchor.after( $panel );
	}
} );

$( document ).on( 'click', '.kraken-summary__toggle', function () {
	const $panel = $( this ).closest( '.kraken-summary' );
	const open = $panel.toggleClass( 'is-open' ).hasClass( 'is-open' );
	$( this ).attr( 'aria-expanded', open ? 'true' : 'false' );
} );

/**
 * Live status polling: while any attachment is optimizing, poll the server and
 * drop the indicator (and refresh the list column) the moment it finishes — so
 * the state no longer needs a manual page reload.
 */
let krakenPollTimer = null;

function krakenCollectOptimizingIds() {
	const ids = {};
	$( '.kraken-stats-optimizing[data-id]' ).each( function () {
		ids[ $( this ).data( 'id' ) ] = true;
	} );
	$( '.attachment.kraken-is-optimizing[data-id]' ).each( function () {
		ids[ $( this ).data( 'id' ) ] = true;
	} );
	// Upload New Media page (the classic .media-item list).
	$( '.media-item.kraken-is-optimizing[data-id]' ).each( function () {
		ids[ $( this ).data( 'id' ) ] = true;
	} );
	return Object.keys( ids );
}

function krakenClearIndicator( id, savings, error, thumb, converted ) {
	// Update the Backbone model too, so a later grid re-render stays consistent
	// (drops the stale optimizing state, keeps the savings/error for the badge).
	if ( window.wp && wp.media && wp.media.attachment ) {
		const model = wp.media.attachment( id );
		if ( model ) {
			model.set( { krakenOptimizing: false, krakenError: error || '' } );
			if ( savings ) {
				model.set( { krakenSavings: savings } );
			}
			// Only a background CONVERSION re-points the attachment at a new file
			// (new extension/mime); the URLs this page rendered at upload time
			// would then 404. Re-fetch just those models so the grid re-renders
			// with the converted file's thumbnails. A plain optimization keeps
			// its URLs — skip the refetch so the grid doesn't flicker.
			if ( converted ) {
				model.fetch();
			}
		}
	}

	// List table: swap the media icon to the converted file's thumbnail.
	if ( thumb ) {
		$( '#post-' + id + ' .media-icon img' )
			.attr( 'src', thumb )
			.removeAttr( 'srcset' );
	}

	// Media grid: drop the spinner, then add the error badge (failed) or the
	// savings badge (succeeded).
	const $att = $( '.attachment[data-id="' + id + '"]' );
	$att.removeClass( 'kraken-is-optimizing' )
		.find( '.kraken-overlay' )
		.remove();

	const $thumb = $att.find( '.thumbnail' ).first();
	// Clear any badge left over from a previous state before deciding which to
	// show. Without this, a now-fixed image keeps its stale red error badge —
	// and the savings badge stays suppressed too, because the error badge also
	// carries the .kraken-badge class the savings guard checks for.
	$thumb.children( '.kraken-badge' ).remove();
	if ( error ) {
		krakenAddErrorBadge( $thumb, error );
	} else if ( savings && $thumb.length ) {
		$thumb.append( '<span class="kraken-badge">−' + savings + '</span>' );
	}

	// List table thumbnail
	$( '#post-' + id + ' .media-icon' )
		.removeClass( 'kraken-is-optimizing' )
		.find( '.kraken-overlay' )
		.remove();

	// Upload New Media page item (the classic .media-item list): swap the
	// spinning status line for the final savings (or error) outcome.
	const $mi = $( '.media-item[data-id="' + id + '"]' );
	if ( $mi.length ) {
		$mi.removeClass( 'kraken-is-optimizing' );
		const $status = $mi.find( '.kraken-upload-status' );
		$status.empty().removeClass( 'kraken-upload-status--error kraken-upload-status--done' );
		if ( error ) {
			$status
				.addClass( 'kraken-upload-status--error' )
				.append(
					$( '<span class="kraken-inline-badge kraken-inline-badge--error" />' ).text( '!' )
				)
				.append( document.createTextNode( ' ' ) )
				.append( $( '<span />' ).text( error ) );
		} else if ( savings ) {
			$status
				.addClass( 'kraken-upload-status--done' )
				.append(
					$( '<span class="kraken-inline-badge" />' ).text( '−' + savings )
				)
				.append( document.createTextNode( ' ' ) )
				.append(
					$( '<span />' ).text(
						( krakenOpts.texts && krakenOpts.texts.optimized ) ||
							'Optimized'
					)
				);
		} else {
			// Nothing was changed (e.g. already optimal) — drop the line quietly.
			$status.remove();
		}
		if ( converted && thumb ) {
			$mi.find( 'img.pinkynail' )
				.attr( 'src', thumb )
				.removeAttr( 'srcset' );
		}
	}
}

function krakenPoll() {
	const ids = krakenCollectOptimizingIds();

	if ( ! ids.length ) {
		krakenPollTimer = null;
		return;
	}

	$.ajax( {
		type: 'POST',
		url: window.kraken_options.ajax_url,
		data: {
			action: 'kraken_optimizing_status',
			ids,
			nonce: window.kraken_options.nonce,
		},
		success( response ) {
			// A PERSISTENT auth failure (e.g. the nonce expired after a tab sat
			// open for hours) would otherwise leave every spinner turning
			// forever while we silently re-poll. Fail safe: clear the indicators
			// so nothing is stuck. A transient malformed response, by contrast,
			// is left alone — we just skip this cycle and retry on the next one.
			if ( response && response.success === false && response.data && response.data.type === 'unauthorized' ) {
				krakenCollectOptimizingIds().forEach( function ( id ) {
					krakenClearIndicator( id );
				} );
				return;
			}
			if ( ! response || ! response.success || ! response.data ) {
				return;
			}
			( response.data.items || [] ).forEach( function ( item ) {
				if ( item.optimizing ) {
					// Keep the Upload New Media verb honest: the server reports
					// the current action, so a label first shown under a stale
					// convert setting (changed after the page loaded) corrects
					// itself on the next poll instead of staying frozen.
					if ( item.action ) {
						krakenSetUploadVerb( item.id, item.action );
					}
					return;
				}
				krakenClearIndicator(
					item.id,
					item.savings,
					item.error,
					item.thumb,
					item.converted
				);
				if ( item.stats_html ) {
					$( '#post-' + item.id )
						.find( '.kraken-stats-media-column' )
						.replaceWith( item.stats_html );
				}
			} );

			// Live-update the Media Library panel usage as quota is consumed.
			const account = response.data.account;
			if ( account ) {
				$( '.kraken-summary__fill' ).css(
					'width',
					account.percent + '%'
				);
				$( '.kraken-summary__usage-text' ).text( account.usage_text );
			}
		},
		complete() {
			krakenPollTimer = null;
			krakenStartPolling();
		},
	} );
}

function krakenStartPolling() {
	if ( ! krakenPollTimer && krakenCollectOptimizingIds().length ) {
		krakenPollTimer = window.setTimeout( krakenPoll, 4000 );
	}
}

$( krakenStartPolling );
$( document ).on( 'kraken:updated', krakenStartPolling );

/**
 * Upload New Media page (media-new.php): the classic uploader renders results
 * as `.media-item` rows, which the Backbone grid override never sees — so this
 * page had no Kraken feedback at all. Mirror the optimizing indicator here:
 * tag each freshly uploaded item, show a spinning "Optimizing…/Converting…"
 * line, and let the shared poll swap it for the savings (or error) outcome.
 */
// The localized verb for an action key ('converting' | 'optimizing'), used by
// both the initial label and the poll's live correction.
function krakenVerbFor( action ) {
	const t = krakenOpts.texts || {};
	if ( action === 'converting' ) {
		return t.converting || 'Converting…';
	}
	return t.optimizing || krakenOptimizingText;
}

// Update an Upload New Media item's spinning status line to the given action's
// verb (keeping the spinner), without disturbing a finished done/error state.
function krakenSetUploadVerb( id, action ) {
	const $status = $(
		'.media-item[data-id="' + id + '"] .kraken-upload-status'
	);
	if (
		! $status.length ||
		$status.hasClass( 'kraken-upload-status--done' ) ||
		$status.hasClass( 'kraken-upload-status--error' )
	) {
		return;
	}
	$status
		.contents()
		.filter( function () {
			return this.nodeType === 3; // text nodes only — keep the spinner
		} )
		.remove();
	$status.append( document.createTextNode( krakenVerbFor( action ) ) );
}

function krakenInitUploadPage() {
	const list = document.getElementById( 'media-items' );
	if ( ! list || ! document.getElementById( 'plupload-upload-ui' ) ) {
		return; // not the Upload New Media page
	}

	// Only freshly uploaded files that will actually be processed get an
	// indicator — when neither auto-optimize nor conversion is on, uploads are
	// left untouched and a spinner would be misleading.
	const willProcess = !! ( krakenOpts.auto_optimize || krakenOpts.convert_format );
	if ( ! willProcess ) {
		return;
	}

	const tagItems = function () {
		$( '#media-items .media-item' ).each( function () {
			const $item = $( this );
			if ( $item.data( 'krakenSeen' ) ) {
				return;
			}
			// PDFs only show the optimizing indicator when the PDF opt-in is
			// on — otherwise the server-side gate skips optimization and a
			// spinner would be misleading. Detect by filename since the
			// classic uploader row doesn't carry a mime attribute.
			if ( ! krakenOpts.optimize_pdf ) {
				const name = ( $item.find( '.filename' ).first().text() || '' )
					.toLowerCase()
					.trim();
				if ( name.endsWith( '.pdf' ) ) {
					$item.data( 'krakenSeen', true );
					return;
				}
			}
			// The attachment id only exists once the upload finishes and the
			// details (with the Edit link) are rendered; until then, skip and
			// let a later mutation catch it.
			const href = $item
				.find( '.edit-attachment[href*="post="]' )
				.attr( 'href' );
			const match = href && href.match( /post=(\d+)/ );
			if ( ! match ) {
				return;
			}
			const id = parseInt( match[ 1 ], 10 );
			// Best-guess verb from the CURRENT convert setting; the poll then
			// corrects it server-side, so it can't stay stale.
			const verb = krakenOpts.convert_format ? 'converting' : 'optimizing';
			$item
				.data( 'krakenSeen', true )
				.attr( 'data-id', id )
				.addClass( 'kraken-is-optimizing' );
			const $status = $(
				'<span class="kraken-upload-status"><span class="kraken-inline-spinner"></span></span>'
			);
			$status.append( document.createTextNode( krakenVerbFor( verb ) ) );
			$item.find( '.filename' ).first().append( $status );
		} );
		krakenStartPolling();
	};

	const observer = new window.MutationObserver( tagItems );
	observer.observe( list, { childList: true, subtree: true } );
	tagItems(); // catch any item already on the page
}
$( krakenInitUploadPage );

/**
 * Convert control: the global "convert uploads to" format. The Kraken.io widget
 * dropdown and the settings select are kept live-synced and auto-saved — change
 * either one and the value is persisted immediately and mirrored to the other.
 */
$( document ).on(
	'change',
	'.kraken-summary__convert-select, #convert_format',
	function () {
		const format = $( this ).val();
		const $hint = $( '.kraken-summary__convert-hint' );

		// Keep the localized snapshot in sync so anything reading it live (e.g.
		// the Upload New Media verb) reflects the new choice at once, with no
		// page reload needed.
		krakenOpts.convert_format = format;

		// Mirror the choice to every other convert control on the page.
		$( '.kraken-summary__convert-select, #convert_format' )
			.not( this )
			.val( format );

		$.ajax( {
			type: 'POST',
			url: window.kraken_options.ajax_url,
			data: {
				action: 'kraken_set_convert_format',
				format,
				nonce: window.kraken_options.nonce,
			},
			success( response ) {
				if ( response.success ) {
					$hint.text( '✓ Saved' );
					window.setTimeout( function () {
						$hint.text( '' );
					}, 1800 );
				}
			},
		} );
	}
);

/**
 * Auto-optimize control: the widget's quick toggle and the settings checkbox
 * (#auto_optimize, "Automatically optimize uploads") are kept live-synced and
 * auto-saved, exactly like the convert control above.
 */
$( document ).on(
	'change',
	'.kraken-summary__auto-optimize-select, #auto_optimize',
	function () {
		const enabled = this.id === 'auto_optimize'
			? ( this.checked ? '1' : '' )
			: $( this ).val();
		const $hint = $( '.kraken-summary__convert-hint' );

		// Mirror to every other auto-optimize control on the page.
		$( '.kraken-summary__auto-optimize-select' ).not( this ).val( enabled );
		$( '#auto_optimize' )
			.not( this )
			.prop( 'checked', enabled === '1' );

		$.ajax( {
			type: 'POST',
			url: window.kraken_options.ajax_url,
			data: {
				action: 'kraken_set_auto_optimize',
				enabled,
				nonce: window.kraken_options.nonce,
			},
			success( response ) {
				if ( response.success ) {
					$hint.text( '✓ Saved' );
					window.setTimeout( function () {
						$hint.text( '' );
					}, 1800 );
				}
			},
		} );
	}
);
