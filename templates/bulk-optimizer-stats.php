<?php
/**
 * Template for displaying bulk row stats.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

$stats = $args['stats']; ?>

<tr class="kraken-bulk-table-row">
	<td>
		<?php echo esc_html( $args['filename'] ); ?>
	</td>
	<td>
	<?php echo esc_html( $args['size'] ); ?>
	</td>
	<td>
		<?php if ( $stats['is_image'] ) : ?>
			<div class="kraken-stats-media-column">

				<?php
				if ( $stats['is_optimized'] ) :
					if ( $summary['total'] ) :
						/* translators: %1$s percentage %2$s total */
						echo esc_html( sprintf( __( 'Saved %1$s (%2$s)', 'kraken-io' ), $summary['percentage'], $summary['total'] ) );
					else :
						esc_html_e( 'No savings', 'kraken-io' );
					endif;

				else :
					/* translators: %s optimization type */
					echo esc_html( sprintf( __( 'No savings found for type %s', 'kraken-io' ), $stats['type'] ) );

				endif;
				?>
			</div>
			<?php
		else :
			esc_html_e( 'N/A', 'kraken-io' );
		endif;
		?>
	</td>
</tr>