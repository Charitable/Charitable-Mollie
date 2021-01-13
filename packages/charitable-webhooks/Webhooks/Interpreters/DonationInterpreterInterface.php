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

namespace Charitable\Webhooks\Interpreters;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Donation Interpreter interface.
 */
Interface DonationInterpreterInterface {

	/**
	 * Get the donation object.
	 *
	 * @since  1.7.0
	 *
	 * @return Charitable_Donation|false Returns the donation if one matches the webhook.
	 *                                   If not, returns false.
	 */
	public function get_donation();

	/**
	 * Get the type of event described by the webhook.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_event_type();

	/**
	 * Get the refunded amount.
	 *
	 * @since  1.7.0
	 *
	 * @return float|false The amount to be refunded, or false if this is not a refund.
	 */
	public function get_refund_amount();

	/**
	 * Get a log message to include when adding the refund.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_refund_log_message();

	/**
	 * Return the gateway transaction ID.
	 *
	 * @since  1.7.0
	 *
	 * @return string|false The gateway transaction ID if available, otherwise false.
	 */
	public function get_gateway_transaction_id();

	/**
	 * Return the gateway transaction URL.
	 *
	 * @since  1.7.0
	 *
	 * @return string|false The URL if available, otherwise false.
	 */
	public function get_gateway_transaction_url();

	/**
	 * Return the donation status based on the webhook event.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_donation_status();

	/**
	 * Return an array of log messages to update the donation.
	 *
	 * @since  1.7.0
	 *
	 * @return array
	 */
	public function get_logs();

	/**
	 * Return an array of meta data to add/update for the donation.
	 *
	 * @since  1.7.0
	 *
	 * @return array
	 */
	public function get_meta();

	/**
	 * Get the response message.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_response_message();

	/**
	 * Get the response HTTP status.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_response_status();
}
