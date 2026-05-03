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

// ---------------- GET EXPENSES ----------------
$stmt = $mysqli->prepare(
    "SELECT category, SUM(amount) AS total
     FROM expenses
     WHERE user_id = ?
     GROUP BY category"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- GET MONTHLY EXPENSES ----------------
$stmt = $mysqli->prepare(
    "SELECT
        DATE_FORMAT(expense_date, '%M %Y') AS month_name,
        category,
        SUM(amount) AS total
     FROM expenses
     WHERE user_id = ?
     GROUP BY month_name, category
     ORDER BY expense_date DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------- GET BUDGETS ----------------
$stmt = $mysqli->prepare(
    "SELECT category, monthly_limit, used
     FROM budgets
     WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
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
$context  = "You are a friendly financial assistant for EWallet, a Nepali e-wallet app.\n";
$context .= "Today's date: " . date('F j, Y') . "\n";
$context .= "User Balance: NRP " . number_format($user['balance'] ?? 0, 2) . "\n\n";

$context .= "Expenses by category (all time):\n";
foreach ($expenses as $e) {
    $context .= "- " . $e['category'] . ": NRP " . number_format($e['total'], 2) . "\n";
}

$context .= "\nMonthly expense breakdown:\n";
$current_month = '';
foreach ($monthly_expenses as $me) {
    if ($me['month_name'] !== $current_month) {
        $current_month = $me['month_name'];
        $context .= "\n" . $current_month . ":\n";
    }
    $context .= "- " . $me['category'] . ": NRP " . number_format($me['total'], 2) . "\n";
}

$context .= "\nBudget limits:\n";
foreach ($budgets as $b) {
    $remaining = $b['monthly_limit'] - $b['used'];
    $context .= "- " . $b['category']
             . ": Limit NRP " . number_format($b['monthly_limit'], 2)
             . ", Used NRP "  . number_format($b['used'], 2)
             . ", Remaining NRP " . number_format($remaining, 2) . "\n";
}

$context .= "\nRecent transactions:\n";
foreach ($transactions as $t) {
    $context .= "- " . $t['type']
             . ": NRP " . number_format($t['amount'], 2)
             . " on " . $t['created_at'] . "\n";
}

$context .= "\nInstructions: Reply in plain text only. ";
$context .= "No markdown or special symbols. ";
$context .= "Keep answers short, friendly and helpful. ";
$context .= "Use the monthly breakdown to answer month-specific questions.";

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
$OPENROUTER_KEY = "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";

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

// ---------------- FINAL FALLBACK ----------------
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