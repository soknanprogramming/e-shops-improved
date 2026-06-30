<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/UserRepository.php';
require_once '../repos/ProductRepository.php';
require_once '../repos/CategoryRepository.php';

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

$userRepo = new UserRepository($conn);
$pendingRequests = $userRepo->getPendingRequests();
$pendingCount = count($pendingRequests);

// Fetch stats
$productRepo = new ProductRepository($conn);
$catRepo = new CategoryRepository($conn);

$stmtTotalUsers = $conn->prepare("SELECT COUNT(*) as total FROM user");
$stmtTotalUsers->execute();
$totalUsers = $stmtTotalUsers->fetch()['total'];

$stmtTotalProducts = $conn->prepare("SELECT COUNT(*) as total FROM product");
$stmtTotalProducts->execute();
$totalProducts = $stmtTotalProducts->fetch()['total'];

$stmtTotalCategories = $conn->prepare("SELECT COUNT(*) as total FROM category");
$stmtTotalCategories->execute();
$totalCategories = $stmtTotalCategories->fetch()['total'];

$stmtActiveProducts = $conn->prepare("SELECT COUNT(*) as total FROM product WHERE showed = 1");
$stmtActiveProducts->execute();
$activeProducts = $stmtActiveProducts->fetch()['total'];

// Fetch recent data
$stmtRecentProducts = $conn->prepare("SELECT p.*, u.name as owner_name, pi.main_image FROM product p LEFT JOIN user u ON p.owner_id = u.id LEFT JOIN product_image pi ON p.product_image_id = pi.id ORDER BY p.created_at DESC LIMIT 5");
$stmtRecentProducts->execute();
$recentProducts = $stmtRecentProducts->fetchAll();

$stmtRecentUsers = $conn->prepare("SELECT name, email, created_at FROM user ORDER BY created_at DESC LIMIT 5");
$stmtRecentUsers->execute();
$recentUsers = $stmtRecentUsers->fetchAll();

$stmtHiddenProducts = $conn->prepare("SELECT COUNT(*) as total FROM product WHERE showed = 0");
$stmtHiddenProducts->execute();
$hiddenProducts = $stmtHiddenProducts->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.25rem; }
            .page-header p { font-size: 0.8rem; }
        }

        @media (max-width: 480px) {
            .page-header h1 { font-size: 1.125rem; }
            .page-header p { font-size: 0.75rem; }
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-container) 100%);
            border-radius: var(--radius-lg);
            padding: 2rem 2.5rem;
            margin-bottom: 1.5rem;
            color: #fff;
        }

        .welcome-card h2 {
            font-family: var(--font-headline);
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0 0 0.5rem;
        }

        .welcome-card p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .welcome-card { padding: 1.5rem; }
            .welcome-card h2 { font-size: 1.125rem; }
            .welcome-card p { font-size: 0.85rem; }
        }

        @media (max-width: 480px) {
            .welcome-card { padding: 1.25rem; }
            .welcome-card h2 { font-size: 1rem; }
            .welcome-card p { font-size: 0.8rem; }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 992px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .stats-grid { grid-template-columns: 1fr; } }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg { width: 24px; height: 24px; }

        .stat-icon.users { background: rgba(26, 51, 37, 0.1); color: var(--primary); }
        .stat-icon.products { background: rgba(157, 124, 57, 0.1); color: var(--secondary); }
        .stat-icon.categories { background: rgba(126, 0, 10, 0.1); color: var(--tertiary); }
        .stat-icon.active { background: rgba(40, 167, 69, 0.1); color: #28a745; }

        .stat-info .stat-value {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
        }

        .stat-info .stat-label {
            font-size: 0.75rem;
            color: var(--on-surface-variant);
            font-weight: 600;
            margin: 0.25rem 0 0;
        }

        @media (max-width: 768px) {
            .stat-card { padding: 1rem 1.25rem; }
            .stat-icon { width: 40px; height: 40px; }
            .stat-icon svg { width: 20px; height: 20px; }
            .stat-info .stat-value { font-size: 1.25rem; }
        }

        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            .stat-info .stat-value { font-size: 1.5rem; }
        }

        /* Pending Alert */
        .pending-alert {
            background: var(--secondary-light);
            border: 1px solid rgba(157, 124, 57, 0.2);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .pending-alert-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .pending-alert svg {
            width: 28px;
            height: 28px;
            color: var(--secondary);
            flex-shrink: 0;
        }

        .pending-alert h3 {
            font-family: var(--font-headline);
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 0.125rem;
            color: var(--on-surface);
        }

        .pending-alert p {
            margin: 0;
            font-size: 0.825rem;
            color: var(--on-surface-variant);
        }

        .btn-review {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: var(--secondary);
            color: #fff;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: opacity 0.2s;
            white-space: nowrap;
        }

        .btn-review:hover { opacity: 0.85; }
        .btn-review svg { width: 16px; height: 16px; }

        @media (max-width: 768px) {
            .pending-alert {
                padding: 1rem;
            }
            .pending-alert h3 { font-size: 0.9rem; }
            .pending-alert p { font-size: 0.775rem; }
            .btn-review { padding: 8px 16px; font-size: 0.75rem; }
        }

        @media (max-width: 480px) {
            .pending-alert {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .pending-alert-left {
                flex-direction: column;
                justify-content: center;
            }
            .btn-review {
                justify-content: center;
            }
        }

        /* Quick Actions */
        .section-title {
            font-family: var(--font-headline);
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--on-surface);
            margin: 0 0 1rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) { .quick-actions { grid-template-columns: 1fr; } }

        .quick-action-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quick-action-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .quick-action-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .quick-action-icon svg { width: 22px; height: 22px; }

        .quick-action-icon.users-icon { background: rgba(26, 51, 37, 0.1); color: var(--primary); }
        .quick-action-icon.products-icon { background: rgba(157, 124, 57, 0.1); color: var(--secondary); }
        .quick-action-icon.categories-icon { background: rgba(126, 0, 10, 0.1); color: var(--tertiary); }

        .quick-action-text h3 {
            font-family: var(--font-headline);
            font-size: 0.9rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
            color: var(--on-surface);
        }

        .quick-action-text p {
            margin: 0;
            font-size: 0.75rem;
            color: var(--on-surface-variant);
        }

        @media (max-width: 768px) {
            .quick-action-card { padding: 1.25rem; }
            .quick-action-icon { width: 40px; height: 40px; }
            .quick-action-icon svg { width: 20px; height: 20px; }
            .quick-action-text h3 { font-size: 0.85rem; }
        }

        @media (max-width: 480px) {
            .quick-action-card {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
        }

        /* Recent Activity Grid */
        .recent-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) { .recent-grid { grid-template-columns: 1fr; } }

        .recent-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1.25rem 1.5rem;
        }

        .recent-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--outline);
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .recent-item:last-child { border-bottom: none; }

        .recent-item-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            min-width: 0;
        }

        .recent-item-img {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--bg-body);
            flex-shrink: 0;
        }

        .recent-item-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--on-surface);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recent-item-meta {
            font-size: 0.75rem;
            color: var(--on-surface-variant);
            margin: 0.125rem 0 0;
        }

        .recent-item-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            flex-shrink: 0;
        }

        .badge-visible { background: rgba(40, 167, 69, 0.12); color: #28a745; }
        .badge-hidden { background: rgba(108, 117, 125, 0.12); color: #6c757d; }

        @media (max-width: 768px) {
            .recent-item-img { width: 36px; height: 36px; }
            .recent-item-name { font-size: 0.8rem; }
            .recent-item-meta { font-size: 0.7rem; }
            .recent-item-badge { font-size: 0.6rem; padding: 3px 8px; }
        }

        @media (max-width: 480px) {
            .recent-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .recent-item-badge {
                align-self: flex-start;
            }
        }

        .view-all-link {
            display: block;
            text-align: center;
            padding: 0.75rem;
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.8rem;
            border-top: 1px solid var(--outline);
            margin-top: 0.5rem;
            transition: all 0.2s;
        }

        .view-all-link:hover {
            background: var(--primary-light);
        }
    </style>
</head>
<body>
    <?php include './assets/admin_sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2>Admin Control Panel</h2>
            <p>Manage users, products, and categories from this central dashboard.</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $totalUsers; ?></p>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon products">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $totalProducts; ?></p>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon categories">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $totalCategories; ?></p>
                    <p class="stat-label">Categories</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $activeProducts; ?></p>
                    <p class="stat-label">Active Products</p>
                </div>
            </div>
        </div>

        <!-- Pending Requests Alert -->
        <?php if ($pendingCount > 0): ?>
            <div class="pending-alert">
                <div class="pending-alert-left">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <h3><?php echo $pendingCount; ?> Pending Request<?php echo $pendingCount > 1 ? 's' : ''; ?></h3>
                        <p>Users are waiting for posting permission approval.</p>
                    </div>
                </div>
                <a href="admin_user.php?filter=requesting" class="btn-review">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Review
                </a>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <h2 class="section-title" style="margin-top: 2rem;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="admin_user.php" class="quick-action-card">
                <div class="quick-action-icon users-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div class="quick-action-text">
                    <h3>Manage Users</h3>
                    <p>View and manage user accounts</p>
                </div>
            </a>
            <a href="admin_product.php" class="quick-action-card">
                <div class="quick-action-icon products-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div class="quick-action-text">
                    <h3>Manage Products</h3>
                    <p>Review and moderate product listings</p>
                </div>
            </a>
            <a href="admin_category.php" class="quick-action-card">
                <div class="quick-action-icon categories-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <div class="quick-action-text">
                    <h3>Manage Categories</h3>
                    <p>Add, edit, or remove categories</p>
                </div>
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="recent-grid">
            <!-- Recent Products -->
            <div class="recent-card">
                <h2 class="section-title">Recent Products</h2>
                <?php foreach ($recentProducts as $rp): ?>
                    <div class="recent-item">
                        <div class="recent-item-info">
                            <img src="../uploads/products/<?php echo htmlspecialchars($rp['main_image'] ?? 'default.png'); ?>" class="recent-item-img">
                            <div>
                                <p class="recent-item-name"><?php echo htmlspecialchars($rp['name']); ?></p>
                                <p class="recent-item-meta">by <?php echo htmlspecialchars($rp['owner_name']); ?> · $<?php echo number_format($rp['prices'], 2); ?></p>
                            </div>
                        </div>
                        <span class="recent-item-badge <?php echo $rp['showed'] ? 'badge-visible' : 'badge-hidden'; ?>">
                            <?php echo $rp['showed'] ? 'Active' : 'Hidden'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                <a href="admin_product.php" class="view-all-link">View All Products →</a>
            </div>

            <!-- Recent Users -->
            <div class="recent-card">
                <h2 class="section-title">Recent Users</h2>
                <?php foreach ($recentUsers as $ru): ?>
                    <div class="recent-item">
                        <div class="recent-item-info">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <p class="recent-item-name"><?php echo htmlspecialchars($ru['name']); ?></p>
                                <p class="recent-item-meta"><?php echo htmlspecialchars($ru['email']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <a href="admin_user.php" class="view-all-link">View All Users →</a>
            </div>
        </div>
         <?php include './assets/footer.php'; ?>
    </div>
   
</body>
</html>
