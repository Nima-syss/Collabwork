<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWallet - Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>

        <div class="main-content">
            <?php include '../components/topbar.php'; ?>

            <div class="settings-page">
                <div class="settings-grid">
                    <section class="settings-panel profile-panel">
                        <div class="profile-avatar"></div>
                        <div class="settings-user-name"><?php echo $user_name; ?></div>
                        <div class="settings-user-email"><?php echo $user_email; ?></div>

                        <div class="settings-links">
                            <a href="#" class="settings-link">View users</a>
                            <a href="#" class="settings-link">Change Password</a>
                        </div>
                    </section>

                    <section class="settings-panel preferences-panel">
                        <div class="settings-panel-header">
                            <div class="settings-panel-icon">
                                <img src="../assets/icons/Vector1.png" alt="Preferences Icon" class="settings-panel-icon-img" />
                            </div>
                            <h2 class="settings-panel-title">Preferences</h2>
                        </div>

                        <div class="settings-field">
                            <label class="settings-label" for="languageSelect">Language</label>
                            <div class="settings-select-wrap">
                                <select id="languageSelect" onchange="handleLanguageChange()">
                                    <option value="en-US">English(US)</option>
                                    <option value="en-GB">English(UK)</option>
                                </select>
                                <span class="settings-select-icon">Globe</span>
                            </div>
                        </div>

                        <div class="settings-field">
                            <label class="settings-label" for="modeSelect">Change Mode</label>
                            <div class="settings-select-wrap">
                                <select id="modeSelect" onchange="handleModeChange()">
                                    <option value="light">Light Mode</option>
                                    <option value="dark">Dark Mode</option>
                                    <option value="auto">Auto</option>
                                </select>
                                <span class="settings-select-icon">v</span>
                            </div>
                        </div>
                    </section>

                    <section class="settings-panel notifications-panel">
                        <div class="settings-panel-header">
                            <div class="settings-panel-icon">
                                <img src="../assets/icons/notificationbell.png" alt="Notifications Icon" class="settings-panel-icon-img" />
                            </div>
                            <h2 class="settings-panel-title">Notifications</h2>
                        </div>

                        <button class="settings-option" type="button" onclick="toggleNotification('email')">Email Notifications</button>
                        <button class="settings-option" type="button" onclick="toggleNotification('push')">Push Notifications</button>
                        <button class="settings-option" type="button" onclick="toggleNotification('expense')">Expense Alerts</button>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
