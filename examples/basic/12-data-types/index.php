<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$testCases = [
    'string'      => 'hello world',
    'integer'     => 42,
    'float'       => 3.14,
    'bool_true'   => true,
    'null_value'  => null,
    'array'       => [1, 'two', 3.0],
    'assoc_array' => ['name' => 'Alice', 'age' => 30],
    'object'      => (object)['color' => 'blue', 'size' => 10],
    'nested'      => ['level1' => ['level2' => ['deep' => 'value']]],
];

echo "Storing and retrieving different data types:\n\n";

foreach ($testCases as $label => $value) {
    Cache::set($label, $value, 60);
    $retrieved = Cache::get($label);
    $match = ($retrieved == $value) ? 'PASS' : 'FAIL';
    echo "  [$match] $label: " . var_export($retrieved, true) . "\n";
}

// Edge case: boolean false and null are indistinguishable from a cache miss
// via Cache::get() since it returns null for misses. Use Cache::has() to
// distinguish between a cached null/false and a miss.
echo "\nEdge case - boolean false:\n";
Cache::set('bool_false', false, 60);
$retrieved = Cache::get('bool_false');
echo "  Cache::get() returns: " . var_export($retrieved, true) . "\n";
echo "  Cache::has() returns: " . (Cache::has('bool_false') ? 'true' : 'false') . "\n";
echo "  Use Cache::has() to confirm the item exists.\n";

Cache::flush();
