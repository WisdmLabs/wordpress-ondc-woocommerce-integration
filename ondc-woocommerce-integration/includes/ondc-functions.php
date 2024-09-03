<?php
/**
 * General functions for ONDC WooCommerce Integration
 *
 * @link       https://www.sellwise.co.in
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */

namespace app\ondcSellerApp;

/**
 * Get Timestamp.
 */
function wdm_ondc_get_timetamp() {
	$current_datetime = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
	return $current_datetime->format( 'Y-m-d\TH:i:s' ) . '.999Z';
}

/**
 * Get unique id.
 */
function wdm_ondc_get_unique_key_id() {
	return md5( uniqid( wp_rand(), true ) );
}
