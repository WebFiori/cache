<?php
namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

/**
 * Updated test class with security enhancements.
 */
class CacheTest extends TestCase {
    
    protected function setUp(): void {
        // Set up a test encryption key for consistent testing
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $testKey;
        KeyManager::clearCache(); // Force reload from environment
        
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
    public function test00() {
        $key = 'first';
        $this->assertTrue(Cache::isEnabled());
        $data = Cache::get($key, function () {
            return 'This is a test.';
        });
        $this->assertEquals('This is a test.', $data);
        $this->assertEquals('This is a test.', Cache::get($key));
        $this->assertNull(Cache::get('not_cached'));
    }
    
    /**
     * @test
     */
    public function test01() {
        $key = 'test_2';
        $this->assertFalse(Cache::has($key));
        $data = Cache::get($key, function () {
            return 'This is a test.';
        }, 5);
        $this->assertEquals('This is a test.', $data);
        $this->assertTrue(Cache::has($key));
        sleep(6);
        $this->assertFalse(Cache::has($key));
        $this->assertNull(Cache::get($key));
    }
    
    /**
     * @test
     */
    public function test03() {
        $key = 'ok_test';
        $this->assertFalse(Cache::has($key));
        $data = Cache::get($key, function () {
            return 'This is a test.';
        }, 600);
        $this->assertEquals('This is a test.', $data);
        $this->assertTrue(Cache::has($key));
        Cache::delete($key);
        $this->assertFalse(Cache::has($key));
        $this->assertNull(Cache::get($key));
    }
    
    /**
     * @test
     */
    public function test04() {
        $key = 'test_3';
        $this->assertFalse(Cache::has($key));
        $data = Cache::get($key, function () {
            return 'This is a test.';
        }, 600);
        $this->assertEquals('This is a test.', $data);
        $item = Cache::getItem($key);
        $this->assertNotNull($item);
        $this->assertEquals(600, $item->getTTL());
        Cache::setTTL($key, 1000);
        $item = Cache::getItem($key);
        $this->assertEquals(1000, $item->getTTL());
        Cache::delete($key);
        $this->assertNull(Cache::getItem($key));
    }
    
    /**
     * @test
     */
    public function test05() {
        $keys = [];
        for ($x = 0 ; $x < 10 ; $x++) {
            $key = 'item_'.$x;
            Cache::get($key, function () {
                return 'This is a test.';
            }, 600);
            $keys[] = $key;
        }
        foreach ($keys as $key) {
            $this->assertTrue(Cache::has($key));
        }
        Cache::flush(null);
        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key));
        }
    }
    
    /**
     * @test
     */
    public function test06() {
        $key = 'bbuu';
        $this->assertTrue(Cache::isEnabled());
        Cache::setEnabled(false);
        $data = Cache::get($key, function () {
            return 'This is a test.';
        });
        $this->assertEquals('This is a test.', $data);
        $this->assertNull(Cache::get($key));
        $this->assertFalse(Cache::isEnabled());
    }
    
    /**
     * @test
     */
    public function testSet00() {
        $key = 'new_cool_key';
        Cache::setEnabled(true);
        $this->assertTrue(Cache::isEnabled());
        $this->assertTrue(Cache::set($key, 'This is a test.', 60, false));
        $this->assertEquals('This is a test.', Cache::get($key));
        $item = Cache::getItem($key);
        $this->assertEquals(60, $item->getTTL());
        
        $this->assertFalse(Cache::set($key, 'This is a test.', 60, false));
        $this->assertEquals('This is a test.', Cache::get($key));
        $item = Cache::getItem($key);
        $this->assertEquals(60, $item->getTTL());
        
        $this->assertTrue(Cache::set($key, 'This is a test 2.', 660, true));
        $this->assertEquals('This is a test 2.', Cache::get($key));
        $item = Cache::getItem($key);
        $this->assertEquals(660, $item->getTTL());
    }
    
    /**
     * @test
     */
    public function testSetTTL00() {
        $key = 'new_cool_key2';
        Cache::setEnabled(true);
        $this->assertTrue(Cache::isEnabled());
        $this->assertTrue(Cache::set($key, 'This is a test.', 60, false));
        $item = Cache::getItem($key);
        $this->assertEquals(60, $item->getTTL());
        Cache::setTTL($key, 700);
        
        $item = Cache::getItem($key);
        $this->assertEquals(700, $item->getTTL());
    }
    
    /**
     * @test
     */
    public function testSetTTL01() {
        $key = 'not exist cool';
        $this->assertFalse(Cache::setTTL($key, 700));
    }
    
    /**
     * @test
     */
    public function testWithPrefix00() {
        $key = 'first';
        $this->assertTrue(Cache::isEnabled());
        $data =Cache::withPrefix('ok')->get($key, function () {
            return 'This is a test.';
        });
        $this->assertEquals('This is a test.', $data);
        $this->assertEquals('This is a test.', Cache::get($key));
        $this->assertNull(Cache::withPrefix('')->get($key));
        Cache::delete($key);
        $this->assertEquals('This is a test.', Cache::withPrefix('ok')->get($key));
    }
    
    /**
     * @test
     */
    public function testWithPrefix01() {
        $key = 'test-';
        $key2 = 'ttest-2-';
        $prefix1 = 'good';
        $prefix2 = 'bad';
        
        for ($x = 0 ; $x < 3 ; $x++) {
            Cache::withPrefix($prefix1)->get($key.$x, function (int $x, $pfx) {
                return 'This is a test. '.$x.$pfx;
            }, 60, [$x, $prefix1]);
        }
        for ($x = 0 ; $x < 3 ; $x++) {
            Cache::withPrefix($prefix2)->get($key2.$x, function (int $x, $pfx) {
                return 'This is a test. '.$x.$pfx;
            }, 60, [$x, $prefix2]);
        }
        Cache::withPrefix($prefix1);
        for ($x = 0 ; $x < 3 ; $x++) {
            $this->assertTrue(Cache::has($key.$x));
        }
        Cache::withPrefix($prefix2);
        for ($x = 0 ; $x < 3 ; $x++) {
            $this->assertTrue(Cache::has($key2.$x));
        }
        Cache::withPrefix($prefix1)->flush();
        for ($x = 0 ; $x < 3 ; $x++) {
            $this->assertFalse(Cache::has($key.$x));
        }
        Cache::withPrefix($prefix2);
        for ($x = 0 ; $x < 3 ; $x++) {
            $this->assertTrue(Cache::has($key2.$x));
        }
    }
    
    /**
     * @test
     */
    public function testEncryptionIntegration() {
        $sensitiveData = 'This is sensitive information';
        $key = 'sensitive_key';
        
        Cache::set($key, $sensitiveData, 60);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($sensitiveData, $retrieved);
        
        // Verify data is actually encrypted in storage
        $item = Cache::getItem($key);
        $this->assertNotNull($item);
        
        // The encrypted data should be different from the original
        $encryptedData = $item->getDataEncrypted();
        $this->assertNotEquals(serialize($sensitiveData), $encryptedData);
    }
    
    /**
     * @test
     */
    public function testCacheWithComplexData() {
        $complexData = [
            'string' => 'test string',
            'number' => 12345,
            'float' => 123.45,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3, 'nested' => ['deep' => 'value']],
            'object' => (object)['property' => 'value', 'nested' => (object)['deep' => 'nested_value']]
        ];
        
        $key = 'complex_data_test';
        Cache::set($key, $complexData, 300);
        
        $retrieved = Cache::get($key);
        $this->assertEquals($complexData, $retrieved);
        
        // Test with generator function
        $generatedData = Cache::get('generated_complex', function() use ($complexData) {
            return $complexData;
        }, 300);
        
        $this->assertEquals($complexData, $generatedData);
    }
    
    /**
     * @test
     */
    public function testCacheWithZeroTTL() {
        $key = 'zero_ttl_test';
        $data = 'This should not be cached';
        
        // TTL of 0 should not cache the item
        $result = Cache::set($key, $data, 0);
        $this->assertFalse(Cache::has($key));
        $this->assertNull(Cache::get($key));
    }
    
    /**
     * @test
     */
    public function testCacheWithNegativeTTL() {
        $key = 'negative_ttl_test';
        $data = 'This should not be cached';
        
        // Negative TTL should result in the item not being stored
        // The FileStorage checks if TTL > 0 before storing
        $result = Cache::set($key, $data, -100);
        
        // The set operation might succeed but the item won't be stored due to negative TTL
        $this->assertFalse(Cache::has($key));
        $this->assertNull(Cache::get($key));
    }
    
    /**
     * @test
     */
    public function testCacheGetWithParameters() {
        $key = 'parameterized_test';
        
        $result = Cache::get($key, function($param1, $param2, $param3) {
            return "Generated with: {$param1}, {$param2}, {$param3}";
        }, 300, ['value1', 'value2', 'value3']);
        
        $expected = "Generated with: value1, value2, value3";
        $this->assertEquals($expected, $result);
        
        // Second call should return cached value
        $cached = Cache::get($key);
        $this->assertEquals($expected, $cached);
    }
    
    /**
     * @test
     */
    public function testCacheGetWithEmptyParameters() {
        $key = 'empty_params_test';
        
        $result = Cache::get($key, function() {
            return 'Generated without parameters';
        }, 300, []);
        
        $this->assertEquals('Generated without parameters', $result);
    }
    
    /**
     * @test
     */
    public function testCacheGetWithNullGenerator() {
        $key = 'null_generator_test';
        
        // Should return null when no generator provided and key doesn't exist
        $result = Cache::get($key, null);
        $this->assertNull($result);
        
        // Set a value first
        Cache::set($key, 'test_value', 300);
        
        // Should return cached value even with null generator
        $result = Cache::get($key, null);
        $this->assertEquals('test_value', $result);
    }
    
    /**
     * @test
     */
    public function testCacheDriverManagement() {
        $originalDriver = Cache::getDriver();
        $this->assertInstanceOf(\WebFiori\Cache\Storage::class, $originalDriver);
        
        // Create a new file storage driver
        $newDriver = new \WebFiori\Cache\FileStorage(__DIR__ . '/test_cache_driver');
        Cache::setDriver($newDriver);
        
        $this->assertSame($newDriver, Cache::getDriver());
        
        // Test that cache operations work with new driver
        Cache::set('driver_test', 'test_data', 300);
        $this->assertTrue(Cache::has('driver_test'));
        $this->assertEquals('test_data', Cache::get('driver_test'));
        
        // Clean up
        Cache::flush();
        if (is_dir(__DIR__ . '/test_cache_driver')) {
            $files = glob(__DIR__ . '/test_cache_driver/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir(__DIR__ . '/test_cache_driver');
        }
        
        // Restore original driver
        Cache::setDriver($originalDriver);
    }
    
    /**
     * @test
     */
    public function testCacheItemDetails() {
        $key = 'item_details_test';
        $data = 'test data for item details';
        $ttl = 600;
        
        Cache::set($key, $data, $ttl);
        $item = Cache::getItem($key);
        
        $this->assertNotNull($item);
        $this->assertEquals($key, $item->getKey());
        $this->assertEquals($data, $item->getDataDecrypted()); // Use decrypted data
        $this->assertEquals($ttl, $item->getTTL());
        $this->assertGreaterThan(0, $item->getCreatedAt());
        $this->assertGreaterThan(time(), $item->getExpiryTime());
        $this->assertEquals($item->getCreatedAt() + $ttl, $item->getExpiryTime());
    }
    
    /**
     * @test
     */
    public function testCacheFlushWithPrefix() {
        // Set up items with different prefixes
        Cache::withPrefix('prefix1_')->set('item1', 'data1', 300);
        Cache::withPrefix('prefix1_')->set('item2', 'data2', 300);
        Cache::withPrefix('prefix2_')->set('item1', 'data3', 300);
        Cache::withPrefix('prefix2_')->set('item2', 'data4', 300);
        
        // Verify all items exist
        $this->assertTrue(Cache::withPrefix('prefix1_')::has('item1'));
        $this->assertTrue(Cache::withPrefix('prefix1_')::has('item2'));
        $this->assertTrue(Cache::withPrefix('prefix2_')::has('item1'));
        $this->assertTrue(Cache::withPrefix('prefix2_')::has('item2'));
        
        // Flush only prefix1 items
        Cache::withPrefix('prefix1_')::flush();
        
        // Verify prefix1 items are gone but prefix2 items remain
        $this->assertFalse(Cache::withPrefix('prefix1_')::has('item1'));
        $this->assertFalse(Cache::withPrefix('prefix1_')::has('item2'));
        $this->assertTrue(Cache::withPrefix('prefix2_')::has('item1'));
        $this->assertTrue(Cache::withPrefix('prefix2_')::has('item2'));
        
        // Clean up remaining items
        Cache::withPrefix('prefix2_')::flush();
    }
    
    /**
     * @test
     */
    public function testCacheWithLargeData() {
        $key = 'large_data_test';
        
        // Create a large string (1MB)
        $largeData = str_repeat('A', 1024 * 1024);
        
        Cache::set($key, $largeData, 300);
        $this->assertTrue(Cache::has($key));
        
        $retrieved = Cache::get($key);
        $this->assertEquals($largeData, $retrieved);
        $this->assertEquals(1024 * 1024, strlen($retrieved));
    }
    
    /**
     * @test
     */
    public function testCacheWithSpecialCharacters() {
        $key = 'special_chars_test';
        $data = "Special chars: àáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ 中文 العربية русский 🚀🎉💯";
        
        Cache::set($key, $data, 300);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($data, $retrieved);
    }
    
    /**
     * @test
     */
    public function testCacheCreateMethod() {
        $storage = new \WebFiori\Cache\FileStorage(__DIR__ . '/test_create_cache');
        $cache = \WebFiori\Cache\Cache::create($storage, true, 'test_prefix_');
        
        $this->assertInstanceOf(\WebFiori\Cache\Cache::class, $cache);
        
        // Clean up
        if (is_dir(__DIR__ . '/test_create_cache')) {
            $files = glob(__DIR__ . '/test_create_cache/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir(__DIR__ . '/test_create_cache');
        }
    }
    
    /**
     * @test
     */
    public function testCacheGetPrefixMethod() {
        $originalPrefix = Cache::getPrefix();
        
        Cache::withPrefix('test_prefix_');
        $this->assertEquals('test_prefix_', Cache::getPrefix());
        
        Cache::withPrefix('another_prefix_');
        $this->assertEquals('another_prefix_', Cache::getPrefix());
        
        Cache::withPrefix('');
        $this->assertEquals('', Cache::getPrefix());
    }
    
    /**
     * @test
     */
    public function testCacheMultipleOperationsSequence() {
        $keys = ['seq1', 'seq2', 'seq3', 'seq4', 'seq5'];
        $data = ['data1', 'data2', 'data3', 'data4', 'data5'];
        
        // Set multiple items
        for ($i = 0; $i < count($keys); $i++) {
            Cache::set($keys[$i], $data[$i], 300);
        }
        
        // Verify all items exist
        foreach ($keys as $key) {
            $this->assertTrue(Cache::has($key));
        }
        
        // Get all items and verify data
        for ($i = 0; $i < count($keys); $i++) {
            $this->assertEquals($data[$i], Cache::get($keys[$i]));
        }
        
        // Delete every other item
        for ($i = 0; $i < count($keys); $i += 2) {
            Cache::delete($keys[$i]);
        }
        
        // Verify deletion pattern
        for ($i = 0; $i < count($keys); $i++) {
            if ($i % 2 === 0) {
                $this->assertFalse(Cache::has($keys[$i]));
            } else {
                $this->assertTrue(Cache::has($keys[$i]));
            }
        }
        
        // Clean up remaining items
        Cache::flush();
    }
    
    /**
     * @test
     */
    public function testInvalidCacheKeyExceptions() {
        // Test empty key
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key cannot be empty');
        Cache::set('', 'data');
    }
    
    /**
     * @test
     */
    public function testInvalidCacheKeyWhitespaceOnly() {
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key cannot be empty');
        Cache::set('   ', 'data');
    }
    
    /**
     * @test
     */
    public function testInvalidCacheKeyTooLong() {
        $longKey = str_repeat('a', 251); // Exceeds 250 character limit
        
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key exceeds maximum length of 250 characters');
        Cache::set($longKey, 'data');
    }
    
    /**
     * @test
     */
    public function testInvalidCacheKeyControlCharacters() {
        $keyWithControlChars = "test\x00key"; // Contains null byte
        
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key contains invalid control characters');
        Cache::set($keyWithControlChars, 'data');
    }
    
    /**
     * @test
     */
    public function testValidCacheKeyEdgeCases() {
        // Test maximum valid length (250 characters)
        $maxLengthKey = str_repeat('a', 250);
        $this->assertTrue(Cache::set($maxLengthKey, 'data', 60));
        $this->assertEquals('data', Cache::get($maxLengthKey));
        
        // Test key with special but valid characters
        $specialKey = 'key-with_special.chars@domain.com:8080/path?query=value#fragment';
        $this->assertTrue(Cache::set($specialKey, 'special_data', 60));
        $this->assertEquals('special_data', Cache::get($specialKey));
        
        // Test key with unicode characters
        $unicodeKey = 'key_with_unicode_中文_العربية_русский';
        $this->assertTrue(Cache::set($unicodeKey, 'unicode_data', 60));
        $this->assertEquals('unicode_data', Cache::get($unicodeKey));
    }
    
    /**
     * @test
     */
    public function testCacheDriverException() {
        $this->expectException(\TypeError::class);
        
        // Try to set an invalid driver (not implementing Storage interface)
        Cache::setDriver(new \stdClass());
    }
    
    /**
     * @test
     */
    public function testCacheWithDisabledState() {
        $key = 'disabled_cache_test';
        $data = 'test data';
        
        // Disable caching
        Cache::setEnabled(false);
        $this->assertFalse(Cache::isEnabled());
        
        // Try to cache data - should not be stored
        $result = Cache::get($key, function() use ($data) {
            return $data;
        }, 300);
        
        // Should return generated data but not cache it
        $this->assertEquals($data, $result);
        $this->assertFalse(Cache::has($key));
        
        // Re-enable caching
        Cache::setEnabled(true);
        $this->assertTrue(Cache::isEnabled());
        
        // Now it should cache
        $result2 = Cache::get($key, function() use ($data) {
            return $data;
        }, 300);
        
        $this->assertEquals($data, $result2);
        $this->assertTrue(Cache::has($key));
    }
    
    /**
     * @test
     */
    public function testSetTTLOnNonExistentKey() {
        $nonExistentKey = 'does_not_exist';
        
        $result = Cache::setTTL($nonExistentKey, 600);
        $this->assertFalse($result);
    }
    
    /**
     * @test
     */
    public function testGetItemOnNonExistentKey() {
        $nonExistentKey = 'does_not_exist';
        
        $item = Cache::getItem($nonExistentKey);
        $this->assertNull($item);
    }
    
    /**
     * @test
     */
    public function testDeleteNonExistentKey() {
        $nonExistentKey = 'does_not_exist';
        
        // Should not throw exception
        Cache::delete($nonExistentKey);
        $this->assertFalse(Cache::has($nonExistentKey));
    }
    
    /**
     * @test
     */
    public function testCacheOverrideExistingItem() {
        $key = 'override_test';
        $originalData = 'original data';
        $newData = 'new data';
        
        // Set original data
        Cache::set($key, $originalData, 300);
        $this->assertEquals($originalData, Cache::get($key));
        
        // Try to set without override - should fail
        $result = Cache::set($key, $newData, 300, false);
        $this->assertFalse($result);
        $this->assertEquals($originalData, Cache::get($key));
        
        // Set with override - should succeed
        $result = Cache::set($key, $newData, 300, true);
        $this->assertTrue($result);
        $this->assertEquals($newData, Cache::get($key));
    }
    
    /**
     * @test
     */
    public function testCacheWithCallableGenerator() {
        $key = 'callable_test';
        $counter = 0;
        
        $generator = function() use (&$counter) {
            $counter++;
            return "Generated #{$counter}";
        };
        
        // First call should generate
        $result1 = Cache::get($key, $generator, 300);
        $this->assertEquals('Generated #1', $result1);
        $this->assertEquals(1, $counter);
        
        // Second call should use cache
        $result2 = Cache::get($key, $generator, 300);
        $this->assertEquals('Generated #1', $result2);
        $this->assertEquals(1, $counter); // Counter should not increment
        
        // Delete and call again - should generate new value
        Cache::delete($key);
        $result3 = Cache::get($key, $generator, 300);
        $this->assertEquals('Generated #2', $result3);
        $this->assertEquals(2, $counter);
    }
    
    /**
     * @test
     */
    public function testCacheWithObjectData() {
        $key = 'object_test';
        $object = new \stdClass();
        $object->property1 = 'value1';
        $object->property2 = 42;
        $object->nested = new \stdClass();
        $object->nested->deep = 'deep_value';
        
        Cache::set($key, $object, 300);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($object, $retrieved);
        $this->assertEquals('value1', $retrieved->property1);
        $this->assertEquals(42, $retrieved->property2);
        $this->assertEquals('deep_value', $retrieved->nested->deep);
    }
    
    /**
     * @test
     */
    public function testCacheWithResourceData() {
        $key = 'resource_test';
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'test data');
        
        // Resources cannot be serialized, so this should work but the resource
        // will be converted to its serialized representation
        Cache::set($key, $resource, 300);
        $retrieved = Cache::get($key);
        
        // The retrieved value will not be the same resource
        $this->assertNotSame($resource, $retrieved);
        
        fclose($resource);
    }
}
