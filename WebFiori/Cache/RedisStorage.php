<?php

/**
 * This file is licensed under MIT License.
 *
 * Copyright (c) 2024 WebFiori Framework
 *
 * For more information on the license, please visit:
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 *
 */
namespace WebFiori\Cache;

use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\CacheStorageException;

/**
 * Redis based cache storage engine.
 */
class RedisStorage implements Storage {
    private \Redis $redis;
    private string $redisPrefix;

    /**
     * Creates new instance of the class.
     *
     * @param \Redis $redis A connected Redis instance.
     * @param string $redisPrefix A prefix applied to all Redis keys to avoid
     * collisions with other applications sharing the same Redis instance.
     */
    public function __construct(\Redis $redis, string $redisPrefix = 'wf_cache:') {
        $this->redis = $redis;
        $this->redisPrefix = $redisPrefix;
    }

    /**
     * Removes an item from the cache.
     *
     * @param string $key The key of the item.
     */
    public function delete(string $key) {
        $this->redis->del($this->redisPrefix.md5($key));
    }

    /**
     * Removes all cached items.
     *
     * @param string|null $prefix An optional prefix. If provided, only items
     * with that prefix are deleted.
     */
    public function flush(?string $prefix) {
        $pattern = $this->redisPrefix.($prefix ?? '').'*';
        $iterator = null;

        while (($keys = $this->redis->scan($iterator, $pattern, 100)) !== false) {
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        }
    }

    /**
     * Returns the Redis instance used by this storage.
     *
     * @return \Redis
     */
    public function getRedis(): \Redis {
        return $this->redis;
    }

    /**
     * Returns the Redis key prefix.
     *
     * @return string
     */
    public function getRedisPrefix(): string {
        return $this->redisPrefix;
    }

    /**
     * Checks if an item exist in the cache.
     *
     * @param string $key The value of item key.
     * @param string|null $prefix Optional prefix.
     *
     * @return bool Returns true if given key exist in the cache.
     */
    public function has(string $key, ?string $prefix): bool {
        return $this->redis->exists($this->buildRedisKey($key, $prefix)) > 0;
    }

    /**
     * Redis handles expiry natively, so this is a no-op.
     *
     * @return int Always returns 0.
     */
    public function purgeExpired(): int {
        return 0;
    }

    /**
     * Reads and returns the data stored in cache item given its key.
     *
     * @param string $key The key of the item.
     * @param string|null $prefix Optional prefix.
     *
     * @return mixed|null If cache item is not expired, its data is returned.
     * Otherwise, null is returned.
     */
    public function read(string $key, ?string $prefix) {
        $item = $this->readItem($key, $prefix);

        if ($item !== null) {
            try {
                return $item->getDataDecrypted();
            } catch (CacheException $e) {
                $this->delete(($prefix ?? '').$key);

                return null;
            }
        }

        return null;
    }

    /**
     * Reads cache item as an object given its key.
     *
     * @param string $key The unique identifier of the item.
     * @param string|null $prefix Optional prefix.
     *
     * @return Item|null If cache item exist, an object of type 'Item' is returned.
     * Otherwise, null is returned.
     */
    public function readItem(string $key, ?string $prefix) {
        $raw = $this->redis->get($this->buildRedisKey($key, $prefix));

        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);

        if ($data === null) {
            return null;
        }

        $secretKey = KeyManager::getEncryptionKey();
        $encryptionEnabled = $secretKey !== null;

        $item = new Item($key, $data['data'], $data['ttl'], $secretKey ?? '');
        $item->setCreatedAt($data['created_at']);
        $item->setPrefix($prefix ?? '');

        $wasEncrypted = $data['encrypted'] ?? true;

        if (!$wasEncrypted || !$encryptionEnabled) {
            $config = new SecurityConfig();
            $config->setEncryptionEnabled(false);
            $item->setSecurityConfig($config);
        }

        $item->setDataIsEncrypted($wasEncrypted);
        $item->setDataFromStorage(true);

        return $item;
    }

    /**
     * Store an item into the cache.
     *
     * @param Item $item An item that will be added to the cache.
     * @throws CacheStorageException If storage fails.
     */
    public function store(Item $item) {
        if ($item->getTTL() <= 0) {
            return;
        }

        $redisKey = $this->buildRedisKey($item->getKey(), $item->getPrefix() ?: null);
        $payload = json_encode([
            'data' => $item->getDataEncrypted(),
            'created_at' => time(),
            'ttl' => $item->getTTL(),
            'expires' => $item->getExpiryTime(),
            'key' => $item->getKey(),
            'encrypted' => $item->getSecurityConfig()->isEncryptionEnabled()
        ]);

        if ($payload === false) {
            throw new CacheStorageException("Failed to encode cache data to JSON for key: {$item->getKey()}");
        }

        $result = $this->redis->setex($redisKey, $item->getTTL(), $payload);

        if ($result === false) {
            throw new CacheStorageException("Failed to store cache item in Redis for key: {$item->getKey()}");
        }
    }

    /**
     * Builds the full Redis key from cache key and prefix.
     *
     * @param string $key The cache key.
     * @param string|null $prefix Optional prefix.
     * @return string The full Redis key.
     */
    private function buildRedisKey(string $key, ?string $prefix): string {
        return $this->redisPrefix.($prefix ?? '').md5($key);
    }
}
