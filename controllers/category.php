<?php
// controllers/category.php
session_start();
require_once '../repos/CategoryRepository.php';
require_once '../configs/connect.php';

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }
}

function upload_category_image($file, $target_dir) {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($mimeType, $allowedMimes)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid('cat_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) return $new_filename;
    return null;
}

$categoryRepo = new CategoryRepository($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    csrf_verify();
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) { header("Location: ../views/login.php"); exit(); }

    $name  = trim($_POST['name']);
    $image = $_FILES['image'];

    if (empty($name) || empty($image['name'])) {
        header("Location: ../views/admin_category_add.php?error=" . urlencode('Please fill all fields'));
        exit();
    }

    $target_dir = "../uploads/categories/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $new_filename = upload_category_image($image, $target_dir);
    if (!$new_filename) {
        header("Location: ../views/admin_category_add.php?error=" . urlencode('Invalid image file. Only JPG, PNG, GIF, WEBP allowed.'));
        exit();
    }

    try {
        $categoryRepo->create(['name' => $name, 'category_image' => $new_filename]);
        header("Location: ../views/admin_category.php?success=" . urlencode('Category added successfully'));
    } catch (PDOException $e) {
        header("Location: ../views/admin_category_add.php?error=" . urlencode('Database error'));
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    csrf_verify();
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) { header("Location: ../views/login.php"); exit(); }

    $id            = (int)$_POST['id'];
    $name          = trim($_POST['name']);
    $current_image = $_POST['current_image'];

    if (empty($name)) {
        header("Location: ../views/admin_category_edit.php?id={$id}&error=" . urlencode('Name is required'));
        exit();
    }

    $final_image_name = $current_image;
    if (!empty($_FILES['image']['name'])) {
        $target_dir   = "../uploads/categories/";
        $new_filename = upload_category_image($_FILES['image'], $target_dir);
        if ($new_filename) $final_image_name = $new_filename;
    }

    try {
        $categoryRepo->update(['id' => $id, 'name' => $name, 'category_image' => $final_image_name]);
        header("Location: ../views/admin_category.php?success=" . urlencode('Category updated successfully'));
    } catch (PDOException $e) {
        header("Location: ../views/admin_category_edit.php?id={$id}&error=" . urlencode('Database error'));
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) { header("Location: ../views/login.php"); exit(); }

    $id = (int)$_GET['id'];
    try {
        if ($categoryRepo->hasProducts($id)) {
            header("Location: ../views/admin_category.php?error=" . urlencode('Cannot delete category because it has products in it.'));
            exit();
        }
        $category = $categoryRepo->findById($id);
        if ($categoryRepo->delete($id)) {
            if ($category && !empty($category['category_image'])) {
                $image_path = "../uploads/categories/" . $category['category_image'];
                if (file_exists($image_path)) unlink($image_path);
            }
            header("Location: ../views/admin_category.php?success=" . urlencode('Category deleted successfully'));
        } else {
            header("Location: ../views/admin_category.php?error=" . urlencode('Failed to delete category'));
        }
    } catch (PDOException $e) {
        $msg = $e->getCode() == '23000' ? 'Cannot delete category because it is being used.' : $e->getMessage();
        header("Location: ../views/admin_category.php?error=" . urlencode($msg));
    }
    exit();
}
