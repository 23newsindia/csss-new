<?php
class MACP_Cache_Manager {
    private $redis_cache;
    private $file_cache;
    private $cache_headers;
    private $cache_metrics;

    public function __construct() {
        $this->redis_cache = new MACP_Redis_Cache();
        $this->file_cache = new MACP_File_Cache();
        $this->cache_headers = new MACP_Cache_Headers();
        $this->cache_metrics = new MACP_Cache_Metrics();
        
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('template_redirect', [$this->cache_headers, 'set_headers']);
        add_action('shutdown', [$this->cache_metrics, 'save_metrics']);
    }

    public function get_cached_content($key) {
        // Try Redis first (fastest)
        $content = $this->redis_cache->get($key);
        if ($content) {
            $this->cache_metrics->record_hit('redis');
            return $content;
        }

        // Try file cache
        $content = $this->file_cache->get($key);
        if ($content) {
            $this->cache_metrics->record_hit('file');
            // Repopulate Redis
            $this->redis_cache->set($key, $content);
            return $content;
        }

        $this->cache_metrics->record_miss();
        return false;
    }

    public function set_cached_content($key, $content) {
        // Save to both caches
        $this->redis_cache->set($key, $content);
        $this->file_cache->set($key, $content);
    }
}