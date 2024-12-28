<?php
class MACP_CSS_Optimizer {
    private $cache_dir;

    public function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/cache/macp/css/';
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }

    public function optimize_css($css) {
        if (empty($css)) {
            return $css;
        }

        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove media queries (for mobile)
        $css = preg_replace('/@media\s+[^{]+\{([^{}]*\{[^{}]*\})*[^{}]*\}/i', '', $css);
        
        return trim($css);
    }

    public function clear_css_cache() {
        array_map('unlink', glob($this->cache_dir . '*'));
    }
}