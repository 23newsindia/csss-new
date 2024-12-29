<?php
/**
 * Handles display of cache metrics in admin
 */
class MACP_Metrics_Display {
    private $calculator;

    public function __construct(MACP_Metrics_Calculator $calculator) {
        $this->calculator = $calculator;
        add_action('admin_menu', [$this, 'add_metrics_page']);
    }

    public function add_metrics_page() {
        add_submenu_page(
            'macp-settings',
            'Cache Metrics',
            'Cache Metrics',
            'manage_options',
            'macp-metrics',
            [$this, 'render_metrics_page']
        );
    }

    public function render_metrics_page() {
        $metrics = $this->calculator->get_all_metrics();
        include MACP_PLUGIN_DIR . 'templates/metrics-page.php';
    }
}