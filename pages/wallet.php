<?php require_once '../backend/pages/wallet_page.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet</title>
    <?php
    $themeCssDir = '../assets/css/';
    $themeExtraLinks = [$themeCssDir . 'wallet.css'];
    include __DIR__ . '/../components/head_theme.php';
    ?>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>

        <div class="main-content">
            <?php include '../components/topbar.php'; ?>

            <div class="wallet-page">
                <h1 class="dashboard-title wallet-title">My Wallet</h1>

                <div class="wallet-top-grid">
                    <section class="wallet-balance-card">
                        <div class="wallet-chip" aria-hidden="true"></div>
                        <div class="wallet-balance-label">Available Balance</div>
                        <div class="wallet-balance-value">NRP <?php echo $wallet_balance; ?></div>

                        <div class="wallet-card-footer">
                            <div class="wallet-owner"><?php echo $user_name; ?></div>
                            <div class="wallet-brand" aria-hidden="true">
                                <span class="wallet-brand-circle wallet-brand-circle-left"></span>
                                <span class="wallet-brand-circle wallet-brand-circle-right"></span>
                            </div>
                        </div>
                    </section>

                    <section class="wallet-actions-grid">
                        <button
                            class="wallet-action-card wallet-action-card-load"
                            type="button"
                            onclick="window.location.href='load_money.php'"
                        >
                            <span class="wallet-action-icon-wrap">
                                <img src="../assets/icons/solar_wallet-linear.png" alt="Load icon" class="wallet-action-icon wallet-action-icon-load">
                            </span>
                            <span class="wallet-action-label">Load</span>
                        </button>

                        <button class="wallet-action-card" type="button"
                            onclick="window.location.href='send_money.php'">
                            <span class="wallet-action-icon-wrap">
                                <img src="../assets/icons/send button.png" alt="Send icon" class="wallet-action-icon">
                            </span>
                            <span class="wallet-action-label">Send</span>
                        </button>

                        <button class="wallet-action-card" type="button" onclick="window.location.href='qr.php'">
                            <span class="wallet-action-icon-wrap">
                                <img src="../assets/icons/Qrcode.png" alt="QR icon" class="wallet-action-icon">
                            </span>
                            <span class="wallet-action-label">QR</span>
                        </button>

                        <button class="wallet-action-card" type="button"
                        onclick="window.location.href='setting.php'">
                            <span class="wallet-action-icon-wrap">
                                <img src="../assets/icons/moreicon.png" alt="More icon" class="wallet-action-icon">
                            </span>
                            <span class="wallet-action-label">More</span>
                            
                        </button>
                    </section>
                </div>

                <section class="wallet-panel">
                    <div class="wallet-panel-header">
                        <h2 class="wallet-panel-title">Recent Activity</h2>
                    </div>

                    <?php if (!$transactions): ?>
                        <div class="wallet-empty-state">No wallet activity yet.</div>
                    <?php else: ?>
                        <div class="wallet-transaction-list">
                            <?php foreach ($transactions as $transaction): ?>
                                <?php
                                $type = $transaction['type'];
                                $amount = number_format((float) $transaction['amount'], 2);
                                // Treat only known incoming types as incoming; everything else is outgoing (e.g., send, expense).
                                $incoming_types = ['load', 'receive'];
                                $is_incoming = in_array($type, $incoming_types, true);
                                $direction_class = $is_incoming ? 'is-incoming' : 'is-outgoing';
                                $amount_prefix = $is_incoming ? '+ NRP ' : '- NRP ';
                                $type_label = $type === 'expense' ? 'Expense' : ucfirst($type);
                                $created_at = date('M d, Y h:i A', strtotime($transaction['created_at']));
                                $search_text = trim($type_label . ' ' . (string)($transaction['description'] ?? '') . ' ' . $created_at);
                                ?>
                                <article class="wallet-transaction-item" data-search-text="<?php echo htmlspecialchars($search_text); ?>">
                                    <div class="wallet-transaction-meta">
                                        <div class="wallet-transaction-type"><?php echo htmlspecialchars($type_label); ?></div>
                                        <div class="wallet-transaction-description"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                        <div class="wallet-transaction-time"><?php echo htmlspecialchars($created_at); ?></div>
                                    </div>
                                    <div class="wallet-transaction-amount <?php echo $direction_class; ?>">
                                        <?php echo htmlspecialchars($amount_prefix . $amount); ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../components/script_main.php'; ?>

    <!-- ── SUCCESS / ERROR TOAST ── -->
    <?php
    $toast_message = trim($_GET['message'] ?? '');
    $toast_type    = ($_GET['type'] ?? 'success') === 'error' ? 'error' : 'success';
    ?>
    <?php if ($toast_message !== ''): ?>
    <div
        id="pageToast"
        class="page-toast page-toast--<?php echo $toast_type; ?>"
        role="status"
        aria-live="polite"
        onclick="dismissToast()"
    >
        <p class="page-toast-title"><?php echo $toast_type === 'success' ? '✓ Success' : '✕ Error'; ?></p>
        <p class="page-toast-message"><?php echo htmlspecialchars($toast_message); ?></p>
    </div>
    <script>
    (function () {
        var toast = document.getElementById('pageToast');
        if (!toast) return;

        // Show after a tiny delay so the CSS transition fires
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });
        });

        // Auto-dismiss after 4 seconds (within the 3–5 s window)
        var timer = setTimeout(dismissToast, 4000);

        function dismissToast() {
            clearTimeout(timer);
            toast.classList.remove('is-visible');
            toast.addEventListener('transitionend', function () {
                toast.remove();
                // Clean the query string so a page-refresh doesn't re-show it
                var url = new URL(window.location.href);
                url.searchParams.delete('message');
                url.searchParams.delete('type');
                history.replaceState(null, '', url.toString());
            }, { once: true });
        }
        window.dismissToast = dismissToast;
    })();
    </script>
    <?php endif; ?>
</body>
</html>