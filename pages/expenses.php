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
    <title>Expenses</title>

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

        <!-- PAGE -->
        <div class="dashboard">

            <h1 class="dashboard-title">Expenses</h1>

            <!-- Controls -->
            <div class="expense-controls" style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <button class="primary-btn" id="addBtn">+ Add Expenses</button>

                <div style="background:#D9D9D9; padding:8px; border-radius:10px;">
                    <button class="filter-btn active" style="background:#5a8554; color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer;">Daily</button>
                    <button class="filter-btn" style="background:#5a8554; color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer;">Weekly</button>
                    <button class="filter-btn" style="background:#5a8554; color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer;">Monthly</button>
                </div>
            </div>

            <!-- Content -->
            <div class="budget-content">

                <div class="card">
                    <h2>Saving Progress 0%</h2>
                    <div class="chart-placeholder">Graph</div>
                </div>

                <div class="card">
                    <h2>Recent Transactions</h2>
                    <div class="transaction-list"></div>
                </div>

            </div>

            <!-- Progress Bar -->
            <div class="card" style="margin-top:20px;">
                <h2 id="progressText">Saving Progress 0%</h2>

                <div class="progress-wrap">
                    <div class="progress-bar">
                        <span id="progressBar" style="width:0%; display:block; height:100%; background:#3e8a51;"></span>
                    </div>
                </div>

                <!-- Labels -->
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-top:5px;">
                    <span>0%</span>
                    <span>25%</span>
                    <span>50%</span>
                    <span>75%</span>
                    <span>100%</span>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
