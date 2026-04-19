# Prefix Flush

Demonstrates that `Cache::flush()` only removes items matching the current prefix. Items under other prefixes are unaffected.

## Run

```bash
php index.php
```

## Expected Output

```
Before flush:
  app1_data:   App 1 data
  app1_config: App 1 config
  app2_data:   App 2 data
  app2_config: App 2 config

After flushing 'app1_':
  app1_data:   NULL
  app1_config: NULL
  app2_data:   App 2 data
  app2_config: App 2 config
```
