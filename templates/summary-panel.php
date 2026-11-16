<?php
/**
 * Template for the Kraken.io summary banner (Media Library + Dashboard widget).
 *
 * When the plugin is not fully active (missing or invalid credentials) only the
 * connection notice is shown — the settings overview is hidden, since nothing
 * is being optimized yet.
 *
 * @package Kraken_IO/Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$data      = isset( $args['data'] ) ? $args['data'] : [];
$context   = isset( $args['context'] ) ? $args['context'] : 'media';
$hide_bulk = ! empty( $args['hide_bulk'] );

if ( empty( $data ) ) {
	return;
}

$has_auth = ! empty( $data['has_auth'] );
$is_valid = ! empty( $data['is_valid'] );
$account  = isset( $data['status'] ) ? $data['status'] : null;
$active   = ( $has_auth && $is_valid );
?>

<div class="kraken-summary kraken-summary--<?php echo esc_attr( $context ); ?><?php echo $active ? '' : ' kraken-summary--inactive'; ?>">

	<div class="kraken-summary__bar">
		<div class="kraken-summary__brand">
			<img class="kraken-summary__logo" src="<?php echo esc_url( $data['logo'] ); ?>" alt="Kraken.io" />

			<?php if ( ! $has_auth ) : ?>
				<span class="kraken-summary__badge kraken-summary__badge--muted"><?php esc_html_e( 'Not connected', 'kraken-io' ); ?></span>
			<?php elseif ( ! $is_valid ) : ?>
				<span class="kraken-summary__badge kraken-summary__badge--error"><?php esc_html_e( 'Invalid credentials', 'kraken-io' ); ?></span>
			<?php elseif ( ! empty( $data['overdue'] ) ) : ?>
				<span class="kraken-summary__badge kraken-summary__badge--error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Payment overdue', 'kraken-io' ); ?></span>
			<?php else : ?>
				<span class="kraken-summary__badge kraken-summary__badge--ok"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Connected', 'kraken-io' ); ?></span>
			<?php endif; ?>
		</div>

		<?php
		if ( $active && ! empty( $account ) && isset( $account['quota_total'] ) ) :
			$total     = (float) $account['quota_total'];
			$used      = isset( $account['quota_used'] ) ? (float) $account['quota_used'] : 0;
			$remaining = isset( $account['quota_remaining'] ) ? (float) $account['quota_remaining'] : max( 0, $total - $used );
			$percent   = $total > 0 ? min( 100, round( $used / $total * 100 ) ) : 0;
			$bar_class = $percent >= 90 ? 'is-danger' : ( $percent >= 70 ? 'is-warning' : '' );
			$plan_name = isset( $account['plan_name'] ) ? $account['plan_name'] : __( 'Active plan', 'kraken-io' );
			?>
			<div class="kraken-summary__usage">
				<span class="kraken-summary__plan"><?php echo esc_html( $plan_name ); ?></span>
				<span class="kraken-summary__track"><span class="kraken-summary__fill <?php echo esc_attr( $bar_class ); ?>" style="width:<?php echo esc_attr( $percent ); ?>%"></span></span>
				<span class="kraken-summary__usage-text">
					<?php
					printf(
						/* translators: %1$s used %2$s total %3$s remaining */
						esc_html__( '%1$s / %2$s · %3$s left', 'kraken-io' ),
						esc_html( kraken_io()->format_bytes( $used ) ),
						esc_html( kraken_io()->format_bytes( $total ) ),
						esc_html( kraken_io()->format_bytes( $remaining ) )
					);
					?>
				</span>
			</div>
		<?php endif; ?>

		<?php if ( $active ) : ?>
			<div class="kraken-summary__actions">
				<a class="kraken-summary__stats-link" href="<?php echo esc_url( $data['stats_url'] ); ?>"><?php esc_html_e( 'View full stats', 'kraken-io' ); ?> &rarr;</a>
				<button type="button" class="kraken-summary__toggle" aria-expanded="false"><?php esc_html_e( 'Settings', 'kraken-io' ); ?><span class="dashicons dashicons-arrow-down-alt2"></span></button>
			</div>
		<?php endif; ?>
	</div>

	<?php
	if ( ! empty( $data['conflicts'] ) ) {
		kraken_io()->get_template( 'conflicts-notice', [ 'conflicts' => $data['conflicts'] ] );
	}
	?>

	<?php if ( ! $has_auth ) : ?>

		<div class="kraken-summary__notice kraken-summary__notice--warning">
			<p><strong><?php esc_html_e( 'No API credentials found.', 'kraken-io' ); ?></strong> <?php esc_html_e( 'Connect your Kraken.io account to start optimizing your images.', 'kraken-io' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( $data['settings_url'] ); ?>"><?php esc_html_e( 'Add API credentials', 'kraken-io' ); ?></a>
		</div>

	<?php elseif ( ! $is_valid ) : ?>

		<div class="kraken-summary__notice kraken-summary__notice--error">
			<p><strong><?php esc_html_e( 'Your Kraken.io API credentials are invalid.', 'kraken-io' ); ?></strong> <?php esc_html_e( 'Please check your API key and secret in the plugin settings.', 'kraken-io' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( $data['settings_url'] ); ?>"><?php esc_html_e( 'Review credentials', 'kraken-io' ); ?></a>
		</div>

	<?php else : ?>

		<?php if ( ! empty( $data['overdue'] ) ) : ?>
			<div class="kraken-summary__alert kraken-summary__alert--error">
				<span class="dashicons dashicons-warning"></span>
				<span><strong><?php esc_html_e( 'Payment overdue — your API access has been restricted.', 'kraken-io' ); ?></strong> <?php esc_html_e( 'Please complete your payment from your kraken.io account to resume optimizing.', 'kraken-io' ); ?></span>
				<a class="kraken-summary__alert-link" href="<?php echo esc_url( $data['links']['billing'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Complete payment', 'kraken-io' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $data['quota_exceeded'] ) ) : ?>
			<div class="kraken-summary__alert kraken-summary__alert--warning">
				<span class="dashicons dashicons-warning"></span>
				<?php if ( ! empty( $data['is_free'] ) ) : ?>
					<span><strong><?php esc_html_e( 'Free test quota used up.', 'kraken-io' ); ?></strong> <?php esc_html_e( 'Upgrade to a paid plan to keep optimizing, or email support@kraken.io to request more test quota.', 'kraken-io' ); ?></span>
					<a class="kraken-summary__alert-link" href="<?php echo esc_url( $data['links']['pricing'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade plan', 'kraken-io' ); ?></a>
				<?php else : ?>
					<span><strong><?php esc_html_e( 'Monthly quota exceeded.', 'kraken-io' ); ?></strong> <?php esc_html_e( 'Further optimizations will be billed at the end of your billing period.', 'kraken-io' ); ?></span>
					<a class="kraken-summary__alert-link" href="<?php echo esc_url( $data['links']['account'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View account', 'kraken-io' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		$unoptimized = isset( $data['unoptimized'] ) ? $data['unoptimized'] : [ 'total' => 0 ];
		$can_manage  = ! empty( $data['can_manage'] );
		$show_bulk   = ! $hide_bulk && ! empty( $unoptimized['total'] );
		if ( $can_manage || $show_bulk ) :
			?>
			<div class="kraken-summary__convert">
				<?php if ( $can_manage ) : ?>
					<label for="kraken-convert-select"><?php esc_html_e( 'Convert uploads to:', 'kraken-io' ); ?></label>
					<select id="kraken-convert-select" class="kraken-summary__convert-select">
						<?php foreach ( $data['convert_formats'] as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $data['convert_format'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php /* Auto-optimize quick toggle intentionally not shown here (yet) — the sync plumbing (AJAX + JS) is in place; re-add the select to enable it. */ ?>
					<span class="kraken-summary__convert-hint" aria-live="polite"></span>
				<?php endif; ?>

				<?php if ( $show_bulk ) : ?>
					<button type="button" class="kraken-summary__bulk-button"
						data-ids="<?php echo esc_attr( wp_json_encode( $unoptimized['ids'] ) ); ?>"
						data-pages="<?php echo esc_attr( $unoptimized['pages'] ); ?>"
						data-total="<?php echo esc_attr( $unoptimized['total'] ); ?>">
						<?php
						printf(
							/* translators: %s number of images */
							esc_html__( 'Bulk optimize (%s)', 'kraken-io' ),
							esc_html( number_format_i18n( $unoptimized['total'] ) )
						);
						?>
					</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="kraken-summary__details">
			<div class="kraken-summary__settings">
				<div class="kraken-summary__group">
					<span class="kraken-summary__group-title"><?php esc_html_e( 'General', 'kraken-io' ); ?></span>
					<?php foreach ( $data['general'] as $item ) : ?>
						<span class="kraken-summary__pill">
							<?php echo esc_html( $item['label'] ); ?>
							<?php if ( ! empty( $item['value'] ) ) : ?>
								<b><?php echo esc_html( $item['value'] ); ?></b>
							<?php endif; ?>
						</span>
					<?php endforeach; ?>
					<a class="kraken-summary__edit" href="<?php echo esc_url( $data['settings_url'] ); ?>"><?php esc_html_e( 'Edit', 'kraken-io' ); ?></a>
				</div>

				<div class="kraken-summary__group">
					<span class="kraken-summary__group-title"><?php esc_html_e( 'Advanced', 'kraken-io' ); ?></span>
					<?php if ( empty( $data['advanced'] ) ) : ?>
						<span class="kraken-summary__none"><?php esc_html_e( 'None enabled', 'kraken-io' ); ?></span>
					<?php else : ?>
						<?php foreach ( $data['advanced'] as $item ) : ?>
							<span class="kraken-summary__pill">
								<?php echo esc_html( $item['label'] ); ?>
								<?php if ( ! empty( $item['value'] ) ) : ?>
									<b><?php echo esc_html( $item['value'] ); ?></b>
								<?php endif; ?>
							</span>
						<?php endforeach; ?>
					<?php endif; ?>
					<a class="kraken-summary__edit" href="<?php echo esc_url( $data['advanced_url'] ); ?>"><?php esc_html_e( 'Edit', 'kraken-io' ); ?></a>
				</div>

				<div class="kraken-summary__formats">
					<span class="kraken-summary__formats-label"><?php esc_html_e( 'Supported:', 'kraken-io' ); ?></span>
					<?php foreach ( $data['formats'] as $format ) : ?>
						<span class="kraken-summary__format"><?php echo esc_html( $format ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

	<?php endif; ?>

</div>
