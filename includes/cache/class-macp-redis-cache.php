<?php
class MACP_Redis_Cache {
    private $redis;
    private $default_ttl = 3600;

    public function __construct() {
        $this->redis = new Redis();
        $this->connect();
    }

    private function connect() {
        try {
            $this->redis->connect('127.0.0.1', 6379);
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        } catch (Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }

    public function get($key) {
        if (!$this->redis) return false;
        return $this->redis->get("macp:$key");
    }

    public function set($key, $value, $ttl = null) {
        if (!$this->redis) return false;
        $ttl = $ttl ?? $this->default_ttl;
        return $this->redis->setex("macp:$key", $ttl, $value);
    }
}