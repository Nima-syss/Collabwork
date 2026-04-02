<?php
session_start();
require_once '../connection.php';

function redirectToLogin($errorField, $message, $email = '') {
    $params = [];
    if ($errorField !== '') {
        $params[] = 'login_error=' . rawurlencode($errorField);
    }
    if ($message !== '') {
        $params[] = 'message=' . rawurlencode($message);
    }
    if ($email !== '') {
        $params[] = 'email=' . rawurlencode($email);
    }
    header('Location: ../../pages/login.php' . (count($params) ? '?' . implode('&', $params) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        redirectToLogin('general', 'Invalid CSRF token. Please refresh the page.', $email);
    }

    if ($email === '' || $password === '') {
        redirectToLogin('general', 'Please fill out all fields.', $email);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectToLogin('email', 'Please enter a valid email address.', $email);
    }

    $stmt = $mysqli->prepare('SELECT id, fullname, password_hash FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        redirectToLogin('general', 'Invalid email.', $email);
    }

    $stmt->bind_result($id, $fullname, $passwordHash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($password, $passwordHash)) {
        redirectToLogin('password', 'Invalid password.', $email);
    }

    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $fullname;
    $_SESSION['user_email'] = $email;

    header('Location: ../../pages/dashboard.php');
    exit;
}

$message = $_GET['message'] ?? '';
$errorField = $_GET['login_error'] ?? '';
$successMessage = $_GET['success'] ?? '';
$email = $_GET['email'] ?? '';
?>
