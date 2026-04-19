# Examples

Working code samples demonstrating the features of the WebFiori Cache library. Each example is self-contained in its own folder with a `README.md` and an `index.php` that can be run independently.

## Prerequisites

Install dependencies from the project root:

```bash
composer install
```

Then run any example:

```bash
php examples/basic/01-set-and-get/index.php
```

## Basic Examples

| # | Example | Description |
|---|---------|-------------|
| 01 | [Set and Get](basic/01-set-and-get/) | Store and retrieve cache items |
| 02 | [Get with Generator](basic/02-get-with-generator/) | Auto-populate cache on miss using a callback |
| 03 | [Generator with Params](basic/03-generator-with-params/) | Pass arguments to the generator callback |
| 04 | [Check Existence](basic/04-check-existence/) | Check if a cache item exists with `has()` |
| 05 | [Delete Item](basic/05-delete-item/) | Remove a specific cache item |
| 06 | [Flush Cache](basic/06-flush-cache/) | Clear all cached items |
| 07 | [Update TTL](basic/07-update-ttl/) | Change the TTL of an existing item |
| 08 | [Override Behavior](basic/08-override-behavior/) | Control whether `set()` overwrites existing items |
| 09 | [TTL Expiration](basic/09-ttl-expiration/) | Items automatically expire after their TTL |
| 10 | [Enable / Disable](basic/10-enable-disable/) | Toggle caching on and off |
| 11 | [Item Inspection](basic/11-item-inspection/) | Read item metadata (key, TTL, timestamps) |
| 12 | [Data Types](basic/12-data-types/) | Cache strings, numbers, arrays, objects, and more |

## Advanced Examples

| # | Example | Description |
|---|---------|-------------|
| 01 | [Prefix Isolation](advanced/01-prefix-isolation/) | Namespace isolation using key prefixes |
| 02 | [Prefix Flush](advanced/02-prefix-flush/) | Flush only items under a specific prefix |
| 03 | [Encryption Key Management](advanced/03-encryption-key-management/) | Generate, set, and validate encryption keys |
| 04 | [Security Config](advanced/04-security-config/) | Customize encryption algorithm and file permissions |
| 05 | [Encryption Disabled](advanced/05-encryption-disabled/) | Store data without encryption |
| 06 | [Encrypt/Decrypt Round-Trip](advanced/06-encrypt-decrypt-roundtrip/) | Full encryption and decryption cycle |
| 07 | [Custom Storage Driver](advanced/07-custom-storage-driver/) | Implement the `Storage` interface |
| 08 | [File Storage Custom Path](advanced/08-file-storage-custom-path/) | Use a custom directory for file-based cache |
| 09 | [File Permissions](advanced/09-file-permissions/) | Default and custom file/directory permissions |
| 10 | [Error Handling](advanced/10-error-handling/) | Exception types and when they are thrown |
| 11 | [Factory and DI](advanced/11-factory-and-di/) | Create cache instances for dependency injection |
