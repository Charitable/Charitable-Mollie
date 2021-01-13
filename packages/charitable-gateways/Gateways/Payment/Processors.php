<?php
/**
 * description
 *
 * @package   package/Classes/class
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     version
 * @version   version
 */

namespace Charitable\Gateways\Payment;

use Charitable\Gateways\Payment\ProcessorInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class
 *
 * @since version
 */
class Processors {

	/**
	 * Registered processors.
	 *
	 * @since 1.0.0
	 *
	 * @var   array
	 */
	public static $processors = array();

	/**
	 * Return the correct Processor for a payment gateway.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $gateway The payment gateway.
	 * @return ProcessorInterface|null
	 */
	public static function get( $gateway ) {
		if ( ! isset( self::$processors[ $gateway ] ) ) {
			return null;
		}

		$processor = new self::$processors[ $gateway ];

		return $processor instanceof ProcessorInterface ? $processor : null;
	}

	/**
	 * Register a Processor handler for a particular payment gateway.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $gateway   The payment gateway.
	 * @param  string $processor The Processor class name.
	 * @return void
	 */
	public static function register( $gateway, $processor ) {
		self::$processors[ $gateway ] = $processor;
	}
}
