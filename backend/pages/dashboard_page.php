<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../config.php';

$user_id = (int) $_SESSION['user_id'];
$user_stmt = $mysqli->prepare('SELECT fullname, email, balance FROM users WHERE id = ?');
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['user_name'] = $user['fullname'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['total_balance'] = (float) $user['balance'];

$user_name = htmlspecialchars($user['fullname']);
$user_email = htmlspecialchars($user['email']);
$total_balance = max(0.0, (float) $user['balance']);
if ($total_balance > WALLET_MAX_BALANCE) $total_balance = WALLET_MAX_BALANCE;
$_SESSION['total_balance'] = (float) $total_balance;

// Monthly spending (last 1 month) + budget summary
$spent_stmt = $mysqli->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total_spent
     FROM expenses
     WHERE user_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"
);
$spent_stmt->bind_param('i', $user_id);
$spent_stmt->execute();
$spent_row = $spent_stmt->get_result()->fetch_assoc();
$spent_stmt->close();
$total_spent = (float) ($spent_row['total_spent'] ?? 0);
if ($total_spent < 0) $total_spent = 0.0;

$budget_stmt = $mysqli->prepare('SELECT COALESCE(SUM(monthly_limit), 0) AS total_budget FROM budgets WHERE user_id = ?');
$budget_stmt->bind_param('i', $user_id);
$budget_stmt->execute();
$budget_row = $budget_stmt->get_result()->fetch_assoc();
$budget_stmt->close();
$total_budget = (float) ($budget_row['total_budget'] ?? 0);
if ($total_budget < 0) $total_budget = 0.0;

// NOTE: This is "remaining budget" for the current period, not wallet balance.
$remaining_budget = max(0.0, $total_budget - $total_spent);
// Backwards-compatible name used by pages/dashboard.php
$money_saved = $remaining_budget;

// Recent transactions for dashboard list
$tx_stmt = $mysqli->prepare('SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 6');
$tx_stmt->bind_param('i', $user_id);
$tx_stmt->execute();
$transactions = $tx_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tx_stmt->close();
