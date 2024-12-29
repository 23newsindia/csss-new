<?php
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-filesystem.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-url-helper.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-cache-helper.php';

class MACP_HTML_Cache {
    private $cache_dir;
    private $excluded_urls;
    private $css_optimizer;
    private $redis;

    public function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/cache/macp/';
        $this->excluded_urls = $this->get_excluded_urls();
        $this->redis = new MACP_Redis();
        
        if (get_option('macp_remove_unused_css', 0)) {
            $this->css_optimizer = new MACP_CSS_Optimizer();
        }
        
        $this->ensure_cache_directory();
    }

    private function get_excluded_urls() {
        return [
            'wp-login.php',
            'wp-admin',
            'wp-cron.php',
            'wp-content',
            'wp-includes',
            'xmlrpc.php',
            'wp-api',
            '/cart/',
            '/checkout/',
            '/my-account/',
            'add-to-cart',
            'logout',
            'lost-password',
            'register'
        ];
    }

    private function ensure_cache_directory() {
        if (!MACP_Filesystem::ensure_directory($this->cache_dir)) {
            MACP_Debug::log("Failed to create or access cache directory: " . $this->cache_dir);
            return false;
        }
        
        if (!file_exists($this->cache_dir . 'index.php')) {
            MACP_Filesystem::write_file($this->cache_dir . 'index.php', '<?php // Silence is golden');
        }
        
        return true;
    }

    public function should_cache_page() {
        if (!MACP_Cache_Helper::is_cacheable_request()) {
            return false;
        }

        $current_url = $_SERVER['REQUEST_URI'];
        foreach ($this->excluded_urls as $excluded_url) {
            if (strpos($current_url, $excluded_url) !== false) {
                MACP_Debug::log("Not caching: Excluded URL pattern found - {$excluded_url}");
                return false;
            }
        }

        return true;
    }

    public function start_buffer() {
        if ($this->should_cache_page()) {
            ob_start([$this, 'cache_output']);
        }
    }

    public function cache_output($buffer) {
        if (strlen($buffer) < 255) {
            return $buffer;
        }

        // Ensure proper protocol in URLs
        $buffer = $this->fix_mixed_content($buffer);

        // Add CSS optimization if enabled
        if (isset($this->css_optimizer)) {
            try {
                $buffer = $this->css_optimizer->optimize_css($buffer);
            } catch (Exception $e) {
                MACP_Debug::log("CSS optimization error: " . $e->getMessage());
            }
        }

        // Apply HTML minification if enabled
        if (get_option('macp_minify_html', 0)) {
            try {
                require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-html-minifier.php';
                $minifier = new MACP_HTML_Minifier([
                    'remove_comments' => true,
                    'remove_whitespace' => true,
                    'remove_blank_lines' => true,
                    'compress_js' => true,
                    'compress_css' => true
                ]);
                $buffer = $minifier->minify($buffer);
            } catch (Exception $e) {
                MACP_Debug::log("HTML minification error: " . $e->getMessage());
            }
        }

        // Get cache paths
        $cache_key = MACP_Cache_Helper::get_cache_key();
        $cache_paths = [
            'html' => MACP_Cache_Helper::get_cache_path($cache_key),
            'gzip' => MACP_Cache_Helper::get_cache_path($cache_key, true)
        ];

        // Save uncompressed version
        if (!MACP_Filesystem::write_file($cache_paths['html'], $buffer)) {
            return $buffer;
        }
        
        // Save gzipped version if enabled
        if (get_option('macp_enable_gzip', 1)) {
            $gzipped = gzencode($buffer, 9);
            if ($gzipped) {
                MACP_Filesystem::write_file($cache_paths['gzip'], $gzipped);
            }
        }

        // Store in Redis for faster access
        if ($this->redis) {
            $this->redis->queue_set('html_' . $cache_key, $buffer, 3600);
            $this->redis->flush_queue();
        }

        return $buffer;
    }

    private function fix_mixed_content($html) {
        if (MACP_URL_Helper::is_https()) {
            // Replace http:// with https:// for same domain resources
            $domain = preg_quote($_SERVER['HTTP_HOST'], '/');
            $html = preg_replace(
                '/(http:\/\/' . $domain . ')/i',
                'https://' . $_SERVER['HTTP_HOST'],
                $html
            );
            
            // Fix protocol-relative URLs
            $html = preg_replace('/src="\/\//i', 'src="https://', $html);
            $html = preg_replace('/href="\/\//i', 'href="https://', $html);
        }
        return $html;
    }

    public function clear_cache($post_id = null) {
        if ($post_id) {
            $url = get_permalink($post_id);
            if ($url) {
                $cache_key = MACP_Cache_Helper::get_cache_key($url);
                $files_to_delete = [
                    MACP_Cache_Helper::get_cache_path($cache_key),
                    MACP_Cache_Helper::get_cache_path($cache_key, true)
                ];
                
                foreach ($files_to_delete as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                // Clear Redis cache
                if ($this->redis) {
                    $this->redis->delete('html_' . $cache_key);
                }
            }
        } else {
            array_map('unlink', glob($this->cache_dir . '*.{html,gz}', GLOB_BRACE));
            
            // Clear all Redis HTML cache
            if ($this->redis) {
                $this->redis->delete($this->redis->keys('html_*'));
            }
        }
    }
}