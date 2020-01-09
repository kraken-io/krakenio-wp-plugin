<?php
/**
* Kraken IO Optimization.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Optimization {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		$this->options = kraken_io()->get_options();

		if ( $this->options['auto_optimize'] ) {
			add_action( 'add_attachment', [ $this, 'optimize_image_on_upload' ] );
			add_filter( 'wp_generate_attachment_metadata', [ $this, 'optimize_thumbnails' ] );
		}
	}

	/**
	 * Reset image.
	 *
	 * @since  2.7
	 * @access public
	 * @return bool
	 */
	public function reset_image( $id ) {
		$is_size_deleted   = delete_post_meta( $id, '_kraken_size' );
		$is_thumbs_deleted = delete_post_meta( $id, '_kraked_thumbs' );

		if ( $is_thumbs_deleted && $is_size_deleted ) {
			return true;
		}

		return false;
	}

	/**
	 * Reset all images.
	 *
	 * @since  2.7
	 * @access public
	 * @return bool
	 */
	public function reset_all_images() {
		$is_thumbs_deleted = delete_post_meta_by_key( '_kraked_thumbs' );
		$is_size_deleted   = delete_post_meta_by_key( '_kraken_size' );

		if ( $is_thumbs_deleted && $is_size_deleted ) {
			return true;
		}

		return false;
	}

	/**
	 * Converts an deserialized API result array into an array
	 * which this plugin will consume
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $result
	 * @param  int $image_id
	 * @return array $result
	 */
	public function get_result_arr( $result, $image_id ) {
		$rv = [];

		$rv['original_size'] = $result['original_size'];
		$rv['kraked_size']   = $result['kraked_size'];
		$rv['saved_bytes']   = $result['saved_bytes'];

		$savings_percentage = $result['saved_bytes'] / $result['original_size'] * 100;

		$rv['savings_percent'] = round( $savings_percentage, 2 ) . '%';
		$rv['type']            = $result['type'];

		if ( ! empty( $result['kraked_width'] ) && ! empty( $result['kraked_height'] ) ) {
			$rv['kraked_width']  = $result['kraked_width'];
			$rv['kraked_height'] = $result['kraked_height'];
		}

		$rv['success'] = $result['success'];
		$rv['meta']    = wp_get_attachment_metadata( $image_id );

		return $rv;
	}

	/**
	 * Replace image with optimized.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $image_path
	 * @param  string $kraked_url
	 * @return bool
	 */
	public function replace_image( $image_path, $kraked_url ) {

	}

	public function get_preserve_meta_options( $options ) {

		$preserve_meta = [];

		if ( $options['preserve_meta_date'] ) {
			$preserve_meta[] = 'date';
		}

		if ( $options['preserve_meta_copyright'] ) {
			$preserve_meta[] = 'copyright';
		}

		if ( $options['preserve_meta_geotag'] ) {
			$preserve_meta[] = 'geotag';
		}

		if ( $options['preserve_meta_orientation'] ) {
			$preserve_meta[] = 'orientation';
		}

		if ( $options['preserve_meta_profile'] ) {
			$preserve_meta[] = 'profile';
		}

		return $preserve_meta;
	}

	/**
	 * Optimize image.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function optimize_image( $image_path, $type, $resize = false ) {
		$settings = $this->options;

		if ( ! empty( $type ) ) {
			$lossy = 'lossy' === $type;
		} else {
			$lossy = 'lossy' === $settings['api_lossy'];
		}

		$params = [
			'file'   => $image_path,
			'wait'   => true,
			'lossy'  => $lossy,
			'origin' => 'wp',
		];

		$preserve_meta = $this->get_preserve_meta_options( $settings );

		if ( count( $preserve_meta ) ) {
			$params['preserve_meta'] = $preserve_meta;
		}

		if ( $settings['chroma'] ) {
			$params['sampling_scheme'] = $settings['chroma'];
		}

		if ( $settings['auto_orient'] ) {
			$params['auto_orient'] = true;
		}

		if ( $resize ) {
			$width  = (int) $settings['resize_width'];
			$height = (int) $settings['resize_height'];
			if ( $width && $height ) {
				$params['resize'] = [
					'strategy' => 'auto',
					'width'    => $width,
					'height'   => $height,
				];
			} elseif ( $width && ! $height ) {
				$params['resize'] = [
					'strategy' => 'landscape',
					'width'    => $width,
				];
			} elseif ( $height && ! $width ) {
				$params['resize'] = [
					'strategy' => 'portrait',
					'height'   => $height,
				];
			}
		}

		if ( isset( $settings['jpeg_quality'] ) && $settings['jpeg_quality'] > 0 ) {
			$params['quality'] = (int) $settings['jpeg_quality'];
		}

		$response = kraken_io()->api->upload( $params );

		return $response;
	}

	/**
	 * Optimize tumbnails.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function optimize_thumbnails() {

	}

	/**
	 * Optimize images on upload.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $image_id
	 */
	public function optimize_image_on_upload( $image_id ) {

		if ( empty( $this->options['optimize_main_image'] ) ) {
			return false;
		}

		$settings = $this->options;
		$type     = $settings['api_lossy'];

		if ( ! wp_attachment_is_image( $image_id ) ) {
			return false;
		}

		$image_path        = get_attached_file( $image_id );
		$image_backup_path = $image_path . '_kraken_' . md5( $image_path );
		$backup_created    = false;

		if ( copy( $image_path, $image_backup_path ) ) {
			$backup_created = true;
		}

		$resize = false;

		if ( ! empty( $settings['resize_width'] ) || ! empty( $settings['resize_height'] ) ) {
			$resize = true;
		}

		// optimize backup image
		if ( $backup_created ) {
			$api_result = $this->optimize_image( $image_backup_path, $type, $resize );
		} else {
			$api_result = $this->optimize_image( $image_path, $type, $resize );
		}

		$data = [];

		if ( ! empty( $api_result ) && ! empty( $api_result['success'] ) ) {
			$data = $this->get_result_arr( $api_result, $image_id );

			if ( $backup_created ) {
				$data['optimized_backup_file'] = $image_backup_path;

				if ( $data['saved_bytes'] > 0 ) {
					if ( ! $this->replace_image( $image_backup_path, $api_result['kraked_url'] ) ) {
						$data['error'] = 'replace_image';
					}
				}
			} else {
				if ( $data['saved_bytes'] > 0 ) {
					if ( ! $this->replace_image( $image_path, $api_result['kraked_url'] ) ) {
						$data['error'] = 'replace_image';
					}
				}
			}

			update_post_meta( $image_id, '_kraken_size', $data );

		} else {
			// error or no optimization
			if ( file_exists( $image_path ) ) {

				$data['original_size'] = filesize( $image_path );
				$data['error']         = $api_result['message'];
				$data['type']          = $api_result['type'];

				update_post_meta( $image_id, '_kraken_size', $data );
			}
		}

	}

	/**
	 * Get image sizes to optimize.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function get_image_sizes_to_optimize() {

	}
}
