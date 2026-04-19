<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Store with a 2-second TTL
Cache::set('short_lived', 'I expire quickly', 2);
echo "Immediately after set: " . Cache::get('short_lived') . "\n";

echo "Waiting 3 seconds for expiration...\n";
sleep(3);

$data = Cache::get('short_lived');
echo "After expiration: " . var_export($data, true) . "\n";

Cache::flush();
