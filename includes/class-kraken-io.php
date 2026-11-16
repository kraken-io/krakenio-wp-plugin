<?php
/**
* Kraken IO.
*
* @package Kraken_IO
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO {

	/**
	 * Options.
	 *
	 * @var    array
	 * @access private
	 */
	private $options = [];

	/**
	 * The instance of the api class.
	 *
	 * @var    Kraken_IO_API
	 * @since  2.7
	 * @access protected
	 */
	public $api = null;

	/**
	 * The instance of the settings class.
	 *
	 * @var    Kraken_IO_Settings
	 * @since  2.7
	 * @access protected
	 */
	public $settings = null;

	/**
	 * The instance of the stats class.
	 *
	 * @var    Kraken_IO_Stats
	 * @since  2.7
	 * @access protected
	 */
	public $stats = null;

	/**
	 * The instance of the optimization class.
	 *
	 * @var    Kraken_IO_Optimization
	 * @since  2.7
	 * @access protected
	 */
	public $optimization = null;

	/**
	 * The instance of the background process class.
	 *
	 * @var    Kraken_IO_Background_Process
	 * @since  2.7
	 * @access protected
	 */
	public $bg_process = null;

	/**
	 * The instance of the summary class.
	 *
	 * @var    Kraken_IO_Summary
	 * @since  2.7
	 * @access protected
	 */
	public $summary = null;

	/**
	 * The single instance of the class.
	 *
	 * @var    Kraken_IO
	 * @since  2.7
	 * @access protected
	 */
	protected static $instance = null;

	/**
	 * A dummy magic method to prevent class from being cloned.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * A dummy magic method to prevent class from being unserialized.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Main instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @since  2.7
	 * @access public
	 * @return Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		$this->options = get_option( '_kraken_options', [] );

		$this->includes();
		$this->init_hooks();

		do_action( 'kraken_io_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  2.7
	 * @access private
	 */
	private function init_hooks() {
		add_action( 'init', [ $this, 'init' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Allow Kraken-supported, non-default upload types (SVG, WebP, AVIF, HEIC/HEIF).
		add_filter( 'upload_mimes', [ $this, 'allow_supported_mimes' ] );
		add_filter( 'wp_check_filetype_and_ext', [ $this, 'fix_supported_filetypes' ], 10, 4 );
	}

	/**
	 * Include required files.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function includes() {

		$dir = $this->get_plugin_path();

		require_once $dir . 'includes/vendor/class-kraken.php';
		require_once $dir . 'includes/vendor/class-wp-async-request.php';
		require_once $dir . 'includes/vendor/class-wp-background-process.php';

		require_once $dir . 'includes/class-kraken-io-api.php';
		require_once $dir . 'includes/class-kraken-io-settings.php';
		require_once $dir . 'includes/class-kraken-io-stats.php';
		require_once $dir . 'includes/class-kraken-io-optimization.php';
		require_once $dir . 'includes/class-kraken-io-ajax.php';
		require_once $dir . 'includes/class-kraken-io-background-process.php';
		require_once $dir . 'includes/class-kraken-io-summary.php';

		require_once $dir . 'includes/supported-plugins/class-kraken-io-support-wp-retina-2x.php';
		require_once $dir . 'includes/supported-plugins/class-kraken-io-support-nextgen-gallery.php';
		require_once $dir . 'includes/supported-plugins/class-kraken-io-support-wp-offload-media.php';
	}

	/**
	 * Init when WordPress Initialises.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function init() {

		// Before init action.
		do_action( 'kraken_io_before_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		$this->settings = new Kraken_IO_Settings();
		$this->options  = array_merge( $this->settings->get_default_options(), $this->options );

		$this->api          = new Kraken_IO_API();
		$this->stats        = new Kraken_IO_Stats();
		$this->optimization = new Kraken_IO_Optimization();
		$this->bg_process   = new Kraken_IO_Background_Process_Pool();
		$this->summary      = new Kraken_IO_Summary();

		// Init action.
		do_action( 'kraken_io_init' );
	}

	/**
	 * Mime types the Kraken.io engine can optimize, keyed by the extension(s)
	 * WordPress should accept for each.
	 *
	 * Beyond WordPress's default image types this covers SVG, AVIF, HEIC/HEIF
	 * and PDF — all accepted by the Kraken API (see the engine whitelist:
	 * png, jpeg, gif, svg, pdf, webp, avif, heic, heif).
	 *
	 * @since  2.7
	 * @access public
	 * @return array
	 */
	public function get_supported_mime_types() {
		// NB: SVG is intentionally NOT here. SVG is XML that can carry inline
		// scripts/event-handlers, so allowing raw SVG uploads is a stored-XSS
		// vector. Re-enabling it safely requires a markup sanitizer + a gated,
		// high-trust opt-in; until then we do not whitelist it for upload.
		return [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
			'avif'         => 'image/avif',
			'heic'         => 'image/heic',
			'heif'         => 'image/heif',
			'pdf'          => 'application/pdf',
		];
	}

	/**
	 * Allow Kraken-supported upload types WordPress does not permit by default
	 * (AVIF and HEIC/HEIF on older cores). Existing entries are preserved.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $mimes Allowed mime types.
	 * @return array
	 */
	public function allow_supported_mimes( $mimes ) {
		foreach ( $this->get_supported_mime_types() as $ext => $mime ) {
			if ( ! isset( $mimes[ $ext ] ) ) {
				$mimes[ $ext ] = $mime;
			}
		}
		return $mimes;
	}

	/**
	 * Let WordPress's real-mime check pass for the extra types we allow.
	 *
	 * Core's finfo validation can reject HEIC/AVIF on installs whose libmagic
	 * doesn't know those formats yet (it returns the file as unidentified). We
	 * restore the ext/type for our formats ONLY when the real bytes do not
	 * contradict the extension — never from the filename alone, so a polyglot
	 * (e.g. HTML content with an .avif name) cannot be smuggled past core's
	 * content/extension-mismatch guard.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array  $data     ext/type/proper_filename result.
	 * @param  string $file     Full path to the file.
	 * @param  string $filename The name of the file.
	 * @param  array  $mimes    Allowed mime types.
	 * @return array
	 */
	public function fix_supported_filetypes( $data, $file, $filename, $mimes ) {
		if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
			return $data;
		}

		$check = wp_check_filetype( $filename, $this->get_supported_mime_types() );

		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return $data;
		}

		// Inspect the actual bytes. Accept the extension's type only if finfo
		// agrees, or genuinely cannot identify the file (newer formats unknown to
		// old libmagic come back as octet-stream / empty). Reject anything finfo
		// recognises as a different, active type (text/html, image/svg+xml, …).
		$real_mime = '';

		if ( is_readable( $file ) && function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );

			if ( $finfo ) {
				$real_mime = (string) finfo_file( $finfo, $file );
				finfo_close( $finfo );
			}
		}

		$ambiguous = [ '', 'application/octet-stream', 'application/x-empty' ];

		if ( $real_mime === $check['type'] || in_array( $real_mime, $ambiguous, true ) ) {
			$data['ext']  = $check['ext'];
			$data['type'] = $check['type'];
		}

		return $data;
	}

	/**
	 * Whether an attachment is a type Kraken.io can optimize.
	 *
	 * Broader than wp_attachment_is_image(): also covers SVG, PDF and HEIC/HEIF,
	 * which WordPress does not classify as images but the Kraken API optimizes.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id Attachment ID.
	 * @return bool
	 */
	public function is_supported_attachment( $id ) {
		// Keep WordPress's stricter, file-validated check for real images
		// (jpeg/png/gif/webp, and avif on WP 6.5+). This does not loosen the
		// existing image gate — it only extends it.
		if ( wp_attachment_is_image( $id ) ) {
			return true;
		}

		// Add the Kraken-supported types WordPress does not classify as images:
		// PDF, HEIC/HEIF, and AVIF on cores older than 6.5.
		$mime = get_post_mime_type( $id );

		if ( ! $mime ) {
			return false;
		}

		$extra = [ 'image/heic', 'image/heif', 'image/avif', 'application/pdf' ];

		return in_array( $mime, $extra, true );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function enqueue_scripts( $hook ) {

		$assets_url     = $this->get_plugin_url() . 'assets/';
		$assets_path    = $this->get_plugin_path() . 'assets/';
		$plugin_version = $this->get_version();

		// Version assets by file mtime so a rebuilt bundle busts the browser
		// cache immediately; fall back to the plugin version if the file is
		// missing for any reason.
		$css_file = $assets_path . 'dist/kraken.css';
		$js_file  = $assets_path . 'dist/kraken.js';
		$css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : $plugin_version;
		$js_ver   = file_exists( $js_file ) ? filemtime( $js_file ) : $plugin_version;

		wp_enqueue_style( 'kraken', $assets_url . 'dist/kraken.css', [], $css_ver );
		wp_enqueue_script( 'kraken', $assets_url . 'dist/kraken.js', [ 'jquery' ], $js_ver, true );

		$args = [
			'ajax_url'         => admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'            => wp_create_nonce( 'kraken-io-nonce' ),
			'tile'             => $assets_url . 'images/kraken-tile.png',
			'bulk_async_limit' => $this->options['bulk_async_limit'],
			// Whether a fresh upload will actually be processed, so the Upload
			// New Media page knows whether to show an optimizing indicator (and
			// which verb). convert_format is the global "convert uploads to".
			'auto_optimize'    => ! empty( $this->options['auto_optimize'] ) ? '1' : '',
			'convert_format'   => isset( $this->options['convert_format'] ) ? $this->options['convert_format'] : '',
			'texts'            => [
				'bulk_async_limit'   => $this->options['bulk_async_limit'],
				'reset_image'        => esc_html__( 'Are you sure you want to remove Kraken metadata for this image?', 'kraken-io' ),
				'reset_all_images'   => esc_html__( 'This will immediately remove all Kraken metadata associated with your images. Are you sure you want to do this?', 'kraken-io' ),
				'error_reset'        => esc_html__( 'Something went wrong. Please reload the page and try again.', 'kraken-io' ),
				'optimizing'         => esc_html__( 'Optimizing…', 'kraken-io' ),
				'converting'         => esc_html__( 'Converting…', 'kraken-io' ),
				'optimized'          => esc_html__( 'Optimized', 'kraken-io' ),
				/* translators: %s number of images */
				'images_to_optimize' => esc_html__( '%s images will be optimized.', 'kraken-io' ),
				/* translators: %1$s optimized %2$s total */
				'images_optimized'   => esc_html__( '%1$s / %2$s images have been optimized.', 'kraken-io' ),
				'support_copied'     => esc_html__( 'Copied!', 'kraken-io' ),
			],
		];

		// Only expose what the JS actually uses. NEVER localize the full options
		// array — it holds api_key/api_secret, which would leak the secret into
		// the page source. All Kraken API calls happen server-side.
		wp_localize_script( 'kraken', 'kraken_options', $args );

		// Settings-screen-only enhancement: the sticky save bar + unsaved-changes
		// guard. Hand-written (no build step), loaded only where the form lives.
		if ( 'settings_page_wp-krakenio' === $hook ) {
			$settings_css = $assets_path . 'admin/settings.css';
			$settings_js  = $assets_path . 'admin/settings.js';

			wp_enqueue_style(
				'kraken-settings',
				$assets_url . 'admin/settings.css',
				[ 'dashicons' ],
				file_exists( $settings_css ) ? filemtime( $settings_css ) : $plugin_version
			);
			wp_enqueue_script(
				'kraken-settings',
				$assets_url . 'admin/settings.js',
				[],
				file_exists( $settings_js ) ? filemtime( $settings_js ) : $plugin_version,
				true
			);
		}

		// RTL locales (Arabic, etc.): mirror the directional bits of the plugin's
		// own panels. Loaded last so it overrides both kraken.css and settings.css.
		if ( is_rtl() ) {
			$rtl_css = $assets_path . 'admin/rtl.css';
			wp_enqueue_style(
				'kraken-rtl',
				$assets_url . 'admin/rtl.css',
				[ 'kraken' ],
				file_exists( $rtl_css ) ? filemtime( $rtl_css ) : $plugin_version
			);
		}
	}

	/**
	 * Load Localisation files.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'kraken-io', false, plugin_basename( dirname( KRAKEN_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_plugin_url() {
		return plugin_dir_url( KRAKEN_PLUGIN_FILE );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_plugin_path() {
		return plugin_dir_path( KRAKEN_PLUGIN_FILE );
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_version() {
		$plugin_data = get_file_data( KRAKEN_PLUGIN_FILE, [ 'Version' => 'Version' ], 'plugin' );
		return $plugin_data['Version'];
	}

	/**
	 * Retrieve the options.
	 *
	 * @since  2.7
	 * @access public
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Set the options.
	 *
	 * @since  2.7
	 * @access public
	 * @param  arry $options
	 * @return void
	 */
	public function set_options( $options ) {
		$this->options = $options;
		update_option( '_kraken_options', $options );
	}

	/**
	 * Reinit the api if the api settings have changed.
	 *
	 * @since  2.7
	 * @access public
	 * @param  arry $old_options
	 * @param  arry $options
	 * @return void
	 */
	public function maybe_reinit_api( $old_options, $options ) {
		if ( $old_options['api_key'] !== $options['api_key'] || $old_options['api_secret'] !== $options['api_secret'] ) {
			$this->api = new Kraken_IO_API();
			do_action( 'kraken_io_credentials_changed' );
		}
	}

	/**
	 * Flush the rewrite ruels if settings have changed.
	 *
	 * @since  2.7
	 * @access public
	 * @param  arry $old_options
	 * @param  arry $options
	 * @return void
	 */
	public function maybe_flush_rewrite_rules( $old_options, $options ) {
		// display_webp is a deprecated/legacy option that may be absent from the
		// defaults; guard the lookups so its removal can't raise a notice.
		$old = isset( $old_options['display_webp'] ) ? $old_options['display_webp'] : false;
		$new = isset( $options['display_webp'] ) ? $options['display_webp'] : false;

		if ( $old !== $new ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Get image sizes to optimize.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function get_image_sizes_to_optimize() {
		$sizes       = [];
		$image_sizes = get_intermediate_image_sizes();
		$default_off = $this->get_default_off_image_sizes();

		foreach ( $image_sizes as $size ) {
			if ( isset( $this->options[ 'include_size_' . $size ] ) ) {
				// An explicit saved choice always wins — never override what the
				// user has actually configured.
				if ( ! empty( $this->options[ 'include_size_' . $size ] ) ) {
					$sizes[] = $size;
				}
			} elseif ( ! in_array( $size, $default_off, true ) ) {
				// No saved choice yet: optimize by default, except the large retina
				// sizes most themes never serve (kept off to save quota).
				$sizes[] = $size;
			}
		}

		return $sizes;
	}

	/**
	 * Intermediate sizes left unticked by default.
	 *
	 * WordPress's "big image" retina sizes (1536×1536 and 2048×2048) are large
	 * and most themes never put them in srcset, so optimizing them by default
	 * mostly burns Kraken quota for no page-speed gain. Sites that do serve them
	 * can simply tick them in the settings, or override this filter. Existing
	 * saved choices are always respected — this only affects the default.
	 *
	 * @since  3.0.0
	 * @access public
	 * @return array
	 */
	public function get_default_off_image_sizes() {
		return apply_filters( 'kraken_io_default_off_image_sizes', [ '1536x1536', '2048x2048' ] );
	}

	/**
	 * Load a template part with passing arguments.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string  $slug   The slug name for the generic template.
	 * @param  array   $args   Pass args with the template load.
	 */
	public function get_template( $slug, $args = [] ) {
		$template = $this->get_plugin_path() . '/templates/' . $slug . '.php';
		include $template;
	}

	/**
	 * Format bytes.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int  $size
	 * @param  int  $precision
	 * @param  string  $value
	 */
	public function format_bytes( $size, $precision = 2 ) {
		if ( $size <= 0 ) {
			return '0 bytes';
		}
		$base     = log( $size, 1024 );
		$suffixes = [ ' bytes', 'KB', 'MB', 'GB', 'TB' ];
		return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[ floor( $base ) ];
	}

	/**
	 * Convert KB to bytes.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string  $str
	 */
	public function kb_string_to_bytes( $str ) {
		$temp = floatVal( $str );
		$rv   = false;
		if ( 0 === $temp ) {
			$rv = '0 bytes';
		} else {
			$rv = $this->format_bytes( ceil( floatval( $str ) * 1024 ) );
		}
		return $rv;
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string $name Constant name.
	 * @param mixed $value Constant value.
	 */
	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}
