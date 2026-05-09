<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../connection.php';

$user_id = (int) $_SESSION['user_id'];

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$message = trim($body['message'] ?? '');
$action  = trim($body['action']  ?? '');

// ---------------- CLEAR HISTORY ----------------
if ($action === 'clear') {
    $stmt = $mysqli->prepare(
        "DELETE FROM chat_history WHERE user_id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// ---------------- LOAD HISTORY ----------------
if ($action === 'load') {
    $stmt = $mysqli->prepare(
        "SELECT role, message
         FROM chat_history
         WHERE user_id = ?
         ORDER BY created_at ASC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        $remaining  = $limit - $used;
        $over       = $used - $limit;
        $status     = $used >= $limit
                        ? 'OVER LIMIT by NRP ' . number_format($over, 2)
                        : 'ON TRACK';
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
$context .= "6. SAVINGS CALCULATION: Savings = TOTAL BUDGET - TOTAL SPENT.\n";
$context .= "   - If result is POSITIVE: 'You saved NRP [amount] this month.'\n";
$context .= "   - If result is ZERO or NEGATIVE: 'You did not save any money. You overspent by NRP [amount].'\n";
$context .= "7. Plain text only. No markdown, no bullet points.\n";
$context .= "8. Never make up numbers not in the data above.\n";

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
$stmt->bind_param("iss", $user_id, $role, $message);
$stmt->execute();
$stmt->close();

// ---------------- OPENROUTER API ----------------
$OPENROUTER_KEY = "API-KEY-HERE"; 

$payload = json_encode([
    'model'      => 'openrouter/auto',
    'messages'   => $messages,
    'max_tokens' => 300
]);

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENROUTER_KEY,
        'HTTP-Referer: http://localhost',
        'X-Title: EWallet Chatbot'
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['reply' => 'Connection Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$data  = json_decode($response, true);
$reply = $data['choices'][0]['message']['content']
         ?? 'Sorry, no response received. Please try again.';

$reply = $reply ?? 'Sorry, I could not get a response right now. Please try again.';

// ---------------- SAVE BOT REPLY ----------------
$role = "assistant";
$stmt = $mysqli->prepare(
    "INSERT INTO chat_history(user_id, role, message) VALUES (?, ?, ?)"
);
$stmt->bind_param("iss", $user_id, $role, $reply);
$stmt->execute();
$stmt->close();

// ---------------- FINAL OUTPUT ----------------
echo json_encode(['reply' => $reply]);
exit;
?>