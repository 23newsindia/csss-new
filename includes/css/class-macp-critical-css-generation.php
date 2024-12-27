<?php
/**
 * Handles the background process for critical CSS generation
 */
class MACP_Critical_CSS_Generation extends WP_Background_Process {
    protected $action = 'critical_css_generation';
    protected $processor;

    public function __construct(MACP_Critical_CSS_Processor $processor) {
        parent::__construct();
        $this->processor = $processor;
    }

    protected function task($item) {
        if (!is_array($item)) {
            return false;
        }

        $transient = get_transient('macp_critical_css_generation_running');
        $mobile = isset($item['mobile']) ? $item['mobile'] : 0;

        $generation_params = [
            'is_mobile' => $mobile,
            'item_type' => $item['type']
        ];

        $generated = $this->processor->generate($item['url'], $item['path'], $generation_params);

        if (is_wp_error($generated)) {
            $this->update_running_transient($transient, $item['path'], $mobile, $generated->get_error_message(), false);
            return false;
        }

        if (isset($generated['code']) && 'generation_pending' === $generated['code']) {
            $pending = get_transient('macp_cpcss_generation_pending');
            
            if (false === $pending) {
                $pending = [];
            }

            $pending[$item['path']] = $item;
            set_transient('macp_cpcss_generation_pending', $pending, HOUR_IN_SECONDS);
            return false;
        }

        $this->update_running_transient(
            $transient, 
            $item['path'], 
            $mobile, 
            $generated['message'], 
            ('generation_successful' === $generated['code'])
        );

        return false;
    }

    protected function complete() {
        parent::complete();
        
        $transient = get_transient('macp_critical_css_generation_running');
        set_transient('macp_critical_css_generation_complete', $transient, HOUR_IN_SECONDS);
        delete_transient('macp_critical_css_generation_running');
        
        do_action('macp_critical_css_generation_complete');
    }

    private function update_running_transient($transient, $path, $is_mobile, $message, $success) {
        if (!is_array($transient)) {
            $transient = [
                'total' => 0,
                'items' => []
            ];
        }

        if (!isset($transient['items'][$path])) {
            $transient['items'][$path] = [
                'status' => []
            ];
        }

        $type = $is_mobile ? 'mobile' : 'nonmobile';
        $transient['items'][$path]['status'][$type] = [
            'success' => $success,
            'message' => $message
        ];

        set_transient('macp_critical_css_generation_running', $transient, HOUR_IN_SECONDS);
    }
}