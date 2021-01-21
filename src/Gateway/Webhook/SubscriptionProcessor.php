<?php
/**
 * Process incoming Mollie webhooks.
 *
 * @package   Charitable Mollie/Classes/\Charitable\Pro\Mollie\Gateway\Webhook\Processor
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Webhook;

use Charitable\Pro\Mollie\Gateway\Api;
use Charitable\Webhooks\Processors\SubscriptionProcessor as BaseSubscriptionProcessor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Webhook\Processor' ) ) :

	/**
	 * Subscription webhook processor.
	 *
	 * @since 1.0.0
	 */
	class SubscriptionProcessor extends BaseSubscriptionProcessor {

		/**
		 * Process first payment.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function process_first_payment() {
			/* Update the initial payment and mark it as complete. */
			$this->process_completed_payment();

			/* Create the subscription using the mandate. */
			$payment = $this->interpreter->get_payment();

			/* Set up subscription arguments to send to Mollie. */
			$subscription_args = array(
				'amount'      => array(
					'currency' => charitable_get_currency(),
					'value'    => number_format( $this->donation->get_total_donation_amount( true ), 2 ),
				),
				'interval'    => $this->get_subscription_interval(),
				'description' => $this->recurring_donation->get_campaigns_donated_to(),
				'mandateId'   => $payment->mandateId,
				'metadata'    => array(
					'recurring_donation_id' => $this->recurring_donation->ID,
				),
			);

			if ( ! empty( $this->recurring_donation->get_donation_length() ) ) {
				$subscription_args['length'] = $this->recurring_donation->get_donation_length();
			}

			$api          = new Api( $this->donation->get( 'test_mode' ) );
			$subscription = $api->post( 'customers/' . $payment->customerId . '/subscriptions', $subscription_args );

			/* Activate the subscription. */
			$this->recurring_donation->renew();

			$this->save_gateway_subscription_data();
			$this->update_meta();
			$this->update_logs();

			$this->set_response( __( 'Subscription Webhook: First payment processed', 'charitable' ) );

			return true;
		}

		/**
		 * Get the subscription interval to use.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_subscription_interval() {
			switch ( $this->recurring_donation->get_donation_period() ) {
				case 'day':
					return '1 day';

				case 'week':
					return '7 days';

				case 'month':
					return '1 months';

				case 'quarter':
					return '3 months';

				case 'semiannual':
					return '6 months';

				case 'year':
					return '12 months';
			}
		}

	}

endif;