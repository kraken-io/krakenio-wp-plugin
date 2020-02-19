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
	protected $has_auth = false;

	public function __construct() {
		$options = kraken_io()->get_options();
		$key     = $options['api_key'];
		$secret  = $options['api_secret'];

		if ( '' !== $key && '' !== $secret ) {
			$this->has_auth = true;
		}

		$this->kraken = new Kraken( $key, $secret, 10 );
	}

	private function return_error( $data = [] ) {
		return wp_parse_args( [ 'success' => false ], $data );
	}

	private function return_no_api_error() {
		return $this->return_error(
			[
				'type' => 'no_auth',
			]
		);
	}

	public function has_auth() {
		return $this->has_auth;
	}

	public function url( $opts = [] ) {

		if ( ! $this->has_auth() ) {
			return $this->return_no_api_error();
		}

		return $this->kraken->url( $opts );
	}

	public function upload( $opts = [] ) {

		if ( ! $this->has_auth() ) {
			return $this->return_no_api_error();
		}

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

		if ( ! $this->has_auth() ) {
			return $this->return_no_api_error();
		}

		return $this->kraken->status();
	}
}
