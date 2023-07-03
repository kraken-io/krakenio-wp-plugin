<?php
/**
 * Template for displaying media column stats.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

$stats   = $args['stats'];
$summary = $stats['stats']; ?>

<?php if ( $stats['is_image'] ) : ?>

	<div class="kraken-stats-media-column">

		<?php if ( ! kraken_io()->api->has_auth() ) : ?>

			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-krakenio&tab=general' ) ); ?>"><?php esc_html_e( 'Connect your account', 'kraken-io' ); ?></a>

		<?php elseif ( $stats['api_errors'] ) : ?>

			<div class="kraken-stats-failed-optimize">
				<p class="kraken-stats-action-detail">
					<strong><?php esc_html_e( 'Failed with errors', 'kraken-io' ); ?></strong><br>
					<?php echo esc_html( implode( '<br>', $stats['api_errors'] ) ); ?>
				</p>
			</div>

		<?php elseif ( $stats['is_optimizing'] ) : ?>

			<div class="kraken-stats-failed-optimize">
				<p class="kraken-stats-action-detail">
					<?php esc_html_e( 'Optimizing...', 'kraken-io' ); ?>
				</p>
			</div>

		<?php elseif ( $stats['is_optimized'] ) : ?>

			<?php if ( $stats['show_button'] ) : ?>
				<button
					type="button"
					class="button kraken-button-optimize-image"
					data-id="<?php echo esc_attr( $stats['id'] ); ?>">
					<?php esc_html_e( 'Optimize Main Image', 'kraken-io' ); ?>
					<span class="spinner"></span>
				</button>
			<?php endif; ?>

			<?php if ( $summary['total'] ) : ?>

				<div class="kraken-stas-savings">

					<?php
						/* translators: %1$s percentage %2$s total */
						echo esc_html( sprintf( __( 'Saved %1$s (%2$s)', 'kraken-io' ), $summary['percentage'], $summary['total'] ) );
					?>

					<div class="kraken-stats-action">
						<a href="#details" class="kraken-stats-action-show-details"><?php esc_html_e( 'Show Details', 'kraken-io' ); ?></a>

						<div class="kraken-stats-action-details">

							<?php if ( $summary['is_main_image_optimized'] ) : ?>
								<p class="kraken-stats-action-detail">
									<strong><?php esc_html_e( 'Main image savings', 'kraken-io' ); ?></strong><br>
									<?php
										/* translators: %1$s bytes %2$s percentage */
										echo esc_html( sprintf( __( '%1$s (%2$s saved)', 'kraken-io' ), $summary['main_image_stats']['saved_bytes'], $summary['main_image_stats']['savings_percentage'] ) );
									?>
								</p>
							<?php endif; ?>

							<?php if ( $summary['is_thumbs_optimized'] ) : ?>
								<p class="kraken-stats-action-detail">
									<strong>
									<?php
										/* translators: %s count */
										echo esc_html( sprintf( __( 'Savings on %s thumbnails', 'kraken-io' ), $summary['thumbs_count'] ) );
									?>
									</strong><br>
									<?php
										/* translators: %1$s bytes %2$s percentage */
										echo esc_html( sprintf( __( '%1$s (%2$s saved)', 'kraken-io' ), $summary['thumbs_stats']['total_savings'], $summary['thumbs_stats']['savings_percentage'] ) );
									?>
								</p>
							<?php endif; ?>

							<p class="kraken-stats-action-detail">
								<strong><?php esc_html_e( 'Optimization mode', 'kraken-io' ); ?></strong><br>
								<?php echo esc_html( $summary['optimization_mode'] ); ?>
							</p>

							<?php if ( $stats['show_reset'] ) : ?>
								<div class="kraken-stats-action">
									<a href="#reset" class="kraken-stats-action-reset-image" data-id="<?php echo esc_attr( $stats['id'] ); ?>"><?php esc_html_e( 'Reset Image', 'kraken-io' ); ?><span class="spinner"></span></a>
								</div>
							<?php endif; ?>
						</div>
					</div>

				</div>

			<?php else : ?>
				<div class="kraken-stas-no-savings">
					<?php esc_html_e( 'No savings', 'kraken-io' ); ?>
				</div>
			<?php endif; ?>

		<?php else : ?>

			<button
				type="button"
				class="button kraken-button-optimize-image"
				data-id="<?php echo esc_attr( $stats['id'] ); ?>">
				<?php esc_html_e( 'Optimize', 'kraken-io' ); ?>
				<span class="spinner"></span>
			</button>

			<?php if ( ! $stats['has_savings'] ) : ?>
				<div class="kraken-stats-no-savings">
					<?php
						/* translators: %s optimization type */
						echo esc_html( sprintf( __( 'No savings found for type %s', 'kraken-io' ), $stats['type'] ) );
					?>
				</div>
			<?php endif; ?>

			<?php if ( $stats['has_error'] ) : ?>
				<div class="kraken-stats-failed-optimize">
					<p class="kraken-stats-action-detail">
						<strong><?php esc_html_e( 'Failed with errors', 'kraken-io' ); ?></strong><br>
						<?php echo esc_html( $stats['has_error'] ); ?>
					</p>
				</div>
			<?php endif; ?>


		<?php endif; ?>
	</div>

<?php else : ?>

	<?php esc_html_e( 'N/A', 'kraken-io' ); ?>

<?php endif; ?>
