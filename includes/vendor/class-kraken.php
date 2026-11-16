<?php

class Kraken_IO_Kraken {
    protected $auth = array();
    private $timeout;
    private $proxyParams;

    public function __construct($key = '', $secret = '', $timeout = 30, $proxyParams = array()) {
        $this->auth = array(
            "auth" => array(
                "api_key" => $key,
                "api_secret" => $secret
            )
        );
        $this->timeout = $timeout;
        $this->proxyParams = $proxyParams;
    }

    public function url($opts = array()) {
        $data = json_encode(array_merge($this->auth, $opts));
        $response = $this->request($data, 'https://api.kraken.io/v1/url', 'url');

        return $response;
    }

    public function upload($opts = array()) {
        if (!isset($opts['file'])) {
            return array(
                "success" => false,
                "error" => "File parameter was not provided"
            );
        }

        if (!file_exists($opts['file'])) {
            return array(
                "success" => false,
                "error" => 'File `' . $opts['file'] . '` does not exist'
            );
        }

        $file = $opts['file'];
        unset($opts['file']);

        $data = array(
            "file" => $file,
            "data" => json_encode(array_merge($this->auth, $opts))
        );

        $response = $this->request($data, 'https://api.kraken.io/v1/upload', 'upload');

        return $response;
    }

    public function status() {
        $data = array('auth' => array(
            'api_key' => $this->auth['auth']['api_key'],
            'api_secret' => $this->auth['auth']['api_secret']
        ));

        $response = $this->request(json_encode($data), 'https://api.kraken.io/user_status', 'url');

        return $response;
    }

    /**
     * Perform the request through the WordPress HTTP API (wp_remote_post) rather
     * than raw cURL, so the plugin works on hosts where the PHP cURL extension
     * is not installed (WP_Http falls back to the streams transport) and never
     * fatals on a missing curl_init().
     *
     * @param  mixed  $data JSON string ("url" type) or array with file+data ("upload").
     * @param  string $url  Kraken.io endpoint.
     * @param  string $type "url" or "upload".
     * @return array
     */
    private function request($data, $url, $type) {
        $args = array(
            'timeout'    => $this->timeout,
            'sslverify'  => true,
            'user-agent' => 'Kraken.io WordPress plugin',
            'headers'    => array(),
            'body'       => '',
        );

        if ($type === 'upload') {
            // Build a multipart/form-data body by hand: a "data" JSON field and
            // the binary "file" field, exactly as the Kraken upload API expects.
            $boundary = 'kraken' . md5(uniqid('', true));
            $file     = $data['file'];
            $contents = file_get_contents($file);

            if (false === $contents) {
                return array(
                    'success' => false,
                    'error'   => 'Could not read the image file for upload.'
                );
            }

            $eol  = "\r\n";
            $body = '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="data"' . $eol . $eol;
            $body .= $data['data'] . $eol;
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . $eol;
            $body .= 'Content-Type: application/octet-stream' . $eol . $eol;
            $body .= $contents . $eol;
            $body .= '--' . $boundary . '--' . $eol;

            $args['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
            $args['body']                    = $body;
        } else {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body']                    = $data;
        }

        if (isset($this->proxyParams['proxy'])) {
            $args['proxy'] = $this->proxyParams['proxy'];
        }

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message()
            );
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($decoded)) {
            return array(
                'success' => false,
                'error'   => 'Invalid response from the Kraken.io API.'
            );
        }

        return $decoded;
    }
}
