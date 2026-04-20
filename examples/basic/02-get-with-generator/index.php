<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

// First call: cache miss, generator runs
$data = $cache->get('user_count', function () {
    echo "Generator called: fetching data...\n";

    return 42;
}, 60);
echo "Result: $data\n";

// Second call: cache hit, generator does NOT run
$data = $cache->get('user_count', function () {
    echo "Generator called again (should not appear).\n";

    return 99;
}, 60);
echo "Result (from cache): $data\n";

$cache->flush();
rmdir(__DIR__.'/cache');
