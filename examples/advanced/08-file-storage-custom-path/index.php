<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$customPath = __DIR__ . '/my_cache';

// Create a FileStorage with a custom directory
$storage = new FileStorage($customPath);
Cache::setDriver($storage);

echo "Cache directory: " . $storage->getPath() . "\n";

Cache::set('example', 'stored in custom path', 60);
echo "Stored value: " . Cache::get('example') . "\n";

// Verify the file was created in the custom directory
$files = glob($customPath . '/*.cache');
echo "Cache files found: " . count($files) . "\n";

// Clean up
Cache::flush();
if (is_dir($customPath)) {
    rmdir($customPath);
}
echo "Cleaned up.\n";
