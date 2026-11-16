<?php
/**
 * Notice listing other active image-optimizer plugins, each with a one-click
 * deactivation link. Rendered inside the Kraken.io summary widget and on the
 * settings page.
 *
 * @package Kraken_IO/Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$conflicts = isset( $args['conflicts'] ) ? $args['conflicts'] : [];

if ( empty( $conflicts ) ) {
	return;
}
?>

<div class="kraken-conflicts">
	<div class="kraken-conflicts__head">
		<span class="dashicons dashicons-warning"></span>
		<strong><?php esc_html_e( 'Other image optimizers detected', 'kraken-io' ); ?></strong>
	</div>
	<p class="kraken-conflicts__intro">
		<?php esc_html_e( 'Running more than one image optimizer on the same uploads wastes quota and can double-compress your images. For best results with Kraken.io, deactivate the plugins below.', 'kraken-io' ); ?>
	</p>
	<ul class="kraken-conflicts__list">
		<?php foreach ( $conflicts as $plugin ) : ?>
			<li class="kraken-conflicts__item">
				<span class="kraken-conflicts__name"><?php echo esc_html( $plugin['name'] ); ?></span>
				<a class="button kraken-conflicts__deactivate" href="<?php echo esc_url( $plugin['deactivate_url'] ); ?>">
					<?php esc_html_e( 'Deactivate', 'kraken-io' ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
