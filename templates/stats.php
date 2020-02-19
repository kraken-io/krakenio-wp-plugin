<?php
/**
 * Template for stats.
 *
 * @package Kraken_IO/Templates
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

$stats = $args['stats'];

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
