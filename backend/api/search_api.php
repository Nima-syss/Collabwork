<?php
// backend/api/search_api.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../connection.php';

$user_id = (int) $_SESSION['user_id'];
$q = trim((string)($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode([
        'query' => '',
        'results' => [
            'expenses' => [],
            'budgets' => [],
            'transactions' => [],
        ],
    ]);
    exit;
}

// Keep query reasonable for LIKE scans
if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($q) > 80) $q = mb_substr($q, 0, 80);
} else {
    if (strlen($q) > 80) $q = substr($q, 0, 80);
}

$like = '%' . $q . '%';

// Expenses (category or note)
$expenses = [];
$stmt = $mysqli->prepare(
    "SELECT id, category, note, amount, expense_date
     FROM expenses
     WHERE user_id = ?
       AND (category LIKE ? OR note LIKE ?)
     ORDER BY expense_date DESC, id DESC
     LIMIT 10"
);
if ($stmt) {
    $stmt->bind_param('iss', $user_id, $like, $like);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Note: this endpoint is used for global searching by expense category/note only.

echo json_encode([
    'query' => $q,
    'results' => [
        'expenses' => $expenses,
        // Keep keys for frontend compatibility, but return empty arrays.
        'budgets' => [],
        'transactions' => [],
    ],
]);
