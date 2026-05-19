<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once '../../backend/connection.php';

$action  = $_GET['action'] ?? 'list';
$user_id = (int)($_GET['id'] ?? 0);
$message = '';
$error   = '';

// ── Handle POST actions ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'update_user') {
        $uid      = (int)($_POST['uid'] ?? 0);
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $balance  = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_FLOAT);
        if ($uid && $fullname && $email && $balance !== false && $balance >= 0) {
            $stmt = $mysqli->prepare('UPDATE users SET fullname=?, email=?, balance=? WHERE id=?');
            $stmt->bind_param('ssdi', $fullname, $email, $balance, $uid);
            $stmt->execute(); $stmt->close();
            $message = 'User updated successfully.';
        } else { $error = 'Invalid input. Please check all fields.'; }
    }

    if ($post_action === 'delete_user') {
        $uid = (int)($_POST['uid'] ?? 0);
        if ($uid) {
            $mysqli->query("DELETE FROM transactions WHERE user_id=$uid");
            $mysqli->query("DELETE FROM expenses WHERE user_id=$uid");
            $mysqli->query("DELETE FROM budgets WHERE user_id=$uid");
            $mysqli->query("DELETE FROM user_settings WHERE user_id=$uid");
            $stmt = $mysqli->prepare('DELETE FROM users WHERE id=?');
            $stmt->bind_param('i', $uid); $stmt->execute(); $stmt->close();
            $message = 'User deleted.';
            $action  = 'list';
        }
    }

    if ($post_action === 'adjust_balance') {
        $uid    = (int)($_POST['uid'] ?? 0);
        $amount = filter_input(INPUT_POST, 'adjust_amount', FILTER_VALIDATE_FLOAT);
        $type   = $_POST['adjust_type'] ?? 'add';
        if ($uid && $amount !== false && $amount > 0) {
            if ($type === 'add') {
                $mysqli->query("UPDATE users SET balance = balance + $amount WHERE id = $uid");
                $desc = "Admin credit: NRP $amount"; $t = 'load';
            } else {
                $mysqli->query("UPDATE users SET balance = GREATEST(0, balance - $amount) WHERE id = $uid");
                $desc = "Admin debit: NRP $amount"; $t = 'expense';
            }
            $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, type, amount, description) VALUES (?,?,?,?)');
            $stmt->bind_param('isds', $uid, $t, $amount, $desc);
            $stmt->execute(); $stmt->close();
            $message = 'Balance adjusted successfully.';
        } else { $error = 'Invalid amount.'; }
    }
}

// ── View single user ───────────────────────────────────────────────────────
$user = null; $user_tx = []; $user_expenses = []; $user_budgets = [];
if ($action === 'view' && $user_id) {
    $stmt = $mysqli->prepare('SELECT * FROM users WHERE id=?');
    $stmt->bind_param('i', $user_id); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); $stmt->close();

    $stmt = $mysqli->prepare('SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10');
    $stmt->bind_param('i', $user_id); $stmt->execute();
    $user_tx = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    $stmt = $mysqli->prepare('SELECT category, SUM(amount) AS total, COUNT(*) AS cnt FROM expenses WHERE user_id=? GROUP BY category ORDER BY total DESC');
    $stmt->bind_param('i', $user_id); $stmt->execute();
    $user_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    $stmt = $mysqli->prepare('SELECT category, monthly_limit, used FROM budgets WHERE user_id=? ORDER BY category ASC');
    $stmt->bind_param('i', $user_id); $stmt->execute();
    $user_budgets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

// ── List all users (with search + stats) ──────────────────────────────────
$search = trim($_GET['search'] ?? '');
if ($search) {
    $like = '%' . $mysqli->real_escape_string($search) . '%';
    $users = $mysqli->query(
        "SELECT u.*, 
            (SELECT COUNT(*) FROM transactions WHERE user_id=u.id) AS tx_count,
            (SELECT COUNT(*) FROM expenses WHERE user_id=u.id) AS exp_count
         FROM users u
         WHERE u.fullname LIKE '$like' OR u.email LIKE '$like' OR u.username LIKE '$like'
         ORDER BY u.created_at DESC"
    )->fetch_all(MYSQLI_ASSOC);
} else {
    $users = $mysqli->query(
        "SELECT u.*,
            (SELECT COUNT(*) FROM transactions WHERE user_id=u.id) AS tx_count,
            (SELECT COUNT(*) FROM expenses WHERE user_id=u.id) AS exp_count
         FROM users u
         ORDER BY u.created_at DESC"
    )->fetch_all(MYSQLI_ASSOC);
}

// Quick aggregate stats
$total_users   = count($users);
$total_balance = array_sum(array_column($users, 'balance'));
$highest_bal   = $users ? max(array_column($users, 'balance')) : 0;
$newest_user   = $users ? $users[0]['fullname'] : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Users – Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>
<div class="container">
    <?php include '../components/admin_sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/admin_topbar.php'; ?>
        <div class="dashboard">

        <?php if ($message): ?><p class="form-success" style="padding:10px 20px 0"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error):   ?><p class="form-error"   style="padding:10px 20px 0"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <?php if ($action === 'view' && $user): ?>
        <!-- ══════════════════════════════════════════
             USER DETAIL VIEW
        ══════════════════════════════════════════ -->
        <div class="admin-detail-header">
            <a href="users.php" class="admin-back-link">← Back to Users</a>
            <h1 class="dashboard-title" style="margin-bottom:6px"><?php echo htmlspecialchars($user['fullname']); ?></h1>
            <p style="color:#666;font-size:14px;margin-bottom:24px">@<?php echo htmlspecialchars($user['username']); ?> · <?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <!-- Profile summary banner -->
        <div class="admin-profile-banner">
            <div class="admin-profile-avatar"><?php echo strtoupper(substr($user['fullname'], 0, 2)); ?></div>
            <div class="admin-profile-info">
                <div class="admin-profile-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                <div class="admin-profile-meta">@<?php echo htmlspecialchars($user['username']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($user['email']); ?></div>
                <div class="admin-profile-joined">Joined <?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
            </div>
            <div class="admin-profile-balance-box">
                <div class="admin-profile-balance-label">Wallet Balance</div>
                <div class="admin-profile-balance-val">NRP <?php echo number_format($user['balance'], 2); ?></div>
            </div>
        </div>

        <!-- Quick stats row -->
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
            <div class="stat-card primary">
                <div class="stat-icon"><img src="../../assets/icons/solar_wallet-linear.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Balance</div>
                <div class="stat-value" style="font-size:20px">NRP <?php echo number_format($user['balance'], 2); ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-icon"><img src="../../assets/icons/analytics.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Transactions</div>
                <div class="stat-value" style="font-size:20px"><?php echo count($user_tx); ?>+</div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-icon"><img src="../../assets/icons/expenses.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Expense Categories</div>
                <div class="stat-value" style="font-size:20px"><?php echo count($user_expenses); ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-icon"><img src="../../assets/icons/budget icon.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Active Budgets</div>
                <div class="stat-value" style="font-size:20px"><?php echo count($user_budgets); ?></div>
            </div>
        </div>

        <div class="admin-grid-2col">
            <!-- LEFT: Edit + Adjust Balance -->
            <div style="display:flex;flex-direction:column;gap:20px">
                <!-- Edit form -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Edit User</h2>
                    <form method="POST" action="" class="admin-form">
                        <input type="hidden" name="post_action" value="update_user">
                        <input type="hidden" name="uid" value="<?php echo $user['id']; ?>">
                        <label class="admin-label">Full Name</label>
                        <input class="admin-input" type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        <label class="admin-label">Email</label>
                        <input class="admin-input" type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <label class="admin-label">Balance (NRP)</label>
                        <input class="admin-input" type="number" name="balance" step="0.01" min="0" value="<?php echo $user['balance']; ?>" required>
                        <div class="admin-form-row">
                            <button class="admin-btn admin-primary" type="submit">Save Changes</button>
                        </div>
                    </form>
                </div>

                <!-- Adjust Balance -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Adjust Balance</h2>
                    <form method="POST" action="" class="admin-form">
                        <input type="hidden" name="post_action" value="adjust_balance">
                        <input type="hidden" name="uid" value="<?php echo $user['id']; ?>">
                        <label class="admin-label">Amount (NRP)</label>
                        <input class="admin-input" type="number" name="adjust_amount" step="0.01" min="0.01" placeholder="0.00" required>
                        <label class="admin-label">Type</label>
                        <select class="admin-input" name="adjust_type">
                            <option value="add">➕ Add (Credit)</option>
                            <option value="remove">➖ Remove (Debit)</option>
                        </select>
                        <div class="admin-form-row">
                            <button class="admin-btn admin-primary" type="submit">Apply Adjustment</button>
                        </div>
                    </form>
                </div>

                <!-- Danger zone -->
                <div class="transaction-section" style="border:1px solid #ffd0d0">
                    <h2 class="transaction-title" style="color:#b33">Danger Zone</h2>
                    <p style="font-size:13px;color:#666;margin-bottom:14px">This will permanently delete the user and all their data including transactions, expenses, and budgets.</p>
                    <form method="POST" action="" onsubmit="return confirm('Permanently delete <?php echo htmlspecialchars($user['fullname']); ?> and all their data? This cannot be undone.')">
                        <input type="hidden" name="post_action" value="delete_user">
                        <input type="hidden" name="uid" value="<?php echo $user['id']; ?>">
                        <button class="admin-btn admin-danger" type="submit">Delete This User</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT: Activity -->
            <div style="display:flex;flex-direction:column;gap:20px">
                <!-- Recent Transactions -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Recent Transactions</h2>
                    <div style="max-height:280px;overflow-y:auto">
                    <?php if ($user_tx): ?>
                        <?php foreach ($user_tx as $tx):
                            $is_out = !in_array($tx['type'], ['load','receive']);
                        ?>
                        <article class="wallet-transaction-item" style="margin-bottom:8px">
                            <div class="wallet-transaction-meta">
                                <div class="wallet-transaction-type"><?php echo ucfirst($tx['type']); ?></div>
                                <div class="wallet-transaction-description"><?php echo htmlspecialchars($tx['description'] ?? ''); ?></div>
                                <div class="wallet-transaction-time"><?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?></div>
                            </div>
                            <div class="wallet-transaction-amount <?php echo $is_out?'is-outgoing':'is-incoming'; ?>">
                                <?php echo ($is_out?'- ':'+ ').'NRP '.number_format($tx['amount'],2); ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="padding:12px;color:#666;font-size:13px">No transactions yet.</p>
                    <?php endif; ?>
                    </div>
                </div>

                <!-- Expense breakdown -->
                <div class="transaction-section">
                    <h2 class="transaction-title">Expenses by Category</h2>
                    <?php if ($user_expenses): ?>
                    <table class="admin-table">
                        <thead><tr><th>Category</th><th>Entries</th><th>Total Spent</th></tr></thead>
                        <tbody>
                        <?php foreach ($user_expenses as $e): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($e['category']); ?></td>
                            <td><?php echo $e['cnt']; ?></td>
                            <td class="tx-out">NRP <?php echo number_format($e['total'],2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="padding:12px;color:#666;font-size:13px">No expenses recorded.</p>
                    <?php endif; ?>
                </div>

                <!-- Budget status -->
                <?php if ($user_budgets): ?>
                <div class="transaction-section">
                    <h2 class="transaction-title">Budget Status</h2>
                    <?php foreach ($user_budgets as $b):
                        $pct = $b['monthly_limit'] > 0 ? min(100, ($b['used']/$b['monthly_limit'])*100) : 0;
                        $bar_color = $pct >= 100 ? '#e55' : ($pct >= 80 ? '#f0a500' : '#0f5a13');
                    ?>
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                            <span style="font-weight:700"><?php echo htmlspecialchars($b['category']); ?></span>
                            <span style="color:#666">NRP <?php echo number_format($b['used'],2); ?> / <?php echo number_format($b['monthly_limit'],2); ?></span>
                        </div>
                        <div class="budget-progress">
                            <span style="width:<?php echo $pct; ?>%;background:<?php echo $bar_color; ?>"></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- ══════════════════════════════════════════
             USER LIST VIEW
        ══════════════════════════════════════════ -->
        <h1 class="dashboard-title">Users</h1>

        <!-- Summary stats -->
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
            <div class="stat-card primary">
                <div class="stat-icon"><img src="../../assets/icons/userprofile.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value" style="font-size:22px"><?php echo number_format($total_users); ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-icon"><img src="../../assets/icons/solar_wallet-linear.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Total Balances</div>
                <div class="stat-value" style="font-size:18px">NRP <?php echo number_format($total_balance, 2); ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-icon"><img src="../../assets/icons/analytics.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Highest Balance</div>
                <div class="stat-value" style="font-size:18px">NRP <?php echo number_format($highest_bal, 2); ?></div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-icon"><img src="../../assets/icons/notificationbell.png" alt="" class="stat-icon-img"></div>
                <div class="stat-label">Latest User</div>
                <div class="stat-value" style="font-size:16px"><?php echo htmlspecialchars($newest_user); ?></div>
            </div>
        </div>

        <!-- Search bar -->
        <div class="admin-toolbar">
            <form method="GET" action="" style="display:flex;gap:10px;align-items:center">
                <input class="admin-search-input" type="text" name="search"
                    placeholder="Search by name, email or username…"
                    value="<?php echo htmlspecialchars($search); ?>">
                <button class="admin-btn admin-primary" type="submit">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="admin-btn">Clear</a>
                <?php endif; ?>
            </form>
            <span class="admin-count"><?php echo $total_users; ?> user<?php echo $total_users != 1 ? 's' : ''; ?><?php echo $search ? ' found' : ''; ?></span>
        </div>

        <!-- Users table -->
        <div class="transaction-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Username</th>
                        <th>Balance</th>
                        <th>Transactions</th>
                        <th>Expenses</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="color:#999"><?php echo $u['id']; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:#1b3f24;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0">
                                <?php echo strtoupper(substr($u['fullname'], 0, 2)); ?>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:14px"><?php echo htmlspecialchars($u['fullname']); ?></div>
                                <div style="font-size:12px;color:#777"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#555">@<?php echo htmlspecialchars($u['username']); ?></td>
                    <td style="font-weight:700;color:<?php echo $u['balance'] > 0 ? '#0f5a13' : '#999'; ?>">
                        NRP <?php echo number_format($u['balance'], 2); ?>
                    </td>
                    <td style="text-align:center">
                        <span class="admin-badge admin-badge-load"><?php echo $u['tx_count']; ?></span>
                    </td>
                    <td style="text-align:center">
                        <span class="admin-badge admin-badge-expense"><?php echo $u['exp_count']; ?></span>
                    </td>
                    <td style="color:#777;font-size:13px"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <a href="users.php?action=view&id=<?php echo $u['id']; ?>" class="admin-action-link">View Profile</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:#777;padding:24px">
                        <?php echo $search ? 'No users match your search.' : 'No users registered yet.'; ?>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        </div>
    </div>
</div>

<style>
/* Admin profile banner */
.admin-profile-banner {
    display: flex;
    align-items: center;
    gap: 20px;
    background: #d9d9d9;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.admin-profile-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #1b3f24;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
}
.admin-profile-info { flex: 1; min-width: 0; }
.admin-profile-name { font-size: 18px; font-weight: 700; color: #1b3f24; }
.admin-profile-meta { font-size: 13px; color: #555; margin: 3px 0; }
.admin-profile-joined { font-size: 12px; color: #888; }
.admin-profile-balance-box {
    background: #1b3f24;
    border-radius: 10px;
    padding: 12px 20px;
    text-align: right;
    flex-shrink: 0;
}
.admin-profile-balance-label { font-size: 11px; color: rgba(255,255,255,0.75); margin-bottom: 4px; }
.admin-profile-balance-val { font-size: 20px; font-weight: 700; color: #fff; }
</style>

<script src="../../assets/js/script.js"></script>
</body>
</html>

