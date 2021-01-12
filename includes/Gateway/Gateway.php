<?php
/**
 * Mollie Gateway class.
 *
 * @package   Charitable Mollie/Classes
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.1
 */

namespace Charitable\Pro\Mollie\Gateway;

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
		 * @var   \Charitable\Pro\Mollie\Gateway\Api
		 */
		private $api;

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

			/* Register payment processor. */
			$this->register_payment_processor();

			/* Register webhook receiver. */
			$this->register_webhook_receiver();
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
		 * Register the Mollie payment processor.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function register_payment_processor() {
			$path = \charitable_mollie()->get_path( 'directory' );

			require_once $path . 'charitable-payment-processors/Payment/ProcessorInterface.php';
			require_once $path . 'charitable-payment-processors/Payment/RequestInterface.php';
			require_once $path . 'charitable-payment-processors/Payment/ResponseInterface.php';
			require_once $path . 'charitable-payment-processors/DonationDataMapper.php';
			require_once $path . 'charitable-payment-processors/PaymentProcessors.php';
			require_once $path . 'charitable-payment-processors/Processor.php';

			\Charitable\PaymentProcessors\PaymentProcessors::register( self::ID, '\Charitable\Pro\Mollie\Gateway\Payment\Processor' );

			add_filter( 'charitable_process_donation_' . self::ID, array( '\Charitable\PaymentProcessors\Processor', 'process' ), 10, 3 );
		}

		/**
		 * Register the Mollie webhook receiver.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function register_webhook_receiver() {
			$path = \charitable_mollie()->get_path( 'directory' );

			require_once $path . 'charitable-webhook-receivers/Interpreters/DonationInterpreterInterface.php';
			require_once $path . 'charitable-webhook-receivers/Processors/ProcessorInterface.php';
			require_once $path . 'charitable-webhook-receivers/Processors/Processor.php';
			require_once $path . 'charitable-webhook-receivers/Processors/DonationProcessor.php';
			require_once $path . 'charitable-webhook-receivers/Receivers/ReceiverInterface.php';
			require_once $path . 'charitable-webhook-receivers/Handler.php';
			require_once $path . 'charitable-webhook-receivers/Receivers.php';

			\Charitable\WebhookReceivers\Receivers::register( Gateway::ID, '\Charitable\Pro\Mollie\Gateway\Webhook\Receiver' );

			/**
			 * This is a temporary hack to ensure the handler works.
			 */
			add_action( 'charitable_process_ipn_' . self::ID, array( $this, 'process_webhook' ) );
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
			\Charitable\WebhookReceivers\Handler::handle( self::ID );
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
			if ( ! isset( $this->api ) ) {
				$this->api = new \Charitable\Pro\Mollie\Gateway\Api( $test_mode );
			}

			return $this->api;
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
			return $this->api()->has_valid_api_key() && $donation->get_gateway_transaction_id();
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
			$donation = charitable_get_donation( $donation_id );

			if ( ! $donation ) {
				return false;
			}

			$api = $this->api();

			if ( ! $api->has_api_key() ) {
				return false;
			}

			$transaction = $donation->get_gateway_transaction_id();

			if ( ! $transaction ) {
				return false;
			}

			/**
			 * @todo Make refund.
			 */
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
	}

endif;
