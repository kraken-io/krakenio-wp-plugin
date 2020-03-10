<?php
/**
* Kraken IO Support for WP Retina 2x.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Support_WP_Retina_2x {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		add_action( 'wr2x_retina_file_added', [ $this, 'retina_file_added' ], 10, 2 );
		add_action( 'wr2x_retina_file_removed', [ $this, 'retina_file_removed' ], 10, 2 );
	}

	/**
	 * Optimize image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @param  string $file
	 * @return bool
	 */
	public function retina_file_added( $id, $file ) {
		kraken_io()->optimization->optimize_single_image( $file );
		kraken_io()->optimization->optimize_single_image_webp( $file );
	}

	/**
	 * Remove image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @param  string $file
	 * @return bool
	 */
	public function retina_file_removed( $id, $file ) {
		$meta = wp_get_attachment_metadata( $id );
		$pathinfo = pathinfo( $meta['file'] );
		$uploads = wp_upload_dir();
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];
		$fullpath = trailingslashit( $basepath ) . $file . '.webp';

		if ( file_exists( $fullpath ) ) {
			unlink( $fullpath );
		}
	}
}

new Kraken_IO_Support_WP_Retina_2x();
