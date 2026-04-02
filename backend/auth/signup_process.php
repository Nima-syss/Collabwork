<?php
session_start();
require_once '../connection.php';

function redirectToSignup($errorField, $message, $fullname = '', $email = '') {
    $params = [];
    if ($errorField !== '') {
        $params[] = 'signup_error=' . rawurlencode($errorField);
    }
    if ($message !== '') {
        $params[] = 'message=' . rawurlencode($message);
    }
    if ($fullname !== '') {
        $params[] = 'fullname=' . rawurlencode($fullname);
    }
    if ($email !== '') {
        $params[] = 'email=' . rawurlencode($email);
    }
    header('Location: ../../pages/signup.php' . (count($params) ? '?' . implode('&', $params) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        redirectToSignup('general', 'Invalid CSRF token. Please refresh the page.', $fullname, $email);
    }

    if ($fullname === '' || $email === '' || $password === '' || $confirmPassword === '') {
        redirectToSignup('general', 'Please fill out all fields.', $fullname, $email);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectToSignup('email', 'Please enter a valid email address.', $fullname, $email);
    }

    if ($password !== $confirmPassword) {
        redirectToSignup('confirm', 'Passwords do not match.', $fullname, $email);
    }

    if (strlen($password) < 4) {
        redirectToSignup('password', 'Password must be at least 4 characters long.', $fullname, $email);
    }

    $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirectToSignup('email', 'Email is already registered. Please log in instead.', $fullname, $email);
    }
    $stmt->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $mysqli->prepare('INSERT INTO users (fullname, email, password_hash, created_at) VALUES (?, ?, ?, NOW())');
    $insert->bind_param('sss', $fullname, $email, $passwordHash);
    if ($insert->execute()) {
        $userId = $insert->insert_id;
        $insert->close();

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullname;
        $_SESSION['user_email'] = $email;

        header('Location: ../../pages/dashboard.php');
        exit;
    }

    $insert->close();
    redirectToSignup('general', 'Unable to create account. Please try again later.', $fullname, $email);
    exit;
}

$message = $_GET['message'] ?? '';
$errorField = $_GET['signup_error'] ?? '';
$successMessage = $_GET['success'] ?? '';
$fullnameValue = $_GET['fullname'] ?? '';
$emailValue = $_GET['email'] ?? '';
?>
