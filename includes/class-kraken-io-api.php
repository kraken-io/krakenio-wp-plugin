<?php
/**
* Kraken IO API.
*
* @package Kraken_IO/Classes
* @since   2.7
*/

defined( 'ABSPATH' ) || exit;

class Kraken_IO_API {

	protected $auth     = [];
	protected $endpoint = 'https://api.kraken.io/';

	public function __construct( $key = '', $secret = '' ) {
		$this->auth = [
			'auth' => [
				'api_key'    => $key,
				'api_secret' => $secret,
			],
		];
	}

	private function request( $data, $url ) {

		if ( empty( $data ) ) {
			return false;
		}

		$data = [
			'body' => wp_json_encode( $data ),
		];

		$response = wp_remote_post( $url, $data );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$http_code = $response['response']['code'];
		$result    = json_decode( $response['body'], true );

		if ( 200 !== $http_code ) {
			return false;
		}

		if ( isset( $result['errors'] ) ) {
			return false;
		}

		return $result;
	}

	public function url( $opts = [] ) {
		$data = array_merge( $this->auth, $opts );
		return $this->request( $data, $this->endpoint . '/v1/url' );
	}

	public function upload( $opts = [] ) {
		if ( ! isset( $opts['file'] ) ) {
			wp_send_json_error(
				[
					'type' => 'file_not_provided',
				]
			);
		}

		if ( ! file_exists( $opts['file'] ) ) {
			wp_send_json_error(
				[
					'type' => 'file_not_found',
				]
			);
		}

		$file = '@' . $opts['file'];

		$data = [
			'file' => $file,
			'data' => array_merge( $this->auth, $opts ),
		];

		return $this->request( $data, $this->endpoint . 'v1/upload' );
	}

	public function status() {
		return $this->request( $this->auth, $this->endpoint . 'user_status' );
	}
}
