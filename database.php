<?php
// Default settings for a fresh XAMPP install on your own computer.
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'vuka_db';

// On a live host, put the real database details in config.php (copy
// config.example.php). config.php is listed in .gitignore, so the password
// never ends up on GitHub.
if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
}

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
