<?php
/**
 * Handles AJAX requests for the admin interface
 */
class MACP_Ajax_Handler {
    private $settings_manager;
    private $critical_css;
    private $filesystem;

    public function __construct(MACP_Settings_Manager $settings_manager, $filesystem) {
        $this->settings_manager = $settings_manager;
        $this->filesystem = $filesystem;
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_ajax_macp_generate_mobile_cpcss', [$this, 'generate_mobile_cpcss']);
        add_action('wp_ajax_macp_toggle_mobile_cpcss', [$this, 'toggle_mobile_cpcss']);
    }

    public function generate_mobile_cpcss() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $processor = new MACP_Critical_CSS_Processor($this->filesystem);
        $generation = new MACP_Critical_CSS_Generation($processor);
        $this->critical_css = new MACP_Critical_CSS($generation, $this->settings_manager, $this->filesystem);

        $this->critical_css->process_handler('mobile');
        wp_send_json_success(['message' => 'Mobile Critical CSS generation started']);
    }

    public function toggle_mobile_cpcss() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $value = (int)$_POST['value'];
        
        if ($this->settings_manager->update_setting('async_css_mobile', $value)) {
            if ($value === 1) {
                $processor = new MACP_Critical_CSS_Processor($this->filesystem);
                $generation = new MACP_Critical_CSS_Generation($processor);
                $this->critical_css = new MACP_Critical_CSS($generation, $this->settings_manager, $this->filesystem);
                $this->critical_css->process_handler('mobile');
            }
            wp_send_json_success(['message' => 'Setting updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update setting']);
        }
    }
}