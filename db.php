<?php
// db.php
// Database connection using PDO
// Credentials from your message:
$DB_HOST = '127.0.0.1';
$DB_NAME = 'db4oa3blzv8gnc';
$DB_USER = 'ueyhm8rqreljw';
$DB_PASS = 'gutn2hie5vxa';
$DB_CHAR = 'utf8mb4';

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHAR}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In production do not echo errors. For dev, show message.
    die("Database connection failed: " . $e->getMessage());
}
