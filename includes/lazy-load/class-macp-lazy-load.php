<?php
class MACP_Lazy_Load {
    private $settings_manager;
    private $content_processor;

    public function __construct() {
        $this->settings_manager = new MACP_Settings_Manager();
        $this->content_processor = new MACP_Lazy_Load_Processor();
        
        // Initialize hooks if lazy load is enabled
        add_action('init', [$this, 'init_hooks']);
    }

    public function init_hooks() {
        if (!$this->is_lazy_load_enabled()) {
            return;
        }

        // Remove WordPress default lazy loading
        add_filter('wp_lazy_loading_enabled', '__return_false');
        
        // Add our lazy loading to various content types
        add_filter('the_content', [$this->content_processor, 'process_content'], 99);
        add_filter('post_thumbnail_html', [$this->content_processor, 'process_content'], 99);
        add_filter('get_avatar', [$this->content_processor, 'process_content'], 99);
        add_filter('widget_text', [$this->content_processor, 'process_content'], 99);
        
        // Add filter for dynamic content
        add_filter('render_block', [$this->content_processor, 'process_content'], 99);
        
        // Handle attachment images
        add_filter('wp_get_attachment_image_attributes', [$this, 'modify_attachment_image_attributes'], 99, 2);
        
        // Enqueue required scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function modify_attachment_image_attributes($attributes, $attachment) {
        if (!$this->is_lazy_load_enabled()) {
            return $attributes;
        }

        // Skip if already processed
        if (isset($attributes['data-src'])) {
            return $attributes;
        }

        if (isset($attributes['src'])) {
            $attributes['data-src'] = $attributes['src'];
            $attributes['src'] = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E";
        }
        
        if (isset($attributes['srcset'])) {
            $attributes['data-srcset'] = $attributes['srcset'];
            unset($attributes['srcset']);
        }

        // Add lazy loading class
        $attributes['class'] = isset($attributes['class']) 
            ? $attributes['class'] . ' macp-lazy'
            : 'macp-lazy';

        return $attributes;
    }

    public function enqueue_scripts() {
        if (!$this->is_lazy_load_enabled()) {
            return;
        }

        // Register and enqueue main stylesheet first
        wp_register_style(
            'macp-styles',
            false,
            [],
            '1.0.0'
        );
        wp_enqueue_style('macp-styles');

        // Enqueue Vanilla LazyLoad from plugin directory
        wp_enqueue_script(
            'vanilla-lazyload',
            plugins_url('/assets/js/vanilla-lazyload.min.js', dirname(dirname(__FILE__))),
            [],
            '17.8.3',
            true
        );

        // Enqueue custom lazy load handler
        wp_enqueue_script(
            'macp-lazy-load',
            plugins_url('/assets/js/lazy-load.js', dirname(dirname(__FILE__))),
            ['vanilla-lazyload'],
            '1.0.0',
            true
        );

        // Add inline CSS
        wp_add_inline_style('macp-styles', "
            .macp-lazy {
                opacity: 0;
                transition: opacity 0.3s ease-in;
            }
            .macp-lazy.macp-lazy-loaded {
                opacity: 1;
            }
            picture .macp-lazy {
                width: 100%;
                height: auto;
            }
        ");

        // Debug output
        error_log('MACP: Scripts enqueued - Lazy Load enabled: ' . $this->is_lazy_load_enabled());
    }

    private function is_lazy_load_enabled() {
        $enabled = get_option('macp_enable_lazy_load', 1);
        return !empty($enabled); // Convert any non-empty value to boolean
    }
}