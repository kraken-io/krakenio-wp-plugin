<?php
/**
 * Kraken IO Summary.
 *
 * Renders the Kraken.io status/settings summary in two places: a panel at the
 * top of the Media Library and a Dashboard widget. Shows the connection state,
 * account usage and the currently active settings, with quick edit links.
 *
 * @package Kraken_IO/Classes
 * @since   2.7
 */

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Summary {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
		add_action( 'admin_notices', [ $this, 'render_media_library_panel' ] );

		// Account usage changes every time an image is optimized, so drop the
		// cached status as soon as that happens (in addition to the short TTL).
		add_action( 'kraken_io_image_optimized', [ $this, 'clear_account_status_cache' ] );
		add_action( 'kraken_io_credentials_changed', [ $this, 'clear_account_status_cache' ] );
	}

	/**
	 * Register the Dashboard widget.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function register_dashboard_widget() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'kraken_io_summary',
			esc_html__( 'Kraken.io Image Optimizer', 'kraken-io' ),
			[ $this, 'render_dashboard_widget' ]
		);
	}

	/**
	 * Render the Dashboard widget.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function render_dashboard_widget() {
		kraken_io()->get_template(
			'summary-panel',
			[
				'data'    => $this->get_data(),
				'context' => 'dashboard',
			]
		);
	}

	/**
	 * Render the panel at the top of the Media Library ("upload"), Add New Media
	 * ("media") and Plugins ("plugins") screens — so Kraken.io is visibly active
	 * wherever it's relevant, and the "deactivate competing optimizers" actions
	 * appear right where users manage their plugins.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function render_media_library_panel() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, [ 'upload', 'media', 'plugins' ], true ) || ! current_user_can( 'upload_files' ) ) {
			return;
		}

		kraken_io()->get_template(
			'summary-panel',
			[
				'data'    => $this->get_data(),
				'context' => 'media',
			]
		);
	}

	/**
	 * Build the data consumed by the panel template.
	 *
	 * @since  2.7
	 * @access public
	 * @return array
	 */
	public function get_data() {
		$assets = kraken_io()->get_plugin_url() . 'assets/images/';

		$data = [
			'logo'         => $assets . 'kraken-logotype.png',
			'tile'         => $assets . 'kraken-tile.png',
			'has_auth'     => kraken_io()->api->has_auth(),
			'is_valid'     => false,
			'status'       => null,
			'general'      => [],
			'advanced'     => [],
			'formats'      => [ 'JPEG', 'PNG', 'GIF', 'WebP', 'AVIF', 'PDF', 'HEIC', 'HEIF' ],
			'settings_url' => admin_url( 'options-general.php?page=wp-krakenio&tab=general' ),
			'advanced_url' => admin_url( 'options-general.php?page=wp-krakenio&tab=advanced' ),
			'stats_url'    => admin_url( 'options-general.php?page=wp-krakenio&tab=stats' ),
		];

		$data['links']          = $this->get_account_links();
		$data['plan_name']      = '';
		$data['is_free']        = false;
		$data['overdue']        = false;
		$data['quota_exceeded'] = false;

		if ( $data['has_auth'] ) {
			$status           = $this->get_account_status();
			$data['status']   = $status;
			$data['is_valid'] = ( ! empty( $status['success'] ) && ! empty( $status['active'] ) );

			if ( $data['is_valid'] ) {
				$data['plan_name'] = isset( $status['plan_name'] ) ? $status['plan_name'] : '';
				$data['is_free']   = ( 'Free' === $data['plan_name'] );
				$data['overdue']   = ! empty( $status['overdue'] );

				$quota_total            = isset( $status['quota_total'] ) ? (float) $status['quota_total'] : 0;
				$quota_used             = isset( $status['quota_used'] ) ? (float) $status['quota_used'] : 0;
				$data['quota_exceeded'] = ( $quota_total > 0 && $quota_used >= $quota_total );
			}
		}

		// Images that can still be bulk-optimized, so the panel can offer a
		// one-click "Bulk optimize" action. Only computed when the account is
		// connected and valid (nothing can be optimized otherwise).
		$data['unoptimized'] = [
			'total' => 0,
			'ids'   => [],
			'pages' => 1,
		];

		if ( $data['is_valid'] ) {
			$unoptimized         = kraken_io()->optimization->get_unoptimized_images();
			$data['unoptimized'] = [
				'total' => (int) $unoptimized['total'],
				'ids'   => array_map( 'intval', (array) $unoptimized['ids'] ),
				'pages' => (int) $unoptimized['pages'],
			];
		}

		// Competing image optimizers that are active alongside Kraken.io.
		$data['conflicts'] = $this->get_conflicting_plugins();

		$settings         = $this->get_active_settings();
		$data['general']  = $settings['general'];
		$data['advanced'] = $settings['advanced'];

		// Global quick-controls — the same options shown on the settings page,
		// kept live-synced with the widget. Only admins (manage_options) may
		// change a global setting from the widget.
		$options                = kraken_io()->get_options();
		$data['convert_format'] = isset( $options['convert_format'] ) ? (string) $options['convert_format'] : '';
		$data['auto_optimize']  = ! empty( $options['auto_optimize'] );
		$data['can_manage']     = current_user_can( 'manage_options' );
		$data['convert_formats'] = [
			''     => __( 'No conversion', 'kraken-io' ),
			'jpeg' => __( 'JPEG', 'kraken-io' ),
			'png'  => __( 'PNG', 'kraken-io' ),
			'gif'  => __( 'GIF', 'kraken-io' ),
			'webp' => __( 'WebP', 'kraken-io' ),
			'avif' => __( 'AVIF', 'kraken-io' ),
		];

		return $data;
	}

	/**
	 * Outbound kraken.io links used by the account panels. Filterable so they
	 * can be corrected without touching templates.
	 *
	 * @since  3.0.0
	 * @access public
	 * @return array
	 */
	public function get_account_links() {
		return apply_filters(
			'kraken_io_account_links',
			[
				'signup'      => 'https://kraken.io/signup',
				'pricing'     => 'https://kraken.io/pricing',
				'login'       => 'https://kraken.io/login',
				'account'     => 'https://kraken.io/account',
				'billing'     => 'https://kraken.io/account/billing',
				'credentials' => 'https://kraken.io/account/api-credentials/all',
			]
		);
	}

	/**
	 * Detect other active image-optimizer plugins that overlap with Kraken.io.
	 * Running two optimizers on the same uploads wastes quota and can
	 * double-compress, so we offer a one-click deactivation for each.
	 *
	 * @since  3.0.0
	 * @access public
	 * @return array List of [ name, file, deactivate_url ].
	 */
	public function get_conflicting_plugins() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$known = [
			'wp-smushit/wp-smush.php'                                    => 'Smush',
			'wp-smush-pro/wp-smush.php'                                  => 'Smush Pro',
			'shortpixel-image-optimiser/wp-shortpixel.php'              => 'ShortPixel Image Optimizer',
			'ewww-image-optimizer/ewww-image-optimizer.php'             => 'EWWW Image Optimizer',
			'ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php' => 'EWWW Image Optimizer Cloud',
			'imagify/imagify.php'                                       => 'Imagify',
			'optimole-wp/optimole-wp.php'                               => 'Optimole',
			'resmushit-image-optimizer/resmushit.php'                   => 'reSmush.it',
			'tiny-compress-images/tiny-compress-images.php'             => 'Compress JPEG & PNG images (TinyPNG)',
			'robin-image-optimizer/robin-image-optimizer.php'           => 'Robin image optimizer',
			'wp-optimize-by-xtraffic/wp-optimize-by-xtraffic.php'       => 'WP Optimize by xTraffic',
		];

		/**
		 * Filter the list of plugins considered to conflict with Kraken.io.
		 *
		 * @param array $known Map of plugin basename => display name.
		 */
		$known = apply_filters( 'kraken_io_conflicting_plugins', $known );

		$conflicts = [];

		foreach ( $known as $file => $name ) {
			if ( ! is_plugin_active( $file ) ) {
				continue;
			}

			$conflicts[] = [
				'name'           => $name,
				'file'           => $file,
				'deactivate_url' => wp_nonce_url(
					self_admin_url( 'plugins.php?action=deactivate&plugin=' . rawurlencode( $file ) ),
					'deactivate-plugin_' . $file
				),
			];
		}

		return $conflicts;
	}

	/**
	 * Account status (plan/quota), cached so we don't hit the API on every
	 * admin page load. Keyed by the credentials so it refreshes when they change.
	 *
	 * @since  2.7
	 * @access public
	 * @return array
	 */
	public function get_account_status() {
		$options = kraken_io()->get_options();
		$key     = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$secret  = isset( $options['api_secret'] ) ? $options['api_secret'] : '';

		$transient = 'kraken_io_account_status_' . md5( $key . '|' . $secret );
		$cached    = get_transient( $transient );

		if ( false !== $cached ) {
			return $cached;
		}

		$status = kraken_io()->api->status();

		// Short, filterable TTL: usage/quota is volatile and payment (overdue)
		// state can change externally, so keep it fresh. It is also cleared on
		// each optimization and on credential changes (see the constructor).
		$ttl = (int) apply_filters( 'kraken_io_account_status_ttl', MINUTE_IN_SECONDS );
		set_transient( $transient, $status, $ttl );

		return $status;
	}

	/**
	 * Drop the cached account status so the next read fetches fresh usage.
	 *
	 * @since  3.0.0
	 * @access public
	 */
	public function clear_account_status_cache() {
		$options = kraken_io()->get_options();
		$key     = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$secret  = isset( $options['api_secret'] ) ? $options['api_secret'] : '';

		delete_transient( 'kraken_io_account_status_' . md5( $key . '|' . $secret ) );
	}

	/**
	 * Collect the active/selected settings, grouped into General and Advanced.
	 *
	 * @since  2.7
	 * @access public
	 * @return array
	 */
	public function get_active_settings() {
		$o        = kraken_io()->get_options();
		$general  = [];
		$advanced = [];

		$get = function ( $key ) use ( $o ) {
			return isset( $o[ $key ] ) ? $o[ $key ] : null;
		};

		// --- General ---
		$general[] = [
			'label' => __( 'Optimization mode', 'kraken-io' ),
			'value' => ( 'lossless' === $get( 'api_lossy' ) ) ? __( 'Lossless', 'kraken-io' ) : __( 'Intelligent lossy', 'kraken-io' ),
		];

		if ( $get( 'auto_optimize' ) ) {
			$general[] = [
				'label' => __( 'Automatically optimize uploads', 'kraken-io' ),
				'value' => null,
			];
		}
		if ( $get( 'optimize_main_image' ) ) {
			$general[] = [
				'label' => __( 'Optimize main image', 'kraken-io' ),
				'value' => null,
			];
		}

		$rw = (int) $get( 'resize_width' );
		$rh = (int) $get( 'resize_height' );
		if ( $rw > 0 || $rh > 0 ) {
			$general[] = [
				'label' => __( 'Resize main image', 'kraken-io' ),
				'value' => sprintf( '%d × %d px', $rw, $rh ),
			];
		}

		$jq = (int) $get( 'jpeg_quality' );
		if ( $jq > 0 ) {
			$general[] = [
				'label' => __( 'JPEG quality', 'kraken-io' ),
				'value' => (string) $jq,
			];
		}

		if ( $get( 'chroma' ) ) {
			$general[] = [
				'label' => __( 'Chroma subsampling', 'kraken-io' ),
				'value' => $get( 'chroma' ),
			];
		}

		// --- Advanced (only the ones that are turned on) ---
		if ( $get( 'auto_orient' ) ) {
			$advanced[] = [
				'label' => __( 'Automatically orient images', 'kraken-io' ),
				'value' => null,
			];
		}
		if ( $get( 'background_process' ) ) {
			$advanced[] = [
				'label' => __( 'Background processing', 'kraken-io' ),
				'value' => null,
			];
		}
		// create_webp / display_webp are deprecated in favour of the Convert
		// feature, so they are no longer surfaced as active settings.
		if ( $get( 'show_reset' ) ) {
			$advanced[] = [
				'label' => __( 'Per-image reset button', 'kraken-io' ),
				'value' => null,
			];
		}

		$preserve = [];
		$meta_map = [
			'preserve_meta_date'        => __( 'Date', 'kraken-io' ),
			'preserve_meta_copyright'   => __( 'Copyright', 'kraken-io' ),
			'preserve_meta_geotag'      => __( 'Geotag', 'kraken-io' ),
			'preserve_meta_orientation' => __( 'Orientation', 'kraken-io' ),
			'preserve_meta_profile'     => __( 'Profile', 'kraken-io' ),
		];
		foreach ( $meta_map as $meta_key => $meta_label ) {
			if ( $get( $meta_key ) ) {
				$preserve[] = $meta_label;
			}
		}
		if ( ! empty( $preserve ) ) {
			$advanced[] = [
				'label' => __( 'Preserve EXIF metadata', 'kraken-io' ),
				'value' => implode( ', ', $preserve ),
			];
		}

		$sizes = 0;
		foreach ( $o as $option_key => $option_value ) {
			if ( 0 === strpos( $option_key, 'include_size_' ) && $option_value ) {
				$sizes++;
			}
		}
		if ( $sizes > 0 ) {
			$advanced[] = [
				'label' => __( 'Image sizes optimized', 'kraken-io' ),
				/* translators: %d number of image sizes */
				'value' => sprintf( _n( '%d size', '%d sizes', $sizes, 'kraken-io' ), $sizes ),
			];
		}

		return [
			'general'  => $general,
			'advanced' => $advanced,
		];
	}
}
