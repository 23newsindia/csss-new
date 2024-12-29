<?php
class MACP_Lazy_Load {
    private $content_processor;
    private $script_loader;

    public function __construct() {
        $this->content_processor = new MACP_Lazy_Load_Processor();
        $this->script_loader = new MACP_Script_Loader();
        
        if ($this->is_lazy_load_enabled()) {
            $this->init_hooks();
        }
    }

    private function init_hooks() {
        // Remove WordPress default lazy loading
        add_filter('wp_lazy_loading_enabled', '__return_false');
        
        // Add our lazy loading to various content types
        add_filter('the_content', [$this->content_processor, 'process_content'], 99);
        add_filter('post_thumbnail_html', [$this->content_processor, 'process_content'], 99);
        add_filter('get_avatar', [$this->content_processor, 'process_content'], 99);
        add_filter('widget_text', [$this->content_processor, 'process_content'], 99);
        add_filter('render_block', [$this->content_processor, 'process_content'], 99);
        
        // Handle attachment images
        add_filter('wp_get_attachment_image_attributes', [$this, 'modify_attachment_image_attributes'], 99, 2);
    }

    public function modify_attachment_image_attributes($attributes, $attachment) {
        if (!isset($attributes['src'])) {
            return $attributes;
        }

        // Skip if already processed
        if (isset($attributes['data-src'])) {
            return $attributes;
        }

        $attributes['data-src'] = $attributes['src'];
        $attributes['src'] = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E";
        
        if (isset($attributes['srcset'])) {
            $attributes['data-srcset'] = $attributes['srcset'];
            unset($attributes['srcset']);
        }

        $attributes['class'] = isset($attributes['class']) 
            ? $attributes['class'] . ' macp-lazy'
            : 'macp-lazy';

        return $attributes;
    }

    private function is_lazy_load_enabled() {
        return get_option('macp_enable_lazy_load', 1);
    }
}