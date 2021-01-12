<?php
/**
 * Interface to be implemented by gateways.
 *
 * @package   Charitable/Classes/ProcessorInterface
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0
 * @version   1.7.0
 */

namespace Charitable\PaymentProcessors\Payment;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment Processor interface.
 *
 * @since 1.7.0
 */
interface ProcessorInterface {
	/**
	 * Get the payment request object.
	 *
	 * @since  1.7.0
	 *
	 * @param  \Charitable_Donation $donation The donation to make a payment request for.
	 * @return \Charitable\PaymentProcessors\Payment\RequestInterface
	 */
	public function get_payment_request( \Charitable_Donation $donation );
}
