<?php
$user_name  = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
?>

<header class="topbar">
    <div class="search-box" id="searchBoxWrapper">
        <input
            type="text"
            placeholder="Search"
            id="globalSearchInput"
            autocomplete="off"
        />
        <span class="search-icon">
            <img src="../assets/icons/Vector.png" alt="Search icon" />
        </span>

        <!-- Dropdown -->
        <div class="search-dropdown" id="searchDropdown">

            

            <!-- Recent searches -->
            <div class="search-dropdown-section" id="recentSection">
                <div class="search-dropdown-label">Recent Searches</div>
                <div id="recentList"></div>
            </div>

            <!-- Search results -->
            <div class="search-dropdown-section" id="resultsSection" style="display:none;">
                <div class="search-dropdown-label">Results</div>
                <div id="resultsList"></div>
            </div>

        </div>
    </div>

    <div class="topbar-right">
        <div class="dark-mode-toggle" id="darkModeToggle" title="Toggle dark mode">
            <input type="checkbox" id="themeSwitch" />
            <label for="themeSwitch" class="slider"></label>
        </div>
        <div class="notif-wrapper" id="notifWrapper">
            <button class="icon-btn notif-btn" id="notifBtn" aria-label="Notifications" aria-expanded="false">
                <img src="../assets/icons/notificationbell.png" alt="Notifications" />
                <span class="notif-badge" id="notifBadge" style="display:none;"></span>
            </button>

            <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Notifications">
                <div class="notif-panel-header">
                    <span class="notif-panel-title">Notifications</span>
                    <button class="notif-mark-all" id="notifMarkAll">Mark all read</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty" id="notifEmpty">
                        <span>🔔</span>
                        <p>You're all caught up!</p>
                    </div>
                </div>
            </div>
        </div>
        <a href="../pages/setting.php" class="user-profile" style="text-decoration:none; color:inherit;">
            <img src="../assets/icons/userprofile.png" alt="User" class="user-icon" />
            <div class="user-info">
                <div class="user-label"><?php echo $user_name; ?></div>
                <div class="user-email"><?php echo $user_email; ?></div>
            </div>
        </a>
    </div>
</header>