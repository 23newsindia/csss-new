<?php
/*
Plugin Name: My Advanced Cache Plugin
Description: Integrates Redis for object caching and static HTML caching with WP Rocket-like interface
Version: 1.3
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('MACP_PLUGIN_FILE', __FILE__);
define('MACP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load Composer autoloader
if (file_exists(MACP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once MACP_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    error_log('Autoload file missing. MACP Plugin may not function properly.');
}

// Load utility classes first
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-filesystem.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-url-helper.php';

// Load core functionality classes
require_once MACP_PLUGIN_DIR . 'includes/class-macp-redis.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-cache-helper.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-html-cache.php';

// Load CSS related classes
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-config.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-extractor.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-optimizer.php';

// Load minification classes
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-html-minifier.php';
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-minify-css.php';
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-minify-js.php';
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-minify-html.php';

// Load JavaScript optimization classes
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-attributes.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-rules.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-handler.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-exclusions.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-optimizer.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-buffer-handler.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-loader.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-tag-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-defer-handler.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-delay-handler.php';

// Load admin classes
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-settings-manager.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-admin-settings.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-admin-assets.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-varnish-settings.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-admin.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-admin-bar.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug-utility.php';

// Initialize the plugin
function MACP() {
    static $instance = null;
    if (null === $instance) {
        $instance = MACP_Plugin::get_instance();
    }
    return $instance;
}

// Start the plugin
add_action('plugins_loaded', 'MACP');