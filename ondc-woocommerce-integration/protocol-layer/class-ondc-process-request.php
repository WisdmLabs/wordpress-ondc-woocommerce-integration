<?php
/**
 * ONDC request process class
 */
namespace app\ondcSellerApp\protocolLayer;

use app\ondcSellerApp as ondcSellerApp;

class ONDC_Process_Request
{
    private $ondc_data = array();

    private $store_logo = "https://ondc.wisdmlabs.net/wp-content/uploads/2024/06/wdm-logo.webp";

    private $store_location = "18.994278, 72.843486";

    private $store_address = array(
        "locality" => "123 Street",
        "street" => "Wisdmlabs",
        "city" => "Mumbai",
        "area_code" => "400001",
        "state" => "MH",
    );

    private $store_customer_care = "ABC Customer Care: 1234567890";

    private $total_price = 0;

    private $context;

    private $ondc_order_status = array(
        "processing" => "In-progress",
        "completed" => "Completed",
        "cancelled" => "Cancelled",
    );

    private $ondc_shipping_status = array(
        "partial-shipped" => "Out-for-delivery",
        "delivered" => "Order-delivered",
        "completed" => "Order-delivered",
    );

    public function __construct()
    {
        // do nothing
        $this->ondc_data = get_option('ondc_seller_app', array());

        $this->store_location = $this->ondc_data['delivery_data']['store_gps'];

        $this->store_address = array(
            "locality" => $this->ondc_data['delivery_data']['store_locality'],
            "street" => $this->ondc_data['delivery_data']['store_street'],
            "city" => $this->ondc_data['delivery_data']['store_city'],
            "area_code" => $this->ondc_data['delivery_data']['store_pincode'],
            "state" => $this->ondc_data['delivery_data']['store_state'],
        );

        $this->ondc_data['bank_data']['upi_address'] = isset($this->ondc_data['bank_data']['upi_id']) ? $this->ondc_data['bank_data']['upi_id'] : "upi@bank";

        $this->store_customer_care = $this->ondc_data['store_data']['store_name'] . " Customer Care: " . $this->ondc_data['store_data']['store_phone'];
    }

    public function process($action, $payload)
    {
        $this->context = $payload->context;
        $payload = $payload->message;
        switch ($action) {
            case 'search':
                $message = $this->search($payload);
                break;
            case 'select':
                $message = $this->select($payload);
                break;
            case 'init':
                $message = $this->init($payload);
                break;
            case 'confirm':
                $message = $this->confirm($payload);
                break;
            case 'status':
                $message = $this->status($payload);
                break;
            case 'track':
                $message = $this->track($payload);
                break;
            case 'cancel':
                $message = $this->cancel($payload);
                break;
            case 'update':
                $message = $this->update($payload);
                break;
            case 'rating':
                $message = $this->rating($payload);
                break;
            case 'support':
                $message = $this->support($payload);
                break;
            default:
                $message = array(
                    "status" => "error",
                    "message" => "Invalid action",
                );
                break;
        }

        return $message;
    }

    public function search($payload)
    {
        $message = array(
            "catalog" => array(
                "bpp/descriptor" => array(),
                "bpp/fulfillments" => array(),
                "bpp/providers" => array(),
            )
        );

        $message["catalog"]["bpp/descriptor"] = $this->get_descriptor();

        $message["catalog"]["bpp/fulfillments"] = $this->get_fulfillments();

        $message["catalog"]["bpp/providers"] = array(
            array(
                "id" => "WDM-ONDC-P1",
                "time" => array(
                    "label" => "enable",
                    "timestamp" => ondcSellerApp\wdm_ondc_get_timetamp(),
                ),
                "fulfillments" => array(
                    array(
                        "id" => "F1",
                        "type" => "Delivery",
                        "contact" => array(
                            "phone" => $this->ondc_data['store_data']['store_phone'],
                            "email" => $this->ondc_data['store_data']['store_email']
                        )
                    ),
                    array(
                        "id" => "F2",
                        "type" => "Self-Pickup",
                        "contact" => array(
                            "phone" => $this->ondc_data['store_data']['store_phone'],
                            "email" => $this->ondc_data['store_data']['store_email']
                        )
                    ),
                    array(
                        "id" => "F3",
                        "type" => "Delivery and Self-Pickup",
                        "contact" => array(
                            "phone" => $this->ondc_data['store_data']['store_phone'],
                            "email" => $this->ondc_data['store_data']['store_email']
                        )
                    )
                ),
                "descriptor" => $this->get_descriptor(0), // get descriptor without tags
                "ttl" => "P1D",
                "@ondc/org/fssai_license_no" => isset($this->ondc_data['store_data']['fssai_license_no']) ? $this->ondc_data['store_data']['fssai_license_no'] : "",
                "locations" => $this->get_store_locations(),
                "categories" => $this->get_categories($payload),
                "items" => $this->get_products($payload),
                "tags" => $this->get_provider_tags(),
            )
        );

        return $message;
    }

    public function select($payload)
    {
        $provider = $payload->order->provider->id;
        
        $message = array(
            "order" => array(
                "provider" => array(
                    "id" => $provider,
                    "locations" => array(
                        array(
                            "id" => $payload->order->provider->locations[0]->id,
                        )
                    )
                ),
                "items" => $this->get_selected_items($payload),
                "fulfillments" => array(
                        array(
                        "id" => "F1",
                        "type" => "Delivery",
                        "@ondc/org/provider_name" => $this->ondc_data['store_data']['store_name'],
                        "tracking" => false,
                        "@ondc/org/category" => "Immediate Delivery",
                        "@ondc/org/TAT" => "PT60M",
                        "state" => array(
                            "descriptor" => array(
                                "code" => "Serviceable"
                            )
                        )
                    )
                ),
                "quote" => $this->get_quote($payload),
            )
        );

        return $message;
    }

    public function init($payload)
    {
        $message = array(
            "order" => array(
                "provider" => $payload->order->provider,
                "items" => $payload->order->items,
                "billing" => $payload->order->billing,
                "fulfillments" => $payload->order->fulfillments,
                "quote" => $this->get_quote($payload),
                "payment" => $this->get_payment_details($payload),
                "cancellation_terms" => $this->get_cancellation_terms(),
                "tags" => $this->get_init_tags(),
            )
        );

        return $message;
    }

    public function confirm($payload)
    {

        // create woocommerce order
        $woo_address = array(
            'first_name' => $payload->order->billing->name,
            'last_name'  => '',
            'company'    => '',
            'email'      => $payload->order->billing->email,
            'phone'      => $payload->order->billing->phone,
            'address_1'  => $payload->order->billing->address->name . $payload->order->billing->address->building,
            'address_2'  => $payload->order->billing->address->locality,
            'city'       => $payload->order->billing->address->city,
            'state'      => $payload->order->billing->address->state,
            'postcode'   => $payload->order->billing->address->area_code,
            'country'    => $payload->order->billing->address->country
        );

        global $woocommerce;

        $order = \wc_create_order();

        foreach( $payload->order->items as $item ) {
            $order->add_product( get_product($item->id), $item->quantity->count);
        }
        $order->set_address( $woo_address, 'billing' );
        $order->calculate_totals();
        $order->update_status("processing", "ONDC order created", 1);

        // set order meta data
        $order->update_meta_data('ondc_order', 'yes');
        $order->update_meta_data('ondc_order_id', $payload->order->id);
        $order->update_meta_data('ondc_order_status', 'Accepted');
        $order->update_meta_data('ondc_order_data', $payload);
        $order->save();

        $message = array(
            "order" => array(
                "id" => $payload->order->id,
                "state" => "Accepted",
                "provider" => $payload->order->provider,
                "items" => $payload->order->items,
                "billing" => $payload->order->billing,
                "fulfillments" => $this->get_order_fulfillments('confirm', $payload),
                "quote" => $this->get_quote($payload),
                "payment" => $payload->order->payment,
                "cancellation_terms" => $this->get_cancellation_terms(),
                "tags" => $this->get_confirm_tags($payload->order->tags),
                "created_at" => $payload->order->created_at,
                "updated_at" => ondcSellerApp\wdm_ondc_get_timetamp(),
            )
        );

        return $message;
    }

    public function status($payload)
    {
        $ondc_order_id = $payload->order_id;

        $orders = wc_get_orders(
            array(
                'meta_query' => array(
                    array(
                        'key' => 'ondc_order_id',
                        'value' => $ondc_order_id,
                    )
                ),
            )
        );

        foreach ($orders as $order) {
            $order_id = $order->ID;
            $order_data = wc_get_order($order_id);
            $order_status = $order_data->get_status();
            $order_payload = $order_data->get_meta('ondc_order_data');
        }

        if(isset($this->ondc_order_status[$order_status])){
            $status = $this->ondc_order_status[$order_status];
        } else {
            $status = "In-progress";
        }

        if(isset($this->ondc_shipping_status[$order_status])){
            $shipping_status = $this->ondc_shipping_status[$order_status];
        } else {
            $shipping_status = false;
        }

        $fulfillment_args = array(
            "shipping_status" => $shipping_status,
        );

        $message = array(
            "order" => array(
                "id" => $ondc_order_id,
                "state" => $status,
                "provider" => $order_payload->order->provider,
                "items" => $order_payload->order->items,
                "billing" => $order_payload->order->billing,
                "fulfillments" => $this->get_order_fulfillments('status', $order_payload, $fulfillment_args),
                "quote" => $order_payload->order->quote,
                "payment" => $order_payload->order->payment,
                "created_at" => $order_payload->order->created_at,
                "updated_at" => ondcSellerApp\wdm_ondc_get_timetamp(),
            )
        );

        // check if invoice is available
        $invoice = false;
        if($invoice){
            $message["order"]["documents"][] = array(
                "url" => "https://invoice_url",
                "label" => "Invoice"
            );
        }

        return $message;
    }

    public function track($payload)
    {
        // do nothing
    }

    public function cancel($payload)
    {
        $ondc_order_id = $payload->order_id;

        $orders = wc_get_orders(
            array(
                'meta_query' => array(
                    array(
                        'key' => 'ondc_order_id',
                        'value' => $ondc_order_id,
                    )
                ),
            )
        );

        foreach ($orders as $order) {
            $order_id = $order->ID;
            $order_data = wc_get_order($order_id);
            $order_data->update_status("cancelled", "ONDC order cancelled", 1);
            $order_payload = $order_data->get_meta('ondc_order_data');
            $precancel_status = $order_data->get_meta('ondc_order_status');
        }

        $fulfillment_args = array(
            "precancel_status" => $precancel_status,
            "reason_id" => $payload->cancellation_reason_id,
        );
        $message = array(
            "order" => array(
                "id" => $ondc_order_id,
                "state" => "Cancelled",
                "provider" => $order_payload->order->provider,
                "items" => $order_payload->order->items,
                "billing" => $order_payload->order->billing,
                "cancellation" => array(
                    "cancelled_by" => "bap_id",
                    "reason" => array(
                        "id" => $payload->cancellation_reason_id
                    )
                ),
                "fulfillments" => $this->get_order_fulfillments('cancel', $order_payload, $fulfillment_args),
                "quote" => $this->get_quote($order_payload, "cancelled"),
                "payment" => $order_payload->order->payment,
                "created_at" => $order_payload->order->created_at,
                "updated_at" => ondcSellerApp\wdm_ondc_get_timetamp(),
            )
        );

        return $message;
    }

    public function update($payload)
    {
        // do nothing
    }

    public function rating($payload)
    {
        // do nothing
    }

    public function support($payload)
    {
        // do nothing
    }

    public function get_descriptor($tags = 1)
    {
        $descriptor = array(
            "name" => $this->ondc_data['store_data']['store_name'],
            "symbol" => isset($this->ondc_data['store_data']['store_logo']) ? $this->ondc_data['store_data']['store_logo'] : $this->store_logo,
            "short_desc" => isset($this->ondc_data['store_data']['short_desc']) ? $this->ondc_data['store_data']['short_desc'] : "SellWise store",
            "long_desc" => isset($this->ondc_data['store_data']['long_desc']) ? $this->ondc_data['store_data']['long_desc'] : "SellWise online store",
            "images" => array(
                isset($this->ondc_data['store_data']['store_logo']) ? $this->ondc_data['store_data']['store_logo'] : $this->store_logo,
            )
        );

        if ($tags) {
            $descriptor["tags"][] = array(
                "code" => "bpp_terms",
                "list" => array(
                    array(
                        "code" => "np_type",
                        "value" => "ISN"
                    ),
                    array(
                        "code" => "accept_bap_terms",
                        "value" => "Y"
                    ),
                    array(
                        "code" => "collect_payment",
                        "value" => "Y"
                    )
                )
            );
        }

        return $descriptor;
    }

    public function get_fulfillments()
    {
        $fulfillments = array(
            array(
                "id" => "1",
                "type" => "Delivery",
            ),
            array(
                "id" => "2",
                "type" => "Self-Pickup",
            ),
            // log error
            // array(
            //     "id" => "3",
            //     "type" => "Delivery and Self-Pickup",
            // ),
        );

        return $fulfillments;
    }

    public function get_categories($payload){
        // if variations are available then create variant groups
        $categories =  array(
            array(
                "id" => "WDM-ONDC-P1-C1",
                "descriptor" => array(
                    "name" => "Variant Group 1"
                ),
                "tags" => array(
                    array(
                        "code" => "type",
                        "list" => array(
                            array(
                                "code" => "type",
                                "value" => "variant_group"
                            )
                        )
                    ),
                    array(
                        "code" => "attr",
                        "list" => array(
                            array(
                                "code" => "name",
                                "value" => "item.quantity.unitized.measure"
                            ),
                            array(
                                "code" => "seq",
                                "value" => "1"
                            )
                        )
                    )
                )
            )
        );

        return $categories;
    }

    public function get_products($payload){

        $items = array();

        $category = $this->context->domain;
        // get product that are sync to ondc
        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => 'ondc_product_sync',
                    'value' => 'yes',
                ),
                array(
                    'key' => 'ondc_product_categories',
                    'value' => $category,
                )
            )
        );

        if ( isset($payload->intent->category->id) ) {
            $args['meta_query'][] = array(
                'key' => 'ondc_product_sub_categories',
                'value' => $payload->intent->category->id,
            );
        }

        if ( isset($payload->intent->item->descriptor->name) ) {
            $args['s' ] = $payload->intent->item->descriptor->name;
        }

        $products = get_posts($args);
        $items = array();
        foreach ($products as $product) {
            $product_id = $product->ID;
            $product_data = wc_get_product($product_id);
            $sub_category = get_post_meta( $product_id, 'ondc_product_sub_categories', true );
            $items[] = array(
                "id" => (string)$product_id,
                "time" => array(
                    "label" => "enable",
                    "timestamp" => ondcSellerApp\wdm_ondc_get_timetamp(),
                ),
                "parent_item_id" => "NA", // if variation then parent id
                "descriptor" => array(
                    "name" => $product_data->get_name(),
                    "code" => "5:12345678",
                    "symbol" => wp_get_attachment_image_url($product_data->get_image_id(), "full"),
                    "short_desc" => $product_data->get_short_description(),
                    "long_desc" => $product_data->get_description(),
                    "images" => array(
                        wp_get_attachment_image_url($product_data->get_image_id(), "full"),
                    ),
                ),
                "price" => array(
                    "value" => $product_data->is_on_sale() ? (string)$product_data->get_sale_price() : (string)$product_data->get_price(),
                    "maximum_value" => (string)$product_data->get_price(),
                    "currency" => "INR",
                ),
                "quantity" => array(
                    "unitized" => array(
                        "measure" => array(
                            "value" => "1",
                            "unit" => "unit"
                        )
                    ),
                    "available" => array(
                        // "count" => (string)$product_data->get_stock_quantity(),
                        "count" => "99"
                    ),
                    "maximum" => array(
                        "count" => (string)$product_data->get_stock_quantity(),
                    )
                ),
                "ttl" => "P1D",
                "category_id" => $sub_category,
                "fulfillment_id" => "F1",
                "location_id" => "WDM-ONDC-P1-L1",
                "@ondc/org/returnable" => false,
                "@ondc/org/cancellable" => true,
                "@ondc/org/return_window" => "P1D",
                "@ondc/org/seller_pickup_return" => false,
                "@ondc/org/time_to_ship"=> "PT45M",
                "@ondc/org/available_on_cod"=> false,
                "@ondc/org/contact_details_consumer_care"=> $this->store_customer_care,
                "tags" => $this->get_product_tags($product_data),
            );
        }

        

        return $items;
    }

    public function get_provider_tags(){
        $tags = array(
            array(
                "code" => "order_value",
                "list" => array(
                    array(
                        "code" => "min_value",
                        "value" => "30.00"
                    )
                )
            ),
            array(
                "code" => "catalog_link",
                "list" => array(
                    array(
                        "code" => "type",
                        "value" => "inline"
                    ),
                    array(
                        "code" => "type_value",
                        "value" => "https://s3.amazon.com/x-12349.zip"
                    ),
                    array(
                        "code" => "type_validity",
                        "value" => "PT24H"
                    ),
                    array(
                        "code" => "last_update",
                        "value" => ondcSellerApp\wdm_ondc_get_timetamp()
                    )
                )
            ),
            array(
                "code" => "timing",
                "list" => array(
                    array(
                        "code" => "type",
                        "value" => "Order"
                    ),
                    array(
                        "code" => "location",
                        "value" => "WDM-ONDC-P1-L1"
                    ),
                    array(
                        "code" => "day_from",
                        "value" => "1"
                    ),
                    array(
                        "code" => "day_to",
                        "value" => "7"
                    ),
                    array(
                        "code" => "time_from",
                        "value" => "0000"
                    ),
                    array(
                        "code" => "time_to",
                        "value" => "2359"
                    )
                )
            ),
            array(
                "code" => "timing",
                "list" => array(
                    array(
                        "code" => "type",
                        "value" => "Self-Pickup"
                    ),
                    array(
                        "code" => "location",
                        "value" => "L1"
                    ),
                    array(
                        "code" => "day_from",
                        "value" => "1"
                    ),
                    array(
                        "code" => "day_to",
                        "value" => "7"
                    ),
                    array(
                        "code" => "time_from",
                        "value" => "1100"
                    ),
                    array(
                        "code" => "time_to",
                        "value" => "2000"
                    )
                )
            ),
            array(
                "code" => "timing",
                "list" => array(
                    array(
                        "code" => "type",
                        "value" => "Delivery"
                    ),
                    array(
                        "code" => "location",
                        "value" => "L1"
                    ),
                    array(
                        "code" => "day_from",
                        "value" => "1"
                    ),
                    array(
                        "code" => "day_to",
                        "value" => "7"
                    ),
                    array(
                        "code" => "time_from",
                        "value" => "1100"
                    ),
                    array(
                        "code" => "time_to",
                        "value" => "2200"
                    )
                )
            ),
            array(
                "code" => "close_timing",
                "list" => array(
                    array(
                        "code" => "location",
                        "value" => "WD-ONDC-P1-L1"
                    ),
                    array(
                        "code" => "start",
                        "value" => "2023-06-03T16:00:00.000Z"
                    ),
                    array(
                        "code" => "end",
                        "value" => "2023-06-03T23:59:00.000Z"
                    )
                )
            ),
            array(
                "code" => "serviceability",
                "list" => array(
                    array(
                        "code" => "location",
                        "value" => "WD-ONDC-P1-L1"
                    ),
                    array(
                        "code" => "category",
                        "value" => "Foodgrains"
                    ),
                    array(
                        "code" => "type",
                        "value" => "10"
                    ),
                    array(
                        "code" => "val",
                        "value" => "3"
                    ),
                    array(
                        "code" => "unit",
                        "value" => "km"
                    )
                )
            )
        );

        return $tags;
    }

    public function get_store_locations(){
        $locations = array(
            array(
                "id" => "WDM-ONDC-P1-L1",
                "gps" => $this->store_location,
                "time" => array(
                    "label" => "enable",
                    "timestamp" => ondcSellerApp\wdm_ondc_get_timetamp(),
                    "days" => "1,2,3,4,5,6,7",
                    "schedule" => array(
                        "holidays" => array(
                            "2024-08-15"
                        ),
                        "frequency" => "PT4H",
                        "times" => array(
                            "1100",
                            "1900"
                        )
                    ),
                    "range" => array(
                        "start" => "1100",
                        "end" => "2100"
                    )
                ),
                "address" => $this->store_address,
                "circle" => array(
                    "gps" => $this->store_location,
                    "radius" => array(
                        "unit" => "km",
                        "value" => "30"
                    )
                )
            )
        );

        return $locations;
    }

    public function get_quote($payload, $status = "accepted"){
        $items = $payload->order->items;

        $this->total_price = 0;
        foreach ($items as $item) {
            $product = wc_get_product($item->id);
            $product_price = $product->is_on_sale() ? $product->get_sale_price() : $product->get_price();
            $quote_items = array(
                "@ondc/org/item_id" => (string)$item->id,
                "@ondc/org/item_quantity" => array(
                    "count" => $item->quantity->count,
                ),
                "title" => $product->get_name(),
                "@ondc/org/title_type" => "item",
                "price" => array(
                    "currency" => "INR",
                    "value" => (string)($product_price * $item->quantity->count),
                ),
                "item" => array(
                    "quantity" => array(
                        "available" => array(
                            // "count" => $product->get_stock_quantity(),
                            "count" => "99"
                        ),
                        "maximum" => array(
                            // "count" => $product->get_stock_quantity(),
                            "count" => "99"
                        )
                    ),
                    "price" => array(
                        "currency" => "INR",
                        "value" => (string)$product_price,
                    )
                )
            );

            $this->total_price += $product_price * $item->quantity->count;
        }

        $delivery_charge = 50;
        $delivery_charges = array(
            "@ondc/org/item_id" => "F1",
            "title" => "Delivery charges",
            "@ondc/org/title_type" => "delivery",
            "price" => array(
              "currency" => "INR",
              "value" => (string)$delivery_charge,
            )
        );

        $this->total_price += $delivery_charge;

        $quote = array(
            "price" => array(
                "currency" => "INR",
                "value" => (string)$this->total_price,
            ),
            "breakup" => array(
                $quote_items,
                $delivery_charges
            ),
            "ttl" => "P1D",
        );

        return $quote;
    }

    public function get_payment_details($payload){
        $payment = array(
            "type" => "ON-ORDER",
            "collected_by" => "BPP",
            "uri" => "https://snp.com/pg",
            "status" => "NOT-PAID",
            "@ondc/org/buyer_app_finder_fee_type" => "percent",
            "@ondc/org/buyer_app_finder_fee_amount" => "3",
            "@ondc/org/settlement_basis" => "delivery",
            "@ondc/org/settlement_window" => "P1D",
            "@ondc/org/withholding_amount" => "10.00",
            "tags" => array(
                array(
                    "code" => "bpp_collect",
                    "list" => array(
                        array(
                            "code" => "success",
                            "value" => "Y"
                        ),
                        array(
                            "code" => "error",
                            "value" => ".."
                        )
                    )
                )
            ),
            "@ondc/org/settlement_details" => array(
                array(
                    "settlement_counterparty" => $this->ondc_data['store_data']['store_name'],
                    "settlement_phase" => "sale-amount",
                    "settlement_type" => "upi",
                    "beneficiary_name" => $this->ondc_data['bank_data']['account_holder_name'],
                    "upi_address" => $this->ondc_data['bank_data']['upi_address'],
                    "settlement_bank_account_no" => $this->ondc_data['bank_data']['account_number'],
                    "settlement_ifsc_code" => $this->ondc_data['bank_data']['ifsc_code'],
                    "bank_name" => $this->ondc_data['bank_data']['bank_name'],
                    "branch_name" => $this->ondc_data['bank_data']['branch_name'],
                )
            )
        );

        return $payment;
    }

    public function get_cancellation_terms(){
        $ccancellation_terms = array(
            array(
                "fulfillment_state" => array(
                    "descriptor" => array(
                        "code" => "Pending",
                        "short_desc" => "002"
                    )
                ),
                "cancellation_fee" => array(
                    "percentage" => "0.00",
                    "amount" => array(
                        "currency" => "INR",
                        "value" => "0.00"
                    )
                )
            ),
            array(
                "fulfillment_state" => array(
                    "descriptor" => array(
                        "code" => "Packed",
                        "short_desc" => "001,003"
                    )
                ),
                "cancellation_fee" => array(
                    "percentage" => "10.00",
                    "amount" => array(
                        "currency" => "INR",
                        "value" => (string)($this->total_price * 10)
                    )
                )
            ),
            array(
                "fulfillment_state" => array(
                    "descriptor" => array(
                        "code" => "Order-picked-up",
                        "short_desc" => "001,003"
                    )
                ),
                "cancellation_fee" => array(
                    "percentage" => "10.00",
                    "amount" => array(
                        "currency" => "INR",
                        "value" => (string)($this->total_price * 10)
                    )
                )
            ),
            array(
                "fulfillment_state" => array(
                    "descriptor" => array(
                        "code" => "Out-for-delivery",
                        "short_desc" => "009"
                    )
                ),
                "cancellation_fee" => array(
                    "percentage" => "0.00",
                    "amount" => array(
                        "currency" => "INR",
                        "value" => "0.00"
                    )
                )
            ),
            array(
                "fulfillment_state" => array(
                    "descriptor" => array(
                        "code" => "Out-for-delivery",
                        "short_desc" => "010,011,012,013,014,015"
                    )
                ),
                "cancellation_fee" => array(
                    "percentage" => "20.00",
                    "amount" => array(
                        "currency" => "INR",
                        "value" => (string)($this->total_price * 20)
                    )
                )
            )
        );

        return $ccancellation_terms;
    }

    public function get_init_tags(){
        $tags = array(
            array(
                "code" => "bpp_terms",
                "list" => array(
                    array(
                        "code" => "max_liability",
                        "value" => "2"
                    ),
                    array(
                        "code" => "max_liability_cap",
                        "value" => "10000.00"
                    ),
                    array(
                        "code" => "mandatory_arbitration",
                        "value" => "false"
                    ),
                    array(
                        "code" => "court_jurisdiction",
                        "value" => "Bengaluru"
                    ),
                    array(
                        "code" => "delay_interest",
                        "value" => "7.50"
                    ),
                    array(
                        "code" => "tax_number",
                        "value" => $this->ondc_data['store_data']['store_gst']
                    ),
                    array(
                        "code" => "provider_tax_number",
                        "value" => $this->ondc_data['store_data']['store_pan']
                    )
                )
            )
        );
        
        return $tags;
    }

    public function get_confirm_tags($bap_tags){
        $init_tags = $this->get_init_tags();

        $bpp_terms = $init_tags[0]['list'];
        $bpp_terms[] = array(
            "code" => "np_type",
            "value" => "ISN"
        );

        $bpp_terms[] = array(
            "code" => "accept_bap_terms ",
            "value" => "Y"
        );
        
        $buyer_gst = "";
        foreach( $bap_tags as $tag ){
            if ( 'bap_terms' === $tag->code ) {
                $lists = $tag->list;
                foreach( $lists as $list ){
                    if ( 'tax_number' === $list->code ) {
                        $buyer_gst = $list->value;
                    }
                }
            }
        }

        $bap_terms = array(
            array(
                "code" => "static_terms",
                "value" => "https://github.com/ONDC-Official/protocol-network-extension/discussions/79"
            ),
              array(
                "code" => "tax_number",
                "value" => $buyer_gst
            )
        );

        $tags = array(
            array(
                "code" => "bpp_terms",
                "list" => $bpp_terms
                ),
            array(
                "code" => "bap_terms",
                "list" => $bap_terms
            )
        );

        return $tags;
    }

    public function get_order_fulfillments($action = 'confirm', $payload, $args = array()){
        $fulfillments = array(
            array(
                "id" => "F1",
                "@ondc/org/provider_name" => $this->ondc_data['store_data']['store_name'],
                "state" => array(
                    "descriptor" => array(
                        "code" => "Pending"
                    )
                ),
                "type" => "Delivery",
                "tracking" => false,
                "@ondc/org/TAT"=> "PT60M",
                "start" => array(
                    "location" => array(
                        "id" => "WDM-ONDC-P1-L1",
                        "descriptor" => array(
                            "name" => $this->ondc_data['store_data']['store_name'],
                        ),
                        "gps" => $this->store_location,
                        "address" => $this->store_address,
                    ),
                    "contact" => array(
                        "phone" => $this->ondc_data['store_data']['store_phone'],
                        "email" => $this->ondc_data['store_data']['store_email']
                    )
                ),
                "end" => $payload->order->fulfillments[0]->end,
            )
        );

        switch($action) {
            case 'confirm':
                break;
            case 'status':
                if( $args['shipping_status'] ) {
                    $fulfillments[0]["state"] = array(
                        "descriptor" => array(
                            "code" => $args['shipping_status']
                        )
                    );
                }
                break;
            case 'cancel':
                $fulfillments[0]["tags"] = array(
                    array(
                        "code" => "cancel_request",
                        "list" => array(
                            array(
                                "code" => "reason_id",
                                "value" => $args['reason_id']
                            ),
                            array(
                                "code" => "initiated_by",
                                "value" => $this->context->bap_id
                            ),
                        )
                    ),
                    array(
                        "code" => "precancel_state",
                        "list" => array(
                            array(
                                "code" => "fulfillment_state",
                                "value" => $args['precancel_status']
                            ),
                            array(
                                "code" => "updated_at",
                                "value" => ondcSellerApp\wdm_ondc_get_timetamp()
                            )
                        )
                    )
                );
                break;
        }

        return $fulfillments;
    }

    public function get_selected_items($payload){
        $items = $payload->order->items;
        $selected_items = array();
        foreach( $items as $item ){
            $product = wc_get_product($item->id);
            $selected_items[] = array(
                "id" => (string)$item->id,
                "fulfillment_id" => "F1",
            );
        }

        return $selected_items;
    }

    public function get_product_tags($product_data){
        $tags = array(
            array(
                "code" => "origin",
                "list" => array(
                    array(
                        "code" => "country",
                        "value" => "IND"
                    )
                )
            ),
            array(
                "code" => "image",
                "list" => array(
                    array(
                        "code" => "type",
                        "value" => "back_image"
                    ),
                    array(
                        "code" => "url",
                        "value" => wp_get_attachment_image_url($product_data->get_image_id(), "full"),
                    )
                )
            ),
            // array(
            //     "code" => "veg_nonveg",
            //     "list" => array(
            //         array(
            //             "code" => "veg",
            //             "value" => "yes"
            //         )
            //     )
            // )
        );

        // get category attribute json
        $url = ONDC_SELLER_APP_PLUGIN_URL . "protocol-layer/category-attributes.json";
        $request = wp_remote_get( $url );
        
        if( ! is_wp_error( $request ) ) {
            $body = wp_remote_retrieve_body( $request );
            $attributes = json_decode( $body );

            $category = get_post_meta( $product_data->get_id(), 'ondc_product_sub_categories', true );

            if ( isset( $attributes->$category ) ) {
                $attributes_list = array();
                foreach( $attributes->$category as $attribute ) {
                    $attributes_list[] = array(
                        "code" => strtolower( str_replace(' ', '_', $attribute) ),
                        "value" => apply_filters( 'ondc_product_attribute_value', 'NA', $product_data->get_id(), $attribute )
                    );
                }

                $tags[] = array(
                    "code" => "attribute",
                    "list" => $attributes_list
                );
            }
        }
        return $tags;
    }
}
