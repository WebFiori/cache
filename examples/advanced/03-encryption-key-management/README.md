# Encryption Key Management

Demonstrates the `KeyManager` class: generating keys, setting them programmatically or via environment variables, and the errors thrown for invalid or missing keys.

## Run

```bash
php index.php
```

## Expected Output

```
Generated key: <64-char hex string>
Key length:    64 characters

Key set via setEncryptionKey().
Retrieved key: <same 64-char hex string>

Key set via $_ENV['CACHE_ENCRYPTION_KEY'].
Retrieved key: <same 64-char hex string>

Invalid key rejected: Invalid cache key: 'too-short' - Invalid encryption key format. Must be 64 hexadecimal characters.

Missing key error: No valid encryption key found. Please set CACHE_ENCRYPTION_KEY environment variable with a 64-character hexadecimal key.
```

Note: The actual key value will differ on each run since it is randomly generated.
