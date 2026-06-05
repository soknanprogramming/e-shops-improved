<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/LikeRepository.php';

if (!isset($_SESSION['user_id'])) {
    // Return JSON for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }
    header("Location: ../views/login.php");
    exit();
}

// CSRF check
function csrf_verify_post() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {

    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!csrf_verify_post()) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit();
        }
        http_response_code(403);
        die('Invalid request.');
    }

    $likeRepo  = new LikeRepository($conn);
    $productId = (int)$_POST['product_id'];
    $liked     = $likeRepo->toggle($_SESSION['user_id'], $productId);
    $count     = $likeRepo->getCount($productId);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
        exit();
    }

    // Normal form submit fallback
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: ../views/product_detail.php?id=" . $productId);
    }
    exit();
}

header("Location: ../views/home.php");
