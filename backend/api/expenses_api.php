<?php
// backend/api/expenses_api.php
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

// ── Allowed categories — must match budgets ENUM exactly ─────────────────
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

// ── GET ───────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $filter   = $_GET['filter'] ?? 'monthly';
    $interval = match($filter) {
        'daily'  => 'INTERVAL 1 DAY',
        'weekly' => 'INTERVAL 7 DAY',
        // "Monthly" view shows a rolling 12-month window so older months (e.g. Feb) are still visible.
        'monthly' => 'INTERVAL 12 MONTH',
        default  => 'INTERVAL 12 MONTH',
    };

    $stmt = $mysqli->prepare(
        "SELECT id, category, amount, note, expense_date, created_at
         FROM expenses
         WHERE user_id = ? AND expense_date >= DATE_SUB(CURDATE(), $interval)
         ORDER BY expense_date DESC, id DESC"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $bstmt = $mysqli->prepare('SELECT COALESCE(SUM(monthly_limit),0) AS total_budget FROM budgets WHERE user_id = ?');
    $bstmt->bind_param('i', $user_id);
    $bstmt->execute();
    $budget = $bstmt->get_result()->fetch_assoc();
    $bstmt->close();

    // ── Graph points (separate from list interval) ───────────────────────
    // Return an aggregated series suitable for a monthly-style line chart.
    // - daily:  1 point (today)
    // - weekly: last 7 days
    // - monthly:last 12 months (monthly totals)
    $graph_points = [];
    if ($filter === 'monthly') {
        $gstmt = $mysqli->prepare(
            "SELECT DATE_FORMAT(expense_date, '%Y-%m') AS period, COALESCE(SUM(amount),0) AS total
             FROM expenses
             WHERE user_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY period
             ORDER BY period ASC"
        );
        $gstmt->bind_param('i', $user_id);
        $gstmt->execute();
        $rows = $gstmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $gstmt->close();

        $map = [];
        foreach ($rows as $r) {
            $p = (string)($r['period'] ?? '');
            if ($p === '') continue;
            $map[$p] = (float)($r['total'] ?? 0);
        }

        // Pad to last 12 months so the line chart matches a Jan–Dec style series.
        $start = new DateTime('first day of this month');
        $start->modify('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $key = $start->format('Y-m');
            $graph_points[] = [
                'label' => $key,
                'value' => (float)($map[$key] ?? 0),
            ];
            $start->modify('+1 month');
        }
    } else {
        $gInterval = ($filter === 'weekly') ? 'INTERVAL 7 DAY' : 'INTERVAL 1 DAY';
        $gstmt = $mysqli->prepare(
            "SELECT expense_date AS period, COALESCE(SUM(amount),0) AS total
             FROM expenses
             WHERE user_id = ? AND expense_date >= DATE_SUB(CURDATE(), $gInterval)
             GROUP BY period
             ORDER BY period ASC"
        );
        $gstmt->bind_param('i', $user_id);
        $gstmt->execute();
        $rows = $gstmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $gstmt->close();

        $map = [];
        foreach ($rows as $r) {
            $p = (string)($r['period'] ?? '');
            if ($p === '') continue;
            $map[$p] = (float)($r['total'] ?? 0);
        }

        if ($filter === 'weekly') {
            $d = new DateTime('today');
            $d->modify('-6 days');
            for ($i = 0; $i < 7; $i++) {
                $key = $d->format('Y-m-d');
                $graph_points[] = [
                    'label' => $key,
                    'value' => (float)($map[$key] ?? 0),
                ];
                $d->modify('+1 day');
            }
        } else {
            $key = (new DateTime('today'))->format('Y-m-d');
            $graph_points[] = [
                'label' => $key,
                'value' => (float)($map[$key] ?? 0),
            ];
        }
    }

    echo json_encode([
        'expenses'     => $expenses,
        'total_spent'  => (float) array_sum(array_column($expenses, 'amount')),
        'total_budget' => (float) $budget['total_budget'],
        'categories'   => VALID_CATEGORIES,
        'graph_points' => $graph_points,
    ]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Shared validator ──────────────────────────────────────────────────────
function validateInput(array $body, bool $requireId = false): array {
    $errors   = [];
    $category = trim($body['category'] ?? '');
    $amount   = filter_var($body['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
    $date     = trim($body['expense_date'] ?? '');

    if ($requireId && (int)($body['id'] ?? 0) <= 0) $errors[] = 'Invalid expense ID.';
    if (!in_array($category, VALID_CATEGORIES, true)) $errors[] = 'Invalid category.';
    if ($amount === false || $amount <= 0)            $errors[] = 'A positive amount is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

    return [
        'errors'   => $errors,
        'id'       => (int)($body['id'] ?? 0),
        'category' => $category,
        'amount'   => $amount === false ? 0.0 : (float) $amount,
        'note'     => trim($body['note'] ?? ''),
        'date'     => $date,
    ];
}

// ── POST: create ──────────────────────────────────────────────────────────
if ($method === 'POST') {
    $v = validateInput($body);
    if ($v['errors']) { http_response_code(400); echo json_encode(['error' => implode(' ', $v['errors'])]); exit; }

    $mysqli->begin_transaction();

    try {
        // Debit wallet balance (prevent overspend)
        $debit_stmt = $mysqli->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
        $debit_stmt->bind_param('did', $v['amount'], $user_id, $v['amount']);
        $debit_stmt->execute();
        $affected = $debit_stmt->affected_rows;
        $debit_stmt->close();

        if ($affected !== 1) {
            $mysqli->rollback();
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient balance for this expense.']);
            exit;
        }

        $stmt = $mysqli->prepare('INSERT INTO expenses (user_id, category, amount, note, expense_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('isdss', $user_id, $v['category'], $v['amount'], $v['note'], $v['date']);

        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to insert expense');
        }
        $new_id = $stmt->insert_id;
        $stmt->close();

        // Sync budget used
        $upd = $mysqli->prepare('UPDATE budgets SET used = used + ? WHERE user_id = ? AND category = ?');
        $upd->bind_param('dis', $v['amount'], $user_id, $v['category']);
        $upd->execute();
        $upd->close();

        // Wallet activity entry
        $type = 'expense';
        $desc = 'Expense: ' . $v['category'] . ($v['note'] !== '' ? ' - ' . $v['note'] : '');
        $tx_stmt = $mysqli->prepare('INSERT INTO transactions (user_id, related_user_id, type, amount, description) VALUES (?, NULL, ?, ?, ?)');
        $tx_stmt->bind_param('isds', $user_id, $type, $v['amount'], $desc);
        $tx_stmt->execute();
        $tx_stmt->close();

        $mysqli->commit();

        $bal_stmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ?');
        $bal_stmt->bind_param('i', $user_id);
        $bal_stmt->execute();
        $bal = $bal_stmt->get_result()->fetch_assoc();
        $bal_stmt->close();

        $_SESSION['total_balance'] = (float) ($bal['balance'] ?? 0);

        echo json_encode([
            'success' => true,
            'id'      => $new_id,
            'balance' => (float) ($bal['balance'] ?? 0),
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
    }
    exit;
}

// ── PUT: update ───────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $v = validateInput($body, true);
    if ($v['errors']) { http_response_code(400); echo json_encode(['error' => implode(' ', $v['errors'])]); exit; }

    $old_stmt = $mysqli->prepare('SELECT amount, category FROM expenses WHERE id = ? AND user_id = ?');
    $old_stmt->bind_param('ii', $v['id'], $user_id);
    $old_stmt->execute();
    $old = $old_stmt->get_result()->fetch_assoc();
    $old_stmt->close();

    if (!$old) { http_response_code(404); echo json_encode(['error' => 'Expense not found.']); exit; }

    $old_amount = (float) $old['amount'];
    $diff = $v['amount'] - $old_amount;

    $mysqli->begin_transaction();

    try {
        // Adjust wallet balance based on diff
        if ($diff > 0) {
            $debit_stmt = $mysqli->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
            $debit_stmt->bind_param('did', $diff, $user_id, $diff);
            $debit_stmt->execute();
            $affected = $debit_stmt->affected_rows;
            $debit_stmt->close();

            if ($affected !== 1) {
                $mysqli->rollback();
                http_response_code(400);
                echo json_encode(['error' => 'Insufficient balance for this expense update.']);
                exit;
            }
        } elseif ($diff < 0) {
            $refund = abs($diff);
            $credit_stmt = $mysqli->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
            $credit_stmt->bind_param('di', $refund, $user_id);
            $credit_stmt->execute();
            $credit_stmt->close();
        }

        $stmt = $mysqli->prepare('UPDATE expenses SET category=?, amount=?, note=?, expense_date=? WHERE id=? AND user_id=?');
        $stmt->bind_param('sdssii', $v['category'], $v['amount'], $v['note'], $v['date'], $v['id'], $user_id);

        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to update expense');
        }
        $stmt->close();

        if ($old['category'] === $v['category']) {
            $upd  = $mysqli->prepare('UPDATE budgets SET used = GREATEST(0, used + ?) WHERE user_id = ? AND category = ?');
            $upd->bind_param('dis', $diff, $user_id, $v['category']);
            $upd->execute();
            $upd->close();
        } else {
            $sub = $mysqli->prepare('UPDATE budgets SET used = GREATEST(0, used - ?) WHERE user_id = ? AND category = ?');
            $sub->bind_param('dis', $old_amount, $user_id, $old['category']);
            $sub->execute();
            $sub->close();

            $add = $mysqli->prepare('UPDATE budgets SET used = used + ? WHERE user_id = ? AND category = ?');
            $add->bind_param('dis', $v['amount'], $user_id, $v['category']);
            $add->execute();
            $add->close();
        }

        $mysqli->commit();

        $bal_stmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ?');
        $bal_stmt->bind_param('i', $user_id);
        $bal_stmt->execute();
        $bal = $bal_stmt->get_result()->fetch_assoc();
        $bal_stmt->close();
        $_SESSION['total_balance'] = (float) ($bal['balance'] ?? 0);

        echo json_encode([
            'success' => true,
            'balance' => (float) ($bal['balance'] ?? 0),
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
    }
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID.']); exit; }

    $old_stmt = $mysqli->prepare('SELECT amount, category FROM expenses WHERE id = ? AND user_id = ?');
    $old_stmt->bind_param('ii', $id, $user_id);
    $old_stmt->execute();
    $old = $old_stmt->get_result()->fetch_assoc();
    $old_stmt->close();

    if (!$old) { http_response_code(404); echo json_encode(['error' => 'Expense not found.']); exit; }

    $old_amount = (float) $old['amount'];

    $mysqli->begin_transaction();

    try {
        $stmt = $mysqli->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);

        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to delete expense');
        }
        $stmt->close();

        $upd = $mysqli->prepare('UPDATE budgets SET used = GREATEST(0, used - ?) WHERE user_id = ? AND category = ?');
        $upd->bind_param('dis', $old_amount, $user_id, $old['category']);
        $upd->execute();
        $upd->close();

        // Refund wallet balance since the expense is removed
        $credit_stmt = $mysqli->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $credit_stmt->bind_param('di', $old_amount, $user_id);
        $credit_stmt->execute();
        $credit_stmt->close();

        $mysqli->commit();

        $bal_stmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ?');
        $bal_stmt->bind_param('i', $user_id);
        $bal_stmt->execute();
        $bal = $bal_stmt->get_result()->fetch_assoc();
        $bal_stmt->close();
        $_SESSION['total_balance'] = (float) ($bal['balance'] ?? 0);

        echo json_encode([
            'success' => true,
            'balance' => (float) ($bal['balance'] ?? 0),
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
