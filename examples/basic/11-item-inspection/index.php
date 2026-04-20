<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

$cache->set('report', ['total' => 150, 'active' => 98], 600);

$item = $cache->getItem('report');

echo "Key:        ".$item->getKey()."\n";
echo "TTL:        ".$item->getTTL()." seconds\n";
echo "Created at: ".date('Y-m-d H:i:s', $item->getCreatedAt())."\n";
echo "Expires at: ".date('Y-m-d H:i:s', $item->getExpiryTime())."\n";
echo "Data:       ".print_r($item->getDataDecrypted(), true);

// getItem returns null for missing keys
$missing = $cache->getItem('no_such_key');
echo "Missing item: ".var_export($missing, true)."\n";

$cache->flush();
rmdir(__DIR__.'/cache');
