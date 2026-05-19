<?php
// pages/forgot_password.php
// Place this file at: Collabwork/pages/forgot_password.php
require_once('../backend/csrf_token.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWallet — Forgot Password</title>
    <?php
    $themeCssDir  = '../assets/css/';
    $themeExtraLinks = [$themeCssDir . 'login.css'];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body class="landing-body login-page">

<div class="login-page__bg" aria-hidden="true">
    <div class="login-page__orb login-page__orb--1"></div>
    <div class="login-page__orb login-page__orb--2"></div>
</div>

<header class="landing-header">
    <a href="landing.php" class="landing-logo login-logo-link">
        <div class="landing-logo-badge landing-logo-badge--sm">
            <img src="../assets/icons/Wallet.png" class="landing-logo-icon" alt="EWallet">
        </div>
        <span>EWallet</span>
    </a>
    <nav class="landing-nav-buttons">
        <a href="login.php" class="landing-btn landing-btn-login">Log in</a>
        <a href="signup.php" class="landing-btn landing-btn-signup">Sign up</a>
    </nav>
</header>

<main class="login-main">
    <div class="login-shell">
        <section class="login-intro" aria-labelledby="fp-intro-heading">
            <div class="login-intro__pill">Account Recovery</div>
            <h1 id="fp-intro-heading">Reset your <span>password</span></h1>
            <p class="login-intro__lead">Enter the email address linked to your account and we'll send you a secure reset link — valid for 1 hour.</p>
            <ul class="login-intro__list">
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Secure one-time reset link via email</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Link expires in 60 minutes</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Used links are invalidated immediately</span>
                </li>
            </ul>
        </section>

        <div class="login-card">
            <div class="login-card__head">
                <h2>Forgot your password?</h2>
                <p>Remembered it? <a href="login.php">Back to log in</a></p>
            </div>

            <?php
            // Display flash messages passed via query string
            $message = trim($_GET['message'] ?? '');
            $success = trim($_GET['success'] ?? '');
            if ($message !== ''): ?>
                <div id="form-message" role="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php elseif ($success !== ''): ?>
                <div id="form-message" class="form-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
            <?php else: ?>
                <div id="form-message" role="alert"></div>
            <?php endif; ?>

            <form class="login-form" action="../backend/auth/send_reset.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="login-field">
                    <label class="login-label" for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                        required
                        value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>"
                    >
                </div>

                <div class="login-actions">
                    <button type="submit" class="login-actions__submit">Send Reset Link</button>
                </div>
            </form>

            <p class="login-foot">Don't have an account? <a href="signup.php">Sign up free</a></p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>
