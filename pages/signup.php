<?php
require_once('../backend/csrf_token.php');

$fullnameValue = $_GET['fullname'] ?? '';
$usernameValue = $_GET['username'] ?? '';
$emailValue = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWallet — Sign up</title>
    <?php
    $themeCssDir = '../assets/css/';
    $themeExtraLinks = [$themeCssDir . 'signup.css'];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body class="landing-body signup-page">

<div class="signup-page__bg" aria-hidden="true">
    <div class="signup-page__orb signup-page__orb--1"></div>
    <div class="signup-page__orb signup-page__orb--2"></div>
</div>

<header class="landing-header">
    <a href="landing.php" class="landing-logo signup-logo-link">
        <div class="landing-logo-badge landing-logo-badge--sm">
            <img src="../assets/icons/Wallet.png" class="landing-logo-icon" alt="">
        </div>
        <span>EWallet</span>
    </a>
    <nav class="landing-nav-buttons">
        <a href="landing.php" class="landing-btn landing-btn-login">Home</a>
        <a href="login.php" class="landing-btn landing-btn-signup">Log in</a>
    </nav>
</header>

<main class="signup-main">
    <div class="signup-shell">
        <section class="signup-intro" aria-labelledby="signup-intro-heading">
            <div class="signup-intro__pill">New account</div>
            <h1 id="signup-intro-heading">Start tracking your <span>money</span> with clarity</h1>
            <p class="signup-intro__lead">Create your EWallet profile in a minute. Same green palette as our homepage — calm, focused, and built for everyday spending.</p>
            <ul class="signup-intro__list">
                <li>
                    <span class="signup-intro__check" aria-hidden="true">✓</span>
                    <span>Budgets, expenses, and wallet in one place</span>
                </li>
                <li>
                    <span class="signup-intro__check" aria-hidden="true">✓</span>
                    <span>Secure sign-in and CSRF-protected forms</span>
                </li>
                <li>
                    <span class="signup-intro__check" aria-hidden="true">✓</span>
                    <span>Optional instant access after sign up</span>
                </li>
            </ul>
        </section>

        <div class="signup-card">
            <div class="signup-card__head">
                <h2>Create your account</h2>
                <p>Already registered? <a href="login.php">Log in</a></p>
            </div>

            <div id="form-message" role="alert" aria-live="polite"></div>

            <form class="signup-form" action="../backend/auth/signup_process.php" method="POST" novalidate>
                <div class="signup-field">
                    <label class="signup-label" for="fullname">Full name</label>
                    <input type="text" id="fullname" name="fullname" autocomplete="name" placeholder="e.g. Alex Morgan" value="<?php echo htmlspecialchars($fullnameValue); ?>">
                    <p id="signup-fullname-error" class="form-error"></p>
                </div>

                <div class="signup-field">
                    <label class="signup-label" for="signup-username">Username</label>
                    <input type="text" id="signup-username" name="username" autocomplete="username" placeholder="3–30 letters, numbers, _" value="<?php echo htmlspecialchars($usernameValue); ?>">
                    <p id="signup-username-error" class="form-error"></p>
                </div>

                <div class="signup-field">
                    <label class="signup-label" for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email" autocomplete="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($emailValue); ?>">
                    <p id="signup-email-error" class="form-error"></p>
                </div>

                <div class="signup-field signup-field--row">
                    <div>
                        <label class="signup-label" for="signup-password">Password</label>
                        <input type="password" id="signup-password" name="password" autocomplete="new-password" placeholder="Min. 8 chars, 1 uppercase, 1 special" required>
                        <p id="signup-password-error" class="form-error"></p>
                    </div>
                    <div>
                        <label class="signup-label" for="confirm-password">Confirm</label>
                        <input type="password" id="confirm-password" name="confirm_password" autocomplete="new-password" placeholder="Repeat password" required>
                        <p id="signup-confirm-error" class="form-error"></p>
                    </div>
                </div>

                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" id="signup_action" name="signup_action" value="register_only">

                <div class="signup-actions">
                    <button type="submit" class="signup-actions__primary">Create account</button>
                    <div class="signup-actions__row" aria-hidden="true"><span>or</span></div>
                    <button type="button" id="login-btn" class="signup-actions__secondary">Sign up &amp; continue to app</button>
                </div>
            </form>

            <p class="signup-footnote">By continuing you agree to use this wallet responsibly. <a href="landing.php">Learn more</a></p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>
