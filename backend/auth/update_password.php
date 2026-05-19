<?php
// backend/auth/update_password.php
// Place this file at: Collabwork/backend/auth/update_password.php
//
// Verifies the token a second time (TOCTOU-safe), hashes the new password,
// updates the users table, and marks the token as used.

session_start();
require_once '../connection.php';

// ── Helper: redirect back to reset_password.php with an error ─────────────────
function redirectResetError(string $token, string $msg): never
{
    header(
        'Location: ../../pages/reset_password.php'
        . '?token=' . urlencode($token)
        . '&error=' . rawurlencode($msg)
    );
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
    header('Location: ../../pages/forgot_password.php?message=' . rawurlencode('Invalid request. Please try again.'));
    exit;
}

// ── Read inputs ───────────────────────────────────────────────────────────────
$rawToken       = trim($_POST['token'] ?? '');
$password       = $_POST['password']         ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

if ($rawToken === '') {
    header('Location: ../../pages/forgot_password.php?message=' . rawurlencode('Invalid reset link.'));
    exit;
}

// ── Validate new password ─────────────────────────────────────────────────────
if ($password === '') {
    redirectResetError($rawToken, 'Please enter a new password.');
}
if (strlen($password) < 8) {
    redirectResetError($rawToken, 'Password must be at least 8 characters.');
}
if (!preg_match('/[A-Z]/', $password)) {
    redirectResetError($rawToken, 'Password must contain at least one uppercase letter.');
}
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    redirectResetError($rawToken, 'Password must contain at least one special character (e.g. !@#$%).');
}
if ($password !== $passwordConfirm) {
    redirectResetError($rawToken, 'Passwords do not match.');
}

// ── Re-verify the token (prevents TOCTOU race & replay) ──────────────────────
$tokenHash = hash('sha256', $rawToken);

$stmt = $mysqli->prepare(
    'SELECT prt.id, prt.user_id, prt.expires_at, prt.used
     FROM password_reset_tokens prt
     WHERE prt.token_hash = ?
     LIMIT 1'
);
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    header(
        'Location: ../../pages/forgot_password.php?message=' .
        rawurlencode('This reset link is invalid or has expired. Please request a new one.')
    );
    exit;
}

$stmt->bind_result($tokenDbId, $userId, $expiresAt, $used);
$stmt->fetch();
$stmt->close();

if ($used || strtotime($expiresAt) <= time()) {
    header(
        'Location: ../../pages/forgot_password.php?message=' .
        rawurlencode('This reset link has already been used or has expired. Please request a new one.')
    );
    exit;
}

// ── Hash the new password ─────────────────────────────────────────────────────
// Uses the same bcrypt algorithm as signup_process.php (PASSWORD_DEFAULT)
$newHash = password_hash($password, PASSWORD_DEFAULT);
if ($newHash === false) {
    error_log('[EWallet] password_hash() failed for user_id=' . $userId);
    redirectResetError($rawToken, 'An unexpected error occurred. Please try again.');
}

// ── Use a transaction: update password + mark token used atomically ───────────
$mysqli->begin_transaction();
try {
    // 1. Update the user's password
    $stmtPass = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmtPass->bind_param('si', $newHash, $userId);
    if (!$stmtPass->execute()) {
        throw new RuntimeException('Failed to update password: ' . $stmtPass->error);
    }
    $stmtPass->close();

    // 2. Mark this token as used
    $stmtUsed = $mysqli->prepare('UPDATE password_reset_tokens SET used = 1 WHERE id = ?');
    $stmtUsed->bind_param('i', $tokenDbId);
    if (!$stmtUsed->execute()) {
        throw new RuntimeException('Failed to invalidate token: ' . $stmtUsed->error);
    }
    $stmtUsed->close();

    // 3. Also invalidate ALL other unused tokens for this user (security hygiene)
    $stmtAll = $mysqli->prepare(
        'UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0'
    );
    $stmtAll->bind_param('i', $userId);
    $stmtAll->execute();
    $stmtAll->close();

    $mysqli->commit();
} catch (RuntimeException $e) {
    $mysqli->rollback();
    error_log('[EWallet] update_password transaction failed: ' . $e->getMessage());
    redirectResetError($rawToken, 'An unexpected error occurred. Please try again.');
}

// ── Destroy any existing session so user has to log in fresh ──────────────────
session_unset();
session_destroy();
session_start();

// ── Redirect to login with a success message ──────────────────────────────────
header(
    'Location: ../../pages/login.php'
    . '?success=' . rawurlencode('Your password has been updated. Please log in with your new password.')
);
exit;
