<?php
/**
 * Contact / support.
 *
 * WordPress can't reliably send mail from most sites — wp_mail() frequently
 * reports success even when nothing is actually delivered (no SMTP, sendmail
 * silently dropping the message, etc.). Rather than show a misleading "sent"
 * we point users at channels that don't depend on the site's mail config at
 * all: the plugin's WordPress.org support forum, and a pre-filled mailto: that
 * opens in the user's own mail client.
 *
 * @package Kraken_IO/Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$forum_url = 'https://wordpress.org/support/plugin/kraken-image-optimizer/';

// Environment details we ask users to include — also pre-filled into the email
// body and offered as a one-click copy.
$diagnostics = array(
	__( 'Site', 'kraken-image-optimizer' )      => home_url(),
	__( 'WordPress', 'kraken-image-optimizer' ) => get_bloginfo( 'version' ),
	__( 'PHP', 'kraken-image-optimizer' )       => PHP_VERSION,
	__( 'Plugin', 'kraken-image-optimizer' )    => kraken_io()->get_version(),
);

$diagnostics_text = '';
foreach ( $diagnostics as $label => $value ) {
	$diagnostics_text .= $label . ': ' . $value . "\n";
}

$mail_subject = sprintf(
	/* translators: %s: site URL */
	__( 'Kraken.io WordPress plugin support — %s', 'kraken-image-optimizer' ),
	home_url()
);
$mail_body = __( 'Describe your question or problem here.', 'kraken-image-optimizer' ) . "\n\n----\n" . $diagnostics_text;
$mailto     = 'mailto:support@kraken.io?subject=' . rawurlencode( $mail_subject ) . '&body=' . rawurlencode( $mail_body );
?>

<div class="kraken-support">
	<h2 class="kraken-support__title"><?php esc_html_e( 'Contact us', 'kraken-image-optimizer' ); ?></h2>
	<p class="kraken-support__intro">
		<?php esc_html_e( 'Have a question or hit a problem? The quickest way to reach the Kraken.io team is our WordPress.org support forum — open a thread and we will reply there. Prefer email? Use the button below to write to us from your own mail app.', 'kraken-image-optimizer' ); ?>
	</p>

	<p class="kraken-support__actions">
		<a class="button button-primary button-hero kraken-support__forum" href="<?php echo esc_url( $forum_url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="dashicons dashicons-format-chat" aria-hidden="true"></span>
			<?php esc_html_e( 'Open the support forum', 'kraken-image-optimizer' ); ?>
		</a>
		<a class="button button-hero kraken-support__email" href="<?php echo esc_url( $mailto ); ?>">
			<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Email support@kraken.io', 'kraken-image-optimizer' ); ?>
		</a>
	</p>

	<div class="kraken-support__diagnostics">
		<p class="kraken-support__diagnostics-head">
			<strong><?php esc_html_e( 'Please include these details so we can help faster:', 'kraken-image-optimizer' ); ?></strong>
			<button type="button" class="button button-small kraken-support__copy" data-clipboard="<?php echo esc_attr( $diagnostics_text ); ?>">
				<?php esc_html_e( 'Copy', 'kraken-image-optimizer' ); ?>
			</button>
			<span class="kraken-support__copied" aria-live="polite"></span>
		</p>
		<ul>
			<?php foreach ( $diagnostics as $label => $value ) : ?>
				<li><?php echo esc_html( $label ); ?>: <code><?php echo esc_html( $value ); ?></code></li>
			<?php endforeach; ?>
		</ul>
		<p class="description kraken-support__hint">
			<?php esc_html_e( 'These links never depend on your server\'s email setup, so your message always reaches us.', 'kraken-image-optimizer' ); ?>
		</p>
	</div>
</div>
