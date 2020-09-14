<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Mollie\Classes
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Admin\Admin' ) ) :

	/**
	 * Admin class.
	 *
	 * @since 1.0.0
	 */
	class Admin {

		/**
		 * The single static class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var   \Charitable\Pro\Mollie\Admin
		 */
		private static $instance = null;

		/**
		 * Create and return the class object.
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Admin();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( ! is_null( self::$instance ) ) {
				return;
			}

			self::$instance = $this;

			/**
			 * Add a direct link to the Extensions settings page from the plugin row.
			 */
			add_filter( 'plugin_action_links_' . plugin_basename( \charitable_mollie()->get_path() ), array( $this, 'add_plugin_action_links' ) );
		}

		/**
		 * Add custom links to the plugin actions.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $links Links to be added to plugin actions row.
		 * @return string[]
		 */
		public function add_plugin_action_links( $links ) {
			if ( \Charitable_Gateways::get_instance()->is_active_gateway( 'mollie' ) ) {
				$links[] = '<a href="' . admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_mollie' ) . '">' . __( 'Settings', 'charitable-mollie' ) . '</a>';
			} else {
				$activate_url = esc_url(
					add_query_arg(
						array(
							'charitable_action' => 'enable_gateway',
							'gateway_id'        => 'mollie',
							'_nonce'            => wp_create_nonce( 'gateway' ),
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);

				$links[] = '<a href="' . $activate_url . '">' . __( 'Activate Mollie Gateway', 'charitable-mollie' ) . '</a>';
			}

			return $links;
		}
	}

endif;
