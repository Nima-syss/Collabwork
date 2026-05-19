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
    <?php
    $themeCssDir = '../assets/css/';
    $themeExtraLinks = [$themeCssDir . 'expenses.css'];
    include __DIR__ . '/../components/head_theme.php';
    ?>
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
        <div class="dashboard expenses-page">
            <h1 class="dashboard-title">Expenses</h1>

            <div class="expense-controls">
                <button class="primary-btn" id="addBtn" type="button">+ Add Expenses</button>

                <div class="time-filters" role="tablist" aria-label="Expense time filters">
                    <button class="filter-btn active" type="button">Daily</button>
                    <button class="filter-btn" type="button">Weekly</button>
                    <button class="filter-btn" type="button">Monthly</button>
                </div>
            </div>

            <div class="budget-content expenses-content">
                <section class="budget-panel expenses-table-card" aria-label="Expenses table">
                    <div class="expenses-card-header">
                        <h2>Expenses</h2>
                        <p class="expenses-card-subtitle">View, update, or delete your expenses.</p>
                    </div>

                    <div class="expenses-table-wrap" role="region" aria-label="Expenses list" tabindex="0">
                        <table class="expenses-table">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Note</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Update</th>
                                    <th scope="col">Delete</th>
                                </tr>
                            </thead>
                            <tbody id="expensesTableBody">
                                <tr>
                                    <td colspan="6" class="expenses-table-empty">No expenses loaded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="expenses-sidebar-col">
                    <aside class="budget-panel expenses-transactions-card" aria-label="Recent transactions">
                        <div class="expenses-card-header">
                            <h2>Recent Transactions</h2>
                            <p class="expenses-card-subtitle">Latest expenses for the selected period.</p>
                        </div>
                        <div class="transaction-list" aria-live="polite"></div>
                    </aside>

                    <section class="budget-panel expenses-graph-card" aria-label="Spending graph">
                        <div class="expenses-card-header">
                            <h2>Spending Graph</h2>
                            <p class="expenses-card-subtitle">Spending overview for the selected period.</p>
                        </div>
                        <div class="expenses-table-wrap">
                            <div class="expenses-graph-wrap">
                                <canvas id="expensesGraph" aria-label="Expenses graph"></canvas>
                                <div class="expenses-graph-empty" id="expensesGraphEmpty" hidden>No data to display.</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/script_main.php'; ?>
<?php
$ewExpJs = __DIR__ . '/../assets/js/expenses.js';
$ewExpVer = is_file($ewExpJs) ? (int) filemtime($ewExpJs) : 1;
?>
<script src="../assets/js/expenses.js?v=<?php echo $ewExpVer; ?>"></script>

</body>
</html>
