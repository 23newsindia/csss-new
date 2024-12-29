<?php
class MACP_Lazy_Load {
    private $settings_manager;
    private $content_processor;

    public function __construct() {
        $this->settings_manager = new MACP_Settings_Manager();
        $this->content_processor = new MACP_Lazy_Load_Processor();
        
        if ($this->is_lazy_load_enabled()) {
            $this->init_hooks();
        }
    }

    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this->content_processor, 'process_content'], 99);
        add_filter('post_thumbnail_html', [$this->content_processor, 'process_content'], 99);
        add_filter('widget_text', [$this->content_processor, 'process_content'], 99);
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'vanilla-lazyload',
            plugins_url('assets/js/vanilla-lazyload.min.js', MACP_PLUGIN_FILE),
            [],
            '17.8.3',
            true
        );

        wp_enqueue_script(
            'macp-lazy-load',
            plugins_url('assets/js/lazy-load.js', MACP_PLUGIN_FILE),
            ['vanilla-lazyload'],
            '1.0.0',
            true
        );
    }

    private function is_lazy_load_enabled() {
        return get_option('macp_enable_lazy_load', 1);
    }
}