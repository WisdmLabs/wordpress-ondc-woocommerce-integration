<?php
/**
 * The Setup Wizard class
 *
 * @link       https://www.sellwise.co.in
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */

namespace app\ondcSellerApp\admin;

/**
 * The Setup Wizard class
 */
class ONDC_Setup_Wizard {

	/**
	 * Setup Wizard initialization.
	 */
	public function __construct() {
		add_filter( 'wisdm_setup_wizards', array( $this, 'test_setup_wizard' ) );
	}

	/**
	 * Injects the wizard, steps and other data to Wisdm setup wizard.
	 *
	 * @param array $wizards The array of wizards.
	 * @return array
	 */
	public function test_setup_wizard( $wizards ) {

		$cpb_wizard = array(
			'ondc-onboarding-wizard' => array( // Unique wizard slug.
				'title'      => 'ONDC WooCommerce Integration Onboarding wizard', // Product Name.
				'capability' => 'manage_options', // The user must have this capability to load the wizard.
				'steps'      => array( // Sequential steps.
					'introduction'    => array( // step slug, every step slug must be unique.
						'step_title'    => 'Introduction', // This will display at the top as a step title.
						'view_callback' => array( $this, 'intro_view' ), // A callback function to display content of this step.
					),
					'user-registration' => array(
						'step_title'    => 'User Registration',
						'view_callback' => array( $this, 'user_registration' ),
						'save_callback' => array( $this, 'handle_form_submission' ), // A callback function to save the data of this step. Optional.
					),
					'store-profile'     => array(
						'step_title'    => 'Store Profile',
						'view_callback' => array( $this, 'store_profile' ),
						'save_callback' => array( $this, 'handle_form_submission' ),
					),
					'delivery-details'  => array(
						'step_title'    => 'Delivery',
						'view_callback' => array( $this, 'delivery_details' ),
						'save_callback' => array( $this, 'handle_form_submission' ),
					),
					'banking-details'   => array(
						'step_title'    => 'Banking',
						'view_callback' => array( $this, 'banking_details' ),
						'save_callback' => array( $this, 'handle_form_submission' ),
					),
					'complete'          => array(
						'step_title'    => 'Complete',
						'view_callback' => array( $this, 'complete' ),
						'save_callback' => array( $this, 'handle_form_submission' ),
					),
					'product-sync'      => array(
						'step_title'    => 'Product Sync',
						'view_callback' => array( $this, 'product_sync' ),
						'save_callback' => array( $this, 'handle_form_submission' ),
					),
					'category-sync'     => array(
						'step_title'    => 'Category Sync',
						'view_callback' => array( $this, 'category_sync' ),
						'save_callback' => array( $this, 'handle_form_submission' ),
					),
				),
			),
		);

		return array_merge( $wizards + $cpb_wizard );
	}

	/**
	 * This is the initialization step. Generally, we do not consider this as a step and ask the user for any action except continue or dismiss the setup.
	 */
	public function intro_view() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();
		?>
		<h1>Welcome to ONDC WooCommerce Integration</h1>
		<p>ONDC WooCommerce Integration is a plugin for sellers to manage their products.</p>
		<p>No time right now? If you don’t want to go through the wizard, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind!</p>

		<p><?php esc_html_e( 'To get started with ONDC WooCommerce Integration, you need to follow the below steps:', 'ondc-woocommerce-integration' ); ?></p>
		<ol>
			<li><?php esc_html_e( 'ONDC user registration', 'ondc-woocommerce-integration' ); ?></li>
			<li><?php esc_html_e( 'Store profile setup', 'ondc-woocommerce-integration' ); ?></li>
			<li><?php esc_html_e( 'Delivery address setup', 'ondc-woocommerce-integration' ); ?></li>
			<li><?php esc_html_e( 'Banking details setup', 'ondc-woocommerce-integration' ); ?></li>
			<li><?php esc_html_e( 'KYC documents upload', 'ondc-woocommerce-integration' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'Once you have completed the above steps, you can start adding your products to the ONDC platform.', 'ondc-woocommerce-integration' ); ?></p>

		<p class="wc-setup-actions step">
			<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button-primary button button-large button-next">Let's Go!</a>
			<a href="<?php echo esc_url( admin_url( 'index.php' ) ); ?>" class="button button-large">Not right now</a>
		</p>
		<?php
	}

	/**
	 * User registration steps.
	 */
	public function user_registration() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();

		$ondc_seller_app = get_option( 'ondc_seller_app' );
		$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="inp_textbox">Subsciber ID</label></th>
						<td>
							<input type="text" id="inp_textbox" name="subscriber_id" value="<?php echo esc_attr( isset( $ondc_seller_app['user_data']['subscriber_id'] ) ? $ondc_seller_app['user_data']['subscriber_id'] : '' ); ?>" class="location-input" value="">
							<p class="description">Enter ONDC Subscriber ID</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_textbox">Subsciber URL</label></th>
						<td>
							<input type="text" id="inp_textbox" name="subscriber_url" value="<?php echo esc_attr( isset( $ondc_seller_app['user_data']['subscriber_url'] ) ? $ondc_seller_app['user_data']['subscriber_url'] : '' ); ?>" class="location-input" value="">
							<p class="description">Enter ONDC Subscriber URL</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_textbox">Name</label></th>
						<td>
							<input type="text" id="inp_textbox" name="name" value="<?php echo esc_attr( isset( $ondc_seller_app['user_data']['name'] ) ? $ondc_seller_app['user_data']['name'] : '' ); ?>" class="location-input" value="">
							<p class="description">Enter your name</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_textbox">Email</label></th>
						<td>
							<input type="email" id="inp_textbox" name="email" value="<?php echo esc_attr( isset( $ondc_seller_app['user_data']['email'] ) ? $ondc_seller_app['user_data']['email'] : '' ); ?>" class="location-input" value="">
							<p class="description">Enter your email</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_textbox">Phone</label></th>
						<td>
							<input type="text" id="inp_textbox" name="phone" value="<?php echo esc_attr( isset( $ondc_seller_app['user_data']['phone'] ) ? $ondc_seller_app['user_data']['phone'] : '' ); ?>" class="location-input" value="">
							<p class="description">Enter your phone number</p>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button button-large button-next">Skip this step</a>
				<?php wp_nonce_field( 'ondc_seller_app_onboarding', 'ondc_seller_app_onboarding_nonce' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Store profile setup steps.
	 */
	public function store_profile() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();

		$ondc_seller_app = get_option( 'ondc_seller_app' );
		$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><label for="inp_textbox">Store Name</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_name" value="<?php echo esc_attr( isset( $ondc_seller_app['store_data']['store_name'] ) ? $ondc_seller_app['store_data']['store_name'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store name</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Contact phone</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_phone" value="<?php echo esc_attr( isset( $ondc_seller_app['store_data']['store_phone'] ) ? $ondc_seller_app['store_data']['store_phone'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store phone number</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Contact Email</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_email" value="<?php echo esc_attr( isset( $ondc_seller_app['store_data']['store_email'] ) ? $ondc_seller_app['store_data']['store_email'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store Email address</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">PAN Number</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_pan" value="<?php echo esc_attr( isset( $ondc_seller_app['store_data']['store_pan'] ) ? $ondc_seller_app['store_data']['store_pan'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store PAN number</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">GST Number</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_gst" value="<?php echo esc_attr( isset( $ondc_seller_app['store_data']['store_gst'] ) ? $ondc_seller_app['store_data']['store_gst'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store GST number</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">FSSAI Number (Optional)</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_fssai" value="<?php echo esc_attr( isset( $ondc_seller_app['store_data']['store_fssai'] ) ? $ondc_seller_app['store_data']['store_fssai'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store FSSAI number</p>
					</td>
				</tr>
				</tbody>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button button-large button-next">Skip this step</a>
				<?php wp_nonce_field( 'ondc_seller_app_onboarding', 'ondc_seller_app_onboarding_nonce' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Store location setup steps.
	 */
	public function delivery_details() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();

		$ondc_seller_app = get_option( 'ondc_seller_app' );
		$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><label for="inp_textbox">Locality</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_locality" value="<?php echo esc_attr( isset( $ondc_seller_app['delivery_data']['store_locality'] ) ? $ondc_seller_app['delivery_data']['store_locality'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store locality</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Street</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_street" value="<?php echo esc_attr( isset( $ondc_seller_app['delivery_data']['store_street'] ) ? $ondc_seller_app['delivery_data']['store_street'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store street</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">City</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_city" value="<?php echo esc_attr( isset( $ondc_seller_app['delivery_data']['store_city'] ) ? $ondc_seller_app['delivery_data']['store_city'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store city</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">State</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_state" value="<?php echo esc_attr( isset( $ondc_seller_app['delivery_data']['store_state'] ) ? $ondc_seller_app['delivery_data']['store_state'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store state</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Pincode</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_pincode" value="<?php echo esc_attr( isset( $ondc_seller_app['delivery_data']['store_pincode'] ) ? $ondc_seller_app['delivery_data']['store_pincode'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store pincode</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">GPS Coordinates</label></th>
					<td>
						<input type="text" id="inp_textbox" name="store_gps" value="<?php echo esc_attr( isset( $ondc_seller_app['delivery_data']['store_gps'] ) ? $ondc_seller_app['delivery_data']['store_gps'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your store GPS coordinates, you can get it from Google Maps</p>
						<div id="map" style="width:100%; margin-top:20px;"></div>
						<button id="wdm_get_current_location">Get Current Location</button>
						<script>
							<?php
							$location = $ondc_seller_app['delivery_data']['store_gps'];
							if( $location ) {
								$location = explode( ',', $location );
							}
							if( $location ) {
								$lat = $location[0];
								$lng = $location[1];
							} else {
								$lat = 28.612964;
								$lng = 77.229463;
							}
							?>
							map = new mappls.Map('map', {center:{lat:<?php echo esc_attr( $lat ); ?>, lng:<?php echo esc_attr( $lng ); ?>}, zoom:15}); // @codingStandardsIgnoreLine.
							var marker = new mappls.Marker({
								map: map,
								position: {"lat": <?php echo esc_attr( $lat ); ?>, "lng": <?php echo esc_attr( $lng ); ?>}, // @codingStandardsIgnoreLine.
							});

							function getLocation() {
								console.log('getLocation');
								if (navigator.geolocation) {
									navigator.geolocation.getCurrentPosition(showPosition);
								} else { 
									console.log("Geolocation is not supported by this browser.");
								}
							}

							function showPosition(position) {
								var lat = position.coords.latitude;
								var lng = position.coords.longitude;
								var coords = lat + ',' + lng;
								console.log(coords);
								document.getElementById('store_gps').value = coords;
								map.setCenter({lat:lat, lng:lng});
								marker.setPosition({lat:lat, lng:lng});
							}

							document.getElementById('wdm_get_current_location').addEventListener('click', function(e) {
								e.preventDefault();
								getLocation();
							});
						</script>
					</td>
				</tr>

				</tbody>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button button-large button-next">Skip this step</a>
				<?php wp_nonce_field( 'ondc_seller_app_onboarding', 'ondc_seller_app_onboarding_nonce' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Store Banking Details.
	 */
	public function banking_details() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();

		$ondc_seller_app = get_option( 'ondc_seller_app' );
		$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><label for="inp_textbox">Account Holder Name</label></th>
					<td>
						<input type="text" id="inp_textbox" name="account_holder_name" value="<?php echo esc_attr( isset( $ondc_seller_app['bank_data']['account_holder_name'] ) ? $ondc_seller_app['bank_data']['account_holder_name'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your account holder name</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Account Number</label></th>
					<td>
						<input type="text" id="inp_textbox" name="account_number" value="<?php echo esc_attr( isset( $ondc_seller_app['bank_data']['account_number'] ) ? $ondc_seller_app['bank_data']['account_number'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your account number</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">IFSC Code</label></th>
					<td>
						<input type="text" id="inp_textbox" name="ifsc_code" value="<?php echo esc_attr( isset( $ondc_seller_app['bank_data']['ifsc_code'] ) ? $ondc_seller_app['bank_data']['ifsc_code'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your IFSC code</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">UPI ID</label></th>
					<td>
						<input type="text" id="inp_textbox" name="upi_id" value="<?php echo esc_attr( isset( $ondc_seller_app['bank_data']['upi_id'] ) ? $ondc_seller_app['bank_data']['upi_id'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your UPI ID</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Bank Name</label></th>
					<td>
						<input type="text" id="inp_textbox" name="bank_name" value="<?php echo esc_attr( isset( $ondc_seller_app['bank_data']['bank_name'] ) ? $ondc_seller_app['bank_data']['bank_name'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your bank name</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inp_textbox">Branch Name</label></th>
					<td>
						<input type="text" id="inp_textbox" name="branch_name" value="<?php echo esc_attr( isset( $ondc_seller_app['bank_data']['branch_name'] ) ? $ondc_seller_app['bank_data']['branch_name'] : '' ); ?>" class="location-input" value="">
						<p class="description">Enter your branch name</p>
					</td>
				</tr>
				</tbody>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button button-large button-next">Skip this step</a>
				<?php wp_nonce_field( 'ondc_seller_app_onboarding', 'ondc_seller_app_onboarding_nonce' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Setup complete.
	 */
	public function complete() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();
		?>
		<h1>Congratulations!</h1>
		<p>You have successfully completed the ONDC WooCommerce Integration setup.</p>
		<p>You can now start adding your products to the ONDC platform.</p>

		<p><?php esc_html_e( 'for each product you add, you will need to provide the following details:', 'ondc-woocommerce-integration' ); ?></p>
		<ol>
				<li><?php esc_html_e( 'Product name', 'ondc-woocommerce-integration' ); ?></li>
				<li><?php esc_html_e( 'Product description', 'ondc-woocommerce-integration' ); ?></li>
				<li><?php esc_html_e( 'ONDC Product category', 'ondc-woocommerce-integration' ); ?></li>
				<li><?php esc_html_e( 'Product price', 'ondc-woocommerce-integration' ); ?></li>
				<li><?php esc_html_e( 'Product image', 'ondc-woocommerce-integration' ); ?></li>
			</ol>
			<p><?php esc_html_e( 'You can start adding your products or sync your existing products from the WooCommerce store.', 'ondc-woocommerce-integration' ); ?></p>

		<p class="wc-setup-actions step">
			<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button-primary button button-large button-next">Let's Start!</a>
			<a href="<?php echo esc_url( admin_url( 'index.php' ) ); ?>" class="button button-large">Not right now</a>
		</p>
		<?php
	}


	/**
	 * Product Sync.
	 */
	public function product_sync() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();

		$ondc_seller_app = get_option( 'ondc_seller_app' );
		$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
			<?php
				// get all woo products.
			$args     = array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
			);
			$products = get_posts( $args );

			// how all products in a table for bulk selection and sync.
			?>
			<p><?php esc_html_e( 'Select the products you want to sync to the ONDC platform:', 'ondc-woocommerce-integration' ); ?></p>
			<style>
				table{
					width: 100%;
					border-collapse: collapse;
				}
				table, th, td{
					border: 1px solid black;
				}
				th, td{
					text-align: center;
					padding: 5px;
					vertical-align: middle;
				}
			</style>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'ondc-woocommerce-integration' ); ?></label>
							<input id="cb-select-all-1" type="checkbox">
						</th>
						<th><?php esc_html_e( 'Product Name', 'ondc-woocommerce-integration' ); ?></th>
						<th><?php esc_html_e( 'Product Price', 'ondc-woocommerce-integration' ); ?></th>
						<th><?php esc_html_e( 'Product Category', 'ondc-woocommerce-integration' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $products as $product ) {
						$product_id         = $product->ID;
						$product_name       = $product->post_title;
						$product_price      = get_post_meta( $product_id, '_price', true );
						$product_categories = get_the_terms( $product_id, 'product_cat' );
						$product_category   = '';
						if ( ! empty( $product_categories ) ) {
							$product_category = $product_categories[0]->name;
						}
						?>
						<tr>
							<th class="check-column">
								<input type="checkbox" name="products[]" value="<?php echo esc_attr( $product_id ); ?>">
							</th>
							<td><?php echo esc_html( $product_name ); ?></td>
							<td><?php echo esc_html( $product_price ); ?></td>
							<td><?php echo esc_html( $product_category ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button button-large button-next">Skip this step</a>
				<?php wp_nonce_field( 'ondc_seller_app_onboarding', 'ondc_seller_app_onboarding_nonce' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Category Sync
	 */
	public function category_sync(){
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();

		$ondc_seller_app = get_option( 'ondc_seller_app' );
		$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
		<p><?php esc_html_e( 'Select the product categories you want to sync to the ONDC platform:', 'ondc-woocommerce-integration' ); ?></p>
				<?php
				// get all woo product categories
				$args = array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false,
				);
				$product_categories = get_terms( $args );

				// get all ondc categories.
				$ondc_admin      = new ONDC_Seller_App_Admin( $this->plugin_name, $this->version );
				$ondc_categories = $ondc_admin->get_ondc_categories();
				?>
				<table class="form-table">
					<thead>
					<tbody>
						<tr>
							<th scope="row">
								<label for="product_categories"><?php esc_html_e( 'Product Categories', 'ondc-woocommerce-integration' ); ?></label>
							</th>
							<td>
								<select name="product_categories[]" id="product_categories" class="regular-text" multiple>
									<?php
									foreach ( $product_categories as $product_category ) {
										?>
										<option value="<?php echo esc_attr( $product_category->term_id ); ?>"><?php echo esc_html( $product_category->name ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="ondc_categories"><?php esc_html_e( 'ONDC Categories', 'ondc-woocommerce-integration' ); ?></label>
							</th>
							<td>
								<select name="ondc_categories[]" id="ondc_categories" class="regular-text">
									<?php
									foreach ( $ondc_categories as $ondc_category ) {
										?>
										<option value="<?php echo esc_attr( $ondc_category['id'] ); ?>"><?php echo esc_html( $ondc_category['name'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			<p class="wc-setup-actions step">
				<?php
					$products_url = admin_url( 'edit.php?post_type=product' );
				?>
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $products_url ); ?>" class="button button-large button-next">Skip to Products</a>
				<?php wp_nonce_field( 'ondc_seller_app_onboarding', 'ondc_seller_app_onboarding_nonce' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * A sample function with possibly all HTML input fields to showcase how they would display in the form. Content is expected by wrapping in the <form> tag.
	 */
	public function form_fields_view() {
		$wizard_handler = Wisdm_Wizard_Handler::get_instance();
		?>
		<form method="post" action="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="inp_textbox">Textbox</label></th>
						<td>
							<input type="text" id="inp_textbox" name="inp_textbox" class="location-input" value="">
							<p class="description">The textbox input field</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="male">Radio Buttons</label></th>
						<td>
							<input type="radio" id="male" name="gender" value="male">
							<label for="male">Male</label><br>
							<input type="radio" id="female" name="gender" value="female">
							<label for="female">Female</label><br>
							<input type="radio" id="other" name="gender" value="other">
							<label for="other">Other</label>
							<p class="description">The Readio Button input field</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_checkbox">Checkbox/Switch</label></th>
						<td>
							<input type="checkbox" name="inp_checkbox" id="inp_checkbox" class="switch-input">
							<label for="inp_checkbox" class="switch-label">
								<span class="toggle--on">On</span>
								<span class="toggle--off">Off</span>
							</label>
							<span class="description">Checkbox input type.</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_color">Color</label></th>
						<td>
							<input type="color" id="inp_color" name="inp_color">
							<span class="description">Color input type. Probably it will behave differently in different browsers.</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_date">Date</label></th>
						<td>
							<input type="date" id="inp_date" name="inp_date">
							<span class="description">Date input type. Probably it will behave differently in different browsers.</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_email">Email</label></th>
						<td>
							<input type="email" id="inp_email" name="inp_email">
							<span class="description">Email input type.</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_file">File</label></th>
						<td>
							<input type="file" id="inp_file" name="inp_file">
							<span class="description">File input type.</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="inp_number">Number</label></th>
						<td>
							<input type="number" id="inp_number" name="inp_number" min="1" max="5">
							<span class="description">Number input type.</span>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="Continue" name="save_step">
				<input type="hidden" name="wisdm_setup_step" value="<?php echo esc_attr( $wizard_handler->get_current_step_slug() ); ?>" />
				<a href="<?php echo esc_url( $wizard_handler->get_next_step_link() ); ?>" class="button button-large button-next">Skip this step</a>
				<?php wp_nonce_field( 'name_of_my_action', 'name_of_nonce_field' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * A final step to tell the user that all steps are completed. Now, you decide what to do.
	 */
	public function ready_view() {
		$setup_wizard = Wisdm_Setup_Wizard::get_instance();
		?>
		<div class="wisdm-setup-done">
					<?php echo $setup_wizard->get_checked_image_html(); ?>
			<h1>All settings are done!</h1>
		</div>

		<div class="wisdm-setup-done-content">
			<p class="wc-setup-actions step">
				<a class="button button-primary" href="#">Setup Group Product</a>
				<a class="button" href="#">More Settings</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle form submission
	 */
	public function handle_form_submission() {
		// verify nonce.
		if ( isset( $_POST['ondc_seller_app_onboarding_nonce'] ) && wp_verify_nonce( $_POST['ondc_seller_app_onboarding_nonce'], 'ondc_seller_app_onboarding' ) ) {
			$view = $_POST['wisdm_setup_step']; // @codingStandardsIgnoreLine
			switch ( $view ) {
				case 'welcome':
					// handle welcome form submission.
					break;
				case 'user-registration':
					// handle user registration form submission.
					$name           = sanitize_text_field( $_POST['name'] ); // @codingStandardsIgnoreLine
					$email          = sanitize_email( $_POST['email'] ); // @codingStandardsIgnoreLine
					$phone          = sanitize_text_field( $_POST['phone'] ); // @codingStandardsIgnoreLine
					$subscriber_id  = sanitize_text_field( $_POST['subscriber_id'] ); // @codingStandardsIgnoreLine
					$subscriber_url = sanitize_text_field( $_POST['subscriber_url'] ); // @codingStandardsIgnoreLine

					$user_data = array(
						'name' => $name,
						'email' => $email,
						'phone' => $phone,
						'subscriber_id' => $subscriber_id,
						'subscriber_url' => $subscriber_url,
					);
					$ondc_seller_app = get_option( 'ondc_seller_app' );
					$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();

					$ondc_seller_app['user_data'] = $user_data;
					update_option( 'ondc_seller_app', $ondc_seller_app );
					return array(
						'status' => 'success',
						'message' => 'User registered successfully',
					);
				case 'store-profile':
					// handle admin profile form submission.
					$store_name  = sanitize_text_field( $_POST['store_name'] ); // @codingStandardsIgnoreLine
					$store_email = sanitize_email( $_POST['store_email'] ); // @codingStandardsIgnoreLine
					$store_phone = sanitize_text_field( $_POST['store_phone'] ); // @codingStandardsIgnoreLine
					$store_pan   = sanitize_text_field( $_POST['store_pan'] ); // @codingStandardsIgnoreLine
					$store_gst   = sanitize_text_field( $_POST['store_gst'] ); // @codingStandardsIgnoreLine
					$store_fssai = sanitize_text_field( $_POST['store_fssai'] ); // @codingStandardsIgnoreLine

					// // get address proof file and upload
					// $store_address_proof = $_FILES['store_address_proof'];
					// $store_address_proof_url = $this->upload_file( $store_address_proof );

					// // get ID proof file and upload
					// $store_id_proof = $_FILES['store_id_proof'];
					// $store_id_proof_url = $this->upload_file( $store_id_proof );

					// // get pan card file and upload
					// $store_pan_card = $_FILES['store_pan_card'];
					// $store_pan_card_url = $this->upload_file( $store_pan_card );

					// // get gst certificate file and upload
					// $store_gst_certificate = $_FILES['store_gst_certificate'];
					// $store_gst_certificate_url = $this->upload_file( $store_gst_certificate );

					$store_data = array(
						'store_name' => $store_name,
						'store_email' => $store_email,
						'store_phone' => $store_phone,
						'store_pan' => $store_pan,
						'store_gst' => $store_gst,
						'store_fssai' => $store_fssai,
					);
					// if ( ! empty( $store_address_proof_url ) ) {
					//     $store_data['store_address_proof'] = $store_address_proof_url;
					// }
					// if ( ! empty( $store_id_proof_url ) ) {
					//     $store_data['store_id_proof'] = $store_id_proof_url;
					// }
					// if ( ! empty( $store_pan_card_url ) ) {
					//     $store_data['store_pan_card'] = $store_pan_card_url;
					// }
					// if ( ! empty( $store_gst_certificate_url ) ) {
					//     $store_data['store_gst_certificate'] = $store_gst_certificate_url;
					// }

					$ondc_seller_app = get_option( 'ondc_seller_app' );
					$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();

					$ondc_seller_app['store_data'] = $store_data;
					update_option( 'ondc_seller_app', $ondc_seller_app );
					return array(
						'status' => 'success',
						'message' => 'Store profile saved successfully',
					);
				case 'delivery-details':
					$store_locality = sanitize_textarea_field( $_POST['store_locality'] ); // @codingStandardsIgnoreLine
					$store_street   = sanitize_textarea_field( $_POST['store_street'] ); // @codingStandardsIgnoreLine
					$store_city     = sanitize_text_field( $_POST['store_city'] ); // @codingStandardsIgnoreLine
					$store_state    = sanitize_text_field( $_POST['store_state'] ); // @codingStandardsIgnoreLine
					$store_pincode  = sanitize_text_field( $_POST['store_pincode'] ); // @codingStandardsIgnoreLine
					$store_gps      = sanitize_text_field( $_POST['store_gps'] ); // @codingStandardsIgnoreLine

					$store_data = array(
						'store_locality' => $store_locality,
						'store_street'   => $store_street,
						'store_city'     => $store_city,
						'store_state'    => $store_state,
						'store_pincode'  => $store_pincode,
						'store_gps'      => $store_gps,
					);

					$ondc_seller_app = get_option( 'ondc_seller_app' );
					$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();

					$ondc_seller_app['delivery_data'] = $store_data;
					update_option( 'ondc_seller_app', $ondc_seller_app );
					return array(
						'status' => 'success',
						'message' => 'Delivery details saved successfully',
					);
				case 'banking-details':
					// handle banking details form submission.
					$account_holder_name = sanitize_text_field( wp_unslash( $_POST['account_holder_name'] ) );
					$account_number      = sanitize_text_field( wp_unslash( $_POST['account_number'] ) );
					$ifsc_code           = sanitize_text_field( wp_unslash( $_POST['ifsc_code'] ) );
					$upi_id              = sanitize_text_field( wp_unslash( $_POST['upi_id'] ) );
					$bank_name           = sanitize_text_field( wp_unslash( $_POST['bank_name'] ) );
					$branch_name         = sanitize_text_field( wp_unslash( $_POST['branch_name'] ) );

					// get cancel cheque file and upload
					// $cancelled_cheque = $_FILES['cancelled_cheque'];
					// $cancelled_cheque_url = $this->upload_file( $cancelled_cheque );

					$bank_data = array(
						'account_holder_name' => $account_holder_name,
						'account_number'      => $account_number,
						'upi_id'              => $upi_id,
						'ifsc_code'           => $ifsc_code,
						'bank_name'           => $bank_name,
						'branch_name'         => $branch_name,
					);
					// if ( ! empty( $cancelled_cheque_url ) ) {
					//     $bank_data['cancelled_cheque'] = $cancelled_cheque_url;
					// }

					$ondc_seller_app = get_option( 'ondc_seller_app' );
					$ondc_seller_app = ! empty( $ondc_seller_app ) ? $ondc_seller_app : array();

					$ondc_seller_app['bank_data'] = $bank_data;
					update_option( 'ondc_seller_app', $ondc_seller_app );
					return array(
						'status' => 'success',
						'message' => 'Banking details saved successfully',
					);
				case 'product-sync':
					// handle product sync form submission.
					$products = $_POST['products']; // @codingStandardsIgnoreLine array of products
					foreach ( $products as $product_id ) {
						// check if valid product.
						$product = wc_get_product( $product_id );
						if ( ! $product ) {
							continue;
						}
						// set meta data for product.
						update_post_meta( $product_id, 'ondc_product_sync', 'yes' );
					}
					return array(
						'status' => 'success',
						'message' => 'Products synced successfully',
					);
				case 'category-sync':
					// handle category sync form submission.
					$product_categories = $_POST['product_categories']; // @codingStandardsIgnoreLine
					$ondc_categories    = $_POST['ondc_categories']; //// @codingStandardsIgnoreLine
					// get the products of all the select product categories and update the ondc category for them .
					foreach ( $product_categories as $product_category ) {
						$args = array(
							'post_type'      => 'product',
							'posts_per_page' => -1,
							'tax_query'      => array(
								array(
									'taxonomy' => 'product_cat',
									'field'    => 'term_id',
									'terms'    => $product_category,
								),
							),
						);
						$products = get_posts( $args );
						foreach ( $products as $product ) {
							$product_id = $product->ID;
							update_post_meta( $product_id, 'ondc_product_categories', $ondc_categories );
						}
					}
					return array(
						'status'  => 'success',
						'message' => 'Categories synced successfully',
					);
				default:
					return array(
						'status'  => 'error',
						'message' => 'Invalid form submission',
					);
			}
		}
	}
}
