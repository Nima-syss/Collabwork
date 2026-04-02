<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- SIDEBAR -->
        <?php include '../components/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- TOP BAR -->
            <?php include '../components/topbar.php'; ?>

            <!-- DASHBOARD -->
            <div class="dashboard">
                <h1 class="dashboard-title">Dashboard</h1>

                <!-- STATS CARDS -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon"><img src="../assets/icons/solar_wallet-linear.png" alt="Wallet icon" class="stat-icon-img" /></div>
                        <div class="stat-label">Total Balance</div>
                        <div class="stat-value">NRP XXX</div>
                        <div class="stat-action">
                            <img src="../assets/icons/mdi_eye-off.png" alt="Eye icon" class="action-icon" /> View Details →
                        </div>
                    </div>

                    <div class="stat-card secondary">
                        <div class="stat-icon"><img src="../assets/icons/solar_wallet-linear.png" alt="Expenses icon" class="stat-icon-img" /></div>
                        <div class="stat-label">Total Spending</div>
                        <div class="stat-value">NRP XXX</div>
                        <div class="stat-action">
                            <img src="../assets/icons/mdi_eye-off.png" alt="Eye icon" class="action-icon" /> View Details →
                        </div>
                    </div>

                    <div class="stat-card secondary">
                        <div class="stat-icon"><img src="../assets/icons/bill.png" alt="Bill icon" class="stat-icon-img" /></div>
                        <div class="stat-label">Money Saved</div>
                        <div class="stat-value">NRP XXX</div>
                        <div class="stat-action">
                            <img src="../assets/icons/mdi_eye-off.png" alt="Eye icon" class="action-icon" /> View Details →
                        </div>
                    </div>
                </div>

                <!-- TRANSACTION HISTORY -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Transaction History</h2>
                    <div class="transaction-list">
                        <div class="transaction-item"></div>
                        <div class="transaction-item"></div>
                        <div class="transaction-item"></div>
                        <div class="transaction-item"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

<script src="../assets/js/script.js"></script>
</body>
</html>
