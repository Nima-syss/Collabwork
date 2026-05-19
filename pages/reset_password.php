<?php
// pages/reset_password.php
// Place this file at: Collabwork/pages/reset_password.php
//
// Validates the token from the URL, then shows the new-password form.

session_start();
require_once('../backend/csrf_token.php');
require_once('../backend/connection.php');

// ── Grab and validate the raw token from the URL ──────────────────────────────
$rawToken = trim($_GET['token'] ?? '');

if ($rawToken === '') {
    header('Location: forgot_password.php?message=' . rawurlencode('Invalid or missing reset link.'));
    exit;
}

$tokenHash = hash('sha256', $rawToken);

// ── Look up the token in the DB ───────────────────────────────────────────────
$stmt = $mysqli->prepare(
    'SELECT prt.id, prt.user_id, prt.expires_at, prt.used
     FROM password_reset_tokens prt
     WHERE prt.token_hash = ?
     LIMIT 1'
);
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$stmt->store_result();

$tokenValid = false;
$tokenDbId  = null;
$tokenUserId = null;

if ($stmt->num_rows > 0) {
    $stmt->bind_result($tokenDbId, $tokenUserId, $expiresAt, $used);
    $stmt->fetch();

    if (!$used && strtotime($expiresAt) > time()) {
        $tokenValid = true;
    }
}
$stmt->close();

// ── If token is invalid/expired, redirect with a clear message ────────────────
if (!$tokenValid) {
    header(
        'Location: forgot_password.php?message=' .
        rawurlencode('This reset link is invalid or has expired. Please request a new one.')
    );
    exit;
}

// ── Collect flash messages (from update_password.php redirecting back here) ───
$errorMsg = trim($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWallet — Reset Password</title>
    <?php
    $themeCssDir     = '../assets/css/';
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
        <section class="login-intro" aria-labelledby="rp-intro-heading">
            <div class="login-intro__pill">Almost there</div>
            <h1 id="rp-intro-heading">Choose a new <span>password</span></h1>
            <p class="login-intro__lead">Pick something strong. Once updated you'll be taken straight to the login page.</p>
            <ul class="login-intro__list">
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Minimum 8 characters</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>At least one uppercase letter (A–Z)</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>At least one special character (e.g. !@#$%)</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Reset link invalidated on success</span>
                </li>
            </ul>
        </section>

        <div class="login-card">
            <div class="login-card__head">
                <h2>Set a new password</h2>
                <p>Know your password? <a href="login.php">Back to log in</a></p>
            </div>

            <?php if ($errorMsg !== ''): ?>
                <div id="form-message" role="alert"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php else: ?>
                <div id="form-message" role="alert"></div>
            <?php endif; ?>

            <form class="login-form" action="../backend/auth/update_password.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <!-- Pass the raw token forward so update_password.php can verify it again -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($rawToken); ?>">

                <div class="login-field">
                    <label class="login-label" for="password">New password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                        required
                        minlength="8"
                    >
                    <p id="login-password-error" class="form-error"></p>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password_confirm">Confirm new password</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        autocomplete="new-password"
                        placeholder="Re-enter your new password"
                        required
                        minlength="8"
                    >
                    <p id="login-confirm-error" class="form-error"></p>
                </div>

                <div class="login-actions">
                    <button type="submit" class="login-actions__submit">Update Password</button>
                </div>
            </form>

            <p class="login-foot">Back to <a href="login.php">Log in</a></p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/script_main.php'; ?>

<script>
// Client-side password validation (UX only — server validates too)
(function () {
    const form    = document.querySelector('.login-form');
    const passEl  = document.getElementById('password');
    const confEl  = document.getElementById('password_confirm');
    const passErr = document.getElementById('login-password-error');
    const confErr = document.getElementById('login-confirm-error');

    if (!form || !passEl || !confEl) return;

    function validatePassword(val) {
        if (val.length < 8)                    return 'Password must be at least 8 characters.';
        if (!/[A-Z]/.test(val))                return 'Password must contain at least one uppercase letter.';
        if (!/[^A-Za-z0-9]/.test(val))         return 'Password must contain at least one special character (e.g. !@#$%).';
        return '';
    }

    passEl.addEventListener('input', function () {
        passErr.textContent = validatePassword(passEl.value);
    });

    confEl.addEventListener('input', function () {
        confErr.textContent = passEl.value !== confEl.value ? 'Passwords do not match.' : '';
    });

    form.addEventListener('submit', function (e) {
        const pErr = validatePassword(passEl.value);
        if (pErr) {
            e.preventDefault();
            passErr.textContent = pErr;
            passEl.focus();
            return;
        }
        if (passEl.value !== confEl.value) {
            e.preventDefault();
            confErr.textContent = 'Passwords do not match.';
            confEl.focus();
        }
    });
})();
</script>
</body>
</html>
