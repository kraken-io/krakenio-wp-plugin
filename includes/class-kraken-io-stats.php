<?php
/**
* Kraken IO Stats.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_Stats {

	/**
	 * Hook in methods.
	 *
	 * @since  2.7
	 * @access public
	 */
	public function __construct() {
		add_filter( 'manage_media_columns', [ $this, 'add_media_columns' ] );
		add_action( 'manage_media_custom_column', [ $this, 'fill_media_columns' ], 10, 2 );
		add_filter( 'attachment_fields_to_edit', [ $this, 'attachment_fields' ], 10, 2 );

		$this->options = kraken_io()->get_options();
	}

	/**
	 * Add custom columns in the Media list table.
	 *
	 * @since  2.7
	 * @access public
	 * @param  array $columns An array of columns.
	 * @return array $columns An array of columns.
	 */
	public function add_media_columns( $columns ) {
		$columns['kraken-stats'] = esc_html__( 'Kraken.io', 'kraken-io' );
		return $columns;
	}

	/**
	 * Add content for custom columns in the Media list table.
	 *
	 * @since  2.7
	 * @access public
	 * @param  string $column_name Name of the custom column.
	 * @param  int $post_id Attachment ID
	 * @return void
	 */
	public function fill_media_columns( $column_name, $id ) {

		switch ( $column_name ) {
			case 'kraken-stats':
				$stats = $this->get_image_stats( $id );
				kraken_io()->get_template( 'media-column-stats', [ 'stats' => $stats ] );
				break;
		}

	}

	/**
	 * Add Kraken stats to media uploader.
	 *
	 * @since  2.7
	 * @access public
	 * @param  $form_fields array, fields to include in attachment form
	 * @param  $post object, attachment record in database
	 * @return $form_fields, modified form fields
	 */
	public function attachment_fields( $form_fields, $post ) {

		$stats = $this->get_image_stats( $post->ID );
		ob_start();
		kraken_io()->get_template( 'media-column-stats', [ 'stats' => $stats ] );
		$template = ob_get_clean();

		$form_fields['kraken-stats'] = [
			'label'         => esc_html__( 'Kraken.io', 'kraken-io' ),
			'input'         => 'html',
			'html'          => $template,
			'show_in_edit'  => true,
			'show_in_modal' => true,
		];

		return $form_fields;
	}

	/**
	 * Get image original size.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $post_id Attachment ID
	 * @return string|bool $size
	 */
	public function get_original_size( $id ) {

		$file = get_attached_file( $id );

		// if file does not exist
		if ( ! $file ) {
			return false;
		}

		$original_size = filesize( $file );
		$original_size = kraken_io()->format_bytes( $original_size );

		if ( wp_attachment_is_image( $id ) ) {

			$meta = get_post_meta( $id, '_kraken_size', true );

			if ( isset( $meta['original_size'] ) ) {

				if ( stripos( $meta['original_size'], 'kb' ) !== false ) {
					return kraken_io()->format_bytes( ceil( floatval( $meta['original_size'] ) * 1024 ) );
				} else {
					return kraken_io()->format_bytes( $meta['original_size'] );
				}
			} else {
				return $original_size;
			}
		} else {
			return $original_size;
		}
	}

	/**
	 * Get image stats.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $post_id Attachment ID
	 * @return array $stats
	 */
	public function get_image_stats( $id ) {

		$type                = $this->options['api_lossy'];
		$optimize_main_image = $this->options['optimize_main_image'];

		$stats = [
			'id'           => $id,
			'type'         => $type,
			'is_image'     => false,
			'is_optimized' => false,
			'has_savings'  => true,
			'has_error'    => false,
			'show_button'  => false,
			'show_reset'   => false,
			'stats'        => [],
			'size'         => $this->get_original_size( $id ),
		];

		$image_url = wp_get_attachment_url( $id );
		$filename  = basename( $image_url );

		if ( ! wp_attachment_is_image( $id ) ) {
			return $stats;
		}

		$meta        = get_post_meta( $id, '_kraken_size', true );
		$thumbs_meta = get_post_meta( $id, '_kraked_thumbs', true );

		$stats['is_image']   = true;
		$stats['image_url']  = $image_url;
		$stats['filename']   = $filename;
		$stats['show_reset'] = $this->options['show_reset'];

		if ( ( isset( $meta['kraked_size'] ) && empty( $meta['no_savings'] ) ) || ! empty( $thumbs_meta ) ) {
			$stats['is_optimized'] = true;
			$stats['stats']        = $this->get_image_stats_summary( $id );

			if ( ! isset( $meta['kraked_size'] ) && $optimize_main_image ) {
				$stats['show_button'] = true;
			}
		} else {
			if ( ! empty( $meta['no_savings'] ) ) {
				$stats['has_savings'] = false;
			} elseif ( isset( $meta['error'] ) ) {
				$stats['has_error'] = $meta['error'];
			}
		}

		return $stats;
	}

	public function calculate_savings( $meta ) {

		if ( isset( $meta['original_size'] ) ) {

			$saved_bytes        = isset( $meta['saved_bytes'] ) ? $meta['saved_bytes'] : '';
			$savings_percentage = isset( $meta['savings_percent'] ) ? $meta['savings_percent'] : '';

			// convert old data format, where applicable
			if ( stripos( $saved_bytes, 'kb' ) !== false ) {
				$saved_bytes = kraken_io()->kb_string_to_bytes( $saved_bytes );
			} else {
				if ( ! $saved_bytes ) {
					$saved_bytes = '0 bytes';
				} else {
					$saved_bytes = kraken_io()->format_bytes( $saved_bytes );
				}
			}

			return [
				'saved_bytes'        => $saved_bytes,
				'savings_percentage' => $savings_percentage,
			];

		} elseif ( ! empty( $meta ) ) {
			$total_thumb_byte_savings  = 0;
			$total_thumb_size          = 0;
			$thumbs_savings_percentage = '';
			$total_thumbs_savings      = '';

			foreach ( $meta as $k => $v ) {
				$total_thumb_size         += $v['original_size'];
				$thumb_byte_savings        = $v['original_size'] - $v['kraked_size'];
				$total_thumb_byte_savings += $thumb_byte_savings;
			}

			$thumbs_savings_percentage = round( ( $total_thumb_byte_savings / $total_thumb_size * 100 ), 2 ) . '%';
			if ( $total_thumb_byte_savings ) {
				$total_thumbs_savings = kraken_io()->format_bytes( $total_thumb_byte_savings );
			} else {
				$total_thumbs_savings = '0 bytes';
			}

			return [
				'savings_percentage' => $thumbs_savings_percentage,
				'total_savings'      => $total_thumbs_savings,
			];
		}
	}

	/**
	 * Get image stats summary.
	 *
	 * @since  2.7
	 * @access public
	 * @param  int $post_id Attachment ID
	 * @return array $summary
	 */
	public function get_image_stats_summary( $id ) {
		$image_meta  = get_post_meta( $id, '_kraken_size', true );
		$thumbs_meta = get_post_meta( $id, '_kraked_thumbs', true );

		$total_original_size = 0;
		$total_saved_bytes   = 0;

		$total_savings_percentage = 0;

		$summary = [
			'percentage'              => 0,
			'total'                   => 0,
			'is_main_image_optimized' => false,
			'is_thumbs_optimized'     => false,
			'main_image_stats'        => [],
			'thumbs_count'            => 0,
			'thumbs_stats'            => [],
			'optimization_mode'       => false,
		];

		$main_image_optimized = ! empty( $image_meta ) && isset( $image_meta['type'] );
		$thumbs_optimized     = ! empty( $thumbs_meta ) && count( $thumbs_meta ) && isset( $thumbs_meta[0]['type'] );

		if ( $main_image_optimized ) {
			$type                               = $image_meta['type'];
			$summary['is_main_image_optimized'] = true;
			$summary['main_image_stats']        = $this->calculate_savings( $image_meta );
		}

		if ( $thumbs_optimized ) {
			$type                           = $thumbs_meta[0]['type'];
			$summary['is_thumbs_optimized'] = true;
			$summary['thumbs_stats']        = $this->calculate_savings( $thumbs_meta );
			$summary['thumbs_count']        = count( $thumbs_meta );
		}

		$summary['optimization_mode'] = ucfirst( $type );

		// backward compat
		if ( isset( $image_meta['original_size'] ) ) {

			$original_size = $image_meta['original_size'];

			if ( stripos( $original_size, 'kb' ) !== false ) {
				$total_original_size = ceil( floatval( $original_size ) * 1024 );
			} else {
				$total_original_size = (int) $original_size;
			}

			if ( isset( $image_meta['saved_bytes'] ) ) {
				$saved_bytes = $image_meta['saved_bytes'];
				if ( is_string( $saved_bytes ) ) {
					$total_saved_bytes = (int) ceil( floatval( $saved_bytes ) * 1024 );
				} else {
					$total_saved_bytes = $saved_bytes;
				}
			}
		}

		if ( ! empty( $thumbs_meta ) ) {
			$thumb_saved_bytes = 0;

			foreach ( $thumbs_meta as $k => $v ) {
				$total_original_size += $v['original_size'];
				$thumb_saved_bytes    = $v['original_size'] - $v['kraked_size'];
				$total_saved_bytes   += $thumb_saved_bytes;
			}
		}

		if ( $total_saved_bytes ) {

			$total_savings_percentage = round( ( $total_saved_bytes / $total_original_size * 100 ), 2 ) . '%';

			$summary['percentage'] = $total_savings_percentage;
			$summary['total']      = kraken_io()->format_bytes( $total_saved_bytes );
		}

		return $summary;
	}

}
