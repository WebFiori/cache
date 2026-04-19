# Security Configuration

Demonstrates the `SecurityConfig` class: default values, programmatic customization, and loading settings from environment variables.

## Run

```bash
php index.php
```

## Expected Output

```
Defaults:
  Encryption enabled:    true
  Algorithm:             aes-256-cbc
  File permissions:      600
  Directory permissions: 700

After customization:
  Encryption enabled:    false
  Algorithm:             aes-128-cbc
  File permissions:      644
  Directory permissions: 755

From environment variables:
  Encryption enabled:    false
  Algorithm:             aes-256-gcm
  File permissions:      640
  Directory permissions: 750
```
