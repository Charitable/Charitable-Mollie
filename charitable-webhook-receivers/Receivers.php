<?php
/**
 * description
 *
 * @package   package/Classes/class
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     version
 * @version   version
 */

namespace Charitable\WebhookReceivers;

use Charitable\WebhookReceivers\Receivers\ReceiverInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class
 *
 * @since version
 */
class Receivers {

	/**
	 * Registered receivers.
	 *
	 * @since 1.0.0
	 *
	 * @var   array
	 */
	public static $receivers = array();

	/**
	 * Return the correct Receiver for a webhook source.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $source The webhook source.
	 * @return ReceiverInterface|null
	 */
	public static function get( $source ) {
		if ( ! isset( self::$receivers[ $source ] ) ) {
			return null;
		}

		$receiver = new self::$receivers[ $source ];

		return $receiver instanceof ReceiverInterface ? $receiver : null;
	}

	/**
	 * Register an Receiver handler for a particular webhook source.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $source   The webhook source.
	 * @param  string $receiver The Receiver class name.
	 * @return void
	 */
	public static function register( $source, $receiver ) {
		self::$receivers[ $source ] = $receiver;
	}
}
