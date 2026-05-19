<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (basename($_SERVER['PHP_SELF'] ?? '') === 'csrf_token.php') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $_SESSION['csrf_token'];
    exit;
}
?>
