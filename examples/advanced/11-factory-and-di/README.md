# Factory and Dependency Injection

Demonstrates creating a `Cache` instance directly with a specific storage driver, enabled state, and prefix via the constructor. This is the recommended approach for dependency injection in modern applications.

## Run

```bash
php index.php
```

## Expected Output

```
Instance type: WebFiori\Cache\Cache
Prefix: myapp_
Stored 'setting': dark_mode
Cleaned up.
```
