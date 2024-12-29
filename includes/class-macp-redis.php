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
            return $this->connection->get_redis()->setex("macp:$key", $ttl, $compressed);
        } catch (Exception $e) {
            error_log('Redis set error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete($key) {
        if (!$this->is_available()) {
            return false;
        }

        try {
            return $this->connection->get_redis()->del("macp:$key");
        } catch (Exception $e) {
            error_log('Redis delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_pattern($pattern) {
        if (!$this->is_available()) {
            return false;
        }

        try {
            $keys = $this->connection->get_redis()->keys("macp:$pattern");
            if (!empty($keys)) {
                return $this->connection->get_redis()->del($keys);
            }
            return true;
        } catch (Exception $e) {
            error_log('Redis delete pattern error: ' . $e->getMessage());
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
        return substr($data, 0, 2) === "\x78\x9c";
    }

    public function flush() {
        if (!$this->is_available()) {
            return false;
        }

        try {
            return $this->connection->get_redis()->flushDb();
        } catch (Exception $e) {
            error_log('Redis flush error: ' . $e->getMessage());
            return false;
        }
    }
}