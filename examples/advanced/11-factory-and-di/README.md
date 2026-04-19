# Factory and Dependency Injection

Demonstrates using `Cache::create()` to instantiate a `Cache` object with a specific storage driver, enabled state, and prefix. This is useful for dependency injection in modern applications.

## Run

```bash
php index.php
```

## Expected Output

```
Instance type: WebFiori\Cache\Cache
Stored 'setting': dark_mode
Prefix: myapp_
Cleaned up.
```
