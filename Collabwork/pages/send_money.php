<?php require_once '../backend/pages/send_money_page.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EWallet - Transfer Funds</title>
  <?php
  $themeCssDir = '../assets/css/';
  $themeExtraLinks = [
    $themeCssDir . 'load_money.css',
    $themeCssDir . 'send_money.css',
  ];
  include __DIR__ . '/../components/head_theme.php';
  ?>
</head>
<body>

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
    <h1 class="page-title">Transfer Funds</h1>

    <form class="send-money-form" id="sendMoneyForm" method="post" action="" data-current-username="<?php echo $current_username; ?>">
    <div class="content-grid">

      <div class="left-col">
        <div class="card recipient-card">
          <label class="card-label">Select Recipient</label>
          <div class="search-box">
            <img src="../assets/icons/username.png" alt="" class="icon-input-left" />
            <input
              type="text"
              id="recipientInput"
              name="recipient_username"
              class="search-input"
              placeholder="username"
              autocomplete="off"
              value="<?php echo htmlspecialchars($recipient_username_value); ?>"
            />
            <button class="search-btn" id="searchBtn" type="button" aria-label="Search">
              <span class="search-btn-icon" aria-hidden="true"></span>
            </button>
          </div>
        </div>

        <div class="card amount-card">
          <label class="input-label" for="amountInput">Enter amount</label>
          <div class="amount-display">
            <span class="currency">Rs</span>
            <input
              type="number"
              id="amountInput"
              name="amount"
              class="amount-input"
              placeholder="200000"
              min="0"
              step="0.01"
              value="<?php echo htmlspecialchars($amount_value); ?>"
            />
          </div>
          <div class="divider"></div>
          <p class="balance-line">Balance <span id="balanceDisplay"><?php echo number_format($total_balance, 2); ?></span></p>
        </div>
      </div>

      <div class="card summary-card">
        <h2 class="summary-title">Transaction Summary</h2>

        <div class="summary-balance-row">
          <div class="summary-balance-info">
            <img src="../assets/icons/Wallet.png" alt="" class="icon-sm summary-wallet-icon" />
            <div>
              <p class="summary-balance-amount" id="summaryBalanceAmount" data-visible-balance="NRP <?php echo number_format($total_balance, 2); ?>" data-hidden-balance="NRP XXXX">NRP <?php echo number_format($total_balance, 2); ?></p>
              <p class="summary-balance-label">Balance</p>
            </div>
          </div>
          <button class="summary-eye-btn" id="toggleSummaryBalance" type="button" aria-label="Show or hide balance" aria-pressed="false">
            <img src="../assets/icons/mdi_eye-off.png" alt="Toggle" class="icon-sm" />
          </button>
        </div>

        <div class="summary-field">
          <label class="summary-label">Recipient:</label>
          <div class="summary-value-box" id="summaryRecipient"><?php echo htmlspecialchars($recipient_username_value); ?></div>
        </div>

        <div class="summary-field">
          <label class="summary-label">Amount Transferred:</label>
          <div class="summary-value-box" id="summaryAmount"><?php echo $amount_value !== '' ? 'Rs ' . htmlspecialchars($amount_value) : ''; ?></div>
        </div>

        <button class="done-btn" id="doneBtn" type="submit">Done</button>
        <?php if ($send_error !== ''): ?>
          <p class="transfer-feedback transfer-feedback-error"><?php echo htmlspecialchars($send_error); ?></p>
        <?php elseif ($send_success !== ''): ?>
          <p class="transfer-feedback transfer-feedback-success"><?php echo htmlspecialchars($send_success); ?></p>
        <?php endif; ?>

        <p class="thank-you-note">Thank you for choosing EWallet</p>
      </div>

    </div>
    </form>
  </main>

  <?php include __DIR__ . '/../components/script_main.php'; ?>
</body>
</html>
