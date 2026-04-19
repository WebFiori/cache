# Encryption Disabled

Demonstrates creating a cache item with encryption explicitly disabled. When encryption is off, `getDataEncrypted()` returns plain serialized data (not encrypted), and `getDataDecrypted()` returns the original value.

## Run

```bash
php index.php
```

## Expected Output

```
Stored representation: s:18:"This is not secret";
Is it just serialized? yes
Decrypted value:       This is not secret
```
