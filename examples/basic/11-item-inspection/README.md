# Item Inspection

Demonstrates using `Cache::getItem()` to retrieve an `Item` object with full metadata: key, TTL, creation time, expiry time, and decrypted data.

## Run

```bash
php index.php
```

## Expected Output

```
Key:        report
TTL:        600 seconds
Created at: <current datetime>
Expires at: <current datetime + 600s>
Data:       Array
(
    [total] => 150
    [active] => 98
)
Missing item: NULL
```

Note: The timestamps will reflect the time you run the example.
