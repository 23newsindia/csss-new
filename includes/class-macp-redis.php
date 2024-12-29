<?php
class MACP_Redis {
    private $redis;
    private $metrics_recorder;
    private $compression_threshold = 1024;
    private $batch_queue = [];
    private $batch_size = 50;

    public function __construct() {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->metrics_recorder = new MACP_Metrics_Recorder();
            } catch (Exception $e) {
                error_log('Redis connection failed: ' . $e->getMessage());
            }
        }
    }

    public function get($key) {
        if (!$this->redis) {
            $this->metrics_recorder->record_miss('redis');
            return false;
        }

        $value = $this->redis->get("macp:$key");
        if ($value !== false) {
            $this->metrics_recorder->record_hit('redis');
            return $this->decompress($value);
        }

        $this->metrics_recorder->record_miss('redis');
        return false;
    }

    public function set($key, $value, $ttl = 3600) {
        if (!$this->redis) return false;

        $compressed = $this->compress($value);
        return $this->redis->setex("macp:$key", $ttl, $compressed);
    }

    public function delete($key) {
        if (!$this->redis) return false;
        return $this->redis->del("macp:$key");
    }

    public function flush() {
        if (!$this->redis) return false;
        return $this->redis->flushDb();
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
        if (empty($this->batch_queue) || !$this->redis) {
            return;
        }

        $pipeline = $this->redis->multi(Redis::PIPELINE);
        foreach ($this->batch_queue as $item) {
            $compressed = $this->compress($item['value']);
            $pipeline->setex("macp:{$item['key']}", $item['ttl'], $compressed);
        }
        $pipeline->exec();
        $this->batch_queue = [];
    }
}