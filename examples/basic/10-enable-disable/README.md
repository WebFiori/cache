# Enable / Disable Caching

Demonstrates toggling caching on and off with `Cache::setEnabled()`. When disabled, generator callbacks still return data but nothing is persisted to storage. Useful for debugging.

## Run

```bash
php index.php
```

## Expected Output

```
Caching enabled: true
Caching enabled: false
Generator returned: generated value
Is it cached? false
Caching enabled: true
Is it cached now? true
```
