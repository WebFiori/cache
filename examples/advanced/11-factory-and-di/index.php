<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cacheDir = __DIR__ . '/factory_cache';

// Create a standalone cache instance via factory method
$storage = new FileStorage($cacheDir);
$cache = Cache::create($storage, true, 'myapp_');

echo "Instance type: " . get_class($cache) . "\n";

// The factory creates an independent instance.
// For cache operations, use the static API with setDriver():
Cache::setDriver($storage);
Cache::withPrefix('myapp_');

Cache::set('setting', 'dark_mode', 60);
echo "Stored 'setting': " . Cache::get('setting') . "\n";
echo "Prefix: " . Cache::getPrefix() . "\n";

// Clean up
Cache::flush();
if (is_dir($cacheDir)) {
    rmdir($cacheDir);
}
echo "Cleaned up.\n";
