<?php
class MACP_CSS_Config {
    public static function get_safelist() {
        $saved_safelist = get_option('macp_css_safelist', []);
        
        $default_safelist = [
            'wp-*',
            'admin-bar*',
            'dashicons*',
            'menu-item*',
            'current-menu-item',
            'page-numbers',
            'post-*',
            'sticky',
            'bypostauthor',
            'wp-caption*',
            'gallery*'
        ];

        return array_merge($default_safelist, $saved_safelist);
    }

    public static function get_excluded_patterns() {
        $saved_patterns = get_option('macp_css_excluded_patterns', []);
        
        $default_patterns = [
            'admin-bar.min.css',
            'dashicons.min.css'
        ];

        return array_merge($default_patterns, $saved_patterns);
    }

    public static function save_safelist($patterns) {
        update_option('macp_css_safelist', array_filter(array_map('sanitize_text_field', $patterns)));
    }

    public static function save_excluded_patterns($patterns) {
        update_option('macp_css_excluded_patterns', array_filter(array_map('sanitize_text_field', $patterns)));
    }
}