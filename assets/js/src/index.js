import '../../css/src/style.scss';
const $ = window.jQuery;

$( document ).on( 'click', '.kraken-action-show-details', function( e ) {
	e.preventDefault();

	const $el = $( this );
	$el.next().toggleClass( 'is-visible' );
} );

$( document ).on( 'click', '.kraken-stats-action-popup-close', function( e ) {
	e.preventDefault();
	$( this ).parent().removeClass( 'is-visible' );
} );

$( document ).on( 'click', '.kraken-button-optimize-image', function( e ) {
	e.preventDefault();

	const $el = $( this );
	const $spinner = $el.find( '.spinner' );

	$spinner.addClass( 'is-active' );
} );
