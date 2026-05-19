<?php require_once '../backend/pages/load_money_page.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EWallet - Load Funds</title>
  <?php
  $themeCssDir = '../assets/css/';
  $themeExtraLinks = [$themeCssDir . 'load_money.css'];
  include __DIR__ . '/../components/head_theme.php';
  ?>
</head>
<body>

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
        <!-- ICON SLOT ➜ icons/bell.svg -->
        <img src="../assets/icons/notificationbell.png" alt="Notifications" class="icon-md" />
      </button>
      <button class="icon-btn" aria-label="Profile">
        <!-- ICON SLOT ➜ icons/user.svg -->
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

  <!-- ── MAIN ── -->
  <main class="main-content">
    <h1 class="page-title">Load Funds</h1>

    <form class="load-money-form" method="post" action="">
      <div class="content-grid">

      <!-- Amount Card -->
      <div class="card amount-card">
        <label class="input-label" for="amountInput">Enter amount</label>
        <div class="amount-display">
          <span class="currency">NRP</span>
          <input
            type="number"
            id="amountInput"
            name="amount"
            class="amount-input"
            placeholder="XXX"
            min="0"
            max="<?php echo htmlspecialchars((string) WALLET_MAX_SINGLE_LOAD); ?>"
            step="0.01"
            value="<?php echo $current_amount; ?>"
          />
        </div>
        <div class="divider"></div>
        <div class="preset-amounts">
          <button class="preset-btn" type="button" data-amount="100">NRP 100</button>
          <button class="preset-btn" type="button" data-amount="500">NRP 500</button>
          <button class="preset-btn" type="button" data-amount="1000">NRP 1000</button>
        </div>
        <button class="load-btn" id="loadBtn" type="submit">Load Money</button>
        <div class="selected-source-display" id="selectedSourceDisplay">
          Funding Source: <span id="selectedSourceLabel"><?php echo htmlspecialchars($selected_funding_label); ?></span>
        </div>
        <?php if ($load_error !== ''): ?>
          <p class="load-money-error"><?php echo htmlspecialchars($load_error); ?></p>
        <?php endif; ?>
      </div>

      <!-- Funding Sources Card -->
      <div class="card funding-card">
        <h2 class="funding-title">Funding Sources</h2>

        <div class="funding-list">
          <label class="funding-option">
            <div class="funding-option-left">
              <img src="../assets/icons/boxicons_bank-filled.png" alt="" class="icon-funding" />
              <span>Bank transfer</span>
            </div>
            <input type="radio" name="fundingSource" value="bank" <?php echo $selected_funding_source === 'bank' ? 'checked' : ''; ?> />
            <span class="radio-custom"></span>
          </label>

          <label class="funding-option">
            <div class="funding-option-left">
              <img src="../assets/icons/famicons_card.png" alt="" class="icon-funding" />
              <span>Debit card</span>
            </div>
            <input type="radio" name="fundingSource" value="visa" <?php echo $selected_funding_source === 'visa' ? 'checked' : ''; ?> />
            <span class="radio-custom"></span>
          </label>

          <label class="funding-option">
            <div class="funding-option-left">
              <img src="../assets/icons/famicons_card.png" alt="" class="icon-funding" />
              <span>Credit card</span>
            </div>
            <input type="radio" name="fundingSource" value="credit" <?php echo $selected_funding_source === 'credit' ? 'checked' : ''; ?> />
            <span class="radio-custom"></span>
          </label>
        </div>

        <p class="security-note">Keep your transactions protected with EWallet</p>
      </div>

      </div>
    </form>
  </main>

  <?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>
