<?php

/**
 * This file is licensed under MIT License.
 *
 * Copyright (c) 2024 WebFiori Framework
 *
 * For more information on the license, please visit:
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 *
 */
namespace WebFiori\Cache;

/**
 * A static facade for the Cache class.
 *
 * Provides a convenient static API that delegates to a default Cache instance.
 * For dependency injection or multiple cache pools, use the Cache class directly.
 */
class CacheFacade {
    /**
     * @var Cache|null The default cache instance.
     */
    private static ?Cache $inst = null;

    /**
     * @see Cache::delete()
     */
    public static function delete(string $key): void {
        self::getInstance()->delete($key);
    }

    /**
     * @see Cache::flush()
     */
    public static function flush(): void {
        self::getInstance()->flush();
    }

    /**
     * @see Cache::get()
     */
    public static function get(string $key, ?callable $generator = null, int $ttl = 60, array $params = []) {
        return self::getInstance()->get($key, $generator, $ttl, $params);
    }

    /**
     * @see Cache::getDriver()
     */
    public static function getDriver(): Storage {
        return self::getInstance()->getDriver();
    }

    /**
     * Returns the default Cache instance, creating it lazily if needed.
     *
     * @return Cache
     */
    public static function getInstance(): Cache {
        if (self::$inst === null) {
            self::$inst = new Cache(
                new FileStorage(__DIR__.DIRECTORY_SEPARATOR.'cache'),
                true,
                ''
            );
        }

        return self::$inst;
    }

    /**
     * @see Cache::getItem()
     */
    public static function getItem(string $key): ?Item {
        return self::getInstance()->getItem($key);
    }

    /**
     * @see Cache::getPrefix()
     */
    public static function getPrefix(): string {
        return self::getInstance()->getPrefix();
    }

    /**
     * @see Cache::has()
     */
    public static function has(string $key): bool {
        return self::getInstance()->has($key);
    }

    /**
     * @see Cache::isEnabled()
     */
    public static function isEnabled(): bool {
        return self::getInstance()->isEnabled();
    }

    /**
     * @see Cache::purgeExpired()
     */
    public static function purgeExpired(): int {
        return self::getInstance()->purgeExpired();
    }

    /**
     * Destroys the default Cache instance. The next call will create a fresh one.
     * Useful for testing.
     */
    public static function reset(): void {
        self::$inst = null;
    }

    /**
     * @see Cache::set()
     */
    public static function set(string $key, $data, int $ttl = 60, bool $override = false): bool {
        return self::getInstance()->set($key, $data, $ttl, $override);
    }

    /**
     * @see Cache::setDriver()
     */
    public static function setDriver(Storage $driver): void {
        self::getInstance()->setDriver($driver);
    }

    /**
     * @see Cache::setEnabled()
     */
    public static function setEnabled(bool $enable): void {
        self::getInstance()->setEnabled($enable);
    }

    /**
     * Replaces the default Cache instance.
     *
     * @param Cache $cache The cache instance to use as default.
     */
    public static function setInstance(Cache $cache): void {
        self::$inst = $cache;
    }

    /**
     * @see Cache::setTTL()
     */
    public static function setTTL(string $key, int $ttl): bool {
        return self::getInstance()->setTTL($key, $ttl);
    }

    /**
     * Returns a new Cache instance with the given prefix, sharing the same
     * driver and enabled state as the default instance.
     *
     * @param string $prefix The prefix to use.
     * @return Cache A new cache instance with the given prefix.
     */
    public static function withPrefix(string $prefix): Cache {
        return self::getInstance()->withPrefix($prefix);
    }
}
