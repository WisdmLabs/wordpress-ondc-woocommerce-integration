<?php
/**
 * The Core plugin class
 *
 * @link       https://www.sellwise.co.in
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */

namespace app\ondcSellerApp;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The Core plugin class
 *
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */
class Ondc_Seller_App {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Initialize the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->plugin_name = 'ondc-woocommerce-integration';
		$this->version     = '1.0.0';

		$this->load_dependencies();
		$this->define_constants();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_system_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ondc-seller-app-admin.php';
		// require_once plugin_dir_path( __FILE__ ) . 'class-ondc-onboarding.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ondc-functions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ondc-logger.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'signing-verification/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'protocol-layer/class-ondc-api-endpoints.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'protocol-layer/class-ondc-subscribe.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'protocol-layer/class-ondc-request-handler.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'protocol-layer/class-ondc-queue-handler.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'protocol-layer/class-ondc-process-request.php';

		// setup wizard.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wisdm-setup/class-wisdm-setup-wizard.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wisdm-setup/class-wisdm-wizard-handler.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ondc-setup-wizard.php';

	}

	/**
	 * Define constants used across the plugin.
	 */
	private function define_constants() {
		define( 'ONDC_STAGGING_URL', 'https://staging.registry.ondc.org' );
		define( 'ONDC_PRE_PRODUCTION_URL', 'https://preprod.registry.ondc.org/ondc' );
		define( 'ONDC_BETA_PRODUCTION_URL', 'https://beta.registry.ondc.org' );
		define( 'ONDC_PRODUCTION_URL', 'https://prod.registry.ondc.org' );

		// plugin root dir.
		define( 'ONDC_SELLER_APP_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'ONDC_SELLER_APP_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ondc_Seller_App_i18n class in order to set the domain and to register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$admin = new admin\Ondc_Seller_App_Admin( $this->get_plugin_name(), $this->get_version() );
		// add ondc categories meta box in product edit page sidebar.
		add_action( 'add_meta_boxes', array( $admin, 'add_ondc_categories_meta_box' ) );

		// add sync product to ondc meta box after product price.
		add_action( 'woocommerce_product_options_pricing', array( $admin, 'add_sync_product_to_ondc_meta_box' ) );

		// save ondc categories.
		add_action( 'save_post', array( $admin, 'save_ondc_metadata' ) );

		// add menu in admin.
		add_action( 'admin_menu', array( $admin, 'add_menu' ) );

		// enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );

		// add order label in order list.
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $admin, 'add_ondc_order_label' ) );

		// add order label in order list.
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $admin, 'add_ondc_order_label_value' ), 10, 2 );

		add_action( 'admin_init', array( $admin, 'welcome_handler' ) );

		$ondc_wizard    = new admin\ONDC_Setup_Wizard();
		$wizard_handler = admin\Wisdm_Wizard_Handler::get_instance();
		$link           = $wizard_handler->get_wizard_first_step_link( 'ondc-onboarding-wizard' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
	}

	/**
	 * Register all of the hooks related to the system functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_system_hooks() {

		$ondc_api = new protocolLayer\ONDC_API_Endpoints();

		$ondc_queue_handler = new protocolLayer\ONDC_Queue_Handler();

	}

	/**
	 * Get the plugin name.
	 *
	 * @since    1.0.0
	 * @return   string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the plugin version.
	 *
	 * @since    1.0.0
	 * @return   string    The version of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

