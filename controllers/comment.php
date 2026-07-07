<?php
session_start();
require_once '../configs/connect.php';
require_once '../configs/middleware.php';
checkRememberCookie($conn);
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
    $postToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    
    // Log for debugging
    if (empty($sessionToken)) {
        error_log("CSRF Verify: Session token is empty!");
        return false;
    }
    if (empty($postToken)) {
        error_log("CSRF Verify: POST token is empty!");
        return false;
    }
    
    if (!hash_equals($sessionToken, $postToken)) {
        error_log("CSRF Verify: Tokens don't match. Session: " . substr($sessionToken, 0, 10) . "... POST: " . substr($postToken, 0, 10) . "...");
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

    $productId = (int)($_POST['product_id'] ?? 0);
    $comment   = trim($_POST['comment'] ?? '');
    $rating    = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $rating    = ($rating >= 1 && $rating <= 5) ? $rating : null;

    if ($rating === null && $comment === '') {
        if (is_ajax_request()) {
            send_json(['success' => false, 'error' => 'Please enter a comment or select a rating.']);
        }
        header("Location: ../views/product_detail.php?id=" . $productId);
        exit();
    }

    $commentId = $commentRepo->add($productId, $_SESSION['user_id'], $comment, $rating);

    $profileStmt = $conn->prepare("SELECT user_image FROM user_profile WHERE user_id = :uid LIMIT 1");
    $profileStmt->execute([':uid' => $_SESSION['user_id']]);
    $profileRow = $profileStmt->fetch(PDO::FETCH_ASSOC);
    $userImage = $profileRow['user_image'] ?? null;

    $ratingStats = ['rating_count' => 0, 'avg_rating' => 0, 'rating_breakdown' => [5=>0,4=>0,3=>0,2=>0,1=>0]];
    try {
        $statsStmt = $conn->prepare(
            "SELECT
                COUNT(rating) AS rating_count,
                AVG(rating) AS avg_rating,
                SUM(rating = 5) AS r5,
                SUM(rating = 4) AS r4,
                SUM(rating = 3) AS r3,
                SUM(rating = 2) AS r2,
                SUM(rating = 1) AS r1
              FROM product_comments
              WHERE product_id = ? AND rating IS NOT NULL"
        );
        $statsStmt->execute([$productId]);
        $row = $statsStmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $ratingStats = [
                'rating_count' => (int)$row['rating_count'],
                'avg_rating' => $row['avg_rating'] !== null ? (float)$row['avg_rating'] : 0,
                'rating_breakdown' => [
                    5 => (int)$row['r5'],
                    4 => (int)$row['r4'],
                    3 => (int)$row['r3'],
                    2 => (int)$row['r2'],
                    1 => (int)$row['r1'],
                ],
            ];
        }
    } catch (Throwable $e) {
        // If the database schema doesn't have the rating column yet, ignore.
    }

    if (is_ajax_request()) {
        send_json([
            'success' => true,
            'id' => (int)$commentId,
            'comment' => $comment,
            'user_name' => $_SESSION['user_name'] ?? 'You',
            'user_image' => $userImage,
            'rating' => $rating,
            'rating_stats' => $ratingStats
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
