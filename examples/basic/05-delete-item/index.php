<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

Cache::set('temp_data', 'will be deleted', 60);
echo "Before delete - has 'temp_data': " . (Cache::has('temp_data') ? 'true' : 'false') . "\n";

Cache::delete('temp_data');
echo "After delete  - has 'temp_data': " . (Cache::has('temp_data') ? 'true' : 'false') . "\n";

// Deleting a non-existent key does not throw an error
Cache::delete('does_not_exist');
echo "Deleting non-existent key: no error.\n";

Cache::flush();
