# Override Behavior

Demonstrates the `$override` parameter of `Cache::set()`. By default, `set()` will not overwrite an existing non-expired item. Pass `true` as the fourth argument to force an overwrite.

## Run

```bash
php index.php
```

## Expected Output

```
Initial value: original
set() without override returned: false
Value after: original
set() with override returned: true
Value after: new_value
```
