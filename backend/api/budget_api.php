<?php
// backend/api/budget_api.php
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

const VALID_CATEGORIES = [
    'Foods',
    'Transportation',
    'Housing',
    'Shopping',
    'Health and Wellness',
    'Education',
    'Entertainment',
    'Others',
    'Unbudgeted',
];

function parseMonth(string $raw): string|false {
    $trimmed = trim($raw);
    if (preg_match('/^(\d{4})-(\d{2})/', $trimmed, $m)) {
        $y = (int) $m[1]; $mo = (int) $m[2];
        if ($y >= 2000 && $y <= 2100 && $mo >= 1 && $mo <= 12)
            return sprintf('%04d-%02d-01', $y, $mo);
    }
    return false;
}

if ($method === 'GET') {
    $month = parseMonth($_GET['month'] ?? date('Y-m'));
    if (!$month) { http_response_code(400); echo json_encode(['error' => 'Invalid month format. Use YYYY-MM.']); exit; }

    $stmt = $mysqli->prepare('SELECT category, monthly_limit, used, created_at, updated_at FROM budgets WHERE user_id = ? AND budget_month = ? ORDER BY category ASC');
    $stmt->bind_param('is', $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode(['budgets' => $result, 'month' => substr($month, 0, 7), 'categories' => VALID_CATEGORIES]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $category = trim($body['category'] ?? '');
    $limit    = filter_var($body['limit'] ?? 0, FILTER_VALIDATE_FLOAT);
    $used     = filter_var($body['used']  ?? 0, FILTER_VALIDATE_FLOAT);
    $month    = parseMonth($body['month'] ?? date('Y-m'));

    if (!in_array($category, VALID_CATEGORIES, true)) { http_response_code(400); echo json_encode(['error' => 'Invalid category.']); exit; }
    if ($limit === false || $limit < 0 || $used === false || $used < 0) { http_response_code(400); echo json_encode(['error' => 'Limit and used must be non-negative numbers.']); exit; }
    if (!$month) { http_response_code(400); echo json_encode(['error' => 'Invalid month format. Use YYYY-MM.']); exit; }

    $stmt = $mysqli->prepare('INSERT INTO budgets (user_id, budget_month, category, monthly_limit, used) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit), used = VALUES(used)');
    $stmt->bind_param('issdd', $user_id, $month, $category, $limit, $used);
    if ($stmt->execute()) { $stmt->close(); echo json_encode(['success' => true]); }
    else { $stmt->close(); http_response_code(500); echo json_encode(['error' => 'Database error.']); }
    exit;
}

if ($method === 'DELETE') {
    $category = trim($body['category'] ?? '');
    $month    = parseMonth($body['month'] ?? date('Y-m'));

    if (!in_array($category, VALID_CATEGORIES, true)) { http_response_code(400); echo json_encode(['error' => 'Invalid category.']); exit; }
    if (!$month) { http_response_code(400); echo json_encode(['error' => 'Invalid month format. Use YYYY-MM.']); exit; }

    $stmt = $mysqli->prepare('DELETE FROM budgets WHERE user_id = ? AND category = ? AND budget_month = ?');
    $stmt->bind_param('iss', $user_id, $category, $month);
    if ($stmt->execute()) { $stmt->close(); echo json_encode(['success' => true]); }
    else { $stmt->close(); http_response_code(500); echo json_encode(['error' => 'Database error.']); }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
