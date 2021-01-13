<?php
/**
 * Interface to be implemented by gateways.
 *
 * @package   Charitable/Classes/RequestInterface
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0
 * @version   1.7.0
 */

namespace Charitable\Gateways\Payment;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment Request interface.
 *
 * @since 1.7.0
 */
interface RequestInterface {
	/**
	 * Class instantiation.
	 *
	 * @since 1.7.0
	 *
	 * @param \Charitable\Helpers\DonationDataMapper $data The data mapper object.
	 */
	public function __construct( \Charitable\Helpers\DonationDataMapper $data );

	/**
	 * Prepare a request.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean Whether the request was successfully prepared.
	 */
	public function prepare_request();

	/**
	 * Make a request.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean Whether the request was successfully made.
	 */
	public function make_request();

	/**
	 * Return a response object.
	 *
	 * @since  1.7.0
	 *
	 * @return ResponseInterface
	 */
	public function get_response() : ResponseInterface;
}
