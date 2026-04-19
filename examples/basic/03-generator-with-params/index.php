<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Pass parameters to the generator callback
$result = Cache::get('user_profile', function (int $userId, bool $includeEmail) {
    echo "Generator called with userId=$userId, includeEmail=" . ($includeEmail ? 'true' : 'false') . "\n";
    $profile = ['id' => $userId, 'name' => 'John'];
    if ($includeEmail) {
        $profile['email'] = 'john@example.com';
    }
    return $profile;
}, 60, [7, true]);

echo "Result: " . print_r($result, true);

Cache::flush();
