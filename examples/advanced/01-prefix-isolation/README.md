# Prefix Isolation

Demonstrates using `Cache::withPrefix()` to create isolated namespaces. Items stored under different prefixes do not collide, even if they share the same key.

## Run

```bash
php index.php
```

## Expected Output

```
users_count:  100
orders_count: 250
no prefix 'count': NULL
```
