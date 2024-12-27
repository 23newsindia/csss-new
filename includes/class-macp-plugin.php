<?php
class MACP_Plugin {
    private static $instance = null;
    private $redis;
    private $html_cache;
    private $admin;
    private $js_optimizer;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init() {
        register_activation_hook(MACP_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MACP_PLUGIN_FILE, [$this, 'deactivate']);

        $this->redis = new MACP_Redis();
        $this->html_cache = new MACP_HTML_Cache();
        $this->js_optimizer = new MACP_JS_Optimizer();
        $this->admin = new MACP_Admin($this->redis);

        $this->init_hooks();
    }

    private function init_hooks() {
        // Initialize caching based on settings
        add_action('template_redirect', [$this, 'initialize_caching'], 0);
        
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
    }

    public function deactivate() {
        $this->html_cache->clear_cache();
    }
}