<?php
// backend/auth/send_reset.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Place this file at: Collabwork/backend/auth/send_reset.php
//
// Requires PHPMailer. Install via Composer:
//   composer require phpmailer/phpmailer
// Then set your Gmail credentials in Collabwork/backend/config.local.php

session_start();
require_once '../connection.php';

// ── PHPMailer autoload ───────────────────────────────────────────────────────
// Adjust path if your vendor/ folder is in a different location.
require_once dirname(__DIR__, 1) . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

// ── Load local mail credentials ──────────────────────────────────────────────
// config.local.php defines: MAIL_HOST, MAIL_PORT, MAIL_USERNAME,
//                           MAIL_PASSWORD, MAIL_FROM, MAIL_FROM_NAME, APP_BASE_URL
$localCfg = dirname(__DIR__) . '/config.local.php';
if (is_file($localCfg)) {
    require_once $localCfg;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function redirectFP(string $type, string $msg, string $email = ''): never
{
    $p = [$type . '=' . rawurlencode($msg)];
    if ($email !== '') {
        $p[] = 'email=' . rawurlencode($email);
    }
    header('Location: ../../pages/forgot_password.php?' . implode('&', $p));
    exit;
}

// ── Guard: POST only ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/forgot_password.php');
    exit;
}

// ── CSRF check ───────────────────────────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    redirectFP('message', 'Invalid request. Please refresh and try again.');
}

// ── Input validation ─────────────────────────────────────────────────────────
$email = trim($_POST['email'] ?? '');
if ($email === '') {
    redirectFP('message', 'Please enter your email address.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectFP('message', 'Please enter a valid email address.', $email);
}

// ── Rate-limiting: max 3 unused tokens per email in the last 15 minutes ──────
$stmtRate = $mysqli->prepare(
    'SELECT COUNT(*) FROM password_reset_tokens prt
     JOIN users u ON u.id = prt.user_id
     WHERE u.email = ?
       AND prt.used = 0
       AND prt.created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
);
$stmtRate->bind_param('s', $email);
$stmtRate->execute();
$stmtRate->bind_result($recentCount);
$stmtRate->fetch();
$stmtRate->close();

if ($recentCount >= 3) {
    // Still show the "generic" success message so we don't leak whether the
    // email is rate-limited specifically.
    redirectFP(
        'success',
        'If that email is registered, a reset link has been sent. Check your inbox (and spam folder).'
    );
}

// ── Look up user — use generic message on miss (security: don't leak emails) ─
$stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$userId = null;
if ($found) {
    $stmt->bind_result($userId);
    $stmt->fetch();
}
$stmt->close();

$genericSuccess = 'If that email is registered, a reset link has been sent. Check your inbox (and spam folder).';

if (!$found) {
    // Do NOT reveal that the email doesn't exist.
    redirectFP('success', $genericSuccess);
}

// ── Generate a cryptographically secure token ─────────────────────────────────
$rawToken  = bin2hex(random_bytes(32));                 // 64-char hex, 256 bits entropy
$tokenHash = hash('sha256', $rawToken);                 // store only the hash in DB
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

// ── Invalidate any existing unused tokens for this user ───────────────────────
$stmtDel = $mysqli->prepare(
    'UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0'
);
$stmtDel->bind_param('i', $userId);
$stmtDel->execute();
$stmtDel->close();

// ── Insert new token ──────────────────────────────────────────────────────────
$stmtIns = $mysqli->prepare(
    'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
);
$stmtIns->bind_param('iss', $userId, $tokenHash, $expiresAt);
$stmtIns->execute();
$stmtIns->close();

// ── Build reset link ──────────────────────────────────────────────────────────
$baseUrl   = defined('APP_BASE_URL') ? rtrim(APP_BASE_URL, '/') : 'http://localhost/Collabwork';
$resetLink = $baseUrl . '/pages/reset_password.php?token=' . urlencode($rawToken);

// ── Send email via PHPMailer + Gmail SMTP ─────────────────────────────────────
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = defined('MAIL_HOST')     ? MAIL_HOST     : 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = defined('MAIL_USERNAME') ? MAIL_USERNAME : 'your-gmail@gmail.com';
    $mail->Password   = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = defined('MAIL_PORT')     ? (int) MAIL_PORT : 587;
    $mail->CharSet    = 'UTF-8';

    // Sender / recipient
    $fromAddress = defined('MAIL_FROM')      ? MAIL_FROM      : $mail->Username;
    $fromName    = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'EWallet';
    $mail->setFrom($fromAddress, $fromName);
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Reset your EWallet password';
    $mail->Body    = buildEmailHtml($resetLink, $fromName);
    $mail->AltBody = buildEmailText($resetLink, $fromName);

    $mail->send();
} catch (MailerException $e) {
    error_log('[EWallet] PHPMailer error for user_id=' . $userId . ': ' . $mail->ErrorInfo);
    redirectFP('message', 'MAIL ERROR: ' . $mail->ErrorInfo);
}

redirectFP('success', $genericSuccess);


// ── Email template helpers ────────────────────────────────────────────────────
function buildEmailHtml(string $link, string $appName): string
{
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f0f7f1;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f7f1;padding:40px 16px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1b3f24,#40c074);padding:28px 36px;">
            <p style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.03em;">{$appName}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 36px 16px;">
            <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#1b3f24;">Reset your password</h1>
            <p style="margin:0 0 24px;font-size:15px;color:#4a5a50;line-height:1.6;">
              We received a request to reset the password for your {$appName} account.
              Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.
            </p>
            <a href="{$safeLink}"
               style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#1b3f24,#40c074);
                      color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;
                      font-weight:700;letter-spacing:-0.01em;">
              Reset Password
            </a>
            <p style="margin:24px 0 0;font-size:13px;color:#8aad9a;line-height:1.5;">
              If the button doesn't work, copy and paste this link into your browser:<br>
              <a href="{$safeLink}" style="color:#40c074;word-break:break-all;">{$safeLink}</a>
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:16px 36px 28px;border-top:1px solid #e8f4ec;">
            <p style="margin:0;font-size:13px;color:#b0c4b8;line-height:1.5;">
              If you didn't request a password reset, you can safely ignore this email.
              Your password won't change unless you click the link above.
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function buildEmailText(string $link, string $appName): string
{
    return "Reset your {$appName} password\n\n"
         . "We received a request to reset your password.\n"
         . "Click the link below (expires in 1 hour):\n\n"
         . $link . "\n\n"
         . "If you didn't request this, you can ignore this email.\n";
}
