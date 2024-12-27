<?php
class MACP_JS_Buffer_Handler {
    private $excluded_scripts = [];
    private $buffer = '';
    private static $instance = null;

    public function __construct($excluded_scripts = []) {
        $this->excluded_scripts = $excluded_scripts;
        self::$instance = $this;
    }

    public static function get_instance() {
        return self::$instance;
    }

    public function start_buffering() {
        ob_start([$this, 'process_buffer']);
    }

    public function end_buffering() {
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    public function process_buffer($html) {
        if (!get_option('macp_enable_js_delay', 0)) {
            return $html;
        }

        // Process all script tags, including those in head
        $pattern = '/(<script[^>]*>.*?<\/script>)|(<head.*?<\/head>)/is';
        
        return preg_replace_callback($pattern, function($matches) {
            // If this is a head section
            if (strpos($matches[0], '<head') === 0) {
                return preg_replace_callback(
                    '/<script\b[^>]*>.*?<\/script>/is',
                    [$this, 'process_script_tag'],
                    $matches[0]
                );
            }
            // If this is a single script tag
            return $this->process_script_tag($matches);
        }, $html);
    }

    private function process_script_tag($matches) {
        $tag = is_array($matches) ? $matches[0] : $matches;

        // Skip if already processed
        if (strpos($tag, 'rocketlazyloadscript') !== false) {
            return $tag;
        }

        // Skip if excluded
        foreach ($this->excluded_scripts as $excluded_script) {
            if (!empty($excluded_script) && strpos($tag, $excluded_script) !== false) {
                return $tag;
            }
        }

        // Extract all attributes
        $attributes = [];
        if (preg_match_all('/(\w+)=(["\'])(.*?)\2/i', $tag, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[3];
            }
        }

        // Build new tag
        $new_tag = '<script type="rocketlazyloadscript"';

        // Handle src attribute specially
        if (isset($attributes['src'])) {
            $new_tag .= ' data-rocket-src="' . $attributes['src'] . '"';
            unset($attributes['src']);
        }

        // Add all other attributes except type
        unset($attributes['type']);
        foreach ($attributes as $name => $value) {
            $new_tag .= ' ' . $name . '="' . $value . '"';
        }

        // Preserve inline script content if any
        if (preg_match('/<script[^>]*>(.*?)<\/script>/is', $tag, $content_matches)) {
            $new_tag .= '>' . $content_matches[1] . '</script>';
        } else {
            $new_tag .= '></script>';
        }

        return $new_tag;
    }
}