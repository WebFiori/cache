<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

Cache::set('config', 'original', 60);
echo "Initial value: " . Cache::get('config') . "\n";

// Without override: set() returns false, value unchanged
$result = Cache::set('config', 'new_value', 60, false);
echo "set() without override returned: " . ($result ? 'true' : 'false') . "\n";
echo "Value after: " . Cache::get('config') . "\n";

// With override: set() returns true, value updated
$result = Cache::set('config', 'new_value', 60, true);
echo "set() with override returned: " . ($result ? 'true' : 'false') . "\n";
echo "Value after: " . Cache::get('config') . "\n";

Cache::flush();
