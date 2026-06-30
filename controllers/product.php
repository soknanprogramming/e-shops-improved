<?php
// ── controllers/product.php ──────────────────────────────────────────────────
// Handles product creation (POST).
// FIX: All redirects corrected from product.php → product_create.php
// ─────────────────────────────────────────────────────────────────────────────
session_start();
require_once '../configs/connect.php';
require_once '../repos/ProductRepository.php';

// 1. Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

// 2. Must be a POST with the create button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {

    // ── CSRF check ───────────────────────────────────────────────────────────
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        header("Location: ../views/product_create.php?error=" . urlencode('Invalid security token. Please try again.'));
        exit();
    }

    // ── Validate can_post permission fresh from DB (don't trust session alone)
    $stmtPerm = $conn->prepare("SELECT can_post FROM user WHERE id = ?");
    $stmtPerm->execute([$_SESSION['user_id']]);
    $perm = $stmtPerm->fetch();
    if (!$perm || $perm['can_post'] != 1) {
        header("Location: ../views/product_create.php?error=" . urlencode('You do not have permission to post products.'));
        exit();
    }

    // ── Sanitise text inputs ─────────────────────────────────────────────────
    $name        = trim($_POST['name']        ?? '');
    $prices      = filter_var($_POST['prices']    ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $discounts   = !empty($_POST['discounts'])
                    ? filter_var($_POST['discounts'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                    : 0;
    $location    = trim($_POST['location']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    if (empty($name)) {
        header("Location: ../views/product_create.php?error=" . urlencode('Product name is required.'));
        exit();
    }
    if ($category_id <= 0) {
        header("Location: ../views/product_create.php?error=" . urlencode('Please select a category.'));
        exit();
    }

    // ── Upload folder ────────────────────────────────────────────────────────
    $target_dir = __DIR__ . "/../uploads/products/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // ── Helper: upload one image ─────────────────────────────────────────────
    function uploadProductImage(array $file, string $target_dir): string|false|null {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null; // No file uploaded (optional slots)
        }
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return false; // Wrong type
        }
        // Verify it's actually an image (not a renamed PHP file)
        $mimeAllowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $mimeAllowed, true)) {
            return false;
        }
        $new_name = 'prod_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
            return $new_name;
        }
        return false;
    }

    // ── Main image (required) ────────────────────────────────────────────────
    $main_image = uploadProductImage($_FILES['image'] ?? [], $target_dir);
    if (!$main_image) {
        header("Location: ../views/product_create.php?error=" . urlencode('Main image is required and must be JPG, PNG, GIF, or WEBP.'));
        exit();
    }

    // ── Additional images (optional) ─────────────────────────────────────────
    $additional_images = [];
    for ($i = 1; $i <= 5; $i++) {
        $key = 'image' . $i;
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            $result = uploadProductImage($_FILES[$key], $target_dir);
            $additional_images[$key] = $result ?: null;
        } else {
            $additional_images[$key] = null;
        }
    }

    // ── Save to database ─────────────────────────────────────────────────────
    try {
        $productRepo = new ProductRepository($conn);
        $productRepo->create([
            'name'        => $name,
            'prices'      => $prices,
            'discounts'   => $discounts,
            'category_id' => $category_id,
            'owner_id'    => (int)$_SESSION['user_id'],
            'location'    => $location,
            'description' => $description,
            'image'       => $main_image,
            'image1'      => $additional_images['image1'],
            'image2'      => $additional_images['image2'],
            'image3'      => $additional_images['image3'],
            'image4'      => $additional_images['image4'],
            'image5'      => $additional_images['image5'],
        ]);

        // Regenerate CSRF token after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: ../views/product_create.php?success=1");
        exit();

    } catch (PDOException $e) {
        // Log real error server-side; show safe message to user
        error_log("Product create DB error: " . $e->getMessage());
        header("Location: ../views/product_create.php?error=" . urlencode('A database error occurred. Please try again.'));
        exit();
    }

} else {
    // Not a valid POST — send back to form
    header("Location: ../views/product_create.php");
    exit();
}
