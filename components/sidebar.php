<aside class="sidebar">
    <div class="logo">
        <img src="../assets/icons/Wallet.png" alt="EWallet logo" class="logo-icon">
        <span>EWallet</span>
    </div>

    <div class="menu-label">Menu</div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><img src="../assets/icons/dashboard.png" alt="Dashboard icon" /></div>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="expenses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><img src="../assets/icons/expenses.png" alt="Expenses icon" /></div>
                <span>Expenses</span>
            </a>
        </li>
        <li class="nav-item my-wallet-item">
            <a href="wallet.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wallet.php' ? 'active' : ''; ?>">
                <div class="nav-icon">
                    <img src="../assets/icons/solar_wallet-linear.png" alt="Wallet icon" class="my-wallet-icon" />
                </div>
                <span>My Wallet</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="budget.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><img src="../assets/icons/budget icon.png" alt="Budget icon" /></div>
                <span>Budget</span>
            </a>
        </li>
    </ul>

    <div class="settings">
        <a class="logout-btn" href="logout.php">Log Out</a>
    </div>
</aside>
