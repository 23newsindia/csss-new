<?php
/**
 * Handles admin settings operations
 */
class MACP_Admin_Settings {
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
        'macp_varnish_port' => 80
    ];

    public function __construct() {
        add_action('wp_ajax_macp_toggle_setting', [$this, 'ajax_toggle_setting']);
        add_action('wp_ajax_macp_save_textarea', [$this, 'ajax_save_textarea']);
        add_action('wp_ajax_macp_clear_cache', [$this, 'ajax_clear_cache']);
    }

    public function get_all_settings() {
        $settings = [];
        foreach ($this->default_settings as $key => $default) {
            $clean_key = str_replace('macp_', '', $key);
            $settings[$clean_key] = (bool)get_option($key, $default);
        }

        // Add Varnish servers array
        $settings['varnish_servers'] = get_option('macp_varnish_servers', ['127.0.0.1']);
        $settings['varnish_port'] = get_option('macp_varnish_port', 6081);

        return $settings;
    }

    public function ajax_toggle_setting() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $option = sanitize_key($_POST['option']);
        $value = (int)$_POST['value'];

        if ($this->update_setting($option, $value)) {
            do_action('macp_settings_updated', $option, $value);
            wp_send_json_success(['message' => 'Setting updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update setting']);
        }
    }

    public function ajax_save_textarea() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $option = sanitize_key($_POST['option']);
        $value = sanitize_textarea_field($_POST['value']);
        $values = array_filter(array_map('trim', explode("\n", $value)));

        switch ($option) {
            case 'macp_varnish_servers':
                update_option($option, $values);
                break;
            case 'macp_excluded_scripts':
            case 'macp_deferred_scripts':
                update_option($option, $values);
                break;
            case 'macp_css_safelist':
                MACP_CSS_Config::save_safelist($values);
                break;
            case 'macp_css_excluded_patterns':
                MACP_CSS_Config::save_excluded_patterns($values);
                break;
        }

        do_action('macp_settings_updated', $option, $values);
        wp_send_json_success(['message' => 'Settings saved']);
    }

    public function ajax_clear_cache() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        do_action('macp_clear_cache');
        
        // Clear Varnish cache if enabled
        if (get_option('macp_enable_varnish', 0)) {
            do_action('macp_clear_all_cache');
        }
        
        wp_send_json_success(['message' => 'Cache cleared successfully']);
    }

    private function update_setting($option, $value) {
        if (!array_key_exists($option, $this->default_settings)) {
            return false;
        }

        $old_value = get_option($option);
        $result = update_option($option, $value);

        if ($result && $old_value !== $value) {
            do_action("update_option_{$option}", $value, $old_value);
            do_action('macp_settings_updated', $option, $value);
        }

        return $result;
    }
}