<?php
// backend/api/settings_api.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../connection.php';

$user_id = (int) $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET: load user settings ───────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $mysqli->prepare(
        'SELECT language, theme, notif_email, notif_push, notif_expense
         FROM user_settings WHERE user_id = ?'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Return defaults if no settings row yet
    echo json_encode($row ?? [
        'language'      => 'en-US',
        'theme'         => 'light',
        'notif_email'   => false,
        'notif_push'    => false,
        'notif_expense' => false,
    ]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── POST: save/update settings ────────────────────────────────────────────
if ($method === 'POST') {
    $language      = in_array($body['language'] ?? '', ['en-US', 'en-GB']) ? $body['language'] : 'en-US';
    $theme         = in_array($body['theme']    ?? '', ['light', 'dark', 'auto']) ? $body['theme'] : 'light';
    $notif_email   = !empty($body['notif_email'])   ? 1 : 0;
    $notif_push    = !empty($body['notif_push'])    ? 1 : 0;
    $notif_expense = !empty($body['notif_expense']) ? 1 : 0;

    $stmt = $mysqli->prepare(
        'INSERT INTO user_settings (user_id, language, theme, notif_email, notif_push, notif_expense)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             language      = VALUES(language),
             theme         = VALUES(theme),
             notif_email   = VALUES(notif_email),
             notif_push    = VALUES(notif_push),
             notif_expense = VALUES(notif_expense)'
    );
    $stmt->bind_param('issiii', $user_id, $language, $theme, $notif_email, $notif_push, $notif_expense);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
