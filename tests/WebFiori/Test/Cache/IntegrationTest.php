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
    
    protected function setUp(): void {
        // Set up a test encryption key for consistent testing
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $testKey;
        KeyManager::clearCache();
        
        // Clean up any existing cache
        Cache::flush();
    }
    
    protected function tearDown(): void {
        // Clean up after each test
        KeyManager::clearCache();
        Cache::flush();
        
        // Clean up environment variables
        unset($_ENV['CACHE_ENCRYPTION_KEY']);
    }
    
    /**
     * @test
     */
    public function testCacheWithCustomStorageDriver() {
        $customStorage = new MockStorage();
        Cache::setDriver($customStorage);
        
        $key = 'custom_storage_test';
        $data = 'custom_storage_data';
        
        Cache::set($key, $data, 300);
        $this->assertEquals($data, Cache::get($key));
        $this->assertTrue(Cache::has($key));
        
        Cache::delete($key);
        $this->assertFalse(Cache::has($key));
    }
    
    /**
     * @test
     */
    public function testCacheWithMultiplePrefixes() {
        $prefixes = ['user_', 'session_', 'api_', 'temp_'];
        $keys = ['data1', 'data2', 'data3'];
        
        // Store data with different prefixes
        foreach ($prefixes as $prefix) {
            Cache::withPrefix($prefix);
            foreach ($keys as $i => $key) {
                Cache::set($key, "data_{$prefix}_{$i}", 300);
            }
        }
        
        // Verify data isolation
        foreach ($prefixes as $prefix) {
            Cache::withPrefix($prefix);
            foreach ($keys as $i => $key) {
                $this->assertEquals("data_{$prefix}_{$i}", Cache::get($key));
            }
        }
        
        // Flush one prefix and verify others remain
        Cache::withPrefix('user_')->flush();
        
        foreach ($keys as $key) {
            $this->assertFalse(Cache::withPrefix('user_')::has($key));
            $this->assertTrue(Cache::withPrefix('session_')::has($key));
            $this->assertTrue(Cache::withPrefix('api_')::has($key));
            $this->assertTrue(Cache::withPrefix('temp_')::has($key));
        }
    }
    
    /**
     * @test
     */
    public function testCachePerformanceWithManyItems() {
        $itemCount = 1000;
        $startTime = microtime(true);
        
        // Store many items
        for ($i = 0; $i < $itemCount; $i++) {
            Cache::set("perf_test_{$i}", "data_{$i}", 300);
        }
        
        $storeTime = microtime(true) - $startTime;
        
        // Retrieve many items
        $startTime = microtime(true);
        for ($i = 0; $i < $itemCount; $i++) {
            $data = Cache::get("perf_test_{$i}");
            $this->assertEquals("data_{$i}", $data);
        }
        
        $retrieveTime = microtime(true) - $startTime;
        
        // Performance should be reasonable (less than 5 seconds for 1000 items)
        $this->assertLessThan(5.0, $storeTime);
        $this->assertLessThan(5.0, $retrieveTime);
        
        // Clean up
        Cache::flush();
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
        
        $key = 'complex_nested_test';
        Cache::set($key, $complexData, 300);
        
        $retrieved = Cache::get($key);
        $this->assertEquals($complexData, $retrieved);
        
        // Test specific nested access
        $this->assertEquals('deep string', $retrieved['level1']['level2']['level3']['string']);
        $this->assertEquals('very deep', $retrieved['level1']['level2']['level3']['object']->nested->deep);
        $this->assertEquals('final value', $retrieved['parallel']['more_nesting']['even']['deeper']['nesting']);
    }
    
    /**
     * @test
     */
    public function testCacheWithCallbackExceptions() {
        $key = 'exception_test';
        
        try {
            Cache::get($key, function() {
                throw new \RuntimeException('Generator failed');
            }, 300);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Generator failed', $e->getMessage());
        }
        
        // Cache should not contain the failed item
        $this->assertFalse(Cache::has($key));
        $this->assertNull(Cache::get($key));
    }
    
    /**
     * @test
     */
    public function testCacheWithRecursiveData() {
        // Create recursive data structure
        $data = ['name' => 'root'];
        $data['self'] = &$data; // Circular reference
        
        $key = 'recursive_test';
        
        // This should handle recursive data gracefully
        Cache::set($key, $data, 300);
        $retrieved = Cache::get($key);
        
        $this->assertEquals('root', $retrieved['name']);
        // Note: Circular references are handled by PHP's serialization
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
            // Removed closure as it cannot be serialized
        ];
        
        foreach ($testCases as $type => $value) {
            $key = "type_test_{$type}";
            Cache::set($key, $value, 300);
            
            $retrieved = Cache::get($key);
            $this->assertEquals($value, $retrieved, "Failed for type: {$type}");
        }
    }
    
    /**
     * @test
     */
    public function testCacheWithVeryLongKeys() {
        // Test with maximum allowed key length (250 characters)
        $maxKey = str_repeat('a', 250);
        Cache::set($maxKey, 'max_key_data', 300);
        $this->assertEquals('max_key_data', Cache::get($maxKey));
        
        // Test with key containing various characters
        $specialKey = str_repeat('ñ', 83); // 83 * 3 bytes = 249 bytes in UTF-8
        Cache::set($specialKey, 'special_key_data', 300);
        $this->assertEquals('special_key_data', Cache::get($specialKey));
    }
    
    /**
     * @test
     */
    public function testCacheStateConsistency() {
        // Test that cache state remains consistent across operations
        $keys = ['key1', 'key2', 'key3'];
        
        // Initial state - no items
        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key));
        }
        
        // Add items
        foreach ($keys as $i => $key) {
            Cache::set($key, "data_{$i}", 300);
            $this->assertTrue(Cache::has($key));
        }
        
        // Verify all items exist
        foreach ($keys as $key) {
            $this->assertTrue(Cache::has($key));
        }
        
        // Delete middle item
        Cache::delete($keys[1]);
        $this->assertTrue(Cache::has($keys[0]));
        $this->assertFalse(Cache::has($keys[1]));
        $this->assertTrue(Cache::has($keys[2]));
        
        // Flush all
        Cache::flush();
        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key));
        }
    }
    
    /**
     * @test
     */
    public function testCacheWithRapidOperations() {
        $key = 'rapid_ops_test';
        
        // Rapid set/get operations
        for ($i = 0; $i < 100; $i++) {
            Cache::set($key, "data_{$i}", 300, true);
            $this->assertEquals("data_{$i}", Cache::get($key));
        }
        
        // Final value should be the last one set
        $this->assertEquals('data_99', Cache::get($key));
    }
    
    /**
     * @test
     */
    public function testCacheWithMemoryLimits() {
        // Test with data that approaches memory limits
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray[] = str_repeat('x', 100); // 100 chars * 10000 = ~1MB
        }
        
        $key = 'memory_test';
        Cache::set($key, $largeArray, 300);
        
        $retrieved = Cache::get($key);
        $this->assertEquals(count($largeArray), count($retrieved));
        $this->assertEquals($largeArray[0], $retrieved[0]);
        $this->assertEquals($largeArray[9999], $retrieved[9999]);
    }
    
    /**
     * @test
     */
    public function testCacheErrorRecovery() {
        // Test cache behavior when storage fails
        $mockStorage = new FailingMockStorage();
        $originalDriver = Cache::getDriver();
        
        try {
            Cache::setDriver($mockStorage);
            
            // Operations should handle failures gracefully
            // When storage fails, the generator should still run and return data
            // but the data won't be cached
            $result = Cache::get('test_key', function() {
                return 'generated_data';
            }, 300);
            
            // Should return generated data even if storage fails
            $this->assertEquals('generated_data', $result);
            
            // Verify it's not cached (since storage failed)
            $this->assertFalse(Cache::has('test_key'));
            
        } finally {
            // Restore original driver
            Cache::setDriver($originalDriver);
        }
    }
}

/**
 * Mock storage implementation for testing
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
        $fullKey = $key;
        unset($this->data[$fullKey]);
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
}

/**
 * Mock storage that fails operations for testing error handling
 */
class FailingMockStorage implements Storage {
    public function store(Item $item) {
        throw new CacheStorageException('Mock storage failure');
    }
    
    public function read(string $key, ?string $prefix) {
        return null; // Always return null (cache miss)
    }
    
    public function readItem(string $key, ?string $prefix): ?Item {
        return null;
    }
    
    public function has(string $key, ?string $prefix): bool {
        return false;
    }
    
    public function delete(string $key) {
        // Do nothing
    }
    
    public function flush(?string $prefix) {
        // Do nothing
    }
}
