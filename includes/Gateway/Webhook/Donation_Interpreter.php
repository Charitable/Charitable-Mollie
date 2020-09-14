<?php
/**
 * Interpret incoming Mollie webhook related to a donation.
 *
 * @package   Charitable/Classes/\Charitable\Pro\Mollie\Gateway\Webhook\Donation_Interpreter
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Webhook;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Webhook\Donation_Interpreter' ) ) :

	/**
	 * \Charitable\Pro\Mollie\Gateway\Webhook\Donation_Interpreter
	 *
	 * @since 1.0.0
	 */
	class Donation_Interpreter implements \Charitable_Webhook_Intepreter_Donations_Interface {

		/**
		 * The general Mollie webhook interpreter.
		 *
		 * @since 1.0.0
		 *
		 * @var   \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter
		 */
		private $interpreter;

		/**
		 * The donation object.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Donation
		 */
		private $donation;

		/**
		 * Response message.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $response_message;

		/**
		 * Response HTTP status code.
		 *
		 * @since 1.0.0
		 *
		 * @var   int
		 */
		private $response_status;

		/**
		 * The event type.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $event_type;

		/**
		 * Logs.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $logs = array();

		/**
		 * Meta data.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $meta = array();

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 *
		 * @param \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter $interpreter The general Mollie webhook interpreter.
		 */
		public function __construct( \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter $interpreter ) {
			$this->interpreter = $interpreter;

			$this->parse_request();
		}

		/**
		 * Retrieve a particular part of the data received.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $prop    The property to retrieve.
		 * @param  mixed  $fallback Fallback to return in the property is not found in the data.
		 * @return mixed
		 */
		public function get( $prop, $fallback = null ) {
			return array_key_exists( $prop, $this->interpreter->data ) ? $this->interpreter->data[ $prop ] : $fallback;
		}

		/**
		 * Get the donation object.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donation|false Returns the donation if one matches the webhook.
		 *                                   If not, returns false.
		 */
		public function get_donation() {
			if ( ! isset( $this->donation ) ) {

				/* The donation ID needs to match a donation post type. */
				if ( \Charitable::DONATION_POST_TYPE !== get_post_type( $this->donation_id ) ) {
					return false;
				}

				$this->donation = charitable_get_donation( $this->donation_id );

				if ( 'paypal' !== $this->donation->get_gateway() ) {
					$this->set_response( __( 'Incorrect Gateway', 'charitable' ) );
					$this->donation = false;
				}
			}

			return $this->donation;
		}

		/**
		 * Get the donation key for this webhook.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_donation_key() {
			$invoice = $this->get( 'invoice' );

			if ( ! is_null( $invoice ) ) {
				return $invoice;
			}

			$custom = json_decode(
				$this->get( 'custom', json_encode( array() ) ),
				true
			);

			if ( is_array( $custom ) && array_key_exists( 'donation_key', $custom ) ) {
				return $custom['donation_key'];
			}

			return false;
		}

		/**
		 * The business email receiving the pament.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_business_email() {
			$email = $this->get( 'business' );

			if ( is_null( $email ) || ! is_email( $email ) ) {
				$email = trim( $this->get( 'receiver_email' ) );
			}

			return $email;
		}

		/**
		 * Get the payment status.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_payment_status() {
			return strtolower( $this->get( 'payment_status' ) );
		}

		/**
		 * Get the type of event described by the webhook.
		 *
		 * @since  1.0.0
		 *
		 * @return string|false
		 */
		public function get_event_type() {
			return isset( $this->event_type ) ? $this->event_type : false;
		}

		/**
		 * Get the refunded amount.
		 *
		 * @since  1.0.0
		 *
		 * @return float|false The amount to be refunded, or false if this is not a refund.
		 */
		public function get_refund_amount() {
			if ( 'refund' !== $this->get_event_type() ) {
				return false;
			}

			return $this->get( 'mc_gross' );
		}

		/**
		 * Get a log message to include when adding the refund.
		 *
		 * @since  1.0.0
		 *
		 * @return string|false
		 */
		public function get_refund_log_message() {
			if ( 'refund' !== $this->get_event_type() ) {
				return false;
			}

			if ( $this->get( 'mc_gross' ) < $this->donation->get_total_donation_amount( true ) ) {
				return sprintf(
					/* Translators: %s: transaction id from PayPal */
					__( 'Partial PayPal refund processed: #%s', 'charitable' ),
					$this->get( 'parent_txn_id', '' )
				);
			}

			return sprintf(
				/* Translators: %1$s: transaction id from PayPal; %2$s: reason code from PayPal */
				__( 'PayPal payment #%1$s refunded with reason: %2$s', 'charitable' ),
				$this->get( 'parent_txn_id', '' ),
				$this->get( 'reason_code', '' )
			);
		}

		/**
		 * Return the gateway transaction ID.
		 *
		 * @since  1.0.0
		 *
		 * @return string|false The gateway transaction ID if available, otherwise false.
		 */
		public function get_gateway_transaction_id() {
			return $this->get( 'txn_id' );
		}

		/**
		 * Return the donation status based on the webhook event.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_donation_status() {
			switch ( $this->get_event_type() ) {
				case 'refund':
					return 'charitable-refunded';

				case 'failed_payment':
					return 'charitable-failed';

				case 'cancellation':
					return 'charitable-cancelled';

				case 'updated_donation':
					switch ( $this->get_payment_status() ) {
						case 'pending':
							$status = 'charitable-pending';
							break;

						case 'canceled_reversal':
							$status = 'charitable-completed';
							break;
					}

					return $status;

				case 'completed_payment':
					return 'charitable-completed';
			}
		}

		/**
		 * Return an array of log messages to update the donation.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_logs() {
			return $this->logs;
		}

		/**
		 * Return an array of meta data to add/update for the donation.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_meta() {
			return $this->meta;
		}

		/**
		 * Get the response message.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_response_message() {
			return $this->response_message;
		}

		/**
		 * Get the response HTTP status.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_response_status() {
			return $this->response_status;
		}

		/**
		 * Parse the data.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function parse_request() {
			$this->donation = $this->get_donation();

			if ( ! $this->donation ) {
				return;
			}

			/* Validate the donation key. */
			if ( ! $this->validate_donation_key() ) {
				return;
			}

			/* Validate the business email. */
			if ( ! $this->validate_business_email() ) {
				return;
			}

			/* Validate the currency. */
			if ( ! $this->validate_currency() ) {
				return;
			}

			/* Validate the amount. */
			if ( ! $this->validate_amount() ) {
				return;
			}

			switch ( $this->get_payment_status() ) {
				case 'refunded':
				case 'reversed':
					$this->event_type = 'refund';
					$this->set_response( __( 'Refund Processed', 'charitable' ) );
					break;

				case 'declined':
				case 'failed':
				case 'denied':
					$this->event_type = 'failed_payment';
					$this->logs[]     = sprintf(
						/* translators: %s: payment status */
						__( 'The donation has failed with the following status: %s', 'charitable' ),
						$this->get_payment_status()
					);
					$this->set_response( __( 'Payment Failed', 'charitable' ) );
					break;

				case 'expired':
				case 'voided':
					$this->event_type = 'cancellation';
					$this->logs[]     = sprintf(
						/* translators: %s: payment status */
						__( 'The donation cancelled with the following status: %s', 'charitable' ),
						$this->get_payment_status()
					);
					$this->set_response( __( 'Donation Cancelled', 'charitable' ) );
					break;

				case 'pending':
					$this->event_type = 'updated_donation';

					if ( ! is_null( $this->get( 'pending_reason' ) ) ) {
						$this->logs[] = $this->get_pending_reason_note();
					}

					$this->set_response( __( 'Donation Pending', 'charitable' ) );
					break;

				case 'canceled_reversal':
					$this->event_type = 'updated_donation';
					$this->logs[]     = __( 'A reversal/dispute on the donation has been cancelled.', 'charitable' );
					$this->set_response( __( 'Donation Reversal Cancelled', 'charitable' ) );
					break;

				case 'completed':
					if ( 'charitable-completed' !== $this->donation->get_status() ) {
						$this->event_type = 'completed_payment';
						$this->logs[]     = sprintf(
							/* translators: %s: PayPal transaction ID */
							__( 'PayPal Transaction ID: %s', 'charitable' ),
							$this->get( 'txn_id' )
						);
						$this->set_response( __( 'Donation Completed', 'charitable' ) );
					}
					break;
			}
		}

		/**
		 * Validate the donation key.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		private function validate_donation_key() {
			$donation_key = $this->get_donation_key();

			if ( ! $donation_key ) {
				$this->set_response( __( 'Missing Donation Key', 'charitable' ) );
				return false;
			}

			if ( $donation_key === $this->donation->get_donation_key() ) {
				return true;
			}

			$this->event_type = 'failed_payment';

			$this->logs[] = sprintf(
				/* translators: %s: IPN data */
				__( 'Donation key in the IPN response does not match the donation. IPN data: %s', 'charitable' ),
				json_encode( $this->data )
			);

			$this->set_response( __( 'Invalid Donation Key', 'charitable' ) );

			return false;
		}

		/**
		 * Validate the business email.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		private function validate_business_email() {
			/**
			 * Turn off business email validation if required.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $validate Whether to validate the business email.
			 */
			if ( ! apply_filters( 'charitable_webhook_paypal_validate_business_email', true ) ) {
				return true;
			}

			/* Compare business email with our PayPal settings. */
			if ( 0 === strcasecmp( $this->get_business_email(), trim( Charitable_Gateway_Paypal::get_paypal_email() ) ) ) {
				return true;
			}

			$this->event_type = 'failed_payment';

			$this->logs[] = sprintf(
				/* translators: %s: IPN data */
				__( 'Invalid Business email in the IPN response. IPN data: %s', 'charitable' ),
				json_encode( $this->data )
			);

			$this->set_response( __( 'Incorrect Business Email', 'charitable' ) );

			return false;
		}

		/**
		 * Validate the business email.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		private function validate_currency() {
			/**
			 * Turn off currency validation if required.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $validate Whether to validate the currency.
			 */
			if ( ! apply_filters( 'charitable_webhook_paypal_validate_currency', true ) ) {
				return true;
			}

			if ( 0 === strcasecmp( $this->get( 'mc_currency' ), charitable_get_currency() ) ) {
				return true;
			}

			$this->event_type = 'failed_payment';

			$this->logs[] = sprintf(
				/* translators: %s: IPN data */
				__( 'The currency in the IPN response does not match the site currency. IPN data: %s', 'charitable' ),
				json_encode( $this->data )
			);

			$this->set_response( __( 'Incorrect Currency', 'charitable' ) );

			return false;
		}

		/**
		 * Validate the amount.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function validate_amount() {
			/**
			 * Turn off amount validation if required.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $validate Whether to validate the amount.
			 */
			if ( ! apply_filters( 'charitable_webhook_paypal_validate_amount', true ) ) {
				return true;
			}

			/* Amount matches. */
			if ( $this->get( 'mc_gross' ) === $this->donation->get_total_donation_amount( true ) ) {
				return true;
			}

			/* This may be a partial refund. */
			if ( false !== $this->get_refund_amount() ) {
				return true;
			}

			$this->event_type = 'failed_payment';

			$this->logs[] = sprintf(
				/* translators: %s: IPN data */
				__( 'The amount in the IPN response does not match the expected donation amount. IPN data: %s', 'charitable' ),
				json_encode( $this->data )
			);

			$this->set_response( __( 'Incorrect Amount', 'charitable' ) );

			return false;
		}

		/**
		 * Return a note to log for a pending payment.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_pending_reason_note() {
			$reason_code = strtolower( $this->get( 'pending_reason' ) );

			switch ( $reason_code ) {
				case 'echeck':
					$note = __( 'Payment made via eCheck and will clear automatically in 5-8 days', 'charitable' );
					break;

				case 'address':
					$note = __( 'Payment requires a confirmed customer address and must be accepted manually through PayPal', 'charitable' );
					break;

				case 'intl':
					$note = __( 'Payment must be accepted manually through PayPal due to international account regulations', 'charitable' );
					break;

				case 'multi-currency':
					$note = __( 'Payment received in non-shop currency and must be accepted manually through PayPal', 'charitable' );
					break;

				case 'paymentreview':
				case 'regulatory_review':
					$note = __( 'Payment is being reviewed by PayPal staff as high-risk or in possible violation of government regulations', 'charitable' );
					break;

				case 'unilateral':
					$note = __( 'Payment was sent to non-confirmed or non-registered email address.', 'charitable' );
					break;

				case 'upgrade':
					$note = __( 'PayPal account must be upgraded before this payment can be accepted', 'charitable' );
					break;

				case 'verify':
					$note = __( 'PayPal account is not verified. Verify account in order to accept this payment', 'charitable' );
					break;

				default:
					/* translators: %s: reason code given by PayPal */
					$note = sprintf( __( 'Payment is pending for unknown reasons. Contact PayPal support for assistance. Reason code: %s', 'charitable' ), $reason_code );
			}

			/**
			 * Filter the donation log note added about the reason for the pending donation.
			 *
			 * @since 1.0.0
			 *
			 * @param string $note        The note.
			 * @param string $reason_code The reason code received from PayPal.
			 */
			return apply_filters( 'charitable_paypal_gateway_pending_reason_note', $note, $reason_code );
		}

		/**
		 * Set the response to send.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $message     Response message.
		 * @param  int    $http_status HTTP status to send.
		 * @return void
		 */
		private function set_response( $message, $http_status = 200 ) {
			$this->response_message = $message;
			$this->response_status  = $http_status;
		}
	}

endif;
