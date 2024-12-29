<?php
/**
 * Calculates cache performance metrics
 */
class MACP_Metrics_Calculator {
    private $redis;
    private $metrics_key = 'macp_cache_metrics';

    public function __construct(MACP_Redis $redis) {
        $this->redis = $redis;
    }

    public function get_hit_rate($cache_type) {
        if (!$this->redis) return 0;

        $hits = (int)$this->redis->hget($this->metrics_key, $cache_type . '_hits') ?: 0;
        $misses = (int)$this->redis->hget($this->metrics_key, $cache_type . '_misses') ?: 0;
        
        $total = $hits + $misses;
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    public function get_all_metrics() {
        if (!$this->redis) return [];

        $metrics = $this->redis->hgetall($this->metrics_key) ?: [];
        
        return [
            'html_cache' => [
                'hit_rate' => $this->get_hit_rate('html'),
                'hits' => (int)($metrics['html_hits'] ?? 0),
                'misses' => (int)($metrics['html_misses'] ?? 0)
            ],
            'redis_cache' => [
                'hit_rate' => $this->get_hit_rate('redis'),
                'hits' => (int)($metrics['redis_hits'] ?? 0),
                'misses' => (int)($metrics['redis_misses'] ?? 0)
            ],
            'total_requests' => (int)($metrics['total_requests'] ?? 0)
        ];
    }
}