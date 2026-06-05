<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Check if user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category</title>
    <link rel="icon" href="../icon/e-commerce-logo.png" sizes="any" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
   <style>
        :root {
            /* Original color tokens preserved */
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
            
            /* Professional UI Upgrades */
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --font-headline: 'Manrope', sans-serif;
            --font-body: 'Public Sans', sans-serif;
            --shadow-subtle: 0 4px 6px -1px rgba(26, 51, 37, 0.02), 0 2px 4px -1px rgba(26, 51, 37, 0.01);
            --shadow-card: 0 10px 30px -5px rgba(26, 51, 37, 0.05), 0 4px 12px -2px rgba(26, 51, 37, 0.03);
            --shadow-toast: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-body);
            color: var(--on-surface);
            display: flex;
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .main-content {
            flex-grow: 1;
            padding: 2.5rem 3.5rem;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            color: var(--on-surface-variant);
            text-decoration: none;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-subtle);
        }
        
        .btn-back:hover { 
            border-color: var(--outline-strong); 
            color: var(--primary);
            background: var(--surface);
            transform: translateX(-2px);
        }
        
        .btn-back:active { transform: translateX(0); }
        .btn-back svg { width: 20px; height: 20px; }

        .page-header h1 {
            font-family: var(--font-headline);
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        /* Form Card */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 2.5rem;
            max-width: 600px;
            box-shadow: var(--shadow-card);
        }

        .form-section-title {
            font-family: var(--font-headline);
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--outline);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title svg {
            width: 22px;
            height: 22px;
            color: var(--secondary);
        }

        /* Form Fields */
        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--on-surface-variant);
            margin-bottom: 0.625rem;
        }

        .form-group label .required {
            color: var(--tertiary);
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-body);
            border: 1.5px solid var(--outline);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-family: var(--font-body);
            color: var(--on-surface);
            outline: none;
            transition: var(--transition-smooth);
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-control::placeholder {
            color: rgba(107, 99, 85, 0.45);
        }

        /* Image Upload Zone */
        .upload-zone {
            border: 2px dashed var(--outline-strong);
            border-radius: var(--radius-md);
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition-smooth);
            background: var(--bg-body);
            position: relative;
        }

        .upload-zone:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        
        .upload-zone:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .upload-zone.has-image {
            padding: 0;
            border-style: solid;
            border-color: var(--outline);
            overflow: hidden;
            background: #000;
        }

        .upload-zone svg.placeholder-icon {
            width: 44px;
            height: 44px;
            color: var(--on-surface-variant);
            opacity: 0.5;
            margin-bottom: 0.75rem;
            transition: var(--transition-smooth);
        }
        
        .upload-zone:hover svg.placeholder-icon {
            color: var(--primary);
            opacity: 1;
            transform: translateY(-2px);
        }

        .upload-zone p {
            margin: 0 0 0.35rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--on-surface);
        }

        .upload-zone .hint {
            font-size: 0.75rem;
            color: var(--on-surface-variant);
            opacity: 0.7;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .upload-zone .preview-img {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
            display: none;
            border-radius: calc(var(--radius-md) - 2px);
            transition: var(--transition-smooth);
        }
        
        .upload-zone.has-image:hover .preview-img {
            opacity: 0.9;
        }

        .upload-zone.has-image .upload-placeholder { display: none; }
        .upload-zone.has-image .preview-img { display: block; }

        /* Actions Customization */
        .form-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-top: 2.25rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--outline);
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 32px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 4px 12px rgba(26, 51, 37, 0.15);
        }

        .btn-submit:hover {
            background: var(--primary-container);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(26, 51, 37, 0.2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            background: transparent;
            color: var(--on-surface-variant);
            border: 1.5px solid var(--outline-strong);
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .btn-cancel:hover {
            border-color: var(--on-surface);
            color: var(--on-surface);
            background: rgba(74, 69, 56, 0.02);
        }

        /* Toast Layout Upgrades */
        .toast {
            position: fixed;
            bottom: 32px;
            right: 32px;
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 9999;
            box-shadow: var(--shadow-toast);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: toastIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275), toastOut 0.3s ease 3.2s forwards;
        }

        .toast-success { background: var(--primary); border-left: 4px solid var(--secondary); }
        .toast-error { background: var(--tertiary); }

        @keyframes toastIn { 
            from { opacity: 0; transform: translateY(20px) scale(0.95); } 
            to { opacity: 1; transform: translateY(0) scale(1); } 
        }
        @keyframes toastOut { 
            from { opacity: 1; transform: translateY(0); } 
            to { opacity: 0; transform: translateY(10px); } 
        }

        /* Dynamic Responsive Layout Grid */
        @media (max-width: 992px) { 
            .main-content { padding: 2rem; } 
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .main-content { padding: 1.5rem; }
            .form-card { padding: 2rem; max-width: 100%; }
            .page-header h1 { font-size: 1.5rem; }
        }

        @media (max-width: 480px) {
            .main-content { padding: 1rem; }
            .page-header { margin-bottom: 1.5rem; }
            .form-card { padding: 1.5rem 1.25rem; }
            .form-actions { flex-direction: column; align-items: stretch; gap: 0.75rem; }
            .btn-submit, .btn-cancel { width: 100%; padding: 12px; }
        }
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
            <h1>Add New Category</h1>
        </div>

        <div class="form-card">
            <form action="../controllers/category.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <h3 class="form-section-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Category Details
                </h3>

                <div class="form-group">
                    <label for="name">Category Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Electronics, Fashion, Furniture" required>
                </div>

                <div class="form-group">
                    <label>Category Image <span class="required">*</span></label>
                    <div class="upload-zone" id="imageZone">
                        <div class="upload-placeholder">
                            <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p>Click to upload category image</p>
                            <span class="hint">PNG, JPG up to 5MB</span>
                        </div>
                        <img id="imagePreview" class="preview-img" alt="Preview">
                        <input type="file" id="imageInput" name="image" accept="image/*" required>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" name="add_category" class="btn-submit">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Category
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