<?php
// backend/notifications_helper.php
// Include this wherever you need to create a notification.
// Requires $mysqli to be available in the calling scope.

/**
 * Insert a notification row.
 *
 * @param mysqli $mysqli   Active DB connection
 * @param int    $user_id  Recipient user ID
 * @param string $type     One of the ENUM values in the notifications table
 * @param string $title    Short title (≤120 chars)
 * @param string $body     Descriptive message (≤255 chars)
 * @param float|null $amount  Optional monetary amount
 */
function create_notification(
    mysqli $mysqli,
    int $user_id,
    string $type,
    string $title,
    string $body,
    ?float $amount = null
): void {
    $stmt = $mysqli->prepare(
        'INSERT INTO notifications (user_id, type, title, body, amount)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssd', $user_id, $type, $title, $body, $amount);
    $stmt->execute();
    $stmt->close();
}
