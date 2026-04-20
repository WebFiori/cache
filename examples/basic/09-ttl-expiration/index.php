<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

// Store with a 2-second TTL
$cache->set('short_lived', 'I expire quickly', 2);
echo "Immediately after set: ".$cache->get('short_lived')."\n";

echo "Waiting 3 seconds for expiration...\n";
sleep(3);

$data = $cache->get('short_lived');
echo "After expiration: ".var_export($data, true)."\n";

$cache->flush();
rmdir(__DIR__.'/cache');
