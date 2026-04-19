# Delete Item

Demonstrates removing a specific cache item with `Cache::delete()`. Also shows that deleting a non-existent key does not throw an error.

## Run

```bash
php index.php
```

## Expected Output

```
Before delete - has 'temp_data': true
After delete  - has 'temp_data': false
Deleting non-existent key: no error.
```
