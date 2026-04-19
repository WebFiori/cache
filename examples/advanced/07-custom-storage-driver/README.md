# Custom Storage Driver

Demonstrates implementing the `Storage` interface to create a custom in-memory cache backend, then plugging it in with `Cache::setDriver()`.

## Run

```bash
php index.php
```

## Expected Output

```
Driver type: MemoryStorage
Items stored: 2
key1: value1
key2: value2
After flush: 0 items
```
