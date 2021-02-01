<?php
/**
 * Class responsible for processing incoming webhooks related to subscriptions.
 *
 * @package   Charitable/Classes/Charitable_Webhook_Processor_Donations
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Webhooks\Processors;

use Charitable\Webhooks\Interpreters\SubscriptionInterpreterInterface;
use Charitable\Gateways\Processor as PaymentProcessor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscription Processor.
 *
 * @since 1.0.0
 */
class SubscriptionProcessor extends DonationProcessor {

	/**
	 * Interpreter.
	 *
	 * @since 1.0.0
	 *
	 * @var   SubscriptionIntepreterInterface
	 */
	protected $interpreter;

	/**
	 * The donation object.
	 *
	 * @since 1.0.0
	 *
	 * @var   Charitable_Donation|false
	 */
	protected $donation;

	/**
	 * The recurring donation object.
	 *
	 * @since 1.0.0
	 *
	 * @var   Charitable_Recurring_Donation|false
	 */
	protected $recurring_donation;

	/**
	 * Set up the processor.
	 *
	 * @since 1.0.0
	 *
	 * @param SubscriptionInterpreterInterface
	 */
	public function __construct( SubscriptionInterpreterInterface $interpreter ) {
		$this->interpreter = $interpreter;
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
	 * Process the webhook event.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean|void
	 */
	public function process() {
		$this->recurring_donation = $this->interpreter->get_recurring_donation();

		/* Without a recurring donation, there's nothing left to do. */
		if ( ! $this->recurring_donation ) {
			$this->set_response(
				__( 'Subscription Webhook: Event could not be matched to a valid Charitable subscription.', 'charitable' )
			);
			return false;
		}

		/* Also get the donation object. This will be false for renewals. */
		$this->donation = $this->interpreter->get_donation();

		/* If this isn't a renewal and there is no donation, there's nothing left to do. */
		if ( ! $this->donation && ! $this->interpreter->is_renewal() ) {
			$this->set_response(
				__( 'Subscription Webhook: Event could not be matched to a valid Charitable donation.', 'charitable' )
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
		 * @since 1.0.0
		 *
		 * @param boolean                          $processed   Whether the webhook event was processed.
		 * @param Charitable_Recurring_Donation    $donation    The recurring donation object.
		 * @param SubscriptionInterpreterInterface $interpreter The source interpreter.
		 */
		return apply_filters( 'charitable_webhook_processor_subscription_process_' . $event_type, false, $this->recurring_donation, $this->interpreter );
	}

	/**
	 * Process a renewal.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function process_renewal() {
		$donation_id = $this->recurring_donation->create_renewal_donation(
			array(
				'status' => 'charitable-completed'
			)
		);

		$this->donation = charitable_get_donation( $donation_id );

		$this->save_gateway_subscription_data();
		$this->update_meta();
		$this->update_logs();

		$this->recurring_donation->log()->add(
			sprintf(
				__( 'Renewal processed. <a href="%1$s">Donation #%2$d</a>', 'charitable' ),
				get_edit_post_link( $donation_id ),
				$donation_id
			)
		);

		$this->set_response( __( 'Subscription Webhook: Renewal processed', 'charitable' ) );

		return true;
	}

	/**
	 * Process first payment.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function process_first_payment() {
		/* Update the initial payment and mark it as complete. */
		$this->process_completed_payment();

		/* Activate the subscription. */
		$this->recurring_donation->renew();

		$this->save_gateway_subscription_data();
		$this->update_meta();
		$this->update_logs();

		$this->set_response( __( 'Subscription Webhook: First payment processed', 'charitable' ) );

		return true;
	}

	/**
	 * Save the gateway subscription ID and URL if available.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function save_gateway_subscription_data() {
		$this->recurring_donation->set_gateway_subscription_id( $this->interpreter->get_gateway_subscription_id() );

		/** @todo Replace with call to $this->donation->set_gateway_subscription_url() once it's in core */
		\Charitable\Packages\Webhooks\set_gateway_subscription_url( $this->interpreter->get_gateway_subscription_url(), $this->recurring_donation );
	}

	/**
	 * Update logs for the recurring donation.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function update_meta() {
		foreach ( $this->interpreter->get_meta() as $meta_key => $meta_value ) {
			update_post_meta( $this->donation->ID, $meta_key, $meta_value );
		}
	}
}
