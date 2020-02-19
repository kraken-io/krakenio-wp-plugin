<?php
/**
 * Template for displaying bulk optimizer.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

$number = $args['total'];

/* translators: %s number of images */
$text = sprintf( _n( '%s image will be optimized.', '%s images will be optimized.', $number, 'kraken-io' ), $number );

if ( $number < 1 ) {
	if ( 'modal' === $args['type'] ) {
		$text = __( 'Selected images are allready optimized', 'kraken-io' );
	} else {
		$text = __( 'All images are allready optimized', 'kraken-io' );
	}
}

$class = '';

if ( 'modal' === $args['type'] ) {
	$class = ' kraken-modal is-active';
}

?>

<div class="kraken-bulk-optimizer-wrapper<?php echo esc_attr( $class ); ?>">
	<div class="kraken-bulk-optimizer">
		<div class="kraken-bulk-header">
			<h4 class="kraken-bulk-heading"><?php esc_html_e( 'Kraken Bulk Image Optimization', 'kraken-io' ); ?></h4>
			<button type="button" class="kraken-bulk-close-modal dashicons dashicons-no"></button>
		</div>

		<div class="kraken-bulk-images">
			<p class="kraken-bulk-images-info"><?php echo esc_html( $text ); ?></p>
			<?php if ( $number > 0 ) : ?>
				<div class="kraken-bulk-actions">
					<button type="button" class="button kraken-button-bulk-optimize" data-total="<?php echo esc_html( $number ); ?>" data-pages="<?php echo esc_attr( $args['pages'] ); ?>" data-ids="<?php echo esc_attr( wp_json_encode( $args['ids'] ) ); ?>">
						<?php esc_html_e( "Krak 'em all", 'kraken-io' ); ?>
						<span class="spinner"></span>
					</button>
					<span class="progress"><span class="optimized">0</span> / <span class="total"><?php echo esc_html( $number ); ?></span></span>
				</div>
			<?php endif; ?>
		</div>

		<table class="kraken-bulk-table">
			<tr class="kraken-bulk-table-header">
				<td><?php esc_html_e( 'Filename', 'kraken-io' ); ?></td>
				<td><?php esc_html_e( 'Original Size', 'kraken-io' ); ?></td>
				<td><?php esc_html_e( 'Kraken.io Stats', 'kraken-io' ); ?></td>
			</tr>
		</table>
	</div>
</div>
