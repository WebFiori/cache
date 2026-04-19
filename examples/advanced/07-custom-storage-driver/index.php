<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\Storage;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

/**
 * A simple in-memory storage driver.
 */
class MemoryStorage implements Storage {
    private array $store = [];

    public function store(Item $item) {
        $key = $item->getPrefix() . $item->getKey();
        $this->store[$key] = [
            'data'       => $item->getDataEncrypted(),
            'ttl'        => $item->getTTL(),
            'created_at' => $item->getCreatedAt(),
            'expires'    => $item->getExpiryTime(),
            'prefix'     => $item->getPrefix(),
        ];
    }

    public function read(string $key, ?string $prefix) {
        $item = $this->readItem($key, $prefix);
        return $item ? $item->getDataDecrypted() : null;
    }

    public function readItem(string $key, ?string $prefix): ?Item {
        $fullKey = ($prefix ?? '') . $key;
        if (!isset($this->store[$fullKey])) {
            return null;
        }
        $entry = $this->store[$fullKey];
        if (time() > $entry['expires']) {
            unset($this->store[$fullKey]);
            return null;
        }
        $item = new Item($key, $entry['data'], $entry['ttl'], KeyManager::getEncryptionKey());
        $item->setCreatedAt($entry['created_at']);
        $item->setPrefix($prefix ?? '');
        $item->setDataIsEncrypted(true);
        return $item;
    }

    public function has(string $key, ?string $prefix): bool {
        return $this->readItem($key, $prefix) !== null;
    }

    public function delete(string $key) {
        unset($this->store[$key]);
    }

    public function flush(?string $prefix) {
        if ($prefix === null) {
            $this->store = [];
        } else {
            foreach (array_keys($this->store) as $k) {
                if (str_starts_with($k, $prefix)) {
                    unset($this->store[$k]);
                }
            }
        }
    }

    public function purgeExpired(): int {
        $removed = 0;
        $now = time();
        foreach ($this->store as $k => $entry) {
            if ($now > $entry['expires']) {
                unset($this->store[$k]);
                $removed++;
            }
        }
        return $removed;
    }

    public function count(): int {
        return count($this->store);
    }
}

// Use the custom driver
$memory = new MemoryStorage();
Cache::setDriver($memory);

Cache::set('key1', 'value1', 60);
Cache::set('key2', 'value2', 60);

echo "Driver type: " . get_class(Cache::getDriver()) . "\n";
echo "Items stored: " . $memory->count() . "\n";
echo "key1: " . Cache::get('key1') . "\n";
echo "key2: " . Cache::get('key2') . "\n";

Cache::flush();
echo "After flush: " . $memory->count() . " items\n";
