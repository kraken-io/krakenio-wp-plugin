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
		$this->options = kraken_io()->get_options();

		if ( $this->options['auto_optimize'] ) {
			add_action( 'add_attachment', [ $this, 'optimize_image_on_upload' ] );
			add_filter( 'wp_generate_attachment_metadata', [ $this, 'optimize_thumbnails_on_resize' ], 10, 2 );
		}

		add_action( 'wp_delete_file', [ $this, 'delete_image' ] );
		add_filter( 'mod_rewrite_rules', [ $this, 'webp_rewrite_rules' ] );
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
	 * Delete image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $file Path to the file to delete.
	 * @return bool
	 */
	public function delete_image( $file ) {
		$webp = $file . '.webp';

		if ( file_exists( $webp ) ) {
			unlink( $webp );
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
	private function format_optimization_response( $response, $id ) {

		$savings_percentage          = $response['saved_bytes'] / $response['original_size'] * 100;
		$response['savings_percent'] = round( $savings_percentage, 2 ) . '%';

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
	private function replace_image( $path, $url ) {

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$optimized_image_contents = file_get_contents( $url );
		$replaced_image           = false;

		if ( $optimized_image_contents ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			$replaced_image = file_put_contents( $path, $optimized_image_contents );
		}

		return false !== $replaced_image;
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
	 * Get optimized image from api.
	 *
	 * @since  2.7
	 * @access private
	 * @param  string $image_path
	 * @param  array $args
	 * @return array
	 */
	private function get_optimized_image( $image_path, $args = [] ) {
		$settings = $this->options;

		$args = wp_parse_args(
			$args,
			[
				'type'   => '',
				'webp'   => false,
				'resize' => true,
			]
		);

		if ( ! empty( $args['type'] ) ) {
			$lossy = 'lossy' === $args['type'];
		} else {
			$lossy = 'lossy' === $settings['api_lossy'];
		}

		$params = [
			'file'   => $image_path,
			'wait'   => true,
			'lossy'  => $lossy,
			'origin' => 'wp',
			'webp'   => $args['webp'],
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

		if ( $args['resize'] && ( ! empty( $settings['resize_width'] ) || ! empty( $settings['resize_height'] ) ) ) {

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

		$response         = kraken_io()->api->upload( $params );
		$response['type'] = ! empty( $type ) ? $type : $settings['api_lossy'];

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
	public function optimize_single_image( $path, $args = [] ) {

		$optimized_image = $this->get_optimized_image( $path, $args );

		if ( isset( $optimized_image['success'] ) && $optimized_image['success'] ) {

			if ( ! isset( $optimized_image['kraked_url'] ) ) {
				return [
					'error' => __( 'Could not get optimized image URL.', 'kraken-io' ),
				];
			}

			if ( ! $this->replace_image( $path, $optimized_image['kraked_url'] ) ) {
				return [
					'error' => __( 'Could not overwrite original file. Please ensure that your files are writable by plugins.', 'kraken-io' ),
				];
			}

			return $optimized_image;
		}

		return [
			'error' => isset( $optimized_image['message'] ) ? $optimized_image['message'] : __( 'Unknown error.', 'kraken-io' ),
		];
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
	public function optimize_single_image_webp( $path, $args = [] ) {

		if ( empty( $this->options['create_webp'] ) ) {
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

		if ( isset( $optimized_image['success'] ) && $optimized_image['success'] ) {

			if ( ! isset( $optimized_image['kraked_url'] ) ) {
				return false;
			}

			$path = $path . '.webp';

			if ( ! $this->replace_image( $path, $optimized_image['kraked_url'] ) ) {
				return false;
			}

			return $optimized_image;
		}

		return false;
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
	public function optimize_main_image( $id, $args = [] ) {
		kraken_io()->define( 'KRAKEN_IO_OPTIMIZE', true );

		$kraked_size = get_post_meta( $id, '_kraken_size', true );

		if ( $kraked_size ) {
			return true;
		}

		$path = get_attached_file( $id );

		// the image doesn't exist
		if ( ! $path ) {
			return [
				'error' => __( "Couldn't find the image path.", 'kraken-io' ),
			];
		}

		$response = $this->optimize_single_image( $path, $args );
		$this->optimize_single_image_webp( $path, $args );

		if ( isset( $response['success'] ) && $response['success'] ) {

			$data = $this->format_optimization_response( $response, $id );
			update_post_meta( $id, '_kraken_size', $data );

			$metadata = wp_get_attachment_metadata( $id );

			if ( ! empty( $response['kraked_width'] ) && ! empty( $response['kraked_height'] ) ) {
				$metadata['width']  = $data['kraked_width'];
				$metadata['height'] = $data['kraked_height'];
			}

			wp_update_attachment_metadata( $id, $metadata );

			return true;
		}

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
	public function optimize_thumbnails( $id, $args = [] ) {
		kraken_io()->define( 'KRAKEN_IO_OPTIMIZE', true );

		$kraked_thumbs = get_post_meta( $id, '_kraked_thumbs', true );

		if ( $kraked_thumbs ) {
			return true;
		}

		$args = wp_parse_args(
			$args,
			[
				'resize' => false,
			]
		);

		$metadata   = wp_get_attachment_metadata( $id );
		$sizes      = kraken_io()->get_image_sizes_to_optimize();
		$thumb_data = [];

		$upload_dir = wp_upload_dir();
		$path_parts = pathinfo( $metadata['file'] );

		// e.g. 04/02, for use in getting correct path or URL
		$upload_subdir = $path_parts['dirname'];

		// all the way up to /uploads
		$upload_base_path = $upload_dir['basedir'];
		$upload_full_path = $upload_base_path . '/' . $upload_subdir;

		$error_responses = [];

		foreach ( $metadata['sizes'] as $key => $size ) {

			if ( in_array( $key, $sizes, true ) ) {
				$path     = $upload_full_path . '/' . $size['file'];
				$response = $this->optimize_single_image( $path, $args );
				$this->optimize_single_image_webp( $path, $args );

				if ( isset( $response['success'] ) && $response['success'] ) {
					$thumb_data[] = [
						'thumb'         => $key,
						'file'          => $size['file'],
						'original_size' => $response['original_size'],
						'kraked_size'   => $response['kraked_size'],
						'type'          => $response['type'],
					];
				} else {
					$error_responses[] = $response['error'];
				}
			}
		}

		if ( $thumb_data ) {
			update_post_meta( $id, '_kraked_thumbs', $thumb_data, false );
			wp_update_attachment_metadata( $id, $metadata );
			return true;
		}

		$error_responses = array_unique( $error_responses );

		return [
			'error' => isset( $error_responses[0] ) ? $error_responses[0] : __( 'There are no image sizes to optimize.', 'kraken-io' ),
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
	public function optimize_image( $id, $args = [] ) {
		$error_messages = [];
		$options        = kraken_io()->get_options();

		if ( $options['optimize_main_image'] ) {
			$optimized_main_image = $this->optimize_main_image( $id, $args );
			if ( isset( $optimized_main_image['error'] ) ) {
				$error_messages[] = $optimized_main_image['error'];
			}
		}

		$optimized_thumbnails = $this->optimize_thumbnails( $id, $args );

		if ( isset( $optimized_thumbnails['error'] ) ) {
			$error_messages[] = $optimized_thumbnails['error'];
		}

		$error_messages = array_unique( $error_messages );

		if ( $error_messages ) {
			return [
				'errors' => $error_messages,
			];
		}

		return true;
	}

	/**
	 * Optimize images on upload.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 */
	public function optimize_image_on_upload( $id ) {

		if ( empty( $this->options['optimize_main_image'] ) ) {
			return false;
		}

		if ( ! wp_attachment_is_image( $id ) ) {
			return false;
		}

		if ( $this->options['background_process'] ) {
			$data = [
				'id'    => $id,
				'type'  => 'main-image',
				'count' => 0,
			];
			kraken_io()->bg_process->push_to_queue( $data );
			kraken_io()->bg_process->save()->dispatch();
		} else {
			$this->optimize_main_image( $id );
		}
	}

	/**
	 * Optimize thumbnails when they are generated.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function optimize_thumbnails_on_resize( $metadata, $id ) {
		if ( $this->options['background_process'] ) {
			$data = [
				'id'    => $id,
				'type'  => 'thumbnails',
				'count' => 0,
			];
			kraken_io()->bg_process->push_to_queue( $data );
			kraken_io()->bg_process->save()->dispatch();
		} else {
			$this->optimize_thumbnails( $id );
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
	public function get_unoptimized_images( $posts_per_page = 30 ) {

		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $posts_per_page,
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => '_kraken_size',
					'compare' => 'NOT EXISTS',
					'value'   => '',
				],
				[
					'key'     => '_kraked_thumbs',
					'compare' => 'NOT EXISTS',
					'value'   => '',
				],
			],
		];

		$query = new WP_Query( $args );

		return [
			'ids'   => wp_list_pluck( $query->posts, 'ID' ),
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
	public function webp_rewrite_rules( $rules ) {
		$home_root   = wp_parse_url( home_url( '/' ) );
		$home_root   = $home_root['path'];
		$options     = get_option( '_kraken_options', [] );
		$has_rewrite = isset( $options['display_webp'] ) ? $options['display_webp'] : false;

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

		if ( $has_rewrite ) {
			$rules = $webp_rules . $rules;
		}

		return $rules;
	}

}
