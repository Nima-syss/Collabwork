<?php
require_once('../backend/csrf_token.php');

$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Page - EWallet</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page login-page">

    <div class="header"> 
    <img src="../assets/icons/Wallet.png" class="header-icon" alt="Wallet Icon"> EWallet </div>

    <div class="login-wrapper">
        <div class="login-container">
            <h2>Welcome Back</h2>

            <img src="../assets/icons/Wallet.png" alt="Wallet Icon" class="wallet-icon">
            <div id="form-message" class="form-error"></div>

            <form action="../backend/auth/login_process.php" method="POST">

                <label>Email Address:</label>
                <div class="input-wrapper">
                    <img src="../assets/icons/username.png" class="input-icon" alt="User Icon">
                    <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <p id="login-email-error" class="form-error"></p>

                <label>Password:</label>
                <div class="input-wrapper">
                    <img src="../assets/icons/Lock.png" class="input-icon" alt="Lock Icon">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <p id="login-password-error" class="form-error"></p>

        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <button type="submit">Log In</button>

                <p style="text-align: center; margin-top: 15px; font-size: 14px;">
                    Don't have an account? <a href="signup.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Sign Up</a>
                </p>

            </form>

        </div>
    </div>


<script src="../assets/js/script.js"></script>

</body>
</html>
