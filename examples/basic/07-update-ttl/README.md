# Update TTL

Demonstrates updating the time-to-live of an existing cache item using `Cache::setTTL()`. Also shows that it returns `false` when the key does not exist.

## Run

```bash
php index.php
```

## Expected Output

```
Original TTL: 60 seconds
Updated TTL:  3600 seconds
setTTL on missing key: false
```
