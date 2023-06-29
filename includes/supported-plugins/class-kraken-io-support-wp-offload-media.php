<?php
/**
* Kraken IO Support for WP Offload Media.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Support_WP_Offload_Media {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		add_filter( 'as3cf_object_meta', [ $this, 'object_meta' ] );
		add_filter( 'as3cf_attachment_file_paths', [ $this, 'attachment_file_paths' ], 10, 3 );
		add_filter( 'as3cf_remove_attachment_paths', [ $this, 'remove_attachment_file_paths' ], 10, 2 );
		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// add_filter( 'as3cf_get_attached_file_copy_back_to_local', array( $this, 'get_attached_file_copy_back_to_local' ), 10, 3 );
	}

	/**
	 * Fixes the ContentType for WebP images.
	 *
	 * @param  array $args The parameters to be used for the S3 upload.
	 * @return array $args The same parameters with ContentType corrected.
	 */
	public function object_meta( $args ) {
		if ( ! empty( $args['SourceFile'] ) && empty( $args['ContentType'] ) && false !== strpos( $args['SourceFile'], '.webp' ) ) {
			$args['ContentType'] = 'image/webp';
		}

		return $args;
	}

	/**
	 * Adds WebP derivatives so that they can be uploaded.
	 *
	 * @param  array $paths
	 * @param  int $attachment_id
	 * @param  array $metadata attachment metadata
	 * @return array $paths
	 */
	public function attachment_file_paths( $paths, $attachment_id, $metadata ) {
		foreach ( $paths as $path ) {
			$webp = $path . '.webp';
			if ( file_exists( $webp ) ) {
				$paths[] = $webp;
			}
		}

		return $paths;
	}

	/**
	 * Cleanup remote storage for WP Offload S3.
	 *
	 * Checks for WebP derivatives so that they can be removed.
	 *
	 * @param  array $paths The image paths currently queued for deletion.
	 * @param  int $id The ID number of the image in the database.
	 * @return array $paths A list of paths to remove.
	 */
	public function remove_attachment_file_paths( $paths, $id ) {
		foreach ( $paths as $path ) {
			if ( is_string( $path ) ) {
				$paths[] = $path . '.webp';
			}
		}

		return $paths;
	}

	/**
	 * Copy back the file from the bucket to the local server so we can edit the file.
	 *
	 * @param  bool $copy_back_to_local default is false
	 * @param  string $file file path of local file
	 * @param  int $attachment_id
	 * @return bool
	 */
	public function get_attached_file_copy_back_to_local( $copy_back_to_local, $file, $attachment_id ) {
		if ( ! defined( 'KRAKEN_IO_OPTIMIZE' ) || ! KRAKEN_IO_OPTIMIZE ) {
			return $copy_back_to_local;
		}

		return true;
	}
}

new Kraken_IO_Support_WP_Offload_Media();
