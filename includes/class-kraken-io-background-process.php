<?php
/**
 * Kraken IO Background Process.
 *
 * @package Kraken_IO/Classes
 * @since   2.7
 */

defined('ABSPATH') || exit;

class Kraken_IO_Background_Process extends Kraken_IO_WP_Background_Process
{

	/**
	 * Initiate new background process.
	 *
	 * Each distinct action string is a fully independent queue with its own
	 * lock, so several instances ("workers") can run concurrently. Worker 0
	 * keeps the original action name, so items queued before an upgrade are
	 * still picked up.
	 *
	 * @param string $suffix Optional worker suffix (e.g. '_w1').
	 */
	public function __construct($suffix = '')
	{

		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'kraken_io_' . get_current_blog_id();
		$this->action = 'kraken_optimize_images' . $suffix;

		// This is needed to prevent timeouts due to threading. See https://core.trac.wordpress.org/ticket/36534.
		putenv('MAGICK_THREAD_LIMIT=1'); // @codingStandardsIgnoreLine.

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
	protected function task($item)
	{

		$item['count']++;

		if ('convert' === $item['type']) {
			$format = kraken_io()->optimization->get_convert_format();
			$result = $format ? kraken_io()->optimization->convert_image($item['id'], $format) : true;
			$is_optimized = (true === $result);

			// Out of retries: surface the failure instead of leaving the grid
			// with a phantom "optimizing" spinner forever.
			if (!$is_optimized && $item['count'] > 3) {
				$message = is_array($result) && isset($result['error'])
					? $result['error']
					: __('Conversion failed.', 'kraken-io');
				update_post_meta($item['id'], '_kraken_size', ['error' => $message]);
				delete_post_meta($item['id'], '_kraken_io_is_optimizing_main_image');
				delete_post_meta($item['id'], '_kraken_io_optimizing_started');
			}
		} elseif ('main-image' === $item['type']) {
			$is_optimized = kraken_io()->optimization->optimize_main_image($item['id']);
		} else {
			$is_optimized = kraken_io()->optimization->optimize_thumbnails($item['id']);
		}

		// if it's optimized or we tried 3 times but failed
		if ($is_optimized || $item['count'] > 3) {
			return false;
		}

		return $item;
	}

}

/**
 * A pool of background-process workers, so queued uploads optimize CONCURRENTLY
 * (up to the "asynchronous requests" setting) instead of one by one.
 *
 * Each worker is an independent queue+lock; items are routed by attachment ID,
 * so an attachment's main image and thumbnails stay on the same worker (and in
 * order) while different attachments process in parallel.
 *
 * @since 3.0.0
 */
class Kraken_IO_Background_Process_Pool
{

	/**
	 * The hard ceiling on workers. All are always instantiated so their AJAX
	 * and cron hooks exist — queued items keep processing even if the
	 * concurrency setting is lowered afterwards.
	 */
	const MAX_WORKERS = 10;

	/**
	 * @var Kraken_IO_Background_Process[]
	 */
	private $workers = [];

	public function __construct()
	{
		// Worker 0 keeps the legacy action name (pre-pool queue items).
		$this->workers[] = new Kraken_IO_Background_Process();
		for ($i = 1; $i < self::MAX_WORKERS; $i++) {
			$this->workers[] = new Kraken_IO_Background_Process('_w' . $i);
		}
	}

	/**
	 * How many workers may run at once — the same 1-10 "asynchronous requests"
	 * setting the bulk optimizer uses.
	 *
	 * @return int
	 */
	private function concurrency()
	{
		$options = kraken_io()->get_options();
		$limit   = isset($options['bulk_async_limit']) ? (int) $options['bulk_async_limit'] : 4;

		return max(1, min(self::MAX_WORKERS, $limit));
	}

	/**
	 * Queue an item and kick its worker.
	 *
	 * @param array $item ['id' => attachment id, 'type' => ..., 'count' => 0]
	 */
	public function enqueue($item)
	{
		$worker = $this->workers[(int) $item['id'] % $this->concurrency()];
		$worker->push_to_queue($item);
		$worker->save()->dispatch();
	}
}
