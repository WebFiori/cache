<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// First call: cache miss, generator runs
$data = Cache::get('user_count', function () {
    echo "Generator called: fetching data...\n";
    return 42;
}, 60);
echo "Result: $data\n";

// Second call: cache hit, generator does NOT run
$data = Cache::get('user_count', function () {
    echo "Generator called again (should not appear).\n";
    return 99;
}, 60);
echo "Result (from cache): $data\n";

Cache::flush();
