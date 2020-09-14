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
	class Interpreter implements \Charitable_Webhook_Interpreter_Interface {

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
			switch ( $this->get_txn_type() ) {
				case 'subscr_signup':
				case 'subscr_payment':
				case 'subscr_cancel':
				case 'subscr_modify':
				case 'subscr_failed':
				case 'subscr_eot':
					return 'subscription';

				default:
					return 'donation';
			}
		}

		/**
		 * Return the interpreter object to use for donation webhooks.
		 *
		 * @since  1.0.0
		 *
		 * @return \Charitable\Pro\Mollie\Gateway\Webhook\Donation_Interpreter|false
		 */
		public function get_donations_interpreter() {
			return new Donation_Interpreter( $this );
		}

		/**
		 * Get the donation ID for this webhook.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_donation_id() {
			$custom = json_decode( $this->data['custom'], true );

			if ( is_array( $custom ) && array_key_exists( 'donation_id', $custom ) ) {
				return absint( $custom['donation_id'] );
			}

			return absint( $custom );
		}

		/**
		 * Validate the webhook request.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function parse_request() {
			if ( ! $this->is_valid_request() ) {
				$this->set_invalid_request( __( 'Invalid Request', 'charitable' ) );
				return;
			}

			$this->data = @file_get_contents( 'php://input' );

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( var_export( $this->data, true ) );
			}

			if ( empty( $this->data ) ) {
				$this->set_invalid_request( __( 'Empty Data', 'charitable' ) );
				return;
			}

			if ( ! $this->verify_ipn() ) {
				die( __( 'IPN Verification Failure', 'charitable' ) );
			}

			$defaults = array(
				'payment_status' => '',
				'custom'         => 0,
				'txn_type'       => '',
			);

			$this->data        = wp_parse_args( $this->data, $defaults );
			$this->donation_id = $this->get_donation_id();

			if ( ! $this->donation_id ) {
				$this->set_invalid_request( __( 'Missing Donation ID', 'charitable' ) );
			}

			$txn_type = $this->get_txn_type();

			if ( has_action( 'charitable_paypal_' . $txn_type ) ) {
				/**
				 * Do something with this particular transaction type.
				 *
				 * @since 1.0.0
				 * @since 1.0.0 Moved hook to \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter::parse_request().
				 *
				 * @param array $data        The data received from PayPal.
				 * @param int   $donation_id The donation ID.
				 */
				do_action( 'charitable_paypal_' . $txn_type, $this->data, $this->donation_id );
			} else {
				/**
				 * Do something with a web_accept ipn.
				 *
				 * @since 1.0.0
				 * @since 1.0.0 Moved hook to \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter::parse_request().
				 *
				 * @param array $data        The data received from PayPal.
				 * @param int   $donation_id The donation ID.
				 */
				do_action( 'charitable_paypal_web_accept', $this->data, $this->donation_id );
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
		 * Return the posted IPN data.
		 *
		 * @since  1.0.0
		 *
		 * @return mixed[]
		 */
		private function get_ipn_data() {
			$post_data = '';

			/* Fallback just in case post_max_size is lower than needed. */
			if ( ini_get( 'allow_url_fopen' ) ) {
				$post_data = file_get_contents( 'php://input' );
			} else {
				ini_set( 'post_max_size', '12M' );
			}

			if ( strlen( $post_data ) ) {
				$arg_separator = ini_get( 'arg_separator.output' );
				$data_string   = 'cmd=_notify-validate' . $arg_separator . $post_data;

				/* Convert collected post data to an array */
				parse_str( $data_string, $data );

				return $data;
			}

			/* Return an empty array if there are no POST variables. */
			if ( empty( $_POST ) ) {
				return array();
			}

			$data = array(
				'cmd' => '_notify-validate',
			);

			return array_merge( $data, $_POST );
		}

		/**
		 * Validates an IPN request with PayPal.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		private function verify_ipn() {
			if ( charitable_get_option( array( 'gateways_paypal', 'disable_ipn_verification' ) ) ) {
				return true;
			}

			/* Get response */
			$response = wp_remote_post(
				$this->get_ipn_check_url(),
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'headers'     => array(
						'host'         => 'www.paypal.com',
						'connection'   => 'close',
						'content-type' => 'application/x-www-form-urlencoded',
						'post'         => '/cgi-bin/webscr HTTP/1.1',

					),
					'sslverify'   => false,
					'body'        => $this->data,
				)
			);

			/**
			 * Filter whether the PayPal IPN was verified.
			 *
			 * @since 1.0.0
			 * @since 1.0.0 Moved hook to \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter::verify_ipn().
			 *
			 * @param boolean        $valid    Whether it has been verified.
			 * @param array|WP_Error $response Array in case of successful request. WP_Error otherwise.
			 */
			return apply_filters( 'charitable_paypal_ipn_verification', $this->is_valid_api_response( $response ), $response );
		}

		/**
		 * Return the URL to send our IPN check to.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		private function get_ipn_check_url() {
			if ( charitable_get_option( 'test_mode' ) ) {
				$paypal_uri = 'https://ipnpb.sandbox.';
			} else {
				$paypal_uri = 'https://ipnpb.';
			}

			$paypal_uri .= 'paypal.com/cgi-bin/webscr';

			/**
			 * Filter the PayPal URI.
			 *
			 * @since 1.0.0
			 * @since 1.5.4 Added $ssl_check and $ipn_check parameters.
			 * @since 1.0.0 Added hook to \Charitable\Pro\Mollie\Gateway\Webhook\Interpreter::get_ipn_check_url().
			 *
			 * @param string  $paypal_uri The URL.
			 * @param boolean $ssl_check  Whether to check SSL.
			 * @param boolean $ipn_check  Whether this is for an IPN request.
			 */
			return apply_filters( 'charitable_paypal_uri', $paypal_uri, true, true );
		}

		/**
		 * Returns whether the API response we received is valid.
		 *
		 * @since  1.0.0
		 *
		 * @param  array|WP_Error $api_response Array in case of successful request. WP_Error otherwise.
		 * @return boolean
		 */
		private function is_valid_api_response( $api_response ) {
			return ! is_wp_error( $api_response ) && 'VERIFIED' === $api_response['body'];
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
