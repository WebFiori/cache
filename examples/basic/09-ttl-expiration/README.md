# TTL Expiration

Demonstrates that cache items automatically expire after their time-to-live (TTL) elapses. After expiration, `Cache::get()` returns `null`.

**Note:** This example sleeps for 3 seconds to demonstrate expiration.

## Run

```bash
php index.php
```

## Expected Output

```
Immediately after set: I expire quickly
Waiting 3 seconds for expiration...
After expiration: NULL
```
