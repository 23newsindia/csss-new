<?php
class MACP_Lazy_Load_Processor {
    private $excluded_classes = ['no-lazy', 'skip-lazy'];
    private $excluded_patterns = [];

    public function __construct() {
        $this->excluded_patterns = $this->get_excluded_patterns();
    }

    public function process_content($content) {
        if (empty($content)) {
            return $content;
        }

        // Process images
        $content = preg_replace_callback('/<img[^>]*>/i', [$this, 'process_image'], $content);
        
        // Process iframes
        $content = preg_replace_callback('/<iframe[^>]*>/i', [$this, 'process_iframe'], $content);

        return $content;
    }

    private function process_image($matches) {
        $img = $matches[0];

        // Skip if already processed or excluded
        if ($this->should_skip($img)) {
            return $img;
        }

        // Get src and srcset
        $src = $this->get_attribute($img, 'src');
        $srcset = $this->get_attribute($img, 'srcset');
        $sizes = $this->get_attribute($img, 'sizes');

        // Replace src with data-src
        $img = $this->replace_attribute($img, 'src', 'data-src');
        if ($srcset) {
            $img = $this->replace_attribute($img, 'srcset', 'data-srcset');
        }
        if ($sizes) {
            $img = $this->replace_attribute($img, 'sizes', 'data-sizes');
        }

        // Add lazy class
        $img = $this->add_class($img, 'lazy');

        // Add noscript fallback
        $img .= '<noscript>' . $matches[0] . '</noscript>';

        return $img;
    }

    private function process_iframe($matches) {
        $iframe = $matches[0];

        if ($this->should_skip($iframe)) {
            return $iframe;
        }

        $src = $this->get_attribute($iframe, 'src');
        $iframe = $this->replace_attribute($iframe, 'src', 'data-src');
        $iframe = $this->add_class($iframe, 'lazy');

        return $iframe . '<noscript>' . $matches[0] . '</noscript>';
    }

    private function should_skip($html) {
        // Skip if already lazy loaded
        if (strpos($html, 'data-src') !== false || strpos($html, 'lazy') !== false) {
            return true;
        }

        // Check excluded classes
        foreach ($this->excluded_classes as $class) {
            if (strpos($html, $class) !== false) {
                return true;
            }
        }

        // Check excluded patterns
        foreach ($this->excluded_patterns as $pattern) {
            if (strpos($html, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function get_excluded_patterns() {
        return array_merge([
            'data:image',
            'gravatar.com',
            'wp-admin',
        ], get_option('macp_lazy_load_excluded', []));
    }

    private function get_attribute($html, $attribute) {
        if (preg_match('/' . $attribute . '=[\'"](.*?)[\'"]/i', $html, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function replace_attribute($html, $old, $new) {
        return preg_replace('/' . $old . '=/i', $new . '=', $html);
    }

    private function add_class($html, $class) {
        if (strpos($html, 'class=') !== false) {
            return preg_replace('/class=([\'"])(.*?)([\'"])/i', 'class=$1$2 ' . $class . '$3', $html);
        }
        return str_replace('>', ' class="' . $class . '">', $html);
    }
}