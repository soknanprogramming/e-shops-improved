<?php
// ── configs/middleware.php ───────────────────────────────────────────────────
// Include this file at the TOP of every protected page (home, dashboard, etc.)
// It handles:
//   1. Session validation
//   2. Remember-me cookie validation (DB check, not blind trust)
//   3. Cookie rolling (extends 10 days on each visit)
//
// Usage:
//   session_start();
//   require_once '../configs/connect.php';
//   require_once '../configs/middleware.php';
//   requireLogin();          // redirects to login.php if not authenticated
//   requireAdmin();          // redirects to home.php if not admin
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Validates the remember-me cookie against the database.
 * If valid: restores the session.
 * If invalid/expired: clears the cookie and redirects to login.
 */
function checkRememberCookie(PDO $conn): void {
    if (isset($_SESSION['user_id'])) {
        return; // Already logged in via session — nothing to do
    }

    if (empty($_COOKIE['remember_user'])) {
        return; // No cookie present
    }

    $token = $_COOKIE['remember_user'];

    // Validate token in DB (also checks expiry)
    $stmt = $conn->prepare("
        SELECT us.user_id, u.name, u.is_admin, u.can_post
        FROM user_sessions us
        JOIN user u ON u.id = us.user_id
        WHERE us.token = :token AND us.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Valid token — restore session
        session_regenerate_id(true);
        $_SESSION['user_id']   = $row['user_id'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['is_admin']  = $row['is_admin'];
        $_SESSION['can_post']  = $row['can_post'];

        // Roll the cookie (extend 10 more days from now)
        $conn->prepare("
            UPDATE user_sessions
            SET expires_at = DATE_ADD(NOW(), INTERVAL 10 DAY)
            WHERE token = :token
        ")->execute([':token' => $token]);

        setcookie('remember_user', $token, [
            'expires'  => time() + (10 * 24 * 60 * 60),
            'path'     => '/',
            'secure'   => false,   // false = works on localhost HTTP + production HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        // Invalid or expired token — clear it
        setcookie('remember_user', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        // Don't redirect here — let requireLogin() decide
    }
}

/**
 * Ensures the user is logged in.
 * Call after checkRememberCookie().
 */
function requireLogin(string $redirectTo = 'login.php'): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Ensures the user is an admin.
 * Call after requireLogin().
 */
function requireAdmin(string $redirectTo = 'home.php'): void {
    if (empty($_SESSION['is_admin'])) {
        header("Location: $redirectTo");
        exit();
    }
}

// Auto-run the cookie check on every page that includes this file
// ($conn must already be set by connect.php before including this file)
if (isset($conn)) {
    checkRememberCookie($conn);
}
