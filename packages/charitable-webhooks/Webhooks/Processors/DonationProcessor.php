<?php
/**
 * Class responsible for processing incoming webhooks related to donations.
 *
 * @package   Charitable/Classes/Charitable_Webhook_Processor_Donations
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0
 * @version   1.7.0
 */

namespace Charitable\Webhooks\Processors;

use Charitable\Webhooks\Interpreters\DonationInterpreterInterface;
use Charitable\Gateways\Processor as PaymentProcessor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DonationProcessor.
 *
 * @since 1.7.0
 */
class DonationProcessor extends Processor {

	/**
	 * Interpreter.
	 *
	 * @since 1.7.0
	 *
	 * @var   DonationIntepreterInterface
	 */
	protected $interpreter;

	/**
	 * The donation object.
	 *
	 * @since 1.7.0
	 *
	 * @var   Charitable_Donation|false
	 */
	protected $donation;

	/**
	 * Set up the processor.
	 *
	 * @since 1.7.0
	 *
	 * @param DonationInterpreterInterface
	 */
	public function __construct( DonationInterpreterInterface $interpreter ) {
		$this->interpreter = $interpreter;
	}

	/**
	 * Get class properties.
	 *
	 * @since  1.7.0
	 *
	 * @param  string $prop The property to retrieve.
	 * @return mixed
	 */
	public function __get( $prop ) {
		return $this->$prop;
	}

	/**
	 * Process the webhook event.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean|void
	 */
	public function process() {
		$this->donation = $this->interpreter->get_donation();

		/* Without a donation, there's nothing left to do. */
		if ( ! $this->donation ) {
			$this->set_response(
				__( 'Donation Webhook: Event could not be matched to a valid Charitable donation.', 'charitable' )
			);
			return false;
		}

		$event_type = $this->interpreter->get_event_type();

		if ( method_exists( $this, 'process_' . $event_type ) ) {
			return call_user_func( array( $this, 'process_' . $event_type ) );
		}

		/**
		 * Process a webhook event for which we don't have a built-in processor.
		 *
		 * @since 1.7.0
		 *
		 * @param boolean                        $processed   Whether the webhook event was processed.
		 * @param Charitable_Donation            $donation    The donation object.
		 * @param Charitable_Webhook_Interpreter $interpreter The source interpreter.
		 */
		return apply_filters( 'charitable_webhook_processor_donations_process_' . $event_type, false, $this->donation, $this->interpreter );
	}

	/**
	 * Process a refund.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean
	 */
	public function process_refund() {
		$this->donation->process_refund(
			$this->interpreter->get_refund_amount(),
			$this->interpreter->get_refund_log_message()
		);

		$this->save_gateway_transaction_data();
		$this->update_meta();
		$this->update_logs();

		$this->set_response( __( 'Donation Webhook: Refund processed', 'charitable' ) );

		return true;
	}

	/**
	 * Process a failed payment for a donation.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean
	 */
	public function process_failed_payment() {
		$this->donation->update_status( 'charitable-failed' );

		$this->save_gateway_transaction_data();
		$this->update_meta();
		$this->update_logs();

		$this->set_response( __( 'Donation Webhook: Donation marked as failed.', 'charitable' ) );

		return true;
	}

	/**
	 * Process a completed payment.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean
	 */
	public function process_completed_payment() {
		$this->donation->update_status( 'charitable-completed' );

		$this->save_gateway_transaction_data();
		$this->update_meta();
		$this->update_logs();

		$this->set_response( __( 'Donation Webhook: Completed payment processed.', 'charitable' ) );

		return true;
	}

	/**
	 * Process a cancelled donation.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean
	 */
	public function process_cancellation() {
		$this->donation->update_status( 'charitable-cancelled' );

		$this->save_gateway_transaction_data();
		$this->update_meta();
		$this->update_logs();

		$this->set_response( __( 'Donation Webhook: Donation cancelled.', 'charitable' ) );

		return true;
	}

	/**
	 * Process a donation that has been updated in some way without
	 * the status necessarily changing.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean
	 */
	public function process_updated_donation() {
		$status = $this->interpreter->get_donation_status();

		/* Update the donation status if it's changed. */
		if ( $this->donation->get_status() !== $status ) {
			$this->donation->update_status( $status );
		}

		$this->update_meta();
		$this->update_logs();

		$this->set_response( __( 'Donation Webhook: Donation updated.', 'charitable' ) );

		return true;
	}

	/**
	 * Save the gateway transaction ID and URL if available.
	 *
	 * @since  1.7.0
	 *
	 * @return void
	 */
	public function save_gateway_transaction_data() {
		$this->donation->set_gateway_transaction_id( $this->interpreter->get_gateway_transaction_id() );

		/** @todo Replace with call to $this->donation->set_gateway_transaction_url() once it's in core */
		\Charitable\Packages\Webhooks\set_gateway_transaction_url( $this->interpreter->get_gateway_transaction_url(), $this->donation );
	}

	/**
	 * Update logs for the donation.
	 *
	 * @since  1.7.0
	 *
	 * @return void
	 */
	public function update_logs() {
		$log = $this->donation->log();

		foreach ( $this->interpreter->get_logs() as $message ) {
			$log->add( $message );
		}
	}

	/**
	 * Update meta for the donation.
	 *
	 * @since  1.7.0
	 *
	 * @return void
	 */
	public function update_meta() {
		foreach ( $this->interpreter->get_meta() as $meta_key => $meta_value ) {
			update_post_meta( $this->donation->ID, $meta_key, $meta_value );
		}
	}
}
