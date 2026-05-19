<?php
// If already logged in, send to dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EWallet – Track What You Spend</title>
    <?php
    $themeCssDir = '../assets/css/';
    $themeExtraLinks = [];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body class="landing-body">

    <!-- HEADER -->
    <header class="landing-header">
        <div class="landing-logo">
            <div class="landing-logo-badge">
                <img src="../assets/icons/Wallet.png" class="landing-logo-icon" alt="EWallet">
            </div>
            <span>EWallet</span>
        </div>
        <nav class="landing-nav-buttons">
            <a href="signup.php" class="landing-btn landing-btn-signup">Sign Up</a>
            <a href="login.php"  class="landing-btn landing-btn-login">Log In</a>
        </nav>
    </header>

    <!-- HERO -->
    <section class="landing-hero">
        <div class="landing-hero-bg-orb orb-1"></div>
        <div class="landing-hero-bg-orb orb-2"></div>
        <div class="landing-hero-text">
            <div class="landing-hero-pill">💰 Smart Finance Management</div>
            <h1 class="landing-h1">Your money,<br><span class="landing-h1-accent">fully in control.</span></h1>
            <p class="landing-tagline">Track <strong>WHAT</strong> you spend — and make every rupee count.</p>
            <div class="landing-hero-actions">
                <a href="signup.php" class="landing-cta-primary">
                    <span>Get Started Free</span>
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a href="login.php" class="landing-cta-secondary">Log In</a>
            </div>
            <div class="landing-hero-trust">
                <div class="landing-trust-item">
                    <span class="trust-icon">🔒</span>
                    <span>Bank-level security</span>
                </div>
                <div class="landing-trust-item">
                    <span class="trust-icon">⚡</span>
                    <span>Instant transfers</span>
                </div>
                <div class="landing-trust-item">
                    <span class="trust-icon">📊</span>
                    <span>Smart analytics</span>
                </div>
            </div>
        </div>
        <div class="landing-hero-image">
            <div class="landing-hero-card-stack">
                <div class="landing-stat-card stat-card-1">
                    <span class="stat-label">Monthly Balance</span>
                    <span class="stat-value">NPR 48,200</span>
                    <span class="stat-badge stat-up">↑ 12.4%</span>
                </div>
                <div class="landing-stat-card stat-card-2">
                    <span class="stat-label">Savings Goal</span>
                    <div class="stat-progress-bar"><div class="stat-progress-fill" style="width:68%"></div></div>
                    <span class="stat-badge">68% complete</span>
                </div>
            </div>
            <img src="../assets/images/Payment.png" alt="EWallet illustration" class="landing-illustration">
        </div>
    </section>

    <!-- STATS STRIP -->
    <div class="landing-stats-strip">
        <div class="stat-strip-item">
            <strong>10,000+</strong><span>Active Users</span>
        </div>
        <div class="stat-strip-divider"></div>
        <div class="stat-strip-item">
            <strong>NPR 2Cr+</strong><span>Transactions Tracked</span>
        </div>
        <div class="stat-strip-divider"></div>
        <div class="stat-strip-item">
            <strong>99.9%</strong><span>Uptime</span>
        </div>
        <div class="stat-strip-divider"></div>
        <div class="stat-strip-item">
            <strong>4.9 ★</strong><span>User Rating</span>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="landing-features">
        <div class="landing-features-header">
            <span class="section-pill">Features</span>
            <h2 class="landing-section-title">Everything you need to<br>manage money smarter</h2>
            <p class="landing-section-subtitle">Built for everyday use — powerful enough for serious finance tracking.</p>
        </div>
        <div class="landing-features-grid">
            <div class="landing-feature-card">
                <div class="landing-feature-icon">
                    <img src="../assets/icons/Wallet.png" alt="Wallet">
                </div>
                <div class="landing-feature-content">
                    <h3>Digital Wallet</h3>
                    <p>Load money via Visa or Bank, send to anyone instantly, and keep your balance in check — all in one place.</p>
                </div>
                <div class="landing-feature-img-wrap">
                    <img src="../assets/images/OnlinePayment.png" alt="Online Payment" class="landing-feature-img">
                </div>
                <a href="signup.php" class="feature-card-link">Get started →</a>
            </div>
            <div class="landing-feature-card landing-feature-card--accent">
                <div class="landing-feature-icon">
                    <img src="../assets/icons/expenses.png" alt="Expenses">
                </div>
                <div class="landing-feature-content">
                    <h3>Expense Tracking</h3>
                    <p>Log every rupee effortlessly. Filter by day, week or month. See exactly where your money goes with clear visual reports.</p>
                </div>
                <div class="landing-feature-img-wrap">
                    <img src="../assets/images/TransferMoney.png" alt="Transfer Money" class="landing-feature-img">
                </div>
                <a href="signup.php" class="feature-card-link">Get started →</a>
            </div>
            <div class="landing-feature-card">
                <div class="landing-feature-icon">
                    <img src="../assets/icons/budget icon.png" alt="Budget">
                </div>
                <div class="landing-feature-content">
                    <h3>Smart Budgeting</h3>
                    <p>Set monthly limits per category. Get visual breakdowns and alerts before you overspend.</p>
                </div>
                <div class="landing-feature-img-wrap">
                    <img src="../assets/images/MoneyRecieved.png" alt="Money Received" class="landing-feature-img">
                </div>
                <a href="signup.php" class="feature-card-link">Get started →</a>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="landing-how">
        <div class="landing-how-inner">
            <span class="section-pill">How it works</span>
            <h2 class="landing-section-title">Up and running in minutes</h2>
            <div class="landing-steps">
                <div class="landing-step">
                    <div class="step-number">01</div>
                    <div class="step-content">
                        <h3>Create your account</h3>
                        <p>Sign up in seconds — no credit card required. Just your email and a password.</p>
                    </div>
                </div>
                <div class="step-connector"></div>
                <div class="landing-step">
                    <div class="step-number">02</div>
                    <div class="step-content">
                        <h3>Load your wallet</h3>
                        <p>Add funds via Visa or bank transfer instantly. Your balance is always up to date.</p>
                    </div>
                </div>
                <div class="step-connector"></div>
                <div class="landing-step">
                    <div class="step-number">03</div>
                    <div class="step-content">
                        <h3>Track & budget</h3>
                        <p>Log expenses, set budgets, and watch your financial health improve week by week.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT / CTA -->
    <section class="landing-about">
        <div class="landing-about-inner">
            <span class="section-pill section-pill--light">About EWallet</span>
            <h2>Finance management<br>made <em>beautifully</em> simple</h2>
            <p>
                EWallet is a clean, efficient expense tracking application designed to help you manage daily finances with ease.
                Record, monitor, and analyse spending habits — then make smarter decisions with clear insights.
                Stay within budget, anytime, anywhere.
            </p>
            <a href="signup.php" class="landing-cta-primary landing-cta-large">
                <span>Create Your Free Account</span>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <p class="about-footnote">Free forever · No credit card · Cancel anytime</p>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="landing-footer">
        <div class="landing-footer-top">
            <div class="landing-footer-brand">
                <div class="landing-logo-badge landing-logo-badge--sm">
                    <img src="../assets/icons/Wallet.png" alt="EWallet" width="20" height="20">
                </div>
                <span class="footer-brand-name">EWallet</span>
            </div>
            <nav class="landing-footer-links">
                <a href="signup.php">Sign Up</a>
                <a href="login.php">Log In</a>
            </nav>
        </div>
        <div class="landing-footer-bottom">
            <p class="landing-footer-copy">© <?php echo date('Y'); ?> EWallet. All rights reserved.</p>
            <p class="landing-footer-tagline">Track what you spend.</p>
        </div>
    </footer>

<?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>