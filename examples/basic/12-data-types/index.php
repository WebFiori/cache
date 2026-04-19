<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

$testCases = [
    'string' => 'hello world',
    'integer' => 42,
    'float' => 3.14,
    'bool_true' => true,
    'null_value' => null,
    'array' => [1, 'two', 3.0],
    'assoc_array' => ['name' => 'Alice', 'age' => 30],
    'object' => (object)['color' => 'blue', 'size' => 10],
    'nested' => ['level1' => ['level2' => ['deep' => 'value']]],
];

echo "Storing and retrieving different data types:\n\n";

foreach ($testCases as $label => $value) {
    $cache->set($label, $value, 60);
    $retrieved = $cache->get($label);
    $match = ($retrieved == $value) ? 'PASS' : 'FAIL';
    echo "  [$match] $label: ".var_export($retrieved, true)."\n";
}

// Edge case: boolean false and null are now correctly cached and retrieved.
// Use has() to distinguish between a cached null and a cache miss.
echo "\nEdge case - boolean false:\n";
$cache->set('bool_false', false, 60);
$retrieved = $cache->get('bool_false');
echo "  get() returns: ".var_export($retrieved, true)."\n";
echo "  has() returns: ".($cache->has('bool_false') ? 'true' : 'false')."\n";

echo "\nEdge case - null:\n";
$cache->set('null_val', null, 60);
$retrieved = $cache->get('null_val');
echo "  get() returns: ".var_export($retrieved, true)."\n";
echo "  has() returns: ".($cache->has('null_val') ? 'true' : 'false')."\n";

$cache->flush();
rmdir(__DIR__.'/cache');
