<?php
/**
 * Handles all plugin settings management
 */
class MACP_Settings_Manager {
    private $default_settings = [
        'macp_enable_redis' => 1,
        'macp_enable_html_cache' => 1,
        'macp_enable_gzip' => 1,
        'macp_minify_html' => 0,
        'macp_minify_css' => 0,
        'macp_minify_js' => 0,
        'macp_remove_unused_css' => 0,
        'macp_process_external_css' => 0,
        'macp_enable_js_defer' => 0,
        'macp_enable_js_delay' => 0,
        'macp_enable_varnish' => 0,
        'macp_varnish_port' => 80,
        'async_css_mobile' => 0
    ];

    /**
     * Get a single setting value
     */
    public function get_setting($key, $default = null) {
        if (!isset($this->default_settings['macp_' . $key]) && !isset($this->default_settings[$key])) {
            return $default;
        }

        $option_key = isset($this->default_settings['macp_' . $key]) ? 'macp_' . $key : $key;
        return get_option($option_key, $this->default_settings[$option_key]);
    }

    /**
     * Get all plugin settings
     */
    public function get_all_settings() {
        $settings = [];
        foreach ($this->default_settings as $key => $default) {
            $clean_key = str_replace('macp_', '', $key);
            $settings[$clean_key] = (bool)get_option($key, $default);
        }

        // Add Varnish settings
        $settings['varnish_servers'] = get_option('macp_varnish_servers', ['127.0.0.1']);
        $settings['varnish_port'] = get_option('macp_varnish_port', 6081);

        return $settings;
    }

    /**
     * Update a single setting
     */
    public function update_setting($key, $value) {
        $option_key = isset($this->default_settings['macp_' . $key]) ? 'macp_' . $key : $key;
        return update_option($option_key, $value);
    }

    /**
     * Get default settings
     */
    public function get_default_settings() {
        return $this->default_settings;
    }
}