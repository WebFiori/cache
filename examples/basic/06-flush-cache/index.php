<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Store several items
Cache::set('item_a', 'A', 60);
Cache::set('item_b', 'B', 60);
Cache::set('item_c', 'C', 60);

echo "Before flush:\n";
echo "  has 'item_a': " . (Cache::has('item_a') ? 'true' : 'false') . "\n";
echo "  has 'item_b': " . (Cache::has('item_b') ? 'true' : 'false') . "\n";
echo "  has 'item_c': " . (Cache::has('item_c') ? 'true' : 'false') . "\n";

Cache::flush();

echo "After flush:\n";
echo "  has 'item_a': " . (Cache::has('item_a') ? 'true' : 'false') . "\n";
echo "  has 'item_b': " . (Cache::has('item_b') ? 'true' : 'false') . "\n";
echo "  has 'item_c': " . (Cache::has('item_c') ? 'true' : 'false') . "\n";
