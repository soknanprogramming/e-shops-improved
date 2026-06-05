<?php
// ── configs/connect.php ──────────────────────────────────────────────────────
// Database connection.
// FIX: Real DB error is logged server-side only, not shown to the visitor.
// ─────────────────────────────────────────────────────────────────────────────
$host     = 'localhost:3307';
$user     = 'root';
$password = 'root';
$database = 'khmer245_db';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false); // safer: real prepared statements
} catch (PDOException $e) {
    // Log the real error to the server log (never show credentials to users)
    error_log("Database connection failed: " . $e->getMessage());
    // Show a friendly message to the visitor
    http_response_code(503);
    die("We're having trouble connecting to the database. Please try again in a moment.");
}