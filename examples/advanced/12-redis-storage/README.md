# Redis Storage Driver

Demonstrates using Redis as the cache backend via `RedisStorage`.

## Prerequisites

- PHP `ext-redis` extension installed
- Redis server running on `127.0.0.1:6379`

## What it demonstrates

- Creating a `RedisStorage` with a connected `\Redis` instance
- Basic `set()` / `get()` operations backed by Redis
- Prefix isolation with `withPrefix()`
- Generator callbacks for cache-miss population
- Redis native TTL handling (no manual expiry cleanup needed)

## Run

```bash
php examples/advanced/12-redis-storage/index.php
```
