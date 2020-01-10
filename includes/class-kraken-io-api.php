<?php
/**
* Kraken IO API.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_API {

	protected $auth        = [];
	protected $endpoint    = 'https://api.kraken.io/';
	protected $api_version = 'v1';
	protected $timeout     = 30;

	public function __construct( $key = '', $secret = '' ) {
		$this->auth = [
			'auth' => [
				'api_key'    => $key,
				'api_secret' => $secret,
			],
		];
	}

	private function return_error( $data = [] ) {
		return wp_parse_args( [ 'success' => false ], $data );
	}

	private function request( $data, $url, $is_json = true ) {

		if ( empty( $data ) ) {
			return $this->return_error(
				[
					'type' => 'empty_data',
				]
			);
		}

		// @codingStandardsIgnoreStart
		$curl = curl_init();

		if ( $is_json ) {
			curl_setopt(
				$curl,
				CURLOPT_HTTPHEADER,
				[
					'Content-Type: application/json',
				]
			);
		}

		curl_setopt( $curl, CURLOPT_URL, $url );

		// Force continue-100 from server
		curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36' );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $curl, CURLOPT_FAILONERROR, 0 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 1 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, $this->timeout );

		$response  = json_decode( curl_exec( $curl ), true );
		$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		if ( 200 !== $http_code ) {
			return $this->return_error(
				[
					'type' => 'wrong_status_code',
				]
			);
		}

		if ( null === $response ) {
			return $this->return_error(
				[
					'type' => 'empty_response',
				]
			);
		}

		curl_close( $curl );
		// @codingStandardsIgnoreEnd

		return $response;
	}

	public function url( $opts = [] ) {
		$data = wp_json_encode( array_merge( $this->auth, $opts ) );
		return $this->request( $data, $this->endpoint . $this->api_version . '/url' );
	}

	public function upload( $opts = [] ) {
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

		if ( class_exists( 'CURLFile' ) ) {
			$file = new CURLFile( $opts['file'] );
		} else {
			$file = '@' . $opts['file'];
		}

		unset( $opts['file'] );

		$data = [
			'file' => $file,
			'data' => wp_json_encode( array_merge( $this->auth, $opts ) ),
		];

		return $this->request( $data, $this->endpoint . $this->api_version . '/upload', false );
	}

	public function status() {
		return $this->request( wp_json_encode( $this->auth ), $this->endpoint . 'user_status' );
	}
}
