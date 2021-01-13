<?php
/**
 * Charitable Mollie template.
 *
 * @package   Charitable Mollie/Classes
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Template' ) ) :

	/**
	 * Template class.
	 *
	 * @since 1.0.0
	 */
	class Template extends \Charitable_Template {

		/**
		 * Set theme template path.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_theme_template_path() {
			/**
			 * Customize the directory to use for template files in themes/child themes.
			 *
			 * @since 1.0.0
			 *
			 * @param string $directory The directory, relative to the theme or child theme's root directory.
			 */
			return trailingslashit( apply_filters( 'charitable_mollie_theme_template_path', 'charitable/charitable-mollie' ) );
		}

		/**
		 * Return the base template path.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_base_template_path() {
			return charitable_mollie()->get_path( 'templates' );
		}
	}

endif;
