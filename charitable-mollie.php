<?php
/**
 * Plugin Name:       Charitable - Mollie
 * Plugin URI:        https://github.com/Charitable/Charitable-Mollie
 * Description:       Accept donations using Mollie.
 * Version:           1.0.0
 * Author:            WP Charitable
 * Author URI:        https://www.wpcharitable.com
 * Requires at least: 5.0
 * Tested up to:      5.5.1
 *
 * Text Domain: charitable-mollie
 * Domain Path: /languages/
 *
 * @package  Charitable Mollie
 * @category Core
 * @author   WP Charitable
 */

namespace Charitable\Pro\Mollie;

use \Charitable\Extensions\Activation\Activation;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin class, but only if Charitable is found and activated.
 *
 * @return false|\Charitable\Pro\Mollie\Mollie Whether the class was loaded.
 */
add_action(
	'plugins_loaded',
	function() {
		/* Load Composer packages. */
		require_once( 'vendor/autoload.php' );

		if ( class_exists( 'Charitable' ) ) {
			return new Mollie( __FILE__ );
		}

		/* Charitable is not installed, so add an activation/installation notice. */
		$activation = new Activation();

		/* translators: %s: link to activate Charitable */
		$activation->activation_notice = __( 'Charitable Mollie requires Charitable! Please <a href="%s">activate it</a> to continue.', 'charitable-mollie' );

		/* translators: %s: link to install Charitable */
		$activation->installation_notice = __( 'Charitable Mollie requires Charitable! Please <a href="%s">install it</a> to continue.', 'charitable-mollie' );

		$activation->run();
	}
);

/**
 * Set up the plugin autoloader.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Charitable\Pro\Mollie\Foo class
 * from includes/Foo.php:
 *
 *      new \Charitable\Pro\Mollie\Foo;
 *
 * @since  1.0.0
 *
 * @param  string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(
	function ( $class ) {
		/* Plugin namespace prefix. */
		$prefix = 'Charitable\\Pro\\Mollie\\';

		/* Base directory for the namespace prefix. */
		$base_dir = __DIR__ . '/includes/';

		/* Check if the class name uses the namespace prefix. */
		$len = strlen( $prefix );

		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}

		/* Get the relative class name. */
		$relative_class = substr( $class, $len );

		/* Get the file path. */
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		/* Bail out if the file doesn't exist. */
		if ( ! file_exists( $file ) ) {
			return;
		}

		/* Finally, require the file. */
		require $file;
	}
);
