# Encrypt / Decrypt Round-Trip

Demonstrates the full encryption and decryption cycle at the `Item` level. Shows that encrypted data differs from the original, and that decryption restores the exact original value.

## Run

```bash
php index.php
```

## Expected Output

```
Original:  Sensitive payment info: card ending 1234
Encrypted: <base64 encoded encrypted string>
Match?     different (encrypted)

Decrypted: Sensitive payment info: card ending 1234
Round-trip match? yes
```

Note: The encrypted string will differ on each run due to random IV generation.
