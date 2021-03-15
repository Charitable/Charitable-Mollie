<?php
/**
 * The class responsible for add donation fields.
 *
 * @package   Charitable Mollie/Classes
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

namespace Charitable\Pro\Mollie\Fields;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Charitable\Pro\Mollie\Fields\DonationFields' ) ) :

	/**
	 * \Charitable\Pro\Mollie\Fields\DonationFields
	 *
	 * @since 1.0.0
	 */
	class DonationFields {

		/**
		 * Add new donation fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public static function add_fields( $fields ) {
			$fields['mollie_customer_id'] = array(
				'label'          => __( 'Mollie Customer ID', 'charitable-mollie' ),
				'data_type'      => 'core',
				'value_callback' => function( \Charitable_Abstract_Donation $donation ) {
					$donor_id = $donation->get_donor_id();

					if ( ! $donor_id ) {
						return false;
					}

					$meta_postfix = $donation->get_test_mode( false ) ? 'test' : 'live';

					return get_metadata( 'donor', $donor_id, 'mollie_customer_id_' . $meta_postfix, true );
				},
				'donation_form'  => false,
				'admin_form'     => false,
				'show_in_meta'   => true,
				'show_in_export' => true,
				'email_tag'      => false,
			);

			return $fields;
		}
	}

endif;