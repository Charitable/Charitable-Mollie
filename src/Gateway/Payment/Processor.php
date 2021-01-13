<?php
/**
 * The class responsible for creating payment requests for Mollie.
 *
 * @package   Charitable Mollie/Classes
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Gateway\Payment;

use Charitable\Helpers\DonationDataMapper;
use Charitable\Gateways\Payment\ProcessorInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Gateway\Payment\Processor' ) ) :

	/**
	 * Payment Processor.
	 *
	 * @since 1.0.0
	 */
	class Processor implements ProcessorInterface {

		/**
		 * Get the payment request object.
		 *
		 * @since  1.0.0
		 *
		 * @param  \Charitable_Donation $donation The donation to make a payment request for.
		 * @return \Charitable\Gateways\Payment\RequestInterface
		 */
		public function get_payment_request( \Charitable_Donation $donation ) {
			$data_map = new DonationDataMapper( $donation );
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

			return new Request( $data_map );
		}
	}

endif;
