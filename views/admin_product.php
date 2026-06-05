<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/ProductRepository.php';

// ── Auth Check ─────────────────────────────────────────────────────────────────
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$productRepo = new ProductRepository($conn);

$orderBy = isset($_GET['order']) ? $_GET['order'] : 'id_desc';

$sortMap = [
    'id_desc'     => 'newest',   'id_asc'      => 'oldest',
    'name_asc'    => 'name_asc', 'name_desc'   => 'name_desc',
    'price_asc'   => 'price_asc','price_desc'  => 'price_desc',
    'owner_asc'   => 'owner_asc','owner_desc'  => 'owner_desc',
    'status_asc'  => 'status_asc','status_desc'=> 'status_desc',
];

$filters = [
    'include_hidden' => true,
    'name'   => $_GET['name']   ?? null,
    'seller' => $_GET['seller'] ?? null,
    'sort'   => $sortMap[$orderBy] ?? 'newest',
];

$products = $productRepo->search($filters);

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status   = (int)$_GET['status'];
    $products = array_filter($products, fn($p) => $p['showed'] == $status);
}

// build query string helper (keeps current filters in sort links)
function qstr($extra = []) {
    $base = array_filter([
        'name'   => $_GET['name']   ?? '',
        'seller' => $_GET['seller'] ?? '',
        'status' => $_GET['status'] ?? '',
    ]);
    return http_build_query(array_merge($base, $extra));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="icon" href="../icon/e-commerce-logo.png" sizes="any" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3325; --primary-container: #2a5038;
            --primary-light: rgba(26,51,37,.05);
            --secondary: #9d7c39;
            --tertiary: #7e000a;
            --bg-body: #faf7f2; --surface: #ffffff;
            --on-surface: #201b09; --on-surface-variant: #6b6355;
            --outline: rgba(74,69,56,.12); --outline-strong: rgba(74,69,56,.25);
            --radius-sm: 8px; --radius-md: 12px; --radius-lg: 16px;
            --font-headline: 'Manrope', sans-serif;
            --font-body: 'Public Sans', sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: var(--font-body); margin: 0; padding: 0; background: var(--bg-body); color: var(--on-surface); display: flex; min-height: 100vh; }

        .main-content { flex-grow: 1; padding: 1.5rem 3rem; width: 100%; }
        @media (max-width:992px) { .main-content { padding: 1.25rem 2rem; } }
        @media (max-width:768px) { body { flex-direction: column; } .main-content { padding: 1rem; } }

        /* ── Page header ── */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: .75rem; }
        .page-header h1 { font-family: var(--font-headline); font-size: 1.5rem; font-weight: 800; color: var(--primary); margin: 0; }
        .count-badge { font-size: .8rem; color: var(--on-surface-variant); font-weight: 600; background: var(--surface); border: 1px solid var(--outline); padding: 6px 14px; border-radius: 20px; }

        /* ── Filter bar ── */
        .filter-bar { background: var(--surface); border: 1px solid var(--outline); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: flex; gap: .75rem; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 150px; }
        .filter-bar label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--on-surface-variant); }
        .filter-bar input, .filter-bar select { padding: 8px 10px; border: 1.5px solid var(--outline); border-radius: var(--radius-sm); font-size: .825rem; font-family: var(--font-body); color: var(--on-surface); background: var(--bg-body); outline: none; transition: border-color .2s; width: 100%; }
        .filter-bar input:focus, .filter-bar select:focus { border-color: var(--primary); }
        .btn-filter { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; cursor: pointer; transition: background .2s; white-space: nowrap; }
        .btn-filter:hover { background: var(--primary-container); }
        .btn-reset { display: inline-flex; align-items: center; padding: 8px 16px; background: transparent; color: var(--on-surface-variant); border: 1.5px solid var(--outline-strong); border-radius: var(--radius-sm); text-decoration: none; font-weight: 600; font-size: .75rem; text-transform: uppercase; transition: all .2s; white-space: nowrap; }
        .btn-reset:hover { border-color: var(--on-surface-variant); color: var(--on-surface); }

        /* ── Table ── */
        .table-card { background: var(--surface); border: 1px solid var(--outline); border-radius: var(--radius-md); overflow: hidden; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 16px; text-align: left; background: var(--bg-body); font-weight: 700; font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: var(--on-surface-variant); border-bottom: 1px solid var(--outline); }
        th a { text-decoration: none; color: inherit; display: inline-flex; align-items: center; gap: 4px; }
        td { padding: 12px 16px; border-bottom: 1px solid var(--outline); font-size: .85rem; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        /* Clickable row */
        .product-row { cursor: pointer; transition: background .15s; }
        .product-row:hover { background: var(--primary-light); }
        .product-row.row-active { background: rgba(26,51,37,.08); border-left: 3px solid var(--primary); }
        .product-row.row-active td:first-child { padding-left: 13px; }

        .product-img { width: 44px; height: 44px; object-fit: cover; border-radius: var(--radius-sm); background: var(--bg-body); display: block; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .badge-visible { background: rgba(40,167,69,.12); color: #28a745; }
        .badge-hidden  { background: rgba(108,117,125,.12); color: #6c757d; }

        .action-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: var(--radius-sm); text-decoration: none; font-weight: 600; font-size: .75rem; transition: all .2s; white-space: nowrap; }
        .btn-show { background: rgba(40,167,69,.12); color: #28a745; }
        .btn-show:hover { background: rgba(40,167,69,.2); }
        .btn-hide { background: rgba(220,53,69,.12); color: #dc3545; }
        .btn-hide:hover { background: rgba(220,53,69,.2); }

        .row-hint { font-size: .68rem; color: var(--primary); opacity: 0; font-weight: 600; transition: opacity .15s; white-space: nowrap; }
        .product-row:hover .row-hint { opacity: 1; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--on-surface-variant); }
        .empty-state svg { width: 48px; height: 48px; opacity: .3; margin-bottom: .75rem; }
        .empty-state p { margin: 0; font-size: .875rem; }

        /* ── Toast ── */
        .toast { position: fixed; bottom: 24px; right: 24px; padding: 10px 18px; border-radius: var(--radius-sm); color: #fff; font-weight: 600; font-size: .825rem; z-index: 9999; animation: toastIn .3s ease, toastOut .3s ease 2.7s forwards; }
        .toast-success { background: var(--primary); }
        .toast-error   { background: var(--tertiary); }
        @keyframes toastIn  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes toastOut { from { opacity:1; } to { opacity:0; transform:translateY(10px); } }

        /* ══════════════════════════════════════════════
           PRODUCT DETAIL MODAL
        ══════════════════════════════════════════════ */
        .pdm-overlay {
            position: fixed; inset: 0;
            background: rgba(15,25,18,.55);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
            opacity: 0; visibility: hidden;
            transition: opacity .25s, visibility .25s;
        }
        .pdm-overlay.open { opacity: 1; visibility: visible; }

        .pdm-sheet {
            background: #fff;
            border-radius: 18px;
            width: 100%; max-width: 860px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 24px 80px rgba(0,0,0,.22);
            transform: translateY(20px) scale(.98);
            transition: transform .28s cubic-bezier(.34,1.56,.64,1);
            scrollbar-width: thin;
            scrollbar-color: rgba(26,51,37,.2) transparent;
        }
        .pdm-overlay.open .pdm-sheet { transform: translateY(0) scale(1); }
        .pdm-sheet::-webkit-scrollbar { width: 5px; }
        .pdm-sheet::-webkit-scrollbar-thumb { background: rgba(26,51,37,.2); border-radius: 4px; }

        .pdm-close {
            position: sticky; top: 14px; float: right;
            margin: 14px 14px 0 0;
            width: 36px; height: 36px; border-radius: 50%;
            border: 1px solid var(--outline); background: rgba(255,255,255,.95);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #6b6355; z-index: 10; transition: all .2s;
        }
        .pdm-close:hover { background: #fff; color: var(--tertiary); border-color: rgba(126,0,10,.3); }

        .pdm-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 5rem 2rem; gap: 1rem; color: #6b6355; font-size: .875rem; }
        .pdm-spinner { width: 32px; height: 32px; border: 3px solid rgba(26,51,37,.12); border-top-color: var(--primary); border-radius: 50%; animation: pdm-spin .7s linear infinite; }
        @keyframes pdm-spin { to { transform: rotate(360deg); } }

        /* Gallery */
        .pdm-gallery { padding: 24px 24px 0; }
        .pdm-main-img-wrap {
            position: relative; width: 100%;
            aspect-ratio: 16/7;
            border-radius: 14px; overflow: hidden;
            background: #f0ede8; cursor: zoom-in;
        }
        .pdm-main-img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .3s; }
        .pdm-main-img-wrap:hover .pdm-main-img { transform: scale(1.03); }
        .pdm-img-hint {
            position: absolute; bottom: 10px; right: 10px;
            background: rgba(0,0,0,.45); backdrop-filter: blur(4px);
            color: #fff; font-size: .62rem; font-weight: 600;
            padding: 4px 10px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: .05em;
            pointer-events: none;
        }
        .pdm-thumbs { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .pdm-thumb {
            width: 60px; height: 60px; border-radius: 8px;
            object-fit: cover; cursor: pointer;
            border: 2px solid transparent;
            transition: border-color .15s, transform .15s;
        }
        .pdm-thumb:hover { border-color: var(--primary); transform: scale(1.05); }
        .pdm-thumb.active { border-color: var(--primary); }

        /* Body */
        .pdm-body { padding: 20px 24px 28px; }

        /* Title row */
        .pdm-title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 12px; }
        .pdm-title { font-family: var(--font-headline); font-size: 1.4rem; font-weight: 800; color: var(--on-surface); margin: 0; }
        .pdm-visibility { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0; }
        .pdm-vis-shown { background: rgba(40,167,69,.12); color: #1a6631; }
        .pdm-vis-hidden { background: rgba(220,53,69,.08); color: #a0202e; }

        /* Price */
        .pdm-price-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .pdm-price { font-family: var(--font-headline); font-size: 1.6rem; font-weight: 800; color: var(--primary); }
        .pdm-discount-badge { background: rgba(157,124,57,.14); color: #7a5c14; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .pdm-original { font-size: .85rem; color: #9d8c6e; text-decoration: line-through; }

        /* Meta grid */
        .pdm-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; border: 1px solid var(--outline); border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
        @media (max-width:560px) { .pdm-meta-grid { grid-template-columns: 1fr; } }
        .pdm-meta-cell { padding: 12px 14px; background: #fff; border-right: 1px solid var(--outline); border-bottom: 1px solid var(--outline); }
        .pdm-meta-cell:nth-child(2n) { border-right: none; }
        .pdm-meta-cell:nth-last-child(-n+2) { border-bottom: none; }
        .pdm-meta-label { font-size: .62rem; text-transform: uppercase; letter-spacing: .07em; color: var(--secondary); font-weight: 700; margin-bottom: 4px; }
        .pdm-meta-value { font-size: .82rem; font-weight: 600; color: var(--on-surface); }

        /* Stats row */
        .pdm-stats { display: flex; gap: 1px; border: 1px solid var(--outline); border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
        .pdm-stat { flex: 1; text-align: center; padding: 12px 8px; background: #fff; border-right: 1px solid var(--outline); }
        .pdm-stat:last-child { border-right: none; }
        .pdm-stat-num { font-family: var(--font-headline); font-size: 1.3rem; font-weight: 800; color: var(--primary); line-height: 1; }
        .pdm-stat-label { font-size: .63rem; color: #6b6355; text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

        /* Description */
        .pdm-desc-label { font-size: .62rem; text-transform: uppercase; letter-spacing: .07em; color: var(--secondary); font-weight: 700; margin-bottom: 8px; }
        .pdm-desc { font-size: .84rem; color: #4a4538; line-height: 1.7; background: #faf7f2; border: 1px solid var(--outline); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }

        /* Seller card */
        .pdm-seller { display: flex; align-items: center; gap: 12px; background: #faf7f2; border: 1px solid var(--outline); border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; }
        .pdm-seller-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.1); flex-shrink: 0; }
        .pdm-seller-initials { width: 44px; height: 44px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.1); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0; font-family: var(--font-headline); }
        .pdm-seller-name { font-weight: 700; font-size: .88rem; color: var(--on-surface); }
        .pdm-seller-meta { font-size: .74rem; color: #6b6355; margin-top: 2px; }
        .pdm-view-profile { margin-left: auto; padding: 6px 14px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); font-size: .72rem; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: .04em; transition: background .2s; flex-shrink: 0; font-family: var(--font-body); }
        .pdm-view-profile:hover { background: var(--primary-container); }

        /* Comments */
        .pdm-comments-label { font-size: .62rem; text-transform: uppercase; letter-spacing: .07em; color: var(--secondary); font-weight: 700; margin-bottom: 8px; }
        .pdm-comment { display: flex; gap: 8px; margin-bottom: 8px; }
        .pdm-comment-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--outline); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; color: var(--on-surface-variant); }
        .pdm-comment-bubble { flex: 1; background: #faf7f2; border: 1px solid var(--outline); border-radius: 10px; padding: 8px 12px; }
        .pdm-comment-user { font-size: .72rem; font-weight: 700; color: var(--primary); }
        .pdm-comment-text { font-size: .78rem; color: #4a4538; margin-top: 2px; line-height: 1.5; }
        .pdm-comment-time { font-size: .65rem; color: #9d8c6e; margin-top: 3px; }

        /* Action buttons in modal */
        .pdm-actions { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
        .pdm-action-btn { flex: 1; min-width: 120px; padding: 10px 16px; border-radius: var(--radius-sm); font-family: var(--font-body); font-size: .8rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; display: flex; align-items: center; justify-content: center; gap: 7px; transition: all .2s; text-decoration: none; text-align: center; }
        .pdm-btn-show { background: rgba(40,167,69,.1); border-color: rgba(40,167,69,.3); color: #1a6631; }
        .pdm-btn-show:hover { background: rgba(40,167,69,.18); }
        .pdm-btn-hide { background: rgba(220,53,69,.08); border-color: rgba(220,53,69,.25); color: #a0202e; }
        .pdm-btn-hide:hover { background: rgba(220,53,69,.15); }
        .pdm-btn-delete { background: rgba(126,0,10,.06); border-color: rgba(126,0,10,.2); color: var(--tertiary); }
        .pdm-btn-delete:hover { background: rgba(126,0,10,.12); }
        .pdm-btn-view { background: var(--primary-light); border-color: var(--outline-strong); color: var(--primary); }
        .pdm-btn-view:hover { background: rgba(26,51,37,.1); }

        /* Lightbox */
        .pdm-lightbox { position: fixed; inset: 0; background: rgba(0,0,0,.92); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 1.5rem; cursor: zoom-out; animation: pdm-lb-in .18s ease; }
        @keyframes pdm-lb-in { from { opacity:0; } to { opacity:1; } }
        .pdm-lightbox img { max-width: 100%; max-height: 90vh; border-radius: 10px; box-shadow: 0 24px 80px rgba(0,0,0,.5); object-fit: contain; pointer-events: none; }
        .pdm-lightbox-close { position: absolute; top: 18px; right: 18px; width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s; font-size: 20px; }
        .pdm-lightbox-close:hover { background: rgba(255,255,255,.3); }
    </style>
</head>
<body>
    <?php include './assets/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Product Management</h1>
            <span class="count-badge"><?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?></span>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Product Name</label>
                <input type="text" name="name" placeholder="Search name..." value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>" form="filterForm">
            </div>
            <div class="filter-group">
                <label>Seller</label>
                <input type="text" name="seller" placeholder="Seller name..." value="<?php echo htmlspecialchars($_GET['seller'] ?? ''); ?>" form="filterForm">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" form="filterForm">
                    <option value="">All Status</option>
                    <option value="1" <?php echo (($_GET['status'] ?? '') === '1') ? 'selected' : ''; ?>>Visible</option>
                    <option value="0" <?php echo (($_GET['status'] ?? '') === '0') ? 'selected' : ''; ?>>Hidden</option>
                </select>
            </div>
            <form id="filterForm" action="" method="GET" style="display:contents;">
                <button type="submit" class="btn-filter">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                <a href="admin_product.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><a href="?<?php echo qstr(['order' => $orderBy==='id_asc'?'id_desc':'id_asc']); ?>">ID <?php if(strpos($orderBy,'id')!==false) echo $orderBy==='id_asc'?'↑':'↓'; ?></a></th>
                            <th>Image</th>
                            <th><a href="?<?php echo qstr(['order' => $orderBy==='name_asc'?'name_desc':'name_asc']); ?>">Name <?php if(strpos($orderBy,'name')!==false) echo $orderBy==='name_asc'?'↑':'↓'; ?></a></th>
                            <th><a href="?<?php echo qstr(['order' => $orderBy==='owner_asc'?'owner_desc':'owner_asc']); ?>">Owner <?php if(strpos($orderBy,'owner')!==false) echo $orderBy==='owner_asc'?'↑':'↓'; ?></a></th>
                            <th><a href="?<?php echo qstr(['order' => $orderBy==='price_asc'?'price_desc':'price_asc']); ?>">Price <?php if(strpos($orderBy,'price')!==false) echo $orderBy==='price_asc'?'↑':'↓'; ?></a></th>
                            <th><a href="?<?php echo qstr(['order' => $orderBy==='status_asc'?'status_desc':'status_asc']); ?>">Status <?php if(strpos($orderBy,'status')!==false) echo $orderBy==='status_asc'?'↑':'↓'; ?></a></th>
                            <th>Actions</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="8">
                                <div class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <p>No products found matching your filters.</p>
                                </div>
                            </td></tr>
                        <?php else: foreach ($products as $product): ?>
                            <tr class="product-row" data-pid="<?php echo (int)$product['id']; ?>" onclick="openProductDetail(<?php echo (int)$product['id']; ?>)">
                                <td><?php echo (int)$product['id']; ?></td>
                                <td onclick="event.stopPropagation()">
                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['main_image'] ?? 'default.png'); ?>" class="product-img">
                                </td>
                                <td><span style="font-weight:600;"><?php echo htmlspecialchars($product['name']); ?></span></td>
                                <td><?php echo htmlspecialchars($product['owner_name']); ?></td>
                                <td>$<?php echo number_format($product['prices'], 2); ?></td>
                                <td>
                                    <?php if ($product['showed']): ?>
                                        <span class="badge badge-visible">Visible</span>
                                    <?php else: ?>
                                        <span class="badge badge-hidden">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td onclick="event.stopPropagation()">
                                    <div style="display:flex;gap:6px;">
                                        <a href="../controllers/product.php?action=toggle_visibility&id=<?php echo (int)$product['id']; ?>&status=<?php echo $product['showed'] ? '0' : '1'; ?>"
                                           class="action-btn <?php echo $product['showed'] ? 'btn-hide' : 'btn-show'; ?>">
                                            <?php echo $product['showed'] ? 'Hide' : 'Show'; ?>
                                        </a>
                                        <a href="../controllers/product.php?action=delete&id=<?php echo (int)$product['id']; ?>"
                                           class="action-btn btn-hide"
                                           onclick="return confirm('Delete this product permanently?')">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                                <td><span class="row-hint">View Details →</span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div class="toast <?php echo isset($_GET['success']) ? 'toast-success' : 'toast-error'; ?>">
            <?php echo htmlspecialchars($_GET['success'] ?? $_GET['error'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <!-- ══ PRODUCT DETAIL MODAL ══ -->
    <div id="productDetailModal" class="pdm-overlay" onclick="closePdmModal(event)">
        <div class="pdm-sheet">
            <button class="pdm-close" onclick="closePdmModalBtn()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="pdm-loading" id="pdmLoading">
                <div class="pdm-spinner"></div>
                <span>Loading product…</span>
            </div>
            <div id="pdmContent" style="display:none;"></div>
        </div>
    </div>

    <script>
    const PDM_IMG = '../uploads/products/';
    const PDM_AVATAR = '../uploads/profiles/';
    const avColors = [
        {bg:'#d4f1e4',color:'#0f6e56'},{bg:'#e8e4ff',color:'#534ab7'},
        {bg:'#fde8d8',color:'#993c1d'},{bg:'#fdf0d4',color:'#854f0b'},
        {bg:'#fce4f0',color:'#993556'},{bg:'#dceeff',color:'#185fa5'},
    ];

    // ── Lightbox ──────────────────────────────────────────────────────────────
    function pdmLightbox(src, label) {
        const lb = document.createElement('div');
        lb.className = 'pdm-lightbox';
        lb.innerHTML = `<img src="${src}" alt="${label}"><button class="pdm-lightbox-close" onclick="this.parentElement.remove()">✕</button>`;
        lb.addEventListener('click', e => { if (e.target === lb) lb.remove(); });
        document.body.appendChild(lb);
        const onKey = e => { if (e.key === 'Escape') { lb.remove(); document.removeEventListener('keydown', onKey); } };
        document.addEventListener('keydown', onKey);
    }

    // ── Open modal ────────────────────────────────────────────────────────────
    function openProductDetail(pid) {
        document.querySelectorAll('.product-row').forEach(r => r.classList.remove('row-active'));
        const row = document.querySelector(`.product-row[data-pid="${pid}"]`);
        if (row) row.classList.add('row-active');

        const modal = document.getElementById('productDetailModal');
        document.getElementById('pdmLoading').style.display = 'flex';
        document.getElementById('pdmContent').style.display = 'none';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        fetch(`/e-shops-improved/views/ajax/get_product_detail.php?pid=${pid}`)
            .then(r => { if (!r.ok) throw new Error('Server error ' + r.status); return r.json(); })
            .then(data => { if (data.error) throw new Error(data.error); renderProduct(data); })
            .catch(err => {
                document.getElementById('pdmLoading').innerHTML =
                    `<div style="text-align:center;padding:3rem;color:#a0202e;">
                       <p style="font-size:.82rem;">${esc(err.message)}</p>
                     </div>`;
            });
    }

    // ── Render ────────────────────────────────────────────────────────────────
    function renderProduct(data) {
        const { product: p, stats, comments } = data;

        // Collect all images
        const imgs = [p.main_image, p.image1, p.image2, p.image3, p.image4, p.image5]
            .filter(Boolean)
            .map(i => PDM_IMG + esc(i));

        const mainImgHTML = imgs.length
            ? `<div class="pdm-main-img-wrap" onclick="pdmLightbox('${imgs[0]}','${esc(p.name)}')">
                 <img src="${imgs[0]}" class="pdm-main-img" id="pdmMainImg" alt="${esc(p.name)}">
                 <div class="pdm-img-hint">🔍 Click to enlarge</div>
               </div>
               ${imgs.length > 1 ? `<div class="pdm-thumbs">${imgs.map((src,i) =>
                   `<img src="${src}" class="pdm-thumb${i===0?' active':''}" onclick="pdmSetMain('${src}',this)" alt="img ${i+1}">`
               ).join('')}</div>` : ''}`
            : `<div style="aspect-ratio:16/7;background:#f0ede8;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#9d8c6e;font-size:.82rem;">No images uploaded</div>`;

        // Price
        const price = p.prices ? `$${parseFloat(p.prices).toLocaleString('en-US',{minimumFractionDigits:2})}` : 'Free';
        const discountHTML = p.discounts > 0
            ? `<span class="pdm-discount-badge">-$${parseFloat(p.discounts).toLocaleString()} off</span>
               <span class="pdm-original">$${(parseFloat(p.prices)+parseFloat(p.discounts)).toLocaleString('en-US',{minimumFractionDigits:2})}</span>`
            : '';

        // Visibility
        const visHTML = p.showed == 1
            ? `<span class="pdm-visibility pdm-vis-shown">● Visible</span>`
            : `<span class="pdm-visibility pdm-vis-hidden">● Hidden</span>`;

        // Seller
        const col = avColors[(p.owner_id||0) % avColors.length];
        const initials = (p.owner_name||'?').trim().split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
        const sellerAvatarHTML = p.owner_avatar
            ? `<img src="${PDM_AVATAR}${esc(p.owner_avatar)}" class="pdm-seller-avatar" alt="${esc(p.owner_name)}">`
            : `<div class="pdm-seller-initials" style="background:${col.bg};color:${col.color};">${initials}</div>`;

        // Comments
        const commentsHTML = comments.length === 0
            ? `<p style="font-size:.78rem;color:#9d8c6e;margin:0;">No comments yet.</p>`
            : comments.map(c => {
                const ci = (c.user_name||'?').trim().split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
                return `<div class="pdm-comment">
                    <div class="pdm-comment-avatar">${ci}</div>
                    <div class="pdm-comment-bubble">
                        <div class="pdm-comment-user">${esc(c.user_name)}</div>
                        <div class="pdm-comment-text">${esc(c.content)}</div>
                        <div class="pdm-comment-time">${fmtDate(c.created_at)}</div>
                    </div>
                </div>`;
            }).join('');

        // Toggle action
        const toggleBtn = p.showed == 1
            ? `<a href="../controllers/product.php?action=toggle_visibility&id=${p.id}&status=0" class="pdm-action-btn pdm-btn-hide">🚫 Hide Product</a>`
            : `<a href="../controllers/product.php?action=toggle_visibility&id=${p.id}&status=1" class="pdm-action-btn pdm-btn-show">✓ Make Visible</a>`;

        const html = `
            <div class="pdm-gallery">${mainImgHTML}</div>
            <div class="pdm-body">
                <div class="pdm-title-row">
                    <h2 class="pdm-title">${esc(p.name)}</h2>
                    ${visHTML}
                </div>

                <div class="pdm-price-row">
                    <div class="pdm-price">${price}</div>
                    ${discountHTML}
                </div>

                <div class="pdm-meta-grid">
                    <div class="pdm-meta-cell"><div class="pdm-meta-label">Category</div><div class="pdm-meta-value">${esc(p.category_name||'—')}</div></div>
                    <div class="pdm-meta-cell"><div class="pdm-meta-label">Location</div><div class="pdm-meta-value">${esc(p.location||'—')}</div></div>
                    <div class="pdm-meta-cell"><div class="pdm-meta-label">Posted</div><div class="pdm-meta-value">${fmtDate(p.created_at)}</div></div>
                    <div class="pdm-meta-cell"><div class="pdm-meta-label">Last Updated</div><div class="pdm-meta-value">${fmtDate(p.updated_at)}</div></div>
                </div>

                <div class="pdm-stats">
                    <div class="pdm-stat"><div class="pdm-stat-num">${stats.like_count}</div><div class="pdm-stat-label">Likes</div></div>
                    <div class="pdm-stat"><div class="pdm-stat-num">${stats.comment_count}</div><div class="pdm-stat-label">Comments</div></div>
                    <div class="pdm-stat"><div class="pdm-stat-num">${imgs.length}</div><div class="pdm-stat-label">Images</div></div>
                    <div class="pdm-stat"><div class="pdm-stat-num">#${p.id}</div><div class="pdm-stat-label">Product ID</div></div>
                </div>

                ${p.description ? `<div class="pdm-desc-label">Description</div><div class="pdm-desc">${esc(p.description)}</div>` : ''}

                <div class="pdm-desc-label">Seller</div>
                <div class="pdm-seller">
                    ${sellerAvatarHTML}
                    <div>
                        <div class="pdm-seller-name">${esc(p.owner_name||'Unknown')}</div>
                        <div class="pdm-seller-meta">${esc(p.owner_email||'')}${p.owner_phone1 ? ' · '+esc(p.owner_phone1) : ''}</div>
                    </div>
                    <button class="pdm-view-profile" onclick="closePdmModalBtn(); setTimeout(()=>openDetail(${p.owner_id}),150);">
                        View Profile
                    </button>
                </div>

                ${comments.length > 0 ? `<div class="pdm-desc-label">Recent Comments</div>${commentsHTML}` : ''}

                <div class="pdm-actions">
                    ${toggleBtn}
                    <a href="../controllers/product.php?action=delete&id=${p.id}" class="pdm-action-btn pdm-btn-delete"
                       onclick="return confirm('Delete this product permanently?')">🗑 Delete</a>
                    <a href="product_detail.php?id=${p.id}" class="pdm-action-btn pdm-btn-view" target="_blank">↗ View on Site</a>
                </div>
            </div>`;

        const content = document.getElementById('pdmContent');
        content.innerHTML = html;
        document.getElementById('pdmLoading').style.display = 'none';
        content.style.display = 'block';
    }

    // ── Thumbnail switcher ────────────────────────────────────────────────────
    function pdmSetMain(src, thumb) {
        document.getElementById('pdmMainImg').src = src;
        document.querySelector('.pdm-main-img-wrap').onclick = () => pdmLightbox(src, '');
        document.querySelectorAll('.pdm-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }

    // ── Close ─────────────────────────────────────────────────────────────────
    function closePdmModal(e) { if (e.target === document.getElementById('productDetailModal')) closePdmModalBtn(); }
    function closePdmModalBtn() {
        document.getElementById('productDetailModal').classList.remove('open');
        document.body.style.overflow = '';
        document.querySelectorAll('.product-row').forEach(r => r.classList.remove('row-active'));
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !document.querySelector('.pdm-lightbox')) closePdmModalBtn();
    });

    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
    }
    function fmtDate(str) {
        if (!str || str === '0000-00-00 00:00:00') return '—';
        const d = new Date(str);
        return isNaN(d) ? str : d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
    }
    </script>
</body>
</html>