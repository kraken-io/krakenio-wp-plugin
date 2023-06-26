<?php
/**
* Kraken IO Background Process.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Background_Process extends WP_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {

		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'kraken_io_' . get_current_blog_id();
		$this->action = 'kraken_optimize_images';

		// This is needed to prevent timeouts due to threading. See https://core.trac.wordpress.org/ticket/36534.
		@putenv( 'MAGICK_THREAD_LIMIT=1' ); // @codingStandardsIgnoreLine.

		parent::__construct();
	}

	/**
	 * Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $item item to iterate over
	 * @return mixed
	 */
	protected function task( $item ) {

		$item['count']++;

		if ( 'main-image' === $item['type'] ) {
			$is_optimized = kraken_io()->optimization->optimize_main_image( $item['id'] );
		} else {
			$is_optimized = kraken_io()->optimization->optimize_thumbnails( $item['id'] );
		}

		// if it's optimized or we tried 3 times but failed
		if ( $is_optimized || $item['count'] > 3 ) {
			return false;
		}

		return $item;
	}

}
