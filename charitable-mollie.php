<?php
/**
 * Plugin Name:       Charitable - Mollie
 * Plugin URI:        https://github.com/Charitable/Charitable-Mollie
 * Description:       Accept donations using Mollie.
 * Version:           1.0.0-beta.1
 * Author:            WP Charitable
 * Author URI:        https://www.wpcharitable.com
 * Requires at least: 5.0
 * Tested up to:      5.6
 *
 * Text Domain: charitable-mollie
 * Domain Path: /languages/
 *
 * @package  Charitable Mollie
 * @category Core
 * @author   WP Charitable
 */

namespace Charitable\Pro\Mollie;

use Charitable\Extensions\Activation\Activation;

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
		/* Load Activation script. */
		require_once( 'vendor/wpcharitable/charitable-extension-activation/src/Activation.php' );

		$activation = new Activation( '1.6.45' );

		if ( $activation->ok() ) {
			spl_autoload_register( '\Charitable\Pro\Mollie\autoloader' );

			return new Mollie( __FILE__ );
		}

		/* translators: %s: link to activate Charitable */
		$activation->activation_notice = __( 'Charitable Mollie requires Charitable! Please <a href="%s">activate it</a> to continue.', 'charitable-mollie' );

		/* translators: %s: link to install Charitable */
		$activation->installation_notice = __( 'Charitable Mollie requires Charitable! Please <a href="%s">install it</a> to continue.', 'charitable-mollie' );

		/* translators: %s: link to update Charitable */
		$activation->update_notice = __( 'Charitable Mollie requires Charitable 1.6.45+! Please <a href="%s">update Charitable</a> to continue.', 'charitable-mollie' );

		$activation->run();

		return false;
	}
);

/**
 * Set up the plugin autoloader.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Charitable\Pro\Mollie\Foo class
 * from src/Foo.php:
 *
 *      new \Charitable\Pro\Mollie\Foo;
 *
 * @since  1.0.0
 *
 * @param  string $class The fully-qualified class name.
 * @return void
 */
function autoloader( $class ) {
	/* Plugin namespace prefix. */
	$prefix = 'Charitable\\Pro\\Mollie\\';

	/* Base directory for the namespace prefix. */
	$base_dir = __DIR__ . '/src/';

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
