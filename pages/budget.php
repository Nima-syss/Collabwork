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
    <title>Budget</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">

        <!-- SIDEBAR -->
        <?php include '../components/sidebar.php'; ?>

        <div class="main-content">
            <!-- TOP BAR -->
            <?php include '../components/topbar.php'; ?>

            <div class="budget-page">
                <h1 class="dashboard-title">Budget</h1>

                <div class="budget-summary">
                    <div class="budget-card">
                        <div class="stat-label">Total Budget</div>
                        <div class="stat-value">NRP XXX</div>
                    </div>
                    <div class="budget-card">
                        <div class="stat-label">Remaining Balance</div>
                        <div class="stat-value">NRP XXX</div>
                    </div>
                    <div class="budget-card">
                        <div class="stat-label">Remaining Budget</div>
                        <div class="stat-value">NRP XXX</div>
                    </div>
                    <div class="budget-card used-card">
                        <div class="stat-label">Used</div>
                        <div class="progress-wrap">
                            <div class="progress-bar"><span></span></div>
                        </div>
                    </div>
                    <div class="budget-btn-wrap">
                        <button class="primary-btn">Monthly</button>
                    </div>
                </div>

                <div class="budget-content">
                    <section class="budget-main">
                        <div class="card">
                            <h2>Budget Breakdown</h2>
                            <div class="chart-placeholder">Pie chart</div>
                        </div>
                    </section>

                    <aside class="budget-aside">
                        <div class="card add-budget-card">
                            <div class="add-budget-title">Add Budget</div>
                            <div class="add-budget-button">+</div>
                            <p>Create a new budget</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeSwitch = document.getElementById('themeSwitch');
            const body = document.body;
            if (!themeSwitch) return;

            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
                themeSwitch.checked = true;
            }

            themeSwitch.addEventListener('change', function () {
                if (this.checked) {
                    body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        });
    </script>

<script src="../assets/js/script.js"></script>
</body>
</html>