<?php
/**
 * OODC Request Handler
 */
namespace app\ondcSellerApp\protocolLayer;

use app\ondcSellerApp as ondcSellerApp;

class ONDC_Request_Handler
{   
    private $domain;
    private $country;
    private $city;
    private $core_version;
    private $bpp_id;
    private $bpp_uri;
    private $bap_id;
    private $bap_uri;
    private $key;
    private $ttl;

    private $action = array(
        "search" => "on_search",
        "select" => "on_select",
        "init" => "on_init",
        "confirm" => "on_confirm",
        "status" => "on_status",
        "track" => "on_track",
        "cancel" => "on_cancel",
        "update" => "on_update",
        "rating" => "on_rating",
        "support" => "on_support",
    );

    public function __construct()
    {
        // load env variables from signing-verification/.env
        $dotenv = \Dotenv\Dotenv::createImmutable(ONDC_SELLER_APP_PLUGIN_DIR . '/signing-verification');
        $dotenv->safeload();

        $ondc_seller_app = get_option( 'ondc_seller_app' );

        $this->domain = 'ONDC:RET10';
        $this->country = 'IND';
        $this->city = 'std:022';
        $this->core_version = '1.2.0';
        $this->bpp_id = isset( $ondc_seller_app['user_data']['subscriber_id'] ) ? $ondc_seller_app['user_data']['subscriber_id'] : '';
        $this->bpp_uri = isset( $ondc_seller_app['user_data']['subscriber_url'] ) ? $ondc_seller_app['user_data']['subscriber_url'] : '';
        $this->bap_id = 'ref-app-buyer-staging-v2.ondc.org';
        $this->bap_uri = 'https://ref-app-buyer-staging-v2.ondc.org/protocol/test/v1/';
        $this->key = $_ENV['ENC_PUB_KEY'];
        $this->ttl = 'PT30S';
    }

    /**
     * Get context data
     */
    public function get_context($action, $options = array())
    {
        $context = array(
            "domain" => isset($options->domain) ? $options->domain : $this->domain,
            "country" => isset($options->country) ? $options->country : $this->country,
            "city" => isset($options->city) ? $options->city : $this->city,
            "action" => $action,
            "core_version" => isset($options->core_version) ? $options->core_version : $this->core_version,
            "bap_id" => isset($options->bap_id) ? $options->bap_id : $this->bap_id,
            "bap_uri" => isset($options->bap_uri) ? $options->bap_uri : $this->bap_uri,
            "bpp_id" => isset($options->bpp_id) ? $options->bpp_id : $this->bpp_id,
            "bpp_uri" => isset($options->bpp_uri) ? $options->bpp_uri : $this->bpp_uri,
            "transaction_id" => isset($options->transaction_id) ? $options->transaction_id : ondcSellerApp\wdm_ondc_get_unique_key_id(),
            "message_id" => isset($options->message_id) ? $options->message_id : ondcSellerApp\wdm_ondc_get_unique_key_id(),
            "timestamp" => ondcSellerApp\wdm_ondc_get_timetamp(),
            "ttl" => isset($options->ttl) ? $options->ttl : $this->ttl,
        );

        if(isset($options->transaction_id)){
            $context['transaction_id'] = $options->transaction_id;
        }
        if(isset($options->message_id)){
            $context['message_id'] = $options->message_id;
        }

        return $context;
    }

    /**
     * Send request to ONDC
     */
    public function send_request($action, $message, $options = array()){
        // get action
        $action = $this->action[$action];
        $context = $this->get_context($action, $options);
        $request = array(
            "context" => $context,
            "message" => $message,
        );

        $request_body = json_encode($request, JSON_UNESCAPED_SLASHES);

        $header = create_authorisation_header($request_body);
        if(isset($options->bap_uri)){
            // check if bap_uri have a trailing slash
            if(substr($options->bap_uri, -1) != '/'){
                $options->bap_uri = $options->bap_uri . '/';
            }
            $url = $options->bap_uri . $context['action'];
        } else {
            // staging url (for testing purpose
            $url = 'https://ref-app-buyer-staging-v2.ondc.org/protocol/test/v1/' . $context['action'];
        }

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $header,
            ),
            'body' => $request_body,
        ));
        
        $ondc_logger = new ondcSellerApp\includes\ONDC_Logger();
        $ondc_logger->log($request_body);
        $ondc_logger->log(wp_remote_retrieve_body($response));

        global $wpdb;
        $table = $wpdb->prefix . 'ondc_message_queue_log';
        $wpdb->insert(
            $table,
            array(
                'action' => $action,
                'payload' => json_encode($request_body),
                'timestamp' => ondcSellerApp\wdm_ondc_get_timetamp(),
            ),
            array(
                '%s',
                '%s',
                '%s',
            )
        );
        return $response;
    }
}