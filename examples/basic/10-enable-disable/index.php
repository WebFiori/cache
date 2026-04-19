<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

echo "Caching enabled: " . (Cache::isEnabled() ? 'true' : 'false') . "\n";

// Disable caching
Cache::setEnabled(false);
echo "Caching enabled: " . (Cache::isEnabled() ? 'true' : 'false') . "\n";

// Generator still returns data, but nothing is stored
$data = Cache::get('key', function () {
    return 'generated value';
}, 60);
echo "Generator returned: $data\n";
echo "Is it cached? " . (Cache::has('key') ? 'true' : 'false') . "\n";

// Re-enable caching
Cache::setEnabled(true);
echo "Caching enabled: " . (Cache::isEnabled() ? 'true' : 'false') . "\n";

// Now it gets stored
$data = Cache::get('key', function () {
    return 'generated value';
}, 60);
echo "Is it cached now? " . (Cache::has('key') ? 'true' : 'false') . "\n";

Cache::flush();
