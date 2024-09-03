<?php
/**
 * The plugin bootstrap file
 *
 * @link    https://www.sellwise.co.in
 * @since   1.0.0
 * @package ONDC WooCommerce Integration
 *
 * @WordPress-plugin
 * Plugin Name: ONDC WooCommerce Integration
 * Plugin URI: https://www.sellwise.co.in
 * Description: ONDC WooCommerce Integration, a plugin for sellers to manage their products.
 * Version: 1.0
 * Author: SellWise
 * Author URI: https://www.sellwise.co.in
 */

namespace app\ondcSellerApp;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Activate the plugin.
 */
function activate_ondc_seller_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ondc-activator.php';
	Ondc_Activator::activate();
}

// Eegister activation hook.
register_activation_hook( __FILE__, '\app\ondcSellerApp\activate_ondc_seller_app' );

/**
 * Deactivate the plugin.
 */
function deactivate_ondc_seller_app() {
	// require_once plugin_dir_path( __FILE__ ) . 'includes/class-ondc-deactivator.php';
	// Ondc_Deactivator::deactivate();
}

// register deactivation hook.
register_deactivation_hook( __FILE__, '\app\ondcSellerApp\deactivate_ondc_seller_app' );


// Load the plugin.
require plugin_dir_path( __FILE__ ) . 'includes/class-ondc-seller-app.php';

/**
 *  Initialize the plugin.
 */
function run_ondc_seller_app() {
	$plugin = new Ondc_Seller_App();
	$plugin->run();
}

// run the plugin.
run_ondc_seller_app();
