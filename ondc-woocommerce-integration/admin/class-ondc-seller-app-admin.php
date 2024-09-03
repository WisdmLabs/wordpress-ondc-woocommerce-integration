<?php
/**
 * The Core plugin class
 *
 * @link       https://www.sellwise.co.in
 * @since      1.0.0
 * @package    ONDC WooCommerce Integration
 */

namespace app\ondcSellerApp\admin;

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
class Ondc_Seller_App_Admin {

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
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}
	/**
	 * Add ondc categories meta box in product edit page sidebar
	 */
	public function add_ondc_categories_meta_box() {
		add_meta_box(
			'ondc_categories',
			__( 'ONDC Categories', 'ondc-woocommerce-integration' ),
			array( $this, 'ondc_categories_meta_box' ),
			'product',
			'side',
			'high'
		);
	}

	/**
	 * Ondc categories meta box
	 */
	public function ondc_categories_meta_box() {
		global $post;
		$product_id = $post->ID;

		$current_ondc_categories     = get_post_meta( $product_id, 'ondc_product_categories', true );
		$current_ondc_sub_categories = get_post_meta( $product_id, 'ondc_product_sub_categories', true );
		$current_ondc_categories     = ! empty( $current_ondc_categories ) ? $current_ondc_categories : '';
		$ondc_categories             = $this->get_ondc_categories();
		?>
		<div class="ondc-categories">
			<label for="ondc_product_categories"><?php esc_html_e( 'ONDC Category:', 'ondc-woocommerce-integration' ); ?></label>
			<select name="ondc_product_categories" id="ondc_product_categories">
				<option value=""><?php esc_html_e( 'Select ONDC Category', 'ondc-woocommerce-integration' ); ?></option>
				<?php foreach ( $ondc_categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category['id'] ); ?>" <?php selected( $current_ondc_categories, $category['id'] ); ?>><?php echo esc_html( $category['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php
		foreach ( $ondc_categories as $category ) {
			if ( ! isset( $category['sub_cats'] ) ) {
				continue;
			}
			$category_id = str_replace( ':', '', $category['id'] );
			?>
				<div class="ondc-sub-categories" id="ondc_product_sub_categories_<?php echo esc_attr( $category_id ); ?>_wrapper">
					<label for="ondc_product_sub_categories_<?php echo esc_attr( $category_id ); ?>"><?php echo sprintf( __( 'ONDC Sub Category for %s:', 'ondc-woocommerce-integration' ), $category['name'] ); ?></label>
					<select name="ondc_product_sub_categories[<?php echo esc_attr( $category['id'] ); ?>]" id="ondc_product_sub_categories_<?php echo esc_attr( $category_id ); ?>">
						<option value=""><?php echo sprintf( __( 'Select Sub Category for %s', 'ondc-woocommerce-integration' ), $category['name'] ); ?></option>
					<?php foreach ( $category['sub_cats'] as $sub_category ) : ?>
							<option value="<?php echo esc_attr( $sub_category ); ?>" <?php selected( $current_ondc_sub_categories, $sub_category ); ?>><?php echo esc_html( $sub_category ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
		}
		?>
		<?php
	}

	/**
	 * Add sync product to ondc meta box after product price
	 */
	public function add_sync_product_to_ondc_meta_box() {
		woocommerce_wp_checkbox(
			array(
				'id'    => 'ondc_product_sync',
				'label' => __(
					'Sync Product to ONDC',
					'ondc-woocommerce-integration'
				),
			)
		);
	}

	/**
	 * Save ondc categories
	 */
	public function save_ondc_metadata( $post_id ) {
		if ( isset( $_POST['ondc_product_categories'] ) ) { // @codingStandardsIgnoreLine
			$ondc_categories = sanitize_text_field( $_POST['ondc_product_categories'] );
			update_post_meta( $post_id, 'ondc_product_categories', $ondc_categories );
		}

		if ( isset( $_POST['ondc_product_sub_categories'] ) ) { // @codingStandardsIgnoreLine
			$ondc_sub_categories = sanitize_text_field( $_POST['ondc_product_sub_categories'][ $ondc_categories ] );
			update_post_meta( $post_id, 'ondc_product_sub_categories', $ondc_sub_categories );
		}

		if ( isset( $_POST['ondc_product_sync'] ) ) { // @codingStandardsIgnoreLine
			update_post_meta( $post_id, 'ondc_product_sync', 'yes' );
		} else {
			update_post_meta( $post_id, 'ondc_product_sync', 'no' );
		}
	}

	/**
	 * Get ondc categories
	 */
	public function get_ondc_categories() {
		$ondc_categories = array(
			'nic2004:52110' => array(
				'id'   => 'nic2004:52110',
				'name' => 'Retail',
			),
			'ONDC:RET10'    => array(
				'id'       => 'ONDC:RET10',
				'name'     => 'Grocery',
				'sub_cats' => array(
					'Fruits and Vegetables',
					'Masala & Seasoning',
					'Oil & Ghee',
					'Eggs, Meat & Fish',
					'Bakery, Cakes & Dairy',
					'Pet Care',
					'Detergents and Dishwash',
					'Dairy and Cheese',
					'Snacks, Dry Fruits, Nuts',
					'Pasta, Soup and Noodles',
					'Cereals and Breakfast',
					'Sauces, Spreads and Dips',
					'Chocolates and Biscuits',
					'Cooking and Baking Needs',
					'Tinned and Processed Food',
					'Atta, Flours and Sooji',
					'Rice and Rice Products',
					'Dals and Pulses',
					'Salt, Sugar and Jaggery',
					'Energy and Soft Drinks',
					'Water',
					'Tea and Coffee',
					'Fruit Juices and Fruit Drinks',
					'Snacks and Namkeen',
					'Ready to Cook and Eat',
					'Pickles and Chutney',
					'Indian Sweets',
					'Frozen Vegetables',
					'Frozen Snacks',
					'Gift Voucher',
				),
			),
			'ONDC:RET11'    => array(
				'id'   => 'ONDC:RET11',
				'name' => 'Food & Beverage',
			),
			'ONDC:RET12'    => array(
				'id'       => 'ONDC:RET12',
				'name'     => 'Fashion',
				'sub_cats' => array(
					'Shirts',
					'T Shirts',
					'Sweatshirts',
					'Kurtas & Kurta Sets',
					'Jackets & Coats',
					'Sweaters',
					'Stoles and Scarves',
					'Mufflers',
					'Suits',
					'Sherwanis',
					'Track Shirts',
					'Track Suits',
					'Unstitched Fabrics',
					'Dresses',
					'Tops',
					'Trousers',
					'Capris',
					'Coordinates',
					'Playsuits',
					'Jumpsuits',
					'Shrugs & Blouses',
					'Blazers & Waistcoats',
					'Tights, Leggings & Jeggings',
					'Track Pants',
					'Jeans',
					'Shorts',
					'Joggers',
					'Dhotis & Dhoti Pants',
					'Churidars',
					'Salwars',
					'Dungarees & Jumpsuits',
					'Skirts',
					'Clothing Sets',
					'Belts',
					'Caps & Hats',
					'Kurtis, Tunics',
					'Sarees',
					'Ethnic Wear',
					'Palazzos',
					'Dress Materials',
					'Lehenga Cholis',
					'Dupattas & Shawls',
					'Burqas & Hijabs',
					'Blouses',
					'Blouse Pieces',
					'Briefs',
					'Boxers',
					'Vests',
					'Robes',
					'Night Suits',
					'Thermal Wear',
					'Swim Bottoms',
					'Swimwear',
					'Bra',
					'Shapewear',
					'Sleepwear & Loungewear',
					'Camisoles',
					'Lingerie Sets & Accessories',
					'Bath Robes',
					'Towels',
					'Pyjamas',
					'Party Wear',
					'Innerwear & Sleepwear',
					'Nightwear & Loungewear',
					'Watches',
					'Gloves',
					'Socks',
					'Stockings',
					'Laces',
					'Soles & Charms',
					'Shoe Racks & Organisers',
					'Shoe Care - Accessories',
					'Flip-Flops & Flats',
					'Sandals & Floaters',
					'Backpacks',
					'Handbags',
					'Trolley, Luggage & Suitcases',
					'Formal Shoes',
					'Casual Shoes',
					'Sports Shoes',
					'Outdoor Shoes',
					'Work & Safety Shoes',
					'Ethnic Shoes',
					'Boots',
					'Heels',
					'Contact Lenses',
					'Eye Glasses',
					'Eye Glass Frames',
					'Sunglasses',
					'Contact Lens Cases',
					'Contact Lens Solutions',
					'Contact Lens Tweezers',
					'Eyeglasses Pouches & Cases',
					'Microfiber Wipes',
					'Eyewear Slings',
					'Bracelets',
					'Chains',
					'Mangalsutra',
					'Anklets',
					'Bangles & Bracelets',
					'Necklaces',
					'Earrings',
					'Jewellery Sets',
					'Nosepins & Noserings',
					'Pendants',
					'Rings',
					'Toe Rings',
					'Gold Coins',
					'Brooch',
				),
			),
			'ONDC:RET13'    => array(
				'id'       => 'ONDC:RET13',
				'name'     => 'Beauty & Personal Care',
				'sub_cats' => array(
					'Fragrance',
					'Bath Soaps and Gels',
					'Hair Oils, Care, and Styling',
					'Shampoos and Conditioners',
					'Shaving and Grooming',
					'Beard Care and Tools',
					'Grooming Tools and Accessories',
					'Makeup - Nail Care',
					'Makeup - Eyes',
					'Makeup - Face',
					'Makeup - Lips',
					'Makeup - Body',
					'Makeup - Remover',
					'Makeup - Sets and Kits',
					'Makeup - Tools and Brushes',
					'Makeup - Kits and Combos',
					'Skin Care - Face Cleansers',
					'Skin Care - Hand and Feet',
					'Body Care - Cleansers',
					'Body Care - Moisturizers',
					'Body Care - Loofah and Other Tools',
					'Body Care - Bath Salt and Additives',
					'Hair Care - Shampoo, Oils, Conditioners',
					'Skin Care - Lotions, Moisturisers, and Creams',
					'Skin Care - Oils and Serums',
					'Gift Voucher',
					'Trimmer',
					'Shaver',
					'Epilator',
					'Hair Straightener',
					'Hair Dryer',
					'Hair Curler',
					'Hair Crimper',
				),
			),
			'ONDC:RET14'    => array(
				'id'       => 'ONDC:RET14',
				'name'     => 'Electronics',
				'sub_cats' => array(
					'Mobile Phone',
					'Smart Watch',
					'Headset',
					'Laptop',
					'Desktop',
					'Tablet',
					'Keyboard',
					'Monitor',
					'Mouse',
					'Power Bank',
					'Earphone',
					'True Wireless Stereo (TWS)',
					'Adapter',
					'Cable',
					'Extension Cord',
					'Audio Accessories',
					'Home Audio',
					'Microphone',
					'Speaker',
					'Vehicle Audio',
					'Camcorder',
					'Camera',
					'Camera Bag',
					'Batteries',
					'Charger',
					'Camera Lens',
					'Photo Printer',
					'Tripod',
					'Camera Accessories',
					'UPS',
					'Networking Device',
					'Printer',
					'Printer Accessories',
					'Storage Drive',
					'Pen Drive',
					'Memory Card',
					'Computer Component',
					'Cooling Pad',
					'Docking Station',
					'Keyboard Guard',
					'Laptop Skin',
					'Laptop Stand',
					'Mousepad',
					'Laptop Bag',
					'Screen Protector',
					'Computer Accessories',
					'Computer Software',
					'Ebook Reader',
					'Tablet Accessories',
					'Gaming Controller',
					'Gaming Chair',
					'Gaming Accessories',
					'Gaming Console',
					'Video Games',
					'Mobile Cover',
					'Mobile Mount',
					'Mobile Screen Guard',
					'Selfie Stick',
					'Mobile Skin Sticker',
					'Biometrics',
					'Home Alarm',
					'Home Automation',
					'Smart Switch',
					'Smart Lighting',
					'Home Safe',
					'Intercom',
					'Sensor',
					'Virtual Reality Headset',
					'3D Glasses',
					'3D Modulator',
					'Projector',
					'Projector Screen',
					'Projector Mount',
					'Projector Accessories',
					'Video Player',
					'Digital Photo Frame',
					'Video Player Accessories',
					'Smart Band',
					'Smart Glasses',
					'Watch Strap Band',
					'Wearable Accessories',
				),
			),
			'ONDC:RET15'    => array(
				'id'       => 'ONDC:RET15',
				'name'     => 'Appliances',
				'sub_cats' => array(
					'Air Purifier',
					'Dehumidifier',
					'Humidifier',
					'Air Cleaner Accessories',
					'Air Conditioner',
					'Air Conditioner Accessories',
					'Air Cooler',
					'Smart TV',
					'Standard TV',
					'TV Mount',
					'Remote',
					'Streaming Device',
					'TV Accessories',
					'Home Theatre Projector',
					'TV Part',
					'TV Remote',
					'Set Top Box',
					'TV Stand',
					'Electric Brush',
					'Electric Iron',
					'Electric Sewing Machine',
					'Water Heater',
					'Heater Cables',
					'Air Heater',
					'Coffee Maker',
					'Beverage Maker',
					'Roti Maker',
					'Induction Cooktop',
					'Sandwich Maker',
					'Electric Cooker',
					'Electric Kettle',
					'Microwave Oven',
					'OTG',
					'Toaster',
					'Electric Air Fryer',
					'Cooking Appliance Accessories',
					'Coffee Grinder',
					'Food Processor',
					'Pasta Maker',
					'Food Processor Accessories',
					'Blender',
					'Juicer',
					'Mixer Grinder',
					'Wet Grinder',
					'Dishwasher',
					'Dishwasher Accessories',
					'Electric Chimney',
					'Kitchen Accessories',
					'Freezer',
					'Refrigerator',
					'Refrigerator Accessories',
					'Vacuum Cleaner',
					'Vacuum Cleaner Parts and Accessories',
					'Washing Machine',
					'Washing Machine Accessories',
					'Water Purifier',
					'Water Cooler',
					'Water Dispenser',
					'Water Purifier Service Kit',
					'Water Purifier Filter',
					'Water Purifier Candle',
					'Water Purifier Pipe',
					'Water Purifier Accessories',
					'Water Cooler Accessories',
					'Inverter',
					'Inverter Batteries',
					'Battery tray',
					'Voltage Stabilizer',
				),
			),
			'ONDC:RET16'    => array(
				'id'       => 'ONDC:RET16',
				'name'     => 'Home & Decor',
				'sub_cats' => array(
					'Home Decor',
					'Furniture',
					'Home Furnishing - Bedding and Linen',
					'Bins and Bathroom',
					'Car and Shoe Care',
					'Disposables and Garbage Bags',
					'Fresheners and Repellents',
					'Mops, Brushes and Scrubs',
					'Party and Festive Needs',
					'Flowers',
					'Pooja Needs',
					'Electricals',
					'Bathroom and Kitchen Fixtures',
					'Garden & Outdoor',
					'Sports and Fitness Equipment',
					'Cookware',
					'Serveware',
					'Kitchen Storage and Containers',
					'Kitchen Tools',
					'Closet/Laundry/Shoe Organization',
					'Toys and Games',
					'Stationery',
					'Gift Voucher',
				),
			),
			'ONDC:RET17'    => array(
				'id'   => 'ONDC:RET17',
				'name' => 'Agriculture',
			),
			'ONDC:RET18'    => array(
				'id'       => 'ONDC:RET18',
				'name'     => 'Health & Wellness',
				'sub_cats' => array(
					'Pain Relief',
					'Nutrition and Fitness Supplements',
					'Speciality Care',
					'Covid Essentials',
					'Diabetes Control',
					'Healthcare & Fitness Devices',
					'Ayurvedic',
					'Homeopathy',
					'Unani and Siddha',
					'Elder Care',
					'Baby Care',
					'Orthopaedic Care',
					'Mobility Aids',
					'Medicated Hair Care',
					'Medicated Skin Care',
					'Speciality Face Cleansers',
					'Gastric Care',
					'ENT Care',
					'Eye Care',
					'Cold and Cough',
					'Sexual Wellness',
					'Feminine Care',
					'Maternity Care',
					'Nursing and Feeding',
					'Hand Wash',
					'Sanitizers',
					'Baby Care - Wipes and Buds',
					'Baby Care - Rash Creams',
					'Baby Care - Diapers and Accessories',
					'Health and Safety',
					'Oral Care',
					'Contraceptives',
					'Breathe Easy',
					'Health Foods and Drinks',
					'Wound Care and Dressings',
					'Surgicals',
					'Mental Wellness',
					'Gift Voucher',
				),
			),
			'ONDC:RET19'    => array(
				'id'   => 'ONDC:RET19',
				'name' => 'Pharma',
			),
			'ONDC:RET1A'    => array(
				'id'   => 'ONDC:RET1A',
				'name' => 'Autoparts & Components',
			),
			'ONDC:RET1B'    => array(
				'id'   => 'ONDC:RET1B',
				'name' => 'Hardware and Industrial',
			),
			'ONDC:RET1C'    => array(
				'id'   => 'ONDC:RET1C',
				'name' => 'Building & Construction Supplies',
			),
			'ONDC:RET1D'    => array(
				'id'   => 'ONDC:RET1D',
				'name' => 'Chemicals',
			),
		);

		$ondc_categories = apply_filters( 'ondc_seller_app_ondc_categories', $ondc_categories );

		return $ondc_categories;
	}

	/**
	 * Add menu in admin
	 */
	public function add_menu() {
		add_menu_page(
			__( 'ONDC WooCommerce Integration', 'ondc-woocommerce-integration' ),
			__( 'ONDC WooCommerce Integration', 'ondc-woocommerce-integration' ),
			'manage_options',
			'ondc-woocommerce-integration',
			array( $this, 'ondc_seller_app_page' ),
			'dashicons-cart',
			6
		);

		// dd sub menu for ondc onboarding.
		add_submenu_page(
			'ondc-woocommerce-integration',
			__( 'ONDC WooCommerce Integration Onboarding', 'ondc-woocommerce-integration' ),
			__( 'ONDC WooCommerce Integration Onboarding', 'ondc-woocommerce-integration' ),
			'manage_options',
			'ondc-seller-app-onboarding',
			array( $this, 'ondc_seller_app_onboarding_page' )
		);

		// add sub menu for ondc subscription.
		add_submenu_page(
			'ondc-woocommerce-integration',
			__( 'ONDC Subscription', 'ondc-woocommerce-integration' ),
			__( 'ONDC Subscription', 'ondc-woocommerce-integration' ),
			'manage_options',
			'ondc-seller-app-subscription',
			array( $this, 'ondc_seller_app_subscription_page' )
		);
	}

	/**
	 * ONDC WooCommerce Integration page
	 */
	public function ondc_seller_app_page() {
		// $this->load_view( 'ondc-woocommerce-integration' );
	}

	/**
	 * ONDC WooCommerce Integration onboarding page
	 */
	public function ondc_seller_app_onboarding_page() {

		$wizard_handler = Wisdm_Wizard_Handler::get_instance();
		$link           = $wizard_handler->get_wizard_first_step_link( 'ondc-onboarding-wizard' );

		wp_safe_redirect( $link );

		// $onboarding = new \app\ondcSellerApp\includes\ONDC_Onboarding();

		// // get the step
		// $step = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : 'welcome';
		// if ( ! empty( $step ) ) {
		// $onboarding->load_view( $step );
		// } else {
		// $onboarding->welcome();
		// }
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'ondc-seller-app-admin', plugin_dir_url( __FILE__ ) . 'assets/css/ondc-seller-app-admin.css', array(), $this->version, 'all' );

		wp_enqueue_script( 'ondc-seller-app-admin', plugin_dir_url( __FILE__ ) . 'assets/js/ondc-seller-app-admin.js', array( 'jquery' ), $this->version, false );

		// add mppmyindia script.
		wp_enqueue_script( 'ondc-map-my-india', 'https://apis.mappls.com/advancedmaps/api/efa68c48a71425f8d8a93fc1bf693740/map_sdk?v=3.0&layer=vector', array( 'jquery' ), $this->version, false );
	}

	/**
	 * ONDC Subscription page
	 */
	public function ondc_seller_app_subscription_page() {
		// show a button to subscribe to ONDC.
		$subsctiption_link = add_query_arg( array( 'subscribed' => 'true' ) );

		$lookup_link = add_query_arg( array( 'lookup' => 'true' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ONDC Subscription', 'ondc-woocommerce-integration' ); ?></h1>
			<p><?php esc_html_e( 'Subscribe to ONDC to start selling on the platform.', 'ondc-woocommerce-integration' ); ?></p>
			<form method="get">
				<input type="hidden" name="page" value="ondc-seller-app-subscription">
				<select name="sub_type" id="ondc_sub_type">
					<option value="stagging"><?php esc_html_e( 'Stagging', 'ondc-woocommerce-integration' ); ?></option>
					<option value="pre-production"><?php esc_html_e( 'Pre-Production', 'ondc-woocommerce-integration' ); ?></option>
					<option value="beta-production"><?php esc_html_e( 'Beta Production', 'ondc-woocommerce-integration' ); ?></option>
					<option value="production"><?php esc_html_e( 'Production', 'ondc-woocommerce-integration' ); ?></option>
				</select>
				<input name="subscribed" type="submit" value="<?php esc_html_e( 'Subscribe', 'ondc-woocommerce-integration' ); ?>" class="button button-primary">
			</form>
			<!-- <a href="<?php echo esc_url( $subsctiption_link ); ?>" class="button button-primary"><?php esc_html_e( 'Subscribe to ONDC', 'ondc-woocommerce-integration' ); ?></a>
			<a href="<?php echo esc_url( $lookup_link ); ?>" class="button button-primary"><?php esc_html_e( 'Lookup', 'ondc-woocommerce-integration' ); ?></a> -->
		</div>
		<?php

		if ( isset( $_GET['subscribed'] ) ) { // @codingStandardsIgnoreLine
			$sub_type = isset( $_GET['sub_type'] ) ? sanitize_text_field( $_GET['sub_type'] ) : 'stagging'; // @codingStandardsIgnoreLine
			$ondc_api = new \app\ondcSellerApp\protocolLayer\ONDC_API_Endpoints(); // @codingStandardsIgnoreLine
			$ondc_api->subscribe( $sub_type );
		}

		if ( isset( $_GET['lookup'] ) ) { // @codingStandardsIgnoreLine
			$ondc_api = new \app\ondcSellerApp\protocolLayer\ONDC_API_Endpoints(); // @codingStandardsIgnoreLine
			$ondc_api->lookup();
		}
	}

	/**
	 * Add order label in order list
	 *
	 * @param string $columns Column name.
	 */
	public function add_ondc_order_label( $columns ) {
		$columns['ondc_order'] = __( 'ONDC Order', 'ondc-woocommerce-integration' );
		return $columns;
	}

	/**
	 * Add order label in order list
	 *
	 * @param string $column_name Column name.
	 * @param object $order Order object.
	 */
	public function add_ondc_order_label_value( $column_name, $order ) {
		if ( 'ondc_order' !== $column_name ) {
			return;
		}
		if ( 'yes' === $order->get_meta( 'ondc_order' ) ) {
			echo '<span class="ondc-order-yes">Yes</span>';
		} else {
			echo '<span class="ondc-order-no">No</span>';
		}
	}

	/**
	 * Ondc welcome handler
	 */
	public function welcome_handler() {
		// Return if no activation redirect transient is set. Or not network admin.
		if ( ! get_transient( '_ondc_activation_redirect' ) || is_network_admin() ) {
			return;
		}

		if ( get_transient( '_ondc_activation_redirect' ) ) {
			// Delete transient used for redirection.
			delete_transient( '_ondc_activation_redirect' );
			update_option( 'ondc_seller_app_onboarding', 1 );
			$wc_url = admin_url( 'index.php?page=wisdm-setup&wizard=ondc-onboarding-wizard&step=introduction' );
			wp_safe_redirect( $wc_url );
			exit;
		}
	}
}
