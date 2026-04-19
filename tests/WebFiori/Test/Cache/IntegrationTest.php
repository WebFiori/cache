<?php
namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\SecurityConfig;
use WebFiori\Cache\Storage;
use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\CacheStorageException;

/**
 * Integration and edge case tests for the cache system.
 */
class IntegrationTest extends TestCase {

    private Cache $cache;

    protected function setUp(): void {
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $testKey;
        KeyManager::clearCache();

        $this->cache = new Cache(new FileStorage(__DIR__ . '/test_integration_cache'));
        $this->cache->flush();
    }

    protected function tearDown(): void {
        KeyManager::clearCache();
        $this->cache->flush();
        unset($_ENV['CACHE_ENCRYPTION_KEY']);
    }

    /**
     * @test
     */
    public function testCacheWithCustomStorageDriver() {
        $cache = new Cache(new MockStorage());

        $cache->set('custom_storage_test', 'custom_storage_data', 300);
        $this->assertEquals('custom_storage_data', $cache->get('custom_storage_test'));
        $this->assertTrue($cache->has('custom_storage_test'));

        $cache->delete('custom_storage_test');
        $this->assertFalse($cache->has('custom_storage_test'));
    }

    /**
     * @test
     */
    public function testCacheWithMultiplePrefixes() {
        $prefixes = ['user_', 'session_', 'api_', 'temp_'];
        $keys = ['data1', 'data2', 'data3'];

        $caches = [];
        foreach ($prefixes as $prefix) {
            $caches[$prefix] = $this->cache->withPrefix($prefix);
            foreach ($keys as $i => $key) {
                $caches[$prefix]->set($key, "data_{$prefix}_{$i}", 300);
            }
        }

        foreach ($prefixes as $prefix) {
            foreach ($keys as $i => $key) {
                $this->assertEquals("data_{$prefix}_{$i}", $caches[$prefix]->get($key));
            }
        }

        $caches['user_']->flush();

        foreach ($keys as $key) {
            $this->assertFalse($caches['user_']->has($key));
            $this->assertTrue($caches['session_']->has($key));
            $this->assertTrue($caches['api_']->has($key));
            $this->assertTrue($caches['temp_']->has($key));
        }
    }

    /**
     * @test
     */
    public function testCachePerformanceWithManyItems() {
        $itemCount = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $itemCount; $i++) {
            $this->cache->set("perf_test_{$i}", "data_{$i}", 300);
        }
        $storeTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        for ($i = 0; $i < $itemCount; $i++) {
            $this->assertEquals("data_{$i}", $this->cache->get("perf_test_{$i}"));
        }
        $retrieveTime = microtime(true) - $startTime;

        $this->assertLessThan(5.0, $storeTime);
        $this->assertLessThan(5.0, $retrieveTime);
    }

    /**
     * @test
     */
    public function testCacheWithComplexNestedData() {
        $complexData = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'string' => 'deep string',
                        'number' => 42,
                        'array' => [1, 2, 3, 4, 5],
                        'object' => (object)[
                            'property' => 'value',
                            'nested' => (object)['deep' => 'very deep']
                        ]
                    ]
                ]
            ],
            'parallel' => [
                'data' => 'parallel data',
                'more_nesting' => [
                    'even' => ['deeper' => ['nesting' => 'final value']]
                ]
            ]
        ];

        $this->cache->set('complex_nested_test', $complexData, 300);
        $retrieved = $this->cache->get('complex_nested_test');
        $this->assertEquals($complexData, $retrieved);
        $this->assertEquals('deep string', $retrieved['level1']['level2']['level3']['string']);
        $this->assertEquals('very deep', $retrieved['level1']['level2']['level3']['object']->nested->deep);
        $this->assertEquals('final value', $retrieved['parallel']['more_nesting']['even']['deeper']['nesting']);
    }

    /**
     * @test
     */
    public function testCacheWithCallbackExceptions() {
        try {
            $this->cache->get('exception_test', function () {
                throw new \RuntimeException('Generator failed');
            }, 300);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Generator failed', $e->getMessage());
        }

        $this->assertFalse($this->cache->has('exception_test'));
    }

    /**
     * @test
     */
    public function testCacheWithRecursiveData() {
        $data = ['name' => 'root'];
        $data['self'] = &$data;

        $this->cache->set('recursive_test', $data, 300);
        $retrieved = $this->cache->get('recursive_test');
        $this->assertEquals('root', $retrieved['name']);
    }

    /**
     * @test
     */
    public function testCacheWithDifferentDataTypes() {
        $testCases = [
            'string' => 'test string',
            'integer' => 12345,
            'float' => 123.456,
            'boolean_true' => true,
            'boolean_false' => false,
            'null' => null,
            'empty_string' => '',
            'zero' => 0,
            'empty_array' => [],
            'indexed_array' => [1, 2, 3],
            'associative_array' => ['key' => 'value'],
            'object' => (object)['prop' => 'value']
        ];

        foreach ($testCases as $type => $value) {
            $key = "type_test_{$type}";
            $this->cache->set($key, $value, 300);
            $this->assertTrue($this->cache->has($key), "has() failed for type: {$type}");
            $retrieved = $this->cache->get($key);
            $this->assertEquals($value, $retrieved, "Failed for type: {$type}");
        }
    }

    /**
     * @test
     */
    public function testCacheWithVeryLongKeys() {
        $maxKey = str_repeat('a', 250);
        $this->cache->set($maxKey, 'max_key_data', 300);
        $this->assertEquals('max_key_data', $this->cache->get($maxKey));

        $specialKey = str_repeat('ñ', 83);
        $this->cache->set($specialKey, 'special_key_data', 300);
        $this->assertEquals('special_key_data', $this->cache->get($specialKey));
    }

    /**
     * @test
     */
    public function testCacheStateConsistency() {
        $keys = ['key1', 'key2', 'key3'];

        foreach ($keys as $key) {
            $this->assertFalse($this->cache->has($key));
        }

        foreach ($keys as $i => $key) {
            $this->cache->set($key, "data_{$i}", 300);
            $this->assertTrue($this->cache->has($key));
        }

        $this->cache->delete($keys[1]);
        $this->assertTrue($this->cache->has($keys[0]));
        $this->assertFalse($this->cache->has($keys[1]));
        $this->assertTrue($this->cache->has($keys[2]));

        $this->cache->flush();
        foreach ($keys as $key) {
            $this->assertFalse($this->cache->has($key));
        }
    }

    /**
     * @test
     */
    public function testCacheWithRapidOperations() {
        $key = 'rapid_ops_test';
        for ($i = 0; $i < 100; $i++) {
            $this->cache->set($key, "data_{$i}", 300, true);
            $this->assertEquals("data_{$i}", $this->cache->get($key));
        }
        $this->assertEquals('data_99', $this->cache->get($key));
    }

    /**
     * @test
     */
    public function testCacheWithMemoryLimits() {
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray[] = str_repeat('x', 100);
        }

        $this->cache->set('memory_test', $largeArray, 300);
        $retrieved = $this->cache->get('memory_test');
        $this->assertEquals(count($largeArray), count($retrieved));
        $this->assertEquals($largeArray[0], $retrieved[0]);
        $this->assertEquals($largeArray[9999], $retrieved[9999]);
    }

    /**
     * @test
     */
    public function testCacheErrorRecovery() {
        $cache = new Cache(new FailingMockStorage());

        $result = $cache->get('test_key', function () {
            return 'generated_data';
        }, 300);

        $this->assertEquals('generated_data', $result);
        $this->assertFalse($cache->has('test_key'));
    }

    /**
     * @test
     */
    public function testPurgeExpired() {
        $this->cache->set('expire1', 'data1', 1);
        $this->cache->set('expire2', 'data2', 1);
        $this->cache->set('keep1', 'data3', 600);

        sleep(2);

        $removed = $this->cache->purgeExpired();
        $this->assertEquals(2, $removed);
        $this->assertFalse($this->cache->has('expire1'));
        $this->assertFalse($this->cache->has('expire2'));
        $this->assertTrue($this->cache->has('keep1'));
    }
}

/**
 * Mock storage implementation for testing.
 */
class MockStorage implements Storage {
    private array $data = [];

    public function store(Item $item) {
        $key = $item->getPrefix() . $item->getKey();
        $this->data[$key] = [
            'data' => $item->getDataEncrypted(),
            'expires' => $item->getExpiryTime(),
            'ttl' => $item->getTTL(),
            'created_at' => $item->getCreatedAt(),
            'key' => $item->getKey(),
            'prefix' => $item->getPrefix()
        ];
    }

    public function read(string $key, ?string $prefix) {
        $item = $this->readItem($key, $prefix);
        return $item ? $item->getDataDecrypted() : null;
    }

    public function readItem(string $key, ?string $prefix): ?Item {
        $fullKey = $prefix . $key;
        if (!isset($this->data[$fullKey])) {
            return null;
        }
        $data = $this->data[$fullKey];
        if (time() > $data['expires']) {
            unset($this->data[$fullKey]);
            return null;
        }
        $item = new Item($key, $data['data'], $data['ttl'], KeyManager::getEncryptionKey());
        $item->setCreatedAt($data['created_at']);
        $item->setPrefix($prefix ?? '');
        $item->setDataIsEncrypted(true);
        return $item;
    }

    public function has(string $key, ?string $prefix): bool {
        return $this->readItem($key, $prefix) !== null;
    }

    public function delete(string $key) {
        unset($this->data[$key]);
    }

    public function flush(?string $prefix) {
        if ($prefix === null) {
            $this->data = [];
        } else {
            foreach (array_keys($this->data) as $key) {
                if (strpos($key, $prefix) === 0) {
                    unset($this->data[$key]);
                }
            }
        }
    }

    public function purgeExpired(): int {
        $removed = 0;
        $now = time();
        foreach ($this->data as $key => $entry) {
            if ($now > $entry['expires']) {
                unset($this->data[$key]);
                $removed++;
            }
        }
        return $removed;
    }
}

/**
 * Mock storage that fails operations for testing error handling.
 */
class FailingMockStorage implements Storage {
    public function store(Item $item) {
        throw new CacheStorageException('Mock storage failure');
    }

    public function read(string $key, ?string $prefix) {
        return null;
    }

    public function readItem(string $key, ?string $prefix): ?Item {
        return null;
    }

    public function has(string $key, ?string $prefix): bool {
        return false;
    }

    public function delete(string $key) {
    }

    public function flush(?string $prefix) {
    }

    public function purgeExpired(): int {
        return 0;
    }
}
