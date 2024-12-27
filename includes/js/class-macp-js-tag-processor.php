<?php
class MACP_JS_Tag_Processor {
    public static function process_tag($tag, $attributes = []) {
        // Skip if already processed
        if (strpos($tag, 'rocketlazyloadscript') !== false) {
            return $tag;
        }

        // Build new tag
        $new_tag = '<script type="rocketlazyloadscript"';

        // Handle src attribute
        if (preg_match('/src=["\']([^"\']+)["\']/', $tag, $src_match)) {
            $new_tag .= ' data-rocket-src="' . $src_match[1] . '"';
        }

        // Add preserved attributes
        foreach ($attributes as $name => $value) {
            if ($name !== 'src' && $name !== 'type') {
                $new_tag .= ' ' . $name . '="' . $value . '"';
            }
        }

        // Close tag
        $new_tag .= '></script>';

        return $new_tag;
    }

    public static function extract_attributes($tag) {
        $attributes = [];
        if (preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $tag, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }
        return $attributes;
    }
}