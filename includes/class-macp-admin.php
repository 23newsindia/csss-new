<?php
/**
 * Main admin class for handling WordPress admin functionality
 */
class MACP_Admin {
    private $redis;
    private $settings_handler;
    private $assets_handler;
    private $settings_manager;

    public function __construct($redis) {
        $this->redis = $redis;
        $this->settings_manager = new MACP_Settings_Manager();
        $this->settings_handler = new MACP_Admin_Settings();
        $this->assets_handler = new MACP_Admin_Assets();

        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this->assets_handler, 'enqueue_admin_assets']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Cache Settings',
            'Cache Settings',
            'manage_options',
            'macp-settings',
            [$this, 'render_settings_page'],
            'dashicons-performance',
            100
        );

        add_submenu_page(
            'macp-settings',
            'Debug Information',
            'Debug Info',
            'manage_options',
            'macp-debug',
            [$this, 'render_debug_page']
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        $settings = $this->settings_handler->get_all_settings();
        $settings_manager = $this->settings_manager; // Make settings manager available to templates
        
        include MACP_PLUGIN_DIR . 'templates/admin-page.php';
        include MACP_PLUGIN_DIR . 'templates/css-exclusions.php';
        include MACP_PLUGIN_DIR . 'templates/js-optimization.php';
    }

    public function render_debug_page() {
        if (!current_user_can('manage_options')) return;
        
        require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug-utility.php';
        $status = MACP_Debug_Utility::check_plugin_status();
        
        include MACP_PLUGIN_DIR . 'templates/debug-page.php';
    }
}