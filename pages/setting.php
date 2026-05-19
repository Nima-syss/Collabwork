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
    <?php
    $themeCssDir = '../assets/css/';
    $themeExtraLinks = [$themeCssDir . 'expenses.css'];
    $settingsCssPath = __DIR__ . '/../assets/css/settings.css';
    $settingsCssVer = is_file($settingsCssPath) ? (string) filemtime($settingsCssPath) : '1';
    $themeTailLinks = [$themeCssDir . 'settings.css?v=' . rawurlencode($settingsCssVer)];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>

        <div class="main-content">
            <?php include '../components/topbar.php'; ?>

            <div class="dashboard expenses-page settings-page">
                <header class="settings-page-header">
                    <h1 class="dashboard-title">Settings</h1>
                </header>

                <div class="settings-grid">
                    <section class="settings-panel budget-panel profile-panel" aria-label="Profile">
                        <div class="profile-avatar">
                            <img src="../assets/icons/userprofile.png" alt="" class="profile-avatar-icon" />
                        </div>

                        <div class="settings-user-name"><?php echo $user_name; ?></div>
                        <div class="settings-user-email"><?php echo $user_email; ?></div>

                        <div class="settings-links">
                            <a href="#" class="settings-link" id="openChangePasswordModal">Change password</a>
                        </div>
                    </section>

                    <section class="settings-panel budget-panel preferences-panel" aria-label="Preferences">
                        <div class="settings-panel-header">
                            <div class="settings-panel-icon" aria-hidden="true">
                                <img src="../assets/icons/Vector1.png" alt="" class="settings-panel-icon-img" />
                            </div>
                            <h2 class="settings-panel-title">Preferences</h2>
                        </div>
                        <p class="settings-card-subtitle">Language and appearance for this device.</p>

                        <div class="settings-field">
                            <label class="settings-label" for="languageSelect">Language</label>
                            <div class="settings-select-wrap">
                                <select id="languageSelect" onchange="handleLanguageChange()">
                                    <option value="en-US">English (US)</option>
                                    <option value="en-GB">English (UK)</option>
                                </select>
                                <span class="settings-select-icon">
                                    <img src="../assets/icons/tabler_world.png" alt="" class="settings-panel-icon-img" />
                                </span>
                            </div>
                        </div>

                        <div class="settings-field">
                            <label class="settings-label" for="modeSelect">Appearance</label>
                            <div class="settings-select-wrap">
                                <select id="modeSelect" onchange="handleModeChange()">
                                    <option value="light">Light mode</option>
                                    <option value="dark">Dark mode</option>
                                    <option value="auto">Auto</option>
                                </select>
                                <span class="settings-select-icon">
                                    <img src="../assets/icons/ic_twotone-arrow-drop-down.png" alt="" class="settings-panel-icon-img" />
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="settings-panel budget-panel notifications-panel" aria-label="Notifications">
                        <div class="settings-panel-header">
                            <div class="settings-panel-icon" aria-hidden="true">
                                <img src="../assets/icons/notificationbell.png" alt="" class="settings-panel-icon-img" />
                            </div>
                            <h2 class="settings-panel-title">Notifications</h2>
                        </div>
                        <p class="settings-card-subtitle">Choose how we reach you about activity and budgets.</p>

                        <div class="settings-notifications-stack">
                            <button class="settings-option" type="button" data-notif="email" onclick="toggleNotification('email')">Email notifications</button>
                            <button class="settings-option" type="button" data-notif="push" onclick="toggleNotification('push')">Push notifications</button>
                            <button class="settings-option" type="button" data-notif="expense" onclick="toggleNotification('expense')">Expense alerts</button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

<!-- ── Change Password Modal ──────────────────────────────────────────── -->
<div id="changePasswordModal" class="cp-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="cpModalTitle">
    <div class="cp-modal">
        <div class="cp-modal-header">
            <h2 class="cp-modal-title" id="cpModalTitle">Change Password</h2>
            <button class="cp-modal-close" id="closeChangePasswordModal" aria-label="Close">&times;</button>
        </div>

        <div id="cpMessage" class="cp-message" style="display:none;"></div>

        <div class="cp-modal-body">
            <div class="cp-field">
                <label class="settings-label" for="cpCurrentPassword">Current Password</label>
                <div class="cp-input-wrap">
                    <input type="password" id="cpCurrentPassword" class="cp-input" placeholder="Enter current password" autocomplete="current-password" />
                    <button type="button" class="cp-eye-btn" data-target="cpCurrentPassword" aria-label="Toggle visibility">
                        <img src="../assets/icons/mdi_eye-off.png" alt="" class="cp-eye-icon" />
                    </button>
                </div>
            </div>

            <div class="cp-field">
                <label class="settings-label" for="cpNewPassword">New Password</label>
                <div class="cp-input-wrap">
                    <input type="password" id="cpNewPassword" class="cp-input" placeholder="Enter new password" autocomplete="new-password" />
                    <button type="button" class="cp-eye-btn" data-target="cpNewPassword" aria-label="Toggle visibility">
                        <img src="../assets/icons/mdi_eye-off.png" alt="" class="cp-eye-icon" />
                    </button>
                </div>
            </div>

            <div class="cp-field">
                <label class="settings-label" for="cpConfirmPassword">Confirm New Password</label>
                <div class="cp-input-wrap">
                    <input type="password" id="cpConfirmPassword" class="cp-input" placeholder="Re-enter new password" autocomplete="new-password" />
                    <button type="button" class="cp-eye-btn" data-target="cpConfirmPassword" aria-label="Toggle visibility">
                        <img src="../assets/icons/mdi_eye-off.png" alt="" class="cp-eye-icon" />
                    </button>
                </div>
            </div>
        </div>

        <div class="cp-modal-footer">
            <button type="button" class="cp-btn cp-btn-cancel" id="cancelChangePassword">Cancel</button>
            <button type="button" class="cp-btn cp-btn-save" id="submitChangePassword">Save Password</button>
        </div>
    </div>
</div>
<!-- ── End Change Password Modal ──────────────────────────────────────── -->

<?php include __DIR__ . '/../components/script_main.php'; ?>
<?php
$ewSetJs = __DIR__ . '/../assets/js/settings.js';
$ewSetVer = is_file($ewSetJs) ? (int) filemtime($ewSetJs) : 1;
?>
<script src="../assets/js/settings.js?v=<?php echo $ewSetVer; ?>"></script>
</body>
</html>
