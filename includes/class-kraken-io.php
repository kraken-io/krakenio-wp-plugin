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
	private $options = array();

	/**
	 * The instance of the api class.
	 *
	 * @var    Kraken_IO_API
	 * @since  2.7
	 * @access protected
	 */
	public $api = null;

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

		$this->file     = KRAKEN_PLUGIN_FILE;
		$this->basename = plugin_basename( $this->file );
		$this->options  = get_option( '_kraken_options' );

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
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Include required files.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function includes() {

		$dir = $this->get_plugin_path();

		require_once $dir . 'includes/class-kraken-io-api.php';
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

		$this->api = new Kraken_IO_API( $this->options['api_key'], $this->options['api_secret'] );

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

		if ( KRAKEN_DEV_MODE === true ) {
			wp_enqueue_style( 'kraken', $assets_url . 'css/kraken.css', array(), $plugin_version );
			wp_enqueue_script( 'kraken', $assets_url . 'js/kraken.js', array( 'jquery' ), $plugin_version, true );
		} else {
			wp_enqueue_style( 'kraken', $assets_url . 'css/kraken.min.css', array(), $plugin_version );
			wp_enqueue_script( 'kraken', $assets_url . 'js/kraken.min.js', array( 'jquery' ), $plugin_version, true );
		}

		wp_localize_script( 'kraken', 'kraken_options', $this->options );
	}

	/**
	 * Load Localisation files.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'kraken-io', false, plugin_basename( dirname( $this->file ) ) . '/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_plugin_url() {
		return plugin_dir_url( $this->file );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_plugin_path() {
		return plugin_dir_path( $this->file );
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_version() {
		$plugin_data = get_file_data( $this->file, array( 'Version' => 'Version' ), 'plugin' );
		return $plugin_data['Version'];
	}

	/**
	 * Retrieve the options.
	 *
	 * @since  2.7
	 * @access public
	 * @return string
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get size information for all currently-registered image sizes.
	 *
	 * @since  2.7
	 * @access public
	 * @return array $sizes
	 */
	public function get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes       = array();
		$image_sizes = get_intermediate_image_sizes();

		foreach ( $image_sizes as $size ) {
			if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$sizes[ $size ]['width']  = get_option( "{$size}_size_w" );
				$sizes[ $size ]['height'] = get_option( "{$size}_size_h" );
				$sizes[ $size ]['crop']   = (bool) get_option( "{$size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$sizes[ $size ] = array(
					'width'  => $_wp_additional_image_sizes[ $size ]['width'],
					'height' => $_wp_additional_image_sizes[ $size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
				);
			}
		}

		return $sizes;
	}

}
