<?php
/**
 * The class responsible for creating payment requests for Mollie.
 *
 * @package   Charitable Mollie/Classes
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Payment;

use Charitable\Pro\Mollie\Gateway\Api;
use Charitable\Gateways\Payment\RequestInterface;
use Charitable\Gateways\Payment\ResponseInterface;
use Charitable\Helpers\DonationDataMapper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Payment\Request' ) ) :

	/**
	 * \Charitable\Pro\Mollie\Gateway\Payment\Request
	 *
	 * @since 1.0.0
	 */
	class Request implements RequestInterface {

		/**
		 * Data map.
		 *
		 * @since 1.0.0
		 *
		 * @var   DonationDataMapper
		 */
		private $data_map;

		/**
		 * Whether this is a test mode request.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		private $test_mode;

		/**
		 * The current user's donor ID.
		 *
		 * @since 1.0.0
		 *
		 * @var   int|false
		 */
		private $donor_id;

		/**
		 * Class instantiation.
		 *
		 * @since 1.0.0
		 *
		 * @param DonationDataMapper $data_map  The data mapper object.
		 * @param boolean|null       $test_mode Whether this is a test mode request.
		 */
		public function __construct( DonationDataMapper $data_map, $test_mode = null ) {
			$this->data_map = $data_map;
			$this->test_mode = is_null( $test_mode ) ? charitable_get_option( 'test_mode' ) : $test_mode;
		}

		/**
		 * Get the API object.
		 *
		 * @since  1.0.0
		 *
		 * @return \Charitable\Pro\Mollie\Gateway\Api
		 */
		public function api() {
			return \charitable_mollie()->gateway()->api();
		}

		/**
		 * Prepare the request.
		 *
		 * @return boolean
		 */
		public function prepare_request() {
			$customer_id = $this->get_customer_id();

			if ( ! $customer_id ) {
				$customer_id = $this->create_customer();
			}

			$this->request_data                    = $this->data_map->get_data( array( 'amount', 'description', 'redirectUrl', 'webhookUrl', 'locale', 'metadata' ) );
			$this->request_data['description']     = sprintf( '%1$s - %2$s', $this->data_map->get_full_name(), $this->request_data['description'] );
			$this->request_data['amount']['value'] = $this->get_payment_amount();
			$this->request_data['customerId']      = $customer_id;
			$this->request_data['sequenceType']    = 0 === $this->data_map->donation_plan_id ? 'oneoff' : 'first';

			return true;
		}

		/**
		 * Create the customer.
		 *
		 * @see https://docs.mollie.com/reference/v2/customers-api/create-customer
		 *
		 * @since  1.0.0
		 *
		 * @return string The customer ID.
		 */
		public function create_customer() {
			$customer_data = $this->data_map->get_data( array( 'name', 'email', 'locale' ) );

			/**
			 * Filter the arguments used to add a new Customer in Mollie.
			 *
			 * @see https://docs.mollie.com/reference/v2/customers-api/create-customer
			 *
			 * @since 1.0.0
			 *
			 * @param array $customer_data The arguments to be passed to create the customer.
			 * @param array $data          Additional data received for the request.
			 */
			$customer_data = apply_filters( 'charitable_mollie_customer_args', $customer_data, $this->data_map );

			try {
				$customer = $this->api()->post( 'customers', $customer_data );

				update_metadata( 'donor', $this->get_donor_id(), $this->get_customer_meta_key(), $customer->id );

				return $customer->id;

			} catch ( \Exception $e ) {

			}
		}

		/**
		 * Returns the Mollie customer id for the current donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean|null $test_mode Whether to get test mode keys. If null, this
		 *                                 will use the current site Test Mode setting.
		 * @return string|false Returns a string if a customer id is set. Otherwise returns false.
		 */
		public function get_customer_id() {
			$donor_id = $this->get_donor_id();

			if ( ! $donor_id ) {
				return false;
			}

			$customer_id = get_metadata( 'donor', $donor_id, $this->get_customer_meta_key(), true );

			if ( ! $customer_id ) {
				return false;
			}

			try {
				$customer = $this->api()->get( 'customers/' . $customer_id );

				return $customer->id;
			} catch ( \Exception $e ) {

			}
		}

		/**
		 * Make the request.
		 *
		 * @return boolean
		 */
		public function make_request() {
			/**
			 * Filter the arguments used to add a new Payment in Mollie.
			 *
			 * @see https://docs.mollie.com/reference/v2/payments-api/create-payment
			 *
			 * @since 1.0.0
			 *
			 * @param array $request_data The arguments to be passed to create the payment.
			 * @param array $data         Additional data received for the request.
			 */
			$this->request_data = apply_filters( 'charitable_mollie_payment_args', $this->request_data, $this->data_map );

			try {
				$this->response_data = $this->api()->post( 'payments', $this->request_data );

				if ( false === $this->response_data ) {
					$response = json_decode( wp_remote_retrieve_body( $this->api()->get_last_response() ) );

					charitable_get_notices()->add_error(
						sprintf(
							/* translators: %s: error message from Mollie */
							__( 'Payment request failed with error: %s.', 'charitable-mollie' ),
							json_decode( wp_remote_retrieve_body( $this->api()->get_last_response() ) )->detail
						)
					);
					return false;
				}

				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Return the response to the request.
		 *
		 * @return \Charitable\Pro\Mollie\Gateway\Payment\Response
		 */
		public function get_response() : ResponseInterface {
			return new Response( $this->response_data );
		}

		/**
		 * Get the meta key used to record the Mollie customer ID.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_customer_meta_key() {
			$meta_postfix = $this->test_mode ? 'test' : 'live';
			return 'mollie_customer_id_' . $meta_postfix;
		}

		/**
		 * Get the current customer's donor ID.
		 *
		 * @since  1.0.0
		 *
		 * @return int|false
		 */
		public function get_donor_id() {
			return $this->data_map->get_donation()->get_donor_id();
		}

		/**
		 * Get the payment amount, taking into account any fees included.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_payment_amount() {
			return number_format( $this->data_map->get_amount(), 2, '.', '' );
		}
	}

endif;
