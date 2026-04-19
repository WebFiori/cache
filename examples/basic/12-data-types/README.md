# Data Types

Demonstrates caching various PHP data types: strings, integers, floats, booleans, null, arrays, objects, and nested structures. All types survive the serialize/encrypt/decrypt/unserialize round-trip.

Also highlights an edge case: `boolean false` and `null` are indistinguishable from a cache miss when using `Cache::get()` alone. Use `Cache::has()` to confirm the item exists.

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
  [PASS] array: array (
  0 => 1,
  1 => 'two',
  2 => 3.0,
)
  [PASS] assoc_array: array (
  'name' => 'Alice',
  'age' => 30,
)
  [PASS] object: (object) array(
   'color' => 'blue',
   'size' => 10,
)
  [PASS] nested: array (
  'level1' =>
  array (
    'level2' =>
    array (
      'deep' => 'value',
    ),
  ),
)

Edge case - boolean false:
  Cache::get() returns: NULL
  Cache::has() returns: true
  Use Cache::has() to confirm the item exists.
```
