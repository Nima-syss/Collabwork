<?php
// TEMPORARY FILE — DELETE AFTER USE
// Visit this page once to set the admin password, then delete this file.

require_once '../backend/connection.php';

$new_password = 'admin123'; // Change this before visiting!
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare('UPDATE admins SET password_hash=? WHERE email=?');
$email = 'admin@ewallet.com';
$stmt->bind_param('ss', $hash, $email);
$stmt->execute();
$stmt->close();

echo '<h2>Done! Admin password set to: ' . htmlspecialchars($new_password) . '</h2>';
echo '<p><strong>Delete this file immediately!</strong></p>';
echo '<p><a href="pages/admin_login.php">Go to Admin Login</a></p>';
