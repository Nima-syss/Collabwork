<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['balance' => 0]);
    exit;
}

require_once __DIR__ . '/../connection.php';

$stmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->bind_param('i', (int) $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$balance = (float) ($row['balance'] ?? 0);
$_SESSION['total_balance'] = $balance;

echo json_encode(['balance' => $balance]);              