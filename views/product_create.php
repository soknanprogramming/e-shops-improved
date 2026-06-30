<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once '../configs/connect.php';
require_once '../repos/CategoryRepository.php';
require_once '../repos/ProfileRepository.php';

// 1. Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Fetch fresh user permissions (also grab request_post_permission for the UI)
$stmtUser = $conn->prepare("SELECT can_post, name, request_post_permission FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user    = $stmtUser->fetch();
$canPost = ($user && $user['can_post'] == 1);
$pendingRequest = ($user && $user['request_post_permission'] == 1);

// 3. Check phone number — redirect to profile with a message instead of hard-blocking
$profileRepo = new ProfileRepository($conn);
$userProfile = $profileRepo->getByUserId($_SESSION['user_id']);
$needsPhone  = (empty($userProfile) || empty($userProfile['phone1']));
// We don't hard-redirect here anymore — we show a warning inside the page
// so users understand what to do instead of hitting a confusing redirect.

// 4. Fetch categories for dropdown
$catRepo    = new CategoryRepository($conn);
$categories = $catRepo->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Product</title>
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
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0 0 0.25rem;
        }

        .page-header p {
            font-size: 0.875rem;
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

        .form-section + .form-section {
            margin-bottom: 1.25rem;
        }

        .section-title {
            font-family: var(--font-headline);
            font-size: 1rem;
            font-weight: 700;
            color: var(--on-surface);
            margin: 0 0 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--outline);
        }

        /* Form Fields */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 576px) { .form-row { grid-template-columns: 1fr; } }

        .form-row:last-child { margin-bottom: 0; }

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
            padding: 3rem 2rem;
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
            width: 36px;
            height: 36px;
            color: var(--on-surface-variant);
            opacity: 0.4;
            margin-bottom: 0.75rem;
        }

        .upload-zone p {
            margin: 0 0 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--on-surface-variant);
        }

        .upload-zone .hint {
            font-size: 0.75rem;
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
            max-height: 280px;
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
            width: 24px;
            height: 24px;
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
            width: 22px;
            height: 22px;
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

        /* No Permission Alert */
        .alert-block {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            background: #fef2f2;
            color: #7e000a;
            border: 1px solid rgba(126,0,10,0.2);
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-block svg { flex-shrink: 0; margin-top: 2px; }
        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border-color: rgba(180,130,0,0.25);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--surface);
            color: var(--on-surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .btn-back:hover { border-color: var(--outline-strong); }
    </style>
</head>
<body>
    <?php include './assets/user_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Post New Product</h1>
            <p>Fill in the details below to list your product</p>
        </div>

        <?php if ($needsPhone && $canPost): ?>
            <div class="alert-block alert-warning" style="margin-bottom:1.25rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div><strong>Phone number required:</strong> You need to add a phone number to your profile before posting.
                <a href="user_profile.php" style="color:#9d7c39;font-weight:700;margin-left:6px;">Add phone number →</a></div>
            </div>
        <?php endif; ?>

        <?php if ($canPost && !$needsPhone): ?>
            <div class="form-wrapper">
                <form action="../controllers/product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <div class="form-layout">
                        <!-- Left: Basic Info -->
                        <div>
                            <div class="form-section">
                                <h3 class="section-title">Basic Information</h3>
                                <div class="form-group">
                                    <label for="name">Product Name <span class="required">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. iPhone 15 Pro Max" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="prices">Price ($) <span class="required">*</span></label>
                                        <input type="number" id="prices" name="prices" class="form-control" step="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="discounts">Discount ($)</label>
                                        <input type="number" id="discounts" name="discounts" class="form-control" step="0.01" placeholder="Optional">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="category_id">Category <span class="required">*</span></label>
                                        <select id="category_id" name="category_id" class="form-control" required>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="location">Location <span class="required">*</span></label>
                                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Phnom Penh" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description <span class="required">*</span></label>
                                    <textarea id="description" name="description" class="form-control" rows="10" placeholder="Describe your product condition, features, and any other details..." required></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Images -->
                        <div>
                            <div class="form-section">
                                <h3 class="section-title">Product Images</h3>

                                <!-- Main Image -->
                                <div class="upload-main">
                                    <label>Main Image <span class="required">*</span></label>
                                    <div class="upload-zone" id="mainZone">
                                        <div class="upload-placeholder">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <p>Click to upload main image</p>
                                            <span class="hint">PNG, JPG up to 5MB</span>
                                        </div>
                                        <img id="mainPreview" class="preview-img" alt="Preview">
                                        <input type="file" id="mainImage" name="image" accept="image/*" required>
                                    </div>
                                </div>

                                <!-- Additional Images -->
                                <label>Additional Images (Optional)</label>
                                <div class="additional-images-grid">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="upload-thumb" id="thumbZone<?php echo $i; ?>">
                                        <div class="upload-placeholder">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </div>
                                        <img id="thumbPreview<?php echo $i; ?>" class="preview-img" alt="Preview">
                                        <button type="button" class="remove-btn" onclick="removeImage(<?php echo $i; ?>)" title="Remove">&times;</button>
                                        <input type="file" id="thumbInput<?php echo $i; ?>" name="image<?php echo $i; ?>" accept="image/*">
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions" style="margin-top: 1.25rem;">
                        <button type="submit" name="create_product" class="btn-submit">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Post Product
                        </button>
                        <a href="user_dashboard.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert-block">
                <?php if ($pendingRequest): ?>
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div><strong>Request Pending:</strong> Your request to post products is waiting for admin approval. We'll notify you once it's approved.</div>
                <?php else: ?>
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <div>
                        <strong>Permission Required:</strong> You need posting permission to list products.
                        <button id="requestPermBtn" onclick="requestPermission()" style="margin-left:10px;background:#1a3325;color:#fff;border:none;padding:7px 16px;border-radius:8px;font-weight:700;font-size:0.82rem;cursor:pointer;">
                            Request Permission
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <a href="user_dashboard.php" class="btn-back">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Back to Dashboard
            </a>
        <?php endif; ?>
    </div>

    <script>
        // Main image preview — guard against element not existing
        const mainImageInput = document.getElementById('mainImage');
        if (mainImageInput) {
            mainImageInput.addEventListener('change', function() {
                const zone    = document.getElementById('mainZone');
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
                const zone  = document.getElementById('thumbZone' + i);
                if (input) {
                    input.addEventListener('change', (function(idx) {
                        return function() {
                            if (this.files && this.files[0]) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    document.getElementById('thumbPreview' + idx).src = e.target.result;
                                    document.getElementById('thumbZone' + idx).classList.add('has-image');
                                };
                                reader.readAsDataURL(this.files[0]);
                            }
                        };
                    })(i));
                }
            }
        }

        // Remove image
        function removeImage(index) {
            const zone    = document.getElementById('thumbZone'    + index);
            const input   = document.getElementById('thumbInput'   + index);
            const preview = document.getElementById('thumbPreview' + index);
            if (zone)    zone.classList.remove('has-image');
            if (preview) preview.src = '';
            if (input)   input.value = '';
        }

        // Request permission via AJAX
        function requestPermission() {
            const btn = document.getElementById('requestPermBtn');
            if (btn) btn.disabled = true;
            fetch('../controllers/user.php?action=request_permission', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    if (btn) btn.disabled = false;
                    alert('Something went wrong. Please try again.');
                }
            })
            .catch(() => {
                if (btn) btn.disabled = false;
                alert('Network error. Please try again.');
            });
        }
    </script>
</body>
</html>
