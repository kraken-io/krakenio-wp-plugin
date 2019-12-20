<?php
/**
 * Template for displaying media column size.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

$size = $args['size'];

if ( $size ) {
	echo esc_html( $size );
} else {
	esc_html_e( '0 bytes', 'kraken-io' );
}
