<?php
/**
 * The Setup Wizard class
 *
 * @link       https://www.sellwise.co.in
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */

namespace app\ondcSellerApp\admin;

if ( ! class_exists( 'Wisdm_Setup_Wizard' ) ) {

	class Wisdm_Setup_Wizard {

		/**
		 * A class instance
		 *
		 * @var Wisdm_Setup_Wizard
		 */
		private static $instance = null;

		/**
		 * Instance of Wisdm_Wizard_Handler class.
		 *
		 * @var Wisdm_Wizard_Handler
		 */
		protected $wizard_handler = null;

		/**
		 * Path to the assets directory of the setup wizard.
		 *
		 * @var string
		 */
		protected $assets_path = null;

		/**
		 * URL to the assets directory of the setup wizard.
		 *
		 * @var string
		 */
		protected $assets_url = null;

		/**
		 * Let's initiate the wizard.
		 *
		 * @return void
		 */
		private function __construct() {
			require_once 'class-wisdm-wizard-handler.php';
			$this->wizard_handler = Wisdm_Wizard_Handler::get_instance();

			$wizard_slug = $this->wizard_handler->get_current_wizard_slug();

			if ( null !== $wizard_slug && current_user_can( $this->wizard_handler->get_wizard_capability( $wizard_slug ) ) ) {

				// Setting up class variables.
				$this->assets_url  = plugin_dir_url( __FILE__ ) . 'assets';
				$this->assets_path = plugin_dir_path( __FILE__ ) . 'assets';

				add_action( 'admin_menu', array( $this, 'admin_menus' ) );
				add_action( 'admin_init', array( $this, 'setup_wizard' ), 99 );
			}
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Add admin menus/screens.
		 */
		public function admin_menus() {
			add_submenu_page( null, '', '', 'manage_options', $this->wizard_handler->get_wizard_page_slug(), '' );
		}

		/**
		 * Show the setup wizard.
		 */
		public function setup_wizard() {
			$get_data  = wp_unslash( $_GET ); // phpcs:ignore
			$post_data = wp_unslash( $_POST ); // phpcs:ignore

			if ( empty( $get_data['page'] ) || $this->wizard_handler->get_wizard_page_slug() !== $get_data['page'] ) {
				return;
			}

			if ( null === $this->wizard_handler->get_current_wizard_slug() ) {
				return;
			}

			$post_data_step_slug = isset( $_POST['wisdm_setup_step'] ) ? $_POST['wisdm_setup_step'] : null;

			if ( null !== $post_data_step_slug && $this->wizard_handler->check_if_step_exists( $post_data_step_slug ) && null !== $this->wizard_handler->get_save_callback( $post_data_step_slug ) ) {
				call_user_func( $this->wizard_handler->get_save_callback( $post_data_step_slug ) );
			}

			do_action( $this->wizard_handler->get_current_wizard_slug() . '_setup_wizard_initiated', $this->wizard_handler->get_current_step_slug() );

			$this->enqueue_scripts();

			ob_start();
			$this->set_setup_wizard_template();
			exit;
		}

		/**
		 * Enqueue scripts & styles
		 *
		 * @return void
		 */
		protected function enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
			// wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
			// wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.1' );
			// wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );

			// wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
			wp_enqueue_style( 'wc-setup', $this->assets_url . '/css/wc-setup.css', array( 'dashicons', 'install' ), filemtime( $this->assets_path . '/css/wc-setup.css' ) );
			wp_enqueue_style( 'wisdm-setup', $this->assets_url . '/css/setup.css', array( 'wc-setup' ), filemtime( $this->assets_path . '/css/setup.css' ) );

			wp_enqueue_script( 'ondc-map-my-india', 'https://apis.mappls.com/advancedmaps/api/efa68c48a71425f8d8a93fc1bf693740/map_sdk?v=3.0&layer=vector', array('jquery'), $this->version, false );
			// wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );
			// wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup.min.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui', 'wp-util', 'jquery-tiptip' ), WC_VERSION );

			/**
			 * Action fires after finishing enqueuing setup wizard assets.
			 *
			 */
			do_action( $this->wizard_handler->get_current_wizard_slug() . '_setup_wizard_enqueue_scripts', $this->wizard_handler->get_current_step_slug() );
		}

		/**
		 * Wizard templates
		 *
		 * @since 2.9.27
		 *
		 * @return void
		 */
		protected function set_setup_wizard_template() {
			$this->setup_wizard_header();
			$this->setup_wizard_steps();
			$this->setup_wizard_content();
			$this->setup_wizard_footer();
		}

		/**
		 * Setup Wizard Header.
		 */
		public function setup_wizard_header() {
			set_current_screen();
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>
			<?php
			echo esc_html( $this->wizard_handler->get_current_wizard_title() );
			esc_html_e( ' &rsaquo; Setup Wizard', 'ondc-woocommerce-integration' );
			?>
				</title>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>
			<body class="wc-setup wisdm-setup wisdm-admin-setup-wizard wp-core-ui">
				<?php
				$logo_url = plugins_url( 'assets/images/sell-wise-logo.png', __FILE__ );
				?>
				<h1 id="wc-logo"><a href="https://sellwise.co.in/"><img src="<?php echo esc_url( $logo_url ); ?>" alt="SellWise Logo" width="70" height="auto" /></a></h1>
				<?php
		}

		/**
		 * Output the steps.
		 */
		public function setup_wizard_steps() {
			$all_steps    = $this->wizard_handler->get_current_wizard_steps();
			$output_steps = $all_steps;
			array_shift( $output_steps ); // Because, we don't want to show the 'introduction' step in the list.

			$current_step_slug = $this->wizard_handler->get_current_step_slug();
			?>
			<ol class="wc-setup-steps">
			<?php foreach ( $output_steps as $step_key => $step ) : ?>
					<li class="
					<?php
					if ( $step_key === $current_step_slug ) {
						echo 'active';
					} elseif ( array_search( $current_step_slug, array_keys( $all_steps ), true ) > array_search( $step_key, array_keys( $all_steps ), true ) ) {
						echo 'done';
					}
					?>
					"><?php echo esc_html( $step['step_title'] ); ?></li>
				<?php endforeach; ?>
			</ol>
				<?php
		}

		/**
		 * Output the content for the current step.
		 */
		public function setup_wizard_content() {
			$callback_fn = $this->wizard_handler->get_current_step_view_callback();
			if ( empty( $callback_fn ) ) {
				wp_safe_redirect( esc_url_raw( $this->wizard_handler->get_wizard_first_step_link( $this->wizard_handler->get_current_wizard_slug() ) ) );
				exit;
			}
			?>
			<div class="wc-setup-content">
			<?php
			call_user_func( $callback_fn );
			?>
			</div>
			<?php
		}
		/**
		 * Setup Wizard Footer.
		 */
		public function setup_wizard_footer() {
			?>
			<?php if ( null === $this->wizard_handler->get_next_step_slug() ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( apply_filters( $this->wizard_handler->get_current_wizard_slug() . '_wisdm_setup_wizards_return_url', admin_url() ) ); ?>"><?php echo esc_html( apply_filters( $this->wizard_handler->get_current_wizard_slug() . '_wisdm_setup_wizards_return_text', 'Return to the WordPress Dashboard' ), 'ondc-woocommerce-integration' ); ?></a>
			<?php endif; ?>
			</body>
		</html>
			<?php
		}

		/**
		 * Returns img HTML tag for ticked image.
		 *
		 * @return string
		 */
		public function get_checked_image_html() {
			return '<img src="' . $this->assets_url . '/images/wisdm-checked.png" alt="' . __( 'DONE', 'ondc-woocommerce-integration' ) . '" />';
		}
	}
}

add_action(
	'plugins_loaded',
	function() {
		Wisdm_Setup_Wizard::get_instance();
	}
);
