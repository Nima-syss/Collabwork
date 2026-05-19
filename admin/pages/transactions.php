<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once '../../backend/connection.php';

$search = trim($_GET['search'] ?? '');
$type   = $_GET['type'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 20;
$offset = ($page - 1) * $per;

$where  = [];
$params = [];
$types  = '';
if ($search) {
    $like = '%'.$mysqli->real_escape_string($search).'%';
    $where[] = "(u.fullname LIKE '$like' OR u.email LIKE '$like' OR t.description LIKE '$like')";
}
if ($type) {
    $where[] = "t.type = '".$mysqli->real_escape_string($type)."'";
}
$wClause = $where ? 'WHERE '.implode(' AND ', $where) : '';

$total = $mysqli->query("SELECT COUNT(*) AS c FROM transactions t JOIN users u ON t.user_id=u.id $wClause")->fetch_assoc()['c'];
$pages = max(1, ceil($total / $per));

$txs = $mysqli->query(
    "SELECT t.*, u.fullname, u.email FROM transactions t
     JOIN users u ON t.user_id = u.id
     $wClause ORDER BY t.created_at DESC LIMIT $per OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

$tx_types = ['load','send','receive','expense'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Transactions – Admin</title>
    <?php
    $themeCssDir = '../../assets/css/';
    $themeExtraLinks = ['../admin.css'];
    include __DIR__ . '/../../components/head_theme.php';
    ?>
</head>
<body>
<div class="container">
    <?php include '../components/admin_sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/admin_topbar.php'; ?>
        <div class="dashboard">
            <h1 class="dashboard-title">Transactions</h1>

            <div class="admin-toolbar">
                <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <input class="admin-search-input" type="text" name="search" placeholder="Search user or description…" value="<?php echo htmlspecialchars($search); ?>">
                    <select class="admin-input" name="type" style="width:auto;padding:8px 12px">
                        <option value="">All Types</option>
                        <?php foreach ($tx_types as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $type===$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="admin-btn admin-primary" type="submit">Filter</button>
                    <a href="transactions.php" class="admin-btn">Clear</a>
                </form>
                <span class="admin-count"><?php echo number_format($total); ?> record<?php echo $total!=1?'s':''; ?></span>
            </div>

            <div class="transaction-section">
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>User</th><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($txs as $tx):
                        $is_out = !in_array($tx['type'],['load','receive']);
                    ?>
                    <tr>
                        <td><?php echo $tx['id']; ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($tx['fullname']); ?></div>
                            <div style="font-size:12px;color:#777"><?php echo htmlspecialchars($tx['email']); ?></div>
                        </td>
                        <td><span class="admin-badge admin-badge-<?php echo $tx['type']; ?>"><?php echo ucfirst($tx['type']); ?></span></td>
                        <td class="<?php echo $is_out?'tx-out':'tx-in'; ?>"><?php echo ($is_out?'- ':'+ ').'NRP '.number_format($tx['amount'],2); ?></td>
                        <td><?php echo htmlspecialchars($tx['description'] ?? '—'); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($tx['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$txs): ?><tr><td colspan="6" style="text-align:center;color:#777;padding:20px">No transactions found.</td></tr><?php endif; ?>
                    </tbody>
                </table>

                <?php if ($pages > 1): ?>
                <div class="admin-pagination">
                    <?php for ($p=1; $p<=$pages; $p++): ?>
                    <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>"
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
