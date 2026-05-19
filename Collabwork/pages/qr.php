<?php require_once '../backend/pages/wallet_page.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EWallet - Share Your QR Code</title>
  <?php
  $themeCssDir = '../assets/css/';
  $themeExtraLinks = [
    $themeCssDir . 'load_money.css',
    $themeCssDir . 'qr.css',
  ];
  include __DIR__ . '/../components/head_theme.php';
  ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
  <?php
    $qr_username = $user_username ?? 'username';
    $qr_email = $user_email ?? 'email@xyz.com';
    $qr_text = $qr_username;  
  ?>

  <!-- ── NAVBAR ── -->
  <nav class="navbar">
    <div class="nav-left">
      <div class="logo">
        <img src="../assets/icons/Wallet.png" alt="EWallet logo" class="logo-icon">
        <span>EWallet</span>
    </div>
      <a href="wallet.php" class="return-link">Return</a>
    </div>

    <div class="nav-right">
      <button class="icon-btn" aria-label="Notifications">
        <img src="../assets/icons/notificationbell.png" alt="Notifications" class="icon-md" />
      </button>
      <button class="icon-btn" aria-label="Profile">
        <img src="../assets/icons/userprofile.png" alt="Profile" class="icon-md" />
      </button>
      <div class="nav-user">
        <div class="user-info">
          <div class="user-label"><?php echo $user_name; ?></div>
          <div class="user-email"><?php echo $user_email; ?></div>
        </div>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <h1 class="page-title">Share Your QR Code</h1>

    <div class="qr-card" data-qr-text="<?php echo htmlspecialchars($qr_text); ?>">
      <div id="qrcode" class="qr-image-wrapper" aria-label="QR code"></div>

      <p class="qr-username"><?php echo $qr_username; ?></p>
      <p class="qr-email">@<?php echo $qr_email; ?></p>

      <button class="download-btn" id="downloadBtn" type="button">
        <img src="../assets/icons/material-symbols_download.png" alt="" class="icon-download" />
        Download
      </button>
    </div>
  </main>

  <?php include __DIR__ . '/../components/script_main.php'; ?>
  <?php
  $ewQrJs = __DIR__ . '/../assets/js/qr.js';
  $ewQrVer = is_file($ewQrJs) ? (int) filemtime($ewQrJs) : 1;
  ?>
  <script src="../assets/js/qr.js?v=<?php echo $ewQrVer; ?>"></script>
</body>
</html>