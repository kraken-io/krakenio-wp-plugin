<?php
/**
 * Kraken IO Ajax.
 *
 * @package Kraken_IO/Classes
 * @since   2.7
 */

defined('ABSPATH') || exit;

class Kraken_IO_Ajax
{

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct()
	{
		add_action('wp_ajax_kraken_reset_image', [$this, 'reset_image']);
		add_action('wp_ajax_kraken_reset_all', [$this, 'reset_all_images']);
		add_action('wp_ajax_kraken_optimize_image', [$this, 'optimize_image']);
		add_action('wp_ajax_kraken_get_unoptimized_images', [$this, 'get_unoptimized_images']);
		add_action('wp_ajax_kraken_optimizing_status', [$this, 'optimizing_status']);
		add_action('wp_ajax_kraken_set_convert_format', [$this, 'set_convert_format']);
		add_action('wp_ajax_kraken_set_auto_optimize', [$this, 'set_auto_optimize']);
	}

	/**
	 * Toggle the global "automatically optimize uploads" option from the
	 * summary panel's quick control, kept live-synced with the settings
	 * checkbox.
	 *
	 * @since  3.0.0
	 * @access public
	 */
	public function set_auto_optimize()
	{
		check_ajax_referer('kraken-io-nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error();
		}

		$enabled = !empty($_POST['enabled']) && '1' === sanitize_text_field(wp_unslash($_POST['enabled']));

		$options                  = kraken_io()->get_options();
		$options['auto_optimize'] = $enabled ? '1' : '';
		kraken_io()->set_options($options);

		wp_send_json_success(['enabled' => $enabled ? '1' : '']);
	}

	/**
	 * The capability required to trigger optimization actions, from the
	 * "Who can optimize images" advanced setting. Defaults to 'upload_files'
	 * (Author and above) and is validated against the allowed set.
	 *
	 * @since  3.0.0
	 * @access private
	 * @return string
	 */
	private function optimize_capability()
	{
		$options    = kraken_io()->get_options();
		$capability = isset($options['optimize_capability']) ? $options['optimize_capability'] : 'upload_files';

		return in_array($capability, ['read', 'upload_files', 'manage_options'], true) ? $capability : 'upload_files';
	}

	/**
	 * Shared gate for single-attachment AJAX actions: valid nonce, the
	 * configured optimize capability, optional per-object edit rights, and a
	 * Kraken-supported attachment. Returns the attachment ID or terminates the
	 * request with an error.
	 *
	 * @since  3.0.0
	 * @access private
	 * @return int Attachment ID.
	 */
	private function authorize_attachment_request()
	{
		check_ajax_referer('kraken-io-nonce', 'nonce');

		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

		// Configurable capability floor (see the "Who can optimize images"
		// advanced setting). Defaults to 'upload_files' (Author and above), since
		// optimization consumes paid Kraken.io quota and rewrites library media.
		if (!$id || !current_user_can($this->optimize_capability())) {
			wp_send_json_error(['type' => 'unauthorized']);
		}

		// Per-object authorization: the user must be able to edit this specific
		// attachment, so a low-privileged user cannot optimize, convert or reset
		// media they do not own. Enforced by default; the filter exists only so a
		// site can deliberately broaden access, never to silently weaken it.
		if (apply_filters('kraken_io_enforce_object_capability', true, $id) && !current_user_can('edit_post', $id)) {
			wp_send_json_error(['type' => 'unauthorized']);
		}

		if (!kraken_io()->is_supported_attachment($id)) {
			wp_send_json_error(['type' => 'not_image']);
		}

		return $id;
	}

	/**
	 * Save the global "convert uploads to" format. Kept live-synced between the
	 * Kraken.io widget dropdown and the settings select. This writes a global
	 * option, so it requires the same capability as the settings page.
	 *
	 * @since  3.0.0
	 * @access public
	 */
	public function set_convert_format()
	{
		check_ajax_referer('kraken-io-nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error();
		}

		$format = isset($_POST['format']) ? sanitize_text_field(wp_unslash($_POST['format'])) : '';
		$valid  = ['', 'jpeg', 'png', 'gif', 'webp', 'avif'];

		if (!in_array($format, $valid, true)) {
			wp_send_json_error();
		}

		$options                   = kraken_io()->get_options();
		$options['convert_format'] = $format;
		kraken_io()->set_options($options);

		wp_send_json_success(['format' => $format]);
	}

	/**
	 * Report the live optimization state of the given attachments so the media
	 * views can drop the "optimizing" indicator (and refresh the column) once
	 * background optimization finishes — without a full page reload.
	 *
	 * @since  3.0.0
	 * @access public
	 */
	public function optimizing_status()
	{
		check_ajax_referer('kraken-io-nonce', 'nonce');

		if (!current_user_can($this->optimize_capability())) {
			wp_send_json_error(['type' => 'unauthorized']);
		}

		$ids   = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : [];
		$items = [];

		foreach ($ids as $id) {
			// Per-object authorization: only report on attachments the user can
			// edit, so a low-privileged poller cannot read the optimization state
			// or savings of media they do not own.
			if (!$id || !current_user_can('edit_post', $id)) {
				continue;
			}

			$optimizing = kraken_io()->stats->is_optimizing($id);
			$item       = [
				'id'         => $id,
				'optimizing' => $optimizing,
			];

			if ($optimizing) {
				// Tell the live indicator which verb to show, derived from the
				// CURRENT convert setting (so it never goes stale against a page
				// that was loaded under a different "convert uploads to" value):
				// a conversion is happening only when a target format is set and
				// this image isn't already in it; otherwise it's a plain optimize.
				$convert    = kraken_io()->optimization->get_convert_format();
				$mime       = (string) get_post_mime_type($id);
				$target     = ('jpeg' === $convert) ? 'image/jpeg' : 'image/' . $convert;
				$converting = $convert && 0 === strpos($mime, 'image/') && $mime !== $target;

				$item['action'] = $converting ? 'converting' : 'optimizing';
			}

			if (!$optimizing) {
				$stats = kraken_io()->stats->get_image_stats($id);
				ob_start();
				kraken_io()->get_template('media-column-stats', ['stats' => $stats]);
				$item['stats_html'] = ob_get_clean();

				// Surface a stored failure so the grid can show an error badge live.
				$item['error'] = is_string($stats['has_error']) ? $stats['has_error'] : '';

				// Only a background CONVERSION re-points the attachment at a new
				// file/extension; the thumbnails the page rendered at upload time
				// would then 404. Signal that (one-shot) so the client re-fetches
				// just those models — a plain optimization keeps its URLs and is
				// left untouched, avoiding a needless refetch and thumb flicker.
				if (get_post_meta($id, '_kraken_io_converted', true)) {
					delete_post_meta($id, '_kraken_io_converted');
					$item['converted'] = true;
					$thumb             = wp_get_attachment_image_src($id, 'thumbnail');
					$item['thumb']     = isset($thumb[0]) ? $thumb[0] : '';
				}

				// Total savings for the grid badge (only if the option is on).
				$options = kraken_io()->get_options();
				if (!empty($options['show_savings_badge'])) {
					$summary         = kraken_io()->stats->get_image_stats_summary($id);
					$percentage      = isset($summary['percentage']) ? $summary['percentage'] : 0;
					$item['savings'] = ( is_string($percentage) && '0%' !== $percentage ) ? $percentage : '';
				}
			}

			$items[] = $item;
		}

		$data = ['items' => $items];

		// Fresh account usage so the Media Library panel updates live as quota
		// is consumed (the status cache is cleared on each optimization).
		if (kraken_io()->api->has_auth()) {
			$status = kraken_io()->summary->get_account_status();

			if (!empty($status['success']) && isset($status['quota_total'])) {
				$total     = (float) $status['quota_total'];
				$used      = isset($status['quota_used']) ? (float) $status['quota_used'] : 0;
				$remaining = isset($status['quota_remaining']) ? (float) $status['quota_remaining'] : max(0, $total - $used);

				$data['account'] = [
					'percent'    => $total > 0 ? min(100, round($used / $total * 100)) : 0,
					'usage_text' => sprintf(
						/* translators: %1$s used %2$s total %3$s remaining */
						esc_html__('%1$s / %2$s · %3$s left', 'kraken-io'),
						kraken_io()->format_bytes($used),
						kraken_io()->format_bytes($total),
						kraken_io()->format_bytes($remaining)
					),
				];
			}
		}

		wp_send_json_success($data);
	}

	/**
	 * Reset image.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function reset_image()
	{
		$id = $this->authorize_attachment_request();

		$reset_image = kraken_io()->optimization->reset_image($id);

		if ($reset_image) {

			$stats = kraken_io()->stats->get_image_stats($id);

			ob_start();
			kraken_io()->get_template('media-column-stats', ['stats' => $stats]);
			$column_html = ob_get_clean();

			wp_send_json_success(
				[
					'html' => $column_html,
				]
			);
		}

		wp_send_json_error(
			[
				'type' => 'error',
			]
		);
	}

	/**
	 * Reset all images.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function reset_all_images()
	{
		check_ajax_referer('kraken-io-nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(
				[
					'type' => 'unauthorized',
				]
			);
		}

		if (kraken_io()->optimization->reset_all_images()) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Optimize image.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function optimize_image()
	{
		$id = $this->authorize_attachment_request();

		$api_errors = false;
		// Honour the global convert setting, so manual and bulk optimization
		// produce the same result as a fresh upload would.
		$optimized_image = kraken_io()->optimization->optimize_or_convert($id);

		if (isset($optimized_image['errors'])) {
			$api_errors = $optimized_image['errors'];
		}

		$stats = kraken_io()->stats->get_image_stats($id, $api_errors);
		$file = get_attached_file($id);
		$size = kraken_io()->format_bytes(filesize($file));
		$filename = basename(get_attached_file($id));
		$thumb_src = wp_get_attachment_image_src($id, 'thumbnail');

		ob_start();
		kraken_io()->get_template(
			'bulk-optimizer-stats',
			[
				'stats' => $stats,
				'filename' => $filename,
				'size' => $size,
				'thumb_src' => isset($thumb_src[0]) ? $thumb_src[0] : '',
			]
		);
		$bulk_stats_html = ob_get_clean();

		ob_start();
		kraken_io()->get_template('media-column-stats', ['stats' => $stats]);
		$stats_html = ob_get_clean();

		wp_send_json_success(
			[
				'id' => $id,
				'size' => $size,
				'filename' => $filename,
				'stats_html' => $stats_html,
				'bulk_stats_html' => $bulk_stats_html,
			]
		);
	}

	public function get_unoptimized_images()
	{
		check_ajax_referer('kraken-io-nonce', 'nonce');

		if (!current_user_can($this->optimize_capability())) {
			wp_send_json_error(['type' => 'unauthorized']);
		}

		$unoptimized_images = kraken_io()->optimization->get_unoptimized_images();

		wp_send_json_success(
			[
				'ids' => $unoptimized_images['ids'],
			]
		);
	}
}

new Kraken_IO_Ajax();
