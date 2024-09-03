<?php
/**
 * ONDC subscribe class
 */
namespace app\ondcSellerApp\protocolLayer;

use app\ondcSellerApp as ondcSellerApp;
class ONDC_Subscribe{

    private $subscriber_body = [
        "context" => ["operation" => ["ops_no" => 2]],
        "message" => [
            "request_id" => "",
            "timestamp" => "",
            "entity" => [
                "gst" => [
                    "legal_entity_name" => "",
                    "business_address" => "",
                    "city_code" => ["std:080"],
                    "gst_no" => "...",
                ],
                "pan" => [
                    "name_as_per_pan" => "...",
                    "pan_no" => "...",
                    "date_of_incorporation" => "...",
                ],
                "name_of_authorised_signatory" => "...",
                "email_id" => "email@domain.in",
                "mobile_no" => "",
                "country" => "IND",
                "subscriber_id" => "",
                "unique_key_id" => "",
                "callback_url" => "/wp-json/ondc/v1",
                "key_pair" => [
                    "signing_public_key" => "",
                    "encryption_public_key" => "",
                    "valid_from" => "2023-12-12T18:27:54.101Z",
                    "valid_until" => "2030-07-08T06:27:54.101Z",
                ],
            ],
            "network_participant" => [
                // [
                //     "subscriber_url" => "/",
                //     "domain" => "ONDC:RET10",
                //     "type" => "sellerApp",
                //     "msn" => false,
                //     "city_code" => [],
                // ],
            ],
        ],
    ];
    /**
     * Subscribe to ONDC
     */
    public function subscribe($type){
        // load env variables from signing-verification/.env
        $dotenv = \Dotenv\Dotenv::createImmutable(ONDC_SELLER_APP_PLUGIN_DIR . '/signing-verification');
        $dotenv->safeload();
        // get ondc data
        $ondc_data = get_option('ondc_seller_app');

        if(!isset($ondc_data['request_id'])){
            $request_id = md5(uniqid(rand(), true));
            $ondc_data['request_id'] = $request_id;
        }

        if(!isset($ondc_data['unique_key_id'])){
            $unique_key_id = md5(uniqid(rand(), true));
            $ondc_data['unique_key_id'] = $unique_key_id;
        }


        $store_address = $ondc_data['delivery_data']['store_locality'] . ', ' . $ondc_data['delivery_data']['store_street'] . ', ' . $ondc_data['delivery_data']['store_city'] . ', ' . $ondc_data['delivery_data']['store_state'] . ', ' . $ondc_data['delivery_data']['store_pincode'];
        // valid from date in Timestamp in RFC3339 format from which token is valid
        // $valid_to = $current_datetime->add(new \DateInterval('P4Y'))->format("Y-m-d\TH:i:s.u\Z");
        $this->subscriber_body['message']['request_id'] = $ondc_data['request_id'];
        $this->subscriber_body['message']['timestamp'] = ondcSellerApp\wdm_ondc_get_timetamp();
        $this->subscriber_body['message']['entity']['subscriber_id'] = isset( $ondc_data['user_data']['subscriber_id'] ) ? $ondc_data['user_data']['subscriber_id'] : '';
        $this->subscriber_body['message']['entity']['unique_key_id'] = $unique_key_id;
        $this->subscriber_body['message']['entity']['key_pair']['signing_public_key'] = $_ENV['SIGNING_PUB_KEY'];
        $this->subscriber_body['message']['entity']['key_pair']['encryption_public_key'] = $_ENV['ENC_PUB_KEY'];
        $this->subscriber_body['message']['entity']['key_pair']['valid_from'] = ondcSellerApp\wdm_ondc_get_timetamp();
        // $this->subscriber_body['message']['entity']['key_pair']['valid_until'] = $valid_to;
        $this->subscriber_body['message']['entity']['gst']['legal_entity_name'] = $ondc_data['store_data']['store_name'];
        $this->subscriber_body['message']['entity']['gst']['business_address'] = $store_address;
        $this->subscriber_body['message']['entity']['gst']['gst_no'] = $ondc_data['store_data']['store_gst'];
        $this->subscriber_body['message']['entity']['pan']['name_as_per_pan'] = $ondc_data['store_data']['store_name'];
        $this->subscriber_body['message']['entity']['pan']['pan_no'] = $ondc_data['store_data']['store_pan'];
        $this->subscriber_body['message']['entity']['pan']['date_of_incorporation'] = '01/01/2021';
        $this->subscriber_body['message']['entity']['name_of_authorised_signatory'] = $ondc_data['user_data']['name'];
        $this->subscriber_body['message']['entity']['email_id'] = $ondc_data['user_data']['email'];
        $this->subscriber_body['message']['entity']['mobile_no'] = $ondc_data['user_data']['phone'];
        $this->subscriber_body['message']['entity']['country'] = 'IND';

        $domains = array(
            'nic2004:52110',
            'ONDC:RET10',
            'ONDC:RET11',
            'ONDC:RET12',
            'ONDC:RET14',
            'ONDC:RET15',
            'ONDC:RET16',
            'ONDC:RET17',
            'ONDC:RET18',
            'ONDC:RET19',
            'ONDC:RET1A',
            'ONDC:RET1B',
            'ONDC:RET1C',
            'ONDC:RET1D',
        );

        $city_codes = array(
            'std:022',
        );

        $city = apply_filters('ondc_city_codes', $city_codes);

        foreach($domains as $domain){
            $this->subscriber_body['message']['network_participant'][] = array(
                "subscriber_url" => "/wp-json/ondc/v1/",
                "domain" => "$domain",
                "type" => "sellerApp",
                "msn" => false,
                "city_code" => $city_codes,
            );
        }

        if ( 'stagging' == $type ) {
            $ondc_url = ONDC_STAGGING_URL;
        } elseif ( 'pre-production' == $type ) {
            $ondc_url = ONDC_PRE_PRODUCTION_URL;
        } elseif ( 'beta-production' == $type ) {
            $ondc_url = ONDC_BETA_PRODUCTION_URL;
        } elseif ( 'production' == $type ) {
            $ondc_url = PRODUCTION_URL;
        } else {
            $ondc_url = ONDC_STAGGING_URL;
        }

        // crerate verification file 
        $signature = sign_response($ondc_data['request_id'], $_ENV['SIGNING_PRIV_KEY']);
        $verification_file = fopen(ONDC_SELLER_APP_PLUGIN_DIR . '/ondc-site-verification.html', 'w');
        $html = '<html>
                    <head>
                        <meta name= "ondc-site-verification" content="' . $signature . '">
                    </head>
                    <body>
                        ONDC Site Verification Page
                    </body>
                </html>';
        fwrite($verification_file, $html);
        fclose($verification_file);

        $request_data = array(
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($this->subscriber_body),
        );

        update_option('ondc_seller_app', $ondc_data);

        // set ondc subscription request type for future use
        update_option('ondc_subscription_request_type', $type);
        
        $response = wp_remote_post($ondc_url . '/subscribe', $request_data);

        if(is_wp_error($response)){
            error_log($response->get_error_message());
        } else {
            error_log(print_r(wp_remote_retrieve_body($response), true));
            print_r(wp_remote_retrieve_body($response));
        }

        return array(
            'success' => 'ACK',
        );
    }

    /**
     * ONDC on_subscribe handler
     */
    public function on_subscribe(){
        // load env variables from signing-verification/.env
        $dotenv = \Dotenv\Dotenv::createImmutable(ONDC_SELLER_APP_PLUGIN_DIR . '/signing-verification');
        $dotenv->safeload();
        
        $data = json_decode( file_get_contents("php://input") , true);
        $subscriber_id = $data['subscriber_id'];
        // Python utility code to decrypt challenge
        // Not in use right now
        // $command = "python3 /var/www/ondc/public_html/decrypt.py " . $data['challenge'];

        // if(function_exists('shell_exec')) {
        //     $output = \shell_exec($command);
        //     error_log("output: " . trim($output));
        // } else {
        //     error_log("shell_exec is disabled");
        // }

        // API for decrypting challenge
        $wdm_url = 'https://ondcapi.wisdmlabs.net/decrypt';

        $sub_type = get_option('ondc_subscription_request_type');

        if ( 'stagging' == $type ) {
            $public_key = "MCowBQYDK2VuAyEAduMuZgmtpjdCuxv+Nc49K0cB6tL/Dj3HZetvVN7ZekM=";
        } elseif ( 'pre-production' == $type ) {
            $public_key = "MCowBQYDK2VuAyEAa9Wbpvd9SsrpOZFcynyt/TO3x0Yrqyys4NUGIvyxX2Q=";
        } elseif ( 'beta-production' == $type ) {
            $public_key = "MCowBQYDK2VuAyEAa9Wbpvd9SsrpOZFcynyt/TO3x0Yrqyys4NUGIvyxX2Q=";
        } elseif ( 'production' == $type ) {
            $public_key = "MCowBQYDK2VuAyEAvVEyZY91O2yV8w8/CAwVDAnqIZDJJUPdLUUKwLo3K0M=";
        } else {
            $public_key = "MCowBQYDK2VuAyEAduMuZgmtpjdCuxv+Nc49K0cB6tL/Dj3HZetvVN7ZekM=";
        }

        $args = array(
            'ENCRYPTION_PRIVATE_KEY' => $_ENV['ENC_PRIV_KEY'],
            'ONDC_PUBLIC_KEY' => $public_key,
            'encryptedText' => $data['challenge']
        );

        $request_data = array(
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($args),
        );

        $response = wp_remote_post($wdm_url, $request_data);

        if(is_wp_error($response)){
            $output = $response->get_error_message();
        } else {
            $body = json_decode(wp_remote_retrieve_body($response));
            $output = $body->decryptedText;
        }

        // PHP code utility to decrypt challenge
        // Code not working right now
        // $output = decrypt($_ENV['ENC_PRIV_KEY'], $_ENV['COUNTERPARTY_PUB_KEY'], $data['challenge']);
        return array(
            'answer' => trim($output)
        );
    }

    public function lookup($type, $sub_id, $domain='ONDC:RET10'){

        $dotenv = \Dotenv\Dotenv::createImmutable(ONDC_SELLER_APP_PLUGIN_DIR . '/signing-verification');
        $dotenv->safeload();
        // valid from date in Timestamp in RFC3339 format from which token is valid
        $search_params = array(
                "country" => "IND",
                "domain" => $domain,
                "type" => "sellerApp",
                "city" =>"std:022",
                "subscriber_id" => $sub_id,
        );

        $signature = create_signature(wp_json_encode($search_params));

        $ondc_seller_app = get_option( 'ondc_seller_app' );

        $args = array(
            "sender_subscriber_id" => isset( $ondc_seller_app['user_data']['subscriber_id'] ) ? $ondc_seller_app['user_data']['subscriber_id'] : '',
		    "request_id" => md5(uniqid(rand(), true)),
		    "timestamp" => ondcSellerApp\wdm_ondc_get_timetamp(),
		    "signature" => $signature,
		    "search_parameters" => $search_params
        );

        $request_data = array(
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($args),
        );

        $response = wp_remote_post(ONDC_STAGGING_URL . '/vlookup', $request_data);

        if(is_wp_error($response)){
            error_log($response->get_error_message());
        } else {
            error_log(print_r(wp_remote_retrieve_body($response), true));
            print_r(wp_remote_retrieve_body($response));
        }

    }
}