<?php
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-config.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-extractor.php';

use MatthiasMullie\Minify;

class MACP_CSS_Optimizer {
    private $cache_dir;

    public function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/cache/macp/css/';
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }

    public function optimize_css($html) {
        if (!get_option('macp_remove_unused_css', 0)) {
            return $html;
        }

        try {
            // Extract CSS files and inline styles
            $css_files = MACP_CSS_Extractor::extract_css_files($html);
            $inline_styles = MACP_CSS_Extractor::extract_inline_styles($html);
            
            // Create new minifier instance
            $minifier = new Minify\CSS();
            
            // Process external CSS files
            $processed_files = [];
            foreach ($css_files as $css_url) {
                if ($this->should_process_css($css_url)) {
                    $css_content = MACP_CSS_Extractor::get_css_content($css_url);
                    if ($css_content) {
                        $minifier->add($css_content);
                        $processed_files[] = $css_url;
                    }
                }
            }

            // Process inline styles
            foreach ($inline_styles as $style) {
                $minifier->add($style);
            }

            // Minify all CSS
            $optimized_css = $minifier->minify();
            
            // Save optimized CSS
            $cache_key = md5($optimized_css);
            $optimized_file = $this->cache_dir . 'optimized_' . $cache_key . '.css';
            
            if (!file_exists($optimized_file)) {
                file_put_contents($optimized_file, $optimized_css);
            }

            // Get the URL for the optimized file
            $optimized_url = str_replace(WP_CONTENT_DIR, content_url(), $optimized_file);

            // Remove original CSS files and collect link tags
            $link_tags = [];
            foreach ($processed_files as $original_file) {
                preg_match('/<link[^>]+href=[\'"]' . preg_quote($original_file, '/') . '[\'"][^>]*>/i', $html, $matches);
                if (!empty($matches[0])) {
                    $link_tags[] = $matches[0];
                }
            }

            // Remove all matched link tags
            foreach ($link_tags as $tag) {
                $html = str_replace($tag, '', $html);
            }

            // Remove inline styles
            $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

            // Add optimized CSS file right after opening head tag
            $html = preg_replace(
                '/(<head[^>]*>)/i',
                '$1' . PHP_EOL . '<link rel="stylesheet" href="' . esc_attr($optimized_url) . '" />',
                $html
            );

            return $html;
        } catch (Exception $e) {
            MACP_Debug::log("CSS optimization error: " . $e->getMessage());
            return $html;
        }
    }

    private function should_process_css($url) {
        if (!get_option('macp_process_external_css', 0) && !MACP_CSS_Extractor::is_local_url($url)) {
            return false;
        }

        foreach (MACP_CSS_Config::get_excluded_patterns() as $pattern) {
            if (strpos($url, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    public function clear_css_cache() {
        array_map('unlink', glob($this->cache_dir . '*'));
        MACP_Debug::log("CSS cache cleared");
    }
}