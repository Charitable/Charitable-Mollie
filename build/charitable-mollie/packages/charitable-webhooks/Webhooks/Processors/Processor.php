<?php
/**
 * description
 *
 * @package   package/Classes/class
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     version
 * @version   version
 */

namespace Charitable\Webhooks\Processors;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processor interface.
 */
abstract class Processor implements ProcessorInterface {

	/**
	 * Response message.
	 *
	 * @since 1.7.0
	 *
	 * @var   string
	 */
	protected $response_message;

	/**
	 * HTTP status to use for response message.
	 *
	 * @since 1.7.0
	 *
	 * @var   int
	 */
	protected $response_status;

	/**
	 * Process the webhook.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean|void
	 */
	abstract public function process();

	/**
	 * Return the HTTP status to send for a processed event.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_response_status() {
		return $this->response_status;
	}

	/**
	 * Response text to send for a processed event.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_response_message() {
		return $this->response_message;
	}

	/**
	 * Set the response to send.
	 *
	 * @since  1.7.0
	 *
	 * @param  string $message     Response message.
	 * @param  int    $http_status HTTP status to send.
	 * @return void
	 */
	protected function set_response( $message, $http_status = 200 ) {
		$this->response_message = $this->interpreter->get_response_message();

		if ( ! $this->response_message ) {
			$this->response_message = $message;
		}

		$this->response_status = $this->interpreter->get_response_status();

		if ( ! $this->response_status ) {
			$this->response_status = $http_status;
		}
	}
}
