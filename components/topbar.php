<?php


$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
?>

    <link rel="stylesheet" href="../assets/css/style.css">
<header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Search">
                    <span class="search-icon">
                      <img src="../assets/icons/Vector.png" alt="Search icon" />
                    </span>
                </div>

                <div class="topbar-right">
                    <div class="dark-mode-toggle" id="darkModeToggle" title="Toggle dark mode">
                        <input type="checkbox" id="themeSwitch" />
                        <label for="themeSwitch" class="slider"></label>
                    </div>
                    <button class="icon-btn">
                        <img src="../assets/icons/notificationbell.png" alt="Notifications" />
                    </button>
                    <a href="../pages/setting.php" class="user-profile" style="text-decoration:none; color:inherit;">
                      <img src="../assets/icons/userprofile.png" alt="User" class="user-icon" />
                      <div class="user-info">
                        <div class="user-label"><?php echo $user_name; ?></div>
                        <div class="user-email"><?php echo $user_email; ?></div>
                      </div>
                    </a>
                </div>
            </header>
<script src="../assets/js/script.js"></script>
