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
        } else {
            $error = 'Invalid input. Please check all fields.';
        }
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
            $message = 'User deleted successfully.';
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
                $desc = "Admin credit: NRP $amount";
                $t    = 'load';
            } else {
                $mysqli->query("UPDATE users SET balance = GREATEST(0, balance - $amount) WHERE id = $uid");
                $desc = "Admin debit: NRP $amount";
                $t    = 'expense';
            }
            $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, type, amount, description) VALUES (?,?,?,?)');
            $stmt->bind_param('isds', $uid, $t, $amount, $desc);
            $stmt->execute(); $stmt->close();
            $message = 'Balance adjusted successfully.';
        } else {
            $error = 'Invalid amount.';
        }
    }
}

// ── View single user ───────────────────────────────────────────────────────
$user = null;
$user_tx = [];
if ($action === 'view' && $user_id) {
    $stmt = $mysqli->prepare('SELECT * FROM users WHERE id=?');
    $stmt->bind_param('i', $user_id); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); $stmt->close();

    $stmt = $mysqli->prepare('SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
    $stmt->bind_param('i', $user_id); $stmt->execute();
    $user_tx = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

// ── List all users ─────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
if ($search) {
    $like = '%' . $mysqli->real_escape_string($search) . '%';
    $users = $mysqli->query("SELECT * FROM users WHERE fullname LIKE '$like' OR email LIKE '$like' OR username LIKE '$like' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $users = $mysqli->query('SELECT * FROM users ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
}
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

        <?php if ($message): ?><p class="form-success" style="padding:10px 20px"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error):   ?><p class="form-error"   style="padding:10px 20px"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <?php if ($action === 'view' && $user): ?>
            <!-- ── User Detail View ── -->
            <div class="admin-detail-header">
                <a href="users.php" class="admin-back-link">← Back to Users</a>
                <h1 class="dashboard-title"><?php echo htmlspecialchars($user['fullname']); ?></h1>
            </div>

            <div class="admin-grid-2col">
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

                    <hr style="margin:24px 0;border-color:rgba(0,0,0,.1)">

                    <h2 class="transaction-title">Adjust Balance</h2>
                    <form method="POST" action="" class="admin-form">
                        <input type="hidden" name="post_action" value="adjust_balance">
                        <input type="hidden" name="uid" value="<?php echo $user['id']; ?>">
                        <label class="admin-label">Amount (NRP)</label>
                        <input class="admin-input" type="number" name="adjust_amount" step="0.01" min="0.01" placeholder="0.00" required>
                        <label class="admin-label">Type</label>
                        <select class="admin-input" name="adjust_type">
                            <option value="add">Add (Credit)</option>
                            <option value="remove">Remove (Debit)</option>
                        </select>
                        <div class="admin-form-row">
                            <button class="admin-btn admin-primary" type="submit">Apply</button>
                        </div>
                    </form>

                    <hr style="margin:24px 0;border-color:rgba(0,0,0,.1)">

                    <form method="POST" action="" onsubmit="return confirm('Permanently delete this user and all their data?')">
                        <input type="hidden" name="post_action" value="delete_user">
                        <input type="hidden" name="uid" value="<?php echo $user['id']; ?>">
                        <button class="admin-btn admin-danger" type="submit">Delete User</button>
                    </form>
                </div>

                <!-- User info + recent transactions -->
                <div class="transaction-section">
                    <h2 class="transaction-title">User Info</h2>
                    <table class="admin-table admin-info-table">
                        <tr><th>Username</th><td>@<?php echo htmlspecialchars($user['username']); ?></td></tr>
                        <tr><th>Balance</th><td>NRP <?php echo number_format($user['balance'],2); ?></td></tr>
                        <tr><th>Joined</th><td><?php echo date('M j, Y H:i', strtotime($user['created_at'])); ?></td></tr>
                    </table>

                    <h2 class="transaction-title" style="margin-top:24px">Recent Transactions</h2>
                    <div class="wallet-transaction-list" style="max-height:320px;overflow-y:auto">
                    <?php foreach ($user_tx as $tx):
                        $is_out = !in_array($tx['type'],['load','receive']);
                    ?>
                        <article class="wallet-transaction-item">
                            <div class="wallet-transaction-meta">
                                <div class="wallet-transaction-type"><?php echo ucfirst($tx['type']); ?></div>
                                <div class="wallet-transaction-description"><?php echo htmlspecialchars($tx['description']); ?></div>
                                <div class="wallet-transaction-time"><?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?></div>
                            </div>
                            <div class="wallet-transaction-amount <?php echo $is_out?'is-outgoing':'is-incoming'; ?>">
                                <?php echo ($is_out?'- ':' + ').'NRP '.number_format($tx['amount'],2); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$user_tx): ?><p style="padding:12px;color:#666">No transactions yet.</p><?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ── User List ── -->
            <h1 class="dashboard-title">Users</h1>
            <div class="admin-toolbar">
                <form method="GET" action="" style="display:flex;gap:10px;align-items:center">
                    <input class="admin-search-input" type="text" name="search" placeholder="Search by name, email or username…" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="admin-btn admin-primary" type="submit">Search</button>
                    <?php if ($search): ?><a href="users.php" class="admin-btn">Clear</a><?php endif; ?>
                </form>
                <span class="admin-count"><?php echo count($users); ?> user<?php echo count($users)!=1?'s':''; ?></span>
            </div>

            <div class="transaction-section">
                <table class="admin-table">
                    <thead>
                        <tr><th>#</th><th>Full Name</th><th>Username</th><th>Email</th><th>Balance</th><th>Joined</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                        <td>@<?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>NRP <?php echo number_format($u['balance'],2); ?></td>
                        <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                        <td><a href="users.php?action=view&id=<?php echo $u['id']; ?>" class="admin-action-link">View / Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?><tr><td colspan="7" style="text-align:center;color:#777;padding:20px">No users found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        </div>
    </div>
</div>
<script src="../../assets/js/script.js"></script>
</body>
</html>
