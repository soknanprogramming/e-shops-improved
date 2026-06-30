<?php
// Database connection. Real DB errors are logged server-side only.
$host     = getenv('DB_HOST') ?: 'mysql';
$port     = getenv('DB_PORT') ?: '3306';
$user     = getenv('DB_USER') ?: 'app_user';
$password = getenv('DB_PASSWORD') ?: 'secret';
$database = getenv('DB_NAME') ?: 'khmer245_db';

try {
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(503);
    die("We're having trouble connecting to the database. Please try again in a moment.");
}
