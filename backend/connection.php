<?php
// connection.php
// Update these values if your XAMPP/MySQL settings are different.
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ewallet';

$mysqli = new mysqli($host, $user, $password, $database);
if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
?>