<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/CommentRepository.php';

function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function send_json($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    if (is_ajax_request()) {
        send_json(['success' => false, 'error' => 'Please log in to comment.']);
    }
    header("Location: ../views/login.php");
    exit();
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

$commentRepo = new CommentRepository($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!csrf_verify()) {
        if (is_ajax_request()) {
            send_json(['success' => false, 'error' => 'Invalid request. Please try again.'], 403);
        }
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }

    $productId = (int)$_POST['product_id'];
    $comment   = trim($_POST['comment']);

    if (empty($comment)) {
        if (is_ajax_request()) {
            send_json(['success' => false, 'error' => 'Comment cannot be empty.']);
        }
        header("Location: ../views/product_detail.php?id=" . $productId);
        exit();
    }

    $commentId = $commentRepo->add($productId, $_SESSION['user_id'], $comment);

    $profileStmt = $conn->prepare("SELECT user_image FROM user_profile WHERE user_id = :uid LIMIT 1");
    $profileStmt->execute([':uid' => $_SESSION['user_id']]);
    $profileRow = $profileStmt->fetch(PDO::FETCH_ASSOC);
    $userImage = $profileRow['user_image'] ?? null;

    if (is_ajax_request()) {
        send_json([
            'success' => true,
            'id' => (int)$commentId,
            'comment' => $comment,
            'user_name' => $_SESSION['user_name'] ?? 'You',
            'user_image' => $userImage
        ]);
    }

    header("Location: ../views/product_detail.php?id=" . $productId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    if (!csrf_verify()) {
        if (is_ajax_request()) {
            send_json(['success' => false, 'error' => 'Invalid request. Please try again.'], 403);
        }
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }

    $commentId = (int)$_POST['comment_id'];
    $productId = (int)$_POST['product_id'];

    $deleted = $commentRepo->delete($commentId, $_SESSION['user_id']);

    if (is_ajax_request()) {
        send_json(['success' => $deleted]);
    }

    header("Location: ../views/product_detail.php?id=" . $productId);
    exit();
}

if (is_ajax_request()) {
    send_json(['success' => false, 'error' => 'Invalid request.'], 400);
}

header("Location: ../views/home.php");
