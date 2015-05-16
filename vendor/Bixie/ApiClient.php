<?php
/* *
 *	Bixie Printshop
 *  HttpCurl.php
 *	Created on 16-5-2015 22:58
 *  @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 *  @license     GNU General Public License version 2 or later; see LICENSE
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */


namespace Bixie;

use BixieLime\ApiException;

class ApiClient {
	/**
	 * @var string
	 */
	protected $endpoint;
	/**
	 * @var string
	 */
	protected $publickey;
	/**
	 * @var string
	 */
	protected $secret;
	/**
	 * @var    array
	 */
	protected $options;

	/**
	 * Constructor. CURLOPT_FOLLOWLOCATION must be disabled when open_basedir or safe_mode are enabled.
	 * @param  string $endpoint
	 * @param  string $publickey
	 * @param  string $secret
	 * @param   array $options
	 * @throws ApiException
	 */
	public function __construct ($endpoint, $publickey, $secret, $options = []) {
		if (!function_exists('curl_init') || !is_callable('curl_init')) {
			throw new ApiException('Cannot use a cURL transport when curl_init() is not available.');
		}
		$this->endpoint = $endpoint;
		$this->publickey = $publickey;
		$this->secret = $secret;
		$this->options = $options;
	}

	/**
	 * @param string $address
	 * @param array $params
	 * @param array $data
	 * @param array $headers
	 * @return HttpResponse
	 * @throws ApiException
	 */
	public function get ($address, array $params = [], array $data = [], array $headers = []) {
		$uri = $this->endpoint . $address . implode('/', $params);
		return $this->request('get', $uri, $data, array_merge([
			'Publickey' => $this->publickey,
			'Seal' => $this->getSeal($headers, $params, $data)
		], $headers));
	}

	/**
	 * @param array  $headers
	 * @param array  $params
	 * @param array $payload
	 * @return bool
	 * @throws ApiException
	 */
	public function getSeal ($headers = [], $params = [], $payload = []) {
		return (new Authenticate(null, $headers, $params, $payload))->calculateSeal($this->publickey, $this->secret);
	}

	/**
	 * Send a request to the server and return a JHttpResponse object with the response.
	 * @param   string  $method    The HTTP method for sending the request.
	 * @param   string  $uri       The URI to the resource to request.
	 * @param   mixed   $data      Either an associative array or a string to be sent with the request.
	 * @param   array   $headers   An array of request headers to send with the request.
	 * @param   integer $timeout   Read timeout in seconds.
	 * @param   string  $userAgent The optional user agent string to send with the request.
	 * @return  HttpResponse
	 * @since   11.3
	 * @throws  ApiException
	 */
	public function request ($method, $uri, $data = null, array $headers = null, $timeout = null, $userAgent = null) {
		// Setup the cURL handle.
		$ch = curl_init();

		// Set the request method.
		switch (strtoupper($method)) {
			case 'GET':
				$options[CURLOPT_HTTPGET] = true;
				break;

			case 'POST':
				$options[CURLOPT_POST] = true;
				break;

			case 'PUT':
				$options[CURLOPT_PUT] = true;
				break;

			default:
				$options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
				break;
		}

		// Don't wait for body when $method is HEAD
		$options[CURLOPT_NOBODY] = ($method === 'HEAD');

		// Initialize the certificate store
		$options[CURLOPT_CAINFO] = !empty($this->options['curl.certpath']) ? $this->options['curl.certpath'] : __DIR__ . '/cacert.pem';

		// If data exists let's encode it and make sure our Content-type header is set.
		if (!empty($data)) {
			// If the data is a scalar value simply add it to the cURL post fields.
			if (is_scalar($data) || (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'multipart/form-data') === 0)) {
				$options[CURLOPT_POSTFIELDS] = $data;
			} // Otherwise we need to encode the value first.
			else {
				$options[CURLOPT_POSTFIELDS] = http_build_query($data);
			}

			if (!isset($headers['Content-Type'])) {
				$headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
			}

			// Add the relevant headers.
			if (is_scalar($options[CURLOPT_POSTFIELDS])) {
				$headers['Content-Length'] = strlen($options[CURLOPT_POSTFIELDS]);
			}
		}

		// Build the headers string for the request.
		$headerArray = array();

		if (isset($headers)) {
			foreach ($headers as $key => $value) {
				$headerArray[] = $key . ': ' . $value;
			}
			// Add the headers string into the stream context options array.
			$options[CURLOPT_HTTPHEADER] = $headerArray;
		}

		//debug cookie
		if (!empty($this->options['debug'])) {
			$options[CURLOPT_COOKIE] = 'XDEBUG_SESSION=PHPSTORM;';
		}


		// If an explicit timeout is given user it.
		if (isset($timeout)) {
			$options[CURLOPT_TIMEOUT] = (int)$timeout;
			$options[CURLOPT_CONNECTTIMEOUT] = (int)$timeout;
		}

		// If an explicit user agent is given use it.
		if (isset($userAgent)) {
			$options[CURLOPT_USERAGENT] = $userAgent;
		}

		// Set the request URL.
		$options[CURLOPT_URL] = $uri;

		// We want our headers. :-)
		$options[CURLOPT_HEADER] = true;

		// Return it... echoing it would be tacky.
		$options[CURLOPT_RETURNTRANSFER] = true;

		// Override the Expect header to prevent cURL from confusing itself in its own stupidity.
		// Link: http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
		$options[CURLOPT_HTTPHEADER][] = 'Expect:';

		/*
		 * Follow redirects if server config allows
		 * @deprecated  safe_mode is removed in PHP 5.4, check will be dropped when PHP 5.3 support is dropped
		 */
		if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
			$options[CURLOPT_FOLLOWLOCATION] = !empty($this->options['curl.follow_location']);
		}

		// Set the cURL options.
		curl_setopt_array($ch, $options);

		// Execute the request and close the connection.
		$content = curl_exec($ch);

		// Check if the content is a string. If it is not, it must be an error.
		if (!is_string($content)) {
			$message = curl_error($ch);

			if (empty($message)) {
				// Error but nothing from cURL? Create our own
				$message = 'No HTTP response received';
			}

			throw new ApiException($message);
		}

		// Get the request information.
		$info = curl_getinfo($ch);

		// Close the connection.
		curl_close($ch);

		return $this->getResponse($content, $info);
	}

	/**
	 * Method to get a response object from a server response.
	 * @param   string $content   The complete server response, including headers
	 *                            as a string if the response has no errors.
	 * @param   array  $info      The cURL request information.
	 * @return  HttpResponse
	 * @since   11.3
	 * @throws  ApiException
	 */
	protected function getResponse ($content, $info) {
		// Create the response object.
		$return = new HttpResponse;

		// Get the number of redirects that occurred.
		$redirects = isset($info['redirect_count']) ? $info['redirect_count'] : 0;

		/*
		 * Split the response into headers and body. If cURL encountered redirects, the headers for the redirected requests will
		 * also be included. So we split the response into header + body + the number of redirects and only use the last two
		 * sections which should be the last set of headers and the actual body.
		 */
		$response = explode("\r\n\r\n", $content, 2 + $redirects);

		// Set the body and data for the response.
		$return->body = array_pop($response);
		$return->data = json_decode($return->body, true);

		// Get the last set of response headers as an array.
		$headers = explode("\r\n", array_pop($response));

		// Get the response code from the first offset of the response headers.
		preg_match('/[0-9]{3}/', array_shift($headers), $matches);

		$code = count($matches) ? $matches[0] : null;

		if (is_numeric($code)) {
			$return->code = (int)$code;
		} // No valid response code was detected.
		else {
			throw new ApiException('No HTTP response code found.');
		}

		// Add the response headers to the response object.
		foreach ($headers as $header) {
			$pos = strpos($header, ':');
			$return->headers[trim(substr($header, 0, $pos))] = trim(substr($header, ($pos + 1)));
		}

		return $return;
	}

	/**
	 * Method to check if HTTP transport cURL is available for use
	 * @return boolean true if available, else false
	 * @since   12.1
	 */
	public static function isSupported () {
		return function_exists('curl_version') && curl_version();
	}

}

class HttpResponse
{
	/**
	 * @var    integer  The server response code.
	 */
	public $code;
	/**
	 * @var    array  Response headers.
	 */
	public $headers = [];
	/**
	 * @var    string  Server response body.
	 */
	public $body;
	/**
	 * @var    array  JSONdecode body.
	 */
	public $data = [];
}
