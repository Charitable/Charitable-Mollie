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

namespace Charitable\Packages\Webhooks;

use Charitable\Webhooks\Receivers;

/* We are on Charitable 1.7 or above. */
if ( version_compare( charitable()->get_version(), '1.7', '>=' ) ) {
	return;
}

/* The package has already been loaded. */
if ( defined( 'CHARITABLE_WEBHOOKS_PACKAGE_LOADED' ) && CHARITABLE_WEBHOOKS_PACKAGE_LOADED ) {
	return;
}

define( 'CHARITABLE_WEBHOOKS_PACKAGE_LOADED', true );

/**
 * Load all files in the package.
 */
require_once 'Webhooks/Interpreters/DonationInterpreterInterface.php';
require_once 'Webhooks/Interpreters/SubscriptionInterpreterInterface.php';
require_once 'Webhooks/Processors/ProcessorInterface.php';
require_once 'Webhooks/Processors/Processor.php';
require_once 'Webhooks/Processors/DonationProcessor.php';
require_once 'Webhooks/Processors/SubscriptionProcessor.php';
require_once 'Webhooks/Receivers/ReceiverInterface.php';
require_once 'Webhooks/Receivers.php';

/**
 * Handle the incoming webhook.
 *
 * @since  1.0.0
 *
 * @return false|void
 */
function handle( $source ) {
	/**
	 * Allow extension to hook into the handle a gateway's IPN.
	 *
	 * @since 1.0.0
	 */
	do_action( 'charitable_process_ipn_' . $source );

	$receiver = Receivers::get( $source );

	if ( is_null( $receiver ) ) {
		return false;
	}

	/* Validate the webhook. */
	if ( ! $receiver->is_valid_webhook() ) {
		status_header( $receiver->get_invalid_response_status() );
		die( $receiver->get_invalid_response_message() );
	}

	$processor = $receiver->get_processor();

	if ( ! $processor ) {
		status_header( 500 );
		die(
			sprintf(
				/* translators: %s: source of webhook */
				__( 'Missing webhook processor for %s.', 'charitable' ),
				$source
			)
		);
	}

	/* Process the webhook. */
	$processor->process();

	/* Set the status header. */
	status_header( $processor->get_response_status() );

	/* Die with a response message. */
	die( $processor->get_response_message() );
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

/**
 * Save the gateway's subscription URL.
 *
 * @since  1.7.0
 *
 * @param  string|false $url The URL of the subscription in the gateway account.
 * @return boolean
 */
function set_gateway_subscription_url( $url, $donation ) {
	if ( ! $url ) {
		return false;
	}

	$key = '_gateway_subscription_url';
	$url = charitable_sanitize_donation_meta( $url, $key );
	return update_post_meta( $donation->ID, $key, $url );
}