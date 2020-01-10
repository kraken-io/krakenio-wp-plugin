<?php
/**
* Kraken IO Ajax.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Ajax {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		add_action( 'wp_ajax_kraken_reset_image', [ $this, 'reset_image' ] );
		add_action( 'wp_ajax_kraken_reset_all', [ $this, 'reset_all_images' ] );
		add_action( 'wp_ajax_kraken_optimize_image', [ $this, 'optimize_image' ] );
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
				[
					'type' => 'nonce',
				]
			);
		}

		$id = (int) $_POST['id'];

		if ( ! wp_attachment_is_image( $id ) ) {
			wp_send_json_error(
				[
					'type' => 'error',
				]
			);
		}

		$reset_image = kraken_io()->optimization->reset_image( $id );

		if ( $reset_image ) {

			$stats = kraken_io()->stats->get_image_stats( $id );
			$size  = kraken_io()->format_bytes( filesize( get_attached_file( $id ) ) );

			ob_start();
			kraken_io()->get_template( 'media-column-stats', [ 'stats' => $stats ] );
			$column_html = ob_get_clean();

			wp_send_json_success(
				[
					'size' => $size,
					'html' => $column_html,
				]
			);
		}

		wp_send_json_error(
			[
				'type' => 'error',
			]
		);
	}

	/**
	 * Reset all images.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function reset_all_images() {

		if ( kraken_io()->optimization->reset_all_images() ) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Optimize image.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function optimize_image() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'kraken-io-nonce' ) ) {
			wp_send_json_error(
				[
					'type' => 'nonce',
				]
			);
		}

		$id = (int) $_POST['id'];

		if ( ! wp_attachment_is_image( $id ) ) {
			wp_send_json_error(
				[
					'type' => 'error',
				]
			);
		}

		$optimize_image = kraken_io()->optimization->optimize_image( $id );

		if ( $optimize_image ) {

			$stats = kraken_io()->stats->get_image_stats( $id );
			$size  = kraken_io()->format_bytes( filesize( get_attached_file( $id ) ) );

			ob_start();
			kraken_io()->get_template( 'media-column-stats', [ 'stats' => $stats ] );
			$column_html = ob_get_clean();

			wp_send_json_success(
				[
					'size' => $size,
					'html' => $column_html,
				]
			);
		}

		wp_send_json_error();
	}

}

new Kraken_IO_Ajax();
