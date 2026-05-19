<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once '../../backend/connection.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

// Stats
$total_users  = $mysqli->query('SELECT COUNT(*) AS c FROM users')->fetch_assoc()['c'];
$total_tx     = $mysqli->query('SELECT COUNT(*) AS c FROM transactions')->fetch_assoc()['c'];
$total_volume = $mysqli->query("SELECT COALESCE(SUM(amount),0) AS s FROM transactions WHERE type IN ('load','send','receive')")->fetch_assoc()['s'];
$total_exp    = $mysqli->query('SELECT COALESCE(SUM(amount),0) AS s FROM expenses')->fetch_assoc()['s'];

// Recent users
$recent_users = $mysqli->query('SELECT id,fullname,email,balance,created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);

// Recent transactions
$recent_tx = $mysqli->query(
    'SELECT t.id, t.type, t.amount, t.description, t.created_at, u.fullname
     FROM transactions t JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC LIMIT 6'
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – EWallet</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>
<div class="container">
    <?php include '../components/admin_sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/admin_topbar.php'; ?>
        <div class="dashboard">
            <h1 class="dashboard-title">Admin Dashboard</h1>

            <div class="stats-grid admin-panel">
                <div class="stat-card primary">
                    <div class="stat-icon"><img src="../../assets/icons/userprofile.png" alt="" class="stat-icon-img"></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <a href="users.php" class="stat-action">View all →</a>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><img src="../../assets/icons/solar_wallet-linear.png" alt="" class="stat-icon-img"></div>
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-value"><?php echo number_format($total_tx); ?></div>
                    <a href="transactions.php" class="stat-action">View all →</a>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><img src="../../assets/icons/analytics.png" alt="" class="stat-icon-img"></div>
                    <div class="stat-label">Total Volume (NRP)</div>
                    <div class="stat-value"><?php echo number_format($total_volume, 2); ?></div>
                </div>
                <div class="stat-card secondary">
                    <div class="stat-icon"><img src="../../assets/icons/expenses.png" alt="" class="stat-icon-img"></div>
                    <div class="stat-label">Total Expenses (NRP)</div>
                    <div class="stat-value"><?php echo number_format($total_exp, 2); ?></div>
                </div>
            </div>

            <div class="admin-grid-2col">
                <!-- Recent Users -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Recent Users</h2>
                    <table class="admin-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Balance</th><th>Joined</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>NRP <?php echo number_format($u['balance'],2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                            <td><a href="users.php?action=view&id=<?php echo $u['id']; ?>" class="admin-action-link">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Transactions -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Recent Transactions</h2>
                    <table class="admin-table">
                        <thead><tr><th>User</th><th>Type</th><th>Amount</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent_tx as $tx): 
                            $is_out = !in_array($tx['type'], ['load','receive']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tx['fullname']); ?></td>
                            <td><span class="admin-badge admin-badge-<?php echo $tx['type']; ?>"><?php echo ucfirst($tx['type']); ?></span></td>
                            <td class="<?php echo $is_out ? 'tx-out' : 'tx-in'; ?>"><?php echo ($is_out?'-':'+').' NRP '.number_format($tx['amount'],2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($tx['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../../assets/js/script.js"></script>
</body>
</html>
