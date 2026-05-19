<?php require_once '../backend/pages/dashboard_page.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <?php
    $themeCssDir = '../assets/css/';
    $themeExtraLinks = [];
    $themeTailLinks = [$themeCssDir . 'dashboard.css'];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body class="user-dashboard">
    <div class="container">
        <!-- SIDEBAR -->
        <?php include '../components/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- TOP BAR -->
            <?php include '../components/topbar.php'; ?>

            <!-- DASHBOARD -->
            <div class="dashboard">
                <header class="dashboard-page-header">
                    <div class="dashboard-page-header__titles">
                        <p class="dashboard-page-header__eyebrow">Overview</p>
                        <h1 class="dashboard-title">Dashboard</h1>
                    </div>
                    <div class="dashboard-page-header__meta">
                        <time class="dashboard-page-header__date" datetime="<?php echo date('c'); ?>"><?php echo htmlspecialchars(date('l, F j, Y')); ?></time>
                    </div>
                </header>

                <!-- STATS CARDS -->
                <div class="stats-grid">

                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <img src="../assets/icons/solar_wallet-linear.png" alt="Wallet icon" class="stat-icon-img" />
                        </div>
                        <div class="stat-label">Total Balance</div>
                        <div class="stat-value"
                            id="totalBalanceValue"
                            data-visible-balance="NRP <?php echo number_format($total_balance, 2); ?>"
                            data-hidden-balance="NRP XXX">
                            NRP XXX
                        </div>
                        <button class="stat-action balance-toggle-btn" id="balanceToggleBtn" type="button" aria-pressed="true">
                            <img src="../assets/icons/mdi_eye-off.png" alt="Eye icon" class="action-icon" /> View Details →
                        </button>
                    </div>

                    <div class="stat-card secondary">
                        <div class="stat-icon">
                            <img src="../assets/icons/solar_wallet-linear.png" alt="Expenses icon" class="stat-icon-img" />
                        </div>
                        <div class="stat-label">Total Spending</div>
                        <div class="stat-value stat-negative"
                            id="totalSpendingValue"
                            data-visible-balance="- NRP <?php echo number_format($total_spent, 2); ?>"
                            data-hidden-balance="- NRP XXX">
                            - NRP XXX
                        </div>
                        <button class="stat-action balance-toggle-btn" id="spendingToggleBtn" type="button" aria-pressed="true">
                            <img src="../assets/icons/mdi_eye-off.png" alt="Eye icon" class="action-icon" /> View Details →
                        </button>
                    </div>

                    <div class="stat-card secondary">
                        <div class="stat-icon">
                            <img src="../assets/icons/bill.png" alt="Bill icon" class="stat-icon-img" />
                        </div>
                        <div class="stat-label">Remaining Budget</div>
                        <div class="stat-value"
                            id="moneySavedValue"
                            data-visible-balance="NRP <?php echo number_format($money_saved, 2); ?>"
                            data-hidden-balance="NRP XXX">
                            NRP XXX
                        </div>
                        <button class="stat-action balance-toggle-btn" id="savedToggleBtn" type="button" aria-pressed="true">
                            <img src="../assets/icons/mdi_eye-off.png" alt="Eye icon" class="action-icon" /> View Details →
                        </button>
                    </div>

                </div>

                <!-- TRANSACTION HISTORY -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Transaction History</h2>
                    <div class="transaction-list dashboard-transactions" aria-live="polite">
                        <?php if (empty($transactions)): ?>
                            <div class="dashboard-tx-empty">No transactions yet.</div>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <?php
                                    $type = strtolower(trim((string)($tx['type'] ?? '')));
                                    $amount = (float)($tx['amount'] ?? 0);
                                    $abs_amount = abs($amount);
                                    $incoming_types = ['load', 'receive'];
                                    $is_out = !in_array($type, $incoming_types, true);
                                    $sign = $is_out ? '-' : '+';
                                    $amount_class = $is_out ? 'out' : 'in';
                                    $desc = htmlspecialchars((string)($tx['description'] ?? 'Transaction'));
                                    $date = '';
                                    if (!empty($tx['created_at'])) {
                                        $ts = strtotime((string)$tx['created_at']);
                                        if ($ts) $date = date('M j, Y', $ts);
                                    }
                                    $search_text = trim($desc . ' ' . $date . ' ' . $sign . ' NRP ' . number_format($abs_amount, 2));
                                ?>
                                <div class="dashboard-tx-item" data-search-text="<?php echo htmlspecialchars($search_text); ?>">
                                    <span class="dashboard-tx-dot"></span>
                                    <div class="dashboard-tx-meta">
                                        <div class="dashboard-tx-desc"><?php echo $desc; ?></div>
                                        <?php if ($date !== ''): ?>
                                            <div class="dashboard-tx-date"><?php echo htmlspecialchars($date); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dashboard-tx-amount <?php echo $amount_class; ?>"
                                        data-visible-balance="<?php echo $sign; ?> NRP <?php echo number_format($abs_amount, 2); ?>"
                                        data-hidden-balance="<?php echo $sign; ?> NRP XXX">
                                        <?php echo $sign; ?> NRP XXX
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>