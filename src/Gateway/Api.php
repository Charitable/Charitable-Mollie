<?php
/**
 * Super-simple, minimum abstraction Mollie API wrapper.
 *
 * @package Charitable Mollie/Classes
 * @author  Eric Daams
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since   1.0.0
 * @version 1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway;

use \Charitable\Pro\Mollie\Mollie as Mollie;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class.
 *
 * @since 1.0.0
 */
class Api {

	/**
	 * The endpoint that we will call with our API requests.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	private $api_endpoint;

	/**
	 * The API key supplied by the user.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	private $api_key;

	/**
	 * Whether the API key was validated.
	 *
	 * @since 1.0.0
	 *
	 * @var   boolean
	 */
	private $api_key_validated;

	/**
	 * The response to the most recent request.
	 *
	 * @since 1.0.0
	 *
	 * @var   WP_Error|?
	 */
	private $last_response;

	/**
	 * Create a new instance.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean|null $test_mode Whether to explicitly get the test or live key. If left
	 *                                as null, this will return the key for the current mode.
	 */
	public function __construct( $test_mode = null ) {
		$this->test_mode    = is_null( $test_mode ) ? charitable_get_option( 'test_mode' ) : $test_mode;
		$this->api_key      = $this->get_api_key();
		$this->api_endpoint = 'https://api.mollie.com/v2';
	}

	/**
	 * Return the API key to use for the current mode.
	 *
	 * @since  1.0.0
	 *
	 * @return string|false
	 */
	public function get_api_key() {
		$setting_key = $this->test_mode ? 'test_api_key' : 'live_api_key';
		$api_key     = trim( charitable_get_option( array( 'gateways_mollie', $setting_key ) ) );

		if ( empty( $api_key ) ) {
			return false;
		}

		return $api_key;
	}

	/**
	 * Checks whether an API key is set.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function has_valid_api_key() {
		/* We're missing an API key. */
		if ( false === $this->api_key ) {
			return false;
		}

		/**
		 * If we have made an API call, we record whether there
		 * was an issue with using the API key. This will catch
		 * incorrect or invalid API keys.
		 */
		if ( isset( $this->api_key_validated ) ) {
			return $this->api_key_validated;
		}

		return true;
	}

	/**
	 * Perform a GET request to the Mollie API.
	 *
	 * @since  1.0.0
	 *
	 * @param  string  $method  The API endpoint we're calling.
	 * @param  mixed[] $args    Array of arguments.
	 * @param  int     $timeout Duration before request times out.
	 * @return mixed[]
	 */
	public function get( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'get', $method, $args, $timeout );
	}

	/**
	 * Perform a POST request to the Mollie API.
	 *
	 * @since   1.0.0
	 *
	 * @param  string  $method  The API endpoint we're calling.
	 * @param  mixed[] $args    Array of arguments.
	 * @param  int     $timeout Duration before request times out.
	 * @return mixed[]
	 */
	public function post( $method, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'post', $method, $args, $timeout );
	}

	/**
	 * Perform a PUT request.
	 *
	 * @since  2.0.0
	 *
	 * @param  string  $method  The API endpoint we're calling.
	 * @param  mixed[] $args    Array of arguments.
	 * @param  int     $timeout Duration before request times out.
	 * @return mixed
	 */
	public function put( $method, $args = array(), $timeout = 10 ) {
		$args['method'] = 'PUT';
		return $this->make_request( 'put', $method, $args, $timeout );
	}

	/**
	 * Performs the underlying HTTP request. Not very exciting.
	 *
	 * @since   1.0.0
	 *
	 * @param  string  $http_verb The HTTP verb to use: get, post, put, patch, delete.
	 * @param  string  $method    The API method to be called.
	 * @param  mixed[] $args      Associative array of parameters to be passed as the body of the request.
	 * @return false|mixed[] Associative array of decoded result. False if there was an error.
	 */
	public function make_request( $http_verb, $method, $args = array(), $timeout = 10 ) {
		if ( ! $this->has_valid_api_key() ) {
			return false;
		}

		$http_verb = strtoupper( $http_verb );
		$url       = $this->api_endpoint . '/' . $method;
		$body      = empty( $args ) ? '' : json_encode( $args );

		$request_args = array(
			'method'      => $http_verb,
			'timeout'     => $timeout,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'user-agent'  => 'Charitable Mollie/' . Mollie::VERSION . '; ' . home_url(),
			'body'        => $body,
			'headers'     => array(
				'content-type'  => 'application/json',
				'authorization' => 'Bearer ' . $this->api_key,
			),
		);

		switch ( $http_verb ) {
			case 'GET':
				$this->last_response = wp_remote_get( $url, $request_args );
				break;
			case 'POST':
				$this->last_response = wp_remote_post( $url, $request_args );
				break;
			default:
				$this->last_response = wp_remote_request( $url, $request_args );
		}

		if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
			error_log( var_export( $this->last_response, true ) );
		}

		/**
		 * If this is the first time we've called the API, check whether the API key is valid.
		 *
		 * We assume it is invalid if a WP_Error has been returned, or if a 401 response code
		 * was returned.
		 *
		 * @see https://developers.Mollie.com/docs/response
		 */
		if ( ! isset( $this->api_key_validated ) ) {
			$this->api_key_validated = $this->api_key_validated( $this->last_response );
		}

		if ( $this->is_failed_request() ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $this->last_response ) );
	}

	/**
	 * Return the response to the most recent request.
	 *
	 * @since  1.0.0
	 *
	 * @return WP_Error|?
	 */
	public function get_last_response() {
		return $this->last_response;
	}

	/**
	 * Checks whether the API key is valid, based on API call response.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	private function api_key_validated() {
		return '401' !== wp_remote_retrieve_response_code( $this->last_response );
	}

	/**
	 * Returns whether the most recent request failed.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	private function is_failed_request() {
		return is_wp_error( $this->last_response ) || '2' !== substr( wp_remote_retrieve_response_code( $this->last_response ), 0, 1 );
	}
}
