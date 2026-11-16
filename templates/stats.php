<?php
/**
 * Template for stats.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

if ( ! kraken_io()->api->has_auth() ) : ?>

	<p><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-krakenio&tab=general' ) ); ?>"><?php esc_html_e( 'Connect your account', 'kraken-io' ); ?></a></p>

	<?php
else :

	$stats      = $args['stats'];
	$percentage = round( $stats['quota_used'] / $stats['quota_total'] * 100 );

	?>

	<div class="kraken-allstats-wrapper">
		<div class="kraken-allstats">

			<table class="kraken-allstats-table">
				<tr class="kraken-allstats-table-row">
					<td><?php esc_html_e( 'Plan Level', 'kraken-io' ); ?></td>
					<td class="kraken-allstats-table-info"><?php echo esc_html( $stats['plan_name'] ); ?></td>
				</tr>
				<tr class="kraken-allstats-table-row">
					<td><?php esc_html_e( 'Quota', 'kraken-io' ); ?></td>
					<td class="kraken-allstats-table-info"><?php echo esc_html( kraken_io()->format_bytes( $stats['quota_total'] ) ); ?></td>
				</tr>
				<tr class="kraken-allstats-table-row">
					<td><?php esc_html_e( 'Current Usage', 'kraken-io' ); ?></td>
					<td class="kraken-allstats-table-info"><?php echo esc_html( kraken_io()->format_bytes( $stats['quota_used'] ) ); ?></td>
				</tr>
				<tr class="kraken-allstats-table-row">
					<td><?php esc_html_e( 'Remaining', 'kraken-io' ); ?></td>
					<td class="kraken-allstats-table-info"><?php echo esc_html( kraken_io()->format_bytes( $stats['quota_remaining'] ) ); ?></td>
				</tr>
			</table>

			<div class="kraken-progress">
				<div class="kraken-progress-circle" data-percent="<?php echo esc_html( $percentage ); ?>" data-color="#298EEA">
					<span class="kraken-progress-circle-background"></span>
					<span class="kraken-progress-circle-value"><?php echo esc_html( $percentage ); ?></span>
					<canvas class="kraken-progress-circle-canvas" height="120" width="120"></canvas>
				</div>
			</div>

		</div>
	</div>

	<?php
	$site = wp_parse_args(
		kraken_io()->stats->get_site_savings(),
		[
			'main_original'  => 0,
			'main_kraked'    => 0,
			'main_saved'     => 0,
			'main_percent'   => 0,
			'thumb_original' => 0,
			'thumb_kraked'   => 0,
			'thumb_saved'    => 0,
			'thumb_percent'  => 0,
			'original'       => 0,
			'kraked'         => 0,
			'saved'          => 0,
			'percent'        => 0,
			'images'         => 0,
			'thumbs'         => 0,
		]
	);

	if ( $site['images'] || $site['thumbs'] ) :
		?>
		<div class="kraken-sitestats">
			<h2 class="kraken-sitestats__title"><?php esc_html_e( 'Savings on this site', 'kraken-io' ); ?></h2>
			<p class="kraken-sitestats__intro">
				<?php esc_html_e( 'These figures cover only the images optimized on this website — every full-size image and all of their thumbnail sizes — and are calculated from this site\'s own data. They are separate from the Kraken.io account quota shown above, which is shared across every site that uses your API key.', 'kraken-io' ); ?>
			</p>

			<div class="kraken-sitestats__cards">
				<div class="kraken-sitestats__hero">
					<span class="kraken-sitestats__hero-value"><?php echo esc_html( kraken_io()->format_bytes( $site['saved'] ) ); ?></span>
					<span class="kraken-sitestats__hero-label"><?php esc_html_e( 'saved on this site', 'kraken-io' ); ?></span>
				</div>
				<div class="kraken-sitestats__meter">
					<div class="kraken-sitestats__meter-track">
						<div class="kraken-sitestats__meter-fill" style="width: <?php echo esc_attr( min( 100, $site['percent'] ) ); ?>%"></div>
					</div>
					<span class="kraken-sitestats__meter-label">
						<?php
						/* translators: %s is a percentage, e.g. "62%". */
						printf( esc_html__( '%s smaller overall', 'kraken-io' ), esc_html( $site['percent'] . '%' ) );
						?>
					</span>
				</div>
			</div>

			<table class="kraken-allstats-table kraken-sitestats__breakdown">
				<thead>
					<tr>
						<th class="kraken-sitestats__rowhead"></th>
						<th><?php esc_html_e( 'Original', 'kraken-io' ); ?></th>
						<th><?php esc_html_e( 'Optimized', 'kraken-io' ); ?></th>
						<th><?php esc_html_e( 'Saved', 'kraken-io' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="kraken-sitestats__rowhead"><?php esc_html_e( 'Full-size images', 'kraken-io' ); ?> <span class="kraken-sitestats__count"><?php echo esc_html( number_format_i18n( $site['images'] ) ); ?></span></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['main_original'] ) ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['main_kraked'] ) ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['main_saved'] ) ); ?> <span class="kraken-sitestats__pct"><?php echo esc_html( $site['main_percent'] . '%' ); ?></span></td>
					</tr>
					<tr>
						<td class="kraken-sitestats__rowhead"><?php esc_html_e( 'Thumbnails', 'kraken-io' ); ?> <span class="kraken-sitestats__count"><?php echo esc_html( number_format_i18n( $site['thumbs'] ) ); ?></span></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['thumb_original'] ) ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['thumb_kraked'] ) ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['thumb_saved'] ) ); ?> <span class="kraken-sitestats__pct"><?php echo esc_html( $site['thumb_percent'] . '%' ); ?></span></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td class="kraken-sitestats__rowhead"><?php esc_html_e( 'Total', 'kraken-io' ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['original'] ) ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['kraked'] ) ); ?></td>
						<td><?php echo esc_html( kraken_io()->format_bytes( $site['saved'] ) ); ?> <span class="kraken-sitestats__pct"><?php echo esc_html( $site['percent'] . '%' ); ?></span></td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	endif;
	?>

	<?php
endif;
