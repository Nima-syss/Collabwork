<aside class="sidebar">
    <div class="logo">
        <img src="../../assets/icons/Wallet.png" alt="EWallet logo" class="logo-icon">
        <span>Admin</span>
    </div>
    <div class="menu-label">Admin Panel</div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':''; ?>">
                <div class="nav-icon"><img src="../../assets/icons/dashboard.png" alt=""/></div>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='users.php'?'active':''; ?>">
                <div class="nav-icon"><img src="../../assets/icons/userprofile.png" alt=""/></div>
                <span>Users</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="transactions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='transactions.php'?'active':''; ?>">
                <div class="nav-icon"><img src="../../assets/icons/solar_wallet-linear.png" alt=""/></div>
                <span>Transactions</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="expenses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='expenses.php'?'active':''; ?>">
                <div class="nav-icon"><img src="../../assets/icons/expenses.png" alt=""/></div>
                <span>Expenses</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="budgets.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='budgets.php'?'active':''; ?>">
                <div class="nav-icon"><img src="../../assets/icons/budget icon.png" alt=""/></div>
                <span>Budgets</span>
            </a>
        </li>
    </ul>
    <div class="settings">
        <a class="logout-btn" href="admin_logout.php">Log Out</a>
    </div>
</aside>
