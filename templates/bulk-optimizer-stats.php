<?php
/**
 * Template for displaying bulk row stats.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

$stats   = $args['stats'];
$summary = $stats['stats']; ?>

<tr class="kraken-bulk-table-row">
	<td>
		<div class="kraken-bulk-table-image">
			<img class="kraken-bulk-table-thumbnail" src="<?php echo esc_url( $args['thumb_src'] ); ?>" alt="<?php echo esc_attr( $args['filename'] ); ?>">
			<?php echo esc_html( $args['filename'] ); ?>
		</div>
	</td>
	<td>
	<?php echo esc_html( $args['size'] ); ?>
	</td>
	<td>
		<?php if ( $stats['is_image'] ) : ?>
			<div class="kraken-stats-media-column">

				<?php if ( $stats['api_errors'] ) : ?>

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

					<?php if ( $summary['total'] ) : ?>

						<?php
						/* translators: %1$s percentage %2$s total */
						echo esc_html( sprintf( __( 'Saved %1$s (%2$s)', 'kraken-io' ), $summary['percentage'], $summary['total'] ) );
						?>

					<?php else : ?>

						<?php esc_html_e( 'No savings', 'kraken-io' ); ?>

					<?php endif; ?>

				<?php else : ?>

					<?php
					/* translators: %s optimization type */
					echo esc_html( sprintf( __( 'No savings found for type %s', 'kraken-io' ), $stats['type'] ) );
					?>

				<?php endif; ?>
			</div>
			<?php
		else :
			esc_html_e( 'N/A', 'kraken-io' );
		endif;
		?>
	</td>
</tr>
