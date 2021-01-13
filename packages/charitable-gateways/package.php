<?php
/**
 * Core file responsible for loading the package if it needs to be.
 *
 * @package   Charitable
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Packages\Gateways;

use Charitable\Gateways\Payment\Processors as PaymentProcessors;

/* We are on Charitable 1.7 or above. */
if ( version_compare( charitable()->get_version(), '1.7', '>=' ) ) {
	return;
}

/* The package has already been loaded. */
if ( defined( 'CHARITABLE_GATEWAYS_PACKAGE_LOADED' ) && CHARITABLE_GATEWAYS_PACKAGE_LOADED ) {
	return;
}

define( 'CHARITABLE_GATEWAYS_PACKAGE_LOADED', true );

/**
 * Load all files in the package.
 */
require_once 'Gateways/Payment/ProcessorInterface.php';
require_once 'Gateways/Payment/RequestInterface.php';
require_once 'Gateways/Payment/ResponseInterface.php';
require_once 'Gateways/Payment/Processors.php';
require_once 'Helpers/DonationDataMapper.php';

/**
 * Process a donation's payment using the Payment Processor API.
 *
 * @since  1.0.0
 *
 * @return boolean|null|array
 */
function process_donation( $response, $donation_id, \Charitable_Donation_Processor $processor ) {
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
		set_gateway_transaction_url( $response->get_gateway_transaction_url(), $donation );

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
function set_gateway_transaction_url( $url, $donation ) {
	if ( ! $url ) {
		return false;
	}

	$key = '_gateway_transaction_url';
	$url = charitable_sanitize_donation_meta( $url, $key );
	return update_post_meta( $donation->ID, $key, $url );
}