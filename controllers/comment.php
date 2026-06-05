<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/CommentRepository.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }
}

$commentRepo = new CommentRepository($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    csrf_verify();
    $productId = (int)$_POST['product_id'];
    $comment   = trim($_POST['comment']);

    if (!empty($comment)) {
        $commentRepo->add($productId, $_SESSION['user_id'], $comment);
    }

    header("Location: ../views/product_detail.php?id=" . $productId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    csrf_verify();
    $commentId = (int)$_POST['comment_id'];
    $productId = (int)$_POST['product_id'];

    $commentRepo->delete($commentId, $_SESSION['user_id']);

    header("Location: ../views/product_detail.php?id=" . $productId);
    exit();
}

header("Location: ../views/home.php");
