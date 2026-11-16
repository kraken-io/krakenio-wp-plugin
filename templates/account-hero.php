<?php
/**
 * Account hero shown at the top of the Kraken.io settings page.
 *
 * Surfaces the connection / plan state with the right call to action:
 *  - not connected  -> get credentials + create an account
 *  - invalid        -> get fresh credentials
 *  - payment overdue -> update billing
 *  - free plan      -> subscribe to a plan
 *  - paid + valid   -> usage + manage account
 *
 * @package Kraken_IO/Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$data = isset( $args['data'] ) ? $args['data'] : [];

if ( empty( $data ) ) {
	return;
}

$has_auth = ! empty( $data['has_auth'] );
$is_valid = ! empty( $data['is_valid'] );
$is_free  = ! empty( $data['is_free'] );
$overdue  = ! empty( $data['overdue'] );
$account  = isset( $data['status'] ) ? $data['status'] : null;
$links    = isset( $data['links'] ) ? $data['links'] : [];

$link_to = function ( $key ) use ( $links ) {
	return isset( $links[ $key ] ) ? $links[ $key ] : 'https://kraken.io';
};

if ( ! $has_auth ) {
	$state = 'disconnected';
} elseif ( ! $is_valid ) {
	$state = 'invalid';
} elseif ( $overdue ) {
	$state = 'overdue';
} elseif ( $is_free ) {
	$state = 'free';
} else {
	$state = 'paid';
}

$show_usage = ( 'free' === $state || 'paid' === $state ) && ! empty( $account ) && isset( $account['quota_total'] );
?>

<div class="kraken-hero kraken-hero--<?php echo esc_attr( $state ); ?>">

	<div class="kraken-hero__main">
		<img class="kraken-hero__logo" src="<?php echo esc_url( $data['tile'] ); ?>" alt="Kraken.io" />

		<div class="kraken-hero__copy">
			<?php
			switch ( $state ) :
				case 'disconnected':
					?>
					<span class="kraken-hero__badge kraken-hero__badge--muted"><?php esc_html_e( 'Not connected', 'kraken-io' ); ?></span>
					<h2 class="kraken-hero__title"><?php esc_html_e( 'Connect your Kraken.io account', 'kraken-io' ); ?></h2>
					<p class="kraken-hero__text"><?php esc_html_e( 'Enter your API key and secret below to start optimizing your images. Don\'t have an account yet? Create one in seconds.', 'kraken-io' ); ?></p>
					<?php
					break;

				case 'invalid':
					?>
					<span class="kraken-hero__badge kraken-hero__badge--error"><?php esc_html_e( 'Invalid credentials', 'kraken-io' ); ?></span>
					<h2 class="kraken-hero__title"><?php esc_html_e( 'Your API credentials are invalid', 'kraken-io' ); ?></h2>
					<p class="kraken-hero__text"><?php esc_html_e( 'Double-check the API key and secret below, or grab fresh credentials from your Kraken.io account.', 'kraken-io' ); ?></p>
					<?php
					break;

				case 'overdue':
					?>
					<span class="kraken-hero__badge kraken-hero__badge--error"><?php esc_html_e( 'Payment overdue', 'kraken-io' ); ?></span>
					<h2 class="kraken-hero__title"><?php esc_html_e( 'Your Kraken.io payment is overdue', 'kraken-io' ); ?></h2>
					<p class="kraken-hero__text"><?php esc_html_e( 'Optimization may be paused until your billing is up to date.', 'kraken-io' ); ?></p>
					<?php
					break;

				case 'free':
					?>
					<span class="kraken-hero__badge kraken-hero__badge--ok"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Connected', 'kraken-io' ); ?></span>
					<h2 class="kraken-hero__title"><?php esc_html_e( 'You\'re on the Free plan', 'kraken-io' ); ?></h2>
					<p class="kraken-hero__text"><?php esc_html_e( 'Upgrade to a paid plan for more monthly quota and larger file sizes.', 'kraken-io' ); ?></p>
					<?php
					break;

				default:
					?>
					<span class="kraken-hero__badge kraken-hero__badge--ok"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Connected', 'kraken-io' ); ?></span>
					<h2 class="kraken-hero__title">
						<?php
						/* translators: %s plan name */
						printf( esc_html__( 'Connected — %s plan', 'kraken-io' ), esc_html( $data['plan_name'] ) );
						?>
					</h2>
					<p class="kraken-hero__text"><?php esc_html_e( 'Your Kraken.io account is active and optimizing your images.', 'kraken-io' ); ?></p>
					<?php
					break;
			endswitch;
			?>
		</div>
	</div>

	<div class="kraken-hero__side">

		<?php
		if ( $show_usage ) :
			$total     = (float) $account['quota_total'];
			$used      = isset( $account['quota_used'] ) ? (float) $account['quota_used'] : 0;
			$remaining = isset( $account['quota_remaining'] ) ? (float) $account['quota_remaining'] : max( 0, $total - $used );
			$percent   = $total > 0 ? min( 100, round( $used / $total * 100 ) ) : 0;
			$bar_class = $percent >= 90 ? 'is-danger' : ( $percent >= 70 ? 'is-warning' : '' );
			?>
			<div class="kraken-hero__usage">
				<div class="kraken-hero__usage-head">
					<span><?php esc_html_e( 'Monthly quota', 'kraken-io' ); ?></span>
					<span class="kraken-hero__usage-pct"><?php echo esc_html( $percent ); ?>%</span>
				</div>
				<span class="kraken-summary__track"><span class="kraken-summary__fill <?php echo esc_attr( $bar_class ); ?>" style="width:<?php echo esc_attr( $percent ); ?>%"></span></span>
				<div class="kraken-hero__usage-foot">
					<span><?php printf( /* translators: %1$s used %2$s total */ esc_html__( '%1$s of %2$s used', 'kraken-io' ), esc_html( kraken_io()->format_bytes( $used ) ), esc_html( kraken_io()->format_bytes( $total ) ) ); ?></span>
					<span><?php printf( /* translators: %s remaining */ esc_html__( '%s left', 'kraken-io' ), esc_html( kraken_io()->format_bytes( $remaining ) ) ); ?></span>
				</div>
			</div>
		<?php endif; ?>

		<div class="kraken-hero__actions">
			<?php
			switch ( $state ) :
				case 'disconnected':
					?>
					<a class="button button-primary button-hero" href="<?php echo esc_url( $link_to( 'signup' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Create an account', 'kraken-io' ); ?></a>
					<a class="button" href="<?php echo esc_url( $link_to( 'credentials' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get API credentials', 'kraken-io' ); ?></a>
					<?php
					break;

				case 'invalid':
					?>
					<a class="button button-primary" href="<?php echo esc_url( $link_to( 'credentials' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get API credentials', 'kraken-io' ); ?></a>
					<?php
					break;

				case 'overdue':
					?>
					<a class="button button-primary" href="<?php echo esc_url( $link_to( 'account' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Update billing', 'kraken-io' ); ?></a>
					<?php
					break;

				case 'free':
					?>
					<a class="button button-primary button-hero" href="<?php echo esc_url( $link_to( 'pricing' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Subscribe to a plan', 'kraken-io' ); ?></a>
					<a class="button" href="<?php echo esc_url( $link_to( 'account' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manage account', 'kraken-io' ); ?></a>
					<?php
					break;

				default:
					?>
					<a class="button button-primary" href="<?php echo esc_url( $link_to( 'account' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manage account', 'kraken-io' ); ?></a>
					<a class="button" href="<?php echo esc_url( $link_to( 'pricing' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade plan', 'kraken-io' ); ?></a>
					<?php
					break;
			endswitch;
			?>
		</div>

	</div>

</div>
