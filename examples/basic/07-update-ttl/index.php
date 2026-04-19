<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

Cache::set('session', 'data', 60);

$item = Cache::getItem('session');
echo "Original TTL: " . $item->getTTL() . " seconds\n";

Cache::setTTL('session', 3600);

$item = Cache::getItem('session');
echo "Updated TTL:  " . $item->getTTL() . " seconds\n";

// setTTL returns false for non-existent keys
$result = Cache::setTTL('no_such_key', 100);
echo "setTTL on missing key: " . ($result ? 'true' : 'false') . "\n";

Cache::flush();
