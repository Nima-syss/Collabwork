<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once '../../backend/connection.php';

$search = trim($_GET['search'] ?? '');
$where  = '';
if ($search) {
    $like  = '%'.$mysqli->real_escape_string($search).'%';
    $where = "WHERE u.fullname LIKE '$like' OR u.email LIKE '$like' OR b.category LIKE '$like'";
}

$budgets = $mysqli->query(
    "SELECT b.*, u.fullname, u.email FROM budgets b JOIN users u ON b.user_id=u.id
     $where ORDER BY b.category ASC"
)->fetch_all(MYSQLI_ASSOC);

$summary = $mysqli->query(
    "SELECT category, COUNT(*) AS users, SUM(monthly_limit) AS total_limit, SUM(used) AS total_used
     FROM budgets GROUP BY category ORDER BY total_limit DESC"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Budgets – Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/budget.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>
<div class="container">
    <?php include '../components/admin_sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/admin_topbar.php'; ?>
        <div class="dashboard">
            <h1 class="dashboard-title">Budget Overview</h1>

            <!-- Category summary -->
            <div class="transaction-section" style="margin-bottom:24px">
                <h2 class="transaction-title">Summary by Category</h2>
                <table class="admin-table">
                    <thead><tr><th>Category</th><th>Users</th><th>Total Limit</th><th>Total Used</th><th>Usage %</th></tr></thead>
                    <tbody>
                    <?php foreach ($summary as $s):
                        $pct = $s['total_limit'] > 0 ? round(($s['total_used']/$s['total_limit'])*100) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['category']); ?></td>
                        <td><?php echo $s['users']; ?></td>
                        <td>NRP <?php echo number_format($s['total_limit'],2); ?></td>
                        <td>NRP <?php echo number_format($s['total_used'],2); ?></td>
                        <td>
                            <div class="budget-progress" style="width:100px;display:inline-block;vertical-align:middle">
                                <span style="width:<?php echo min(100,$pct); ?>%;background:<?php echo $pct>=90?'#e55':'#0f5a13'; ?>"></span>
                            </div>
                            <span style="margin-left:8px"><?php echo $pct; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Individual budgets -->
            <div class="admin-toolbar">
                <form method="GET" action="" style="display:flex;gap:10px;align-items:center">
                    <input class="admin-search-input" type="text" name="search" placeholder="Search user or category…" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="admin-btn admin-primary" type="submit">Search</button>
                    <?php if ($search): ?><a href="budgets.php" class="admin-btn">Clear</a><?php endif; ?>
                </form>
                <span class="admin-count"><?php echo count($budgets); ?> budget<?php echo count($budgets)!=1?'s':''; ?></span>
            </div>
            <div class="transaction-section">
                <table class="admin-table">
                    <thead><tr><th>User</th><th>Category</th><th>Monthly Limit</th><th>Used</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($budgets as $b):
                        $rem = max(0, $b['monthly_limit'] - $b['used']);
                        $pct = $b['monthly_limit'] > 0 ? ($b['used']/$b['monthly_limit'])*100 : 0;
                        $status = $pct >= 100 ? 'Over Limit' : ($pct >= 80 ? 'Near Limit' : 'On Track');
                        $sClass = $pct >= 100 ? 'admin-badge-expense' : ($pct >= 80 ? 'admin-badge-send' : 'admin-badge-receive');
                    ?>
                    <tr>
                        <td>
                            <div><?php echo htmlspecialchars($b['fullname']); ?></div>
                            <div style="font-size:12px;color:#777"><?php echo htmlspecialchars($b['email']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($b['category']); ?></td>
                        <td>NRP <?php echo number_format($b['monthly_limit'],2); ?></td>
                        <td>NRP <?php echo number_format($b['used'],2); ?></td>
                        <td>NRP <?php echo number_format($rem,2); ?></td>
                        <td><span class="admin-badge <?php echo $sClass; ?>"><?php echo $status; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$budgets): ?><tr><td colspan="6" style="text-align:center;color:#777;padding:20px">No budgets found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="../../assets/js/script.js"></script>
</body>
</html>
