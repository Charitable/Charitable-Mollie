<?php
/**
 * description
 *
 * @package   WebhookReceivers/Classes
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     version
 * @version   version
 */

namespace Charitable\WebhookReceivers;

use \Charitable\WebhookReceivers\Receivers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handler class.
 *
 * @since 1.7.0
 */
class Handler {

	/**
	 * Handle the incoming webhook.
	 *
	 * @since  1.7.0
	 *
	 * @return false|void
	 */
	public static function handle( $source ) {
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
}
