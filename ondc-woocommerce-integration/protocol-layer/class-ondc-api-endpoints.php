<?php
/**
 * ONDC protocol layer
 */
namespace app\ondcSellerApp\protocolLayer;

use app\ondcSellerApp as ondcSellerApp;

class ONDC_API_Endpoints
{
    private $signing_private_key;
    private $signing_public_key;
    private $encryption_private_key;
    private $encryption_public_key;

    private $endpoints = array(
        "search",
        "select",
        "init",
        "confirm",
        "status",
        "track",
        "cancel",
        "update",
        "rating",
        "support",
        "on_search",
        "on_select",
        "on_init",
        "on_confirm",
        "on_track",
        "on_cancel",
        "on_update",
        "on_status",
        "on_rating",
        "on_support",
        "get_cancellation_reasons",
        "cancellation_reasons",
        "get_return_reasons",
        "return_reasons",
        "get_rating_categories",
        "rating_categories",
        "get_feedback_categories",
        "feedback_categories",
        "get_feedback_form",
        "feedback_form"
    );

    public function __construct()
    {
        // load env variables from signing-verification/.env
        $dotenv = \Dotenv\Dotenv::createImmutable(ONDC_SELLER_APP_PLUGIN_DIR . '/signing-verification');
        $dotenv->safeload();

        // load private and public keys
        $this->signing_private_key = $_ENV['SIGNING_PRIV_KEY'];
        $this->signing_public_key = $_ENV['SIGNING_PUB_KEY'];
        $this->encryption_private_key = $_ENV['ENC_PRIV_KEY'];
        $this->encryption_public_key = $_ENV['ENC_PUB_KEY'];

        // register all endpoints
        add_action('rest_api_init', array($this, 'register_endpoints'));

        // check if there is request for ondc-site-verification.html file
        add_action( 'parse_request', array( $this, 'ondc_site_verification' ) );
    }

    public function register_endpoints()
    {
        foreach ($this->endpoints as $endpoint) {
            register_rest_route('ondc/v1',  $endpoint, array(
                'methods' => \WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'handle_ondc_request'),
                'permission_callback' => '__return_true'
            ));
        }

        register_rest_route('ondc/v1', '/on_subscribe', array(
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => array($this, 'on_subscribe'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_ondc_request($request_data)
    {
        $ondc_queue_handler = new ONDC_Queue_Handler();
        $message = array(
            "ack" => array(
                "status" => "ACK"
            )
            );
        $params = $request_data->get_body();
        $params = json_decode($params);
        if( isset($params->context) ){
            $action = $params->context->action;
            $ondc_queue_handler->add_message($action, $params, 1);
        } else {
            $message["ack"]["status"] = "NACK";
            $ondc_queue_handler->add_message('search', $params, 1);
        }

        // add log
        $ondc_logger = new ondcSellerApp\includes\ONDC_Logger();
        $ondc_logger->log($request_data->get_body());

        $request_handler = new ONDC_Request_Handler();
        $context = $request_handler->get_context($action, $params->context);

        $ack_response = array(
            "context" => $context,
            "message" => $message
        );
        $ondc_logger->log(wp_json_encode($ack_response, JSON_UNESCAPED_SLASHES));
        return $ack_response;
    }

    public function subscribe($type){
        $ondc_subscribe = new ONDC_Subscribe();
        $response = $ondc_subscribe->subscribe($type);
        echo wp_json_encode($response);
    }

    public function on_subscribe(){
        $ondc_subscribe = new ONDC_Subscribe();
        $response = $ondc_subscribe->on_subscribe();
        return $response;
    }

    public function lookup(){
        $ondc_subscribe = new ONDC_Subscribe();
        $response = $ondc_subscribe->lookup();
        return $response;
    }

    public function ondc_site_verification( $wp ) {
        if ( $wp->request == 'ondc-site-verification.html' ) {
            header( 'Content-Type: text/html' );
            echo file_get_contents( ONDC_SELLER_APP_PLUGIN_DIR . '/ondc-site-verification.html' );
            exit;
        }
    }
}