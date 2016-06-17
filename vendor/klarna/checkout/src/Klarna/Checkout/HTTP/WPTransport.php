<?php
/**
 * Copyright 2015 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * File containing the Klarna_Checkout_HTTP_WPTransport class
 *
 * PHP version 5.3
 *
 * @category   Payment
 * @package       Payment_Klarna
 * @subpackage HTTP
 * @author       Klarna <support@klarna.com>
 * @copyright  2015 Klarna AB AB
 * @license       http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */

/**
 * Klarna HTTP transport implementation for cURL
 *
 * @category   Payment
 * @package       Payment_Klarna
 * @subpackage HTTP
 * @author       Klarna <support@klarna.com>
 * @copyright  2015 Klarna AB
 * @license       http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link       http://developers.klarna.com/
 */
class Klarna_Checkout_HTTP_WPTransport implements Klarna_Checkout_HTTP_TransportInterface {
	const DEFAULT_TIMEOUT = 10;

	/**
	 * Number of seconds before the connection times out.
	 *
	 * @var int
	 */
	protected $timeout;

	/**
	 * Initializes a new instance of the HTTP cURL class.
	 */
	public function __construct() {
		$this->timeout = self::DEFAULT_TIMEOUT;
	}

	/**
	 * Sets the number of seconds until a connection times out.
	 *
	 * @param int $timeout number of seconds
	 *
	 * @return void
	 */
	public function setTimeout( $timeout ) {
		$this->timeout = intval( $timeout );
	}

	/**
	 * Gets the number of seconds before the connection times out.
	 *
	 * @return int timeout in number of seconds
	 */
	public function getTimeout() {
		return $this->timeout;
	}

	/**
	 * Performs a HTTP request.
	 *
	 * @param  Klarna_Checkout_HTTP_Request $request the HTTP request to send.
	 *
	 * @throws Klarna_Checkout_ConnectionErrorException Thrown for unspecified
	 *                                                    network or hardware issues.
	 * @return Klarna_Checkout_HTTP_Response
	 */
	public function send( Klarna_Checkout_HTTP_Request $request ) {
		// Set arguments for wp_remote_request
		$args = array(
			'method'  => $request->getMethod(),
			'headers' => $request->getHeaders(),
			'body'    => $request->getData(),
			'timeout' => apply_filters( 'kco_http_request_timeout', $this->getTimeout() ),
		);

		// For GET requests we need to get Klarna order URI, set in WC session
		if ( 'GET' == $request->getMethod() && WC()->session->get( 'klarna_request_uri' ) ) {
			$req_url = WC()->session->get( 'klarna_request_uri' );
		} else {
			$req_url = $request->getURL();
		}

		$my_response = wp_remote_request( $req_url, $args );

		// Set order URI as session value for GET request
		if ( 'POST' == $request->getMethod() ) {
			if ( class_exists( 'WC_Session' ) ) {
				WC()->session->__unset( 'klarna_request_uri' );

				if ( is_wp_error( $my_response ) ) {
					error_log( var_export( $my_response, true ) );
				} elseif ( isset( $my_response['headers']['location'] ) ) {
					$klarna_request_uri = $my_response['headers']['location'];
				} else {
					$klarna_request_uri = $request->getURL();
				}

				WC()->session->set( 'klarna_request_uri', $klarna_request_uri );
			}
		}

		if ( ! is_wp_error( $my_response ) ) {
			$response = new Klarna_Checkout_HTTP_Response( $request, $request->getHeaders(), intval( $my_response['response']['code'] ), strval( $my_response['body'] ) );
			return $response;
		} else {
			return $my_response;
		}

	}

	/**
	 * Creates a HTTP request object.
	 *
	 * @param  string $url the request URL.
	 *
	 * @throws InvalidArgumentException If the specified argument
	 *                                    is not of type string.
	 * @return Klarna_Checkout_HTTP_Request
	 */
	public function createRequest( $url ) {
		return new Klarna_Checkout_HTTP_Request( $url );
	}
}