<?php
class MACP_CSS_Extractor {
    public static function extract_css_files($html) {
        preg_match_all('/<link[^>]+href=[\'"]([^\'"]+\.css(?:\?[^\'"]*)?)[\'"][^>]*>/i', $html, $matches);
        return array_filter($matches[1], function($url) {
            return strpos($url, '.css') !== false;
        });
    }

    public static function extract_inline_styles($html) {
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $matches);
        return array_filter($matches[1]);
    }

    public static function get_css_content($url) {
        if (strpos($url, '//') === 0) {
            $url = (is_ssl() ? 'https:' : 'http:') . $url;
        } elseif (strpos($url, 'http') !== 0) {
            $url = site_url($url);
        }

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }
}