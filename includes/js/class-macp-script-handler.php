<?php
/**
 * Handles WordPress script processing and modifications
 */
class MACP_Script_Handler {
    private $excluded_scripts = [];

    public function __construct() {
        $this->excluded_scripts = get_option('macp_excluded_scripts', []);
        $this->init_hooks();
    }

    private function init_hooks() {
        // Hook into script_loader_tag to modify script tags
        add_filter('script_loader_tag', [$this, 'process_script_tag'], 10, 3);
    }

    public function process_script_tag($tag, $handle, $src) {
        // Skip processing if defer is not enabled
        if (!get_option('macp_enable_js_defer', 0)) {
            return $tag;
        }

        // Skip if script is excluded
        if ($this->is_script_excluded($handle, $src)) {
            return $tag;
        }

        // Don't add defer to inline scripts
        if (!$src) {
            return $tag;
        }

        // Add defer attribute if not already present
        if (strpos($tag, 'defer') === false) {
            $tag = str_replace(' src=', ' defer="defer" src=', $tag);
        }

        return $tag;
    }

    private function is_script_excluded($handle, $src) {
        // Check handle-based exclusions
        if (in_array($handle, $this->excluded_scripts)) {
            return true;
        }

        // Check URL-based exclusions
        foreach ($this->excluded_scripts as $excluded_script) {
            if (strpos($src, $excluded_script) !== false) {
                return true;
            }
        }

        return false;
    }
}