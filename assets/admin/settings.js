/**
 * Kraken.io settings screen — "unsaved changes" guard.
 *
 * Watches the settings form and, the moment anything changes, flips the sticky
 * save bar into its "unsaved changes" state and arms a beforeunload warning, so
 * a freshly pasted API key/secret (or any other change) can't be lost by simply
 * navigating away. Submitting or resetting the form clears the guard.
 *
 * Hand-authored vanilla JS — no build step, no framework, no i18n strings (all
 * visible text is rendered translatable in PHP; this only toggles a CSS class).
 */
( function () {
	'use strict';

	var bar = document.getElementById( 'kraken-savebar' );

	if ( ! bar ) {
		return;
	}

	var form = bar.closest ? bar.closest( 'form' ) : null;

	if ( ! form ) {
		return;
	}

	var dirty = false;

	function markDirty() {
		if ( dirty ) {
			return;
		}

		dirty = true;
		bar.classList.add( 'is-dirty' );
		bar.classList.remove( 'is-saved' );
	}

	function clearDirty() {
		dirty = false;
		bar.classList.remove( 'is-dirty' );
	}

	form.addEventListener( 'input', markDirty );
	form.addEventListener( 'change', markDirty );
	form.addEventListener( 'submit', clearDirty );
	form.addEventListener( 'reset', clearDirty );

	window.addEventListener( 'beforeunload', function ( event ) {
		if ( ! dirty ) {
			return undefined;
		}

		// Modern browsers show their own generic confirmation; setting
		// returnValue is what actually triggers it.
		event.preventDefault();
		event.returnValue = '';
		return '';
	} );
}() );
