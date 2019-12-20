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
		add_action( 'wp_ajax_kraken_reset_image', array( $this, 'reset_image' ) );
		add_action( 'wp_ajax_kraken_reset_all', array( $this, 'reset_all_images' ) );
	}

	/**
	 * Reset image.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function reset_image() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'kraken-io-nonce' ) ) {
			wp_send_json_error(
				array(
					'type' => 'nonce',
				)
			);
		}

		$id = (int) $_POST['id'];

		if ( ! wp_attachment_is_image( $id ) ) {
			wp_send_json_error(
				array(
					'type' => 'error',
				)
			);
		}

		$size = kraken_io()->format_bytes( filesize( get_attached_file( $id ) ) );

		$is_size_deleted   = delete_post_meta( $id, '_kraken_size' );
		$is_thumbs_deleted = delete_post_meta( $id, '_kraked_thumbs' );

		if ( $is_thumbs_deleted && $is_size_deleted ) {

			$stats = kraken_io()->stats->get_image_stats( $id );

			ob_start();
			kraken_io()->get_template( 'media-column-stats', array( 'stats' => $stats ) );
			$column_html = ob_get_clean();

			wp_send_json_success(
				array(
					'size' => $size,
					'html' => $column_html,
				)
			);
		}

		wp_send_json_error(
			array(
				'type' => 'error',
			)
		);
	}

	/**
	 * Reset all images.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function reset_all_images() {
		$is_thumbs_deleted = delete_post_meta_by_key( '_kraked_thumbs' );
		$is_size_deleted   = delete_post_meta_by_key( '_kraken_size' );

		if ( $is_thumbs_deleted && $is_size_deleted ) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

}
