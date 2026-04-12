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

// ── Allowed categories — must match expenses ENUM exactly ─────────────────
const VALID_CATEGORIES = [
    'Foods',
    'Transportation',
    'Housing',
    'Shopping',
    'Health and Wellness',
    'Education',
    'Entertainment',
    'Others',
];

// ── GET: fetch all budgets for this user ──────────────────────────────────
if ($method === 'GET') {
    $stmt = $mysqli->prepare(
        'SELECT category, monthly_limit, used
         FROM budgets WHERE user_id = ? ORDER BY category ASC'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'budgets'    => $result,
        'categories' => VALID_CATEGORIES,
    ]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── POST: create or update a budget ──────────────────────────────────────
if ($method === 'POST') {
    $category = trim($body['category'] ?? '');
    $limit    = filter_var($body['limit'] ?? 0, FILTER_VALIDATE_FLOAT);
    $used     = filter_var($body['used']  ?? 0, FILTER_VALIDATE_FLOAT);

    if (!in_array($category, VALID_CATEGORIES, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category. Must be one of: ' . implode(', ', VALID_CATEGORIES)]);
        exit;
    }

    if ($limit === false || $limit < 0 || $used === false || $used < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Limit and used must be non-negative numbers.']);
        exit;
    }

    $stmt = $mysqli->prepare(
        'INSERT INTO budgets (user_id, category, monthly_limit, used)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit), used = VALUES(used)'
    );
    $stmt->bind_param('isdd', $user_id, $category, $limit, $used);

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

// ── DELETE: remove a budget by category ──────────────────────────────────
if ($method === 'DELETE') {
    $category = trim($body['category'] ?? '');

    if (!in_array($category, VALID_CATEGORIES, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category.']);
        exit;
    }

    $stmt = $mysqli->prepare(
        'DELETE FROM budgets WHERE user_id = ? AND category = ?'
    );
    $stmt->bind_param('is', $user_id, $category);

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
