<?php
/**
 * The class responsible for helping us understand the response to a Mollie payment request.
 *
 * @package   Charitable Mollie/Classes/\Charitable\Pro\Mollie\Gateway\Payment\Response
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Payment;

use Charitable\Gateways\Payment\ResponseInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Payment\Response' ) ) :

	/**
	 * Response object after making payment request.
	 *
	 * @since 1.0.0
	 */
	class Response implements ResponseInterface {

		/**
		 * The response data.
		 *
		 * @since 1.0.0
		 *
		 * @var   object
		 */
		private $response;

		/**
		 * Set up the response object.
		 *
		 * @since 1.0.0
		 *
		 * @param object $response The response data.
		 */
		public function __construct( $response ) {
			$this->response = $response;
		}

		/**
		 * Return the gateway transaction id.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_gateway_transaction_id() {
			return $this->response->id;
		}

		/**
		 * Return the gateway transaction url
		 *
		 * @since  1.0.0
		 *
		 * @return string|false
		 */
		public function get_gateway_transaction_url() {
			return $this->response->_links->dashboard->href;
		}

		/**
		 * Returns whether the payment requires some further action.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function payment_requires_action() {
			return false;
		}

		/**
		 * Get any data that is needed to perform the required action.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_required_action_data() {
			return array();
		}

		/**
		 * Whether the payment requires a redirect to a payment page.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function payment_requires_redirect() {
			return true;
		}

		/**
		 * The URL to redirect the donor to, or null if not required.
		 *
		 * @since  1.0.0
		 *
		 * @return string|null
		 */
		public function get_redirect() {
			return $this->response->_links->checkout->href;
		}

		/**
		 * Returns whether the payment failed.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function payment_failed() {
			return 'failed' === $this->response->status;
		}

		/**
		 * Returns whether the payment was completed.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function payment_completed() {
			return 'paid' === $this->response->status;
		}

		/**
		 * Returns whether the payment was cancelled.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function payment_cancelled() {
			return in_array(
				$this->response->status,
				array( 'canceled', 'expired' ),
				true
			);
		}

		/**
		 * Returns any log messages to be added for the payment.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_logs() {
			return array();
		}

		/**
		 * Returns any meta data to be recorded for the payment, beyond
		 * the gateway transaction id.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_meta() {
			return array();
		}
	}

endif;
