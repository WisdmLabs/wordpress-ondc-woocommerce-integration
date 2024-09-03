<?php
/**
 * The Plugin Activation Class
 *
 * @link       https://www.sellwise.co.in
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */

namespace app\ondcSellerApp;

/**
 * The activation class
 *
 * @since 1.0.0
 */
class Ondc_Activator {

	/**
	 * The activation method
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Create tables.
		self::create_tables();

		self::create_files();

		// ONDC WooCommerce Integration onboarding.
		$ondc_onboarding = get_option( 'ondc_seller_app_onboarding', 0 );
		if ( ! $ondc_onboarding || 0 === $ondc_onboarding ) {
			set_transient( '_ondc_activation_redirect', 1, 30 );
		}
	}

	/**
	 * Create tables
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'ondc_message_queue';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			action varchar(255) NOT NULL,
			payload longtext NOT NULL,
			priority int(11) NOT NULL,
			timestamp varchar(255) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$table_name = $wpdb->prefix . 'ondc_message_queue_log';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			action varchar(255) NOT NULL,
			payload longtext NOT NULL,
			timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql );

	}

	/**
	 * Log directory
	 */
	private static function create_files() {
		// Install files and folders for uploading files and prevent hotlinking.
		$upload_dir = wp_upload_dir();

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/ondc-logs/',
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // @codingStandardsIgnoreLine
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // @codingStandardsIgnoreLine
					fclose( $file_handle ); // @codingStandardsIgnoreLine
				}
			}
		}
	}
}
