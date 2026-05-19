<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];
require_once __DIR__ . '/../backend/connection.php';

$bal_stmt = $mysqli->prepare('SELECT balance FROM users WHERE id = ?');
$bal_stmt->bind_param('i', $user_id);
$bal_stmt->execute();
$bal_row = $bal_stmt->get_result()->fetch_assoc();
$bal_stmt->close();
$wallet_balance_raw = (float) ($bal_row['balance'] ?? 0);
$wallet_balance_display = number_format($wallet_balance_raw, 2);
$_SESSION['total_balance'] = $wallet_balance_raw;

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget</title>
    <?php
    $themeCssDir = '../assets/css/';
    // Load after app-shell.css so budget rules win; bust browser cache when file changes.
    $themeExtraLinks = [];
    $budgetCssPath = __DIR__ . '/../assets/css/budget.css';
    $budgetCssVer = is_file($budgetCssPath) ? (string) filemtime($budgetCssPath) : '1';
    $themeTailLinks = [$themeCssDir . 'budget.css?v=' . rawurlencode($budgetCssVer)];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body>
    <div class="container">

        <!-- SIDEBAR -->
        <?php include '../components/sidebar.php'; ?>

        <div class="main-content">
            <!-- TOP BAR -->
            <?php include '../components/topbar.php'; ?>

            <div class="dashboard budget-page">
                <h1 class="dashboard-title budget-title">Budget</h1>

                <!-- Month Navigator -->
                <div class="budget-month-nav">
                    <button class="month-nav-btn" id="prevMonth" aria-label="Previous month">&#8249;</button>
                    <span class="month-nav-label" id="currentMonthLabel"></span>
                    <button class="month-nav-btn" id="nextMonth" aria-label="Next month">&#8250;</button>
                </div>

                <div class="budget-summary">
                    <div class="budget-card summary-card">
                        <div class="stat-label">Total Budget</div>
                        <div class="stat-value" id="totalBudgetValue">NRP 0</div>
                    </div>
                    <div class="budget-card summary-card">
                        <div class="stat-label">Remaining Balance</div>
                        <div class="stat-value" id="remainingBalanceValue" data-wallet-balance="<?php echo htmlspecialchars((string) $wallet_balance_raw); ?>">NRP <?php echo htmlspecialchars($wallet_balance_display); ?></div>
                    </div>
                    <div class="budget-card summary-card">
                        <div class="stat-label">Remaining Budget</div>
                        <div class="stat-value" id="remainingBudgetValue">NRP 0</div>
                    </div>
                    <div class="budget-card used-card">
                        <div class="stat-label">Used</div>
                        <div class="progress-wrap">
                            <div class="progress-bar"><span id="overallUsedBar"></span></div>
                        </div>
                    </div>
                    <div class="budget-btn-wrap">
                        <button class="primary-btn">Monthly</button>
                    </div>
                </div>

                <div class="budget-content">
                    <section class="budget-main">
                        <div class="budget-panel budget-breakdown-card">
                            <div class="budget-breakdown-header">
                                <h2>Budget Breakdown</h2>
                                <button class="budget-add-top" id="openBudgetModal" type="button">Edit Budget</button>
                            </div>
                            <div class="budget-list" id="budgetList"></div>
                        </div>
                    </section>

                    <aside class="budget-aside">
                        <div class="budget-panel budget-chart-card">
                            <h2 class="budget-chart-title">Pie chart</h2>
                            <div class="budget-chart-wrap">
                                <canvas id="budgetPie" width="320" height="320" aria-label="Budget pie chart"></canvas>
                            </div>
                            <div class="budget-legend" id="budgetLegend"></div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <div class="budget-modal-overlay" id="budgetModalOverlay" hidden>
        <div class="budget-modal" role="dialog" aria-modal="true" aria-labelledby="budgetModalTitle">
            <div class="budget-modal-header">
                <h2 class="budget-modal-title" id="budgetModalTitle">Add / Edit Budget</h2>
                <button class="budget-modal-close" id="closeBudgetModal" type="button" aria-label="Close">×</button>
            </div>

            <form class="budget-modal-form" id="budgetForm">
                <input type="hidden" id="editingCategory" name="editingCategory" value="" />

                <label class="budget-field">
                    <span class="budget-label">Category</span>
                    <select id="budgetCategory" name="category" required></select>
                </label>

                <label class="budget-field">
                    <span class="budget-label">Monthly Limit (NRP)</span>
                    <input id="budgetLimit" name="limit" type="number" min="0" step="0.01" placeholder="0" required />
                </label>

                <label class="budget-field">
                    <span class="budget-label">Used (NRP)</span>
                    <input id="budgetUsed" name="used" type="number" min="0" step="0.01" placeholder="0" />
                </label>

                <div class="budget-modal-actions">
                    <button class="budget-btn secondary" id="deleteBudgetBtn" type="button">Delete</button>
                    <div class="budget-modal-actions-right">
                        <button class="budget-btn ghost" id="cancelBudgetBtn" type="button">Cancel</button>
                        <button class="budget-btn primary" type="submit">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php include __DIR__ . '/../components/script_main.php'; ?>
<?php
$ewCatJs = __DIR__ . '/../assets/js/categories.js';
$ewBudJs = __DIR__ . '/../assets/js/budget.js';
$ewCatVer = is_file($ewCatJs) ? (int) filemtime($ewCatJs) : 1;
$ewBudVer = is_file($ewBudJs) ? (int) filemtime($ewBudJs) : 1;
?>
<script src="../assets/js/categories.js?v=<?php echo $ewCatVer; ?>"></script>
<script src="../assets/js/budget.js?v=<?php echo $ewBudVer; ?>"></script>
</body>
</html>
