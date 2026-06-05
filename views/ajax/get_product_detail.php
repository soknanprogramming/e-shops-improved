<?php
/**
 * ajax/get_product_detail.php
 * Secure endpoint — admin only — returns full product detail as JSON
 * Place this file at:  views/ajax/get_product_detail.php
 */

// ── CRITICAL: suppress all PHP errors/warnings from polluting JSON output ────
ini_set('display_errors', 0);
error_reporting(0);
ob_start(); // buffer any accidental output

session_start();
header('Content-Type: application/json');

// helper: discard any buffered HTML and send clean error JSON
function jsonError(int $code, string $msg): void {
    ob_end_clean();
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit();
}

// ── 1. Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    jsonError(403, 'Forbidden');
}

// ── 2. Validate input ─────────────────────────────────────────────────────────
$pid = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;
if ($pid <= 0) {
    jsonError(400, 'Invalid product id');
}

try {
    require_once '../../configs/connect.php';
} catch (Throwable $e) {
    jsonError(500, 'Database connection failed');
}

// ── 3. Core product + images + owner ─────────────────────────────────────────
try {
    $stmt = $conn->prepare("
        SELECT
            p.id, p.name, p.location, p.prices, p.discounts,
            p.showed, p.description, p.created_at, p.updated_at,
            p.owner_id,
            pi.main_image, pi.image1, pi.image2, pi.image3, pi.image4, pi.image5,
            c.name        AS category_name,
            u.name        AS owner_name,
            u.email       AS owner_email,
            up.user_image AS owner_avatar,
            up.phone1     AS owner_phone1
        FROM product p
        LEFT JOIN product_image pi ON pi.id      = p.product_image_id
        LEFT JOIN category      c  ON c.id       = p.category_id
        LEFT JOIN user          u  ON u.id        = p.owner_id
        LEFT JOIN user_profile  up ON up.user_id  = p.owner_id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$pid]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    jsonError(500, 'Query failed: ' . $e->getMessage());
}

if (!$product) {
    jsonError(404, 'Product not found');
}

// ── 4. Stats ──────────────────────────────────────────────────────────────────
try {
    $stmtStats = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM product_likes    WHERE product_id = ?) AS like_count,
            (SELECT COUNT(*) FROM product_comments WHERE product_id = ?) AS comment_count
    ");
    $stmtStats->execute([$pid, $pid]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // non-fatal — return zeros
    $stats = ['like_count' => 0, 'comment_count' => 0];
}

// ── 5. Recent comments (up to 8) ─────────────────────────────────────────────
try {
    $stmtComments = $conn->prepare("
        SELECT
            pc.id, pc.content, pc.created_at,
            u.name  AS user_name,
            up.user_image
        FROM product_comments pc
        LEFT JOIN user         u  ON u.id         = pc.user_id
        LEFT JOIN user_profile up ON up.user_id   = pc.user_id
        WHERE pc.product_id = ?
        ORDER BY pc.created_at DESC
        LIMIT 8
    ");
    $stmtComments->execute([$pid]);
    $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $comments = [];
}

// ── 6. Flush buffer and return JSON ──────────────────────────────────────────
ob_end_clean();
echo json_encode([
    'product'  => $product,
    'stats'    => $stats,
    'comments' => $comments,
], JSON_UNESCAPED_UNICODE);