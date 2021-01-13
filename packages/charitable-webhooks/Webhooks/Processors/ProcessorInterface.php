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

namespace Charitable\Webhooks\Processors;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processor interface.
 */
Interface ProcessorInterface {

	/**
	 * Process the webhook.
	 *
	 * @since  1.7.0
	 *
	 * @return boolean|void
	 */
	public function process();

	/**
	 * Return the HTTP status to send for a processed event.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_response_status();

	/**
	 * Response text to send for a processed event.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_response_message();
}
