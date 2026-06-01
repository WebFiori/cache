<?php

namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\Cache;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\RedisStorage;
use WebFiori\Cache\SecurityConfig;

/**
 * @group redis
 */
class RedisStorageTest extends TestCase {
    private static \Redis $redis;
    private static string $prefix = 'wf_test:';

    public static function setUpBeforeClass(): void {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('ext-redis is not available.');
        }

        self::$redis = new \Redis();

        try {
            if (!self::$redis->connect('127.0.0.1', 6379, 2.0)) {
                self::markTestSkipped('Cannot connect to Redis at 127.0.0.1:6379.');
            }
        } catch (\RedisException $e) {
            self::markTestSkipped('Cannot connect to Redis at 127.0.0.1:6379: '.$e->getMessage());
        }
    }

    protected function setUp(): void {
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_KEY'] = $testKey;
        KeyManager::clearCache();

        // Flush test keys
        $iterator = null;

        while (($keys = self::$redis->scan($iterator, self::$prefix.'*', 100)) !== false) {
            if (!empty($keys)) {
                self::$redis->del($keys);
            }
        }
    }

    protected function tearDown(): void {
        unset($_ENV['CACHE_KEY']);
        KeyManager::clearCache();
    }

    public function testStoreAndRead(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('hello', 'world', 60);
        $this->assertEquals('world', $cache->get('hello'));
    }

    public function testStoreAndReadWithoutEncryption(): void {
        unset($_ENV['CACHE_KEY']);
        KeyManager::clearCache();

        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('plain', 'text_value', 60);
        $this->assertEquals('text_value', $cache->get('plain'));
    }

    public function testHas(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $this->assertFalse($cache->has('missing'));
        $cache->set('exists', 'data', 60);
        $this->assertTrue($cache->has('exists'));
    }

    public function testDelete(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('to_delete', 'value', 60);
        $this->assertTrue($cache->has('to_delete'));

        $cache->delete('to_delete');
        $this->assertFalse($cache->has('to_delete'));
    }

    public function testFlush(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('a', '1', 60);
        $cache->set('b', '2', 60);
        $cache->flush();

        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testFlushWithPrefix(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $users = $cache->withPrefix('users_');
        $orders = $cache->withPrefix('orders_');

        $users->set('count', 10, 60);
        $orders->set('count', 20, 60);

        $users->flush();

        $this->assertFalse($users->has('count'));
        $this->assertTrue($orders->has('count'));
    }

    public function testTTLExpiration(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('short_lived', 'data', 1);
        $this->assertTrue($cache->has('short_lived'));

        sleep(2);
        $this->assertFalse($cache->has('short_lived'));
    }

    public function testPurgeExpiredReturnsZero(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $this->assertEquals(0, $storage->purgeExpired());
    }

    public function testGetWithGenerator(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $value = $cache->get('generated', function () {
            return 'from_callback';
        }, 60);

        $this->assertEquals('from_callback', $value);
        $this->assertEquals('from_callback', $cache->get('generated'));
    }

    public function testDataTypes(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        // Array
        $cache->set('arr', ['a' => 1, 'b' => 2], 60, true);
        $this->assertEquals(['a' => 1, 'b' => 2], $cache->get('arr'));

        // Integer
        $cache->set('int', 42, 60, true);
        $this->assertEquals(42, $cache->get('int'));

        // Boolean
        $cache->set('bool', true, 60, true);
        $this->assertTrue($cache->get('bool'));

        // Null
        $cache->set('null_val', null, 60, true);
        $this->assertNull($cache->get('null_val'));
    }

    public function testGetItem(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('meta', 'info', 120);
        $item = $cache->getItem('meta');

        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('meta', $item->getKey());
        $this->assertEquals(120, $item->getTTL());
    }

    public function testSetTTL(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('ttl_test', 'value', 60);
        $result = $cache->setTTL('ttl_test', 300);

        $this->assertTrue($result);

        $item = $cache->getItem('ttl_test');
        $this->assertEquals(300, $item->getTTL());
    }

    public function testOverrideBehavior(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $cache = new Cache($storage);

        $cache->set('key', 'original', 60);
        $this->assertFalse($cache->set('key', 'new', 60, false));
        $this->assertEquals('original', $cache->get('key'));

        $this->assertTrue($cache->set('key', 'new', 60, true));
        $this->assertEquals('new', $cache->get('key'));
    }

    public function testGetRedisAndPrefix(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $this->assertSame(self::$redis, $storage->getRedis());
        $this->assertEquals(self::$prefix, $storage->getRedisPrefix());
    }

    public function testReadItemReturnsNullForMissing(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $this->assertNull($storage->readItem('nonexistent', null));
    }

    public function testReadReturnsNullForMissing(): void {
        $storage = new RedisStorage(self::$redis, self::$prefix);
        $this->assertNull($storage->read('nonexistent', null));
    }
}
