# Generator with Parameters

Demonstrates passing parameters to the generator callback via the fourth argument of `Cache::get()`. The parameters are forwarded to the callback using `call_user_func_array`.

## Run

```bash
php index.php
```

## Expected Output

```
Generator called with userId=7, includeEmail=true
Result: Array
(
    [id] => 7
    [name] => John
    [email] => john@example.com
)
```
