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
    if (!$this->is_lazy_load_enabled()) {
        return;
    }

    // Remove WordPress default lazy loading
    add_filter('wp_lazy_loading_enabled', '__return_false');
    
    // Add our lazy loading
    add_filter('the_content', [$this->content_processor, 'process_content'], 99);
    add_filter('post_thumbnail_html', [$this->content_processor, 'process_content'], 99);
    add_filter('get_avatar', [$this->content_processor, 'process_content'], 99);
    add_filter('widget_text', [$this->content_processor, 'process_content'], 99);
    add_filter('wp_get_attachment_image_attributes', [$this, 'modify_attachment_image_attributes'], 99);
    
    // Enqueue required scripts
    add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
}

public function modify_attachment_image_attributes($attributes) {
    if (!$this->is_lazy_load_enabled()) {
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

    if (isset($attributes['class'])) {
        $attributes['class'] .= ' macp-lazy';
    } else {
        $attributes['class'] = 'macp-lazy';
    }

    $attributes['loading'] = 'lazy';

    return $attributes;
}


public function enqueue_scripts() {
    if ( ! $this->is_lazy_load_enabled() ) {
        return;
    }

    // Enqueue Vanilla LazyLoad Script
    wp_enqueue_script(
        'vanilla-lazyload',
        plugins_url('assets/js/vanilla-lazyload.min.js', MACP_PLUGIN_FILE),
        [],
        '17.8.3',
        true
    );

    // Enqueue Custom Lazy Load Handler
    wp_enqueue_script(
        'macp-lazy-load',
        plugins_url('assets/js/lazy-load.js', MACP_PLUGIN_FILE),
        ['vanilla-lazyload'],
        '1.0.0',
        true
    );

    // Inline CSS for Lazy Loading
    $css = "
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
    ";
    wp_add_inline_style('macp-lazy-load', $css);
}
  
  
    private function is_lazy_load_enabled() {
        return get_option('macp_enable_lazy_load', 1);
    }
}