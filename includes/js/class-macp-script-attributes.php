<?php
/**
 * Handles script attribute parsing and validation
 */
class MACP_Script_Attributes {
    /**
     * Check if script tag has specific attribute
     */
    public static function has_attribute($tag, $attribute) {
        return strpos($tag, $attribute) !== false;
    }

    /**
     * Check if script is an inline configuration script
     */
    public static function is_inline_config($tag) {
        return strpos($tag, '-js-extra') !== false || 
               strpos($tag, 'CDATA') !== false ||
               !preg_match('/\ssrc=["\']([^"\']+)["\']/', $tag);
    }
}