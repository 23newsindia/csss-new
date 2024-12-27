<?php
/**
 * Handles script exclusion logic
 */
class MACP_Script_Exclusions {
    private static $default_exclusions = [
        '/wp-admin/',
        'wp-includes/js/admin-bar',
        'wp-includes/js/jquery/jquery',
        'wp-includes/js/jquery/jquery-migrate',
        'wp-includes/js/underscore',
        'wp-includes/js/wp-util',
        'wp-includes/js/api-request',
        'wp-includes/js/dist/a11y',
        'wp-admin/js/common',
        'wp-includes/js/hoverintent-js',
        'wp-includes/js/clipboard'
    ];

    /**
     * Check if script should be excluded
     */
    public static function should_exclude($src, $type = 'defer') {
        // Always exclude admin scripts
        if (is_admin() || strpos($src, '/wp-admin/') !== false) {
            return true;
        }

        // Check default exclusions
        foreach (self::$default_exclusions as $pattern) {
            if (strpos($src, $pattern) !== false) {
                return true;
            }
        }

        // Get custom exclusions
        $custom_exclusions = get_option("macp_{$type}_excluded_scripts", []);
        
        // Check custom exclusions
        foreach ($custom_exclusions as $pattern) {
            if (!empty($pattern) && strpos($src, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save custom exclusions
     */
    public static function save_exclusions($exclusions, $type = 'defer') {
        $clean_exclusions = array_map('trim', explode("\n", $exclusions));
        $clean_exclusions = array_filter($clean_exclusions);
        update_option("macp_{$type}_excluded_scripts", $clean_exclusions);
    }
}