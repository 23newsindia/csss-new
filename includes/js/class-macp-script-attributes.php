<?php
/**
 * Handles script attribute management and transformation
 */
class MACP_Script_Attributes {
    /**
     * Check if script should be excluded from processing
     */
    public static function is_excluded($tag, $excluded_scripts) {
        foreach ($excluded_scripts as $excluded_script) {
            if (!empty($excluded_script) && strpos($tag, $excluded_script) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract script attributes from tag
     */
    public static function extract_attributes($tag) {
        $attributes = [];
        if (preg_match_all('/(\w+)=(["\'])(.*?)\2/i', $tag, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[3];
            }
        }
        return $attributes;
    }

    /**
     * Get script source from tag
     */
    public static function get_src($tag) {
        if (preg_match('/src=["\']([^"\']+)["\']/', $tag, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Get inline content from script tag
     */
    public static function get_inline_content($tag) {
        if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $tag, $match)) {
            return $match[1];
        }
        return '';
    }
}