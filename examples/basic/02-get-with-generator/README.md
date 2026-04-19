# Get with Generator

Demonstrates using `Cache::get()` with a generator callback. The callback only runs on a cache miss. On subsequent calls, the cached value is returned without invoking the callback.

## Run

```bash
php index.php
```

## Expected Output

```
Generator called: fetching data...
Result: 42
Result (from cache): 42
```
