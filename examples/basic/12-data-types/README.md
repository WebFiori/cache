# Data Types

Demonstrates caching various PHP data types: strings, integers, floats, booleans, null, arrays, objects, and nested structures. All types survive the serialize/encrypt/decrypt/unserialize round-trip.

In v3, `false` and `null` are correctly cached and retrieved. Use `has()` to distinguish between a cached `null` and a cache miss.

## Run

```bash
php index.php
```

## Expected Output

```
Storing and retrieving different data types:

  [PASS] string: 'hello world'
  [PASS] integer: 42
  [PASS] float: 3.14
  [PASS] bool_true: true
  [PASS] null_value: NULL
  [PASS] array: array (...)
  [PASS] assoc_array: array (...)
  [PASS] object: (object) array(...)
  [PASS] nested: array (...)

Edge case - boolean false:
  get() returns: false
  has() returns: true

Edge case - null:
  get() returns: NULL
  has() returns: true
```
