<?php
class MACP_Redis {
    private $redis;
    private $compression_type;
    private $batch_size = 50;
    private $batch_queue = [];
    private $compression_threshold = 1024; // 1KB

    public function __construct() {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->init_compression();
            } catch (Exception $e) {
                error_log('Redis connection failed: ' . $e->getMessage());
            }
        }
    }

    private function init_compression() {
        // Try different compression methods in order of preference
        if (function_exists('zstd_compress')) {
            $this->compression_type = 'zstd';
        } elseif (function_exists('lz4_compress')) {
            $this->compression_type = 'lz4';
        } elseif (function_exists('lzf_compress')) {
            $this->compression_type = 'lzf';
        }
    }

    private function compress($data) {
        if (strlen($data) < $this->compression_threshold) {
            return $data;
        }

        $compressed = null;
        switch ($this->compression_type) {
            case 'zstd':
                $compressed = zstd_compress($data);
                break;
            case 'lz4':
                $compressed = lz4_compress($data);
                break;
            case 'lzf':
                $compressed = lzf_compress($data);
                break;
        }

        return $compressed ?: $data;
    }

    private function decompress($data) {
        if (empty($data)) return $data;

        $decompressed = null;
        switch ($this->compression_type) {
            case 'zstd':
                $decompressed = zstd_uncompress($data);
                break;
            case 'lz4':
                $decompressed = lz4_uncompress($data);
                break;
            case 'lzf':
                $decompressed = lzf_decompress($data);
                break;
        }

        return $decompressed ?: $data;
    }

    public function batch_prefetch($keys) {
        if (empty($keys) || !$this->redis) {
            return [];
        }

        // Split keys into batches
        $batches = array_chunk($keys, $this->batch_size);
        $values = [];

        foreach ($batches as $batch) {
            $compressed_values = $this->redis->mget($batch);
            foreach ($compressed_values as $index => $compressed_value) {
                if ($compressed_value !== false) {
                    $values[$batch[$index]] = unserialize($this->decompress($compressed_value));
                }
            }
        }

        return $values;
    }

    public function queue_set($key, $value, $ttl = 3600) {
        $this->batch_queue[] = [
            'key' => $key,
            'value' => $this->compress(serialize($value)),
            'ttl' => $ttl
        ];

        if (count($this->batch_queue) >= $this->batch_size) {
            $this->flush_queue();
        }
    }

    public function flush_queue() {
        if (empty($this->batch_queue) || !$this->redis) {
            return;
        }

        $pipeline = $this->redis->multi(Redis::PIPELINE);
        foreach ($this->batch_queue as $item) {
            $pipeline->setex($item['key'], $item['ttl'], $item['value']);
        }
        $pipeline->exec();
        $this->batch_queue = [];
    }

    public function prime_cache() {
        if (!$this->redis) return;

        // Cache recent posts
        $recent_posts = get_posts([
            'numberposts' => 10,
            'post_status' => 'publish',
            'fields' => ['ID', 'post_title', 'post_date']
        ]);
        $this->queue_set('recent_posts', $recent_posts, 3600);

        // Cache menu
        $menu_items = wp_get_nav_menu_items('primary');
        if ($menu_items) {
            $this->queue_set('primary_menu', $menu_items, 7200);
        }

        // Cache homepage data
        $homepage_data = [
            'title' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'featured_posts' => get_posts([
                'numberposts' => 5,
                'post_status' => 'publish',
                'category_name' => 'featured',
                'fields' => ['ID', 'post_title', 'post_date']
            ]),
        ];
        $this->queue_set('homepage_data', $homepage_data, 1800);

        // Flush any remaining items in the queue
        $this->flush_queue();
    }
}