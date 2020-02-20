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
    public function __construct()
    {

        // Uses unique prefix per blog so each blog has separate queue.
        // $this->prefix = 'wp_' . get_current_blog_id();
        $this->action = 'kraken_optimize_images';

        // This is needed to prevent timeouts due to threading. See https://core.trac.wordpress.org/ticket/36534.
		@putenv( 'MAGICK_THREAD_LIMIT=1' ); // @codingStandardsIgnoreLine.
		
		update_option('_dsa', 'construct');

        parent::__construct();
    }

	/**
	 * Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $id item to iterate over
	 * @return mixed
	 */
	protected function task( $id ) {
		update_option('_dsa', $id);
		error_log( 'task id: ' . $id );
		$optimized_main_image = kraken_io()->optimization->optimize_main_image( $id );
		$optimized_thumbnails = kraken_io()->optimization->optimize_thumbnails( $id );

		if ( $optimized_main_image && $optimized_thumbnails ) {
			return false;
		}

		return $id;
	}

	/**
	 * Complete
	 *
	 * @since  2.7
	 * @access public
	 */
	protected function complete() {
		parent::complete();
		error_log('done');
	}

}
