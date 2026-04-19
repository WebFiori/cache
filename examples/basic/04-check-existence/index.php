<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

echo "Before set - has 'status': " . (Cache::has('status') ? 'true' : 'false') . "\n";

Cache::set('status', 'active', 60);

echo "After set  - has 'status': " . (Cache::has('status') ? 'true' : 'false') . "\n";
echo "Has 'other_key': " . (Cache::has('other_key') ? 'true' : 'false') . "\n";

Cache::flush();
