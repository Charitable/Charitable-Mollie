<?php
/**
 * Interpret incoming Mollie webhooks.
 *
 * @package   Charitable Mollie/Classes/\Charitable\Pro\Mollie\Gateway\Webhook\Interpreter
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Webhook;

use Charitable\Pro\Mollie\Gateway\Api;
use Charitable\Webhooks\Receivers\ReceiverInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Webhook\Receiver' ) ) :

	/**
	 * \Charitable\Pro\Mollie\Gateway\Webhook\Receiver
	 *
	 * @since 1.0.0
	 */
	class Receiver implements ReceiverInterface {

		/**
		 * Get the Processor to use for the webhook event.
		 *
		 * @since  1.0.0
		 *
		 * @return Processor
		 */
		public function get_processor() {
			return new DonationProcessor( $this->get_interpreter() );
		}

		/**
		 * Return the DonationIntepreter object to use for donation webhooks.
		 *
		 * @since  1.0.0
		 *
		 * @return DonationInterpreter
		 */
		public function get_interpreter() {
			return new DonationInterpreter();
		}

		/**
		 * Return the HTTP status to send for an invalid event.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_invalid_response_status() {
			return 500;
		}

		/**
		 * Response text to send for an invalid event.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_invalid_response_message() {
			return __( 'Invalid request', 'charitable-mollie' );
		}

		/**
		 * Check whether this is a valid webhook.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_valid_webhook() {
			return ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' === $_SERVER['REQUEST_METHOD'];
		}
	}

endif;
