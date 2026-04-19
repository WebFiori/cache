<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));
$app1 = $cache->withPrefix('app1_');
$app2 = $cache->withPrefix('app2_');

$app1->set('data', 'App 1 data', 60);
$app1->set('config', 'App 1 config', 60);
$app2->set('data', 'App 2 data', 60);
$app2->set('config', 'App 2 config', 60);

echo "Before flush:\n";
echo "  app1_data:   ".$app1->get('data')."\n";
echo "  app1_config: ".$app1->get('config')."\n";
echo "  app2_data:   ".$app2->get('data')."\n";
echo "  app2_config: ".$app2->get('config')."\n";

// Flush only app1 items
$app1->flush();

echo "\nAfter flushing 'app1_':\n";
echo "  app1_data:   ".var_export($app1->get('data'), true)."\n";
echo "  app1_config: ".var_export($app1->get('config'), true)."\n";
echo "  app2_data:   ".$app2->get('data')."\n";
echo "  app2_config: ".$app2->get('config')."\n";

$app2->flush();
rmdir(__DIR__.'/cache');
