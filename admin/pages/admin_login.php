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
    <?php
    $themeCssDir = '../../assets/css/';
    $themeExtraLinks = [$themeCssDir . 'login.css'];
    include __DIR__ . '/../../components/head_theme.php';
    ?>
</head>
<body class="landing-body login-page">

<div class="login-page__bg" aria-hidden="true">
    <div class="login-page__orb login-page__orb--1"></div>
    <div class="login-page__orb login-page__orb--2"></div>
</div>

<header class="landing-header">
    <a href="../../pages/landing.php" class="landing-logo login-logo-link">
        <div class="landing-logo-badge landing-logo-badge--sm">
            <img src="../../assets/icons/Wallet.png" class="landing-logo-icon" alt="EWallet">
        </div>
        <span>EWallet Admin</span>
    </a>
    <nav class="landing-nav-buttons">
        <a href="../../pages/landing.php" class="landing-btn landing-btn-login">Site home</a>
        <a href="../../pages/login.php" class="landing-btn landing-btn-signup">User log in</a>
    </nav>
</header>

<main class="login-main">
    <div class="login-shell">
        <section class="login-intro" aria-labelledby="admin-login-heading">
            <div class="login-intro__pill">Administrator</div>
            <h1 id="admin-login-heading">Secure <span>console</span> access</h1>
            <p class="login-intro__lead">Sign in to manage users, transactions, and budgets. Styling matches the main EWallet app with the same green palette and dark mode support.</p>
            <ul class="login-intro__list">
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Session-based authentication</span>
                </li>
                <li>
                    <span class="login-intro__check" aria-hidden="true">✓</span>
                    <span>Same theme toggle as the user app (when logged in)</span>
                </li>
            </ul>
        </section>

        <div class="login-card">
            <div class="login-card__head">
                <h2>Admin sign in</h2>
                <p>User account? <a href="../../pages/login.php">Log in as user</a></p>
            </div>

            <?php if ($error): ?>
                <div class="form-error" role="alert" style="margin-bottom:14px;padding:10px 12px;border-radius:8px;background:rgba(217,83,79,0.1);border:1px solid rgba(217,83,79,0.25);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="login-field">
                    <label class="login-label" for="admin-email">Email</label>
                    <input type="email" id="admin-email" name="email" autocomplete="username" placeholder="admin@example.com" required>
                </div>

                <div class="login-field">
                    <label class="login-label" for="admin-password">Password</label>
                    <input type="password" id="admin-password" name="password" autocomplete="current-password" placeholder="Password" required>
                </div>

                <div class="login-actions">
                    <button type="submit" class="login-actions__submit">Log in as admin</button>
                </div>
            </form>

            <p class="login-foot">EWallet administration · <a href="../../pages/landing.php">Public site</a></p>
        </div>
    </div>
</main>

<?php $ewScriptHref = '../../assets/js/script.js'; include __DIR__ . '/../../components/script_main.php'; ?>
</body>
</html>
