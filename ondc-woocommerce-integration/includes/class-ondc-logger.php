<?php
/**
 * ONDC Logger
 */
namespace app\ondcSellerApp\includes;

class ONDC_Logger
{
    private $log_file;
    private $log_dir;

    public function __construct()
    {   
        $upload_dir = wp_upload_dir();
        
        $this->log_dir = $upload_dir['basedir'] . '/ondc-logs/';
        $log_file_name = 'ondc-logs-' . date('Y-m-d') . '.log';
        $this->log_file = $this->log_dir . $log_file_name;
    }

    /**
     * Log message
     */
    public function log($message)
    {
        if (!file_exists($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }

        $log_message = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }
}