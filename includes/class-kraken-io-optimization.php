<?php
/**
 * Kraken IO Optimization.
 *
 * @package Kraken_IO/Classes
 * @since   2.7
 */

defined('ABSPATH') || exit;

class Kraken_IO_Optimization
{
	/**
	 * Options.
	 *
	 * @var    array
	 * @access private
	 */
	private $options = [];

	/**
	 * Guard so convert_image()'s own metadata regeneration doesn't re-trigger
	 * the convert-on-upload filter (recursion).
	 *
	 * @var    bool
	 * @access private
	 */
	private $is_converting = false;

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct()
	{
		$this->options = kraken_io()->get_options();

		add_action('add_attachment', [$this, 'on_upload']);

		if ($this->options['auto_optimize']) {
			add_filter('wp_generate_attachment_metadata', [$this, 'optimize_thumbnails_on_resize'], 10, 2);
		}

		// Convert-on-upload runs even when auto-optimize is off (conversion also
		// optimizes). It runs at priority 20 — after WordPress has finished
		// generating the original metadata — so renaming the file is safe.
		add_filter('wp_generate_attachment_metadata', [$this, 'maybe_convert_on_metadata'], 20, 2);

		add_action('wp_delete_file', [$this, 'delete_image']);
		add_filter('mod_rewrite_rules', [$this, 'webp_rewrite_rules']);
	}

	/**
	 * Dispatcher for a freshly uploaded attachment: convert if a target format
	 * is set, otherwise optimize when auto-optimize is enabled.
	 *
	 * @since  3.0.0
	 * @access public
	 * @param  int $id Attachment ID.
	 */
	public function on_upload($id)
	{
		if (!kraken_io()->is_supported_attachment($id)) {
			return;
		}

		if ('application/pdf' === (string) get_post_mime_type($id) && empty($this->options['optimize_pdf'])) {
			return;
		}

		// When converting, defer to maybe_convert_on_metadata so WordPress
		// finishes generating the original metadata against the original file.
		if ($this->converts_on_upload($id)) {
			return;
		}

		if (!empty($this->options['auto_optimize'])) {
			$this->optimize_image_on_upload($id);
		}
	}

	/**
	 * Convert a freshly uploaded image once WordPress has generated its metadata.
	 *
	 * @since  3.0.0
	 * @access public
	 * @param  array $metadata Generated attachment metadata.
	 * @param  int   $id       Attachment ID.
	 * @return array
	 */
	public function maybe_convert_on_metadata($metadata, $id)
	{
		// Skip our own internal regeneration (recursion guard).
		if ($this->is_converting) {
			return $metadata;
		}

		// PDF opt-in gate: when the user has NOT opted in to PDF optimization,
		// convert-on-upload must also skip PDFs. Without this, a site with a
		// global "Convert uploads to" format set would silently send the
		// uploaded PDF to Kraken, download the converted image, and
		// wp_delete_file() the original PDF — data loss for a user who
		// explicitly opted out.
		if ('application/pdf' === (string) get_post_mime_type($id) && empty($this->options['optimize_pdf'])) {
			return $metadata;
		}

		// Only images are converted; PDFs are optimized via on_upload instead.
		if (!$this->converts_on_upload($id) || !kraken_io()->is_supported_attachment($id)) {
			return $metadata;
		}

		$convert = $this->get_convert_format();

		// With background processing on, conversion must not block the upload
		// request (an AVIF encode can take ~30s — long enough that users close
		// the page and lose the rest of their upload queue). Queue it instead:
		// the upload returns instantly, the grid shows the optimizing spinner,
		// and the worker converts + re-points the attachment in the background.
		if (!empty($this->options['background_process'])) {
			kraken_io()->bg_process->enqueue(
				[
					'id' => $id,
					'type' => 'convert',
					'count' => 0,
				]
			);
			update_post_meta($id, '_kraken_io_is_optimizing_main_image', true);
			update_post_meta($id, '_kraken_io_optimizing_started', time());

			return $metadata;
		}

		$this->convert_image($id, $convert);

		$fresh = wp_get_attachment_metadata($id);

		return $fresh ? $fresh : $metadata;
	}

	/**
	 * Optimize an attachment, honouring the global "convert uploads to"
	 * setting: images that aren't already in the target format get converted
	 * (conversion optimizes too); everything else — PDFs, files already in the
	 * target format, or no conversion configured — is plainly optimized. This
	 * is what manual and bulk optimization run, so they behave exactly like
	 * fresh uploads do.
	 *
	 * @since  3.0.0
	 * @access public
	 * @param  int $id Attachment ID.
	 * @return bool|array True-ish on success, array with 'errors' on failure.
	 */
	public function optimize_or_convert($id)
	{
		// No PDF opt-in gate here: this is the explicit, per-file path (the
		// Optimize button). "Optimize PDF uploads" only controls AUTOMATIC
		// optimization on upload; a user can always optimize a PDF by hand.
		// Bulk optimization excludes PDFs separately (get_unoptimized_images).
		$format = $this->get_convert_format();
		$mime   = (string) get_post_mime_type($id);
		$target = ('jpeg' === $format) ? 'image/jpeg' : 'image/' . $format;

		if ($format && 0 === strpos($mime, 'image/') && $mime !== $target) {
			$result = $this->convert_image($id, $format);

			if (true === $result) {
				return true;
			}

			return [
				'errors' => [isset($result['error']) ? $result['error'] : __('Conversion failed.', 'kraken-image-optimizer')],
			];
		}

		return $this->optimize_image($id);
	}

	/**
	 * Reset image.
	 *
	 * @since  2.7
	 * @access public
	 * @return bool
	 */
	public function reset_image($id)
	{
		delete_post_meta($id, '_kraken_size');
		delete_post_meta($id, '_kraked_thumbs');

		return true;
	}

	/**
	 * Reset all images.
	 *
	 * @since  2.7
	 * @access public
	 * @return bool
	 */
	public function reset_all_images()
	{
		delete_post_meta_by_key('_kraked_thumbs');
		delete_post_meta_by_key('_kraken_size');

		return true;
	}

	/**
	 * Delete image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $file Path to the file to delete.
	 * @return bool
	 */
	public function delete_image($file)
	{
		$webp = $file . '.webp';

		if (file_exists($webp)) {
			unlink($webp);
		}

		return $file;
	}

	/**
	 * Format optimization response for meta
	 *
	 * @since  2.7
	 * @access private
	 * @param  array $response
	 * @param  int $id
	 * @return array $response
	 */
	private function format_optimization_response($response, $id)
	{

		$original_size      = isset($response['original_size']) ? (float) $response['original_size'] : 0;
		$savings_percentage = $original_size > 0 ? $response['saved_bytes'] / $original_size * 100 : 0;
		$response['savings_percent'] = round($savings_percentage, 2) . '%';

		return $response;
	}


	/**
	 * Replace image with optimized.
	 *
	 * @since  2.7
	 * @access private
	 * @param  string $path
	 * @param  string $url
	 * @return bool
	 */
	private function replace_image($path, $url)
	{

		$response = wp_remote_get($url);
		$optimized_image_contents = !is_wp_error($response) ? wp_remote_retrieve_body($response) : false;
		$replaced_image = false;

		if ($optimized_image_contents) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			$replaced_image = file_put_contents($path, $optimized_image_contents);
		}

		return false !== $replaced_image;
	}

	public function get_preserve_meta_options($options)
	{

		$preserve_meta = [];

		if ($options['preserve_meta_date']) {
			$preserve_meta[] = 'date';
		}

		if ($options['preserve_meta_copyright']) {
			$preserve_meta[] = 'copyright';
		}

		if ($options['preserve_meta_geotag']) {
			$preserve_meta[] = 'geotag';
		}

		if ($options['preserve_meta_orientation']) {
			$preserve_meta[] = 'orientation';
		}

		if ($options['preserve_meta_profile']) {
			$preserve_meta[] = 'profile';
		}

		return $preserve_meta;
	}

	/**
	 * Get optimized image from api.
	 *
	 * @since  2.7
	 * @access private
	 * @param  string $image_path
	 * @param  array $args
	 * @return array
	 */
	private function get_optimized_image($image_path, $args = [])
	{
		$settings = $this->options;

		$args = wp_parse_args(
			$args,
			[
				'type' => '',
				'webp' => false,
				'resize' => true,
				'convert' => '',
			]
		);

		if (!empty($args['type'])) {
			$lossy = 'lossy' === $args['type'];
		} else {
			$lossy = 'lossy' === $settings['api_lossy'];
		}

		$params = [
			'file' => $image_path,
			'wait' => true,
			'lossy' => $lossy,
			'origin' => 'wp',
			'webp' => $args['webp'],
		];

		$preserve_meta = $this->get_preserve_meta_options($settings);

		if (count($preserve_meta)) {
			$params['preserve_meta'] = $preserve_meta;
		}

		if ($settings['chroma']) {
			$params['sampling_scheme'] = $settings['chroma'];
		}

		if ($settings['auto_orient']) {
			$params['auto_orient'] = true;
		}

		// Never send resize for PDFs: the API would run the document through an
		// image resizer, rasterizing every page (text stops being text, fonts are
		// dropped and the file usually grows). Resize is an image-only setting.
		$is_pdf = 'pdf' === strtolower((string) pathinfo($image_path, PATHINFO_EXTENSION));

		if (!$is_pdf && $args['resize'] && (!empty($settings['resize_width']) || !empty($settings['resize_height']))) {

			$width = (int) $settings['resize_width'];
			$height = (int) $settings['resize_height'];

			if ($width && $height) {
				$params['resize'] = [
					'strategy' => 'auto',
					'width' => $width,
					'height' => $height,
				];
			} elseif ($width && !$height) {
				$params['resize'] = [
					'strategy' => 'landscape',
					'width' => $width,
				];
			} elseif ($height && !$width) {
				$params['resize'] = [
					'strategy' => 'portrait',
					'height' => $height,
				];
			}
		}

		// PDF compression level (screen|ebook|printer|prepress). Empty means
		// "Automatic": the API picks settings from the document's content.
		if ($is_pdf && !empty($settings['pdf_level']) && in_array($settings['pdf_level'], ['screen', 'ebook', 'printer', 'prepress'], true)) {
			$params['level'] = $settings['pdf_level'];
		}

		if (isset($settings['jpeg_quality']) && $settings['jpeg_quality'] > 0) {
			$params['quality'] = (int) $settings['jpeg_quality'];
		}

		if (!empty($args['convert'])) {
			$params['convert'] = [
				'format'         => $args['convert'],
				'keep_extension' => false,
			];
		}

		$response = kraken_io()->api->upload($params);

		// Tag the optimization mode (lossy/lossless) on SUCCESS only. On failure
		// the response may carry a machine error 'type' from the API wrapper
		// (e.g. 'file_not_found'); overwriting it here would erase the real reason
		// and surface downstream as a meaningless "Unknown error."
		if (!empty($response['success'])) {
			$response['type'] = !empty($args['type']) ? $args['type'] : $settings['api_lossy'];
		}

		return $response;
	}

	/**
	 * Optimize single image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $path
	 * @param  array $args
	 * @return bool
	 */
	public function optimize_single_image($path, $args = [])
	{

		$optimized_image = $this->get_optimized_image($path, $args);

		if (isset($optimized_image['success']) && $optimized_image['success']) {

			if (!isset($optimized_image['kraked_url'])) {
				return [
					'error' => __('Could not get optimized image URL.', 'kraken-image-optimizer'),
				];
			}

			// Keep the original when the result is not smaller. The API can return
			// a larger file (e.g. small text-only PDFs) while reporting
			// saved_bytes = 0; overwriting would silently grow the file.
			if (isset($optimized_image['original_size'], $optimized_image['kraked_size'])
				&& (float) $optimized_image['kraked_size'] >= (float) $optimized_image['original_size']) {
				$optimized_image['kraked_size'] = $optimized_image['original_size'];
				$optimized_image['saved_bytes'] = 0;

				return $optimized_image;
			}

			if (!$this->replace_image($path, $optimized_image['kraked_url'])) {
				return [
					'error' => __('Could not overwrite original file. Please ensure that your files are writable by plugins.', 'kraken-image-optimizer'),
				];
			}

			return $optimized_image;
		}

		return [
			'error' => $this->resolve_error_message($optimized_image),
		];
	}

	/**
	 * Turn an optimization response into a human-readable error message.
	 *
	 * A failure is described three different ways depending on where it happened:
	 * the Kraken API reports its own failures in 'message'; the HTTP client
	 * (class-kraken.php) reports transport failures — a timeout, an unreadable
	 * file, an invalid response — in 'error'; and the API wrapper (Kraken_IO_API)
	 * reports pre-flight failures as a machine 'type' code. Reading all three
	 * means the user sees the real reason instead of a meaningless "Unknown error."
	 *
	 * @since  3.0.2
	 * @access private
	 * @param  mixed  $response  The optimization response array.
	 * @param  string $fallback  Message to use when none of the above is present.
	 * @return string
	 */
	private function resolve_error_message($response, $fallback = '')
	{
		if (is_array($response)) {
			if (!empty($response['message'])) {
				return $response['message'];
			}

			if (!empty($response['error'])) {
				return $response['error'];
			}

			if (!empty($response['type'])) {
				$map = [
					'no_auth'           => __('Your Kraken.io API credentials are missing or invalid.', 'kraken-image-optimizer'),
					'empty_data'        => __('There was nothing to send for optimization.', 'kraken-image-optimizer'),
					'file_not_provided' => __('No file was provided to optimize.', 'kraken-image-optimizer'),
					'file_not_found'    => __('The image file could not be found on the server.', 'kraken-image-optimizer'),
				];

				if (isset($map[$response['type']])) {
					return $map[$response['type']];
				}
			}
		}

		return '' !== $fallback ? $fallback : __('Unknown error.', 'kraken-image-optimizer');
	}

	/**
	 * Optimize single image to webp.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $path
	 * @param  string $type
	 * @return bool
	 */
	public function optimize_single_image_webp($path, $args = [])
	{

		if (empty($this->options['create_webp'])) {
			return false;
		}

		$optimized_image = $this->get_optimized_image(
			$path,
			wp_parse_args(
				$args,
				[
					'webp' => true,
				]
			)
		);

		if (isset($optimized_image['success']) && $optimized_image['success']) {

			if (!isset($optimized_image['kraked_url'])) {
				return false;
			}

			$path = $path . '.webp';

			if (!$this->replace_image($path, $optimized_image['kraked_url'])) {
				return false;
			}

			return $optimized_image;
		}

		return false;
	}

	/**
	 * Stamp the moment optimization started, but only if it isn't already set —
	 * so the original enqueue time is preserved across the worker's retries.
	 *
	 * The "optimizing" flag and this timestamp drive is_optimizing()'s staleness
	 * guard together; keeping them written as a pair means the guard can never
	 * read a flag with no start time (which it treats as finished) and wrongly
	 * hide an in-progress spinner — nor leave an orphaned flag spinning forever.
	 *
	 * @since  3.0.0
	 * @access private
	 * @param  int $id Attachment ID.
	 */
	private function stamp_optimizing_started($id)
	{
		if (!get_post_meta($id, '_kraken_io_optimizing_started', true)) {
			update_post_meta($id, '_kraken_io_optimizing_started', time());
		}
	}

	/**
	 * Optimize main image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @param  array $args
	 * @return bool|array True if optimized, array with error message if not.
	 */
	public function optimize_main_image($id, $args = [])
	{
		kraken_io()->define('KRAKEN_IO_OPTIMIZE', true);

		$kraked_size = get_post_meta($id, '_kraken_size', true);

		// A stored success short-circuits. A stored error must NOT — otherwise a
		// failed image (e.g. inactive credentials) could never be retried.
		if ($kraked_size && (!is_array($kraked_size) || empty($kraked_size['error']))) {
			// Another path (e.g. the convert flow) finished first — make sure a
			// queued job's "optimizing" flag can't outlive the actual work.
			delete_post_meta($id, '_kraken_io_is_optimizing_main_image');
			return true;
		}

		$path = get_attached_file($id);

		// the image doesn't exist
		if (!$path) {
			return [
				'error' => __("Couldn't find the image path.", 'kraken-image-optimizer'),
			];
		}

		update_post_meta($id, '_kraken_io_is_optimizing_main_image', true);
		$this->stamp_optimizing_started($id);

		$response = $this->optimize_single_image($path, $args);
		$this->optimize_single_image_webp($path, $args);

		if (isset($response['success']) && $response['success']) {

			$data = $this->format_optimization_response($response, $id);
			update_post_meta($id, '_kraken_size', $data);

			$metadata = wp_get_attachment_metadata($id);

			if (!empty($response['kraked_width']) && !empty($response['kraked_height'])) {
				$metadata['width'] = $data['kraked_width'];
				$metadata['height'] = $data['kraked_height'];
			}

			wp_update_attachment_metadata($id, $metadata);

			delete_post_meta($id, '_kraken_io_is_optimizing_main_image');

			// Account usage just changed — let listeners (e.g. the summary
			// panel cache) react.
			do_action('kraken_io_image_optimized', $id);

			return true;
		}

		delete_post_meta($id, '_kraken_io_is_optimizing_main_image');

		// Persist the failure so the Media list column and the attachment detail
		// modal can surface it (the `has_error` branch reads `_kraken_size['error']`).
		// Without this the error is lost: the spinner just times out and the image
		// silently falls back to the "Optimize" button with no reason shown.
		$error_message = isset($response['error']) ? $response['error'] : __('Optimization failed.', 'kraken-image-optimizer');
		update_post_meta($id, '_kraken_size', ['error' => $error_message]);

		return $response;
	}

	/**
	 * Optimize thumbnails.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @param  array $args
	 * @return bool|string True if optimized, array with error message if not.
	 */
	public function optimize_thumbnails($id, $args = [])
	{
		kraken_io()->define('KRAKEN_IO_OPTIMIZE', true);

		$kraked_thumbs = get_post_meta($id, '_kraked_thumbs', true);

		if ($kraked_thumbs) {
			// Already done via another path — never leave a queued job's
			// "optimizing" flag behind to show a phantom spinner.
			delete_post_meta($id, '_kraken_io_is_optimizing_thumbnails');
			return true;
		}

		update_post_meta($id, '_kraken_io_is_optimizing_thumbnails', true);
		$this->stamp_optimizing_started($id);

		$args = wp_parse_args(
			$args,
			[
				'resize' => false,
			]
		);

		$metadata = wp_get_attachment_metadata($id);
		$sizes = kraken_io()->get_image_sizes_to_optimize();
		$thumb_data = [];

		// Sub-sizes live next to the attached file. Derive the directory from
		// the attached file itself, not $metadata['file']: WordPress does not
		// set 'file' in the metadata of PDFs (only 'sizes' for the generated
		// preview images), which made every PDF thumbnail path wrong
		// ("uploads/./name-pdf-150x150.jpg") and manual optimization fail.
		$attached_file    = get_attached_file($id);
		$upload_full_path = $attached_file ? dirname($attached_file) : '';

		$error_responses = [];
		$sizes_meta      = (is_array($metadata) && !empty($metadata['sizes']) && is_array($metadata['sizes'])) ? $metadata['sizes'] : [];

		foreach ($sizes_meta as $key => $size) {

			if (in_array($key, $sizes, true)) {
				$path = $upload_full_path . '/' . $size['file'];
				$response = $this->optimize_single_image($path, $args);
				$this->optimize_single_image_webp($path, $args);

				if (isset($response['success']) && $response['success']) {
					$thumb_data[] = [
						'thumb' => $key,
						'file' => $size['file'],
						'original_size' => $response['original_size'],
						'kraked_size' => $response['kraked_size'],
						'type' => $response['type'],
					];
				} else {
					$error_responses[] = $response['error'];
				}
			}
		}

		delete_post_meta($id, '_kraken_io_is_optimizing_thumbnails');

		if ($thumb_data) {
			update_post_meta($id, '_kraked_thumbs', $thumb_data, false);
			wp_update_attachment_metadata($id, $metadata);
			return true;
		}

		$error_responses = array_unique($error_responses);

		// Having no intermediate sizes to optimize is NOT a failure: PDFs and
		// next-gen uploads (e.g. AVIF) frequently have no generated sub-sizes,
		// and the file itself is handled by optimize_main_image(). Only surface
		// an error when a thumbnail genuinely failed — otherwise report success
		// ("nothing more to do") so a successful main-file optimization isn't
		// masked as "There are no image sizes to optimize."
		if (empty($error_responses)) {
			return true;
		}

		return [
			'error' => $error_responses[0],
		];
	}

	/**
	 * Optimize image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @param  array $args
	 * @return bool|string True on success, error message on failure
	 */
	public function optimize_image($id, $args = [])
	{
		$error_messages = [];
		$options = kraken_io()->get_options();

		if ($options['optimize_main_image']) {
			$optimized_main_image = $this->optimize_main_image($id, $args);
			if (isset($optimized_main_image['error'])) {
				$error_messages[] = $optimized_main_image['error'];
			}
		}

		$optimized_thumbnails = $this->optimize_thumbnails($id, $args);

		if (isset($optimized_thumbnails['error'])) {
			$error_messages[] = $optimized_thumbnails['error'];
		}

		$error_messages = array_unique($error_messages);

		if ($error_messages) {
			return [
				'errors' => $error_messages,
			];
		}

		return true;
	}

	/**
	 * The conversion target format to apply to a new upload. This is the global
	 * "convert uploads to" setting, kept live-synced between the settings page
	 * and the Kraken.io widget dropdown.
	 *
	 * @since  3.0.0
	 * @access public
	 * @return string Empty string means "do not convert".
	 */
	public function get_convert_format()
	{
		return isset($this->options['convert_format']) ? $this->options['convert_format'] : '';
	}

	/**
	 * Whether a fresh upload goes through the convert flow. Conversion only
	 * ever applies to images: "Convert uploads to" turns image uploads into
	 * another image format. Non-images (PDFs) are never converted — doing so
	 * would replace the document with a raster of its first page and delete
	 * the original file. They are optimized like any other upload instead.
	 *
	 * @since  3.1.0
	 * @access public
	 * @param  int $id Attachment ID.
	 * @return bool
	 */
	public function converts_on_upload($id)
	{
		return '' !== (string) $this->get_convert_format()
			&& 0 === strpos((string) get_post_mime_type($id), 'image/');
	}

	/**
	 * Optimize AND convert an attachment to another format, then re-point the
	 * WordPress attachment at the new file (extension, mime, intermediate sizes).
	 *
	 * @since  3.0.0
	 * @access public
	 * @param  int    $id     Attachment ID.
	 * @param  string $format Target format: jpeg|png|gif|webp|avif.
	 * @return bool|array True on success, array with 'error' on failure.
	 */
	public function convert_image($id, $format)
	{
		$valid = ['jpeg', 'png', 'gif', 'webp', 'avif'];

		if (!in_array($format, $valid, true)) {
			return ['error' => __('Unsupported conversion format.', 'kraken-image-optimizer')];
		}

		// Defense in depth: never convert a non-image (e.g. a PDF). Converting
		// would replace the document with an image and delete the original.
		if (!kraken_io()->is_supported_attachment($id) || 0 !== strpos((string) get_post_mime_type($id), 'image/')) {
			return ['error' => __('This file cannot be converted.', 'kraken-image-optimizer')];
		}

		$path = get_attached_file($id);

		if (!$path || !file_exists($path)) {
			return ['error' => __("Couldn't find the image path.", 'kraken-image-optimizer')];
		}

		// Heavier formats (notably AVIF) can transiently time out on the encoder
		// under load. A couple of bounded attempts let those self-heal, so a
		// single flaky response doesn't surface as a hard "Conversion failed" —
		// on the synchronous manual/bulk path especially, which (unlike the
		// background worker) has no outer retry. The count is filterable for
		// sites that want to trade latency for resilience differently.
		$attempts = (int) apply_filters('kraken_io_convert_attempts', 2, $format);
		$attempts = max(1, min(5, $attempts));
		$response = [];

		for ($try = 1; $try <= $attempts; $try++) {
			$response = $this->get_optimized_image($path, ['convert' => $format]);

			if (!empty($response['success']) && !empty($response['kraked_url'])) {
				break;
			}

			// Brief backoff before another attempt (skip after the last one).
			if ($try < $attempts) {
				sleep(1);
			}
		}

		if (empty($response['success']) || empty($response['kraked_url'])) {
			return [
				'error' => $this->resolve_error_message($response, __('Conversion failed.', 'kraken-image-optimizer')),
			];
		}

		// Target extension + mime for the new format.
		$ext      = ('jpeg' === $format) ? 'jpg' : $format;
		$mime     = ('jpg' === $ext) ? 'image/jpeg' : 'image/' . $ext;
		$new_path = preg_replace('/\.[^.\/\\\\]+$/', '.' . $ext, $path);

		// Download the converted file.
		$remote = wp_remote_get($response['kraked_url'], ['timeout' => 30]);
		$body   = !is_wp_error($remote) ? wp_remote_retrieve_body($remote) : false;

		if (!$body) {
			return ['error' => __('Could not download the converted image.', 'kraken-image-optimizer')];
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		if (false === file_put_contents($new_path, $body)) {
			return ['error' => __('Could not write the converted image. Please ensure your uploads are writable.', 'kraken-image-optimizer')];
		}

		// If the extension changed, retire the old files and re-point the attachment.
		if ($new_path !== $path) {
			$old_meta = wp_get_attachment_metadata($id);
			$dir      = trailingslashit(dirname($path));

			if (!empty($old_meta['sizes']) && is_array($old_meta['sizes'])) {
				foreach ($old_meta['sizes'] as $size) {
					if (!empty($size['file']) && file_exists($dir . $size['file'])) {
						wp_delete_file($dir . $size['file']);
					}
				}
			}

			if (file_exists($path)) {
				wp_delete_file($path);
			}

			// Drop any stale .webp companion of the original file.
			$this->delete_image($path);

			update_attached_file($id, $new_path);
			wp_update_post(['ID' => $id, 'post_mime_type' => $mime]);

			// One-shot signal to the live poll that THIS attachment's file was
			// re-pointed (new extension/mime). Only then must the grid re-fetch
			// the model — a plain optimization keeps the same URLs, so it skips
			// the extra REST round-trip (and the thumbnail flicker) entirely.
			update_post_meta($id, '_kraken_io_converted', 1);
		}

		// Reset Kraken metadata for a clean state on the converted file.
		delete_post_meta($id, '_kraked_thumbs');

		// Regenerate intermediate sizes for the converted file (this also lets
		// the thumbnail optimization run on the new sizes).
		if (!function_exists('wp_generate_attachment_metadata')) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Guard so this regeneration doesn't re-enter maybe_convert_on_metadata.
		$this->is_converting = true;
		$new_meta            = wp_generate_attachment_metadata($id, $new_path);
		$this->is_converting = false;

		if (!empty($new_meta)) {
			wp_update_attachment_metadata($id, $new_meta);
		}

		// Store the optimization stats for the converted main image.
		$data = $this->format_optimization_response($response, $id);
		update_post_meta($id, '_kraken_size', $data);

		delete_post_meta($id, '_kraken_io_is_optimizing_main_image');
		delete_post_meta($id, '_kraken_io_optimizing_started');

		do_action('kraken_io_image_optimized', $id);

		return true;
	}

	/**
	 * Optimize images on upload.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 */
	public function optimize_image_on_upload($id)
	{

		if (empty($this->options['optimize_main_image'])) {
			return false;
		}

		if (!kraken_io()->is_supported_attachment($id)) {
			return false;
		}

		if ($this->options['background_process']) {
			$data = [
				'id' => $id,
				'type' => 'main-image',
				'count' => 0,
			];
			kraken_io()->bg_process->enqueue($data);
			update_post_meta($id, '_kraken_io_is_optimizing_main_image', true);
			update_post_meta($id, '_kraken_io_optimizing_started', time());
		} else {
			$this->optimize_main_image($id);
		}
	}

	/**
	 * Optimize thumbnails when they are generated.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function optimize_thumbnails_on_resize($metadata, $id)
	{
		if ('application/pdf' === (string) get_post_mime_type($id) && empty($this->options['optimize_pdf'])) {
			return $metadata;
		}

		// When converting, thumbnails are regenerated in the new format by the
		// convert flow — don't optimize the soon-to-be-replaced originals.
		if ($this->converts_on_upload($id)) {
			return $metadata;
		}

		if ($this->options['background_process']) {
			$data = [
				'id' => $id,
				'type' => 'thumbnails',
				'count' => 0,
			];
			kraken_io()->bg_process->enqueue($data);
			update_post_meta($id, '_kraken_io_is_optimizing_thumbnails', true);
			update_post_meta($id, '_kraken_io_optimizing_started', time());
		} else {
			$this->optimize_thumbnails($id);
		}

		return $metadata;
	}

	/**
	 * Get all unoptimized images.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $posts_per_page
	 * @return array $data
	 */
	public function get_unoptimized_images($posts_per_page = 30)
	{

		$mime_types = array_values(kraken_io()->get_supported_mime_types());
		// When PDF optimization is off, exclude application/pdf so PDFs don't
		// count toward the bulk queue and don't get re-processed forever.
		if (empty($this->options['optimize_pdf'])) {
			$mime_types = array_values(array_filter($mime_types, function ($m) {
				return 'application/pdf' !== $m;
			}));
		}

		$args = [
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			// Only count/list attachments we can actually optimize. Without this
			// the query also matched non-images (HTML, zips, docs, …) that never
			// optimize, so the bulk count was inflated and never reached zero.
			'post_mime_type' => $mime_types,
			'posts_per_page' => $posts_per_page,
			'meta_query' => [
				// A file is "unoptimized" only when it has NEITHER marker: no
				// optimized main image AND no optimized thumbnails. (OR here wrongly
				// kept thumbnail-less files — PDFs, AVIF, tiny images with no sub-
				// sizes — in the list forever, since they never get a _kraked_thumbs
				// entry even after a successful optimization.) This mirrors how
				// get_image_stats() decides a file is already optimized.
				'relation' => 'AND',
				[
					'key' => '_kraken_size',
					'compare' => 'NOT EXISTS',
					'value' => '',
				],
				[
					'key' => '_kraked_thumbs',
					'compare' => 'NOT EXISTS',
					'value' => '',
				],
			],
		];

		$query = new WP_Query($args);

		return [
			'ids' => wp_list_pluck($query->posts, 'ID'),
			'pages' => $query->max_num_pages,
			'total' => $query->found_posts,
		];
	}

	/**
	 * Add webp rules rewrite rules to an .htaccess file
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $rules
	 * @return string $rules
	 */
	public function webp_rewrite_rules($rules)
	{
		$home_root = wp_parse_url(home_url('/'));
		$home_root = $home_root['path'];
		$options = get_option('_kraken_options', []);
		$has_rewrite = isset($options['display_webp']) ? $options['display_webp'] : false;

		$webp_rules = <<<EOD
\n# BEGIN Kraken WebP

<IfModule mod_setenvif.c>
# Vary: Accept for all the requests to jpeg and png.
SetEnvIf Request_URI "\.(jpe?g|png)$" REQUEST_image
</IfModule>

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$home_root}

# Check if browser supports WebP images.
RewriteCond %{HTTP_ACCEPT} image/webp

# Check if WebP replacement image exists.
RewriteCond %{REQUEST_FILENAME}.webp -f

# Serve WebP image instead.
RewriteRule (.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,NC]
</IfModule>

<IfModule mod_headers.c>
Header append Vary Accept env=REQUEST_image
</IfModule>

<IfModule mod_mime.c>
AddType image/webp .webp
</IfModule>

# END Kraken WebP\n\n
EOD;

		if ($has_rewrite) {
			$rules = $webp_rules . $rules;
		}

		return $rules;
	}

}
