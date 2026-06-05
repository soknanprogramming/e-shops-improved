<?php
// ── controllers/auth.php ─────────────────────────────────────────────────────
// Handles login, register, and logout POST/GET.
// FIXES:
//   • secure flag set to false so remember cookie works on localhost (HTTP)
//   • Login page now validates DB token before trusting remember cookie
//   • Errors logged server-side instead of exposed to user
// ─────────────────────────────────────────────────────────────────────────────
session_start();

require_once '../repos/UserRepository.php';
require_once '../configs/connect.php';

$userRepo = new UserRepository($conn);

define('REMEMBER_COOKIE', 'remember_user');
define('REMEMBER_DAYS',   10);

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Issue a new remember-me token, store it in user_sessions, set the cookie.
 *
 * Required table — already included in updated SQL file (see new-table.sql):
 *   CREATE TABLE IF NOT EXISTS user_sessions (
 *       id         INT AUTO_INCREMENT PRIMARY KEY,
 *       user_id    INT         NOT NULL,
 *       token      VARCHAR(64) NOT NULL UNIQUE,
 *       expires_at DATETIME    NOT NULL,
 *       created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
 *       INDEX idx_token   (token),
 *       INDEX idx_user_id (user_id)
 *   );
 */
function issueRememberCookie(PDO $conn, int $userId): void {
    $token = bin2hex(random_bytes(32)); // 64-char secure token

    $stmt = $conn->prepare("
        INSERT INTO user_sessions (user_id, token, expires_at)
        VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL :days DAY))
    ");
    $stmt->execute([':uid' => $userId, ':token' => $token, ':days' => REMEMBER_DAYS]);

    setcookie(REMEMBER_COOKIE, $token, [
        'expires'  => time() + (REMEMBER_DAYS * 24 * 60 * 60),
        'path'     => '/',
        'secure'   => false,   // ← false = works on both HTTP (localhost) and HTTPS (production)
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Delete the remember-me token from DB and expire the cookie.
 */
function revokeRememberCookie(PDO $conn): void {
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        try {
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE token = :token");
            $stmt->execute([':token' => $_COOKIE[REMEMBER_COOKIE]]);
        } catch (PDOException $e) {
            error_log("revokeRememberCookie DB error: " . $e->getMessage());
        }
    }
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// LOGOUT  (GET ?logout or POST logout)
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['logout']) || isset($_POST['logout'])) {
    revokeRememberCookie($conn);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header("Location: ../views/login.php");
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// POST ACTIONS
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── REGISTER ─────────────────────────────────────────────────────────────
    if (isset($_POST['register'])) {

        // CSRF check
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            header("Location: ../views/register.php?error=" . urlencode('Invalid security token. Please try again.'));
            exit();
        }

        $name             = trim($_POST['name']             ?? '');
        $first_name       = trim($_POST['first_name']       ?? '');
        $last_name        = trim($_POST['last_name']        ?? '');
        $email            = trim($_POST['email']            ?? '');
        $password         =      $_POST['password']         ?? '';
        $confirm_password =      $_POST['confirm_password'] ?? '';

        // Basic validation
        if (empty($name) || empty($first_name) || empty($last_name) || empty($email)) {
            $_SESSION['register_input'] = compact('name', 'first_name', 'last_name', 'email');
            header("Location: ../views/register.php?error=" . urlencode('All fields are required.'));
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_input'] = compact('name', 'first_name', 'last_name', 'email');
            header("Location: ../views/register.php?error=" . urlencode('Please enter a valid email address.'));
            exit();
        }

        if (strlen($password) < 6) {
            $_SESSION['register_input'] = compact('name', 'first_name', 'last_name', 'email');
            header("Location: ../views/register.php?error=" . urlencode('Password must be at least 6 characters.'));
            exit();
        }

        if ($password !== $confirm_password) {
            $_SESSION['register_input'] = compact('name', 'first_name', 'last_name', 'email');
            header("Location: ../views/register.php?error=" . urlencode('Passwords do not match.'));
            exit();
        }

        if ($userRepo->findByName($name)) {
            $_SESSION['register_input'] = compact('name', 'first_name', 'last_name', 'email');
            header("Location: ../views/register.php?error=" . urlencode('Username already taken.'));
            exit();
        }

        if ($userRepo->findByEmail($email)) {
            $_SESSION['register_input'] = compact('name', 'first_name', 'last_name', 'email');
            header("Location: ../views/register.php?error=" . urlencode('Email already registered.'));
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userRepo->create([
                'name'       => $name,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'email'      => $email,
                'password'   => $hashed_password,
                'is_admin'   => 0,
                'avatar'     => 0,
                'can_post'   => 1,  // ← Grant posting permission immediately on register
            ]);
        } catch (PDOException $e) {
            error_log("Register DB error: " . $e->getMessage());
            header("Location: ../views/register.php?error=" . urlencode('A database error occurred. Please try again.'));
            exit();
        }

        // Regenerate CSRF after successful register
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: ../views/login.php?success=" . urlencode('Registration successful. Please log in.'));
        exit();
    }

    // ── LOGIN ─────────────────────────────────────────────────────────────────
    if (isset($_POST['login'])) {

        // CSRF check
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            header("Location: ../views/login.php?error=" . urlencode('Invalid security token. Please try again.'));
            exit();
        }

        $email    = trim($_POST['email']    ?? '');
        $password =      $_POST['password'] ?? '';

        $user = $userRepo->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin']  = $user['is_admin'];
            $_SESSION['can_post']  = $user['can_post'] ?? 0;

            // Regenerate CSRF after login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if (!empty($_POST['remember'])) {
                issueRememberCookie($conn, (int) $user['id']);
            }

            header("Location: ../views/home.php");
            exit();

        } else {
            $_SESSION['login_email'] = $email;
            session_write_close();
            header("Location: ../views/login.php?error=" . urlencode('Invalid email or password.'));
            exit();
        }
    }
}