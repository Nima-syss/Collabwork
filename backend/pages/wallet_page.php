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
$bal = max(0.0, (float) $user['balance']);
if ($bal > WALLET_MAX_BALANCE) $bal = WALLET_MAX_BALANCE;
$_SESSION['total_balance'] = (float) $bal;

$transactions_stmt = $mysqli->prepare('SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 8');
$transactions_stmt->bind_param('i', $user_id);
$transactions_stmt->execute();
$transactions_result = $transactions_stmt->get_result();
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
$transactions_stmt->close();

$user_name = htmlspecialchars($user['fullname'] ?? 'Username');
$user_email = htmlspecialchars($user['email'] ?? 'email@yz.com');
$wallet_balance = number_format($bal, 2);
