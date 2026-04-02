<?php
require_once('../backend/csrf_token.php');

$fullnameValue = $_GET['fullname'] ?? '';
$emailValue = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EWallet Sign Up</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page signup-page">

<header class="navbar">
    <h2>EWallet</h2>
</header>

<div class="container">
    <div class="signup-box">
        <h2>Join EWallet</h2>
        <div id="form-message" class="form-error"></div>

      <form action="../backend/auth/signup_process.php" method="POST">
            <label>Full Name:</label>
            <input type="text" id="fullname" name="fullname" placeholder="Enter your name" value="<?php echo htmlspecialchars($fullnameValue); ?>">
            <p id="signup-fullname-error" class="form-error"></p>

            <label>Email Address:</label>
            <input type="email" id="signup-email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($emailValue); ?>">
            <p id="signup-email-error" class="form-error"></p>

            <div class="row">
                <div>
                    <label>Password:</label>
                    <input type="password" id="signup-password" name="password" placeholder="Min 4 characters" required>
                    <p id="signup-password-error" class="form-error"></p>
                </div>

                <div>
                    <label>Confirm:</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm password" required>
                    <p id="signup-confirm-error" class="form-error"></p>
                </div>
            </div>

        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="buttons">
                <button type="submit" class="signup-btn">Sign Up</button>
                <span>OR</span>
                <button type="button" id="login-btn" class="login-btn" type=submit>Log In</button>
            </div>
        </form>
    </div>
</div>


<script src="../assets/js/script.js"></script>

</body>

</html>
