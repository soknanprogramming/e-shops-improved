<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once '../repos/CategoryRepository.php';
require_once '../configs/connect.php';

// 1. Check Auth
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// 2. Fetch Category Data
if (!isset($_GET['id'])) {
    header("Location: admin_category.php");
    exit();
}

$id = $_GET['id'];
$categoryRepo = new CategoryRepository($conn);
$category = $categoryRepo->findById($id);

if (!$category) {
    header("Location: admin_category.php?error=Category not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link rel="icon" href="../icon/e-commerce-logo.png" sizes="any" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3325;
            --primary-container: #2a5038;
            --primary-light: rgba(26, 51, 37, 0.05);
            --secondary: #9d7c39;
            --secondary-light: rgba(157, 124, 57, 0.1);
            --tertiary: #7e000a;
            --bg-body: #faf7f2;
            --surface: #ffffff;
            --on-surface: #201b09;
            --on-surface-variant: #6b6355;
            --outline: rgba(74, 69, 56, 0.12);
            --outline-strong: rgba(74, 69, 56, 0.25);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --font-headline: 'Manrope', sans-serif;
            --font-body: 'Public Sans', sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            margin: 0;
            padding: 0;
            background-color: var(--bg-body);
            color: var(--on-surface);
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            padding: 1.5rem 3rem;
            width: 100%;
        }

        @media (max-width: 992px) { .main-content { padding: 1.25rem 2rem; } }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .main-content { padding: 1rem; }
        }

        @media (max-width: 480px) {
            .main-content { padding: 0.75rem; }
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            color: var(--on-surface);
            text-decoration: none;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .btn-back:hover { border-color: var(--outline-strong); background: var(--bg-body); }
        .btn-back svg { width: 18px; height: 18px; }

        .page-header h1 {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.25rem; }
        }

        /* Form Card */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1.5rem 2rem;
            max-width: 520px;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 1.25rem 1.5rem;
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 1rem;
            }
        }

        .form-section-title {
            font-family: var(--font-headline);
            font-size: 1rem;
            font-weight: 700;
            color: var(--on-surface);
            margin: 0 0 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--outline);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section-title svg {
            width: 18px;
            height: 18px;
            color: var(--secondary);
            opacity: 0.8;
        }

        /* Form Fields */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--on-surface-variant);
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-body);
            border: 1.5px solid var(--outline);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-family: var(--font-body);
            color: var(--on-surface);
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        /* Image Upload Zone */
        .upload-zone {
            border: 2px dashed var(--outline-strong);
            border-radius: var(--radius-md);
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-body);
            position: relative;
        }

        .upload-zone:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .upload-zone.has-image {
            padding: 0;
            border-style: solid;
            border-color: var(--outline);
            overflow: hidden;
        }

        .upload-zone svg.placeholder-icon {
            width: 32px;
            height: 32px;
            color: var(--on-surface-variant);
            opacity: 0.4;
            margin-bottom: 0.5rem;
        }

        .upload-zone p {
            margin: 0 0 0.25rem;
            font-weight: 600;
            font-size: 0.825rem;
            color: var(--on-surface-variant);
        }

        .upload-zone .hint {
            font-size: 0.7rem;
            color: var(--on-surface-variant);
            opacity: 0.6;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-zone .preview-img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            display: none;
            border-radius: var(--radius-sm);
        }

        .upload-zone.has-image .upload-placeholder { display: none; }
        .upload-zone.has-image .preview-img { display: block; }

        /* Current Image */
        .current-image-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--bg-body);
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
        }

        .current-image-preview img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--outline);
        }

        .current-image-preview .label {
            font-size: 0.75rem;
            color: var(--on-surface-variant);
            font-weight: 600;
        }

        .current-image-preview .filename {
            font-size: 0.8rem;
            color: var(--on-surface);
            font-weight: 500;
        }

        /* Submit Button */
        .form-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-submit:hover {
            background: var(--primary-container);
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 20px;
            background: transparent;
            color: var(--on-surface-variant);
            border: 1.5px solid var(--outline-strong);
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-cancel:hover {
            border-color: var(--on-surface-variant);
            color: var(--on-surface);
        }

        @media (max-width: 768px) {
            .btn-submit,
            .btn-cancel {
                padding: 10px 20px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-submit,
            .btn-cancel {
                justify-content: center;
            }
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            color: #fff;
            font-weight: 600;
            font-size: 0.825rem;
            z-index: 9999;
            animation: toastIn 0.3s ease, toastOut 0.3s ease 2.7s forwards;
        }

        .toast-success { background: var(--primary); }
        .toast-error { background: var(--tertiary); }

        @keyframes toastIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes toastOut { from { opacity: 1; } to { opacity: 0; transform: translateY(10px); } }
    </style>
</head>
<body>
    <?php include './assets/admin_sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <a href="admin_category.php" class="btn-back">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h1>Edit Category</h1>
        </div>

        <div class="form-card">
            <form action="../controllers/category.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo $category['category_image']; ?>">

                <h3 class="form-section-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Category Details
                </h3>

                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                </div>

                <!-- Current Image -->
                <div class="current-image-preview">
                    <img src="../uploads/categories/<?php echo htmlspecialchars($category['category_image']); ?>" alt="Current">
                    <div>
                        <p class="label">Current Image</p>
                        <p class="filename"><?php echo htmlspecialchars($category['category_image']); ?></p>
                    </div>
                </div>

                <!-- Upload New Image -->
                <div class="form-group">
                    <label>Replace Image (Optional)</label>
                    <div class="upload-zone" id="imageZone">
                        <div class="upload-placeholder">
                            <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p>Click to upload a new image</p>
                            <span class="hint">PNG, JPG up to 5MB</span>
                        </div>
                        <img id="imagePreview" class="preview-img" alt="Preview">
                        <input type="file" id="imageInput" name="image" accept="image/*">
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" name="update_category" class="btn-submit">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Update Category
                    </button>
                    <a href="admin_category.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div class="toast <?php echo isset($_GET['success']) ? 'toast-success' : 'toast-error'; ?>">
            <?php echo htmlspecialchars($_GET['success'] ?? $_GET['error'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <script>
        // Image preview
        document.getElementById('imageInput').addEventListener('change', function() {
            const zone = document.getElementById('imageZone');
            const preview = document.getElementById('imagePreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    zone.classList.add('has-image');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>