<?php
/**
 * ajax/get_user_detail.php
 * Secure endpoint — admin only — returns full user detail as JSON
 * Place this file at:  views/ajax/get_user_detail.php
 */
session_start();
header('Content-Type: application/json');

// ── 1. Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// ── 2. Validate input ─────────────────────────────────────────────────────────
$uid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
if ($uid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user id']);
    exit();
}

require_once '../../configs/connect.php';

// ── 3. Core user + profile ────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT
        u.id, u.name, u.first_name, u.last_name,
        u.email, u.provider, u.avatar,
        u.is_admin, u.can_post, u.request_post_permission,
        u.created_at, u.updated_at,
        up.bio, up.user_image, up.background_image,
        up.phone1, up.phone2,
        g.name AS gender
    FROM user u
    LEFT JOIN user_profile up ON up.user_id = u.id
    LEFT JOIN gender       g  ON g.id = up.gender_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit();
}

// ── 4. Active sessions ────────────────────────────────────────────────────────
$stmtSess = $conn->prepare("
    SELECT id, created_at, expires_at,
           CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END AS is_active
    FROM user_sessions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmtSess->execute([$uid]);
$sessions = $stmtSess->fetchAll(PDO::FETCH_ASSOC);

// ── 5. Products posted by this user ──────────────────────────────────────────
$stmtProd = $conn->prepare("
    SELECT
        p.id, p.name, p.location, p.prices, p.discounts,
        p.showed, p.description, p.created_at,
        pi.main_image,
        c.name AS category,
        (SELECT COUNT(*) FROM product_likes pl WHERE pl.product_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM product_comments pc WHERE pc.product_id = p.id) AS comment_count
    FROM product p
    LEFT JOIN product_image pi ON pi.id = p.product_image_id
    LEFT JOIN category c       ON c.id  = p.category_id
    WHERE p.owner_id = ?
    ORDER BY p.created_at DESC
");
$stmtProd->execute([$uid]);
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// ── 6. Stats ──────────────────────────────────────────────────────────────────
$stmtStats = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM product WHERE owner_id = ?)  AS total_products,
        (SELECT COUNT(*) FROM product_likes pl
            JOIN product p ON p.id = pl.product_id
            WHERE p.owner_id = ?)                          AS total_likes_received,
        (SELECT COUNT(*) FROM product_comments pc
            JOIN product p ON p.id = pc.product_id
            WHERE p.owner_id = ?)                          AS total_comments_received
");
$stmtStats->execute([$uid, $uid, $uid]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// ── 7. Return JSON ────────────────────────────────────────────────────────────
echo json_encode([
    'user'     => $user,
    'sessions' => $sessions,
    'products' => $products,
    'stats'    => $stats,
], JSON_UNESCAPED_UNICODE);