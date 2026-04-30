<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../config.php';

$load_error = '';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $funding_source = $_POST['fundingSource'] ?? 'visa';
    $allowed_sources = ['visa', 'bank'];

    if ($amount === false || $amount === null || $amount <= 0) {
        $load_error = 'Please enter a valid amount.';
    } elseif ($amount > WALLET_MAX_SINGLE_LOAD) {
        $load_error = 'Amount exceeds the allowed limit.';
    } elseif (!in_array($funding_source, $allowed_sources, true)) {
        $load_error = 'Please choose a valid funding source.';
    } else {
        $max_balance = WALLET_MAX_BALANCE;
        $update_stmt = $mysqli->prepare('UPDATE users SET balance = GREATEST(0, balance) + ? WHERE id = ? AND (GREATEST(0, balance) + ?) <= ?');
        $update_stmt->bind_param('didd', $amount, $user_id, $amount, $max_balance);
        $update_stmt->execute();
        $affected = $update_stmt->affected_rows;
        $update_stmt->close();

        if ($affected !== 1) {
            $load_error = 'Balance limit exceeded. Please enter a smaller amount.';
        } else {
        $description = 'Loaded money via ' . ($funding_source === 'bank' ? 'Bank' : 'Visa debit card');
        $transaction_stmt = $mysqli->prepare('INSERT INTO transactions (user_id, related_user_id, type, amount, description) VALUES (?, NULL, ?, ?, ?)');
        $type = 'load';
        $transaction_stmt->bind_param('isds', $user_id, $type, $amount, $description);
        $transaction_stmt->execute();
        $transaction_stmt->close();

        $user['balance'] = max(0.0, (float) $user['balance']) + $amount;
        if ($user['balance'] > WALLET_MAX_BALANCE) $user['balance'] = WALLET_MAX_BALANCE;
        $_SESSION['total_balance'] = (float) $user['balance'];
        $_SESSION['last_funding_source'] = $funding_source;
        $_SESSION['last_loaded_amount'] = $amount;

        header('Location: dashboard.php');
        exit;
        }
    }
}

$user_name = htmlspecialchars($user['fullname'] ?? ($_SESSION['user_name'] ?? 'User'));
$user_email = htmlspecialchars($user['email'] ?? ($_SESSION['user_email'] ?? ''));
$selected_funding_source = $_POST['fundingSource'] ?? ($_SESSION['last_funding_source'] ?? 'visa');
$selected_funding_label = $selected_funding_source === 'bank' ? 'Bank' : 'Visa debit card';
$current_amount = isset($_POST['amount']) ? htmlspecialchars((string) $_POST['amount']) : '';
