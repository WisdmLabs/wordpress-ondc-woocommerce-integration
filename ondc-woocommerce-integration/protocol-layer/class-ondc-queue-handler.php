<?php
/**
 * ONDC request handler class to add messages queues
 */
namespace app\ondcSellerApp\protocolLayer;

use app\ondcSellerApp as ondcSellerApp;

class ONDC_Queue_Handler
{
    private $queue = [];

    private $queue_enabled = 'no';

    public function __construct()
    {
        // load env variables from signing-verification/.env
        $dotenv = \Dotenv\Dotenv::createImmutable(ONDC_SELLER_APP_PLUGIN_DIR . '/signing-verification');
        $dotenv->safeload();

        $this->queue_enabled = get_option('ondc_seller_app_enable_message_queue', 'no');
        $this->queue_enabled = 'yes';

        add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
        
        if ( 'yes' === $this->queue_enabled ) {
            add_action( 'ondc_message_schedule_cron', array( $this, 'ondc_message_consumer' ) );

            if ( ! wp_next_scheduled( 'ondc_message_schedule_cron' ) ) {
                wp_schedule_event( time(), 'five_seconds', 'ondc_message_schedule_cron' );
            }
        }
    }

    /**
     * Add message to queue
     */
    public function add_message($action, $payload, $priority)
    {
        global $wpdb;
        if( 'no' === $this->queue_enabled || 'search' === $action ) {
            $this->handle_request($action, $payload);
        } else {
            $table = $wpdb->prefix . 'ondc_message_queue';
            $wpdb->insert(
                $table,
                array(
                    'action' => $action,
                    'payload' => json_encode($payload),
                    'priority' => $priority,
                    'timestamp' => ondcSellerApp\wdm_ondc_get_timetamp(),
                ),
                array(
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                )
            );
        }

        // add log
        $table = $wpdb->prefix . 'ondc_message_queue_log';
        $wpdb->insert(
            $table,
            array(
                'action' => $action,
                'payload' => json_encode($payload),
                'timestamp' => ondcSellerApp\wdm_ondc_get_timetamp(),
            ),
            array(
                '%s',
                '%s',
                '%s',
            )
        );

    }

    /**
     * Add cron time interval for 5 sec
     */
    public function add_cron_interval( $schedules ) { 
        $schedules['five_seconds'] = array(
            'interval' => 5,
            'display'  => esc_html__( 'Every Five Seconds' ), );
        return $schedules;
    }

    /**
     * Ondc scheduled messsage consumer
     */
    public function ondc_message_consumer() {
        global $wpdb;
        error_log("running cron");
        $table = $wpdb->prefix . 'ondc_message_queue';
        $messages = $wpdb->get_results("SELECT * FROM $table ORDER BY priority DESC, timestamp ASC LIMIT 1");

        if ( ! empty($messages) ) {
            $action = $messages[0]->action;
            $payload = json_decode($messages[0]->payload);
            $this->handle_request($action, $payload);

            $wpdb->delete( $table, array( 'id' => $messages[0]->id ) );
        }
    }

    /**
     * Handle request
     */
    public function handle_request($action, $payload)
    {
        $request = new ONDC_Process_Request();
        $message = $request->process($action, $payload);

        $request_handler = new ONDC_Request_Handler();
        $response = $request_handler->send_request($action, $message, $payload->context);

        return $response;
    }
}