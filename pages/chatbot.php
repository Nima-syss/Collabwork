<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$display_name = htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_username'] ?? 'there', ENT_QUOTES, 'UTF-8');

/** True if OpenRouter key is set (env or backend/config.local.php). */
function ewallet_openrouter_configured(): bool
{
    $env = getenv('OPENROUTER_API_KEY');
    if (is_string($env) && strlen(trim($env)) > 20) {
        return true;
    }
    $path = __DIR__ . '/../backend/config.local.php';
    if (!is_readable($path)) {
        return false;
    }
    $local = include $path;
    if (!is_array($local)) {
        return false;
    }
    $key = trim((string) ($local['openrouter_api_key'] ?? ''));
    return strlen($key) > 20;
}

$openrouter_ok = ewallet_openrouter_configured();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat – EWallet</title>
    <?php
    $themeCssDir = '../assets/css/';
    $chatbotCssPath = __DIR__ . '/../assets/css/chatbot.css';
    $chatbotCssVer = is_file($chatbotCssPath) ? (string) filemtime($chatbotCssPath) : '1';
    $themeExtraLinks = [$themeCssDir . 'chatbot.css?v=' . rawurlencode($chatbotCssVer)];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body class="ew-chat-layout">
<div class="container">
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content main-content--chat">
        <?php include '../components/topbar.php'; ?>

        <div class="chat-page">
            <div class="chat-page__head">
                <h1 class="chat-page__title">Chat</h1>
                <button type="button" class="chat-page__clear" id="clear-btn">Clear</button>
            </div>

            <div class="chat-box">
                <?php if (!$openrouter_ok): ?>
                <p class="chat-setup-note" role="status">
                    Add <code>openrouter_api_key</code> in <code>backend/config.local.php</code>
                    (get a key at <a href="https://openrouter.ai/keys" target="_blank" rel="noopener">openrouter.ai/keys</a>), then refresh.
                </p>
                <?php endif; ?>

                <div
                    class="chat-messages"
                    id="chat-messages"
                    data-display-name="<?php echo $display_name; ?>"
                ></div>

                <div class="chat-input-row">
                    <input type="text" id="chat-input" placeholder="Type a message…" autocomplete="off" />
                    <button type="button" id="chat-send">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$chatbotJs = __DIR__ . '/../assets/js/chatbot.js';
$chatbotJsVer = is_file($chatbotJs) ? (string) filemtime($chatbotJs) : '1';
?>
<?php include __DIR__ . '/../components/script_main.php'; ?>
<script src="../assets/js/chatbot.js?v=<?php echo htmlspecialchars(rawurlencode($chatbotJsVer), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
