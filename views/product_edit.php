<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once '../configs/connect.php';
require_once '../repos/CategoryRepository.php';
require_once '../repos/ProductRepository.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit();
}

$productId = $_GET['id'];
$productRepo = new ProductRepository($conn);
$product = $productRepo->getById($productId);

// Check if product exists and belongs to user
if (!$product || $product['owner_id'] != $_SESSION['user_id']) {
    header("Location: user_dashboard.php?error=Unauthorized access");
    exit();
}

// Fetch categories for dropdown
$catRepo = new CategoryRepository($conn);
$categories = $catRepo->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
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
            --surface-low: #fef9f3;
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
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            padding: 1.5rem 3rem;
            max-width: calc(100vw - 240px);
        }

        @media (max-width: 992px) { .main-content { padding: 1.25rem 2rem; } }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar {
                width: 100% !important;
                height: auto !important;
                position: relative !important;
                border-right: none !important;
                border-bottom: 1px solid var(--outline) !important;
            }
            .sidebar-menu { display: flex; overflow-x: auto; padding: 0.5rem !important; gap: 4px; }
            .sidebar-link { white-space: nowrap; flex-shrink: 0; padding: 8px 12px !important; font-size: 0.75rem !important; }
            .sidebar-link span { display: none; }
            .sidebar-brand { display: none; }
            .sidebar-footer { display: none; }
            .main-content { max-width: 100%; padding: 1rem; }
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
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

        .page-header p {
            font-size: 0.825rem;
            color: var(--on-surface-variant);
            margin: 0;
        }

        /* Form Container */
        .form-wrapper {
            max-width: 100%;
        }

        .form-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            align-items: start;
        }

        @media (max-width: 992px) { .form-layout { grid-template-columns: 1fr; } }

        /* Section */
        .form-section {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 0;
        }

        .section-title {
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

        .section-title svg {
            width: 18px;
            height: 18px;
            color: var(--secondary);
            opacity: 0.8;
        }

        /* Form Fields */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group:last-child { margin-bottom: 0; }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--on-surface-variant);
            margin-bottom: 0.5rem;
        }

        .form-group label .required {
            color: var(--tertiary);
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

        .form-control::placeholder {
            color: rgba(107, 99, 85, 0.5);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        select.form-control {
            cursor: pointer;
        }

        /* Image Upload */
        .upload-main {
            margin-bottom: 1.25rem;
        }

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

        .upload-zone svg {
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
            max-height: 240px;
            object-fit: cover;
            display: none;
            border-radius: var(--radius-sm);
        }

        .upload-zone.has-image .upload-placeholder { display: none; }
        .upload-zone.has-image .preview-img { display: block; }

        /* Additional Images Grid */
        .additional-images-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        @media (max-width: 480px) { .additional-images-grid { grid-template-columns: repeat(2, 1fr); } }

        .upload-thumb {
            aspect-ratio: 1;
            border: 2px dashed var(--outline);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-body);
            position: relative;
            overflow: hidden;
        }

        .upload-thumb:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .upload-thumb.has-image {
            border-style: solid;
            border-color: var(--outline);
        }

        .upload-thumb svg {
            width: 20px;
            height: 20px;
            color: var(--on-surface-variant);
            opacity: 0.3;
        }

        .upload-thumb input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-thumb .preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .upload-thumb.has-image .upload-placeholder { display: none; }
        .upload-thumb.has-image .preview-img { display: block; }

        .upload-thumb .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--tertiary);
            color: #fff;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
            z-index: 2;
        }

        .upload-thumb.has-image .remove-btn { display: flex; }

        /* Submit Button */
        .form-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-top: 1.25rem;
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
        }

        .btn-cancel:hover {
            border-color: var(--on-surface-variant);
            color: var(--on-surface);
        }

        /* Existing image thumbnail */
        .existing-thumb {
            display: inline-block;
            margin-top: 8px;
        }
        .existing-thumb img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--outline);
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
    <?php include './assets/user_sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <a href="user_dashboard.php" class="btn-back">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h1>Edit Product</h1>
                    <p>Update details for "<?php echo htmlspecialchars($product['name']); ?>"</p>
                </div>
            </div>
        </div>

        <div class="form-wrapper">
            <form action="../controllers/product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div class="form-layout">
                    <!-- Left: Basic Info -->
                    <div>
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Product Details
                            </h3>
                            <div class="form-group">
                                <label for="name">Product Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="prices">Price ($) <span class="required">*</span></label>
                                <input type="number" id="prices" name="prices" class="form-control" step="0.01" value="<?php echo htmlspecialchars($product['prices']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="discounts">Discount Amount ($)</label>
                                <input type="number" id="discounts" name="discounts" class="form-control" step="0.01" value="<?php echo htmlspecialchars($product['discounts']); ?>" placeholder="0.00 if none">
                            </div>
                            <div class="form-group">
                                <label for="category_id">Category <span class="required">*</span></label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="location">Location <span class="required">*</span></label>
                                <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($product['location']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description <span class="required">*</span></label>
                                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-actions">
                            <button type="submit" name="update_product" class="btn-submit">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Update Product
                            </button>
                            <a href="user_dashboard.php" class="btn-cancel">Cancel</a>
                        </div>
                    </div>

                    <!-- Right: Images -->
                    <div>
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Product Images
                            </h3>

                            <!-- Main Image -->
                            <div class="upload-main">
                                <label>Main Image (replace if needed)</label>
                                <div class="upload-zone <?php echo $product['main_image'] ? 'has-image' : ''; ?>" id="mainZone">
                                    <div class="upload-placeholder">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <p>Click to replace main image</p>
                                        <span class="hint">Leave unchanged to keep current</span>
                                    </div>
                                    <?php if ($product['main_image']): ?>
                                        <img src="../uploads/products/<?php echo htmlspecialchars($product['main_image']); ?>" class="preview-img" alt="Current" id="mainPreview">
                                    <?php endif; ?>
                                    <input type="file" id="mainImage" name="image" accept="image/*">
                                </div>
                            </div>

                            <!-- Additional Images -->
                            <label>Additional Images (replace if needed)</label>
                            <div class="additional-images-grid">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="upload-thumb <?php echo !empty($product['image'.$i]) ? 'has-image' : ''; ?>" id="thumbZone<?php echo $i; ?>">
                                    <div class="upload-placeholder">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                    <?php if (!empty($product['image'.$i])): ?>
                                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image'.$i]); ?>" class="preview-img" alt="Current" id="thumbPreview<?php echo $i; ?>">
                                    <?php else: ?>
                                        <img class="preview-img" alt="Preview" id="thumbPreview<?php echo $i; ?>">
                                    <?php endif; ?>
                                    <button type="button" class="remove-btn" onclick="removeImage(<?php echo $i; ?>)" title="Remove">&times;</button>
                                    <input type="file" id="thumbInput<?php echo $i; ?>" name="image<?php echo $i; ?>" accept="image/*">
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
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
        // Main image preview
        document.getElementById('mainImage').addEventListener('change', function() {
            const zone = document.getElementById('mainZone');
            const preview = document.getElementById('mainPreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    zone.classList.add('has-image');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Additional image previews
        for (let i = 1; i <= 5; i++) {
            const input = document.getElementById('thumbInput' + i);
            const zone = document.getElementById('thumbZone' + i);
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('thumbPreview' + i).src = e.target.result;
                        zone.classList.add('has-image');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Remove image
        function removeImage(index) {
            const zone = document.getElementById('thumbZone' + index);
            const input = document.getElementById('thumbInput' + index);
            zone.classList.remove('has-image');
            document.getElementById('thumbPreview' + index).src = '';
            input.value = '';
        }
    </script>
</body>
</html>