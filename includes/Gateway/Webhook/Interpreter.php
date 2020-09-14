<?php
/**
 * Interpret incoming Mollie webhooks.
 *
 * @package   Charitable Mollie/Classes/\Charitable\Pro\Mollie\Gateway\Webhook\Interpreter
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Webhook;

use Charitable\Pro\Mollie\Gateway\Api as Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Webhook\Interpreter' ) ) :

	/**
	 * \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter
	 *
	 * @since 1.0.0
	 */
	class Interpreter implements \Charitable_Webhook_Interpreter_Interface, \Charitable_Webhook_Intepreter_Donations_Interface {

		/**
		 * Valid webhook.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		private $valid;

		/**
		 * The response message to send.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $response;

		/**
		 * The donation ID.
		 *
		 * @since 1.0.0
		 *
		 * @var   int
		 */
		private $donation_id;

		/**
		 * The donation object.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Donation
		 */
		private $donation;

		/**
		 * The payment object from Mollie.
		 *
		 * @since 1.0.0
		 *
		 * @var   object
		 */
		private $payment;

		/**
		 * The parsed data.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $data;

		/**
		 * Set up interpreter object.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->parse_request();
		}

		/**
		 * Get class properties.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $prop The property to retrieve.
		 * @return mixed
		 */
		public function __get( $prop ) {
			return $this->$prop;
		}

		/**
		 * Check whether this is a valid webhook.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_valid_webhook() {
			return $this->valid;
		}

		/**
		 * Check whether there is a processor to use for the webhook source.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_processor() {
			return false;
		}

		/**
		 * Get the processor to use for the webhook source.
		 *
		 * @since  1.0.0
		 *
		 * @return false|Charitable_Webhook_Processor
		 */
		public function get_processor() {
			return false;
		}

		/**
		 * Get the subject of this webhook event. The only
		 * webhook event subject currently handled by Charitable
		 * core is a donations.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_event_subject() {
			if ( isset( $this->payment->subscriptionId ) ) {
				return 'subscription';
			}

			return 'donation';
		}

		/**
		 * Return the interpreter object to use for donation webhooks.
		 *
		 * @since  1.0.0
		 *
		 * @return \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter|false
		 */
		public function get_donations_interpreter() {
			return $this;
		}

		/**
		 * Get the donation object.
		 *
		 * @since  1.7.0
		 *
		 * @return Charitable_Donation|false Returns the donation if one matches the webhook.
		 */
		public function get_donation() {
			if ( ! isset( $this->donation ) ) {
				/* The donation ID needs to match a donation post type. */
				if ( Charitable::DONATION_POST_TYPE !== get_post_type( $this->donation_id ) ) {
					return false;
				}

				$this->donation = charitable_get_donation( $this->donation_id );

				if ( 'mollie' !== $this->donation->get_gateway() ) {
					$this->set_response( __( 'Incorrect gateway', 'charitable-mollie' ) );
					$this->donation = false;
				}
			}

			return $this->donation;
		}

		/**
		 * Get the Mollie payment object.
		 *
		 * @since  1.0.0
		 *
		 * @return object|false Returns the payment object if one exists.
		 */
		public function get_payment() {
			if ( ! isset( $this->payment ) ) {
				$api = new Api( $this->donation->get( 'test_mode' ) );
				$this->payment = $api->get( 'payments/'. $this->data['id'] . '?embed=refunds' );
				error_log( var_export( $this->payment, true ) );
			}

			return $this->payment;
		}

		/**
		 * Get the type of event described by the webhook.
		 *
		 * @since  1.7.0
		 *
		 * @return string
		 */
		public function get_event_type() {
			if ( $this->get_refund_amount() ) {
				return 'refund';
			}

			switch ( $this->get_payment()->status ) {
				case 'open':
				case 'pending':
					break;

				case 'canceled':
					break;

				case 'expired':
					break;

				case 'failed':
					break;

				case 'paid':
					break;
			}
		}

		/**
		 * Get the refunded amount.
		 *
		 * @since  1.7.0
		 *
		 * @return float|false The amount to be refunded, or false if this is not a refund.
		 */
		public function get_refund_amount() {
			if ( ! isset( $this->payment->amountRefunded ) ) {
				return false;
			}

			return $this->payment->amountRefunded->value;
		}

		/**
		 * Get a log message to include when adding the refund.
		 *
		 * @since  1.7.0
		 *
		 * @return string
		 */
		public function get_refund_log_message() {
			// $payment = $this->get_payment->
		}

		/**
		 * Return the gateway transaction ID.
		 *
		 * @since  1.7.0
		 *
		 * @return string|false The gateway transaction ID if available, otherwise false.
		 */
		public function get_gateway_transaction_id();

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

		/**
		 * Validate the webhook request.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function parse_request() {
			if ( ! $this->is_valid_request() ) {
				$this->set_invalid_request( __( 'Invalid request', 'charitable-mollie' ) );
				return;
			}

			$payload = file_get_contents( 'php://input' );

			if ( empty( $payload ) ) {
				$this->set_invalid_request( __( 'Empty data', 'charitable-mollie' ) );
				return;
			}

			parse_str( $payload, $this->data );

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( var_export( $this->data, true ) );
			}

			if ( empty( $this->data ) || ! array_key_exists( 'id', $this->data ) ) {
				$this->set_invalid_request( __( 'Invalid data', 'charitable-mollie' ) );
				return;
			}

			/* See if we have a donation stored with this transaction ID. */
			$this->donation_id = charitable_get_donation_by_transaction_id( $this->data['id'] );

			/* Check that the donation is valid. */
			if ( is_null( $this->donation_id ) || ! $this->get_donation() ) {
				$this->set_invalid_request( __( 'No such donation here.', 'charitable-mollie' ) );
				return;
			}

			/* Get the payment from Mollie. */
			if ( ! $this->get_payment() ) {
				$this->set_invalid_request( __( 'Invalid payment', 'charitable-mollie' ) );
				return;
			}
		}

		/**
		 * Returns whether the webhook request is valid.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		private function is_valid_request() {
			return ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' === $_SERVER['REQUEST_METHOD'];
		}

		/**
		 * Set this as an invalid request.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $response The response to send.
		 * @return void
		 */
		private function set_invalid_request( $response = '' ) {
			$this->valid    = false;
			$this->response = '';
		}
	}

endif;
