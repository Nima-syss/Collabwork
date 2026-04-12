<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../backend/connection.php';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare('SELECT id, fullname, password_hash FROM admins WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id']   = $row['id'];
        $_SESSION['admin_name'] = $row['fullname'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – EWallet</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body class="auth-page login-page">
<div class="header">
    <img src="../../assets/icons/Wallet.png" class="header-icon" alt="Wallet Icon"> EWallet Admin
</div>
<div class="login-wrapper">
    <div class="login-container">
        <h2>Admin Login</h2>
        <img src="../../assets/icons/Wallet.png" alt="Wallet Icon" class="wallet-icon">
        <?php if ($error): ?>
            <p class="form-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label>Email Address:</label>
            <div class="input-wrapper">
                <img src="../../assets/icons/username.png" class="input-icon" alt="User Icon">
                <input type="email" name="email" placeholder="Admin email" required>
            </div>
            <label>Password:</label>
            <div class="input-wrapper">
                <img src="../../assets/icons/Lock.png" class="input-icon" alt="Lock Icon">
                <input type="password" name="password" placeholder="Admin password" required>
            </div>
            <button type="submit">Log In</button>
        </form>
    </div>
</div>
</body>
</html>
