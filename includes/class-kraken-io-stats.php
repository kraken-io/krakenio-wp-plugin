<?php
/**
 * Kraken IO Stats.
 *
 * @package Kraken_IO/Classes
 * @since   2.7
 */

defined('ABSPATH') || exit;

class Kraken_IO_Stats
{
	/**
	 * Options.
	 *
	 * @var    array
	 * @access private
	 */
	private $options = [];

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct()
	{
		add_filter('manage_media_columns', [$this, 'add_media_columns']);
		add_action('manage_media_custom_column', [$this, 'fill_media_columns'], 10, 2);
		add_filter('attachment_fields_to_edit', [$this, 'attachment_fields'], 10, 2);

		// Expose the optimizing state to the JS media views (grid + modal) so we
		// can overlay an indicator on the thumbnail while optimization runs.
		add_filter('wp_prepare_attachment_for_js', [$this, 'add_js_optimizing_flag'], 10, 2);

		// Keep the site-wide savings figure (Stats tab) fresh as images optimize.
		add_action('kraken_io_image_optimized', [$this, 'clear_site_savings_cache']);

		$this->options = kraken_io()->get_options();
	}

	/**
	 * Aggregate optimization savings for THIS site.
	 *
	 * Sums the bytes saved across every optimized attachment on the site — each
	 * full-size image AND all of its thumbnail sizes — from the per-image meta the
	 * plugin already stores (_kraken_size and _kraked_thumbs). This is a site-local
	 * figure built from this install's own data; it is unrelated to the Kraken.io
	 * account quota shown alongside it.
	 *
	 * Cached in a transient (busted whenever an image is optimized) so the Stats
	 * tab never re-runs the aggregation on every page load.
	 *
	 * @since  3.0.3
	 * @access public
	 * @param  bool $force Recompute even if a cached value exists.
	 * @return array {original, kraked, saved, percent, images, thumbs}
	 */
	public function get_site_savings($force = false)
	{
		$cache_key = 'kraken_io_site_savings';

		if (!$force) {
			$cached = get_transient($cache_key);
			if (is_array($cached)) {
				return $cached;
			}
		}

		global $wpdb;

		// Track full-size images and thumbnails SEPARATELY, so the Stats screen
		// can show "from how much to how much" for each — the original total
		// includes every generated thumbnail, which is why it is normally far
		// larger than what the user actually uploaded.
		$main_original  = 0;
		$main_kraked    = 0;
		$thumb_original = 0;
		$thumb_kraked   = 0;
		$images         = 0;
		$thumbs         = 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('_kraken_size', '_kraked_thumbs')"
		);

		foreach ((array) $rows as $value) {
			$data = maybe_unserialize($value);

			if (!is_array($data)) {
				continue;
			}

			if (isset($data['original_size'])) {
				// _kraken_size for a successfully optimized main image.
				$o = $this->to_bytes($data['original_size']);
				$k = isset($data['kraked_size']) ? $this->to_bytes($data['kraked_size']) : $o;

				if ($o > 0) {
					$main_original += $o;
					$main_kraked   += $k;
					$images++;
				}
			} elseif (!isset($data['error'])) {
				// _kraked_thumbs: a list of per-thumbnail-size entries.
				foreach ($data as $thumb) {
					if (!is_array($thumb) || !isset($thumb['original_size'])) {
						continue;
					}

					$o = $this->to_bytes($thumb['original_size']);
					$k = isset($thumb['kraked_size']) ? $this->to_bytes($thumb['kraked_size']) : $o;

					if ($o > 0) {
						$thumb_original += $o;
						$thumb_kraked   += $k;
						$thumbs++;
					}
				}
			}
		}

		$total_original = $main_original + $thumb_original;
		$total_kraked   = $main_kraked + $thumb_kraked;

		$result = [
			'main_original'  => $main_original,
			'main_kraked'    => $main_kraked,
			'main_saved'     => max(0, $main_original - $main_kraked),
			'main_percent'   => $this->pct($main_original, $main_kraked),
			'thumb_original' => $thumb_original,
			'thumb_kraked'   => $thumb_kraked,
			'thumb_saved'    => max(0, $thumb_original - $thumb_kraked),
			'thumb_percent'  => $this->pct($thumb_original, $thumb_kraked),
			'original'       => $total_original,
			'kraked'         => $total_kraked,
			'saved'          => max(0, $total_original - $total_kraked),
			'percent'        => $this->pct($total_original, $total_kraked),
			'images'         => $images,
			'thumbs'         => $thumbs,
		];

		set_transient($cache_key, $result, HOUR_IN_SECONDS);

		return $result;
	}

	/**
	 * Percentage saved, guarded against divide-by-zero.
	 *
	 * @since  3.0.3
	 * @access private
	 * @param  int $original
	 * @param  int $kraked
	 * @return float
	 */
	private function pct($original, $kraked)
	{
		return $original > 0 ? round(max(0, $original - $kraked) / $original * 100, 1) : 0;
	}

	/**
	 * Normalise a stored size to an integer number of bytes. Modern data stores
	 * plain byte integers; very old data stored strings like "12.3 kb" — both are
	 * accepted so historical optimizations still count.
	 *
	 * @since  3.0.3
	 * @access private
	 * @param  mixed $value
	 * @return int
	 */
	private function to_bytes($value)
	{
		// Defensive: ignore anything that isn't a plain scalar (a malformed or
		// partially-written meta value could be an array/object/null). Never let
		// the Stats screen fatal on bad data.
		if (!is_scalar($value)) {
			return 0;
		}

		if (is_numeric($value)) {
			return (int) $value;
		}

		if (is_string($value) && stripos($value, 'kb') !== false) {
			return (int) round((float) $value * 1024);
		}

		return (int) $value;
	}

	/**
	 * Bust the cached site-wide savings figure.
	 *
	 * @since  3.0.3
	 * @access public
	 */
	public function clear_site_savings_cache()
	{
		delete_transient('kraken_io_site_savings');
	}

	/**
	 * Whether an attachment is currently being optimized (main image or thumbnails).
	 *
	 * @since  3.0.0
	 * @access public
	 * @param  int $id Attachment ID.
	 * @return bool
	 */
	public function is_optimizing($id)
	{
		// Nothing can be optimizing if the account isn't connected — don't show
		// the indicator on a disconnected site.
		if (!kraken_io()->api->has_auth()) {
			return false;
		}

		$meta = get_post_meta($id, '_kraken_size', true);

		// A stored error is a finished (failed) state, not "optimizing".
		if (is_array($meta) && isset($meta['error'])) {
			return false;
		}

		// Once the main image has a result, the image IS optimized as far as
		// the user can see — show the savings badge, never a spinner, even if
		// the thumbnails are still working through the background queue. Their
		// stats simply fill in when that job completes.
		if (is_array($meta) && isset($meta['kraked_size'])) {
			return false;
		}

		$thumbs_meta         = get_post_meta($id, '_kraked_thumbs', true);
		$optimizing_main     = get_post_meta($id, '_kraken_io_is_optimizing_main_image', true);
		$optimizing_thumbs   = get_post_meta($id, '_kraken_io_is_optimizing_thumbnails', true);
		$optimize_main_image = !empty($this->options['optimize_main_image']);

		// Staleness guard: a background job that never reported back (server
		// restart, dropped loopback, etc.) must not show a perpetual spinner.
		// Treat an optimizing flag with no recent start time as finished.
		if ($optimizing_main || $optimizing_thumbs) {
			$started = (int) get_post_meta($id, '_kraken_io_optimizing_started', true);
			$timeout = (int) apply_filters('kraken_io_optimizing_timeout', 5 * MINUTE_IN_SECONDS);

			if ($started <= 0 || ( time() - $started ) > $timeout) {
				return false;
			}
		}

		if (!isset($meta['kraked_size']) && $optimize_main_image && $optimizing_main) {
			return true;
		}

		if (empty($thumbs_meta) && $optimizing_thumbs) {
			return true;
		}

		return false;
	}

	/**
	 * Flag the optimizing state on the attachment model used by the media grid/modal.
	 *
	 * @since  3.0.0
	 * @access public
	 * @param  array   $response   Prepared attachment data.
	 * @param  WP_Post $attachment Attachment post object.
	 * @return array
	 */
	public function add_js_optimizing_flag($response, $attachment)
	{
		$id = $attachment->ID;

		if (!kraken_io()->is_supported_attachment($id)) {
			return $response;
		}

		$optimizing = $this->is_optimizing($id);

		$response['krakenOptimizing'] = $optimizing;
		$response['krakenSavings']    = '';
		$response['krakenError']      = '';

		// Expose a stored failure so the grid can show a red error badge (not
		// gated on the savings-badge option — errors should always be visible).
		if (!$optimizing) {
			$meta = get_post_meta($id, '_kraken_size', true);
			if (is_array($meta) && !empty($meta['error'])) {
				$response['krakenError'] = $meta['error'];
			}
		}

		// When finished, expose the total savings so the grid can show a badge
		// (only if the badge option is enabled).
		if (!$optimizing && !empty($this->options['show_savings_badge'])) {
			$summary     = $this->get_image_stats_summary($id);
			$has_savings = !empty($summary['is_main_image_optimized']) || !empty($summary['is_thumbs_optimized']);
			$percentage  = isset($summary['percentage']) ? $summary['percentage'] : 0;

			if ($has_savings && is_string($percentage) && '0%' !== $percentage) {
				$response['krakenSavings'] = $percentage;
			}
		}

		return $response;
	}

	/**
	 * Add custom columns in the Media list table.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $columns An array of columns.
	 * @return array $columns An array of columns.
	 */
	public function add_media_columns($columns)
	{
		$columns['kraken-stats'] = esc_html__('Kraken.io', 'kraken-io');
		return $columns;
	}

	/**
	 * Add content for custom columns in the Media list table.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $column_name Name of the custom column.
	 * @param  int $post_id Attachment ID
	 * @return void
	 */
	public function fill_media_columns($column_name, $id)
	{

		switch ($column_name) {
			case 'kraken-stats':
				$stats = $this->get_image_stats($id);
				kraken_io()->get_template('media-column-stats', ['stats' => $stats]);
				break;
		}

	}

	/**
	 * Add Kraken stats to media uploader.
	 *
	 * @since  2.7
	 * @access public
	 * @param  $form_fields array, fields to include in attachment form
	 * @param  $post object, attachment record in database
	 * @return $form_fields, modified form fields
	 */
	public function attachment_fields($form_fields, $post)
	{

		$stats = $this->get_image_stats($post->ID);
		ob_start();
		kraken_io()->get_template('media-column-stats', ['stats' => $stats]);
		$template = ob_get_clean();

		$form_fields['kraken-stats'] = [
			'label' => esc_html__('Kraken.io', 'kraken-io'),
			'input' => 'html',
			'html' => $template,
			'show_in_edit' => true,
			'show_in_modal' => true,
		];

		return $form_fields;
	}

	/**
	 * Get image original size.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $post_id Attachment ID
	 * @return string|bool $size
	 */
	public function get_original_size($id)
	{

		$file = get_attached_file($id);

		// if file does not exist
		if (!$file) {
			return false;
		}

		$original_size = filesize($file);
		$original_size = kraken_io()->format_bytes($original_size);

		if (wp_attachment_is_image($id)) {

			$meta = get_post_meta($id, '_kraken_size', true);

			if (isset($meta['original_size'])) {

				if (stripos($meta['original_size'], 'kb') !== false) {
					return kraken_io()->format_bytes(ceil(floatval($meta['original_size']) * 1024));
				} else {
					return kraken_io()->format_bytes($meta['original_size']);
				}
			} else {
				return $original_size;
			}
		} else {
			return $original_size;
		}
	}

	/**
	 * Get image stats.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $post_id Attachment ID
	 * @param  bool|string $api_errors API error messages
	 * @return array $stats
	 */
	public function get_image_stats($id, $api_errors = false)
	{

		$type = $this->options['api_lossy'];
		$optimize_main_image = $this->options['optimize_main_image'];

		$stats = [
			'id' => $id,
			'type' => $type,
			'is_image' => false,
			'is_optimizing' => false,
			'is_optimized' => false,
			'has_savings' => true,
			'has_error' => false,
			'show_button' => false,
			'show_reset' => false,
			'stats' => [],
			'api_errors' => $api_errors,
			'size' => $this->get_original_size($id),
		];

		$image_url = wp_get_attachment_url($id);
		$filename = basename($image_url);

		if (!kraken_io()->is_supported_attachment($id)) {
			return $stats;
		}

		$meta = get_post_meta($id, '_kraken_size', true);
		$thumbs_meta = get_post_meta($id, '_kraked_thumbs', true);

		$stats['is_image'] = true;
		$stats['image_url'] = $image_url;
		$stats['filename'] = $filename;
		$stats['show_reset'] = $this->options['show_reset'];

		if ((isset($meta['kraked_size']) && empty($meta['no_savings'])) || !empty($thumbs_meta)) {
			$stats['is_optimized'] = true;
			$stats['stats'] = $this->get_image_stats_summary($id);

			if (!isset($meta['kraked_size']) && $optimize_main_image) {
				$stats['show_button'] = true;
			}
		} else {
			if (!empty($meta['no_savings'])) {
				$stats['has_savings'] = false;
			} elseif (isset($meta['error'])) {
				$stats['has_error'] = $meta['error'];
			}
		}

		// Single source of truth for the optimizing state. The is_optimizing()
		// method also applies the error guard (a stored error is a finished/failed
		// state), the staleness guard, and the auth check — so a failed image shows
		// its error instead of a perpetual "Optimizing..." spinner. Duplicating the
		// flag logic here (as before) ignored those guards and hid the error.
		$stats['is_optimizing'] = $this->is_optimizing($id);

		return $stats;
	}

	public function calculate_savings($meta)
	{

		if (isset($meta['original_size'])) {

			$saved_bytes = isset($meta['saved_bytes']) ? $meta['saved_bytes'] : '';
			$savings_percentage = isset($meta['savings_percent']) ? $meta['savings_percent'] : '';

			// convert old data format, where applicable
			if (stripos($saved_bytes, 'kb') !== false) {
				$saved_bytes = kraken_io()->kb_string_to_bytes($saved_bytes);
			} else {
				if (!$saved_bytes) {
					$saved_bytes = '0 bytes';
				} else {
					$saved_bytes = kraken_io()->format_bytes($saved_bytes);
				}
			}

			return [
				'saved_bytes' => $saved_bytes,
				'savings_percentage' => $savings_percentage,
			];

		} elseif (!empty($meta)) {
			$total_thumb_byte_savings = 0;
			$total_thumb_size = 0;
			$thumbs_savings_percentage = '';
			$total_thumbs_savings = '';

			foreach ($meta as $k => $v) {
				$total_thumb_size += $v['original_size'];
				$thumb_byte_savings = $v['original_size'] - $v['kraked_size'];
				$total_thumb_byte_savings += $thumb_byte_savings;
			}

			$thumbs_savings_percentage = $total_thumb_size > 0 ? round(($total_thumb_byte_savings / $total_thumb_size * 100), 2) . '%' : '0%';
			if ($total_thumb_byte_savings) {
				$total_thumbs_savings = kraken_io()->format_bytes($total_thumb_byte_savings);
			} else {
				$total_thumbs_savings = '0 bytes';
			}

			return [
				'savings_percentage' => $thumbs_savings_percentage,
				'total_savings' => $total_thumbs_savings,
			];
		}

		return [
			'saved_bytes' => '0 bytes',
			'savings_percentage' => '0%',
		];
	}

	/**
	 * Get image stats summary.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $post_id Attachment ID
	 * @return array $summary
	 */
	public function get_image_stats_summary($id)
	{
		$image_meta = get_post_meta($id, '_kraken_size', true);
		$thumbs_meta = get_post_meta($id, '_kraked_thumbs', true);

		$total_original_size = 0;
		$total_saved_bytes = 0;

		$total_savings_percentage = 0;
		$type                     = '';

		$summary = [
			'percentage' => 0,
			'total' => 0,
			'is_main_image_optimized' => false,
			'is_thumbs_optimized' => false,
			'main_image_stats' => [],
			'thumbs_count' => 0,
			'thumbs_stats' => [],
			'optimization_mode' => false,
		];

		$main_image_optimized = !empty($image_meta) && isset($image_meta['type']);
		$thumbs_optimized = !empty($thumbs_meta) && count($thumbs_meta) && isset($thumbs_meta[0]['type']);

		if ($main_image_optimized) {
			$type = $image_meta['type'];
			$summary['is_main_image_optimized'] = true;
			$summary['main_image_stats'] = $this->calculate_savings($image_meta);
		}

		if ($thumbs_optimized) {
			$type = $thumbs_meta[0]['type'];
			$summary['is_thumbs_optimized'] = true;
			$summary['thumbs_stats'] = $this->calculate_savings($thumbs_meta);
			$summary['thumbs_count'] = count($thumbs_meta);
		}

		$summary['optimization_mode'] = ucfirst($type);

		// backward compat
		if (isset($image_meta['original_size'])) {

			$original_size = $image_meta['original_size'];

			if (stripos($original_size, 'kb') !== false) {
				$total_original_size = ceil(floatval($original_size) * 1024);
			} else {
				$total_original_size = (int) $original_size;
			}

			if (isset($image_meta['saved_bytes'])) {
				$saved_bytes = $image_meta['saved_bytes'];
				if (is_string($saved_bytes)) {
					$total_saved_bytes = (int) ceil(floatval($saved_bytes) * 1024);
				} else {
					$total_saved_bytes = $saved_bytes;
				}
			}
		}

		if (!empty($thumbs_meta)) {
			$thumb_saved_bytes = 0;

			foreach ($thumbs_meta as $k => $v) {
				$total_original_size += $v['original_size'];
				$thumb_saved_bytes = $v['original_size'] - $v['kraked_size'];
				$total_saved_bytes += $thumb_saved_bytes;
			}
		}

		if ($total_saved_bytes && $total_original_size > 0) {

			$total_savings_percentage = round(($total_saved_bytes / $total_original_size * 100), 2) . '%';

			$summary['percentage'] = $total_savings_percentage;
			$summary['total'] = kraken_io()->format_bytes($total_saved_bytes);
		}

		return $summary;
	}

}
