<?php
/**
 * Handles the actual generation of Critical CSS
 */
class MACP_Critical_CSS_Generator {
    private $filesystem;
    private $base_dir;
    
    public function __construct() {
        $this->filesystem = new MACP_Filesystem();
        $this->base_dir = WP_CONTENT_DIR . '/cache/macp/critical-css/';
        $this->init();
    }

    private function init() {
        // Create base directory if it doesn't exist
        if (!file_exists($this->base_dir)) {
            wp_mkdir_p($this->base_dir);
        }
    }

    public function generate_mobile_css() {
        // Create directory if it doesn't exist
        if (!file_exists($this->base_dir)) {
            if (!wp_mkdir_p($this->base_dir)) {
                return false;
            }
        }

        // Get templates to generate CSS for
        $templates = $this->get_templates_list();
        
        foreach ($templates as $key => $url) {
            if ($url) {
                $this->generate_template_css($key, $url);
            }
        }

        return true;
    }

    private function get_templates_list() {
        return [
            'front_page' => home_url('/'),
            'blog' => get_permalink(get_option('page_for_posts')),
            'post' => $this->get_latest_post_url(),
            'page' => $this->get_sample_page_url()
        ];
    }

    private function generate_template_css($key, $url) {
        $filename = $key . '-mobile.css';
        $filepath = $this->base_dir . $filename;

        // Generate CSS content
        $css = $this->extract_critical_css($url);
        
        if ($css) {
            return file_put_contents($filepath, $css);
        }
        
        return false;
    }

    private function extract_critical_css($url) {
        $response = wp_remote_get($url, [
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $html = wp_remote_retrieve_body($response);
        
        // Extract all CSS
        $css = '';

        // Get inline styles
        preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $html, $matches);
        if (!empty($matches[1])) {
            $css .= implode("\n", $matches[1]);
        }

        // Get external stylesheets
        preg_match_all('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $stylesheet) {
                $style_url = $this->make_absolute_url($stylesheet, $url);
                $style_content = $this->get_external_css($style_url);
                if ($style_content) {
                    $css .= "\n" . $style_content;
                }
            }
        }

        return $this->optimize_css($css);
    }

    private function get_external_css($url) {
        $response = wp_remote_get($url);
        if (!is_wp_error($response)) {
            return wp_remote_retrieve_body($response);
        }
        return false;
    }

    private function make_absolute_url($url, $base) {
        if (strpos($url, 'http') !== 0) {
            if (strpos($url, '//') === 0) {
                return 'https:' . $url;
            }
            if (strpos($url, '/') === 0) {
                $parsed = parse_url($base);
                return $parsed['scheme'] . '://' . $parsed['host'] . $url;
            }
        }
        return $url;
    }

    private function optimize_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove media queries (for mobile)
        $css = preg_replace('/@media\s+[^{]+\{([^{}]*\{[^{}]*\})*[^{}]*\}/i', '', $css);
        
        return trim($css);
    }

    private function get_latest_post_url() {
        $posts = get_posts(['numberposts' => 1]);
        return !empty($posts) ? get_permalink($posts[0]) : false;
    }

    private function get_sample_page_url() {
        $pages = get_pages(['number' => 1]);
        return !empty($pages) ? get_permalink($pages[0]) : false;
    }
}