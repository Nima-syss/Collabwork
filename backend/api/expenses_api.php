<?php
// Updated version for expenses and budget page
// backend/api/expenses_api.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    'Unbudgeted',
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

    if ($v['errors']) {
        http_response_code(400);
        echo json_encode([
            'error' => implode(' ', $v['errors'])
        ]);
        exit;
    }

    $expense_month = date('Y-m-01', strtotime($v['date']));

    $mysqli->begin_transaction();

    try {

        // ─────────────────────────────────────────────────────────────
        // CHECK CATEGORY BUDGET
        // ─────────────────────────────────────────────────────────────

        $budget_stmt = $mysqli->prepare(
            'SELECT monthly_limit, used
             FROM budgets
             WHERE user_id = ?
             AND category = ?
             AND budget_month = ?'
        );

        $budget_stmt->bind_param(
            'iss',
            $user_id,
            $v['category'],
            $expense_month
        );

        $budget_stmt->execute();

        $cat_row = $budget_stmt->get_result()->fetch_assoc();

        $budget_stmt->close();

        // ─────────────────────────────────────────────────────────────
        // CHECK IF ANY CATEGORY BUDGET EXISTS THIS MONTH (Unbudgeted excluded)
        // ─────────────────────────────────────────────────────────────

        // Count real category budgets only (Unbudgeted is unlimited — does not satisfy "set a budget")
        $real_stmt = $mysqli->prepare(
            'SELECT COUNT(*) AS total
             FROM budgets
             WHERE user_id = ?
             AND budget_month = ?
             AND category <> ?'
        );
        $unb = 'Unbudgeted';
        $real_stmt->bind_param('iss', $user_id, $expense_month, $unb);
        $real_stmt->execute();
        $real_row = $real_stmt->get_result()->fetch_assoc();
        $real_stmt->close();

        $has_budget = (int)($real_row['total'] ?? 0) > 0;

        // ─────────────────────────────────────────────────────────────
        // SOFT WARNING IF NO BUDGET
        // ─────────────────────────────────────────────────────────────

        $force_no_budget = !empty($body['force_no_budget']);

        if (!$has_budget && !$force_no_budget && $v['category'] !== 'Unbudgeted') {

            $mysqli->rollback();

            http_response_code(400);

            echo json_encode([
                'warning' => true,
                'no_budget_warning' => true,
                'month' => date('F Y', strtotime($expense_month)),
                'error' => 'No category budget set for this month. Continue anyway?'
            ]);

            exit;
        }

        // ─────────────────────────────────────────────────────────────
        // BUDGET LIMIT CHECK
        // ─────────────────────────────────────────────────────────────

        $force_over_budget = !empty($body['force_over_budget']);

        if ($cat_row) {

            $limit = (float)$cat_row['monthly_limit'];

            $used = (float)$cat_row['used'];

            $new_used = $used + $v['amount'];

            // Unbudgeted category: monthly_limit stays 0 = no cap (track spending only)
            $is_unbudgeted_cat = ($v['category'] === 'Unbudgeted');

            if (
                !$is_unbudgeted_cat &&
                $limit > 0 &&
                $new_used > $limit &&
                !$force_over_budget
            ) {

                $mysqli->rollback();

                http_response_code(400);

                echo json_encode([
                    'warning' => true,
                    'budget_exceeded' => true,
                    'category' => $v['category'],
                    'over_by' => round($new_used - $limit, 2),
                    'limit' => $limit,
                    'error' => 'Budget exceeded. Continue anyway?'
                ]);

                exit;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // WALLET BALANCE CHECK
        // ─────────────────────────────────────────────────────────────

        $wallet_stmt = $mysqli->prepare(
            'UPDATE users
             SET balance = balance - ?
             WHERE id = ?
             AND balance >= ?'
        );

        $wallet_stmt->bind_param(
            'did',
            $v['amount'],
            $user_id,
            $v['amount']
        );

        $wallet_stmt->execute();

        if ($wallet_stmt->affected_rows !== 1) {

            $wallet_stmt->close();

            $mysqli->rollback();

            http_response_code(400);

            echo json_encode([
                'error' => 'Insufficient balance. Load money from your bank or card, or reduce the amount.'
            ]);

            exit;
        }

        $wallet_stmt->close();

        // ─────────────────────────────────────────────────────────────
        // INSERT EXPENSE
        // ─────────────────────────────────────────────────────────────

        $insert_stmt = $mysqli->prepare(
            'INSERT INTO expenses
             (user_id, category, amount, note, expense_date)
             VALUES (?, ?, ?, ?, ?)'
        );

    $expense_category = $cat_row
        ? $v['category']
        : 'Unbudgeted';

    $insert_stmt->bind_param(
        'isdss',
        $user_id,
        $expense_category,
        $v['amount'],
        $v['note'],
        $v['date']
    );

        if (!$insert_stmt->execute()) {
            throw new RuntimeException('Failed to insert expense');
        }

        $expense_id = $insert_stmt->insert_id;

        $insert_stmt->close();

        // ─────────────────────────────────────────────────────────────
        // TRACK BUDGET USAGE
        // ─────────────────────────────────────────────────────────────

        if ($cat_row) {

            // Normal budget tracking

            $update_budget = $mysqli->prepare(
                'UPDATE budgets
                 SET used = used + ?
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?'
            );

            $update_budget->bind_param(
                'diss',
                $v['amount'],
                $user_id,
                $v['category'],
                $expense_month
            );

            $update_budget->execute();

            $update_budget->close();

        } else {

            // ─────────────────────────────────────────────
            // TRACK UNBUDGETED EXPENSES
            // ─────────────────────────────────────────────

            $unbudgeted_category = 'Unbudgeted';

            // create tracker row if not exists

            $check_unbudgeted = $mysqli->prepare(
                'SELECT id
                 FROM budgets
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?'
            );

            $check_unbudgeted->bind_param(
                'iss',
                $user_id,
                $unbudgeted_category,
                $expense_month
            );

            $check_unbudgeted->execute();

            $exists = $check_unbudgeted
                ->get_result()
                ->fetch_assoc();

            $check_unbudgeted->close();

            if (!$exists) {

                $create_unbudgeted = $mysqli->prepare(
                    'INSERT INTO budgets
                     (user_id, category, monthly_limit, used, budget_month)
                     VALUES (?, ?, 0, 0, ?)'
                );

                $create_unbudgeted->bind_param(
                    'iss',
                    $user_id,
                    $unbudgeted_category,
                    $expense_month
                );

                $create_unbudgeted->execute();

                $create_unbudgeted->close();
            }

            // add usage

            $update_unbudgeted = $mysqli->prepare(
                'UPDATE budgets
                 SET used = used + ?
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?'
            );

            $update_unbudgeted->bind_param(
                'diss',
                $v['amount'],
                $user_id,
                $unbudgeted_category,
                $expense_month
            );

            $update_unbudgeted->execute();

            $update_unbudgeted->close();
        }

        // ─────────────────────────────────────────────────────────────
        // TRANSACTION LOG
        // ─────────────────────────────────────────────────────────────

        $type = 'expense';

        $desc =
            'Expense: ' .
            $v['category'] .
            ($v['note'] ? ' - ' . $v['note'] : '');

        $tx_stmt = $mysqli->prepare(
            'INSERT INTO transactions
             (user_id, related_user_id, type, amount, description)
             VALUES (?, NULL, ?, ?, ?)'
        );

        $tx_stmt->bind_param(
            'isds',
            $user_id,
            $type,
            $v['amount'],
            $desc
        );

        $tx_stmt->execute();

        $tx_stmt->close();

        // ─────────────────────────────────────────────────────────────
        // COMMIT
        // ─────────────────────────────────────────────────────────────

        $mysqli->commit();

        // refresh session balance

        $bal_stmt = $mysqli->prepare(
            'SELECT balance
             FROM users
             WHERE id = ?'
        );

        $bal_stmt->bind_param('i', $user_id);

        $bal_stmt->execute();

        $bal = $bal_stmt->get_result()->fetch_assoc();

        $bal_stmt->close();

        $_SESSION['total_balance'] =
            (float)($bal['balance'] ?? 0);

        echo json_encode([
            'success' => true,
            'id' => $expense_id,
            'balance' => (float)($bal['balance'] ?? 0),
            'tracked_as' => $cat_row
                ? $v['category']
                : 'Unbudgeted'
        ]);

    } catch (Throwable $e) {

        $mysqli->rollback();

        http_response_code(500);

        echo json_encode([
            'error' => 'Database error.',
            'details' => $e->getMessage()
        ]);
    }

    exit;
}

// ── PUT: update ───────────────────────────────────────────────────────────
if ($method === 'PUT') {

    $v = validateInput($body, true);

    if ($v['errors']) {
        http_response_code(400);
        echo json_encode(['error' => implode(' ', $v['errors'])]);
        exit;
    }

    $old_stmt = $mysqli->prepare(
        'SELECT amount, category, expense_date 
         FROM expenses 
         WHERE id = ? AND user_id = ?'
    );

    $old_stmt->bind_param('ii', $v['id'], $user_id);
    $old_stmt->execute();

    $old = $old_stmt->get_result()->fetch_assoc();

    $old_stmt->close();

    if (!$old) {
        http_response_code(404);
        echo json_encode(['error' => 'Expense not found.']);
        exit;
    }

    $old_amount = (float)$old['amount'];

    $diff = $v['amount'] - $old_amount;

    $old_expense_month = substr($old['expense_date'], 0, 7) . '-01';
    $new_expense_month = substr($v['date'], 0, 7) . '-01';

    $mysqli->begin_transaction();

    try {

        // ── Adjust wallet balance ───────────────────────────────────────
        if ($diff > 0) {

            $debit_stmt = $mysqli->prepare(
                'UPDATE users 
                 SET balance = balance - ? 
                 WHERE id = ? AND balance >= ?'
            );

            $debit_stmt->bind_param('did', $diff, $user_id, $diff);

            $debit_stmt->execute();

            $affected = $debit_stmt->affected_rows;

            $debit_stmt->close();

            if ($affected !== 1) {

                $mysqli->rollback();

                http_response_code(400);

                echo json_encode([
                    'error' => 'Insufficient balance for this expense update. Load money or lower the amount.'
                ]);

                exit;
            }

        } elseif ($diff < 0) {

            $refund = abs($diff);

            $credit_stmt = $mysqli->prepare(
                'UPDATE users 
                 SET balance = balance + ? 
                 WHERE id = ?'
            );

            $credit_stmt->bind_param('di', $refund, $user_id);

            $credit_stmt->execute();

            $credit_stmt->close();
        }

        // ── Budget limit check ─────────────────────────────────────────
        $target_category = $v['category'];

        $bchk2 = $mysqli->prepare(
            'SELECT monthly_limit, used 
             FROM budgets 
             WHERE user_id = ? 
             AND category = ? 
             AND budget_month = ?'
        );

        $bchk2->bind_param(
            'iss',
            $user_id,
            $target_category,
            $new_expense_month
        );

        $bchk2->execute();

        $brow2 = $bchk2->get_result()->fetch_assoc();

        $bchk2->close();

        if (!$brow2 && $target_category !== 'Unbudgeted') {

            $mysqli->rollback();

            http_response_code(400);

            echo json_encode([
                'error' => 'No budget found for this category and month.'
            ]);

            exit;
        }

        if (!$brow2 && $target_category === 'Unbudgeted') {

            $mk_unb = $mysqli->prepare(
                'INSERT INTO budgets (user_id, category, monthly_limit, used, budget_month)
                 VALUES (?, ?, 0, 0, ?)'
            );
            $unb_cat = 'Unbudgeted';
            $mk_unb->bind_param('iss', $user_id, $unb_cat, $new_expense_month);
            $mk_unb->execute();
            $mk_unb->close();

            $bchk2 = $mysqli->prepare(
                'SELECT monthly_limit, used
                 FROM budgets
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?'
            );
            $bchk2->bind_param('iss', $user_id, $target_category, $new_expense_month);
            $bchk2->execute();
            $brow2 = $bchk2->get_result()->fetch_assoc();
            $bchk2->close();
        }

        $force = !empty($body['force_over_budget']);

        $limit2 = (float)($brow2['monthly_limit'] ?? 0);

        $current_used2 = (float)($brow2['used'] ?? 0);

        // projected budget usage
        $projected = $current_used2 - $old_amount + $v['amount'];

        $is_unbudgeted_put = ($target_category === 'Unbudgeted');

        if (!$is_unbudgeted_put && $limit2 > 0 && $projected > $limit2 && !$force) {

            $mysqli->rollback();

            http_response_code(400);

            $over2 = number_format($projected - $limit2, 2);

            echo json_encode([
                'warning'         => true,
                'budget_exceeded' => true,
                'category'        => $target_category,
                'over_by'         => $over2,
                'limit'           => number_format($limit2, 2),
                'error'           => 'You are NRP ' . $over2 .
                                     ' over your NRP ' .
                                     number_format($limit2, 2) .
                                     ' budget for ' .
                                     $target_category .
                                     '. Do you still want to update this expense?',
            ]);

            exit;
        }

        // ── Update expense ─────────────────────────────────────────────
        $stmt = $mysqli->prepare(
            'UPDATE expenses
             SET category = ?, amount = ?, note = ?, expense_date = ?
             WHERE id = ? AND user_id = ?'
        );

        $stmt->bind_param(
            'sdssii',
            $v['category'],
            $v['amount'],
            $v['note'],
            $v['date'],
            $v['id'],
            $user_id
        );

        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to update expense');
        }

        $stmt->close();

        // ── Update budget usage ────────────────────────────────────────
        if (
            $old['category'] === $v['category']
            && $old_expense_month === $new_expense_month
        ) {

            $budget_category = $old['category'];

            // check if original category budget existed
            $check_budget = $mysqli->prepare(
                'SELECT id
                FROM budgets
                WHERE user_id = ?
                AND category = ?
                AND budget_month = ?'
            );

            $check_budget->bind_param(
                'iss',
                $user_id,
                $old['category'],
                $old_expense_month
            );

            $check_budget->execute();

            $exists = $check_budget
                ->get_result()
                ->fetch_assoc();

            $check_budget->close();

            if (!$exists) {
                $budget_category = 'Unbudgeted';
            }

            $upd = $mysqli->prepare(
                'UPDATE budgets
                SET used = GREATEST(0, used - ?)
                WHERE user_id = ?
                AND category = ?
                AND budget_month = ?'
            );

            $upd->bind_param(
                'diss',
                $old_amount,
                $user_id,
                $budget_category,
                $old_expense_month
            );

            $upd->execute();

            $upd->close();

        } else {

            // remove old usage
            $sub = $mysqli->prepare(
                'UPDATE budgets
                 SET used = GREATEST(0, used - ?)
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?'
            );

            $sub->bind_param(
                'diss',
                $old_amount,
                $user_id,
                $old['category'],
                $old_expense_month
            );

            $sub->execute();

            $sub->close();

            // add new usage
            $add = $mysqli->prepare(
                'UPDATE budgets
                 SET used = used + ?
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?'
            );

            $add->bind_param(
                'diss',
                $v['amount'],
                $user_id,
                $v['category'],
                $new_expense_month
            );

            $add->execute();

            $add->close();
        }

        $mysqli->commit();

        // ── Refresh session balance ────────────────────────────────────
        $bal_stmt = $mysqli->prepare(
            'SELECT balance FROM users WHERE id = ?'
        );

        $bal_stmt->bind_param('i', $user_id);

        $bal_stmt->execute();

        $bal = $bal_stmt->get_result()->fetch_assoc();

        $bal_stmt->close();

        $_SESSION['total_balance'] = (float)($bal['balance'] ?? 0);

        echo json_encode([
            'success' => true,
            'balance' => (float)($bal['balance'] ?? 0),
        ]);

    } catch (Throwable $e) {

        $mysqli->rollback();

        http_response_code(500);

        echo json_encode([
            'error' => 'Database error.',
            'details' => $e->getMessage()
        ]);
    }

    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID.']); exit; }

    $old_stmt = $mysqli->prepare('SELECT amount, category, expense_date FROM expenses WHERE id = ? AND user_id = ?');
    $old_stmt->bind_param('ii', $id, $user_id);
    $old_stmt->execute();
    $old = $old_stmt->get_result()->fetch_assoc();
    $old_stmt->close();

    if (!$old) { http_response_code(404); echo json_encode(['error' => 'Expense not found.']); exit; }

    $old_amount = (float) $old['amount'];
    $del_expense_month = substr($old['expense_date'], 0, 7) . '-01';

    $mysqli->begin_transaction();

    try {
        $stmt = $mysqli->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);

        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to delete expense');
        }
        $stmt->close();

        $upd = $mysqli->prepare('UPDATE budgets SET used = GREATEST(0, used - ?) WHERE user_id = ? AND category = ? AND budget_month = ?');
        $upd->bind_param('diss', $old_amount, $user_id, $old['category'], $del_expense_month);
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

