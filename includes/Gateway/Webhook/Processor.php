<?php
/**
 * Process incoming Mollie webhooks.
 *
 * @package   Charitable Mollie/Classes/\Charitable\Pro\Mollie\Gateway\Webhook\Processor
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Webhook;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Webhook\Processor' ) ) :

	/**
	 * \Charitable\Pro\Mollie\Gateway\Webhook\Processor
	 *
	 * @since 1.0.0
	 */
	class Processor extends \Charitable_Webhook_Processor_Donations {

		/**
		 * Process refunds.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function process_refund() {
			$refund_log = array(
				'time'             => time(),
				'message'          => $this->interpreter->get_refund_log_message(), // Most recent refund note.
				'campaign_refunds' => array(),
				'total_refund'     => $this->interpreter->get_refund_amount(),
			);

			foreach ( $this->interpreter->get_refunds() as $refund ) {
				$refund_log['campaign_refunds'][] = $refund->amount->value;
			}

			update_post_meta( $this->donation->ID, 'donation_refund', $refund_log );

			$this->donation->update_status( 'charitable-refunded' );

			$this->save_gateway_transaction_data();
			$this->update_meta();
			$this->update_logs();

			$this->set_response( __( 'Donation Webhook: Refund processed', 'charitable' ) );

			return true;
		}
	}

endif;
