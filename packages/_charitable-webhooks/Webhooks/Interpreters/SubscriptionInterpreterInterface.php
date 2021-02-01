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
 * Subscription Interpreter interface.
 */
Interface SubscriptionInterpreterInterface extends DonationInterpreterInterface {

	/**
	 * Get the Recurring Donation object.
	 *
	 * @since  1.0.0
	 *
	 * @return Charitable_Recurring_Donation|false Returns the Recurring Donation if one matches the webhook.
	 *                                             If not, returns false.
	 */
	public function get_recurring_donation();

	/**
	 * Get the subscription ID used in the payment gateway.
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|false
	 */
	public function get_gateway_subscription_id();

	/**
	 * Get the URL to access the subscription in the gateway's dashboard.
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|false
	 */
	public function get_gateway_subscription_url();

	/**
	 * Return the Subscription status based on the webhook event.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_subscription_status();
}
