# Forgot Password Feature — Setup Guide

## Files Delivered

```
forgot_password_feature/
├── database/
│   └── password_reset_tokens.sql     ← Run once in MySQL
├── pages/
│   ├── forgot_password.php           ← NEW: email entry form
│   ├── reset_password.php            ← NEW: new-password form
│   └── login.php                     ← MODIFIED: adds "Forgot password?" link + success msg
└── backend/
    ├── auth/
    │   ├── send_reset.php            ← NEW: validates email, sends reset email
    │   └── update_password.php       ← NEW: hashes new password, updates DB
    └── config.local.php              ← MODIFIED: add mail constants to your existing file
```

---

## Step 1 — Copy Files Into Your Project

Copy into your **Collabwork/** root exactly as shown:

| Delivered file | Destination in your project |
|---|---|
| `pages/forgot_password.php` | `Collabwork/pages/forgot_password.php` |
| `pages/reset_password.php` | `Collabwork/pages/reset_password.php` |
| `backend/auth/send_reset.php` | `Collabwork/backend/auth/send_reset.php` |
| `backend/auth/update_password.php` | `Collabwork/backend/auth/update_password.php` |

---

## Step 2 — Replace login.php (or apply the two diffs)

**Option A — Replace the whole file:**
Copy `pages/login.php` from this package to `Collabwork/pages/login.php`.
The file is identical to your original except for two small additions — no UI changes.

**Option B — Apply diffs manually:**

**Diff 1** — In `pages/login.php`, find:
```html
<div id="form-message" role="alert" aria-live="polite"></div>
```
Replace with:
```php
<?php
$loginSuccess = trim($_GET['success'] ?? '');
if ($loginSuccess !== ''): ?>
    <div id="form-message" class="form-success" role="alert" aria-live="polite">
        <?php echo htmlspecialchars($loginSuccess); ?>
    </div>
<?php else: ?>
    <div id="form-message" role="alert" aria-live="polite"></div>
<?php endif; ?>
```

**Diff 2** — In `pages/login.php`, find (just above `<div class="login-actions">`):
```html
<input type="hidden" id="csrf_token" name="csrf_token" ...>

<div class="login-actions">
```
Insert between them:
```html
<div style="text-align:right; margin-top:4px; margin-bottom:0;">
    <a href="forgot_password.php"
       style="font-size:13px; color:var(--green-mid); font-weight:600; text-decoration:none;">
        Forgot password?
    </a>
</div>
```

---

## Step 3 — Create the Database Table

Open your MySQL client (phpMyAdmin, MySQL Workbench, or CLI) and run:

```sql
-- Option A: CLI
mysql -u root ewallet < database/password_reset_tokens.sql

-- Option B: phpMyAdmin
-- Paste the contents of database/password_reset_tokens.sql into the SQL tab
-- Make sure the "ewallet" database is selected
```

The table looks like this:
```sql
password_reset_tokens
  id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY
  user_id      INT UNSIGNED  FK → users.id (CASCADE)
  token_hash   VARCHAR(64)   UNIQUE  (SHA-256 of the raw token)
  expires_at   DATETIME              (1 hour from creation)
  used         TINYINT(1)            (0=active, 1=consumed)
  created_at   DATETIME              DEFAULT CURRENT_TIMESTAMP
```

---

## Step 4 — Install PHPMailer via Composer

In your **Collabwork/** root folder (where `backend/` lives), run:

```bash
composer require phpmailer/phpmailer
```

This creates `vendor/` in your project root. The `send_reset.php` file already
includes the autoloader at the correct relative path.

> **No Composer?** Download the three PHPMailer class files from
> https://github.com/PHPMailer/PHPMailer/tree/master/src
> and place them in `Collabwork/vendor/phpmailer/phpmailer/src/`, then adjust the
> `require_once` in `send_reset.php` to point to each class manually.

---

## Step 5 — Configure Gmail SMTP

### 5a — Enable 2-Step Verification on your Google account
Go to: https://myaccount.google.com/security → Turn on 2-Step Verification.

### 5b — Generate an App Password
Go to: https://myaccount.google.com/apppasswords
- Select app: **Mail**
- Select device: **Other** → type "EWallet"
- Click **Generate** → copy the 16-character code (e.g. `abcd efgh ijkl mnop`)

### 5c — Edit config.local.php
Open `Collabwork/backend/config.local.php` and add these constants:

```php
// Full URL to your app root (no trailing slash)
define('APP_BASE_URL', 'http://localhost/Collabwork');

define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'your-gmail@gmail.com');
define('MAIL_PASSWORD',  'abcd efgh ijkl mnop');   // App Password (spaces OK)
define('MAIL_FROM',      'your-gmail@gmail.com');
define('MAIL_FROM_NAME', 'EWallet');
```

> **On a live server** set `APP_BASE_URL` to your real domain, e.g.:
> `define('APP_BASE_URL', 'https://yourdomain.com/Collabwork');`

### ⚠ Make sure config.local.php is in .gitignore
Your project already has `config.local.php` excluded — keep it that way.

---

## Security Features Implemented

| Feature | Implementation |
|---|---|
| Cryptographically secure token | `bin2hex(random_bytes(32))` — 256 bits entropy |
| Token stored as hash | `hash('sha256', $rawToken)` — raw token never saved |
| CSRF protection | All POST forms check `$_SESSION['csrf_token']` via `hash_equals()` |
| Prepared statements | Every DB query uses `bind_param()` — no SQL injection risk |
| Timing-safe comparison | `hash_equals()` used for CSRF; SHA-256 hash lookup for tokens |
| Token expiry | 1 hour enforced both in DB (`expires_at`) and PHP (`strtotime`) |
| Single-use tokens | `used = 1` set atomically in a transaction on successful reset |
| Old tokens invalidated | All unused tokens for a user are cleared on new request + on success |
| Rate limiting | Max 3 reset requests per email per 15 minutes |
| Email enumeration prevention | Generic success message whether email exists or not |
| Atomic DB update | `begin_transaction()` ensures password + token invalidation succeed together |
| Session destruction | `session_destroy()` after reset forces fresh login |
| Password hashing | `password_hash($password, PASSWORD_DEFAULT)` — bcrypt, matches existing system |

---

## Flow Diagram

```
[login.php]
    └─ "Forgot password?" link
         ↓
[forgot_password.php]  — user enters email
         ↓ POST
[send_reset.php]
    ├─ CSRF check
    ├─ rate-limit check (max 3 / 15 min)
    ├─ look up email in users table
    ├─ generate raw token → hash → store in password_reset_tokens
    ├─ send email with link: reset_password.php?token=<rawToken>
    └─ redirect → forgot_password.php?success=...
         ↓ (user clicks email link)
[reset_password.php?token=...]
    ├─ hash token → look up in DB
    ├─ check not used + not expired
    └─ show new-password form
         ↓ POST
[update_password.php]
    ├─ CSRF check
    ├─ re-verify token (TOCTOU-safe)
    ├─ validate password (min 8 chars, must match confirm)
    ├─ password_hash() → UPDATE users SET password_hash = ?
    ├─ mark token used = 1  (transaction)
    ├─ session_destroy()
    └─ redirect → login.php?success=...
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| "SMTP connect() failed" | Check App Password is correct; ensure 2FA is on |
| "Class PHPMailer not found" | Run `composer require phpmailer/phpmailer` in project root |
| Email goes to spam | Use a proper domain From address; consider SPF/DKIM records |
| "Invalid reset link" immediately | Check `APP_BASE_URL` is correct; ensure the DB table was created |
| Token expired too fast | Increase `+1 hour` in `send_reset.php` to `+2 hours` if needed |
| Gmail blocks the connection | In Gmail: Settings → Forwarding and POP/IMAP → enable IMAP |
