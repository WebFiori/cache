# File Permissions

Demonstrates the default restrictive file permissions (0600 for files, 0700 for directories) and how to customize them via `SecurityConfig`.

## Run

```bash
php index.php
```

## Expected Output

```
Default permissions:
  Directory: 700
  File:      600

Custom permissions:
  Directory: 755
  File:      644
```
