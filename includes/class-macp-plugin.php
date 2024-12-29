<?php
/**
 * Main plugin class that handles initialization and core functionality
 */
class MACP_Plugin {
    private static $instance = null;
    private $redis;
    private $html_cache;
    private $admin;
    private $js_optimizer;
    private $admin_bar;
    private $varnish;
    private $varnish_settings;
    private $script_handler;
    private $settings_manager;
    private $critical_css;
    private $metrics_collector;
    private $metrics_calculator;
    private $metrics_display;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // Register activation and deactivation hooks
        register_activation_hook(MACP_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MACP_PLUGIN_FILE, [$this, 'deactivate']);

        // Initialize settings manager first
        $this->settings_manager = new MACP_Settings_Manager();

        // Initialize Redis
        $this->redis = new MACP_Redis();

        // Initialize metrics components
        $this->metrics_collector = new MACP_Metrics_Collector($this->redis);
        $this->metrics_calculator = new MACP_Metrics_Calculator($this->redis);
        $this->metrics_display = new MACP_Metrics_Display($this->metrics_calculator);

        // Initialize other components
        $this->html_cache = new MACP_HTML_Cache($this->redis, $this->metrics_collector);
        $this->js_optimizer = new MACP_JS_Optimizer();
        $this->admin = new MACP_Admin($this->redis);
        $this->admin_bar = new MACP_Admin_Bar();
        $this->script_handler = new MACP_Script_Handler();
        
        // Initialize Critical CSS
        $this->critical_css = new MACP_Critical_CSS($this->settings_manager);
        
        // Initialize Varnish if enabled
        if (get_option('macp_enable_varnish', 0)) {
            $this->varnish = new MACP_Varnish();
        }
        $this->varnish_settings = new MACP_Varnish_Settings();

        $this->init_hooks();
        
        MACP_Debug::log('Plugin initialized');
    }

    private function init_hooks() {
        // Initialize caching based on settings
        add_action('init', [$this, 'initialize_caching'], 0);
        
        // Handle cache clearing
        add_action('save_post', [$this->html_cache, 'clear_cache']);
        add_action('comment_post', [$this->html_cache, 'clear_cache']);
        add_action('wp_trash_post', [$this->html_cache, 'clear_cache']);
        add_action('switch_theme', [$this->html_cache, 'clear_cache']);
        
        // Add hook for Redis cache priming
        if (get_option('macp_enable_redis', 1)) {
            add_action('init', [$this->redis, 'prime_cache']);
        }
    }

    public function initialize_caching() {
        if (get_option('macp_enable_html_cache', 1)) {
            $this->html_cache->start_buffer();
        }
    }

    public function activate() {
        // Create cache directory
        wp_mkdir_p(WP_CONTENT_DIR . '/cache/macp');
        
        // Set default options
        add_option('macp_enable_html_cache', 1);
        add_option('macp_enable_gzip', 1);
        add_option('macp_enable_redis', 1);
        add_option('macp_minify_html', 0);
        add_option('macp_enable_js_defer', 0);
        add_option('macp_enable_js_delay', 0);
        add_option('macp_enable_varnish', 0);
        add_option('macp_varnish_servers', ['127.0.0.1']);
        add_option('macp_varnish_port', 6081);
        add_option('macp_enable_critical_css', 0);
    }

    public function deactivate() {
        $this->html_cache->clear_cache();
    }
}