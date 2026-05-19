<?php
require_once('../backend/csrf_token.php');

$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWallet — Log in</title>
    <?php
    $themeCssDir = '../assets/css/';
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
        <a href="landing.php" class="landing-btn landing-btn-login">Home</a>
        <a href="signup.php" class="landing-btn landing-btn-signup">Sign up</a>
    </nav>
</header>

<main class="login-main">
    <div class="login-shell">
        <section class="login-intro" aria-labelledby="login-intro-heading">
            <div class="login-intro__pill">Welcome back</div>
            <h1 id="login-intro-heading">Pick up where you left <span>off</span></h1>
            <p class="login-intro__lead">Log in to manage your wallet, budgets, and expenses — same calm green experience as the rest of EWallet.</p>
            <ul class="login-intro__list">
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Encrypted session after successful sign-in</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>CSRF-protected login form</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Instant access to your dashboard</span>
                </li>
            </ul>
        </section>

        <div class="login-card">
            <div class="login-card__head">
                <h2>Log in to your account</h2>
                <p>New here? <a href="signup.php">Create an account</a></p>
            </div>

            <?php
            // ── CHANGE 1: success message from password reset ──────────────────
            $loginSuccess = trim($_GET['success'] ?? '');
            if ($loginSuccess !== ''): ?>
                <div id="form-message" class="form-success" role="alert" aria-live="polite">
                    <?php echo htmlspecialchars($loginSuccess); ?>
                </div>
            <?php else: ?>
                <div id="form-message" role="alert" aria-live="polite"></div>
            <?php endif; ?>

            <form class="login-form" action="../backend/auth/login_process.php" method="POST">
                <div class="login-field">
                    <label class="login-label" for="email">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($email); ?>">
                    <p id="login-email-error" class="form-error"></p>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Your password" required>
                    <p id="login-password-error" class="form-error"></p>
                </div>

                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <?php // ── CHANGE 2: Forgot password link ──────────────────────────────── ?>
                <div style="text-align:right; margin-top:4px; margin-bottom:0;">
                    <a href="forgot_password.php"
                       style="font-size:13px; color:var(--green-mid); font-weight:600; text-decoration:none;">
                        Forgot password?
                    </a>
                </div>

                <div class="login-actions">
                    <button type="submit" class="login-actions__submit">Log in</button>
                </div>
            </form>

            <p class="login-foot">Prefer to browse first? <a href="landing.php">Return to home</a></p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>
