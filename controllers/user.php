<?php
session_start();
require_once '../repos/UserRepository.php';
require_once '../configs/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

$userRepo = new UserRepository($conn);

// Toggle Role (admin only)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_role' && isset($_GET['id'])) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header("Location: ../views/home.php"); exit();
    }
    $id = (int)$_GET['id'];
    if ($id == $_SESSION['user_id']) {
        header("Location: ../views/admin_user.php?error=" . urlencode('You cannot change your own role')); exit();
    }
    $user = $userRepo->findById($id);
    if ($user) {
        $new_role = $user['is_admin'] == 1 ? 0 : 1;
        $userRepo->update($id, ['is_admin' => $new_role]);
        $msg = $new_role ? 'User promoted to Admin' : 'User demoted to User';
        $redirectParams = [];
        foreach (['search', 'filter', 'order'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $redirectParams[$key] = $_GET[$key];
            }
        }
        $redirectParams['success'] = $msg;
        header("Location: ../views/admin_user.php?" . http_build_query($redirectParams));
    } else {
        header("Location: ../views/admin_user.php?error=" . urlencode('User not found'));
    }
    exit();
}

// Toggle Post Permission (admin only)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_permission' && isset($_GET['id'])) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header("Location: ../views/home.php"); exit();
    }
    $id   = (int)$_GET['id'];
    $user = $userRepo->findById($id);
    if ($user) {
        $new_status = (isset($user['can_post']) && $user['can_post'] == 1) ? 0 : 1;
        $updateData = ['can_post' => $new_status];
        if ($new_status == 1) $updateData['request_post_permission'] = 0;
        $userRepo->update($id, $updateData);
        $msg = $new_status ? 'User allowed to post products' : 'User posting permission revoked';
        $redirectParams = [];
        foreach (['search', 'filter', 'order'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $redirectParams[$key] = $_GET[$key];
            }
        }
        $redirectParams['success'] = $msg;
        header("Location: ../views/admin_user.php?" . http_build_query($redirectParams));
    } else {
        header("Location: ../views/admin_user.php?error=" . urlencode('User not found'));
    }
    exit();
}

// Request Permission (user side)
if (isset($_GET['action']) && $_GET['action'] === 'request_permission') {
    $success = $userRepo->update($_SESSION['user_id'], ['request_post_permission' => 1]);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => 'Permission requested successfully.']);
        exit();
    }
    header("Location: ../views/user_dashboard.php?success=" . urlencode('Permission requested. Please wait for admin approval.'));
    exit();
}
