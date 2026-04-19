# Error Handling

Demonstrates the library's exception hierarchy and the conditions that trigger each exception type:

- `InvalidCacheKeyException` — empty key, key too long, key with control characters
- `CacheStorageException` — empty storage path, path is a file instead of directory
- `CacheException` — missing encryption key when encryption is enabled

## Run

```bash
php index.php
```

## Expected Output

```
1. Empty cache key:
   Invalid cache key - Cache key cannot be empty

2. Key too long:
   Invalid cache key: 'xxx...xxx' - Cache key exceeds maximum length of 250 characters

3. Key with control characters:
   Invalid cache key: 'bad\x00key' - Cache key contains invalid control characters

4. Empty storage path:
   Cache path cannot be empty

5. Storage path is a file:
   Cache path exists but is not a directory: /path/to/not_a_dir

6. Missing encryption key:
   No valid encryption key found. Please set CACHE_ENCRYPTION_KEY environment variable with a 64-character hexadecimal key.
```

Note: File paths in the output will reflect your local file system.
