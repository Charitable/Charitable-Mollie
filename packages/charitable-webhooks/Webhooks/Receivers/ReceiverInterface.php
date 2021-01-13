<?php
/**
 * description
 *
 * @package   package/Classes/class
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     version
 * @version   version
 */

namespace Charitable\Webhooks\Receivers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interpreter interface.
 */
Interface ReceiverInterface {

	/**
	 * Check whether this is a valid webhook.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean
	 */
	public function is_valid_webhook();

	/**
	 * Get the Processor to use for the webhook event.
	 *
	 * @since  1.7.0
	 *
	 * @return Processor
	 */
	public function get_processor();

	/**
	 * Return the Interpreter object to use for donation webhooks.
	 *
	 * @since  1.7.0
	 *
	 * @return Interpreter
	 */
	public function get_interpreter();

	/**
	 * Return the HTTP status to send for an invalid event.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_invalid_response_status();

	/**
	 * Response text to send for an invalid event.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_invalid_response_message();
}
