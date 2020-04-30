<?php
/**
* Kraken IO Support for NextGEN Gallery.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Support_NextGEN_Gallery {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		add_action( 'ngg_added_new_image', [ $this, 'added_new_image' ], 10 );
		add_action( 'ngg_delete_image', [ $this, 'delete_image' ], 10 );
	}

	/**
	 * Remove image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @param  string $size
	 * @return void
	 */
	public function added_new_image( $image ) {
		$registry = C_Component_Registry::get_instance();
		$storage  = $registry->get_utility( 'I_Gallery_Storage' );

		$sizes = $storage->get_image_sizes( $image );
		$sizes = $this->maybe_get_more_sizes( $sizes, $image->meta_data );

		foreach ( $sizes as $size ) {
			if ( 'backup' === $size ) {
				continue;
			}

			$file = $storage->get_image_abspath( $image, $size );

			kraken_io()->optimization->optimize_single_image( $file );
			kraken_io()->optimization->optimize_single_image_webp( $file );
		}
	}

	/**
	* Looks for more sizes to optimize in the image metadata.
	*
	* @since  2.7
	* @access public
	* @param  array $sizes The image sizes NextGEN gave us.
	* @param  array $meta The image metadata from NextGEN.
	* @return array The full list of known image sizes for this image.
	*/
	public function maybe_get_more_sizes( $sizes, $meta ) {
		if ( 2 === count( $sizes ) ) {
			foreach ( $meta as $meta_key => $meta_val ) {
				if ( 'backup' !== $meta_key && is_array( $meta_val ) && isset( $meta_val['width'] ) ) {
					$sizes[] = $meta_key;
				}
			}
		}
		return $sizes;
	}

	/**
	 * Remove image.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id
	 * @return void
	 */
	public function delete_image( $id ) {
		$registry = C_Component_Registry::get_instance();
		$storage  = $registry->get_utility( 'I_Gallery_Storage' );

		$image = $storage->object->_image_mapper->find( $id );
		$sizes = $storage->get_image_sizes( $image );

		foreach ( $sizes as $size ) {
			$image_abspath = $storage->get_image_abspath( $image, $size );
			$fullpath      = $image_abspath . '.webp';

			if ( file_exists( $image_abspath ) ) {
				unlink( $fullpath );
			}
		}
	}
}

new Kraken_IO_Support_NextGEN_Gallery();
