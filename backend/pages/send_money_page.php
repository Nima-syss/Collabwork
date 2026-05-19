<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../config.php';

$user_id = (int) $_SESSION['user_id'];
$send_error = '';
$send_success = '';

$user_stmt = $mysqli->prepare('SELECT fullname, username, email, balance FROM users WHERE id = ?');
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$current_user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$current_user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['user_name'] = $current_user['fullname'];
$_SESSION['user_username'] = $current_user['username'];
$_SESSION['user_email'] = $current_user['email'];
$_SESSION['total_balance'] = (float) $current_user['balance'];

$recipient_username_value = trim($_POST['recipient_username'] ?? '');
$amount_value = trim($_POST['amount'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_username = trim($_POST['recipient_username'] ?? '');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if ($recipient_username === '') {
        $send_error = 'Please enter a recipient username.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $recipient_username)) {
        $send_error = 'Please enter a valid recipient username.';
    } elseif (strcasecmp($recipient_username, $current_user['username']) === 0) {
        $send_error = 'You cannot transfer money to your own account.';
    } elseif ($amount === false || $amount === null || $amount <= 0) {
        $send_error = 'Please enter a valid amount.';
    } elseif ($amount > (float) $current_user['balance']) {
        $send_error = 'Insufficient balance for this transfer.';
    } else {
        $recipient_stmt = $mysqli->prepare('SELECT id, fullname FROM users WHERE username = ? LIMIT 1');
        $recipient_stmt->bind_param('s', $recipient_username);
        $recipient_stmt->execute();
        $recipient_result = $recipient_stmt->get_result();
        $recipient = $recipient_result->fetch_assoc();
        $recipient_stmt->close();

        if ($recipient) {
            $mysqli->begin_transaction();

            try {
                $debit_stmt = $mysqli->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
                $debit_stmt->bind_param('did', $amount, $user_id, $amount);
                $debit_stmt->execute();
                $affected = $debit_stmt->affected_rows;
                $debit_stmt->close();

                if ($affected !== 1) {
                    $mysqli->rollback();
                    $send_error = 'Insufficient balance for this transfer.';
                } else {
                    $recipient_id = (int) $recipient['id'];

                    $max_balance = WALLET_MAX_BALANCE;
                    $credit_stmt = $mysqli->prepare('UPDATE users SET balance = GREATEST(0, balance) + ? WHERE id = ? AND (GREATEST(0, balance) + ?) <= ?');
                    $credit_stmt->bind_param('didd', $amount, $recipient_id, $amount, $max_balance);
                    $credit_stmt->execute();
                    $credit_ok = $credit_stmt->affected_rows === 1;
                    $credit_stmt->close();

                    if (!$credit_ok) {
                        $mysqli->rollback();
                        $send_error = 'Recipient balance limit exceeded.';
                    } else {
                    $send_type = 'send';
                    $send_description = 'Sent money to @' . $recipient_username;
                    $send_tx_stmt = $mysqli->prepare('INSERT INTO transactions (user_id, related_user_id, type, amount, description) VALUES (?, ?, ?, ?, ?)');
                    $send_tx_stmt->bind_param('iisds', $user_id, $recipient_id, $send_type, $amount, $send_description);
                    $send_tx_stmt->execute();
                    $send_tx_stmt->close();

                    $receive_type = 'receive';
                    $receive_description = 'Received money from @' . $current_user['username'];
                    $receive_tx_stmt = $mysqli->prepare('INSERT INTO transactions (user_id, related_user_id, type, amount, description) VALUES (?, ?, ?, ?, ?)');
                    $receive_tx_stmt->bind_param('iisds', $recipient_id, $user_id, $receive_type, $amount, $receive_description);
                    $receive_tx_stmt->execute();
                    $receive_tx_stmt->close();

                    $mysqli->commit();

                    require_once __DIR__ . '/../notifications_helper.php';
                    create_notification($mysqli, $user_id, 'money_sent',
                        'Money sent',
                        'NRP ' . number_format($amount, 2) . ' sent to ' . htmlspecialchars($recipient['fullname']) . ' (@' . $recipient_username . ').',
                        $amount
                    );
                    create_notification($mysqli, $recipient_id, 'money_received',
                        'Money received',
                        'NRP ' . number_format($amount, 2) . ' received from ' . htmlspecialchars($current_user['fullname']) . ' (@' . $current_user['username'] . ').',
                        $amount
                    );

                    $current_user['balance'] -= $amount;
                    $_SESSION['total_balance'] = (float) $current_user['balance'];
                    header('Location: ../pages/wallet.php?message=' . rawurlencode('Transfer completed successfully.') . '&type=success');
                    exit;
                    }
                }
            } catch (Throwable $e) {
                $mysqli->rollback();
                $send_error = 'Unable to process the transfer right now.';
            }
        } else {
            $send_error = 'Recipient username was not found.';
        }
    }
}

$user_name = htmlspecialchars($current_user['fullname'] ?? ($_SESSION['user_name'] ?? 'User'));
$user_email = htmlspecialchars($current_user['email'] ?? ($_SESSION['user_email'] ?? ''));
$total_balance = (float) $current_user['balance'];
$current_username = htmlspecialchars($current_user['username'] ?? '');
