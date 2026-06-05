<?php
// ── logout.php ───────────────────────────────────────────────────────────────
// Safely destroys the session AND revokes the remember-me cookie in the DB.
// ─────────────────────────────────────────────────────────────────────────────
session_start();
require_once '../configs/connect.php';

// 1. Revoke the remember-me cookie from the database first
if (!empty($_COOKIE['remember_user'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE token = :token");
        $stmt->execute([':token' => $_COOKIE['remember_user']]);
    } catch (PDOException $e) {
        // Don't block logout if DB fails — just continue
    }
    // Expire the cookie in the browser
    setcookie('remember_user', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => false,   // false = works on both HTTP (local) and HTTPS (production)
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// 2. Destroy the PHP session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// 3. Redirect to login
header("Location: login.php");
exit();