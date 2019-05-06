<?php
/*
	Copyright 2015  Karim Salman  (email : ksalman@kraken.io)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * Plugin Name: Kraken Image Optimizer
 * Plugin URI: http://wordpress.org/plugins/kraken-image-optimizer/
 * Description: This plugin allows you to optimize your WordPress images through the Kraken API, the world's most advanced image optimization solution.
 * Author: Karim Salman
 * Version: 2.6.3
 * Stable Tag: 2.6.3
 * Author URI: https://kraken.io
 * License GPL2
 */


if ( !class_exists( 'Wp_Kraken' ) ) {

	define( 'KRAKEN_DEV_MODE', false );
	class Wp_Kraken {

		private $id;

		private $kraken_settings = array();

		private $thumbs_data = array();

		private $optimization_type = 'lossy';

		public static $kraken_plugin_version = '2.6.3';

		function __construct() {
			$plugin_dir_path = dirname( __FILE__ );
			require_once( $plugin_dir_path . '/lib/Kraken.php' );
			$this->kraken_settings = get_option( '_kraken_options' );
			$this->optimization_type = $this->kraken_settings['api_lossy'];
			add_action( 'admin_enqueue_scripts', array( &$this, 'my_enqueue' ) );
			add_action( 'wp_ajax_kraken_reset', array( &$this, 'kraken_media_library_reset' ) );
			add_action( 'wp_ajax_kraken_optimize', array( &$this, 'kraken_optimize' ) );
			add_action( 'wp_ajax_kraken_request', array( &$this, 'kraken_media_library_ajax_callback' ) );
			add_action( 'wp_ajax_kraken_reset_all', array( &$this, 'kraken_media_library_reset_all' ) );
			add_action( 'manage_media_custom_column', array( &$this, 'fill_media_columns' ), 10, 2 );
			add_filter( 'manage_media_columns', array( &$this, 'add_media_columns') );
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( &$this, 'add_settings_link' ) );

			if ( ( !empty( $this->kraken_settings ) && !empty( $this->kraken_settings['auto_optimize'] ) ) || !isset( $this->kraken_settings['auto_optimize'] ) ) {
				add_action( 'add_attachment', array( &$this, 'kraken_media_uploader_callback' ) );			
				add_filter( 'wp_generate_attachment_metadata', array( &$this, 'optimize_thumbnails' ) );
			}

			// in case settings were not resaved after update
			if ( !isset( $this->kraken_settings["optimize_main_image"] ) ) {
				$this->kraken_settings["optimize_main_image"] = 1;
			}

			// in case settings were not resaved after update
			if ( !isset( $this->kraken_settings["chroma"] ) ) {
				$this->kraken_settings["chroma"] = '4:2:0';
			}

			add_action( 'admin_menu', array( &$this, 'kraken_menu' ) );	
		}

		function preg_array_key_exists( $pattern, $array ) {
		    $keys = array_keys( $array );    
		    return (int) preg_grep( $pattern,$keys );
		}

		function isApiActive() {
			$settings = $this->kraken_settings;
			$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$api_secret = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';
			if ( empty( $api_key ) || empty( $api_secret) ) {
				return false;
			}
			return true;			
		}

		function kraken_menu() {
			add_options_page( 'Kraken Image Optimizer Settings', 'Kraken.io', 'manage_options', 'wp-krakenio', array( &$this, 'kraken_settings_page' ) );
		}

		function add_settings_link ( $links ) {
			$mylinks = array(
				'<a href="' . admin_url( 'options-general.php?page=wp-krakenio' ) . '">Settings</a>',
			);
			return array_merge( $links, $mylinks );
		}

		function kraken_settings_page() {

			if ( !empty( $_POST ) ) {
				$options = $_POST['_kraken_options'];
				$result = $this->validate_options( $options );
				update_option( '_kraken_options', $result['valid'] );
			}

			$settings = get_option( '_kraken_options' );
			$lossy = isset( $settings['api_lossy'] ) ? $settings['api_lossy'] : 'lossy';
			$auto_optimize = isset( $settings['auto_optimize'] ) ? $settings['auto_optimize'] : 1;
			$optimize_main_image = isset( $settings['optimize_main_image'] ) ? $settings['optimize_main_image'] : 1;
			$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$api_secret = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';
			$show_reset = isset( $settings['show_reset'] ) ? $settings['show_reset'] : 0;
			$bulk_async_limit = isset( $settings['bulk_async_limit'] ) ? $settings['bulk_async_limit'] : 4;
			$preserve_meta_date = isset( $settings['preserve_meta_date'] ) ? $settings['preserve_meta_date'] : 0;
			$preserve_meta_copyright = isset( $settings['preserve_meta_copyright'] ) ? $settings['preserve_meta_copyright'] : 0;
			$preserve_meta_geotag = isset( $settings['preserve_meta_geotag'] ) ? $settings['preserve_meta_geotag'] : 0;
			$preserve_meta_orientation = isset( $settings['preserve_meta_orientation'] ) ? $settings['preserve_meta_orientation'] : 0;
			$preserve_meta_profile = isset( $settings['preserve_meta_profile'] ) ? $settings['preserve_meta_profile'] : 0;
			$auto_orient = isset( $settings['auto_orient'] ) ? $settings['auto_orient'] : 1;
			$resize_width = isset( $settings['resize_width'] ) ? $settings['resize_width'] : 0;
			$resize_height = isset( $settings['resize_height'] ) ? $settings['resize_height'] : 0;
			$jpeg_quality = isset( $settings['jpeg_quality'] ) ? $settings['jpeg_quality'] : 0;
			$chroma_subsampling = isset( $settings['chroma'] ) ? $settings['chroma'] : '4:2:0';
		
			$sizes = array_keys($this->get_image_sizes());
			foreach ($sizes as $size) {
				$valid['include_size_' . $size] = isset( $settings['include_size_' . $size]) ? $settings['include_size_' . $size] : 1;
			}

			$status = $this->get_api_status( $api_key, $api_secret );

			$icon_url = admin_url() . 'images/';
			if ( $status !== false && isset( $status['active'] ) && $status['active'] === true ) {
				$icon_url .= 'yes.png';
				$status_html = '<p class="apiStatus">Your credentials are valid <span class="apiValid" style="background:url(' . "'$icon_url') no-repeat 0 0" . '"></span></p>';
			} else {
				$icon_url .= 'no.png';
				$status_html = '<p class="apiStatus">There is a problem with your credentials <span class="apiInvalid" style="background:url(' . "'$icon_url') no-repeat 0 0" . '"></span></p>';
			}

			?>	<h1 class="kraken-admin-section-title">Kraken.io Settings</h1>
					<?php if ( isset( $result['error'] ) ) { ?>
						<div class="kraken error settings-error">
						<?php foreach( $result['error'] as $error ) { ?>
							<p><?php echo $error; ?></p>
						<?php } ?>
						</div>
					<?php } else if ( isset( $result['success'] ) ) { ?>
						<div class="kraken updated settings-error">
							<p>Settings saved.</p>
						</div>
					<?php } ?>

					<?php if ( !function_exists( 'curl_init' ) ) { ?>
						<p class="curl-warning"><strong>Warning: </strong>CURL is not available. Please install CURL before using this plugin</p>
					<?php } ?>

					<form id="krakenSettings" method="post">
						<a href="http://kraken.io/account" target="_blank" title="Log in to your Kraken.io account">Kraken.io</a> API settings
						<table class="form-table">
						    <tbody>
						        <tr>
						            <th scope="row">API Key:</th>
						            <td>
						                <input id="kraken_api_key" name="_kraken_options[api_key]" type="text" value="<?php echo esc_attr( $api_key ); ?>" size="50">
						            </td>
						        </tr>
						        <tr>
						            <th scope="row">API Secret:</th>
						            <td>
						                <input id="kraken_api_secret" name="_kraken_options[api_secret]" type="text" value="<?php echo esc_attr( $api_secret ); ?>" size="50">
						            </td>
						        </tr>
						        <tr>
						            <th scope="row">API status:</th>
						            <td>
						                <?php echo $status_html ?>
						            </td>
						        </tr>
						        <tr class="with-tip">
						            <th scope="row">Optimization mode:</th>
						            <td>
						                <input type="radio" id="kraken_lossy" name="_kraken_options[api_lossy]" value="lossy" <?php checked( 'lossy', $lossy, true ); ?>/>
						                <label for="kraken_lossy">Intelligent Lossy</label>
						                <input style="margin-left:10px;" type="radio" id="kraken_lossless" name="_kraken_options[api_lossy]" value="lossless" <?php checked( 'lossless', $lossy, true ) ?>/>
						                <label for="kraken_lossless">Lossless</label>
						            </td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			The <strong>Intelligent Lossy</strong> mode will yield the greatest savings without perceivable reducing the quality of your images, and so we recommend this setting to users.<br />
						        			The <strong>Lossless</strong> mode will result in an unchanged image, however, will yield reduced savings as the image will not be recompressed.
						        		</div>
						        	</td>
						        </tr>
						        <tr class="with-tip">
						            <th scope="row">Automatically optimize uploads:</th>
						            <td>
						                <input type="checkbox" id="auto_optimize" name="_kraken_options[auto_optimize]" value="1" <?php checked( 1, $auto_optimize, true ); ?>/>
						            </td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			Enabled by default. This setting causes images uploaded through the Media Uploader to be optimized on-the-fly.<br />
						        			If you do not wish to do this, or wish to optimize images later, disable this setting by unchecking the box.
						        		</div>
						        	</td>
						        </tr>
						        <tr class="with-tip">
						            <th scope="row">Optimize main image:</th>
						            <td>
						                <input type="checkbox" id="optimize_main_image" name="_kraken_options[optimize_main_image]" value="1" <?php checked( 1, $optimize_main_image, true ); ?>/>
						            </td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			Enabled by default. This option causes the image uploaded by the user to get optimized, as well as all sizes generated by WordPress.<br />
						        			Disabling this option results in faster uploading, since the main image is not sent to our system for optimization.<br />
						        			Disable this option if you never use the "main" image upload in your posts, or speed of image uploading is an issue.
						        		</div>
						        	</td>
						        </tr>
						        <tr class="with-tip">
						        	<th scope="row">Resize main image:</th>
						        	<td>
						        		Max Width (px):&nbsp;&nbsp;<input type="text" id="kraken_maximum_width" name="_kraken_options[resize_width]" value="<?php echo esc_attr( $resize_width ); ?>" style="width:50px;" />&nbsp;&nbsp;&nbsp;Max Height (px):&nbsp;<input type="text" id="kraken_maximum_height" name="_kraken_options[resize_height]" value="<?php echo esc_attr( $resize_height ); ?>" style="width:50px;" />
						        	</td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			You can restrict the maximum dimensions of image uploads by width and/or height.<br /> 
						        			It is especially useful if you wish to prevent unnecessarily large photos with extremely high resolutions from being uploaded, for example, <br />
						        			photos shot with a recent-model iPhone. Note: you can restrict the dimenions by width, height, or both. A value of zero disables.
						        		</div>
						        	</td>
						        </tr>
						        <tr class="with-tip">
						        	<th scope="row">JPEG quality setting:</th>
						        	<td>
										<select name="_kraken_options[jpeg_quality]">
											<?php $i = 0 ?>
											<?php foreach ( range(100, 25) as $number ) { ?>
												<?php if ( $i === 0 ) { ?>
													<?php echo '<option value="0">Intelligent lossy (recommended)</option>'; ?>
												<?php } ?>
												<?php if ($i > 0) { ?>
													<option value="<?php echo $number ?>" <?php selected( $jpeg_quality, $number, true); ?>>
													<?php echo $number; ?>
												<?php } ?>
													</option>
												<?php $i++ ?>
											<?php } ?>
										</select>
						        	</td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			Advanced users can force the quality of JPEG images to a discrete "q" value between 25 and 100 using this setting <br />
						        			For example, forcing the quality to 60 or 70 might yield greater savings, but the resulting quality might be affected, depending on the image. <br />
						        			We therefore recommend keeping the <strong>Intelligent Lossy</strong> setting, which will not allow a resulting image of unacceptable quality.<br />
						        			This setting will be ignored when using the <strong>lossless</strong> optimization mode.
						        		</div>
						        	</td>
						        </tr>
						        <tr class="with-tip">
						            <th scope="row">Chroma subsampling scheme:</th>
						            <td>
						                <input type="radio" id="kraken_chroma_420" name="_kraken_options[chroma]" value="4:2:0" <?php checked( '4:2:0', $chroma_subsampling, true ); ?>/>
						                <label for="kraken_chroma_420">4:2:0 (default)</label>
						                <input style="margin-left:10px;" type="radio" id="kraken_chroma_422" name="_kraken_options[chroma]" value="4:2:2" <?php checked( '4:2:2', $chroma_subsampling, true ) ?>/>
						                <label for="kraken_chroma_422">4:2:2</label>
						                <input style="margin-left:10px;" type="radio" id="kraken_chroma_444" name="_kraken_options[chroma]" value="4:4:4" <?php checked( '4:4:4', $chroma_subsampling, true ) ?>/>
						                <label for="kraken_chroma_444">4:4:4 (no subsampling)</label>						             
						            </td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			Advanced users can also set the resolution at which colour is encoded for JPEG images. In short, the default setting of <strong>4:2:0</strong> is suitable for most images,<br />
						        			and will result in the lowest possible optimized file size. Images containing high contrast text or bright red areas on flat backgrounds might benefit from disabling chroma subsampling<br />
						        			(by setting it to <strong>4:4:4</strong>). More information can be found in our <a href="https://kraken.io/docs/chroma-subsampling" target="_blank">documentation</a>.
						        		</div>
						        	</td>
						        </tr>						        					      
						        <tr class="no-border">
						        	<td class="krakenAdvancedSettings"><h3><span class="kraken-advanced-settings-label">Advanced Settings</span></h3></td>
						        </tr>
						        <tr class="kraken-advanced-settings">
						        	<td colspan="2" class="krakenAdvancedSettingsDescription"><small>We recommend that you leave these settings at their default values</td>
						        </tr>
						        <tr class="kraken-advanced-settings">
						            <th scope="row">Image Sizes to Krak:</th>
									<td>
						            	<?php $size_count = count($sizes); ?>
						            	<?php $i = 0; ?>
						            	<?php foreach($sizes as $size) { ?>
						            	<?php $size_checked = isset( $valid['include_size_' . $size] ) ? $valid['include_size_' . $size] : 1; ?>
						                <label for="<?php echo "kraken_size_$size" ?>"><input type="checkbox" id="kraken_size_<?php echo $size ?>" name="_kraken_options[include_size_<?php echo $size ?>]" value="1" <?php checked( 1, $size_checked, true ); ?>/>&nbsp;<?php echo $size ?></label>&nbsp;&nbsp;&nbsp;&nbsp;
						            	<?php $i++ ?>
						            	<?php if ($i % 3 == 0) { ?>
						            		<br />
						            	<?php } ?>
     							        <?php } ?>
						            </td>
						        </tr>						        
						        <tr class="kraken-advanced-settings">
						            <th scope="row">Preserve EXIF Metadata:</th>
						            <td>
						                <label for="preserve_meta_date"><input type="checkbox" id="preserve_meta_date" name="_kraken_options[preserve_meta_date]" value="1" <?php checked( 1, $preserve_meta_date, true ); ?>/>&nbsp;Date</label>&nbsp;&nbsp;&nbsp;&nbsp;
						                <label for="preserve_meta_copyright"><input type="checkbox" id="preserve_meta_copyright" name="_kraken_options[preserve_meta_copyright]" value="1" <?php checked( 1, $preserve_meta_copyright, true ); ?>/>&nbsp;Copyright</label>&nbsp;&nbsp;&nbsp;&nbsp;
						                <label for="preserve_meta_geotag"><input type="checkbox" id="preserve_meta_geotag" name="_kraken_options[preserve_meta_geotag]" value="1" <?php checked( 1, $preserve_meta_geotag, true ); ?>/>&nbsp;Geotag</label>&nbsp;&nbsp;&nbsp;&nbsp;
    						            <label for="preserve_meta_orientation"><input type="checkbox" id="preserve_meta_orientation" name="_kraken_options[preserve_meta_orientation]" value="1" <?php checked( 1, $preserve_meta_orientation, true ); ?>/>&nbsp;Orientation</label>&nbsp;&nbsp;&nbsp;&nbsp;
						                <label for="preserve_meta_profile"><input type="checkbox" id="preserve_meta_profile" name="_kraken_options[preserve_meta_profile]" value="1" <?php checked( 1, $preserve_meta_profile, true ); ?>/>&nbsp;Profile</label>&nbsp;&nbsp;&nbsp;&nbsp;
						            </td>
						        </tr>
						        <tr class="kraken-advanced-settings with-tip">
						            <th scope="row">Automatically Orient Images:</th>
						            <td>
						            	<input type="checkbox" id="auto_orient" name="_kraken_options[auto_orient]" value="1" <?php checked( 1, $auto_orient, true ); ?>/>
						            </td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			This setting will rotate the JPEG image according to its <strong>Orientation</strong> EXIF metadata such that it will always be correctly displayed in Web Browsers.<br />
						        			Enable this setting if many of your image uploads come from smart phones or digital cameras which set the orientation based on how they are held at the time of shooting.
						        		</div>
						        	</td>
						        </tr>
    						    <tr class="kraken-advanced-settings with-tip">
						            <th scope="row">Show metadata reset per image:</th>
						            <td>
						                <input type="checkbox" id="kraken_show_reset" name="_kraken_options[show_reset]" value="1" <?php checked( 1, $show_reset, true ); ?>/>
						                &nbsp;&nbsp;&nbsp;&nbsp;<span class="kraken-reset-all enabled">Reset All Images</span>
						            </td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			Checking this option will add a Reset button in the "Show Details" popup in the Kraken Stats column for each optimized image.<br /> 
						        			Resetting an image will remove the Kraken.io metadata associated with it, effectively making your blog forget that it had been optimized in the first place, allowing further optimization in some cases.<br /> 
						        			If an image has been optimized using the lossless setting, lossless optimization will not yield any greater savings. If in doubt, please contact support@kraken.io
						        		</div>
						        	</td>
						        </tr>
						        <tr class="kraken-advanced-settings with-tip">
						        	<th scope="row">Bulk Concurrency:</th>
						        	<td>
										<select name="_kraken_options[bulk_async_limit]">
											<?php foreach ( range(1, 10) as $number ) { ?>
												<option value="<?php echo $number ?>" <?php selected( $bulk_async_limit, $number, true); ?>>
													<?php echo $number ?>
												</option>
											<?php } ?>
										</select>
						        	</td>
						        </tr>
						        <tr class="tip">
						        	<td colspan="2">
						        		<div>
						        			This settings defines how many images can be processed at the same time using the bulk optimizer. The recommended (and default) value is 4. <br /> 
						        			For blogs on very small hosting plans, or with reduced connectivity, a lower number might be necessary to avoid hitting request limits.
						        		</div>
						        	</td>
						        </tr>
						    </tbody>
						</table>
			     <input type="submit" name="kraken_save" id="kraken_save" class="button button-primary" value="Save All"/>
			  </form>
			<?php
		}

		function validate_options( $input ) {
			$valid = array();
			$error = array();
			$valid['api_lossy'] = $input['api_lossy'];
			$valid['auto_optimize'] = isset( $input['auto_optimize'] )? 1 : 0;
			$valid['optimize_main_image'] = isset( $input['optimize_main_image'] ) ? 1 : 0;
			$valid['preserve_meta_date'] = isset( $input['preserve_meta_date'] ) ? $input['preserve_meta_date'] : 0;
			$valid['preserve_meta_copyright'] = isset( $input['preserve_meta_copyright'] ) ? $input['preserve_meta_copyright'] : 0;
			$valid['preserve_meta_geotag'] = isset( $input['preserve_meta_geotag'] ) ? $input['preserve_meta_geotag'] : 0;
			$valid['preserve_meta_orientation'] = isset( $input['preserve_meta_orientation'] ) ? $input['preserve_meta_orientation'] : 0;
			$valid['preserve_meta_profile'] = isset( $input['preserve_meta_profile'] ) ? $input['preserve_meta_profile'] : 0;
			$valid['auto_orient'] = isset( $input['auto_orient'] ) ? $input['auto_orient'] : 0;
			$valid['show_reset'] = isset( $input['show_reset'] ) ? 1 : 0;
			$valid['bulk_async_limit'] = isset( $input['bulk_async_limit'] ) ? $input['bulk_async_limit'] : 4;
			$valid['resize_width'] = isset( $input['resize_width'] ) ? (int) $input['resize_width'] : 0;
			$valid['resize_height'] = isset( $input['resize_height'] ) ? (int) $input['resize_height'] : 0;
			$valid['jpeg_quality'] = isset( $input['jpeg_quality'] ) ? (int) $input['jpeg_quality'] : 0;
			$valid['chroma'] = isset( $input['chroma'] ) ? $input['chroma'] : '4:2:0';


			$sizes = get_intermediate_image_sizes();
			foreach ($sizes as $size) {
				$valid['include_size_' . $size] = isset( $input['include_size_' . $size] ) ? 1 : 0;
			}

			if ( $valid['show_reset'] ) {
				$valid['show_reset'] = $input['show_reset'];
			}

			if ( empty( $input['api_key']) || empty( $input['api_secret'] ) ) {
				$error[] = 'API Credentials must not be left blank.';
			} else {
			
				$status = $this->get_api_status( $input['api_key'], $input['api_secret'] );

				if ( $status !== false ) {

					if ( isset($status['active']) && $status['active'] === true ) {
						if ( $status['plan_name'] === 'Developers' ) {
							$error[] = 'Developer API credentials cannot be used with this plugin.';
						} else {
							$valid['api_key'] = $input['api_key'];
							$valid['api_secret'] = $input['api_secret'];
						}
					} else {
						$error[] = 'There is a problem with your credentials. Please check them from your Kraken.io account.';
					}

				} else {
					$error[] = 'Please enter a valid Kraken.io API key and secret.';
				}			
			}

			if ( !empty( $error ) ) {
				return array( 'success' => false, 'error' => $error, 'valid' => $valid );
			} else {
				return array( 'success' => true, 'valid' => $valid );
			}
		}

		function my_enqueue( $hook ) {

			if ( $hook == 'options-media.php' || $hook == 'upload.php' || $hook == 'settings_page_wp-krakenio' ) {
				wp_enqueue_script( 'jquery' );
				if ( KRAKEN_DEV_MODE === true ) {
					wp_enqueue_script( 'async-js', plugins_url( '/js/async.js', __FILE__ ) );
					wp_enqueue_script( 'tipsy-js', plugins_url( '/js/jquery.tipsy.js', __FILE__ ), array( 'jquery' ) );
					wp_enqueue_script( 'modal-js', plugins_url( '/js/jquery.modal.min.js', __FILE__ ), array( 'jquery' ) );
					wp_enqueue_script( 'ajax-script', plugins_url( '/js/ajax.js', __FILE__ ), array( 'jquery' ) );
					wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
					wp_localize_script( 'ajax-script', 'kraken_settings', $this->kraken_settings );
					wp_enqueue_style( 'kraken_admin_style', plugins_url( 'css/admin.css', __FILE__ ) );
					wp_enqueue_style( 'tipsy-style', plugins_url( 'css/tipsy.css', __FILE__ ) );
					wp_enqueue_style( 'modal-style', plugins_url( 'css/jquery.modal.css', __FILE__ ) );
				} else {
					wp_enqueue_script( 'kraken-js', plugins_url( '/js/dist/kraken.min.js', __FILE__ ), array( 'jquery' ) );
					wp_localize_script( 'kraken-js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
					wp_localize_script( 'kraken-js', 'kraken_settings', $this->kraken_settings );
					wp_enqueue_style( 'kraken-css', plugins_url( 'css/dist/kraken.min.css', __FILE__ ) );					
				}
			}
		}

		function get_api_status( $api_key, $api_secret ) {

			if ( !empty( $api_key ) && !empty( $api_secret ) ) {
				$kraken = new Kraken( $api_key, $api_secret );
				$status = $kraken->status();
				return $status;
			}
			return false;
		}

		/**
		 *	Converts an deserialized API result array into an array
		 *	which this plugin will consume
		 */
		function get_result_arr( $result, $image_id ) {
			$rv = array();
			$rv['original_size'] = $result['original_size'];
			$rv['kraked_size'] = $result['kraked_size'];
			$rv['saved_bytes'] = $result['saved_bytes'];
			$savings_percentage = $result['saved_bytes'] / $result['original_size'] * 100;
			$rv['savings_percent'] = round( $savings_percentage, 2 ) . '%';
			$rv['type'] = $result['type'];
			if ( !empty( $result['kraked_width'] ) && !empty( $result['kraked_height'] ) ) {
				$rv['kraked_width'] = $result['kraked_width'];
				$rv['kraked_height'] = $result['kraked_height'];
			}
			$rv['success'] = $result['success'];
			$rv['meta'] = wp_get_attachment_metadata( $image_id );
			return $rv;		
		}


		/**
		 *  Handles optimizing already-uploaded images in the  Media Library
		 */
		function kraken_media_library_ajax_callback() {
			$image_id = (int) $_POST['id'];
			$type = false;

			if ( isset( $_POST['type'] ) ) {
				$type = $_POST['type'];
				$this->optimization_type = $type;
			}

			$this->id = $image_id;

			if ( wp_attachment_is_image( $image_id ) ) {

				$settings = $this->kraken_settings;

				$image_path = get_attached_file( $image_id );
				$optimize_main_image = !empty( $settings['optimize_main_image'] );
				$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
				$api_secret = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';

				$data = array();

				if ( empty( $api_key ) && empty( $api_secret ) ) {
					$data['error'] = 'There is a problem with your credentials. Please check them in the Kraken.io settings section of Media Settings, and try again.';
					update_post_meta( $image_id, '_kraken_size', $data );
					echo json_encode( array( 'error' => $data['error'] ) );
					exit;
				}

				if ( $optimize_main_image ) {

					// check if thumbs already optimized
					$thumbs_optimized = false;
					$kraked_thumbs_data = get_post_meta( $image_id, '_kraked_thumbs', true );
					
					if ( !empty ( $kraked_thumbs_data ) ) {
						$thumbs_optimized = true;
					}

					// get metadata for thumbnails
					$image_data = wp_get_attachment_metadata( $image_id );

					if ( !$thumbs_optimized ) {
						$this->optimize_thumbnails( $image_data );
					} else {

						// re-optimize thumbs if mode has changed
						$kraked_thumbs_mode = $kraked_thumbs_data[0]['type'];						
						if ( strcmp( $kraked_thumbs_mode, $this->optimization_type ) !== 0 ) {
							wp_generate_attachment_metadata( $image_id, $image_path );
							$this->optimize_thumbnails( $image_data );
						}
					}

					$resize = false;
					if ( !empty( $settings['resize_width'] ) || !empty( $settings['resize_height'] ) ) {
						$resize = true;
					}

					$api_result = $this->optimize_image( $image_path, $type, $resize );

					if ( !empty( $api_result ) && !empty( $api_result['success'] ) ) {
						$data = $this->get_result_arr( $api_result, $image_id );
						if ( $this->replace_image( $image_path, $api_result['kraked_url'] ) ) {

							if ( !empty( $data['kraked_width'] ) && !empty( $data['kraked_height'] ) ) {
								$image_data = wp_get_attachment_metadata( $image_id );
								$image_data['width'] = $data['kraked_width'];
								$image_data['height'] = $data['kraked_height'];
								wp_update_attachment_metadata( $image_id, $image_data );
							}

							// store kraked info to DB
							update_post_meta( $image_id, '_kraken_size', $data );

							// krak thumbnails, store that data too. This can be unset when there are no thumbs
							$kraked_thumbs_data = get_post_meta( $image_id, '_kraked_thumbs', true );
							if ( !empty( $kraked_thumbs_data ) ) {
								$data['thumbs_data'] = $kraked_thumbs_data;
								$data['success'] = true;
							}

							$data['html'] = $this->generate_stats_summary( $image_id );
							echo json_encode( $data );
						
						} else {
							echo json_encode( array( 'error' => 'Could not overwrite original file. Please ensure that your files are writable by plugins.' ) );
							exit;
						}	

					} else {
						// error or no optimization
						if ( file_exists( $image_path ) ) {
							update_post_meta( $image_id, '_kraken_size', $data );
						} else {
							// file not found
						}
						echo json_encode( array( 'error' => $api_result['message'], '' ) );
					}
				} else {
					// get metadata for thumbnails
					$image_data = wp_get_attachment_metadata( $image_id );
					$this->optimize_thumbnails( $image_data );

					// krak thumbnails, store that data too. This can be unset when there are no thumbs
					$kraked_thumbs_data = get_post_meta( $image_id, '_kraked_thumbs', true );

					if ( !empty( $kraked_thumbs_data ) ) {
						$data['thumbs_data'] = $kraked_thumbs_data;
						$data['success'] = true;
					}
					$data['html'] = $this->generate_stats_summary( $image_id );

					echo json_encode( $data );
				}
			}
			wp_die();
		}


		function is_successful( $response ) {}

		/**
		 *  Handles optimizing images uploaded through any of the media uploaders.
		 */
		function kraken_media_uploader_callback( $image_id ) {

			$this->id = $image_id;

			if ( empty( $this->kraken_settings['optimize_main_image'] ) ) {
				return;
			}

			$settings = $this->kraken_settings;
			$type = $settings['api_lossy'];

			if ( !$this->isApiActive() ) {
				remove_filter( 'wp_generate_attachment_metadata', array( &$this, 'optimize_thumbnails') );
				remove_action( 'add_attachment', array( &$this, 'kraken_media_uploader_callback' ) );
				return;
			}

			if ( wp_attachment_is_image( $image_id ) ) {

				$image_path = get_attached_file( $image_id );
				$image_backup_path = $image_path . '_kraken_' . md5( $image_path );
				$backup_created = false;

				if ( copy( $image_path, $image_backup_path ) ) {
					$backup_created = true;
				}

				$resize = false;
				if ( !empty( $settings['resize_width'] ) || !empty( $settings['resize_height'] ) ) {
					$resize = true;
				}

				// optimize backup image
				if ( $backup_created ) {
					$api_result = $this->optimize_image( $image_backup_path, $type, $resize );
				} else {
					$api_result = $this->optimize_image( $image_path, $type, $resize );
				}				

				$data = array();

				if ( !empty( $api_result ) && !empty( $api_result['success'] ) ) {
					$data = $this->get_result_arr( $api_result, $image_id );
					
					if ( $backup_created ) {
						$data['optimized_backup_file'] = $image_backup_path;
						if ( $data['saved_bytes'] > 0 ) {
							if ( $this->replace_image( $image_backup_path, $api_result['kraked_url'] ) ) {
							} else {
								error_log('Kraken.io: Could not replace local image with optimized image.');
							}						
						}						
					} else {
						if ( $data['saved_bytes'] > 0 ) {
							if ( $this->replace_image( $image_path, $api_result['kraked_url'] ) ) {
							} else {
								error_log('Kraken.io: Could not replace local image with optimized image.');
							}						
						}
					}
					update_post_meta( $image_id, '_kraken_size', $data );

				} else {
					// error or no optimization
					if ( file_exists( $image_path ) ) {

						$data['original_size'] = filesize( $image_path );
						$data['error'] = $api_result['message'];
						$data['type'] = $api_result['type'];
						update_post_meta( $image_id, '_kraken_size', $data );

					} else {
						// file not found
					}
				}
			}
		}

		function kraken_media_library_reset() {
			$image_id = (int) $_POST['id'];
			$image_meta = get_post_meta( $image_id, '_kraken_size', true );
			$original_size = self::formatBytes( filesize( get_attached_file( $image_id ) ) );
			delete_post_meta( $image_id, '_kraken_size' );
			delete_post_meta( $image_id, '_kraked_thumbs' );			
			echo json_encode( array( 'success' => true, 'original_size' => $original_size, 'html' => $this->optimize_button_html( $image_id ) ) );
			wp_die();
 		}

		function kraken_media_library_reset_all() {
			$result = null;
			delete_post_meta_by_key( '_kraked_thumbs' );
			delete_post_meta_by_key( '_kraken_size' );
			$result = json_encode( array( 'success' => true ) );
			echo $result;
			wp_die();
 		}


		function optimize_button_html( $id )  {
			$image_url = wp_get_attachment_url( $id );
			$filename = basename( $image_url );

$html = <<<EOD
	<div class="buttonWrap">
		<button type="button" 
				data-setting="$this->optimization_type" 
				class="kraken_req" 
				data-id="$id" 
				id="krakenid-$id" 
				data-filename="$filename" 
				data-url="<$image_url">
			Optimize This Image
		</button>
		<small class="krakenOptimizationType" style="display:none">$this->optimization_type</small>
		<span class="krakenSpinner"></span>
	</div>
EOD;

			return $html;
		}


		function show_credentials_validity() {

			$settings = $this->kraken_settings;
			$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$api_secret = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';

			$status = $this->get_api_status( $api_key, $api_secret );
			$url = admin_url() . 'images/';

			if ( $status !== false && isset( $status['active'] ) && $status['active'] === true ) {
				$url .= 'yes.png';
				echo '<p class="apiStatus">Your credentials are valid <span class="apiValid" style="background:url(' . "'$url') no-repeat 0 0" . '"></span></p>';
			} else {
				$url .= 'no.png';
				echo '<p class="apiStatus">There is a problem with your credentials <span class="apiInvalid" style="background:url(' . "'$url') no-repeat 0 0" . '"></span></p>';
			}
		}

		function show_kraken_image_optimizer() {
			echo '<a href="http://kraken.io" title="Visit Kraken.io Homepage">Kraken.io</a> API settings';
		}

		function show_api_key() {
			$settings = $this->kraken_settings;
			$value = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			?>
				<input id='kraken_api_key' name='_kraken_options[api_key]'
				 type='text' value='<?php echo esc_attr( $value ); ?>' size="50"/>
			<?php
		}

		function show_api_secret() {
			$settings = $this->kraken_settings;
			$value = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';
			?>
				<input id='kraken_api_secret' name='_kraken_options[api_secret]'
				 type='text' value='<?php echo esc_attr( $value ); ?>' size="50"/>
			<?php
		}

		function show_lossy() {
			$options = get_option( '_kraken_options' );
			$value = isset( $options['api_lossy'] ) ? $options['api_lossy'] : 'lossy';

			$html = '<input type="radio" id="kraken_lossy" name="_kraken_options[api_lossy]" value="lossy"' . checked( 'lossy', $value, false ) . '/>';
			$html .= '<label for="kraken_lossy">Lossy</label>';

			$html .= '<input style="margin-left:10px;" type="radio" id="kraken_lossless" name="_kraken_options[api_lossy]" value="lossless"' . checked( 'lossless', $value, false ) . '/>';
			$html .= '<label for="kraken_lossless">Lossless</label>';

			echo $html;
		}

		function show_auto_optimize() {
			$options = get_option( '_kraken_options' );
			$auto_optimize = isset( $options['auto_optimize'] ) ? $options['auto_optimize'] : 1;
			?>
			<input type="checkbox" id="auto_optimize" name="_kraken_options[auto_optimize]" value="1" <?php checked( 1, $auto_optimize, true ); ?>/>
			<?php
		}

		function show_reset_field() {
			$options = get_option( '_kraken_options' );
			$show_reset = isset( $options['show_reset'] ) ? $options['show_reset'] : 0;
			?>
			<input type="checkbox" id="show_reset" name="_kraken_options[show_reset]" value="1" <?php checked( 1, $show_reset, true ); ?>/>
			<span class="kraken-reset-all enabled">Reset All Images</span>
			<?php
		}

		function show_bulk_async_limit() {
			$options = get_option( '_kraken_options' );
			$bulk_limit = isset( $options['bulk_async_limit'] ) ? $options['bulk_async_limit'] : 4;
			?>
			<select name="_kraken_options[bulk_async_limit]">
				<?php foreach ( range(1, 10) as $number ) { ?>
					<option value="<?php echo $number ?>" <?php selected( $bulk_limit, $number, true); ?>>
						<?php echo $number ?>
					</option>
				<?php } ?>
			</select>
			<?php
		}

		function add_media_columns( $columns ) {
			$columns['original_size'] = 'Original Size';
			$columns['kraked_size'] = 'Kraken.io Stats';
			return $columns;
		}


		static function KBStringToBytes( $str ) {
			$temp = floatVal( $str );
			$rv = false;
			if ( 0 == $temp ) {
				$rv = '0 bytes';
			} else {
				$rv = self::formatBytes( ceil( floatval( $str) * 1024 ) );
			}
			return $rv;
		}


		static function calculate_savings( $meta ) {

			if ( isset( $meta['original_size'] ) ) {

				$saved_bytes = isset( $meta['saved_bytes'] ) ? $meta['saved_bytes'] : '';
				$savings_percentage = $meta['savings_percent'];

				// convert old data format, where applicable
				if ( stripos( $saved_bytes, 'kb' ) !== false ) {
					$saved_bytes = self::KBStringToBytes( $saved_bytes );
				} else {
					if ( !$saved_bytes ) {
						$saved_bytes = '0 bytes';
					} else {
						$saved_bytes = self::formatBytes( $saved_bytes );
					}
				}

				return array( 
					'saved_bytes' => $saved_bytes,
					'savings_percentage' => $savings_percentage 
				);
			
			} else if ( !empty( $meta ) ) {
				$thumbs_count = count( $meta );
				$total_thumb_byte_savings = 0;
				$total_thumb_size = 0;
				$thumbs_savings_percentage = '';
				$total_thumbs_savings = '';

				foreach ( $meta as $k => $v ) {
					$total_thumb_size += $v['original_size'];
					$thumb_byte_savings = $v['original_size'] - $v['kraked_size'];
					$total_thumb_byte_savings += $thumb_byte_savings;
				}

				$thumbs_savings_percentage = round( ( $total_thumb_byte_savings / $total_thumb_size * 100 ), 2 ) . '%';
				if ( $total_thumb_byte_savings ) {
					$total_thumbs_savings = self::formatBytes( $total_thumb_byte_savings );
				} else {
					$total_thumbs_savings = '0 bytes';
				}
				return array( 
					'savings_percentage' => $thumbs_savings_percentage,
					'total_savings' => $total_thumbs_savings 
				);
			}
		}

		function generate_stats_summary( $id ) {
			$image_meta = get_post_meta( $id, '_kraken_size', true );
			$thumbs_meta = get_post_meta( $id, '_kraked_thumbs', true );

			$total_original_size = 0;
			$total_kraked_size = 0;
			$total_saved_bytes = 0;
			
			$total_savings_percentage = 0;

			// crap for backward compat
			if ( isset( $image_meta['original_size'] ) ) {

				$original_size = $image_meta['original_size'];

				if ( stripos( $original_size, 'kb' ) !== false ) {
					$total_original_size = ceil( floatval( $original_size ) * 1024 );
				} else {
					$total_original_size = (int) $original_size;
				}

				if ( isset( $image_meta['saved_bytes'] ) ) {
					$saved_bytes = $image_meta['saved_bytes'];
					if ( is_string( $saved_bytes ) ) {
						$total_saved_bytes = (int) ceil( floatval( $saved_bytes ) * 1024 );
					} else {
						$total_saved_bytes = $saved_bytes;
					}
				}

				$total_kraked_size = $total_original_size - $total_saved_bytes;
			} 

			if ( !empty( $thumbs_meta ) ) {
				$thumb_saved_bytes = 0;
				$total_thumb_byte_savings = 0;
				$total_thumb_size = 0;

				foreach ( $thumbs_meta as $k => $v ) {
					$total_original_size += $v['original_size'];
					$thumb_saved_bytes = $v['original_size'] - $v['kraked_size'];
					$total_saved_bytes += $thumb_saved_bytes;
				}

			}
			$total_savings_percentage = round( ( $total_saved_bytes / $total_original_size * 100 ), 2 ) . '%';
			$summary_string = '';
			if ( !$total_saved_bytes ) {
				$summary_string = 'No savings';
			} else {
				$total_savings = self::formatBytes( $total_saved_bytes );
				$detailed_results_html = $this->results_html( $id );
				$summary_string = '<div class="kraken-result-wrap">' . "Saved $total_savings_percentage ($total_savings)";
				$summary_string .= '<br /><small class="kraken-item-details" data-id="' . $id . '" original-title="' . htmlspecialchars($detailed_results_html) .'">Show details</small></div>';
			}
			return $summary_string;
		}

		function results_html( $id ) {

			$settings = $this->kraken_settings;
			$optimize_main_image = !empty( $settings['optimize_main_image'] ); 

			// get meta data for main post and thumbs
			$image_meta = get_post_meta( $id, '_kraken_size', true );
			$thumbs_meta = get_post_meta( $id, '_kraked_thumbs', true );
			$main_image_optimized = !empty( $image_meta ) && isset( $image_meta['type'] );
			$thumbs_optimized = !empty( $thumbs_meta ) && count( $thumbs_meta ) && isset( $thumbs_meta[0]['type'] );

			$type = '';
			$kraked_size = '';
			$savings_percentage = '';

			if ( $main_image_optimized ) {
				$type = $image_meta['type'];
				$kraked_size = isset( $image_meta['kraked_size'] ) ? $image_meta['kraked_size'] : '';
				$savings_percentage = $image_meta['savings_percent'];
				$main_image_kraked_stats = self::calculate_savings( $image_meta );
			} 

			if ( $thumbs_optimized ) {
				$type = $thumbs_meta[0]['type'];
				$thumbs_kraked_stats = self::calculate_savings( $thumbs_meta );
				$thumbs_count = count( $thumbs_meta );
			}
			
			ob_start();
			?>
				<?php if ( $main_image_optimized ) { ?>
				<div class="kraken_detailed_results_wrap">
				<span class=""><strong>Main image savings:</strong></span>
				<br />
				<span style="display:inline-block;margin-bottom:5px"><?php echo $main_image_kraked_stats['saved_bytes']; ?> (<?php echo $main_image_kraked_stats['savings_percentage']; ?> saved)</span>
				<?php } ?>
				<?php if ( $main_image_optimized && $thumbs_optimized ) { ?>
				<br />
				<?php } ?>
				<?php if ( $thumbs_optimized ) { ?>
					<span><strong>Savings on <?php echo $thumbs_count; ?> thumbnails:</strong></span>
					<br />
					<span style="display:inline-block;margin-bottom:5px"><?php echo $thumbs_kraked_stats['total_savings']; ?> (<?php echo $thumbs_kraked_stats['savings_percentage']; ?> saved)</span>
				<?php } ?>
				<br />
				<span><strong>Optimization mode:</strong></span>
				<br />
				<span><?php echo ucfirst($type); ?></span>	
				<?php if ( !empty( $this->kraken_settings['show_reset'] ) ) { ?>
					<br />
					<small
						class="krakenReset" data-id="<?php echo $id; ?>"
						title="Removes Kraken metadata associated with this image">
						Reset
					</small>
					<span class="krakenSpinner"></span>
				</div>
				<?php } ?>
			<?php 	
			$html = ob_get_clean();
			return $html;
		}

		function fill_media_columns( $column_name, $id ) {

			$settings = $this->kraken_settings;
			$optimize_main_image = !empty( $settings['optimize_main_image'] ); 

			$file = get_attached_file( $id );
			$original_size = filesize( $file );

			// handle the case where file does not exist
			if ( $original_size === 0 || $original_size === false ) {
				echo '0 bytes';
				return;
			} else {
				$original_size = self::formatBytes( $original_size );				
			}
			
			$type = isset( $settings['api_lossy'] ) ? $settings['api_lossy'] : 'lossy';

			if ( strcmp( $column_name, 'original_size' ) === 0 ) {
				if ( wp_attachment_is_image( $id ) ) {

					$meta = get_post_meta( $id, '_kraken_size', true );

					if ( isset( $meta['original_size'] ) ) {

						if ( stripos( $meta['original_size'], 'kb' ) !== false ) {
							echo self::formatBytes( ceil( floatval( $meta['original_size']) * 1024 ) );
						} else {
							echo self::formatBytes( $meta['original_size'] );
						}
						
					} else {
						echo $original_size;
					}
				} else {
					echo $original_size;
				}
			} else if ( strcmp( $column_name, 'kraked_size' ) === 0 ) {
				echo '<div class="kraken-wrap">';
				$image_url = wp_get_attachment_url( $id );
				$filename = basename( $image_url );
				if ( wp_attachment_is_image( $id ) ) {

					$meta = get_post_meta( $id, '_kraken_size', true );
					$thumbs_meta = get_post_meta( $id, '_kraked_thumbs', true );

					// Is it optimized? Show some stats
					if ( ( isset( $meta['kraked_size'] ) && empty( $meta['no_savings'] ) ) || !empty( $thumbs_meta ) ) {
						if ( !isset( $meta['kraked_size'] ) && $optimize_main_image ) {
							echo '<div class="buttonWrap"><button data-setting="' . $type . '" type="button" class="kraken_req" data-id="' . $id . '" id="krakenid-' . $id .'" data-filename="' . $filename . '" data-url="' . $image_url . '">Optimize Main Image</button><span class="krakenSpinner"></span></div>';
						}
						echo $this->generate_stats_summary( $id );

					// Were there no savings, or was there an error?
					} else {
						echo '<div class="buttonWrap"><button data-setting="' . $type . '" type="button" class="kraken_req" data-id="' . $id . '" id="krakenid-' . $id .'" data-filename="' . $filename . '" data-url="' . $image_url . '">Optimize This Image</button><span class="krakenSpinner"></span></div>';
						if ( !empty( $meta['no_savings'] ) ) {
							echo '<div class="noSavings"><strong>No savings found</strong><br /><small>Type:&nbsp;' . $meta['type'] . '</small></div>';
						} else if ( isset( $meta['error'] ) ) {
							$error = $meta['error'];
							echo '<div class="krakenErrorWrap"><a class="krakenError" title="' . $error . '">Failed! Hover here</a></div>';
						}
					}
				} else {
					echo 'n/a';
				}
				echo '</div>';
			}
		}

		function replace_image( $image_path, $kraked_url ) {
			$rv = false;
			$ch =  curl_init( $kraked_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0 ); 
        	curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );           	      
            curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36' );
			$result = curl_exec( $ch );

			if ( $result ) {
				$rv = file_put_contents( $image_path, $result );
			}
			return $rv !== false;
		}

		function optimize_image( $image_path, $type, $resize = false ) {
			$settings = $this->kraken_settings;
			$kraken = new Kraken( $settings['api_key'], $settings['api_secret'] );

			if ( !empty( $type ) ) {
				$lossy = $type === 'lossy';
			} else {
				$lossy = $settings['api_lossy'] === 'lossy';
			}

			$params = array(
				'file' => $image_path,
				'wait' => true,
				'lossy' => $lossy,
				'origin' => 'wp'
			);

			$preserve_meta_arr = array();
			if ( $settings['preserve_meta_date'] ) {
				$preserve_meta_arr[] = 'date';
			}
			if ( $settings['preserve_meta_copyright'] ) {
				$preserve_meta_arr[] = 'copyright';
			}
			if ( $settings['preserve_meta_geotag'] ) {
				$preserve_meta_arr[] = 'geotag';
			}
			if ( $settings['preserve_meta_orientation'] ) {
				$preserve_meta_arr[] = 'orientation';
			}
			if ( $settings['preserve_meta_profile'] ) {
				$preserve_meta_arr[] = 'profile';
			}
			if ( $settings['chroma'] ) {
				$params['sampling_scheme'] = $settings['chroma'];
			}

			if ( count( $preserve_meta_arr ) ) {
				$params['preserve_meta'] = $preserve_meta_arr;
			}

			if ( $settings['auto_orient'] ) {
				$params['auto_orient'] = true;
			}

			if ( $resize ) {
				$width = (int) $settings['resize_width'];
				$height = (int) $settings['resize_height'];
				if ( $width && $height ) {
					$params['resize'] = array('strategy' => 'auto', 'width' => $width, 'height' => $height );
				} elseif ( $width && !$height ) {
					$params['resize'] = array('strategy' => 'landscape', 'width' => $width );
				} elseif ( $height && !$width ) {
					$params['resize'] = array('strategy' => 'portrait', 'height' => $height );
				}
			}

			if ( isset( $settings['jpeg_quality'] ) && $settings['jpeg_quality'] > 0 ) {
				$params['quality'] = (int) $settings['jpeg_quality'];
			}
			
			set_time_limit(400);
			$data = $kraken->upload( $params );
			$data['type'] = !empty( $type ) ? $type : $settings['api_lossy'];
			return $data;
		}

		function get_sizes_to_krak() {
			$settings = $this->kraken_settings;
			$rv = array();

			foreach( $settings as $key => $value ) {
				if ( strpos( $key, 'include_size' ) === 0 && !empty( $value ) ) {
					$rv[] = $key;
				}
			}
			return $rv;
		}

		function optimize_thumbnails( $image_data ) {

			$image_id = $this->id;
			if ( empty( $image_id ) ) {
				global $wpdb;
				$post = $wpdb->get_row( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s LIMIT 1", $image_data['file'] ) );
				$image_id = $post->post_id;
			}

			$kraken_meta = get_post_meta( $image_id, '_kraken_size', true );
			$image_backup_path = isset( $kraken_meta['optimized_backup_file'] ) ? $kraken_meta['optimized_backup_file'] : '';
			
			if ( $image_backup_path ) {
				$original_image_path = get_attached_file( $image_id );				
				if ( copy( $image_backup_path, $original_image_path ) ) {
					unlink( $image_backup_path );
					unset( $kraken_meta['optimized_backup_file'] );
					update_post_meta( $image_id, '_kraken_size', $kraken_meta );
				}
			}

			if ( !$this->preg_array_key_exists( '/^include_size_/', $this->kraken_settings ) ) {
				
				global $_wp_additional_image_sizes;
				$sizes = array();

				foreach ( get_intermediate_image_sizes() as $_size ) {
					if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
						$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
						$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
						$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
					} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
						$sizes[ $_size ] = array(
							'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
							'height' => $_wp_additional_image_sizes[ $_size ]['height'],
							'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
						);
					}
				}
				$sizes = array_keys( $sizes );
				foreach ($sizes as $size) {
					$this->kraken_settings['include_size_' . $size] = 1;
				}
			}			

			// when resizing has taken place via API, update the post metadata accordingly
			if ( !empty( $kraken_meta['kraked_width'] ) && !empty( $kraken_meta['kraked_height'] ) ) {
				$image_data['width'] = $kraken_meta['kraked_width'];
				$image_data['height'] = $kraken_meta['kraked_height'];
			}


			$path_parts = pathinfo( $image_data['file'] );

			// e.g. 04/02, for use in getting correct path or URL
			$upload_subdir = $path_parts['dirname'];

			$upload_dir = wp_upload_dir();

			// all the way up to /uploads
			$upload_base_path = $upload_dir['basedir'];
			$upload_full_path = $upload_base_path . '/' . $upload_subdir;

			$sizes = array();

			if ( isset( $image_data['sizes'] ) ) {
				$sizes = $image_data['sizes'];
			}

			if ( !empty( $sizes ) ) {

				$sizes_to_krak = $this->get_sizes_to_krak();
				$thumb_path = '';
				$thumbs_optimized_store = array();
				$this_thumb = array();

				foreach ( $sizes as $key => $size ) {

					if ( !in_array("include_size_$key", $sizes_to_krak) ) {
						continue;
					}

					$thumb_path = $upload_full_path . '/' . $size['file'];
					
					if ( file_exists( $thumb_path ) !== false ) {
						$result = $this->optimize_image( $thumb_path, $this->optimization_type );
						if ( !empty( $result ) && isset( $result['success'] ) && isset( $result['kraked_url'] ) ) {
							$kraked_url = $result['kraked_url'];
							if ( (int) $result['saved_bytes'] !== 0 ) {
								if ( $this->replace_image( $thumb_path, $kraked_url ) ) {
									$this_thumb = array( 'thumb' => $key, 'file' => $size['file'], 'original_size' => $result['original_size'], 'kraked_size' => $result['kraked_size'], 'type' => $this->optimization_type );
									$thumbs_optimized_store [] = $this_thumb;
								}
							} else {
								$this_thumb = array( 'thumb' => $key, 'file' => $size['file'], 'original_size' => $result['original_size'], 'kraked_size' => $result['original_size'], 'type' => $this->optimization_type );
								$thumbs_optimized_store [] = $this_thumb;								
							}
						} 
					}
				}
			}
			if ( !empty( $thumbs_optimized_store ) ) {
				update_post_meta( $image_id, '_kraked_thumbs', $thumbs_optimized_store, false );
			}
			return $image_data;
		}

		function get_image_sizes() {
			global $_wp_additional_image_sizes;

			$sizes = array();

			foreach ( get_intermediate_image_sizes() as $_size ) {
				if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
					$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
					$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
					$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],
						'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
					);
				}
			}

			return $sizes;
		}


		static function formatBytes( $size, $precision = 2 ) {
		    $base = log( $size, 1024 );
		    $suffixes = array( ' bytes', 'KB', 'MB', 'GB', 'TB' );   
		    return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[floor( $base )];
		}
	}
}

new Wp_Kraken();
