<?php
/**
 * A class responsible for providing convenient access donation
 * data, including the ability to structure it in a formatted way
 * for different payment gateways.
 *
 * @package   Charitable/Classes/Charitable_Donation_Data_Mapper
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0
 * @version   1.7.0
 */

namespace Charitable\PaymentProcessors;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data Mapper helper.
 *
 * @since 1.7.0
 */
class DonationDataMapper {

	/**
	 * Donation.
	 *
	 * @since 1.7.0
	 *
	 * @var   \Charitable_Donation
	 */
	private $donation;

	/**
	 * Data map.
	 *
	 * @since 1.7.0
	 *
	 * @var   array
	 */
	private $map;

	/**
	 * The mapped data, with the payment gateway's keys
	 * and the data from the donation.
	 *
	 * @since 1.7.0
	 *
	 * @var   array
	 */
	private $data;

	/**
	 * Create class object.
	 *
	 * @since 1.7.0
	 */
	public function __construct( \Charitable_Donation $donation ) {
		$this->donation = $donation;
	}

	/**
	 * Return donation properties.
	 *
	 * @since  1.7.0
	 *
	 * @param  string $prop The property to retrieve.
	 * @return mixed
	 */
	public function __get( $prop ) {
		$method = 'get_' . $prop;

		if ( method_exists( $this, $method ) ) {
			return call_user_func( array( $this, $method ) );
		}

		return $this->donation->get( $prop );
	}

	/**
	 * Get the full name of the donor.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_full_name() {
		return $this->donation->get( 'donor' )->get_name();
	}

	/**
	 * Get the URL that the donor should be returned to after
	 * completing their donation.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_return_url() {
		return charitable_get_permalink(
			'donation_receipt_page',
			array( 'donation_id' => $this->donation->ID )
		);
	}

	/**
	 * Get the URL that the donor should be returned to
	 * if they cancel their donation.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_cancel_url() {
		return charitable_get_permalink(
			'donation_cancel_page',
			array( 'donation_id' => $this->donation->ID )
		);
	}

	/**
	 * Return the URL that the gateway should send its
	 * webhook to.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_webhook_url() {
		return charitable_get_ipn_url( $this->donation->get_gateway() );
	}

	/**
	 * Return the sanitized donation amount.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_amount() {
		return $this->donation->get_total_donation_amount( true );
	}

	/**
	 * Return the donation amount in cents.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_amount_in_cents() {
		return $this->get_amount() * 100;
	}

	/**
	 * Return the donation description.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->donation->get_campaigns_donated_to();
	}

	/**
	 * Return the currency used for the donation.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_currency() {
		return charitable_get_currency();
	}

	/**
	 * Return the donation key.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_donation_key() {
		return $this->donation->get_donation_key();
	}

	/**
	 * Return the donation ID.
	 *
	 * @since  1.7.0
	 *
	 * @return int
	 */
	public function get_donation_id() {
		return $this->donation->ID;
	}

	/**
	 * Return the site locale.
	 *
	 * @since  1.7.0
	 *
	 * @return string
	 */
	public function get_locale() {
		return get_locale();
	}

	/**
	 * Add the map to be used for the mapped data.
	 *
	 * @since  1.7.0
	 *
	 * @param  array $map A key value map.
	 * @return void
	 */
	public function add_map( $map ) {
		$this->map = $map;
	}

	/**
	 * Return the mapped data.
	 *
	 * @since  1.7.0
	 *
	 * @return array
	 */
	public function get_mapped_data() {
		if ( ! isset( $this->data ) ) {
			if ( ! isset( $this->map ) ) {
				$this->map = $this->get_default_map();
			}

			$this->data = array();

			foreach ( $this->map as $key => $gateway_key ) {
				if ( false === strpos( $gateway_key, '.' ) ) {
					$this->data[ $gateway_key ] = $this->$key;
				} else {
					$parts = explode( '.', $gateway_key );
					$first = current( $parts );

					array_shift( $parts );

					if ( ! array_key_exists( $first, $this->data ) ) {
						$this->data[ $first ] = array();
					}

					$this->data[ $first ] = array_merge_recursive(
						$this->get_data_branch( array(), $parts, $key ),
						$this->data[ $first ]
					);
				}
			}
		}

		return $this->data;
	}

	/**
	 * Get the data to include in a particular branch.
	 *
	 * @since  1.7.0
	 *
	 * @param  array  $data     The full data branch as it currently is.
	 * @param  array  $branches The branches that will go into this branch.
	 * @param  string $key      The key of the value to get.
	 * @return array
	 */
	public function get_data_branch( $data, $branches, $key ) {
		$current = current( $branches );

		array_shift( $branches );

		/* We've reached the end. */
		if ( empty( $branches ) ) {
			$data[ $current ] = $this->$key;
			return $data;
		}

		$data[ $current ] = array();

		return $this->get_data_branch( $data, $branches, $key );
	}

	/**
	 * Return a default map to be used if no map was defined.
	 *
	 * @since  1.7.0
	 *
	 * @return array
	 */
	public function get_default_map() {
		$keys = array(
			'email',
			'first_name',
			'last_name',
			'full_name',
			'address',
			'address_2',
			'city',
			'country',
			'postcode',
			'donation_key',
			'donation_id',
			'amount',
			'currency',
			'description',
			'locale',
			'return_url',
			'cancel_url',
			'webhook_url',
		);

		return array_combine( $keys, $keys );
	}

	/**
	 * Return some or all of the data.
	 *
	 * @since  1.7.0
	 *
	 * @param  string[]|null $keys The keys to get, or null to get all data.
	 * @return array
	 */
	public function get_data( $keys = null ) {
		$data = $this->get_mapped_data();

		if ( ! is_array( $keys ) ) {
			return $data;
		}

		return array_intersect_key( $data, array_flip( $keys ) );
	}
}
