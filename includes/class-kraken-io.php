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
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' ); }

	/**
	 * A dummy magic method to prevent class from being unserialized.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' ); }

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
	}

	/**
	 * Include required files.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function includes() {

		$dir = $this->get_plugin_path();

		require_once $dir . 'includes/vendor/autoload.php';
		require_once $dir . 'includes/class-kraken-io-api.php';
		require_once $dir . 'includes/class-kraken-io-settings.php';
		require_once $dir . 'includes/class-kraken-io-stats.php';
		require_once $dir . 'includes/class-kraken-io-optimization.php';
		require_once $dir . 'includes/class-kraken-io-ajax.php';
		require_once $dir . 'includes/class-kraken-io-background-process.php';
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
		$this->bg_process   = new Kraken_IO_Background_Process();

		// Init action.
		do_action( 'kraken_io_init' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function enqueue_scripts( $hook ) {

		$assets_url     = $this->get_plugin_url() . 'assets/';
		$plugin_version = $this->get_version();

		wp_enqueue_style( 'kraken', $assets_url . 'dist/kraken.css', [], $plugin_version );
		wp_enqueue_script( 'kraken', $assets_url . 'dist/kraken.js', [ 'jquery' ], $plugin_version, true );

		$args = [
			'ajax_url' => admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'    => wp_create_nonce( 'kraken-io-nonce' ),
			'texts'    => [
				'reset_image'      => esc_html__( 'Are you sure you want to remove Kraken metadata for this image?', 'kraken-io' ),
				'reset_all_images' => esc_html__( 'This will immediately remove all Kraken metadata associated with your images. Are you sure you want to do this?', 'kraken-io' ),
				'error_reset'      => esc_html__( 'Something went wrong. Please reload the page and try again.', 'kraken-io' ),
			],
		];

		wp_localize_script( 'kraken', 'kraken_options', wp_parse_args( $args, $this->options ) );
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
		if ( $old_options['display_webp'] !== $options['display_webp'] ) {
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

		foreach ( $image_sizes as $size ) {
			if ( ! isset( $this->options[ 'include_size_' . $size ] ) || ! empty( $this->options[ 'include_size_' . $size ] ) ) {
				$sizes[] = $size;
			}
		}

		return $sizes;
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
		$base     = log( $size, 1024 );
		$suffixes = [ ' bytes', 'KB', 'MB', 'GB', 'TB' ];
		return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[ floor( $base ) ];
	}

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

}
