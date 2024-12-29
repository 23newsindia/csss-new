<?php
class MACP_Redis {
    private $connection;
    private $metrics_recorder;
    private $compression_threshold = 1024;
    private $batch_queue = [];
    private $batch_size = 50;

    public function __construct() {
        $this->connection = MACP_Redis_Connection::get_instance();
        $this->metrics_recorder = new MACP_Metrics_Recorder();
    }

    public function is_available() {
        return $this->connection->is_connected();
    }

    public function get_status() {
        $status = new MACP_Redis_Status();
        return $status->get_status();
    }

    public function prime_cache() {
        if (!$this->is_available()) {
            return;
        }

        try {
            $cache_dir = WP_CONTENT_DIR . '/cache/macp/';
            if (!is_dir($cache_dir)) {
                return;
            }

            $files = glob($cache_dir . '*.html');
            if (!is_array($files)) {
                return;
            }

            foreach ($files as $file) {
                $key = basename($file, '.html');
                if (!$this->connection->get_redis()->exists("macp:$key")) {
                    $content = file_get_contents($file);
                    if ($content !== false) {
                        $this->set($key, $content);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Redis cache priming failed: ' . $e->getMessage());
        }
    }

    public function get($key) {
        if (!$this->is_available()) {
            $this->metrics_recorder->record_miss('redis');
            return false;
        }

        try {
            $value = $this->connection->get_redis()->get("macp:$key");
            if ($value !== false) {
                $this->metrics_recorder->record_hit('redis');
                return $this->decompress($value);
            }
        } catch (Exception $e) {
            error_log('Redis get error: ' . $e->getMessage());
        }

        $this->metrics_recorder->record_miss('redis');
        return false;
    }

    public function set($key, $value, $ttl = 3600) {
        if (!$this->is_available()) {
            return false;
        }

        try {
            $compressed = $this->compress($value);
            return $this->connection->get_redis()->setex("macp:$key", $ttl, $compressed);
        } catch (Exception $e) {
            error_log('Redis set error: ' . $e->getMessage());
            return false;
        }
    }

    private function compress($data) {
        if (strlen($data) < $this->compression_threshold) {
            return $data;
        }
        return gzcompress($data, 9);
    }

    private function decompress($data) {
        if ($this->is_compressed($data)) {
            return gzuncompress($data);
        }
        return $data;
    }

    private function is_compressed($data) {
        return substr($data, 0, 2) === "\x1f\x8b" || substr($data, 0, 2) === "\x78\x9c";
    }

    public function queue_set($key, $value, $ttl = 3600) {
        $this->batch_queue[] = [
            'key' => $key,
            'value' => $value,
            'ttl' => $ttl
        ];

        if (count($this->batch_queue) >= $this->batch_size) {
            $this->flush_queue();
        }
    }

    public function flush_queue() {
        if (empty($this->batch_queue) || !$this->is_available()) {
            return;
        }

        try {
            $pipeline = $this->connection->get_redis()->multi(Redis::PIPELINE);
            foreach ($this->batch_queue as $item) {
                $compressed = $this->compress($item['value']);
                $pipeline->setex("macp:{$item['key']}", $item['ttl'], $compressed);
            }
            $pipeline->exec();
        } catch (Exception $e) {
            error_log('Redis pipeline error: ' . $e->getMessage());
        }
        $this->batch_queue = [];
    }
}