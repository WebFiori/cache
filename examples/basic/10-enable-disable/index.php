<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

echo "Caching enabled: ".($cache->isEnabled() ? 'true' : 'false')."\n";

// Disable caching
$cache->setEnabled(false);
echo "Caching enabled: ".($cache->isEnabled() ? 'true' : 'false')."\n";

// Generator still returns data, but nothing is stored
$data = $cache->get('key', function () {
    return 'generated value';
}, 60);
echo "Generator returned: $data\n";
echo "Is it cached? ".($cache->has('key') ? 'true' : 'false')."\n";

// Re-enable caching
$cache->setEnabled(true);
echo "Caching enabled: ".($cache->isEnabled() ? 'true' : 'false')."\n";

// Now it gets stored
$data = $cache->get('key', function () {
    return 'generated value';
}, 60);
echo "Is it cached now? ".($cache->has('key') ? 'true' : 'false')."\n";

$cache->flush();
rmdir(__DIR__.'/cache');
