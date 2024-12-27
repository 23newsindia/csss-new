<?php
/**
 * Handles script tag processing and transformation
 */
class MACP_Script_Processor {
    /**
     * Process a script tag
     */
    public static function process_tag($tag, $excluded_scripts = []) {
        // Skip if already processed
        if (strpos($tag, 'rocketlazyloadscript') !== false) {
            return $tag;
        }

        $src = self::get_script_src($tag);
        $attributes = self::get_script_attributes($tag);
        $inline_content = self::get_inline_content($tag);

        // Skip processing for inline config scripts
        if (self::is_inline_config($tag)) {
            return $tag;
        }

        // Build new tag
        $new_tag = '<script';

        // Handle delay functionality
        if (get_option('macp_enable_js_delay', 0) && !self::is_excluded($src, $excluded_scripts)) {
            $new_tag .= ' type="rocketlazyloadscript"';
            if ($src) {
                $new_tag .= ' data-rocket-src="' . esc_attr($src) . '"';
            }
        } else {
            $new_tag .= ' type="text/javascript"';
            if ($src) {
                $new_tag .= ' src="' . esc_attr($src) . '"';
            }
        }

        // Handle defer functionality
        if (get_option('macp_enable_js_defer', 0) && !self::is_excluded($src, $excluded_scripts)) {
            $new_tag .= ' defer="defer"';
        }

        // Add remaining attributes
        foreach ($attributes as $name => $value) {
            if (!in_array($name, ['type', 'src', 'defer'])) {
                $new_tag .= ' ' . $name . '="' . esc_attr($value) . '"';
            }
        }

        // Add content and close tag
        if ($inline_content) {
            $new_tag .= '>' . $inline_content . '</script>';
        } else {
            $new_tag .= '></script>';
        }

        return $new_tag;
    }

    /**
     * Get script src attribute
     */
    private static function get_script_src($tag) {
        if (preg_match('/src=["\']([^"\']+)["\']/', $tag, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Get script attributes
     */
    private static function get_script_attributes($tag) {
        $attributes = [];
        if (preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $tag, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }
        return $attributes;
    }

    /**
     * Get inline script content
     */
    private static function get_inline_content($tag) {
        if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $tag, $match)) {
            return $match[1];
        }
        return '';
    }

    /**
     * Check if script is an inline configuration script
     */
    private static function is_inline_config($tag) {
        return strpos($tag, '-js-extra') !== false || 
               strpos($tag, 'CDATA') !== false ||
               !preg_match('/\ssrc=["\']([^"\']+)["\']/', $tag);
    }

    /**
     * Check if script should be excluded
     */
    private static function is_excluded($src, $excluded_scripts) {
        if (!$src || empty($excluded_scripts)) {
            return false;
        }

        foreach ($excluded_scripts as $excluded_script) {
            if (!empty($excluded_script) && strpos($src, $excluded_script) !== false) {
                return true;
            }
        }

        return false;
    }
}