<?php
/**
* Kraken IO Settings.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Settings {

	/**
	 * Settings fields.
	 *
	 * @var    array
	 * @access protected
	 */
	protected $settings = [];

	/**
	 * Settings errors.
	 *
	 * @var    array
	 * @access protected
	 */
	protected $settings_errors = [];

	/**
	 * Settings sucess.
	 *
	 * @var    array
	 * @access protected
	 */
	protected $settings_sucess = [];

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
	public function __construct() {
		add_filter( 'plugin_action_links_' . kraken_io()->basename, [ $this, 'plugin_action_links' ] );
		add_filter( 'bulk_actions-upload', [ $this, 'register_bulk_optimize_option' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'handle_bulk_optimize' ], 10, 3 );
		add_filter( 'admin_footer', [ $this, 'footer_bulk_modal' ] );
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		$this->register_settings();
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $links Plugin Action links
	 * @return array $links Plugin Action links
	 */
	public function plugin_action_links( $links ) {
		$links['settings'] = '<a href="' . esc_url( admin_url( 'options-general.php?page=wp-krakenio' ) ) . '" aria-label="' . esc_attr__( 'Kraken.io Settings', 'kraken-io' ) . '">' . esc_attr__( 'Settings', 'kraken-io' ) . '</a>';
		return $links;
	}

	/**
	 * Add optimize bulk option.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $actions Bulk actions
	 * @return array $actions Bulk links
	 */
	public function register_bulk_optimize_option( $actions ) {
		$actions['kraken_bulk'] = esc_html__( "Krak 'em all", 'kraken-io' );
		return $actions;
	}

	/**
	 * Add optimize bulk option.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $actions Redirect url
	 * @param  string $actions Redirect url
	 * @param  array $ids Post ids
	 * @return string $redirect Redirect url
	 */
	public function handle_bulk_optimize( $redirect, $action, $ids ) {

		if ( 'kraken_bulk' === $action ) {
			$redirect = add_query_arg( 'kraken_bulk', implode( ',', $ids ), $redirect );
		}

		return $redirect;
	}

	/**
	 * Add bulk optimizer template to admin footer.
	 *
	 * @since  2.7
	 * @access public
	 * @return void
	 */
	public function footer_bulk_modal() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['kraken_bulk'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids = explode( ',', $_GET['kraken_bulk'] );

		kraken_io()->get_template(
			'bulk-optimizer',
			[
				'ids'   => $ids,
				'type'  => 'modal',
				'total' => count( $ids ),
				'pages' => 1,
			]
		);
	}

	/**
	 * Add options page under "Settings".
	 *
	 * @since  2.7
	 * @access public
	 */
	public function add_options_page() {
		add_options_page(
			esc_html__( 'Kraken.io Settings', 'kraken-io' ),
			esc_html__( 'Kraken.io', 'kraken-io' ),
			'manage_options',
			'wp-krakenio',
			[ $this, 'create_options_page' ]
		);
	}

	/**
	 * Options page callback.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function create_options_page() {

		$active_tab = 'general';

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['tab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$active_tab = $_GET['tab'];
		}

		$tabs = [
			'general'  => [
				'title'       => __( 'General Settings', 'kraken-io' ),
				'description' => __( '<a href="http://kraken.io/account" target="_blank">Kraken.io</a> API Settings.', 'kraken-io' ),
			],
			'advanced' => [
				'title'       => __( 'Advanced Settings', 'kraken-io' ),
				'description' => __( 'We recommend that you leave these settings at their default values.', 'kraken-io' ),
			],
			'tools'    => [
				'title'       => __( 'Tools', 'kraken-io' ),
				'description' => '',
			],
			'stats'    => [
				'title'       => __( 'Stats', 'kraken-io' ),
				'description' => '',
			],
		];

		$this->options = kraken_io()->get_options();
		$this->save_options( $active_tab );

		?>
		<div class="kraken wraps">
			<h1><?php esc_html_e( 'Kraken.io Settings', 'kraken-io' ); ?></h1>

			<?php $this->settings_notices(); ?>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $id => $tab ) {
					$active_class = $active_tab === $id ? ' nav-tab-active' : false;
					$link         = admin_url( 'options-general.php?page=wp-krakenio&tab=' . $id );
					echo '<a href="' . esc_url( $link ) . '" class="nav-tab' . esc_attr( $active_class ) . '">' . esc_html( $tab['title'] ) . '</a>';
				}
				?>
			</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=wp-krakenio&tab=' . $active_tab ) ); ?>">
				<?php
					wp_nonce_field( 'kraken_io_settings', 'kraken_io_settings_nonce' );
					$this->do_settings_sections( $tabs, $active_tab );
				?>

				<?php if ( 'general' === $active_tab || 'advanced' === $active_tab ) : ?>
					<p class="submit">
						<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'kraken-io' ); ?>">
					</p>
					<?php
				elseif ( 'stats' === $active_tab ) :
					$status = kraken_io()->api->status();
					kraken_io()->get_template( 'stats', [ 'stats' => $status ] );
				else :
					$unoptimized_images = kraken_io()->optimization->get_unoptimized_images();
					kraken_io()->get_template( 'bulk-optimizer', wp_parse_args( $unoptimized_images, [ 'type' => 'tool' ] ) );
				endif;
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get value for field.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Settings
	 * @return mixed $value
	 */
	public function get_field_value( $settings ) {

		if ( ! isset( $settings['id'] ) ) {
			return false;
		}

		$id   = $settings['id'];
		$type = $settings['type'];

		if ( isset( $this->options[ $id ] ) ) {
			return $this->options[ $id ];
		}

		if ( 'multi_text' === $type || 'multi_checkbox' === $type ) {
			$values = [];
			foreach ( $settings['options'] as $option => $title ) {
				if ( isset( $this->options[ $option ] ) ) {
					$values[ $option ] = $this->options[ $option ];
				} elseif ( isset( $settings['default'][ $option ] ) ) {
					$values[ $option ] = $settings['default'][ $option ];
				}
			}
			return $values;
		}

		return isset( $settings['default'] ) ? $settings['default'] : false;
	}

	/**
	 * Show Settings Notices
	 *
	 * @since  2.7
	 * @access public
	 * @return void
	 */
	public function settings_notices() {
		if ( $this->settings_errors ) {
			echo '<div class="notice notice-success is-dismissible">';
			foreach ( $this->settings_errors as $error ) {
				echo '<p><strong>' . esc_html( $error ) . '</strong></p>';
			}
			echo '</div>';
		}

		if ( $this->settings_sucess ) {
			echo '<div class="notice notice-success is-dismissible">';
			foreach ( $this->settings_sucess as $notice ) {
				echo '<p><strong>' . esc_html( $notice ) . '</strong></p>';
			}
			echo '</div>';
		}
	}

	/**
	 * Save Settings
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $active Active tab
	 * @return void
	 */
	public function save_options( $active ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $_POST ) {
			return false;
		}

		if ( ! isset( $this->settings[ $active ] ) ) {
			return false;
		}

		$settings = $this->settings[ $active ];

		if ( ! wp_verify_nonce( $_POST['kraken_io_settings_nonce'], 'kraken_io_settings' ) ) {
			$this->settings_errors[] = __( 'Please refresh the page and try again.', 'kraken-io' );
			return false;
		}

		if ( ! isset( $_POST['kraken_options'] ) ) {
			$this->settings_errors[] = __( 'There are no Kraken options.', 'kraken-io' );
			return false;
		}

		$options = $_POST['kraken_options'];
		$options = $this->sanitize_options( $options, $settings );
		$options = $this->validate_options( $options, $this->options, $settings );
		$options = array_merge( $this->options, $options );
		$options = array_merge( $this->get_default_options(), $options );

		$old_options   = $this->options;
		$this->options = $options;

		kraken_io()->set_options( $options );
		kraken_io()->maybe_reinit_api( $old_options, $options );

		$this->settings_sucess[] = __( 'Settings Saved', 'kraken-io' );
	}

	/**
	 * Sanitize options.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $options Values.
	 * @param  array $settings Settings
	 * @return array $options Sanitized options.
	 */
	public function sanitize_options( $options, $settings ) {
		$sanitized_options = [];

		foreach ( $settings as $key => $setting ) {

			$id    = isset( $setting['id'] ) ? $setting['id'] : 'empty';
			$value = isset( $options[ $id ] ) ? $options[ $id ] : false;
			$type  = $setting['type'];

			if ( isset( $setting['id'] ) ) {
				if ( isset( $setting['sanitize_callback'] ) ) {
					if ( 'multi_text' === $type || 'multi_checkbox' === $type ) {
						foreach ( $setting['options'] as $option => $title ) {
							$val                          = isset( $options[ $option ] ) ? $options[ $option ] : false;
							$sanitized_options[ $option ] = call_user_func( $setting['sanitize_callback'], $val );
						}
					} else {
						$sanitized_options[ $setting['id'] ] = call_user_func( $setting['sanitize_callback'], $value );
					}
				} else {
					$sanitized_options[ $setting['id'] ] = $value;
				}
			}
		}

		return $sanitized_options;
	}

	/**
	 * Get default options.
	 *
	 * @since  2.7
	 * @access public
	 * @return array $options options.
	 */
	public function get_default_options() {
		$settings = $this->settings;
		$options  = [];

		foreach ( $settings as $section ) {
			foreach ( $section as $setting ) {
				if ( isset( $setting['id'] ) ) {
					$type = $setting['type'];
					if ( 'multi_text' === $type || 'multi_checkbox' === $type ) {
						foreach ( $setting['options'] as $option => $title ) {
							$default            = isset( $setting['default'][ $option ] ) ? $setting['default'][ $option ] : '';
							$options[ $option ] = $default;
						}
					} else {
						$options[ $setting['id'] ] = $setting['default'];
					}
				}
			}
		}

		return $options;
	}

	/**
	 * Validate options.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $options Values.
	 * @param  array $old_options Old values.
	 * @param  array $settings Settings
	 * @return array $options Validated options.
	 */
	public function validate_options( $options, $old_options, $settings ) {
		$validated_options = [];

		foreach ( $settings as $key => $setting ) {

			$id    = isset( $setting['id'] ) ? $setting['id'] : 'empty';
			$value = isset( $options[ $id ] ) ? $options[ $id ] : false;

			if ( isset( $setting['validate_callback'] ) ) {
				$is_valid = call_user_func( $setting['validate_callback'], $value, $setting['options'] );
				if ( $is_valid ) {
					$validated_options[ $setting['id'] ] = $value;
				} else {
					if ( isset( $old_options[ $setting['id'] ] ) ) {
						$old_value = $old_options[ $setting['id'] ];
					} else {
						$old_options = $this->get_default_options();
						$old_value   = $old_options[ $setting['id'] ];
					}
					$validated_options[ $setting['id'] ] = $old_value;
				}
			}
		}

		return array_merge( $options, $validated_options );
	}

	/**
	 * Prints out all settings sections added to a settings page.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $tabs Tabs
	 * @param  string $active Active tab
	 * @return void
	 */
	public function do_settings_sections( $tabs, $active ) {

		if ( ! isset( $this->settings[ $active ] ) ) {
			return;
		}

		$settings    = $this->settings[ $active ];
		$description = $tabs[ $active ]['description'];

		echo '<p>' . wp_kses_post( $description ) . '</p>';

		echo '<table class="form-table kraken-form-table" role="presentation">';

		foreach ( $settings as $setting ) {
			$value = $this->get_field_value( $setting );

			echo '<tr>';

			echo '<th scope="row">' . esc_html( $setting['title'] ) . '</th>';

			echo '<td>';

			call_user_func( [ $this, 'do_settings_field_' . $setting['type'] ], $setting, $value );

			if ( isset( $setting['description'] ) ) {
				echo '<ul class="descriptions">';
				foreach ( $setting['description'] as $description ) {
					echo '<li><p class="description">' . wp_kses_post( $description ) . '</p></li>';
				}
				echo '</ul>';
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}

	/**
	 * Prints out setting field for type text.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_text( $settings, $value ) {
		echo '<input type="text" class="regular-text" id="' . esc_attr( $settings['id'] ) . '" name="kraken_options[' . esc_attr( $settings['id'] ) . ']" value="' . esc_attr( $value ) . '">';
	}

	/**
	 * Prints out setting field for type checkbox.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_checkbox( $settings, $value ) {
		echo '<input type="checkbox" class="tog" id="' . esc_attr( $settings['id'] ) . '" name="kraken_options[' . esc_attr( $settings['id'] ) . ']" value="1" ' . checked( $value, '1', false ) . '>';
		echo '<label for="' . esc_attr( $settings['id'] ) . '">' . esc_html( $settings['label'] ) . '</label>';
	}

	/**
	 * Prints out setting field for type radio.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_radio( $settings, $value ) {
		foreach ( $settings['options'] as $option => $title ) {
			$id = $settings['id'] . '_' . $option;
			echo '<p>';
				echo '<input type="radio" class="tog" id="' . esc_attr( $id ) . '" name="kraken_options[' . esc_attr( $settings['id'] ) . ']" value="' . esc_attr( $option ) . '" ' . checked( $value, $option, false ) . '>';
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</label>';
			echo '</p>';
		}
	}

	/**
	 * Prints out setting field for type select.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_select( $settings, $value ) {
		echo '<select name="kraken_options[' . esc_attr( $settings['id'] ) . ']" id="' . esc_attr( $settings['id'] ) . '">';
		foreach ( $settings['options'] as $option => $title ) {
			echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $title ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Prints out setting field for type multi_checkbox.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_multi_checkbox( $settings, $value ) {
		foreach ( $settings['options'] as $option => $title ) {
			$id  = $settings['id'] . '_' . $option;
			$val = isset( $value[ $option ] ) ? $value[ $option ] : 0;
			echo '<p>';
				echo '<input type="checkbox" class="tog" id="' . esc_attr( $id ) . '" name="kraken_options[' . esc_attr( $option ) . ']" value="1" ' . checked( $val, '1', false ) . '>';
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</label>';
			echo '</p>';
		}
	}

	/**
	 * Prints out setting field for type multi_text.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_multi_text( $settings, $value ) {
		foreach ( $settings['options'] as $option => $title ) {
			echo '<p>';
			echo '<input type="number" class="small-text" id="' . esc_attr( $option ) . '" name="kraken_options[' . esc_attr( $option ) . ']" value="' . esc_attr( $value[ $option ] ) . '">';
			echo ' <label for="' . esc_attr( $option ) . '">' . esc_html( $title ) . '</label>';
			echo '</p>';
		}
	}

	/**
	 * Prints out setting field for type api_status.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $settings Field settings
	 * @param  mixed $value Field value
	 * @return void
	 */
	public function do_settings_field_api_status( $settings, $value ) {
		$status = kraken_io()->api->status();

		if ( $status['success'] && isset( $status['active'] ) && true === $status['active'] ) {
			echo '<p><span class="kraken-status-success dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Your credentials are valid.', 'kraken-io' ) . '</p>';
		} else {
			echo '<p><span class="kraken-status-error  dashicons dashicons-warning"></span> ' . esc_html__( 'There is a problem with your credentials.', 'kraken-io' ) . '</p>';
		}
	}

	/**
	 * Get prefixed image sizes.
	 *
	 * @since  2.7
	 * @access public
	 * @param  bool $prefix Prefix sizes
	 * @return array $sizes Image sizes
	 */
	public function get_prefixed_image_sizes( $type = 'all' ) {

		$sizes             = [];
		$image_sizes       = get_intermediate_image_sizes();
		$sizes_to_optimize = kraken_io()->get_image_sizes_to_optimize();

		foreach ( $image_sizes as $size ) {
			if ( 'all' === $type ) {
				$sizes[ 'include_size_' . $size ] = $size;
			} else {
				if ( in_array( $size, $sizes_to_optimize, true ) ) {
					$sizes[ 'include_size_' . $size ] = '1';
				}
			}
		}

		return $sizes;
	}

	/**
	 * Register settings fields.
	 *
	 * @since  2.7
	 * @access public
	 * @return void
	 */
	public function register_settings() {
		$this->settings = [
			'general'  => [
				[
					'id'                => 'api_key',
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
					'title'             => __( 'API Key', 'kraken-io' ),
					'default'           => '',
				],
				[
					'id'                => 'api_secret',
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
					'title'             => __( 'API Secret', 'kraken-io' ),
					'default'           => '',
				],
				[
					'type'    => 'api_status',
					'title'   => __( 'API Status', 'kraken-io' ),
					'default' => '',
				],
				[
					'id'                => 'api_lossy',
					'type'              => 'radio',
					'validate_callback' => [ $this, 'validate_select_radio' ],
					'default'           => 'lossy',
					'options'           => [
						'lossy'    => __( 'Intelligent lossy', 'kraken-io' ),
						'lossless' => __( 'Lossless', 'kraken-io' ),
					],
					'title'             => __( 'Optimization mode', 'kraken-io' ),
					'description'       => [
						__( 'The Intelligent Lossy mode will yield the greatest savings without perceivable reducing the quality of your images, and so we recommend this setting to users.', 'kraken-io' ),
						__( 'The Lossless mode will result in an unchanged image, however, will yield reduced savings as the image will not be recompressed.', 'kraken-io' ),
					],
				],
				[
					'id'                => 'auto_optimize',
					'type'              => 'checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'default'           => true,
					'title'             => __( 'Optimize uploads', 'kraken-io' ),
					'label'             => __( 'Automatically optimize uploads', 'kraken-io' ),
					'description'       => [
						__( 'Enabled by default. This setting causes images uploaded through the Media Uploader to be optimized on-the-fly.', 'kraken-io' ),
						__( 'If you do not wish to do this, or wish to optimize images later, disable this setting by unchecking the box.', 'kraken-io' ),
					],
				],
				[
					'id'                => 'optimize_main_image',
					'type'              => 'checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'default'           => true,
					'title'             => __( 'Optimize images', 'kraken-io' ),
					'label'             => __( 'Optimize main image', 'kraken-io' ),
					'description'       => [
						__( 'Enabled by default. This option causes the image uploaded by the user to get optimized, as well as all sizes generated by WordPress.', 'kraken-io' ),
						__( 'Disabling this option results in faster uploading, since the main image is not sent to our system for optimization.', 'kraken-io' ),
						__( 'Disable this option if you never use the "main" image upload in your posts, or speed of image uploading is an issue.', 'kraken-io' ),
					],
				],
				[
					'id'                => 'resize',
					'type'              => 'multi_text',
					'sanitize_callback' => 'abs',
					'options'           => [
						'resize_width'  => __( 'Max width (px)', 'kraken-io' ),
						'resize_height' => __( 'Max height (px)', 'kraken-io' ),
					],
					'default'           => [
						'resize_width'  => '0',
						'resize_height' => '0',
					],
					'title'             => __( 'Resize main image', 'kraken-io' ),
					'description'       => [
						__( 'You can restrict the maximum dimensions of image uploads by width and/or height.', 'kraken-io' ),
						__(
							'It is especially useful if you wish to prevent unnecessarily large photos with extremely high resolutions from being uploaded, for example, photos shot with a recent-model iPhone. Note: you can restrict the dimenions by width, height, or both. A value of zero disables.',
							'kraken-io'
						),
					],
				],
				[
					'id'                => 'jpeg_quality',
					'type'              => 'select',
					'validate_callback' => [ $this, 'validate_select_radio' ],
					'options'           => array_replace(
						[
							0 => __( 'Intelligent lossy (recommended)', 'kraken-io' ),
						],
						array_combine( range( 99, 25 ), range( 99, 25 ) )
					),
					'default'           => '0',
					'title'             => __( 'JPEG quality setting', 'kraken-io' ),
					'description'       => [
						__( 'Advanced users can force the quality of JPEG images to a discrete "q" value between 25 and 100 using this setting.', 'kraken-io' ),
						__( 'For example, forcing the quality to 60 or 70 might yield greater savings, but the resulting quality might be affected, depending on the image.', 'kraken-io' ),
						__( 'We therefore recommend keeping the <strong>Intelligent Lossy</strong> setting, which will not allow a resulting image of unacceptable quality.', 'kraken-io' ),
						__( 'This setting will be ignored when using the <strong>lossless</strong> optimization mode.', 'kraken-io' ),
					],
				],
				[
					'id'                => 'chroma',
					'type'              => 'radio',
					'validate_callback' => [ $this, 'validate_select_radio' ],
					'default'           => '4:2:0',
					'options'           => [
						'4:2:0' => __( '4:2:0 (default)', 'kraken-io' ),
						'4:2:2' => __( '4:2:2', 'kraken-io' ),
						'4:4:4' => __( '4:4:4 (no subsampling)', 'kraken-io' ),
					],
					'title'             => __( 'Chroma subsampling scheme', 'kraken-io' ),
					'description'       => [
						__( 'Advanced users can also set the resolution at which colour is encoded for JPEG images.', 'kraken-io' ),
						__( 'In short, the default setting of 4:2:0 is suitable for most images, and will result in the lowest possible optimized file size.', 'kraken-io' ),
						__( 'Images containing high contrast text or bright red areas on flat backgrounds might benefit from disabling chroma subsampling (by setting it to 4:4:4). ', 'kraken-io' ),
						__( 'More information can be found in our <a href="https://kraken.io/docs/chroma-subsampling" target="_blank">documentation</a>.', 'kraken-io' ),
					],
				],
			],
			'advanced' => [
				[
					'id'                => 'include_size',
					'type'              => 'multi_checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'default'           => $this->get_prefixed_image_sizes( 'to-optimize' ),
					'options'           => $this->get_prefixed_image_sizes(),
					'title'             => __( 'Image sizes to Krak', 'kraken-io' ),
				],
				[
					'id'                => 'preserve_exif_metadata',
					'type'              => 'multi_checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'options'           => [
						'preserve_meta_date'        => __( 'Date', 'kraken-io' ),
						'preserve_meta_copyright'   => __( 'Copyright', 'kraken-io' ),
						'preserve_meta_geotag'      => __( 'Geotag', 'kraken-io' ),
						'preserve_meta_orientation' => __( 'Orientation', 'kraken-io' ),
						'preserve_meta_profile'     => __( 'Profile Profile', 'kraken-io' ),
					],
					'default'           => [
						'preserve_meta_date'        => '',
						'preserve_meta_copyright'   => '',
						'preserve_meta_geotag'      => '',
						'preserve_meta_orientation' => '',
						'preserve_meta_profile'     => '',
					],
					'title'             => __( 'Preserve EXIF Metadata', 'kraken-io' ),
				],
				[
					'id'                => 'auto_orient',
					'type'              => 'checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'default'           => true,
					'title'             => __( 'Image orientation', 'kraken-io' ),
					'label'             => __( 'Automatically orient images', 'kraken-io' ),
					'description'       => [
						__( 'This setting will rotate the JPEG image according to its Orientation EXIF metadata such that it will always be correctly displayed in Web Browsers.', 'kraken-io' ),
						__( 'Enable this setting if many of your image uploads come from smart phones or digital cameras which set the orientation based on how they are held at the time of shooting.', 'kraken-io' ),
					],
				],
				[
					'id'                => 'show_reset',
					'type'              => 'checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'default'           => false,
					'title'             => __( 'Metadata reset per image', 'kraken-io' ),
					'label'             => __( 'Show reset button', 'kraken-io' ),
					'description'       => [
						__( 'Checking this option will add a Reset button in the "Show Details" popup in the Kraken Stats column for each optimized image.', 'kraken-io' ),
						__( 'Resetting an image will remove the Kraken.io metadata associated with it, effectively making your blog forget that it had been optimized in the first place, allowing further optimization in some cases.', 'kraken-io' ),
						__( 'If an image has been optimized using the lossless setting, lossless optimization will not yield any greater savings. If in doubt, please contact support@kraken.io', 'kraken-io' ),
					],
				],
				[
					'id'                => 'background_process',
					'type'              => 'checkbox',
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
					'default'           => true,
					'title'             => __( 'Background process', 'kraken-io' ),
					'label'             => __( 'Process image optimization in background', 'kraken-io' ),
					'description'       => [
						__( 'This setting will optimize images in background when using the option "Automatically optimize uploads" resulting in faster image uploads.', 'kraken-io' ),
					],
				],
				[
					'id'                => 'bulk_async_limit',
					'type'              => 'select',
					'validate_callback' => [ $this, 'validate_select_radio' ],
					'options'           => array_combine( range( 1, 10 ), range( 1, 10 ) ),
					'default'           => 4,
					'title'             => __( 'Bulk concurrency', 'kraken-io' ),
					'description'       => [
						__( 'This settings defines how many images can be processed at the same time using the bulk optimizer. The recommended (and default) value is 4.', 'kraken-io' ),
						__( 'For blogs on very small hosting plans, or with reduced connectivity, a lower number might be necessary to avoid hitting request limits.', 'kraken-io' ),
					],
				],
			],
		];
	}

	/**
	 * Checkbox sanitization callback.
	 *
	 * @since  2.7
	 * @access public
	 * @param  bool $checked Whether the checkbox is checked.
	 * @return bool Whether the checkbox is checked.
	 */
	public function sanitize_checkbox( $checked ) {
		return $checked ? '1' : '0';
	}

	/**
	 * Sanitize number.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int     $number     Number to sanitize.
	 * @return int     $number     Sanitized number otherwise, the setting default.
	 */
	public function sanitize_number( $number ) {
		$number = intval( $number );
		return $number > 0 ? $number : 0;
	}

	/**
	 * Validate select and radio options callback.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $selected Value selected.
	 * @param  array $options Posibble values
	 * @return bool Whether the option is valid.
	 */
	public function validate_select_radio( $selected, $options ) {
		return array_key_exists( $selected, $options ) ? true : false;
	}

}
