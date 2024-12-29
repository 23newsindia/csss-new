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
            $success = $this->connection->get_redis()->setex("macp:$key", $ttl, $compressed);
            if ($success) {
                $this->metrics_recorder->record_hit('redis');
            }
            return $success;
        } catch (Exception $e) {
            error_log('Redis set error: ' . $e->getMessage());
            $this->metrics_recorder->record_miss('redis');
            return false;
        }
    }

    public function is_available() {
        return $this->connection->is_connected();
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
            $result = $pipeline->exec();
            
            // Record metrics for batch operations
            foreach ($result as $success) {
                if ($success) {
                    $this->metrics_recorder->record_hit('redis');
                } else {
                    $this->metrics_recorder->record_miss('redis');
                }
            }
        } catch (Exception $e) {
            error_log('Redis pipeline error: ' . $e->getMessage());
            // Record misses for failed batch
            foreach ($this->batch_queue as $item) {
                $this->metrics_recorder->record_miss('redis');
            }
        }
        $this->batch_queue = [];
    }
}