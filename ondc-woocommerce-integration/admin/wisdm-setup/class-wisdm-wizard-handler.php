<?php

/**
 * A wizards handler class.
 */
namespace app\ondcSellerApp\admin;
if ( ! class_exists( 'Wisdm_Wizard_Handler' ) ) {

	class Wisdm_Wizard_Handler {

		protected static $instance = null;

		/**
		 * Multiple wizards can be added to setup.
		 *  - Each wizard should have a unique slug.
		 *  - Each wizard must have a user capability.
		 *  - Each wizard must have steps.
		 *  - Each wizard must have a Title.
		 * array(
		 *  'ondc-onboarding-wizard' => array(
		 *    'title' => 'WISDM Custom Product Boxes', // Product Name
		 *    'capability' => 'manage_options', // The user must have this capability to load the wizard.
		 *    'scripts' => array( // Scripts to be loaded on wizard page.
		 *      'cpb-wizard-script-slug' => 'cpb-wizard-script-file-url',
		 *      'cpb-wizard-script-slug-2' => 'cpb-wizard-script-file-url-2',
		 *     ),
		 *    'styles' => array( // Styles to be loaded on wizard page.
		 *      'cpb-wizard-style-slug' => 'cpb-wizard-style-file-url',
		 *      'cpb-wizard-style-slug-2' => 'cpb-wizard-style-file-url-2',
		 *     ),
		 *    'steps' => array( // Sequential steps.
		 *      'step-slug-1' => array(
		 *       'step_title' => 'General Settings',
		 *       'arguments'  => $arguments,
		 *       'template'   => $template_file_path,
		 *      ),
		 *      'step-slug-2' => array(
		 *       'step_title' => 'Another Step',
		 *       'arguments'  => $arguments,
		 *       'template'   => $template_file_path,
		 *      ),
		 *     )
		 *  )
		 * )
		 *
		 * @var null | array
		 */
		protected $wizards = null;

		/**
		 * Current wizard slug.
		 *
		 * @var string
		 */
		protected $current_wizard_slug = null;

		/**
		 * A slug used to register the page.
		 *
		 * @var string
		 */
		protected $wizard_page_slug = 'wisdm-setup';

		private function __construct() {
			$this->wizards = apply_filters( 'wisdm_setup_wizards', array() );
			// echo '<pre>';
			// print_r( $this->wizards );
			// echo '</pre>';
			// $this->initiate_wizard();
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Initiate wizard.
		 *
		 * @return void
		 */
		protected function initiate_wizard() {

			$wizard_slug = $this->get_current_wizard_slug();

			// if ( null !== $wizard_slug && current_user_can( $this->get_wizard_capability( $wizard_slug ) ) ) {

			// 	$current_step_num = $this->get_current_step_number();
			// }
		}

		/**
		 * Wizard page slug.
		 *
		 * @return string
		 */
		public function get_wizard_page_slug() {
			return $this->wizard_page_slug;
		}

		/*
		|
		| About wizard.
		|
		*/

		/**
		 * Get title of the current setup wizard.
		 *
		 * @return string
		 */
		public function get_current_wizard_title() {
			$wizard_slug = $this->get_current_wizard_slug();
			return ( isset( $this->wizards[ $wizard_slug ] ) && isset( $this->wizards[ $wizard_slug ]['title'] ) ) ? $this->wizards[ $wizard_slug ]['title'] : '';
		}

		/**
		 * What's the current wizard slug?
		 *
		 * @return null | string
		 */
		public function get_current_wizard_slug() {
			if ( null === $this->current_wizard_slug ) {
				$wizard_slug = isset( $_GET['wizard'] ) ? trim( $_GET['wizard'] ) : null;
				if ( $this->validate_wizard_slug( $wizard_slug ) ) {
					$this->current_wizard_slug = $wizard_slug;
				}
			}
			return $this->current_wizard_slug;
		}

		/**
		 * Check if the slug is present in the dataset. Return true if it's present.
		 *
		 * @return boolean
		 */
		private function validate_wizard_slug( $wizard_slug ) {
			$is_valid = false; // True if we have wizard in our data.

			if ( null !== $wizard_slug && isset( $this->wizards[ $wizard_slug ] ) ) {
				$is_valid = true;
			}
			return $is_valid;
		}

		/**
		 * Returns capability for the given wizard slug.
		 *
		 * @param string $wizard_slug Wizard slug.
		 *
		 * @return boolean
		 */
		public function get_wizard_capability( $wizard_slug ) {
			$capability = null;
			if ( isset( $this->wizards[ $wizard_slug ]['capability'] ) ) {
				$capability = $this->wizards[ $wizard_slug ]['capability'];
			}
			return $capability;
		}

		/**
		 * Returns the landing page to the setup wizard. That is the first step.
		 *
		 * @param string $wizard_slug
		 * @return string
		 */
		public function get_wizard_first_step_link( $wizard_slug ) {
			$url = admin_url( 'index.php' );
			if ( $this->validate_wizard_slug( $wizard_slug ) ) {
				$first_step = ( isset( $this->wizards[ $wizard_slug ]['steps'] ) && is_array( $this->wizards[ $wizard_slug ]['steps'] ) && ! empty( $this->wizards[ $wizard_slug ]['steps'] ) ) ? key( $this->wizards[ $wizard_slug ]['steps'] ) : null;

				$url = add_query_arg(
					array(
						'page'   => $this->get_wizard_page_slug(),
						'wizard' => $wizard_slug,
						'step'   => $first_step,
					),
					$url
				);
			}
			return $url;
		}

		/*
		|
		| About steps.
		|
		*/

		/**
		 * Returns true if the given step is present in the current setup wizard.
		 *
		 * @param string $step_slug
		 *
		 * @return boolean
		 */
		public function check_if_step_exists( $step_slug ) {
			$does_exist  = false;
			$wizard_slug = $this->get_current_wizard_slug();

			if ( isset( $this->wizards[ $wizard_slug ]['steps'][ $step_slug ] ) ) {
				$does_exist = true;
			}
			return $does_exist;
		}

		/**
		 * Returns the current step slug. Null, if not found.
		 *
		 * @return string
		 */
		public function get_current_step_slug() {
			$step_slug       = null;
			$param_step_slug = isset( $_GET['step'] ) ? trim( $_GET['step'] ) : null;

			if ( $this->check_if_step_exists( $param_step_slug ) ) {
				$step_slug = $param_step_slug;
			}
			return $step_slug;
		}

		/**
		 * Returns callback method for the current step.
		 *
		 * @return mixed.
		 */
		public function get_current_step_view_callback() {
			$step_slug   = $this->get_current_step_slug();
			$wizard_slug = $this->get_current_wizard_slug();
			$callback    = null;

			if ( null !== $step_slug && isset( $this->wizards[ $wizard_slug ]['steps'][ $step_slug ]['view_callback'] ) ) {
				$callback = $this->wizards[ $wizard_slug ]['steps'][ $step_slug ]['view_callback'];
			}
			return $callback;
		}

		/**
		 * Returns callback method to save the data of the step.
		 *
		 * @return mixed.
		 */
		public function get_save_callback( $step_slug ) {
			$wizard_slug = $this->get_current_wizard_slug();
			$callback    = null;

			if ( null !== $step_slug && isset( $this->wizards[ $wizard_slug ]['steps'][ $step_slug ]['save_callback'] ) ) {
				$callback = $this->wizards[ $wizard_slug ]['steps'][ $step_slug ]['save_callback'];
			}
			return $callback;
		}

		/**
		 * Returns next step slug, null if not exists.
		 *
		 * @return mixed
		 */
		public function get_next_step_slug() {
			$next_step_slug    = null;
			$wizard_slug       = $this->get_current_wizard_slug();
			$current_step_slug = $this->get_current_step_slug();

			if ( null !== $current_step_slug && null !== $wizard_slug ) {
				$steps = $this->wizards[ $wizard_slug ]['steps'];
				// We are setting pointer to the current step.
				while ( key( $steps ) !== $current_step_slug ) {
					next( $steps );
				}
				// Now, we are moving one step ahead.
				next( $steps );
				$next_step_slug = key( $steps ); // NULL or string.
			}
			return $next_step_slug;
		}

		/**
		 * Returns the next step link.
		 *
		 * @return string
		 */
		public function get_next_step_link() {
			return add_query_arg(
				array(
					'step' => $this->get_next_step_slug(),
				)
			);
		}

		/**
		 * Returns all the steps of a current wizard.
		 *
		 * @return array
		 */
		public function get_current_wizard_steps() {
			$wizard_slug = $this->get_current_wizard_slug();
			return ( isset( $this->wizards[ $wizard_slug ] ) && isset( $this->wizards[ $wizard_slug ]['steps'] ) ) ? $this->wizards[ $wizard_slug ]['steps'] : '';
		}
	}

}
