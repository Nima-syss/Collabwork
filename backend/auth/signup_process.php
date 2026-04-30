<?php
session_start();
require_once '../connection.php';

function redirectToSignup($errorField, $message, $fullname = '', $username = '', $email = '') {
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
    if ($username !== '') {
        $params[] = 'username=' . rawurlencode($username);
    }
    if ($email !== '') {
        $params[] = 'email=' . rawurlencode($email);
    }
    header('Location: ../../pages/signup.php' . (count($params) ? '?' . implode('&', $params) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $signupAction = $_POST['signup_action'] ?? 'register_only';

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        redirectToSignup('general', 'Invalid CSRF token. Please refresh the page.', $fullname, $username, $email);
    }

    if ($fullname === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        redirectToSignup('general', 'Please fill out all fields.', $fullname, $username, $email);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectToSignup('email', 'Please enter a valid email address.', $fullname, $username, $email);
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        redirectToSignup('username', 'Username must be 3-30 characters and use only letters, numbers, and underscores.', $fullname, $username, $email);
    }

    if ($password !== $confirmPassword) {
        redirectToSignup('confirm', 'Passwords do not match.', $fullname, $username, $email);
    }

    if (strlen($password) < 4) {
        redirectToSignup('password', 'Password must be at least 4 characters long.', $fullname, $username, $email);
    }

    $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirectToSignup('email', 'Email is already registered. Please log in instead.', $fullname, $username, $email);
    }
    $stmt->close();

    $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirectToSignup('username', 'Username is already taken. Please choose another one.', $fullname, $username, $email);
    }
    $stmt->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $mysqli->prepare('INSERT INTO users (fullname, username, email, balance, password_hash, created_at) VALUES (?, ?, ?, 0.00, ?, NOW())');
    $insert->bind_param('ssss', $fullname, $username, $email, $passwordHash);
    if ($insert->execute()) {
        $userId = $insert->insert_id;
        $insert->close();

        if ($signupAction === 'register_and_login') {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $fullname;
            $_SESSION['user_username'] = $username;
            $_SESSION['user_email'] = $email;
            $_SESSION['total_balance'] = 0;

            header('Location: ../../pages/dashboard.php');
            exit;
        }

        header('Location: ../../pages/login.php?success=' . rawurlencode('Successfully registered. Please log in.') . '&email=' . rawurlencode($email));
        exit;
    }

    $insert->close();
    redirectToSignup('general', 'Unable to create account. Please try again later.', $fullname, $username, $email);
    exit;
}

$message = $_GET['message'] ?? '';
$errorField = $_GET['signup_error'] ?? '';
$successMessage = $_GET['success'] ?? '';
$fullnameValue = $_GET['fullname'] ?? '';
$usernameValue = $_GET['username'] ?? '';
$emailValue = $_GET['email'] ?? '';
?>
