<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../connection.php';

// Table required for chat persistence (was missing from repo migrations).
$mysqli->query(
    "CREATE TABLE IF NOT EXISTS chat_history (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        role VARCHAR(32) NOT NULL,
        message MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_chat_user_time (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$user_id = (int) $_SESSION['user_id'];

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$message = trim((string) ($body['message'] ?? ''));
$action  = strtolower(trim((string) ($body['action'] ?? '')));

// ---------------- CLEAR HISTORY ----------------
if ($action === 'clear') {
    $stmt = $mysqli->prepare(
        "DELETE FROM chat_history WHERE user_id = ?"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $mysqli->error]);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// ---------------- LOAD HISTORY ----------------
// action=load, OR empty POST with no message key (avoids mis-routing to "Message cannot be empty" without history).
$wants_history = ($action === 'load')
    || ($action === '' && $message === '' && !array_key_exists('message', $body));

if ($wants_history) {
    $stmt = $mysqli->prepare(
        "SELECT role, message
         FROM chat_history
         WHERE user_id = ?
         ORDER BY created_at ASC"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $mysqli->error, 'history' => []]);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $err, 'history' => []]);
        exit;
    }
    $res = $stmt->get_result();
    $history = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    echo json_encode(['history' => $history]);
    exit;
}

// ---------------- EMPTY MESSAGE CHECK ----------------
if ($message === '') {
    echo json_encode(['reply' => 'Message cannot be empty.']);
    exit;
}

// ---------------- GET USER BALANCE ----------------
$stmt = $mysqli->prepare(
    "SELECT balance FROM users WHERE id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


// ---------------- GET CURRENT MONTH EXPENSES ----------------
$current_month_start = date('Y-m-01');
$current_month_end   = date('Y-m-t');

$stmt = $mysqli->prepare(
    "SELECT category, SUM(amount) AS total
     FROM expenses
     WHERE user_id = ?
     AND YEAR(expense_date) = YEAR(CURDATE())
     AND MONTH(expense_date) = MONTH(CURDATE())
     GROUP BY category"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- SPENDING TODAY (local server date) ----------------
$stmt = $mysqli->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = ?
     AND expense_date = CURDATE()"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$today_spend = (float) ($today_row['total'] ?? 0);

// ---------------- GET ALL TIME EXPENSES ----------------
$stmt = $mysqli->prepare(
    "SELECT category, SUM(amount) AS total
     FROM expenses
     WHERE user_id = ?
     GROUP BY category"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- GET MONTHLY EXPENSES BREAKDOWN ----------------
$stmt = $mysqli->prepare(
    "SELECT
        DATE_FORMAT(expense_date, '%M %Y') AS month_name,
        DATE_FORMAT(expense_date, '%Y-%m') AS month_key,
        category,
        SUM(amount) AS total
     FROM expenses
     WHERE user_id = ?
     GROUP BY month_key, month_name, category
     ORDER BY month_key DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- TOTAL SPENT PER CALENDAR MONTH ----------------
$stmt = $mysqli->prepare(
    "SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month_key,
            DATE_FORMAT(expense_date, '%M %Y') AS month_label,
            COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = ?
     GROUP BY month_key, month_label
     ORDER BY month_key DESC
     LIMIT 36"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$month_totals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();



// ---------------- GET BUDGETS FOR CURRENT MONTH ----------------
$stmt = $mysqli->prepare(
    "SELECT category, monthly_limit, used
     FROM budgets
     WHERE user_id = ?
     AND budget_month = ?"
);
$stmt->bind_param("is", $user_id, $current_month_start);
$stmt->execute();
$budgets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- GET WALLET TRANSACTIONS ----------------
$stmt = $mysqli->prepare(
    "SELECT type, amount, created_at
     FROM transactions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 10"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- GET CHAT HISTORY ----------------
$stmt = $mysqli->prepare(
    "SELECT role, message
     FROM chat_history
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 10"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = array_reverse(
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
);
$stmt->close();

// ---------------- BUILD AI CONTEXT ----------------
$current_month_label = date('F Y');       // May 2026
$today_label         = date('l, F j, Y'); // Wednesday, May 6, 2026

$context  = "You are a financial assistant for EWallet.\n";
$context .= "Today: " . $today_label . "\n";
$context .= "Current month: " . $current_month_label . "\n\n";

$context .= "WALLET BALANCE: NRP " . number_format($user['balance'] ?? 0, 2) . "\n\n";

// Current month expenses
$month_total = 0;
$context .= "EXPENSES THIS MONTH (" . $current_month_label . "):\n";
if (empty($expenses)) {
    $context .= "No expenses this month.\n";
} else {
    foreach ($expenses as $e) {
        $context .= "- " . $e['category'] . ": NRP " . number_format($e['total'], 2) . "\n";
        $month_total += (float)$e['total'];
    }
}
$context .= "TOTAL SPENT THIS MONTH: NRP " . number_format($month_total, 2) . "\n\n";

$context .= "SPENDING TODAY (all categories, " . $today_label . "): NRP " . number_format($today_spend, 2) . "\n\n";

$context .= "TOTAL SPENT BY CALENDAR MONTH (all expense categories):\n";
if (empty($month_totals)) {
    $context .= "No expenses recorded.\n";
} else {
    foreach ($month_totals as $mt) {
        $context .= "- " . $mt['month_label'] . " (" . $mt['month_key'] . "): NRP " . number_format((float)$mt['total'], 2) . "\n";
    }
}
$context .= "\n";

// Budget for current month with exact numbers
$total_budget = 0;
$total_used   = 0;
$context .= "BUDGET FOR " . strtoupper($current_month_label) . " (exact numbers from database):\n";
if (empty($budgets)) {
    $context .= "No budget set for this month.\n";
} else {
    foreach ($budgets as $b) {
        $limit      = (float)$b['monthly_limit'];
        $used       = (float)$b['used'];
        if ($b['category'] === 'Unbudgeted') {
            $context .= "- Unbudgeted:\n";
            $context .= "  No monthly spending cap (tracking only).\n";
            $context .= "  Amount tracked this month: NRP " . number_format($used, 2) . "\n\n";
            continue;
        }
        $remaining  = $limit - $used;
        $over       = $used - $limit;
        $status     = ($limit > 0 && $used > $limit)
                        ? 'OVER LIMIT by NRP ' . number_format($over, 2)
                        : (($limit > 0 && $used >= $limit) ? 'AT OR OVER LIMIT' : 'ON TRACK');
        $context .= "- " . $b['category'] . ":\n";
        $context .= "  Limit: NRP " . number_format($limit, 2) . "\n";
        $context .= "  Used: NRP "  . number_format($used, 2) . "\n";
        $context .= "  Remaining: NRP " . number_format($remaining, 2) . "\n";
        $context .= "  Status: " . $status . "\n";
        $total_budget += $limit;
        $total_used   += $used;
    }
    $total_remaining = $total_budget - $total_used;
    $context .= "\nTOTAL BUDGET: NRP "    . number_format($total_budget, 2) . "\n";
    $context .= "TOTAL USED: NRP "        . number_format($total_used, 2) . "\n";
    $context .= "TOTAL REMAINING: NRP "   . number_format($total_remaining, 2) . "\n\n";
}

// Monthly history
$context .= "MONTHLY EXPENSE HISTORY:\n";
$current_group = '';
foreach ($monthly_expenses as $me) {
    if ($me['month_name'] !== $current_group) {
        $current_group = $me['month_name'];
        $context .= "\n" . $current_group . ":\n";
    }
    $context .= "  - " . $me['category'] . ": NRP " . number_format($me['total'], 2) . "\n";
}

// Recent transactions
$context .= "\nRECENT TRANSACTIONS:\n";
foreach ($transactions as $t) {
    $context .= "- " . ucfirst($t['type'])
             . ": NRP " . number_format($t['amount'], 2)
             . " on " . date('M j, Y', strtotime($t['created_at'])) . "\n";
}

// Strict instructions
$context .= "\n--- STRICT RULES ---\n";
$context .= "1. Use ONLY the exact numbers above. Never calculate or estimate.\n";
$context .= "2. For current month always use " . $current_month_label . " data above.\n";
$context .= "3. Answer in 2-4 short sentences max.\n";
$context .= "4. Always include exact NRP amounts.\n";
$context .= "5. If budget is OVER LIMIT say exactly how much over.\n";
$context .= "6. SAVINGS CALCULATION: Use TOTAL BUDGET and TOTAL USED lines above (Unbudgeted is excluded from those totals). Savings = TOTAL BUDGET minus TOTAL USED for capped categories only.\n";
$context .= "   - If result is POSITIVE: 'You saved NRP [amount] this month.'\n";
$context .= "   - If result is ZERO or NEGATIVE: 'You did not save any money. You overspent by NRP [amount].'\n";
$context .= "7. For 'what did I spend today' / Nepali like 'aaja kati kharch' / 'today': answer using SPENDING TODAY only.\n";
$context .= "8. For spending in a specific month / Nepali like 'yo mahina' / 'last month': use TOTAL SPENT BY CALENDAR MONTH lines.\n";
$context .= "9. Plain text only. No markdown, no bullet points.\n";
$context .= "10. Never make up numbers not in the data above.\n";

// ---------------- BUILD MESSAGE ARRAY ----------------
$messages = [];

// System message
$messages[] = [
    'role'    => 'system',
    'content' => $context
];

// Old chat history
foreach ($history as $chat) {
    $messages[] = [
        'role'    => $chat['role'],
        'content' => $chat['message']
    ];
}

// New user message
$messages[] = [
    'role'    => 'user',
    'content' => $message
];

// ---------------- SAVE USER MESSAGE ----------------
$role = "user";
$stmt = $mysqli->prepare(
    "INSERT INTO chat_history(user_id, role, message) VALUES (?, ?, ?)"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['reply' => 'Database error: could not save message. Check that MySQL is running and chat_history exists.']);
    exit;
}
$stmt->bind_param("iss", $user_id, $role, $message);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['reply' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmt->close();

// ---------------- OPENROUTER API ----------------
// Set key via environment variable OPENROUTER_API_KEY, or backend/config.local.php:
//   <?php return ['openrouter_api_key' => 'sk-or-v1-...']; (do not commit real keys)
$OPENROUTER_KEY = getenv('OPENROUTER_API_KEY') ?: '';
if ($OPENROUTER_KEY === '' && is_readable(__DIR__ . '/../config.local.php')) {
    $local = include __DIR__ . '/../config.local.php';
    if (is_array($local) && !empty($local['openrouter_api_key'])) {
        $OPENROUTER_KEY = trim((string) $local['openrouter_api_key']);
    }
}
if ($OPENROUTER_KEY === '') {
    echo json_encode([
        'reply' => 'Chatbot is not configured: add your OpenRouter API key. Set the OPENROUTER_API_KEY environment variable for PHP, or create backend/config.local.php with return [\'openrouter_api_key\' => \'sk-or-v1-...\']; (see config.local.example.php).',
    ]);
    exit;
}

if (!function_exists('curl_init')) {
    echo json_encode(['reply' => 'PHP cURL extension is disabled. Enable curl in php.ini for XAMPP, then restart Apache.']);
    exit;
}

$payload = json_encode([
    'models' => [
        'openai/gpt-4o-mini',
        'meta-llama/llama-3.1-8b-instruct:free',
        'mistralai/mistral-7b-instruct:free',
    ],
    'route'      => 'fallback',
    'messages'   => $messages,
    'max_tokens' => 300
]);

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');

$referer = (!empty($_SERVER['HTTP_ORIGIN']))
    ? $_SERVER['HTTP_ORIGIN']
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENROUTER_KEY,
        'HTTP-Referer: ' . $referer,
        'X-Title: EWallet Chatbot'
    ]
]);

$response = curl_exec($ch);
$curlErr  = curl_errno($ch) ? curl_error($ch) : '';
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr !== '') {
    echo json_encode(['reply' => 'Connection error calling OpenRouter: ' . $curlErr]);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    echo json_encode(['reply' => 'Invalid JSON from AI service. Raw: ' . substr((string) $response, 0, 240)]);
    exit;
}

if (!empty($data['error'])) {
    $msg = is_array($data['error'])
        ? ($data['error']['message'] ?? json_encode($data['error']))
        : (string) $data['error'];
    echo json_encode(['reply' => 'OpenRouter error' . ($httpCode ? ' (HTTP ' . $httpCode . ')' : '') . ': ' . $msg]);
    exit;
}

if ($httpCode >= 400) {
    echo json_encode(['reply' => 'OpenRouter returned HTTP ' . $httpCode . '. Check API key and model access.']);
    exit;
}

$reply = $data['choices'][0]['message']['content'] ?? null;
if ($reply === null || trim((string) $reply) === '') {
    echo json_encode(['reply' => 'No reply in AI response. Check model name (openrouter/auto) and account credits at openrouter.ai.']);
    exit;
}

// ---------------- SAVE BOT REPLY ----------------
$role = "assistant";
$stmt = $mysqli->prepare(
    "INSERT INTO chat_history(user_id, role, message) VALUES (?, ?, ?)"
);
if ($stmt) {
    $stmt->bind_param("iss", $user_id, $role, $reply);
    $stmt->execute();
    $stmt->close();
}

// ---------------- FINAL OUTPUT ----------------
echo json_encode(['reply' => $reply]);
exit;
?>