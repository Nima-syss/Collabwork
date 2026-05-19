<?php
// backend/api/notifications_api.php
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

// ── GET: fetch notifications (latest 30, return unread count) ─────────────
if ($method === 'GET') {
    $stmt = $mysqli->prepare(
        'SELECT id, type, title, body, amount, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 30'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $unread = 0;
    foreach ($rows as &$r) {
        $r['is_read'] = (bool) $r['is_read'];
        $r['amount']  = $r['amount'] !== null ? (float) $r['amount'] : null;
        if (!$r['is_read']) $unread++;
    }
    unset($r);

    echo json_encode(['notifications' => $rows, 'unread' => $unread]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── POST: mark one or all as read ─────────────────────────────────────────
if ($method === 'POST') {
    $action = trim($body['action'] ?? '');

    if ($action === 'mark_all_read') {
        $stmt = $mysqli->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mark_read') {
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid id']);
            exit;
        }
        $stmt = $mysqli->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
