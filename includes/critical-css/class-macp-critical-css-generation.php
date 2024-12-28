<?php
/**
 * Handles the background process for Critical CSS generation
 */
class MACP_Critical_CSS_Generation {
    private $critical_css;
    private $queue = [];

    public function __construct(MACP_Critical_CSS $critical_css) {
        $this->critical_css = $critical_css;
    }

    public function push_to_queue($item) {
        $this->queue[] = $item;
    }

    public function save() {
        set_transient('macp_critical_css_queue', $this->queue, HOUR_IN_SECONDS);
        return $this;
    }

    public function dispatch() {
        if (!empty($this->queue)) {
            wp_schedule_single_event(time(), 'macp_generate_critical_css_event');
        }
    }

    public function process_item($item) {
        if (!is_array($item)) {
            return false;
        }

        try {
            $css = $this->generate_critical_css($item['url']);
            if ($css) {
                file_put_contents(
                    WP_CONTENT_DIR . '/cache/macp/critical-css/' . $item['path'],
                    $css
                );
                return true;
            }
        } catch (Exception $e) {
            MACP_Debug::log("Critical CSS generation failed for {$item['url']}: " . $e->getMessage());
        }

        return false;
    }

    private function generate_critical_css($url) {
        // Get page content
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch URL: ' . $url);
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            throw new Exception('Empty response from URL: ' . $url);
        }

        // Extract and process CSS
        $critical_css = $this->extract_critical_css($html);
        if (empty($critical_css)) {
            throw new Exception('No CSS content found');
        }

        return $critical_css;
    }

    private function extract_critical_css($html) {
        $critical_css = '';

        // Create DOM document
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        // Get all CSS links and inline styles
        $links = $dom->getElementsByTagName('link');
        $styles = $dom->getElementsByTagName('style');

        // Process external stylesheets
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'stylesheet') {
                $href = $link->getAttribute('href');
                if ($href) {
                    $css_content = $this->get_external_css($href);
                    if ($css_content) {
                        $critical_css .= $this->process_css($css_content);
                    }
                }
            }
        }

        // Process inline styles
        foreach ($styles as $style) {
            $critical_css .= $this->process_css($style->nodeValue);
        }

        return $critical_css;
    }

    private function get_external_css($url) {
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        } elseif (strpos($url, '/') === 0) {
            $url = home_url($url);
        }

        $response = wp_remote_get($url);
        if (!is_wp_error($response)) {
            return wp_remote_retrieve_body($response);
        }

        return '';
    }

    private function process_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove media queries
        $css = preg_replace('/@media[^{]*{([^{}]|{[^{}]*})*}/i', '', $css);

        return trim($css);
    }
}