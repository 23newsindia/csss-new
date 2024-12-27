<?php
/**
 * Handles admin settings operations
 */
class MACP_Admin_Settings {
    private $settings_manager;
    private $filesystem;

    public function __construct() {
        $this->settings_manager = new MACP_Settings_Manager();
        $this->filesystem = new MACP_Filesystem();
        add_action('wp_ajax_macp_toggle_setting', [$this, 'ajax_toggle_setting']);
        add_action('wp_ajax_macp_save_textarea', [$this, 'ajax_save_textarea']);
        add_action('wp_ajax_macp_clear_cache', [$this, 'ajax_clear_cache']);
    }

    public function get_all_settings() {
        return $this->settings_manager->get_all_settings();
    }

    public function ajax_toggle_setting() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $option = sanitize_key($_POST['option']);
        $value = (int)$_POST['value'];

        if ($this->settings_manager->update_setting($option, $value)) {
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

        switch ($option) {
            case 'macp_defer_excluded_scripts':
            case 'macp_delay_excluded_scripts':
                $type = strpos($option, 'defer') !== false ? 'defer' : 'delay';
                MACP_Script_Exclusions::save_exclusions($value, $type);
                break;
            case 'macp_varnish_servers':
                $servers = array_filter(array_map('trim', explode("\n", $value)));
                update_option($option, $servers);
                break;
            case 'macp_css_safelist':
                MACP_CSS_Config::save_safelist(array_filter(array_map('trim', explode("\n", $value))));
                break;
            case 'macp_css_excluded_patterns':
                MACP_CSS_Config::save_excluded_patterns(array_filter(array_map('trim', explode("\n", $value))));
                break;
        }

        do_action('macp_settings_updated', $option, $value);
        wp_send_json_success(['message' => 'Settings saved']);
    }

    public function ajax_clear_cache() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        do_action('macp_clear_cache');
        
        if (get_option('macp_enable_varnish', 0)) {
            do_action('macp_clear_all_cache');
        }
        
        wp_send_json_success(['message' => 'Cache cleared successfully']);
    }

    public function init_ajax_handlers() {
        add_action('wp_ajax_macp_generate_mobile_cpcss', [$this, 'ajax_generate_mobile_cpcss']);
        add_action('wp_ajax_macp_toggle_mobile_cpcss', [$this, 'ajax_toggle_mobile_cpcss']);
    }

    public function ajax_generate_mobile_cpcss() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $critical_css = new MACP_Critical_CSS(
            new MACP_Critical_CSS_Generation(new MACP_Critical_CSS_Processor($this->filesystem)),
            $this->settings_manager,
            $this->filesystem
        );

        $critical_css->process_handler('mobile');

        wp_send_json_success(['message' => 'Mobile Critical CSS generation started']);
    }

    public function ajax_toggle_mobile_cpcss() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $value = (int)$_POST['value'];
        
        if ($this->settings_manager->update_setting('async_css_mobile', $value)) {
            if ($value === 1) {
                $critical_css = new MACP_Critical_CSS(
                    new MACP_Critical_CSS_Generation(new MACP_Critical_CSS_Processor($this->filesystem)),
                    $this->settings_manager,
                    $this->filesystem
                );
                $critical_css->process_handler('mobile');
            }
            wp_send_json_success(['message' => 'Setting updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update setting']);
        }
    }
}