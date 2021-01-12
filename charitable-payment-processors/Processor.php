<?php
/**
 * description
 *
 * @package   PaymentProcessors/Classes
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     version
 * @version   version
 */

namespace Charitable\PaymentProcessors;

use \Charitable\PaymentProcessors\Receivers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processor class.
 *
 * @since 1.7.0
 */
class Processor {

	/**
	 * Process the payment for the donation.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean|null|array
	 */
	public static function process( $response, $donation_id, \Charitable_Donation_Processor $processor ) {
		$processor = PaymentProcessors::get( $processor->get_donation_data_value( 'gateway' ) );

		if ( is_null( $processor ) ) {
			return $response;
		}

		$donation = new \Charitable_Donation( $donation_id );
		$request  = $processor->get_payment_request( $donation );

		if ( $request->prepare_request() ) {
			$request->make_request();

			$response = $request->get_response();

			/* Save the gateway transaction ID */
			$donation->set_gateway_transaction_id( $response->get_gateway_transaction_id() );

			/** @todo when moving to core, replace with $donation->set_gateway_transaction_url() */
			self::set_gateway_transaction_url( $response->get_gateway_transaction_url(), $donation );

			foreach ( $response->get_logs() as $log ) {
				$donation->log()->add( $log );
			}

			foreach ( $response->get_meta() as $key => $value ) {
				update_post_meta( $this->donation_id, $key, $value );
			}

			if ( $response->payment_requires_redirect() ) {
				return array(
					'success'  => true,
					'redirect' => $response->get_redirect(),
				);
			}

			if ( $response->payment_requires_action() ) {
				return array_merge( array( 'requires_action' => true ), $response->get_required_action_data() );
			}

			if ( $response->payment_failed() ) {
				/** @todo Handle failed payment */
				$donation->update_status( 'charitable-failed' );
				return false;
			}

			if ( $response->payment_completed() ) {
				/** @todo Handle completed payment */
				$donation->update_status( 'charitable-completed' );
				return true;
			}

			if ( $response->payment_cancelled() ) {
				/** @todo Handle cancelled payment */
				$donation->update_status( 'charitable-cancelled' );
				return false;
			}
		}
	}

	/**
	 * Save the gateway's transaction URL.
	 *
	 * @since  1.7.0
	 *
	 * @param  string|false $url The URL of the transaction in the gateway account.
	 * @return boolean
	 */
	public static function set_gateway_transaction_url( $url, $donation ) {
		if ( ! $url ) {
			return false;
		}

		$key = '_gateway_transaction_url';
		$url = charitable_sanitize_donation_meta( $url, $key );
		return update_post_meta( $donation->ID, $key, $url );
	}
}
