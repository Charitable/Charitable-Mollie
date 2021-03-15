<?php
/**
 * Mollie Gateway class.
 *
 * @package   Charitable Mollie/Classes
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.1
 */

namespace Charitable\Pro\Mollie\Gateway;

use Charitable\Webhooks\Receivers as WebhookReceivers;
use Charitable\Gateways\Payment\Processors as PaymentProcessors;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Gateway' ) ) :

	/**
	 * Mollie Gateway.
	 *
	 * @since 1.0.0
	 */
	class Gateway extends \Charitable_Gateway {

		/** The gateway ID. */
		const ID = 'mollie';

		/**
		 * Boolean flag recording whether the gateway hooks
		 * have been set up.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		private static $setup = false;

		/**
		 * API object.
		 *
		 * @since 1.0.0
		 *
		 * @var   \Charitable\Pro\Mollie\Gateway\Api[]
		 */
		private $api = array();

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			/**
			 * Change the Mollie gateway name as its shown in the gateway settings page.
			 *
			 * @since 1.0.0
			 *
			 * @param string $name The gateway name.
			 */
			$this->name = apply_filters( 'charitable_gateway_mollie_name', __( 'Mollie', 'charitable-mollie' ) );

			$this->defaults = array(
				'label' => __( 'Mollie', 'charitable-mollie' ),
			);

			$this->supports = array(
				'1.3.0',
				'refunds',
				'recurring',
			);

			$this->setup();
		}

		/**
		 * Set up hooks for the class.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function setup() {
			if ( self::$setup ) {
				return;
			}

			self::$setup = true;

			/* Register our new gateway. */
			add_filter( 'charitable_payment_gateways', array( $this, 'register_gateway' ) );

			/* Refund a donation from the dashboard. */
			add_action( 'charitable_process_refund_mollie', array( $this, 'refund_donation_from_dashboard' ) );

			/* Handle subscription cancellations. */
			add_filter( 'charitable_recurring_can_cancel_mollie', array( $this, 'is_subscription_cancellable' ), 10, 2 );
			add_action( 'charitable_process_cancellation_mollie', array( $this, 'cancel_subscription' ), 10, 2 );

			if ( version_compare( charitable()->get_version(), '1.7', '<' ) ) {
				/* Register payment processor. */
				$this->load_forward_compatible_packages();
			}

			/* Register the Mollie webhook receiver. */
			WebhookReceivers::register( self::ID, '\Charitable\Pro\Mollie\Gateway\Webhook\Receiver' );

			/* Register the Mollie payment processor */
			PaymentProcessors::register( self::ID, '\Charitable\Pro\Mollie\Gateway\Payment\Processor' );
		}

		/**
		 * Returns the current gateway's ID.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Register gateway settings.
		 *
		 * @since  1.0.0
		 *
		 * @param  array[] $settings Default array of settings for the gateway.
		 * @return array[]
		 */
		public function gateway_settings( $settings ) {
			return array_merge(
				$settings,
				array(
					'keys'         => array(
						'title'    => __( 'Mollie API Keys', 'charitable-mollie' ),
						'type'     => 'heading',
						'priority' => 4,
					),
					'live_api_key' => array(
						'type'     => 'text',
						'title'    => __( 'Live API key', 'charitable-mollie' ),
						'priority' => 6,
						'class'    => 'wide',
					),
					'test_api_key' => array(
						'type'     => 'text',
						'title'    => __( 'Test API key', 'charitable-mollie' ),
						'priority' => 8,
						'class'    => 'wide',
					),
				)
			);
		}

		/**
		 * Register the payment gateway class.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $gateways The list of registered gateways.
		 * @return string[]
		 */
		public function register_gateway( $gateways ) {
			$gateways['mollie'] = '\Charitable\Pro\Mollie\Gateway\Gateway';
			return $gateways;
		}

		/**
		 * Get the API object.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean|null $test_mode Whether to explicitly get the test or live key.
		 *                                 If left as null, this will return the key for the
		 *                                 current mode.
		 * @return \Charitable\Pro\Mollie\Gateway\Api
		 */
		public function api( $test_mode = null ) {
			$test_mode = is_null( $test_mode ) ? charitable_get_option( 'test_mode' ) : $test_mode;

			if ( ! isset( $this->api[ $test_mode ] ) ) {
				$this->api[ $test_mode ] = new \Charitable\Pro\Mollie\Gateway\Api( $test_mode );
			}

			return $this->api[ $test_mode ];
		}

		/**
		 * Check whether a particular donation can be refunded automatically in Mollie.
		 *
		 * @since  1.0.0
		 *
		 * @param  \Charitable_Donation $donation The donation object.
		 * @return boolean
		 */
		public function is_donation_refundable( \Charitable_Donation $donation ) {
			return $this->api( $donation->get_test_mode( false ) )->has_valid_api_key()
				&& $donation->get_gateway_transaction_id()
				&& ! get_post_meta( $donation->ID, '_mollie_refunded', true );
		}

		/**
		 * Process a refund initiated in the WordPress dashboard.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean
		 */
		public function refund_donation_from_dashboard( $donation_id ) {
			/* The donation has been refunded previously. */
			if ( get_post_meta( $donation_id, '_mollie_refunded', true ) ) {
				return false;
			}

			$donation = charitable_get_donation( $donation_id );

			if ( ! $donation ) {
				return false;
			}

			$api = $this->api( $donation->get_test_mode( false ) );

			if ( ! $api->has_valid_api_key() ) {
				return false;
			}

			$transaction = $donation->get_gateway_transaction_id();

			if ( ! $transaction ) {
				return false;
			}

			/* Post refund to Mollie. */
			$response_data = $api->post(
				'payments/' . $transaction . '/refunds',
				array(
					'amount' => array(
						'currency' => charitable_get_currency(),
						'value'    => number_format( $donation->get_total_donation_amount( true ), 2 ),
					)
				),
			);

			/* Check for an error. */
			if ( false === $response_data ) {
				$response = $this->api()->get_last_response();
				$error    = is_wp_error( $response ) ? $response->get_error_message() : json_decode( wp_remote_retrieve_body( $response ) )->message;
				$donation->log()->add(
					sprintf(
						__( 'Mollie refund failed with message: %s', 'charitable-mollie' ),
						$error
					)
				);

				return false;
			}

			/* Double-check the response status. */
			if ( ! $response_data->status ) {
				$donation->log()->add(
					sprintf(
						__( 'Mollie refund failed with message: %s', 'charitable-mollie' ),
						$response_data->message
					)
				);

				return false;
			}

			update_post_meta( $donation->ID, '_mollie_refunded', true );

			$donation->log()->add( __( 'Refunded automatically from dashboard', 'charitable-paystack' ) );

			return true;
		}

		/**
		 * Checks whether a subscription can be cancelled.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean                       $can_cancel Whether the subscription can be cancelled.
		 * @param  Charitable_Recurring_Donation $donation   The donation object.
		 * @return boolean
		 */
		public function is_subscription_cancellable( $can_cancel, \Charitable_Recurring_Donation $donation ) {
			if ( ! $can_cancel ) {
				return $can_cancel;
			}

			return $this->api( $donation->get_test_mode( false ) )->has_valid_api_key()
				&& ! empty( $donation->get_gateway_subscription_id() )
				&& ! get_post_meta( $donation->ID, '_mollie_cancelled', true );
		}

		/**
		 * Cancel a recurring donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean                       $cancelled Whether the subscription was cancelled successfully in the gateway.
		 * @param  Charitable_Recurring_Donation $donation The donation object.
		 * @return boolean
		 */
		public function cancel_subscription( $cancelled, \Charitable_Recurring_Donation $donation ) {
			$subscription_id = $donation->get_gateway_subscription_id();

			if ( ! $subscription_id ) {
				return false;
			}

			$customer_id = $donation->get( 'mollie_customer_id' );

			if ( ! $customer_id ) {
				return false;
			}

			/* Disable the subscription. */
			$response_data = $this->api( $donation->get_test_mode( false ) )->make_request(
				'delete',
				sprintf( 'customers/%1$s/subscriptions/%2$s', $customer_id, $subscription_id )
			);

			/* Check for an error. */
			if ( false === $response_data ) {
				$response = $this->api()->get_last_response();
				$error    = is_wp_error( $response ) ? $response->get_error_message() : json_decode( wp_remote_retrieve_body( $response ) )->message;
				$donation->log()->add(
					sprintf(
						__( 'Mollie subscription cancellation failed with message: %s', 'charitable-mollie' ),
						$error
					)
				);

				return false;
			}

			/* Double-check the response status. */
			if ( ! $response_data->status ) {
				$donation->log()->add(
					sprintf(
						__( 'Mollie subscription cancellation failed with message: %s', 'charitable-mollie' ),
						$response_data->message
					)
				);

				return false;
			}

			update_post_meta( $donation->ID, '_mollie_cancelled', true );

			return true;
		}

		/**
		 * Get the payment request object.
		 *
		 * @since  1.0.0
		 *
		 * @param  \Charitable_Donation $donation The donation to make a payment request for.
		 * @return \Charitable\Pro\Mollie\Gateway\Payment\Request
		 */
		public function get_payment_request( \Charitable_Donation $donation ) {
			$data_map = new \Charitable_Donation_Data_Mapper( $donation );
			$data_map->add_map(
				array(
					'email'        => 'email',
					'full_name'    => 'name',
					'donation_key' => 'metadata.donation_key',
					'donation_id'  => 'metadata.donation_id',
					'amount'       => 'amount.value',
					'currency'     => 'amount.currency',
					'description'  => 'description',
					'locale'       => 'locale',
					'return_url'   => 'redirectUrl',
					'webhook_url'  => 'webhookUrl',
				)
			);

			return new Payment\Request( $data_map );
		}

		/**
		 * Process a Mollie webhook.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function process_webhook() {
			/* Stop infinite recursion. */
			remove_action( 'charitable_process_ipn_' . self::ID, array( $this, 'process_webhook' ) );
			\Charitable\Packages\Webhooks\handle( self::ID );
		}

		/**
		 * Load the gateways & webhooks packages for forward compatibility.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function load_forward_compatible_packages() {
			require_once \charitable_mollie()->get_path( 'directory' ) . 'packages/charitable-gateways/package.php';
			require_once \charitable_mollie()->get_path( 'directory' ) . 'packages/charitable-webhooks/package.php';

			add_filter( 'charitable_process_donation_' . self::ID, '\Charitable\Packages\Gateways\process_donation', 10, 3 );
			add_action( 'charitable_process_ipn_' . self::ID, array( $this, 'process_webhook' ) );
		}
	}


endif;
