<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once '../../backend/connection.php';

$search   = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per      = 20;
$offset   = ($page - 1) * $per;

$where = [];
if ($search) {
    $like = '%'.$mysqli->real_escape_string($search).'%';
    $where[] = "(u.fullname LIKE '$like' OR e.category LIKE '$like' OR e.note LIKE '$like')";
}
if ($category) {
    $c = $mysqli->real_escape_string($category);
    $where[] = "e.category = '$c'";
}
$wClause = $where ? 'WHERE '.implode(' AND ',$where) : '';

$total = $mysqli->query("SELECT COUNT(*) AS c FROM expenses e JOIN users u ON e.user_id=u.id $wClause")->fetch_assoc()['c'];
$pages = max(1, ceil($total/$per));

$exps = $mysqli->query(
    "SELECT e.*, u.fullname FROM expenses e JOIN users u ON e.user_id=u.id
     $wClause ORDER BY e.expense_date DESC, e.id DESC LIMIT $per OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

$categories = [
    'Foods',
    'Transportation',
    'Housing',
    'Shopping',
    'Health and Wellness',
    'Education',
    'Entertainment',
    'Others',
    'Unbudgeted'
];

// Summary by category
$cat_summary = $mysqli->query(
    "SELECT category, COUNT(*) AS cnt, SUM(amount) AS total FROM expenses GROUP BY category ORDER BY total DESC"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Expenses – Admin</title>
    <?php
    $themeCssDir = '../../assets/css/';
    $themeExtraLinks = [
        $themeCssDir . 'expenses.css',
        '../admin.css',
    ];
    include __DIR__ . '/../../components/head_theme.php';
    ?>
</head>
<body>
<div class="container">
    <?php include '../components/admin_sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/admin_topbar.php'; ?>
        <div class="dashboard">
            <h1 class="dashboard-title">All Expenses</h1>

            <!-- Category summary cards -->
            <div class="admin-cat-grid">
                <?php foreach ($cat_summary as $cs): ?>
                <div class="admin-cat-card">
                    <div class="stat-label"><?php echo htmlspecialchars($cs['category']); ?></div>
                    <div class="stat-value" style="font-size:18px">NRP <?php echo number_format($cs['total'],2); ?></div>
                    <div style="font-size:12px;color:#777"><?php echo $cs['cnt']; ?> entries</div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="admin-toolbar">
                <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <input class="admin-search-input" type="text" name="search" placeholder="Search user, category or note…" value="<?php echo htmlspecialchars($search); ?>">
                    <select class="admin-input" name="category" style="width:auto;padding:8px 12px">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category===$cat?'selected':''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="admin-btn admin-primary" type="submit">Filter</button>
                    <a href="expenses.php" class="admin-btn">Clear</a>
                </form>
                <span class="admin-count"><?php echo number_format($total); ?> record<?php echo $total!=1?'s':''; ?></span>
            </div>

            <div class="transaction-section">
                <table class="admin-table">
                    <thead>
                        <tr><th>Date</th><th>User</th><th>Category</th><th>Note</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($exps as $e): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['expense_date']); ?></td>
                        <td><?php echo htmlspecialchars($e['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($e['category']); ?></td>
                        <td><?php echo htmlspecialchars($e['note'] ?: '—'); ?></td>
                        <td class="tx-out">- NRP <?php echo number_format($e['amount'],2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$exps): ?><tr><td colspan="5" style="text-align:center;color:#777;padding:20px">No expenses found.</td></tr><?php endif; ?>
                    </tbody>
                </table>

                <?php if ($pages > 1): ?>
                <div class="admin-pagination">
                    <?php for ($p=1; $p<=$pages; $p++): ?>
                    <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"
                       class="admin-page-btn <?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $ewScriptHref = '../../assets/js/script.js'; include __DIR__ . '/../../components/script_main.php'; ?>
</body>
</html>
