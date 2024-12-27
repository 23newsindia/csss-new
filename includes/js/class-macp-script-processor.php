<?php
/**
 * Handles script tag processing and transformation
 */
class MACP_Script_Processor {
    /**
     * Process a script tag
     */
    public static function process_tag($tag, $excluded_scripts = []) {
        // Skip if already processed or excluded
        if (strpos($tag, 'rocketlazyloadscript') !== false || 
            MACP_Script_Attributes::is_excluded($tag, $excluded_scripts)) {
            return $tag;
        }

        $attributes = MACP_Script_Attributes::extract_attributes($tag);
        $src = MACP_Script_Attributes::get_src($tag);
        $inline_content = MACP_Script_Attributes::get_inline_content($tag);

        // Build new tag
        $new_tag = '<script';

        // Handle script type
        if (get_option('macp_enable_js_delay', 0)) {
            $new_tag .= ' type="rocketlazyloadscript"';
        } else {
            $new_tag .= ' type="text/javascript"';
        }

        // Handle src attribute
        if ($src) {
            if (get_option('macp_enable_js_delay', 0)) {
                $new_tag .= ' data-rocket-src="' . $src . '"';
            } else {
                $new_tag .= ' src="' . $src . '"';
            }
        }

        // Add defer attribute if enabled
        if (get_option('macp_enable_js_defer', 0)) {
            $new_tag .= ' defer="defer"';
        }

        // Add remaining attributes
        foreach ($attributes as $name => $value) {
            if (!in_array($name, ['type', 'src'])) {
                $new_tag .= ' ' . $name . '="' . $value . '"';
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
}