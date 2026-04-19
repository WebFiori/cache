<?php
namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\Storage;

/**
 * Test class for Cache instance operations.
 */
class CacheTest extends TestCase {

    private Cache $cache;

    protected function setUp(): void {
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $testKey;
        KeyManager::clearCache();

        $this->cache = new Cache(new FileStorage(__DIR__ . '/test_cache'));
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
    public function test00() {
        $key = 'first';
        $this->assertTrue($this->cache->isEnabled());
        $data = $this->cache->get($key, function () {
            return 'This is a test.';
        });
        $this->assertEquals('This is a test.', $data);
        $this->assertEquals('This is a test.', $this->cache->get($key));
        $this->assertNull($this->cache->get('not_cached'));
    }

    /**
     * @test
     */
    public function test01() {
        $key = 'test_2';
        $this->assertFalse($this->cache->has($key));
        $data = $this->cache->get($key, function () {
            return 'This is a test.';
        }, 5);
        $this->assertEquals('This is a test.', $data);
        $this->assertTrue($this->cache->has($key));
        sleep(6);
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    /**
     * @test
     */
    public function test03() {
        $key = 'ok_test';
        $this->assertFalse($this->cache->has($key));
        $data = $this->cache->get($key, function () {
            return 'This is a test.';
        }, 600);
        $this->assertEquals('This is a test.', $data);
        $this->assertTrue($this->cache->has($key));
        $this->cache->delete($key);
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    /**
     * @test
     */
    public function test04() {
        $key = 'test_3';
        $this->assertFalse($this->cache->has($key));
        $data = $this->cache->get($key, function () {
            return 'This is a test.';
        }, 600);
        $this->assertEquals('This is a test.', $data);
        $item = $this->cache->getItem($key);
        $this->assertNotNull($item);
        $this->assertEquals(600, $item->getTTL());
        $this->cache->setTTL($key, 1000);
        $item = $this->cache->getItem($key);
        $this->assertEquals(1000, $item->getTTL());
        $this->cache->delete($key);
        $this->assertNull($this->cache->getItem($key));
    }

    /**
     * @test
     */
    public function test05() {
        $keys = [];
        for ($x = 0; $x < 10; $x++) {
            $key = 'item_' . $x;
            $this->cache->get($key, function () {
                return 'This is a test.';
            }, 600);
            $keys[] = $key;
        }
        foreach ($keys as $key) {
            $this->assertTrue($this->cache->has($key));
        }
        $this->cache->flush();
        foreach ($keys as $key) {
            $this->assertFalse($this->cache->has($key));
        }
    }

    /**
     * @test
     */
    public function test06() {
        $key = 'bbuu';
        $this->assertTrue($this->cache->isEnabled());
        $this->cache->setEnabled(false);
        $data = $this->cache->get($key, function () {
            return 'This is a test.';
        });
        $this->assertEquals('This is a test.', $data);
        $this->assertNull($this->cache->get($key));
        $this->assertFalse($this->cache->isEnabled());
    }

    /**
     * @test
     */
    public function testSet00() {
        $key = 'new_cool_key';
        $this->cache->setEnabled(true);
        $this->assertTrue($this->cache->isEnabled());
        $this->assertTrue($this->cache->set($key, 'This is a test.', 60, false));
        $this->assertEquals('This is a test.', $this->cache->get($key));
        $item = $this->cache->getItem($key);
        $this->assertEquals(60, $item->getTTL());

        $this->assertFalse($this->cache->set($key, 'This is a test.', 60, false));
        $this->assertEquals('This is a test.', $this->cache->get($key));
        $item = $this->cache->getItem($key);
        $this->assertEquals(60, $item->getTTL());

        $this->assertTrue($this->cache->set($key, 'This is a test 2.', 660, true));
        $this->assertEquals('This is a test 2.', $this->cache->get($key));
        $item = $this->cache->getItem($key);
        $this->assertEquals(660, $item->getTTL());
    }

    /**
     * @test
     */
    public function testSetTTL00() {
        $key = 'new_cool_key2';
        $this->cache->setEnabled(true);
        $this->assertTrue($this->cache->set($key, 'This is a test.', 60, false));
        $item = $this->cache->getItem($key);
        $this->assertEquals(60, $item->getTTL());
        $this->cache->setTTL($key, 700);
        $item = $this->cache->getItem($key);
        $this->assertEquals(700, $item->getTTL());
    }

    /**
     * @test
     */
    public function testSetTTL01() {
        $key = 'not exist cool';
        $this->assertFalse($this->cache->setTTL($key, 700));
    }

    /**
     * @test
     */
    public function testWithPrefix00() {
        $key = 'first';
        $this->assertTrue($this->cache->isEnabled());
        $prefixed = $this->cache->withPrefix('ok');
        $data = $prefixed->get($key, function () {
            return 'This is a test.';
        });
        $this->assertEquals('This is a test.', $data);
        $this->assertEquals('This is a test.', $prefixed->get($key));
        // Without prefix, key does not exist
        $this->assertNull($this->cache->get($key));
        // After deleting from no-prefix, prefixed still has it
        $this->assertEquals('This is a test.', $prefixed->get($key));
    }

    /**
     * @test
     */
    public function testWithPrefix01() {
        $key = 'test-';
        $key2 = 'ttest-2-';
        $prefix1 = 'good';
        $prefix2 = 'bad';

        $cache1 = $this->cache->withPrefix($prefix1);
        $cache2 = $this->cache->withPrefix($prefix2);

        for ($x = 0; $x < 3; $x++) {
            $cache1->get($key . $x, function (int $x, $pfx) {
                return 'This is a test. ' . $x . $pfx;
            }, 60, [$x, $prefix1]);
        }
        for ($x = 0; $x < 3; $x++) {
            $cache2->get($key2 . $x, function (int $x, $pfx) {
                return 'This is a test. ' . $x . $pfx;
            }, 60, [$x, $prefix2]);
        }
        for ($x = 0; $x < 3; $x++) {
            $this->assertTrue($cache1->has($key . $x));
        }
        for ($x = 0; $x < 3; $x++) {
            $this->assertTrue($cache2->has($key2 . $x));
        }
        $cache1->flush();
        for ($x = 0; $x < 3; $x++) {
            $this->assertFalse($cache1->has($key . $x));
        }
        for ($x = 0; $x < 3; $x++) {
            $this->assertTrue($cache2->has($key2 . $x));
        }
    }

    /**
     * @test
     */
    public function testWithPrefixDoesNotMutate() {
        $this->cache->set('key', 'no_prefix', 60);
        $prefixed = $this->cache->withPrefix('pfx_');
        $prefixed->set('key', 'with_prefix', 60);

        // Original cache is unaffected
        $this->assertEquals('', $this->cache->getPrefix());
        $this->assertEquals('no_prefix', $this->cache->get('key'));

        // Prefixed cache has its own namespace
        $this->assertEquals('pfx_', $prefixed->getPrefix());
        $this->assertEquals('with_prefix', $prefixed->get('key'));
    }

    /**
     * @test
     */
    public function testEncryptionIntegration() {
        $sensitiveData = 'This is sensitive information';
        $key = 'sensitive_key';

        $this->cache->set($key, $sensitiveData, 60);
        $retrieved = $this->cache->get($key);

        $this->assertEquals($sensitiveData, $retrieved);

        $item = $this->cache->getItem($key);
        $this->assertNotNull($item);
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
        $this->cache->set($key, $complexData, 300);
        $retrieved = $this->cache->get($key);
        $this->assertEquals($complexData, $retrieved);
    }

    /**
     * @test
     */
    public function testCacheWithZeroTTL() {
        $key = 'zero_ttl_test';
        $this->cache->set($key, 'data', 0);
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    /**
     * @test
     */
    public function testCacheWithNegativeTTL() {
        $key = 'negative_ttl_test';
        $this->cache->set($key, 'data', -100);
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    /**
     * @test
     */
    public function testCacheGetWithParameters() {
        $key = 'parameterized_test';
        $result = $this->cache->get($key, function ($p1, $p2, $p3) {
            return "Generated with: {$p1}, {$p2}, {$p3}";
        }, 300, ['value1', 'value2', 'value3']);

        $this->assertEquals("Generated with: value1, value2, value3", $result);
        $this->assertEquals("Generated with: value1, value2, value3", $this->cache->get($key));
    }

    /**
     * @test
     */
    public function testCacheGetWithEmptyParameters() {
        $result = $this->cache->get('empty_params_test', function () {
            return 'Generated without parameters';
        }, 300, []);
        $this->assertEquals('Generated without parameters', $result);
    }

    /**
     * @test
     */
    public function testCacheGetWithNullGenerator() {
        $this->assertNull($this->cache->get('null_generator_test', null));

        $this->cache->set('null_generator_test', 'test_value', 300);
        $this->assertEquals('test_value', $this->cache->get('null_generator_test', null));
    }

    /**
     * @test
     */
    public function testCacheDriverManagement() {
        $originalDriver = $this->cache->getDriver();
        $this->assertInstanceOf(Storage::class, $originalDriver);

        $newDriver = new FileStorage(__DIR__ . '/test_cache_driver');
        $this->cache->setDriver($newDriver);
        $this->assertSame($newDriver, $this->cache->getDriver());

        $this->cache->set('driver_test', 'test_data', 300);
        $this->assertTrue($this->cache->has('driver_test'));
        $this->assertEquals('test_data', $this->cache->get('driver_test'));

        $this->cache->flush();
        if (is_dir(__DIR__ . '/test_cache_driver')) {
            rmdir(__DIR__ . '/test_cache_driver');
        }
        $this->cache->setDriver($originalDriver);
    }

    /**
     * @test
     */
    public function testCacheItemDetails() {
        $key = 'item_details_test';
        $data = 'test data for item details';
        $ttl = 600;

        $this->cache->set($key, $data, $ttl);
        $item = $this->cache->getItem($key);

        $this->assertNotNull($item);
        $this->assertEquals($key, $item->getKey());
        $this->assertEquals($data, $item->getDataDecrypted());
        $this->assertEquals($ttl, $item->getTTL());
        $this->assertGreaterThan(0, $item->getCreatedAt());
        $this->assertGreaterThan(time(), $item->getExpiryTime());
        $this->assertEquals($item->getCreatedAt() + $ttl, $item->getExpiryTime());
    }

    /**
     * @test
     */
    public function testCacheFlushWithPrefix() {
        $c1 = $this->cache->withPrefix('prefix1_');
        $c2 = $this->cache->withPrefix('prefix2_');

        $c1->set('item1', 'data1', 300);
        $c1->set('item2', 'data2', 300);
        $c2->set('item1', 'data3', 300);
        $c2->set('item2', 'data4', 300);

        $this->assertTrue($c1->has('item1'));
        $this->assertTrue($c1->has('item2'));
        $this->assertTrue($c2->has('item1'));
        $this->assertTrue($c2->has('item2'));

        $c1->flush();

        $this->assertFalse($c1->has('item1'));
        $this->assertFalse($c1->has('item2'));
        $this->assertTrue($c2->has('item1'));
        $this->assertTrue($c2->has('item2'));

        $c2->flush();
    }

    /**
     * @test
     */
    public function testCacheWithLargeData() {
        $largeData = str_repeat('A', 1024 * 1024);
        $this->cache->set('large_data_test', $largeData, 300);
        $this->assertTrue($this->cache->has('large_data_test'));
        $retrieved = $this->cache->get('large_data_test');
        $this->assertEquals($largeData, $retrieved);
        $this->assertEquals(1024 * 1024, strlen($retrieved));
    }

    /**
     * @test
     */
    public function testCacheWithSpecialCharacters() {
        $data = "Special chars: àáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ 中文 العربية русский 🚀🎉💯";
        $this->cache->set('special_chars_test', $data, 300);
        $this->assertEquals($data, $this->cache->get('special_chars_test'));
    }

    /**
     * @test
     */
    public function testCacheGetPrefixMethod() {
        $this->assertEquals('', $this->cache->getPrefix());
        $prefixed = $this->cache->withPrefix('test_prefix_');
        $this->assertEquals('test_prefix_', $prefixed->getPrefix());
        // Original unchanged
        $this->assertEquals('', $this->cache->getPrefix());
    }

    /**
     * @test
     */
    public function testCacheMultipleOperationsSequence() {
        $keys = ['seq1', 'seq2', 'seq3', 'seq4', 'seq5'];
        $data = ['data1', 'data2', 'data3', 'data4', 'data5'];

        for ($i = 0; $i < count($keys); $i++) {
            $this->cache->set($keys[$i], $data[$i], 300);
        }
        foreach ($keys as $key) {
            $this->assertTrue($this->cache->has($key));
        }
        for ($i = 0; $i < count($keys); $i++) {
            $this->assertEquals($data[$i], $this->cache->get($keys[$i]));
        }
        for ($i = 0; $i < count($keys); $i += 2) {
            $this->cache->delete($keys[$i]);
        }
        for ($i = 0; $i < count($keys); $i++) {
            if ($i % 2 === 0) {
                $this->assertFalse($this->cache->has($keys[$i]));
            } else {
                $this->assertTrue($this->cache->has($keys[$i]));
            }
        }
    }

    /**
     * @test
     */
    public function testInvalidCacheKeyExceptions() {
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key cannot be empty');
        $this->cache->set('', 'data');
    }

    /**
     * @test
     */
    public function testInvalidCacheKeyWhitespaceOnly() {
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key cannot be empty');
        $this->cache->set('   ', 'data');
    }

    /**
     * @test
     */
    public function testInvalidCacheKeyTooLong() {
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key exceeds maximum length of 250 characters');
        $this->cache->set(str_repeat('a', 251), 'data');
    }

    /**
     * @test
     */
    public function testInvalidCacheKeyControlCharacters() {
        $this->expectException(\WebFiori\Cache\Exceptions\InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Cache key contains invalid control characters');
        $this->cache->set("test\x00key", 'data');
    }

    /**
     * @test
     */
    public function testValidCacheKeyEdgeCases() {
        $maxLengthKey = str_repeat('a', 250);
        $this->assertTrue($this->cache->set($maxLengthKey, 'data', 60));
        $this->assertEquals('data', $this->cache->get($maxLengthKey));

        $specialKey = 'key-with_special.chars@domain.com:8080/path?query=value#fragment';
        $this->assertTrue($this->cache->set($specialKey, 'special_data', 60));
        $this->assertEquals('special_data', $this->cache->get($specialKey));

        $unicodeKey = 'key_with_unicode_中文_العربية_русский';
        $this->assertTrue($this->cache->set($unicodeKey, 'unicode_data', 60));
        $this->assertEquals('unicode_data', $this->cache->get($unicodeKey));
    }

    /**
     * @test
     */
    public function testCacheWithDisabledState() {
        $key = 'disabled_cache_test';
        $data = 'test data';

        $this->cache->setEnabled(false);
        $this->assertFalse($this->cache->isEnabled());

        $result = $this->cache->get($key, function () use ($data) {
            return $data;
        }, 300);
        $this->assertEquals($data, $result);
        $this->assertFalse($this->cache->has($key));

        $this->cache->setEnabled(true);
        $result2 = $this->cache->get($key, function () use ($data) {
            return $data;
        }, 300);
        $this->assertEquals($data, $result2);
        $this->assertTrue($this->cache->has($key));
    }

    /**
     * @test
     */
    public function testSetTTLOnNonExistentKey() {
        $this->assertFalse($this->cache->setTTL('does_not_exist', 600));
    }

    /**
     * @test
     */
    public function testGetItemOnNonExistentKey() {
        $this->assertNull($this->cache->getItem('does_not_exist'));
    }

    /**
     * @test
     */
    public function testDeleteNonExistentKey() {
        $this->cache->delete('does_not_exist');
        $this->assertFalse($this->cache->has('does_not_exist'));
    }

    /**
     * @test
     */
    public function testCacheOverrideExistingItem() {
        $this->cache->set('override_test', 'original', 300);
        $this->assertEquals('original', $this->cache->get('override_test'));

        $this->assertFalse($this->cache->set('override_test', 'new', 300, false));
        $this->assertEquals('original', $this->cache->get('override_test'));

        $this->assertTrue($this->cache->set('override_test', 'new', 300, true));
        $this->assertEquals('new', $this->cache->get('override_test'));
    }

    /**
     * @test
     */
    public function testCacheWithCallableGenerator() {
        $counter = 0;
        $generator = function () use (&$counter) {
            $counter++;
            return "Generated #{$counter}";
        };

        $this->assertEquals('Generated #1', $this->cache->get('callable_test', $generator, 300));
        $this->assertEquals(1, $counter);

        $this->assertEquals('Generated #1', $this->cache->get('callable_test', $generator, 300));
        $this->assertEquals(1, $counter);

        $this->cache->delete('callable_test');
        $this->assertEquals('Generated #2', $this->cache->get('callable_test', $generator, 300));
        $this->assertEquals(2, $counter);
    }

    /**
     * @test
     */
    public function testCacheWithObjectData() {
        $object = new \stdClass();
        $object->property1 = 'value1';
        $object->property2 = 42;
        $object->nested = new \stdClass();
        $object->nested->deep = 'deep_value';

        $this->cache->set('object_test', $object, 300);
        $retrieved = $this->cache->get('object_test');

        $this->assertEquals($object, $retrieved);
        $this->assertEquals('value1', $retrieved->property1);
        $this->assertEquals(42, $retrieved->property2);
        $this->assertEquals('deep_value', $retrieved->nested->deep);
    }

    /**
     * @test
     */
    public function testCacheWithFalsyValues() {
        // false
        $this->cache->set('val_false', false, 60);
        $this->assertTrue($this->cache->has('val_false'));
        $this->assertFalse($this->cache->get('val_false'));

        // null
        $this->cache->set('val_null', null, 60);
        $this->assertTrue($this->cache->has('val_null'));
        $this->assertNull($this->cache->get('val_null'));

        // 0
        $this->cache->set('val_zero', 0, 60);
        $this->assertTrue($this->cache->has('val_zero'));
        $this->assertSame(0, $this->cache->get('val_zero'));

        // empty string
        $this->cache->set('val_empty', '', 60);
        $this->assertTrue($this->cache->has('val_empty'));
        $this->assertSame('', $this->cache->get('val_empty'));

        // Generator should NOT run when false is cached
        $counter = 0;
        $this->cache->set('gen_false', false, 60);
        $val = $this->cache->get('gen_false', function () use (&$counter) {
            $counter++;
            return 'generated';
        }, 60);
        $this->assertFalse($val);
        $this->assertEquals(0, $counter);
    }

    /**
     * @test
     */
    public function testSetReturnsFalseOnStorageFailure() {
        // Make directory read-only to force storage failure
        $dir = __DIR__ . '/readonly_test';
        mkdir($dir, 0700, true);
        $cache = new Cache(new FileStorage($dir));
        chmod($dir, 0444);

        $result = $cache->set('test', 'data', 60);
        $this->assertFalse($result);

        chmod($dir, 0700);
        rmdir($dir);
    }
}
