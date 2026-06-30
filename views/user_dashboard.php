<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/ProductRepository.php';

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1.1 Fetch User Status (to check for pending requests)
$stmtUser = $conn->prepare("SELECT name, can_post, request_post_permission FROM user WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

// 1.2 Fetch User Profile (to check for phone number)
$stmtProfile = $conn->prepare("SELECT phone1 FROM user_profile WHERE user_id = ?");
$stmtProfile->execute([$_SESSION['user_id']]);
$userProfile = $stmtProfile->fetch();

// 2. Fetch My Products (with search)
$search = $_GET['search'] ?? '';
$productRepo = new ProductRepository($conn);

if (!empty($search)) {
    $myProducts = $productRepo->getByOwnerIdWithSearch($_SESSION['user_id'], $search);
} else {
    $stmt = $conn->prepare("SELECT p.*, pi.main_image, c.name as category_name FROM product p LEFT JOIN product_image pi ON p.product_image_id = pi.id LEFT JOIN category c ON p.category_id = c.id WHERE p.owner_id = ? ORDER BY p.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $myProducts = $stmt->fetchAll();
}

$approvedCount = array_reduce($myProducts, fn($carry, $p) => $carry + ($p['showed'] ? 1 : 0), 0);
$hiddenCount = count($myProducts) - $approvedCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard</title>
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
            --success: #10b981;
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
            --shadow-sm: 0 2px 8px rgba(32, 27, 9, 0.06);
            --shadow-md: 0 4px 16px rgba(32, 27, 9, 0.08);
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

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 1.5rem 3rem;
            max-width: calc(100vw - 240px);
        }

        @media (max-width: 992px) {
            .main-content { padding: 1.25rem 2rem; }
        }

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
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .page-header h1 {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
        }

        .btn-header {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-post-product {
            background: var(--secondary);
            color: #fff;
        }
        .btn-post-product:hover { background: #b08f45; }

        .btn-post-product.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-request {
            background: #17a2b8;
            color: #fff;
        }
        .btn-request:hover { background: #138496; }

        .btn-pending {
            background: #ffc107;
            color: #000;
            pointer-events: none;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-container) 100%);
            color: #fff;
            padding: 1.5rem 1.75rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .welcome-text h2 {
            font-family: var(--font-headline);
            font-size: 1.375rem;
            font-weight: 800;
            margin: 0 0 0.2rem;
        }

        .welcome-text p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.875rem;
        }

        .welcome-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-welcome {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 1.5px solid rgba(255,255,255,0.25);
            color: #fff;
            background: rgba(255,255,255,0.08);
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-welcome:hover {
            background: rgba(255,255,255,0.18);
            border-color: rgba(255,255,255,0.4);
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon.total { background: var(--primary-light); color: var(--primary); }
        .stat-icon.active { background: var(--secondary-light); color: var(--secondary); }
        .stat-icon.hidden { background: rgba(126, 0, 10, 0.08); color: var(--tertiary); }

        .stat-icon svg { width: 20px; height: 20px; }

        .stat-info .stat-value {
            font-family: var(--font-headline);
            font-size: 1.375rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.1;
        }

        .stat-info .stat-label {
            font-size: 0.7rem;
            color: var(--on-surface-variant);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            margin: 0;
        }

        /* Alerts */
        .alert-card {
            padding: 0.875rem 1.125rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.825rem;
        }

        .alert-card svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px; }

        .alert-warning {
            background: #fff8e1;
            color: #856404;
            border: 1px solid #ffe082;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-card a {
            color: inherit;
            text-decoration: underline;
            font-weight: 700;
        }

        /* Section Header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .section-header h2 {
            font-family: var(--font-headline);
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--on-surface);
            margin: 0;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input-wrapper svg.search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--on-surface-variant);
            opacity: 0.4;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 11px 14px 11px 44px;
            background: var(--surface);
            border: 1.5px solid var(--outline);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-family: var(--font-body);
            color: var(--on-surface);
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .search-input:focus ~ svg.search-icon {
            opacity: 1;
            color: var(--primary);
        }

        .search-input::placeholder {
            color: rgba(107, 99, 85, 0.4);
        }

        .btn-search-clear {
            padding: 11px 18px;
            background: var(--surface);
            border: 1.5px solid var(--outline);
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--on-surface-variant);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-search-clear:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-search-clear svg {
            width: 16px;
            height: 16px;
        }

        .search-results-info {
            padding: 10px 14px;
            background: var(--primary-light);
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            font-size: 0.825rem;
            color: var(--primary);
            font-weight: 600;
        }

        .search-results-info strong {
            font-weight: 700;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.25rem;
        }

        @media (max-width: 768px) {
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        }

        @media (max-width: 480px) {
            .product-grid { grid-template-columns: 1fr; }
        }

        .product-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.3s;
        }

        .product-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--outline-strong);
        }

        .product-card-image {
            position: relative;
            width: 100%;
            padding-top: 60%;
            background: var(--surface-low);
            overflow: hidden;
        }

        .product-card-image img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
        }

        .product-card.hidden-item .product-card-image img {
            filter: grayscale(1);
            opacity: 0.5;
        }

        /* Product Card Link */
        .product-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card-link:hover .product-card-image {
            opacity: 0.92;
        }

        .product-name-link {
            text-decoration: none;
            color: var(--on-surface);
            transition: color 0.2s;
        }

        .product-name-link:hover {
            color: var(--primary);
        }

        .badge {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-hidden { background: var(--tertiary); color: #fff; }
        .badge-active { background: var(--success); color: #fff; }

        .product-card-body {
            padding: 0.875rem 1rem 1rem;
        }

        .product-card-body h3 {
            font-family: var(--font-headline);
            font-size: 0.925rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .product-price {
            font-weight: 800;
            font-size: 1rem;
            color: var(--primary);
        }

        .product-category-tag {
            font-size: 0.65rem;
            color: var(--secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .product-card-actions {
            display: flex;
            gap: 0.375rem;
            margin-top: 0.75rem;
            padding-top: 0.625rem;
            border-top: 1px solid var(--outline);
        }

        .btn-card {
            flex: 1;
            padding: 6px 0;
            text-align: center;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: all 0.2s;
        }

        .btn-card-view {
            background: var(--secondary-light);
            color: var(--secondary);
            border: 1px solid rgba(157, 124, 57, 0.2);
        }
        .btn-card-view:hover {
            background: var(--secondary);
            color: #fff;
        }

        .btn-card-edit {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid var(--outline);
        }
        .btn-card-edit:hover {
            background: var(--primary);
            color: #fff;
        }

        .btn-card-delete {
            background: rgba(126, 0, 10, 0.06);
            color: var(--tertiary);
            border: 1px solid rgba(126, 0, 10, 0.15);
        }
        .btn-card-delete:hover {
            background: var(--tertiary);
            color: #fff;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2.5rem 2rem;
            background: var(--surface);
            border: 1px dashed var(--outline);
            border-radius: var(--radius-lg);
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            color: var(--on-surface-variant);
            opacity: 0.3;
            margin-bottom: 0.75rem;
        }

        .empty-state h3 {
            font-family: var(--font-headline);
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 0.4rem;
        }

        .empty-state p {
            color: var(--on-surface-variant);
            font-size: 0.825rem;
            margin: 0;
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
            <h1>My Products</h1>
            <div>
                <?php if ($currentUser['can_post'] == 1): ?>
                    <a href="product_create.php" class="btn-header btn-post-product">+ Post Product</a>
                <?php elseif ($currentUser['request_post_permission'] == 1): ?>
                    <span class="btn-header btn-pending">Request Pending</span>
                <?php else: ?>
                    <button id="request-btn" class="btn-header btn-request">Request Permission</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>!</h2>
                <p>Manage your products and track your listings</p>
            </div>
            <div class="welcome-actions">
                <a href="user_profile.php" class="btn-welcome">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Edit Profile
                </a>
                <a href="logout.php" class="btn-welcome">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($currentUser['can_post'] != 1): ?>
            <div class="alert-card alert-warning">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <strong>Posting Pending:</strong> Your posting permission is awaiting admin approval. You can still browse and manage existing listings.
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($userProfile) || empty($userProfile['phone1'])): ?>
            <div class="alert-card alert-danger">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <strong>Action Required:</strong> You must add a phone number before posting products.
                    <a href="user_profile.php">Update your profile now</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon total">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo count($myProducts); ?></p>
                    <p class="stat-label">Total</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $approvedCount; ?></p>
                    <p class="stat-label">Active</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon hidden">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $hiddenCount; ?></p>
                    <p class="stat-label">Hidden</p>
                </div>
            </div>
        </div>

        <!-- My Products -->
        <div class="section-header">
            <h2>My Products</h2>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" name="search" class="search-input" placeholder="Search my products..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <?php if (!empty($search)): ?>
                <a href="user_dashboard.php" class="btn-search-clear">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if (!empty($search)): ?>
            <div class="search-results-info">
                Found <strong><?php echo count($myProducts); ?></strong> product<?php echo count($myProducts) !== 1 ? 's' : ''; ?> matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
            </div>
        <?php endif; ?>

        <?php if (count($myProducts) > 0): ?>
            <div class="product-grid">
                <?php foreach ($myProducts as $product): ?>
                    <div class="product-card <?php echo !$product['showed'] ? 'hidden-item' : ''; ?>">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                            <div class="product-card-image">
                                <img src="../uploads/products/<?php echo htmlspecialchars($product['main_image'] ?? 'default.png'); ?>" alt="Product Image">
                                <?php if (!$product['showed']): ?>
                                    <span class="badge badge-hidden">Hidden</span>
                                <?php else: ?>
                                    <span class="badge badge-active">Active</span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="product-card-body">
                            <h3><a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-name-link"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                            <div class="product-meta">
                                <span class="product-price">$<?php echo number_format($product['prices'], 2); ?></span>
                                <?php if (!empty($product['category_name'])): ?>
                                    <span class="product-category-tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-actions">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-card btn-card-view">View</a>
                                <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn-card btn-card-edit">Edit</a>
                                <a href="../controllers/product.php?action=delete&id=<?php echo $product['id']; ?>" class="btn-card btn-card-delete" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <h3>No products yet</h3>
                <p>Start by posting your first product listing</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div class="toast <?php echo isset($_GET['success']) ? 'toast-success' : 'toast-error'; ?>">
            <?php echo htmlspecialchars($_GET['success'] ?? $_GET['error'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <script>
        const requestBtn = document.getElementById('request-btn');
        if (requestBtn) {
            requestBtn.addEventListener('click', function() {
                requestBtn.disabled = true;
                requestBtn.textContent = 'Sending...';

                fetch('../controllers/user.php?action=request_permission', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        requestBtn.parentElement.innerHTML = '<span class="btn-header btn-pending">Request Pending</span>';
                    } else {
                        alert('Error: ' + data.message);
                        requestBtn.disabled = false;
                        requestBtn.textContent = 'Request Permission';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    requestBtn.disabled = false;
                    requestBtn.textContent = 'Request Permission';
                });
            });
        }
    </script>
</body>
</html>
