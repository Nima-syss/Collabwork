<?php
$admin_name  = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
?>
<link rel="stylesheet" href="../../assets/css/style.css">
<header class="topbar">
    <div class="search-box">
        <input type="text" placeholder="Search..." id="adminSearch">
        <span class="search-icon"><img src="../../assets/icons/Vector.png" alt=""/></span>
    </div>
    <div class="topbar-right">
        <div class="dark-mode-toggle" id="darkModeToggle">
            <input type="checkbox" id="themeSwitch"/>
            <label for="themeSwitch" class="slider"></label>
        </div>
        <img src="../../assets/icons/userprofile.png" alt="Admin" class="user-icon"/>
        <div class="user-info">
            <div class="user-label">Administrator</div>
            <div class="user-email"><?php echo $admin_name; ?></div>
        </div>
    </div>
</header>
