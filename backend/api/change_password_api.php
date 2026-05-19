<?php
// backend/api/change_password_api.php
// Handles Change Password requests for authenticated users.
// Follows the same session/connection/response style as settings_api.php.

session_start();
header('Content-Type: application/json');

// ── Auth guard (same pattern as settings_api.php) ────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Only accept POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../connection.php';

$user_id = (int) $_SESSION['user_id'];

// ── Read JSON body ────────────────────────────────────────────────────────
$body           = json_decode(file_get_contents('php://input'), true) ?? [];
$current_pass   = $body['current_password']  ?? '';
$new_pass       = $body['new_password']      ?? '';
$confirm_pass   = $body['confirm_password']  ?? '';

// ── Server-side validation ────────────────────────────────────────────────

// 1. Prevent empty fields
if ($current_pass === '' || $new_pass === '' || $confirm_pass === '') {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// 2. Prevent mismatched passwords
if ($new_pass !== $confirm_pass) {
    echo json_encode(['error' => 'New passwords do not match.']);
    exit;
}

// 3. Minimum password length (matches signup_process.php minimum of 4)
if (strlen($new_pass) < 4) {
    echo json_encode(['error' => 'New password must be at least 4 characters.']);
    exit;
}

// 4. Prevent reusing the same password
if ($current_pass === $new_pass) {
    echo json_encode(['error' => 'New password must be different from your current password.']);
    exit;
}

// ── Fetch current hash from DB (prepared statement — no SQL injection) ────
$stmt = $mysqli->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(500);
    echo json_encode(['error' => 'User not found.']);
    exit;
}

// 5. Verify current password using password_verify() (same as login_process.php)
if (!password_verify($current_pass, $row['password_hash'])) {
    echo json_encode(['error' => 'Current password is incorrect.']);
    exit;
}

// ── Hash new password (same function used in signup_process.php) ──────────
$new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

// ── Update the password in the DB ─────────────────────────────────────────
$update = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$update->bind_param('si', $new_hash, $user_id);

if ($update->execute()) {
    $update->close();
    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
} else {
    $update->close();
    http_response_code(500);
    echo json_encode(['error' => 'Could not update password. Please try again.']);
}
