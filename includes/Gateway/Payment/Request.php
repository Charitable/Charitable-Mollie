<?php
/**
 * The class responsible for creating payment requests for Mollie.
 *
 * @package   Charitable Mollie/Classes/\Charitable\Pro\Mollie\Gateway\Payment\Request
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Payment;

use \Charitable\Pro\Mollie\Gateway\Api as Api;

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
	class Request implements \Charitable_Gateway_Payment_Request_Interface {

		/**
		 * Data map.
		 *
		 * @since 1.0.0
		 *
		 * @var   \Charitable_Donation_Data_Mapper
		 */
		private $data_map;

		/**
		 * Class instantiation.
		 *
		 * @since 1.0.0
		 *
		 * @param \Charitable_Donation_Data_Mapper $data_map The data mapper object.
		 */
		public function __construct( \Charitable_Donation_Data_Mapper $data_map ) {
			$this->data_map = $data_map;
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
			$customer_id = $this->create_customer();

			$this->request_data                    = $this->data_map->get_data( array( 'amount', 'description', 'redirectUrl', 'webhookUrl', 'locale', 'metadata' ) );
			$this->request_data['amount']['value'] = number_format( $this->request_data['amount']['value'], 2 );
			$this->request_data['customerId']      = $customer_id;
			$this->request_data['sequenceType']    = 'oneoff';

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

			try {
				$customer = $this->api()->post( 'customers', $customer_data );
				/**
				 * POST: https://api.mollie.com/v2/customers
				 *
				 * @see https://docs.mollie.com/reference/v2/customers-api/create-customer
				 */
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
			try {
				/**
				 * POST: https://api.mollie.com/v2/payments
				 *
				 * @see https://docs.mollie.com/reference/v2/payments-api/create-payment
				 */
				$this->response_data = $this->api()->post( 'payments', $this->request_data );

				return true;

			} catch ( Exception $e ) {

			}
		}

		/**
		 * Return the response to the request.
		 *
		 * @return \Charitable\Pro\Mollie\Gateway\Payment\Response
		 */
		public function get_response() {
			return new Response( $this->response_data );
		}
	}

endif;
