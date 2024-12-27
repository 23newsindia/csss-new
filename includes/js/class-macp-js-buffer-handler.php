<?php
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-attributes.php';

class MACP_JS_Buffer_Handler {
    private $excluded_scripts = [];
    private static $instance = null;

    public function __construct($excluded_scripts = []) {
        $this->excluded_scripts = $excluded_scripts;
        self::$instance = $this;
    }

    public static function get_instance() {
        return self::$instance;
    }

    public function start_buffering() {
        ob_start([$this, 'process_buffer']);
    }

    public function end_buffering() {
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    public function process_buffer($html) {
        if (!get_option('macp_enable_js_delay', 0) && !get_option('macp_enable_js_defer', 0)) {
            return $html;
        }

        // Process all script tags, including those in head
        return preg_replace_callback(
            '/<script\b[^>]*>.*?<\/script>/is',
            function($matches) {
                return MACP_Script_Processor::process_tag($matches[0], $this->excluded_scripts);
            },
            $html
        );
    }
}