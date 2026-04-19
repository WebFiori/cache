# Flush Cache

Demonstrates clearing all cached items at once using `Cache::flush()`.

## Run

```bash
php index.php
```

## Expected Output

```
Before flush:
  has 'item_a': true
  has 'item_b': true
  has 'item_c': true
After flush:
  has 'item_a': false
  has 'item_b': false
  has 'item_c': false
```
