<?php
session_start();
require_once '../repos/CategoryRepository.php';
require_once '../configs/connect.php';

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

// 2. Handle Ordering parameters
$orderBy = isset($_GET['order']) ? $_GET['order'] : 'id_desc';

// 3. Fetch Categories
$categories = [];
try {
    $categoryRepo = new CategoryRepository($conn);
    $categories = $categoryRepo->getAllWithFilters('', $orderBy);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Categories</title>
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
            justify-content: space-between;
            margin-bottom: 1.5rem;
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

        .page-header .count-badge {
            font-size: 0.8rem;
            color: var(--on-surface-variant);
            font-weight: 600;
            background: var(--surface);
            border: 1px solid var(--outline);
            padding: 6px 14px;
            border-radius: 20px;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: var(--primary);
            color: #fff;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-add:hover { background: var(--primary-container); }
        .btn-add svg { width: 16px; height: 16px; }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.25rem; }
            .btn-add { padding: 8px 16px; font-size: 0.75rem; }
        }

        @media (max-width: 480px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            .page-header .count-badge {
                align-self: flex-start;
            }
        }

        /* Table Card */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 12px 16px;
            text-align: left;
            background: var(--bg-body);
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--on-surface-variant);
            border-bottom: 1px solid var(--outline);
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--outline);
            font-size: 0.85rem;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover { background: var(--primary-light); }

        .cat-img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            background: var(--bg-body);
        }

        .cat-name {
            font-weight: 600;
            color: var(--on-surface);
        }

        .action-link {
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            transition: opacity 0.2s;
            white-space: nowrap;
        }

        .action-link.edit { color: var(--primary); }
        .action-link.delete { color: var(--tertiary); }
        .action-link:hover { opacity: 0.75; }

        @media (max-width: 768px) {
            .action-link {
                font-size: 0.75rem;
            }
            .cat-img {
                width: 40px;
                height: 40px;
            }
            th, td {
                padding: 10px 12px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .action-link {
                font-size: 0.7rem;
            }
            .cat-img {
                width: 36px;
                height: 36px;
            }
            th, td {
                padding: 8px 10px;
                font-size: 0.75rem;
            }
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--on-surface-variant);
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            opacity: 0.3;
            margin-bottom: 0.75rem;
        }

        .empty-state p { margin: 0; font-size: 0.875rem; }

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
            <h1>Category Management</h1>
            <span class="count-badge"><?php echo count($categories); ?> categor<?php echo count($categories) !== 1 ? 'ies' : 'y'; ?></span>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <a href="admin_category_add.php" class="btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add New Category
            </a>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="?order=<?php echo ($orderBy === 'id_asc') ? 'id_desc' : 'id_asc'; ?>"
                                   style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 4px;">
                                    ID
                                    <?php if (strpos($orderBy, 'id') !== false): ?>
                                        <span><?php echo $orderBy === 'id_asc' ? '↑' : '↓'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?order=<?php echo ($orderBy === 'name_asc') ? 'name_desc' : 'name_asc'; ?>"
                                   style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 4px;">
                                    Name
                                    <?php if (strpos($orderBy, 'name') !== false): ?>
                                        <span><?php echo $orderBy === 'name_asc' ? '↑' : '↓'; ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat['id']); ?></td>
                                    <td><span class="cat-name"><?php echo htmlspecialchars($cat['name']); ?></span></td>
                                    <td>
                                        <img src="../uploads/categories/<?php echo htmlspecialchars($cat['category_image']); ?>" class="cat-img">
                                    </td>
                                    <td>
                                        <a href="admin_category_edit.php?id=<?php echo $cat['id']; ?>" class="action-link edit">Edit</a>
                                        <span style="margin: 0 6px; color: var(--outline-strong);">|</span>
                                        <a href="../controllers/category.php?action=delete&id=<?php echo $cat['id']; ?>" class="action-link delete" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                        <p>No categories found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div class="toast <?php echo isset($_GET['success']) ? 'toast-success' : 'toast-error'; ?>">
            <?php echo htmlspecialchars($_GET['success'] ?? $_GET['error'] ?? ''); ?>
        </div>
    <?php endif; ?>
</body>
</html>
