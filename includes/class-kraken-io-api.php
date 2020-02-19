<?php
/**
* Kraken IO API.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_API {

	protected $kraken;

	public function __construct() {
		$options = kraken_io()->get_options();
		$key     = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$secret  = isset( $options['api_secret'] ) ? $options['api_secret'] : '';

		$this->kraken = new Kraken( $key, $secret );
	}

	private function return_error( $data = [] ) {
		return wp_parse_args( [ 'success' => false ], $data );
	}

	public function url( $opts = [] ) {
		return $this->kraken->status( $opts );
	}

	public function upload( $opts = [] ) {

		if ( empty( $opts ) ) {
			return $this->return_error(
				[
					'type' => 'empty_data',
				]
			);
		}

		if ( ! isset( $opts['file'] ) ) {
			return $this->return_error(
				[
					'type' => 'file_not_provided',
				]
			);
		}

		if ( ! file_exists( $opts['file'] ) ) {
			return $this->return_error(
				[
					'type' => 'file_not_found',
				]
			);
		}

		$response = $this->kraken->upload( $opts );

		return $response;
	}

	public function status() {
		return $this->kraken->status();
	}
}
