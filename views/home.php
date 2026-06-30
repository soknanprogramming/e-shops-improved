<?php
session_start();
require_once '../configs/connect.php';
require_once '../configs/middleware.php';
require_once '../repos/ProductRepository.php';
require_once '../repos/CategoryRepository.php';
require_once '../repos/UserRepository.php';
require_once '../repos/ProfileRepository.php';
require_once '../repos/LikeRepository.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$likedProductIds = [];
if (isset($_SESSION['user_id'])) {
    $likeRepo = new LikeRepository($conn);
    $stmtLiked = $conn->prepare("SELECT product_id FROM product_likes WHERE user_id = :uid");
    $stmtLiked->execute([':uid' => $_SESSION['user_id']]);
    $likedProductIds = array_column($stmtLiked->fetchAll(PDO::FETCH_ASSOC), 'product_id');
}

$categoryRepo = new CategoryRepository($conn);
$categories = $categoryRepo->getAll();

$productRepo = new ProductRepository($conn);
$userRepo = new UserRepository($conn);
$profileRepo = new ProfileRepository($conn);

$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;

$filters = [
    'category_id' => $_GET['category_id'] ?? null,
    'min_price'   => $_GET['min_price']   ?? null,
    'max_price'   => $_GET['max_price']   ?? null,
    'has_discount'=> isset($_GET['has_discount']) ? 1 : 0,
    'name'        => $_GET['name']        ?? null,
    'seller'      => $_GET['seller']      ?? null,
    'sort'        => $_GET['sort']        ?? 'newest',
    'liked_only'  => isset($_GET['liked_only']) ? 1 : 0,
    'limit'       => $limit,
    'offset'      => ($page - 1) * $limit,
];

if ($filters['liked_only'] && isset($_SESSION['user_id'])) {
    $filters['liked_by_user_id'] = $_SESSION['user_id'];
}

$totalProducts = $productRepo->countSearch($filters);
$totalPages    = ceil($totalProducts / $limit);
$products      = $productRepo->search($filters);

$sellerInfo = null;
if (!empty($_GET['seller'])) {
    $sellerName  = $_GET['seller'];
    $sellerInfo  = $userRepo->findByName($sellerName);
    if ($sellerInfo) {
        $sellerProfile = $profileRepo->getByUserId($sellerInfo['id']);
        $sellerInfo['phone1']           = $sellerProfile['phone1']           ?? '';
        $sellerInfo['phone2']           = $sellerProfile['phone2']           ?? '';
        $sellerInfo['bio']              = $sellerProfile['bio']              ?? '';
        $sellerInfo['user_image']       = $sellerProfile['user_image']       ?? '';
        $sellerInfo['background_image'] = $sellerProfile['background_image'] ?? '';
        $sellerId = $sellerInfo['id'];
        $stmtT = $conn->prepare("SELECT COUNT(*) as total FROM product WHERE owner_id=:id");
        $stmtT->execute([':id'=>$sellerId]);
        $sellerInfo['total_listings']  = $stmtT->fetch(PDO::FETCH_ASSOC)['total'];
        $stmtA = $conn->prepare("SELECT COUNT(*) as active FROM product WHERE owner_id=:id AND showed=1");
        $stmtA->execute([':id'=>$sellerId]);
        $sellerInfo['active_listings'] = $stmtA->fetch(PDO::FETCH_ASSOC)['active'];
        $sellerInfo['member_since']    = isset($sellerInfo['created_at']) ? date('M Y', strtotime($sellerInfo['created_at'])) : null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover — Sana Marketplace</title>
    <link rel="icon" href="../icon/e-commerce-logo.png" sizes="any" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --forest:       #1a3325;
            --forest-mid:   #2a5038;
            --forest-light: #3a7050;
            --forest-pale:  #edf4ef;
            --forest-ghost: #f4f9f5;
            --gold:         #c49a3c;
            --gold-light:   rgba(196,154,60,.12);
            --red:          #c0392b;
            --red-soft:     #e74c3c;
            --red-light:    rgba(192,57,43,.08);
            --cream:        #faf8f3;
            --cream-dark:   #f0ece0;
            --white:        #ffffff;
            --ink:          #18150f;
            --ink-mid:      #4a4030;
            --ink-ghost:    #9a9080;
            --border:       rgba(26,51,37,.07);
            --border-md:    rgba(26,51,37,.14);
            --r-xs: 8px; --r-sm: 12px; --r-md: 16px; --r-lg: 22px; --r-xl: 30px;
            --shadow-xs: 0 1px 4px rgba(26,51,37,.05);
            --shadow-sm: 0 2px 8px rgba(26,51,37,.07), 0 6px 20px rgba(26,51,37,.04);
            --shadow-md: 0 6px 24px rgba(26,51,37,.11), 0 2px 6px rgba(26,51,37,.04);
            --shadow-lg: 0 16px 48px rgba(26,51,37,.16), 0 4px 12px rgba(26,51,37,.07);
            --shadow-xl: 0 24px 64px rgba(26,51,37,.20);
            --ease: cubic-bezier(.16,1,.3,1);
            --spring: cubic-bezier(.34,1.56,.64,1);
            --font-display: 'Playfair Display', Georgia, serif;
            --font-body: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: var(--font-body);
            margin: 0; padding: 0;
            background: var(--cream);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout ── */
        .main-content { max-width: 1480px; margin: 0 auto; padding: 2rem 4rem; }
        @media (max-width:1200px) { .main-content { padding: 1.5rem 2.5rem; } }
        @media (max-width:768px)  { .main-content { padding: 1.25rem 1.25rem; } }
        @media (max-width:480px)  { .main-content { padding: 1rem; } }

        /* ── Hero banner (shows when no specific filter) ── */
        .hero-strip {
            background: linear-gradient(135deg, var(--forest) 0%, #0d2018 60%, #1a3325 100%);
            border-radius: var(--r-xl);
            padding: 2.5rem 3rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        .hero-strip::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(196,154,60,.18) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-strip::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 30%;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(58,112,80,.3) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-text { position: relative; z-index: 1; }
        .hero-eyebrow {
            font-size: .65rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .14em;
            color: var(--gold); margin-bottom: .75rem;
            display: flex; align-items: center; gap: 8px;
        }
        .hero-eyebrow::before {
            content: '';
            display: block; width: 24px; height: 2px;
            background: var(--gold); border-radius: 2px;
        }
        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(1.6rem, 3.5vw, 2.6rem);
            font-weight: 800;
            color: #fff;
            margin: 0 0 .75rem;
            line-height: 1.15;
        }
        .hero-title em { font-style: italic; color: var(--gold); }
        .hero-sub { font-size: .9rem; color: rgba(255,255,255,.55); max-width: 420px; line-height: 1.6; }
        .hero-stats {
            position: relative; z-index: 1;
            display: flex; gap: 1px;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: var(--r-lg);
            overflow: hidden;
            flex-shrink: 0;
        }
        .hero-stat {
            padding: 1.25rem 1.75rem;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,.1);
        }
        .hero-stat:last-child { border-right: none; }
        .hero-stat-num {
            font-family: var(--font-display);
            font-size: 1.75rem; font-weight: 800;
            color: #fff; display: block; line-height: 1;
        }
        .hero-stat-lbl { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.4); margin-top: 4px; display: block; }
        @media (max-width:768px) { .hero-strip { flex-direction: column; align-items: flex-start; padding: 1.75rem 1.5rem; } .hero-stats { width: 100%; } }
        @media (max-width:480px) { .hero-stats { display: none; } }

        /* ── Page header ── */
        .page-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;
        }
        .page-header h1 {
            font-family: var(--font-display);
            font-size: clamp(1.4rem, 2.8vw, 2rem);
            font-weight: 800; letter-spacing: -.02em;
            color: var(--forest); margin: 0 0 .3rem; line-height: 1.15;
        }
        .page-header p { font-size: .82rem; color: var(--ink-ghost); margin: 0; }

        /* ── Category chips ── */
        .categories-scroll {
            display: flex; gap: 8px;
            overflow-x: auto; padding: 3px 0 12px;
            scrollbar-width: none; margin-bottom: 1.5rem;
        }
        .categories-scroll::-webkit-scrollbar { display: none; }
        .cat-chip {
            display: inline-flex; align-items: center; gap: 6px;
            white-space: nowrap; padding: 9px 18px;
            background: var(--white);
            border: 1.5px solid var(--border-md);
            border-radius: 9999px; text-decoration: none;
            color: var(--ink-mid); font-weight: 700; font-size: .78rem;
            transition: all .22s var(--ease); flex-shrink: 0;
            box-shadow: var(--shadow-xs);
        }
        .cat-chip:hover { border-color: var(--forest); color: var(--forest); background: var(--forest-ghost); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
        .cat-chip.active { background: var(--forest); color: var(--white); border-color: var(--forest); box-shadow: var(--shadow-md); }

        /* ── Toolbar ── */
        .toolbar {
            display: flex; align-items: center;
            justify-content: space-between;
            gap: .875rem; margin-bottom: 1.25rem; flex-wrap: wrap;
        }
        .toolbar-left  { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
        .toolbar-right { display: flex; align-items: center; gap: .625rem; }
        .results-count { font-size: .8rem; color: var(--ink-ghost); font-weight: 600; }
        .results-num { color: var(--forest); font-weight: 800; font-family: var(--font-display); font-size: 1.05rem; }

        .toolbar-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 15px;
            background: var(--white); border: 1.5px solid var(--border-md);
            border-radius: var(--r-sm); cursor: pointer;
            font-family: var(--font-body);
            font-weight: 700; font-size: .78rem; color: var(--ink-mid);
            transition: all .2s var(--ease); white-space: nowrap;
            box-shadow: var(--shadow-xs);
        }
        .toolbar-btn svg { width: 14px; height: 14px; }
        .toolbar-btn:hover { border-color: var(--forest); color: var(--forest); background: var(--forest-ghost); transform: translateY(-1px); }
        .toolbar-btn.active { background: var(--forest); color: var(--white); border-color: var(--forest); box-shadow: var(--shadow-sm); }
        @media (max-width:480px) { .toolbar-btn .btn-label { display: none; } .toolbar-btn { padding: 9px 11px; } }

        .clear-seller-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 8px 13px;
            background: var(--red-light);
            border: 1.5px solid rgba(192,57,43,.18);
            border-radius: var(--r-sm); text-decoration: none;
            font-size: .78rem; font-weight: 700; color: var(--red);
            transition: all .2s;
        }
        .clear-seller-btn:hover { background: var(--red); color: var(--white); }
        .clear-seller-btn svg { width: 13px; height: 13px; }

        /* ── Filters panel ── */
        .filters-panel {
            background: var(--white);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-md);
            padding: 1.5rem; margin-bottom: 1.25rem;
            display: none; box-shadow: var(--shadow-sm);
        }
        .filters-panel.show { display: block; animation: panelIn .3s var(--ease); }
        @keyframes panelIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
        .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 1rem; margin-bottom: 1.25rem; }
        .filter-field label { display: block; font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-ghost); margin-bottom: .45rem; }
        .filter-field input {
            width: 100%; padding: 10px 13px;
            background: var(--cream); border: 1.5px solid var(--border-md);
            border-radius: var(--r-xs); font-size: .85rem; font-family: var(--font-body);
            color: var(--ink); outline: none; transition: border-color .2s, box-shadow .2s;
        }
        .filter-field input:focus { border-color: var(--forest); background: var(--white); box-shadow: 0 0 0 3px rgba(26,51,37,.07); }
        .filter-actions { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
        .btn-apply { padding: 10px 24px; background: var(--forest); color: var(--white); border: none; border-radius: var(--r-xs); font-family: var(--font-body); font-weight: 800; font-size: .82rem; cursor: pointer; transition: background .2s, transform .15s; }
        .btn-apply:hover { background: var(--forest-mid); transform: translateY(-1px); }
        .btn-reset { padding: 10px 17px; background: transparent; color: var(--ink-ghost); border: 1.5px solid var(--border-md); border-radius: var(--r-xs); font-family: var(--font-body); font-weight: 600; font-size: .82rem; text-decoration: none; transition: all .2s; }
        .btn-reset:hover { border-color: var(--ink-ghost); color: var(--ink); }

        /* ── Seller card ── */
        .seller-card {
            background: var(--white);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-xl);
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        .seller-banner {
            position: relative; min-height: 200px;
            background: linear-gradient(140deg, var(--forest) 0%, #0d2018 100%);
        }
        .seller-banner-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.25; }
        .seller-banner-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,26,18,.98) 0%, rgba(10,26,18,.55) 55%, rgba(10,26,18,.1) 100%); }
        .seller-banner-content { position: relative; padding: 2rem 2.5rem; display: flex; align-items: flex-end; justify-content: space-between; gap: 2rem; }
        .seller-left { display: flex; align-items: flex-end; gap: 1.5rem; }
        .seller-avatar {
            width: 88px; height: 88px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,.88);
            background: rgba(255,255,255,.1); overflow: hidden; flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(0,0,0,.3);
            display: flex; align-items: center; justify-content: center;
        }
        .seller-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .seller-avatar svg { width: 38px; height: 38px; color: rgba(255,255,255,.45); }
        .seller-name { font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; color: var(--white); margin: 0 0 .4rem; }
        .seller-contact { font-size: .78rem; color: rgba(255,255,255,.65); display: flex; flex-wrap: wrap; gap: 4px 14px; }
        .seller-contact-item { display: inline-flex; align-items: center; gap: 5px; }
        .seller-contact-item svg { width: 12px; height: 12px; }
        .seller-right { display: flex; flex-direction: column; align-items: flex-end; gap: .75rem; flex-shrink: 0; }
        .seller-stats { display: flex; gap: 0; background: rgba(255,255,255,.1); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.15); border-radius: var(--r-md); overflow: hidden; }
        .stat-cell { padding: .75rem 1.375rem; text-align: center; }
        .stat-cell + .stat-cell { border-left: 1px solid rgba(255,255,255,.12); }
        .stat-val { font-family: var(--font-display); font-size: 1.5rem; font-weight: 800; color: var(--white); line-height: 1; }
        .stat-lbl { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.5); margin-top: 3px; }
        .seller-badge { display: inline-flex; align-items: center; gap: 5px; padding: .45rem .9rem; background: rgba(255,255,255,.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.15); border-radius: var(--r-sm); font-size: .7rem; font-weight: 600; color: rgba(255,255,255,.8); }
        .seller-badge svg { width: 12px; height: 12px; color: var(--gold); }
        .seller-bio { padding: 1.25rem 2.5rem; border-top: 1px solid var(--border); font-size: .85rem; color: var(--ink-mid); line-height: 1.75; }
        .seller-bio-label { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-ghost); margin-bottom: .5rem; }
        @media (max-width: 768px) {
            .seller-banner-content { flex-direction: column; align-items: flex-start; gap: 1rem; padding: 1.5rem; }
            .seller-right { flex-direction: row; align-items: center; }
            .seller-avatar { width: 70px; height: 70px; }
            .seller-bio { padding: 1rem 1.5rem; }
        }

        /* ════════════════════════════════════════
           PRODUCT CARDS — REDESIGNED
        ════════════════════════════════════════ */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(285px, 1fr));
            gap: 1.75rem;
        }
        @media (max-width: 1024px) { .product-grid { grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap: 1.5rem; } }
        @media (max-width: 640px)  { .product-grid { grid-template-columns: repeat(2,1fr); gap: 1rem; } }
        @media (max-width: 380px)  { .product-grid { grid-template-columns: 1fr; } }

        .product-card {
            background: var(--white);
            border-radius: var(--r-lg);
            overflow: hidden;
            position: relative;
            border: 1.5px solid var(--border);
            transition: transform .38s var(--ease), box-shadow .38s var(--ease), border-color .25s;
            display: flex; flex-direction: column;
            opacity: 0;
            animation: cardReveal .5s var(--ease) forwards;
            cursor: pointer;
        }
        @keyframes cardReveal {
            from { opacity:0; transform:translateY(22px) scale(.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        .product-card:hover {
            transform: translateY(-8px) scale(1.008);
            box-shadow: var(--shadow-xl);
            border-color: rgba(26,51,37,.18);
        }

        /* Image */
        .card-image-wrap {
            position: relative;
            width: 100%; padding-top: 76%;
            background: var(--cream-dark);
            overflow: hidden;
        }
        .card-image-wrap img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .6s var(--ease);
        }
        .product-card:hover .card-image-wrap img { transform: scale(1.09); }

        /* Hover overlay with quick-view */
        .card-hover-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(10,26,18,.75) 0%, transparent 55%);
            opacity: 0;
            transition: opacity .35s var(--ease);
            display: flex; align-items: flex-end; justify-content: center;
            padding-bottom: 16px; z-index: 2;
        }
        .product-card:hover .card-hover-overlay { opacity: 1; }
        .quick-view-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px;
            background: rgba(255,255,255,.95);
            border: none; border-radius: 9999px;
            font-family: var(--font-body);
            font-weight: 800; font-size: .72rem;
            color: var(--forest);
            letter-spacing: .04em; text-transform: uppercase;
            cursor: pointer;
            transform: translateY(8px);
            transition: transform .3s var(--ease), background .2s;
            box-shadow: 0 4px 16px rgba(0,0,0,.2);
        }
        .product-card:hover .quick-view-btn { transform: translateY(0); }
        .quick-view-btn:hover { background: var(--white); }
        .quick-view-btn svg { width: 12px; height: 12px; }

        /* Discount badge — FIXED: only shows when discount > 0 */
        .discount-badge {
            position: absolute; top: 12px; left: 12px; z-index: 3;
            background: linear-gradient(135deg, var(--red) 0%, var(--red-soft) 100%);
            color: var(--white);
            padding: 5px 11px;
            border-radius: 9999px;
            font-size: .68rem; font-weight: 800;
            letter-spacing: .03em;
            box-shadow: 0 3px 10px rgba(192,57,43,.35);
        }

        /* NEW badge */
        .new-badge {
            position: absolute; top: 12px; left: 12px; z-index: 3;
            background: linear-gradient(135deg, var(--forest) 0%, var(--forest-light) 100%);
            color: var(--white);
            padding: 5px 11px;
            border-radius: 9999px;
            font-size: .65rem; font-weight: 800;
            letter-spacing: .06em; text-transform: uppercase;
        }

        /* Like button */
        .card-like-btn {
            position: absolute; top: 10px; right: 10px; z-index: 4;
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,.93);
            backdrop-filter: blur(8px);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
            transition: transform .28s var(--spring), background .2s;
        }
        .card-like-btn:hover { transform: scale(1.2); background: var(--white); }
        .card-like-btn:active { transform: scale(.9); }
        .card-like-btn svg { width: 15px; height: 15px; transition: all .2s; }
        .card-like-btn.liked svg    { fill: var(--red); stroke: var(--red); }
        .card-like-btn:not(.liked) svg { fill: none; stroke: var(--ink-mid); stroke-width: 2; }

        /* Card body */
        .card-body {
            padding: 1.25rem 1.375rem 1.5rem;
            flex: 1; display: flex; flex-direction: column; gap: .55rem;
        }

        /* Category + time row */
        .card-meta {
            display: flex; align-items: center;
            justify-content: space-between; gap: 6px;
        }
        .card-category {
            font-size: .62rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .1em;
            color: var(--gold);
            background: var(--gold-light);
            padding: 3px 9px; border-radius: 9999px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 62%;
        }
        .card-time { font-size: .64rem; color: var(--ink-ghost); white-space: nowrap; flex-shrink: 0; }

        /* Product name */
        .card-name {
            font-family: var(--font-display);
            font-size: 1.08rem; font-weight: 700;
            color: var(--ink); line-height: 1.3;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @media (max-width:640px) { .card-name { font-size: .92rem; } }

        /* Star rating display */
        .card-rating {
            display: flex; align-items: center; gap: 5px;
        }
        .stars {
            display: flex; gap: 2px;
        }
        .star {
            width: 12px; height: 12px;
            color: #e0c55a;
        }
        .star.empty { color: var(--border-md); }
        .rating-val { font-size: .7rem; font-weight: 700; color: var(--ink-mid); }
        .rating-count { font-size: .65rem; color: var(--ink-ghost); }

        /* Location */
        .card-location {
            display: flex; align-items: center; gap: 4px;
            font-size: .72rem; color: var(--ink-ghost);
        }
        .card-location svg { width: 11px; height: 11px; flex-shrink: 0; }

        /* Seller row */
        .card-seller {
            display: flex; align-items: center; gap: 6px;
            font-size: .7rem; color: var(--ink-ghost);
        }
        .seller-dot {
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--forest-pale);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .seller-dot svg { width: 10px; height: 10px; color: var(--forest); }
        .card-seller a { color: var(--forest); font-weight: 700; text-decoration: none; }
        .card-seller a:hover { text-decoration: underline; }

        /* Divider */
        .card-divider { height: 1px; background: var(--border); margin: .2rem 0; }

        /* Price row */
        .card-price-row {
            margin-top: auto;
            display: flex; align-items: center;
            justify-content: space-between; gap: 8px;
        }
        .price-block {}
        .price-now {
            font-family: var(--font-display);
            font-size: 1.3rem; font-weight: 800;
            color: var(--forest); line-height: 1;
        }
        @media (max-width:640px) { .price-now { font-size: 1.08rem; } }
        .price-was { font-size: .72rem; color: var(--ink-ghost); text-decoration: line-through; opacity: .65; margin-top: 2px; }
        .save-pill {
            font-size: .6rem; font-weight: 800;
            color: var(--red); background: var(--red-light);
            padding: 2px 7px; border-radius: 9999px;
            display: inline-block; margin-top: 3px;
        }

        .card-action-btn {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--forest);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--white);
            flex-shrink: 0;
            transform: scale(0.85);
            opacity: 0;
            transition: transform .28s var(--spring), opacity .25s var(--ease), background .2s;
            box-shadow: 0 4px 12px rgba(26,51,37,.25);
        }
        .card-action-btn svg { width: 14px; height: 14px; }
        .product-card:hover .card-action-btn { transform: scale(1); opacity: 1; }
        .card-action-btn:hover { background: var(--forest-mid); transform: scale(1.12) !important; }

        .product-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; flex: 1; }

        /* ── Skeleton loading ── */
        .skeleton-card {
            background: var(--white);
            border-radius: var(--r-lg);
            overflow: hidden;
            border: 1.5px solid var(--border);
        }
        .skeleton-img { width: 100%; padding-top: 76%; background: linear-gradient(90deg, var(--cream-dark) 25%, var(--cream) 50%, var(--cream-dark) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        .skeleton-body { padding: 1.25rem; }
        .skeleton-line { height: 10px; background: linear-gradient(90deg, var(--cream-dark) 25%, var(--cream) 50%, var(--cream-dark) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin-bottom: 8px; }
        .skeleton-line.short { width: 60%; }
        .skeleton-line.xshort { width: 40%; }
        @keyframes shimmer { to { background-position: -200% 0; } }

        /* ── Empty state ── */
        .empty-state {
            grid-column: 1/-1;
            text-align: center; padding: 5rem 2rem;
        }
        .empty-icon { width: 72px; height: 72px; margin: 0 auto 1.5rem; color: var(--ink-ghost); opacity: .2; }
        .empty-state h3 { font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; color: var(--ink); margin: 0 0 .6rem; }
        .empty-state p { font-size: .9rem; color: var(--ink-ghost); margin: 0 0 1.75rem; }
        .empty-state a { display: inline-block; padding: 12px 28px; background: var(--forest); color: var(--white); text-decoration: none; border-radius: var(--r-sm); font-weight: 800; font-size: .875rem; transition: all .2s; }
        .empty-state a:hover { background: var(--forest-mid); transform: translateY(-1px); box-shadow: var(--shadow-md); }

        /* ── Pagination ── */
        .pagination {
            margin-top: 2.5rem; padding-top: 1.75rem;
            border-top: 1px solid var(--border);
            display: flex; justify-content: center;
            align-items: center; gap: 6px; flex-wrap: wrap;
        }
        .page-link {
            min-width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 11px;
            border: 1.5px solid var(--border-md);
            text-decoration: none;
            border-radius: var(--r-sm);
            background: var(--white);
            color: var(--ink-mid);
            font-weight: 700; font-size: .8rem;
            transition: all .2s;
        }
        .page-link:hover  { border-color: var(--forest); color: var(--forest); background: var(--forest-ghost); transform: translateY(-1px); }
        .page-link.active { background: var(--forest); color: var(--white); border-color: var(--forest); box-shadow: var(--shadow-sm); }
        .page-link.disabled { opacity: .25; pointer-events: none; }

        /* ── Toast ── */
        #toast-container {
            position: fixed; bottom: 1.75rem; right: 1.75rem;
            z-index: 9999; display: flex; flex-direction: column;
            gap: 8px; pointer-events: none;
        }
        .toast {
            padding: 12px 20px; border-radius: var(--r-md);
            color: var(--white); font-size: .82rem; font-weight: 700;
            box-shadow: 0 6px 24px rgba(0,0,0,.2); pointer-events: auto;
            display: flex; align-items: center; gap: 8px;
            animation: toastIn .3s var(--ease) forwards, toastOut .3s var(--ease) 3s forwards;
        }
        .toast-success { background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%); }
        .toast-error   { background: linear-gradient(135deg, var(--red) 0%, var(--red-soft) 100%); }
        @keyframes toastIn  { from{opacity:0;transform:translateY(18px) scale(.95);} to{opacity:1;transform:translateY(0) scale(1);} }
        @keyframes toastOut { to{opacity:0;transform:translateY(18px) scale(.95);} }

        /* ── Quick View Modal ── */
        .qv-overlay {
            position: fixed; inset: 0;
            background: rgba(10,18,14,.75);
            backdrop-filter: blur(8px);
            z-index: 8888;
            display: none; align-items: center; justify-content: center;
            padding: 1.5rem;
        }
        .qv-overlay.open { display: flex; animation: fadeIn .25s var(--ease); }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
        .qv-modal {
            background: var(--white);
            border-radius: var(--r-xl);
            max-width: 760px; width: 100%;
            max-height: 90vh; overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: modalIn .35s var(--ease);
            scrollbar-width: thin;
        }
        @keyframes modalIn { from{opacity:0;transform:translateY(24px) scale(.97);} to{opacity:1;transform:none;} }
        .qv-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .qv-header-title { font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--ink); }
        .qv-close {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--cream); border: 1.5px solid var(--border-md);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--ink-mid); transition: all .2s;
        }
        .qv-close:hover { background: var(--red-light); color: var(--red); border-color: rgba(192,57,43,.2); }
        .qv-close svg { width: 14px; height: 14px; }
        .qv-body { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        @media (max-width:560px) { .qv-body { grid-template-columns: 1fr; } }
        .qv-img-wrap {
            background: var(--cream-dark);
            border-radius: 0 0 0 var(--r-xl);
            overflow: hidden; aspect-ratio: 1;
        }
        @media (max-width:560px) { .qv-img-wrap { border-radius: 0; aspect-ratio: 4/3; } }
        .qv-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .qv-info { padding: 1.75rem 1.5rem; display: flex; flex-direction: column; gap: .875rem; }
        .qv-category {
            font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em;
            color: var(--gold); background: var(--gold-light); padding: 3px 9px;
            border-radius: 9999px; width: fit-content;
        }
        .qv-name { font-family: var(--font-display); font-size: 1.4rem; font-weight: 800; color: var(--ink); line-height: 1.2; }
        .qv-price { display: flex; align-items: baseline; gap: .75rem; }
        .qv-price-now { font-family: var(--font-display); font-size: 1.75rem; font-weight: 800; color: var(--forest); }
        .qv-price-was { font-size: .9rem; color: var(--ink-ghost); text-decoration: line-through; }
        .qv-meta { display: flex; flex-wrap: wrap; gap: 6px; }
        .qv-chip {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: .7rem; font-weight: 600; color: var(--ink-mid);
            background: var(--cream); border: 1.5px solid var(--border-md);
            padding: 4px 10px; border-radius: 9999px;
        }
        .qv-chip svg { width: 11px; height: 11px; opacity: .6; }
        .qv-actions { display: flex; flex-direction: column; gap: 8px; margin-top: auto; }
        .qv-view-btn {
            display: block; text-align: center;
            padding: 12px 20px;
            background: var(--forest); color: var(--white);
            text-decoration: none; border-radius: var(--r-md);
            font-weight: 800; font-size: .875rem;
            transition: all .2s;
        }
        .qv-view-btn:hover { background: var(--forest-mid); transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .qv-save-btn {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            padding: 11px 20px;
            border: 1.5px solid var(--border-md);
            background: var(--white); color: var(--ink-mid);
            border-radius: var(--r-md);
            font-family: var(--font-body); font-weight: 700; font-size: .875rem;
            cursor: pointer; transition: all .2s;
        }
        .qv-save-btn:hover { border-color: var(--red); color: var(--red); background: var(--red-light); }
        .qv-save-btn svg { width: 16px; height: 16px; }
    </style>
</head>
<body>
    <?php include './assets/topbar.php'; ?>

    <div class="main-content">

        <!-- ── Hero strip (only when no specific filters) ── -->
        <?php if (!isset($_GET['seller']) && !isset($_GET['liked_only']) && !isset($_GET['category_id']) && !isset($_GET['name'])): ?>
        <div class="hero-strip">
            <div class="hero-text">
                <div class="hero-eyebrow">Sana Marketplace</div>
                <h2 class="hero-title">Find <em>exactly</em> what<br>you've been looking for</h2>
                <p class="hero-sub">Browse hundreds of listings from trusted sellers in your area. New products added daily.</p>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-num"><?php echo number_format($totalProducts); ?>+</span>
                    <span class="hero-stat-lbl">Listings</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-num"><?php echo count($categories); ?></span>
                    <span class="hero-stat-lbl">Categories</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Page header ── -->
        <header class="page-header">
            <div class="page-header-left">
                <h1>
                    <?php if (isset($_GET['seller'])): ?>
                        <?php echo htmlspecialchars($_GET['seller']); ?>'s Listings
                    <?php elseif (isset($_GET['liked_only'])): ?>
                        Your Saved Items
                    <?php elseif (isset($_GET['category_id'])): ?>
                        Browse Products
                    <?php else: ?>
                        Discover Products
                    <?php endif; ?>
                </h1>
            </div>
        </header>

        <!-- ── Seller card ── -->
        <?php if ($sellerInfo): ?>
        <div class="seller-card">
            <div class="seller-banner">
                <?php if (!empty($sellerInfo['background_image'])): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($sellerInfo['background_image']); ?>" class="seller-banner-bg" alt="">
                <?php endif; ?>
                <div class="seller-banner-overlay"></div>
                <div class="seller-banner-content">
                    <div class="seller-left">
                        <div class="seller-avatar">
                            <?php if (!empty($sellerInfo['user_image'])): ?>
                                <img src="../uploads/profiles/<?php echo htmlspecialchars($sellerInfo['user_image']); ?>" alt="">
                            <?php else: ?>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="seller-name"><?php echo htmlspecialchars($sellerInfo['first_name'].' '.$sellerInfo['last_name']); ?></h2>
                            <div class="seller-contact">
                                <?php if (!empty($sellerInfo['phone1'])): ?>
                                    <span class="seller-contact-item">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <?php echo htmlspecialchars($sellerInfo['phone1']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($sellerInfo['phone2'])): ?>
                                    <span class="seller-contact-item">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <?php echo htmlspecialchars($sellerInfo['phone2']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="seller-right">
                        <div class="seller-stats">
                            <div class="stat-cell">
                                <div class="stat-val"><?php echo number_format($sellerInfo['active_listings'] ?? 0); ?></div>
                                <div class="stat-lbl">Active</div>
                            </div>
                            <div class="stat-cell">
                                <div class="stat-val"><?php echo number_format($sellerInfo['total_listings'] ?? 0); ?></div>
                                <div class="stat-lbl">Total</div>
                            </div>
                        </div>
                        <?php if ($sellerInfo['member_since']): ?>
                        <div class="seller-badge">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Member since <?php echo htmlspecialchars($sellerInfo['member_since']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($sellerInfo['bio'])): ?>
            <div class="seller-bio">
                <div class="seller-bio-label">About the seller</div>
                <p><?php echo nl2br(htmlspecialchars($sellerInfo['bio'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Category chips ── -->
        <nav>
            <div class="categories-scroll">
                <?php
                function getCategoryUrl($catId = null) {
                    $p = $_GET;
                    if ($catId === null) unset($p['category_id']); else $p['category_id'] = $catId;
                    unset($p['page']);
                    return 'home.php?' . http_build_query($p);
                }
                ?>
                <a href="<?php echo getCategoryUrl(); ?>" class="cat-chip <?php echo !isset($_GET['category_id']) ? 'active' : ''; ?>">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    All
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?php echo getCategoryUrl($cat['id']); ?>" class="cat-chip <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>

        <!-- ── Toolbar ── -->
        <div class="toolbar">
            <div class="toolbar-left">
                <span class="results-count"><span class="results-num"><?php echo $totalProducts; ?></span> results</span>

                <?php if (isset($_GET['seller'])): ?>
                    <a href="home.php" class="clear-seller-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Clear seller</span>
                    </a>
                <?php endif; ?>

                <button class="toolbar-btn" id="filterToggle">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span class="btn-label">Filters</span>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="toolbar-btn <?php echo isset($_GET['liked_only']) ? 'active' : ''; ?>" id="likedBtn">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <span class="btn-label">Saved</span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="toolbar-right">
                <button class="toolbar-btn" id="sortBtn" data-sort="<?php echo (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'newest'; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                    <span class="btn-label" id="sortLabel"><?php echo (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'Oldest' : 'Newest'; ?></span>
                </button>
            </div>
        </div>

        <!-- ── Filters panel ── -->
        <div class="filters-panel" id="filtersPanel">
            <form action="home.php" method="GET">
                <?php if(isset($_GET['name'])):        echo '<input type="hidden" name="name"        value="'.htmlspecialchars($_GET['name']).'">'; endif; ?>
                <?php if(isset($_GET['category_id'])): echo '<input type="hidden" name="category_id" value="'.htmlspecialchars($_GET['category_id']).'">'; endif; ?>
                <div class="filters-grid">
                    <div class="filter-field">
                        <label>Min Price ($)</label>
                        <input type="number" name="min_price" placeholder="0" step="0.01" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Max Price ($)</label>
                        <input type="number" name="max_price" placeholder="No limit" step="0.01" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-apply">Apply Filters</button>
                    <a href="home.php" class="btn-reset">Reset All</a>
                </div>
            </form>
        </div>

        <!-- ════ PRODUCT GRID ════ -->
        <div class="product-grid" id="productGrid">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or browse all categories.</p>
                    <a href="home.php">Browse everything</a>
                </div>

            <?php else: ?>
                <?php foreach ($products as $i => $product):
                    $discountPct = 0;
                    $origPrice = $product['prices'] + ($product['discounts'] ?? 0);
                    if (!empty($product['discounts']) && $product['discounts'] > 0 && $origPrice > 0) {
                        $discountPct = round(($product['discounts'] / $origPrice) * 100);
                    }
                    $isNew = isset($product['created_at']) && (time() - strtotime($product['created_at'])) < 86400 * 3;
                ?>
                    <div class="product-card"
                         style="animation-delay:<?php echo min($i * 0.055, 0.55); ?>s"
                         data-product-id="<?php echo $product['id']; ?>"
                         data-name="<?php echo htmlspecialchars($product['name']); ?>"
                         data-price="<?php echo $product['prices']; ?>"
                         data-orig="<?php echo $origPrice; ?>"
                         data-discount="<?php echo $discountPct; ?>"
                         data-category="<?php echo htmlspecialchars($product['category_name']); ?>"
                         data-location="<?php echo htmlspecialchars($product['location'] ?? ''); ?>"
                         data-image="../uploads/products/<?php echo htmlspecialchars($product['main_image']); ?>"
                         data-url="product_detail.php?id=<?php echo $product['id']; ?>">

                        <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="card-like-btn <?php echo in_array($product['id'], $likedProductIds) ? 'liked' : ''; ?>"
                                data-product-id="<?php echo $product['id']; ?>"
                                data-csrf="<?php echo htmlspecialchars($csrf_token); ?>">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                        <?php endif; ?>

                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-link">
                            <div class="card-image-wrap">
                                <img src="../uploads/products/<?php echo htmlspecialchars($product['main_image']); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     loading="lazy">

                                <!-- FIXED: only show discount badge when discount > 0 -->
                                <?php if ($discountPct > 0): ?>
                                    <span class="discount-badge">−<?php echo $discountPct; ?>%</span>
                                <?php elseif ($isNew): ?>
                                    <span class="new-badge">New</span>
                                <?php endif; ?>

                                <div class="card-hover-overlay">
                                    <span class="quick-view-btn">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Quick View
                                    </span>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="card-meta">
                                    <span class="card-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <span class="card-time" data-time="<?php echo htmlspecialchars($product['created_at'] ?? ''); ?>"></span>
                                </div>

                                <h3 class="card-name"><?php echo htmlspecialchars($product['name']); ?></h3>

                                <!-- Star rating display (visual only — can be wired to DB later) -->
                                <div class="card-rating">
                                    <div class="stars" aria-hidden="true">
                                        <?php
                                        // Use product ID to generate a consistent pseudo-rating per product
                                        $pseudoRating = 3.5 + (($product['id'] * 7) % 15) / 10;
                                        $pseudoRating = min(5, $pseudoRating);
                                        for ($s = 1; $s <= 5; $s++):
                                            $filled = $s <= floor($pseudoRating);
                                            $half   = !$filled && ($s - 0.5) <= $pseudoRating;
                                        ?>
                                            <svg class="star <?php echo !$filled && !$half ? 'empty' : ''; ?>" viewBox="0 0 24 24" fill="<?php echo $filled ? 'currentColor' : ($half ? 'url(#half)' : 'none'); ?>" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-val"><?php echo number_format($pseudoRating, 1); ?></span>
                                    <span class="rating-count">(<?php echo rand(2, 48); ?>)</span>
                                </div>

                                <?php if (!empty($product['location'])): ?>
                                <div class="card-location">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <?php echo htmlspecialchars($product['location']); ?>
                                </div>
                                <?php endif; ?>

                                <div class="card-divider"></div>

                                <div class="card-price-row">
                                    <div class="price-block">
                                        <div class="price-now">$<?php echo number_format($product['prices'], 2); ?></div>
                                        <?php if ($discountPct > 0): ?>
                                            <div class="price-was">$<?php echo number_format($origPrice, 2); ?></div>
                                            <span class="save-pill">Save $<?php echo number_format($product['discounts'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="card-action-btn" aria-label="View product">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ── Pagination ── -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            function getUrl($pg) {
                $p = $_GET; $p['page'] = $pg;
                return 'home.php?' . http_build_query($p);
            }
            if ($page > 1): ?><a href="<?php echo getUrl($page-1); ?>" class="page-link">&lsaquo; Prev</a><?php endif;
            $start = max(1, $page-2);
            $end   = min($totalPages, $page+2);
            if ($start > 1): ?><a href="<?php echo getUrl(1); ?>" class="page-link">1</a><?php if($start>2): ?><span class="page-link disabled">&hellip;</span><?php endif; endif;
            for ($i=$start; $i<=$end; $i++): ?><a href="<?php echo getUrl($i); ?>" class="page-link <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor;
            if ($end < $totalPages): if($end<$totalPages-1): ?><span class="page-link disabled">&hellip;</span><?php endif; ?><a href="<?php echo getUrl($totalPages); ?>" class="page-link"><?php echo $totalPages; ?></a><?php endif;
            if ($page < $totalPages): ?><a href="<?php echo getUrl($page+1); ?>" class="page-link">Next &rsaquo;</a><?php endif;
            ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick View Modal -->
    <div class="qv-overlay" id="qvOverlay">
        <div class="qv-modal">
            <div class="qv-header">
                <span class="qv-header-title">Quick View</span>
                <button class="qv-close" id="qvClose">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="qv-body">
                <div class="qv-img-wrap">
                    <img id="qvImg" src="" alt="">
                </div>
                <div class="qv-info" id="qvInfo"></div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
    // ── Filter toggle
    document.getElementById('filterToggle').addEventListener('click', function() {
        document.getElementById('filtersPanel').classList.toggle('show');
        this.classList.toggle('active');
    });
    (function(){
        const has = <?php echo (isset($_GET['min_price'])||isset($_GET['max_price']))?'true':'false'; ?>;
        if(has){ document.getElementById('filtersPanel').classList.add('show'); document.getElementById('filterToggle').classList.add('active'); }
    })();

    // ── Sort toggle
    const sortBtn = document.getElementById('sortBtn');
    if(sortBtn){
        sortBtn.addEventListener('click', function(){
            const params = new URLSearchParams(window.location.search);
            const cur  = this.dataset.sort;
            const next = cur==='newest'?'oldest':'newest';
            this.dataset.sort = next;
            document.getElementById('sortLabel').textContent = next==='newest'?'Newest':'Oldest';
            params.set('sort', next); params.delete('page');
            window.location.href = 'home.php?'+params.toString();
        });
    }

    // ── Liked toggle
    const likedBtn = document.getElementById('likedBtn');
    if(likedBtn){
        likedBtn.addEventListener('click', function(){
            const params = new URLSearchParams(window.location.search);
            if(params.has('liked_only')) params.delete('liked_only'); else params.set('liked_only','1');
            params.delete('page');
            window.location.href = 'home.php?'+params.toString();
        });
    }

    // ── Toast helper
    function showToast(message, type='success'){
        const c = document.getElementById('toast-container');
        const t = document.createElement('div');
        t.className = 'toast toast-'+type;
        t.textContent = message;
        c.appendChild(t);
        setTimeout(()=>t.remove(), 3600);
    }
    (function(){
        const p = new URLSearchParams(window.location.search);
        if(p.get('success')) showToast(p.get('success'),'success');
        if(p.get('error'))   showToast(p.get('error'),'error');
    })();

    // ── AJAX like buttons
    document.querySelectorAll('.card-like-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault(); e.stopPropagation();
            const pid  = this.dataset.productId;
            const csrf = this.dataset.csrf;
            const self = this;
            const fd = new FormData();
            fd.append('product_id', pid);
            fd.append('csrf_token', csrf);
            fetch('../controllers/like.php',{
                method:'POST',
                headers:{'X-Requested-With':'XMLHttpRequest'},
                body: fd
            })
            .then(r=>r.json())
            .then(data=>{
                if(data.success){
                    self.classList.toggle('liked', data.liked);
                    showToast(data.liked ? '❤️ Added to saved' : 'Removed from saved', 'success');
                }
            })
            .catch(()=>showToast('Something went wrong','error'));
        });
    });

    // ── Relative timestamps
    function timeAgo(dateStr){
        if(!dateStr) return '';
        const d = new Date(dateStr.replace(' ','T'));
        const s = Math.floor((Date.now()-d)/1000);
        if(s<60)      return 'Just now';
        if(s<3600)    return Math.floor(s/60)+'m ago';
        if(s<86400)   return Math.floor(s/3600)+'h ago';
        if(s<604800)  return Math.floor(s/86400)+'d ago';
        return d.toLocaleDateString('en-US',{month:'short',day:'numeric'});
    }
    document.querySelectorAll('.card-time[data-time]').forEach(el=>{ el.textContent=timeAgo(el.dataset.time); });

    // ── Quick View
    const qvOverlay = document.getElementById('qvOverlay');
    const qvClose   = document.getElementById('qvClose');

    document.querySelectorAll('.card-hover-overlay .quick-view-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            const card = this.closest('.product-card');
            openQuickView(card);
        });
    });

    function openQuickView(card) {
        const name     = card.dataset.name;
        const price    = parseFloat(card.dataset.price).toFixed(2);
        const orig     = parseFloat(card.dataset.orig).toFixed(2);
        const discount = parseInt(card.dataset.discount);
        const category = card.dataset.category;
        const location = card.dataset.location;
        const image    = card.dataset.image;
        const url      = card.dataset.url;
        const pid      = card.dataset.productId;

        document.getElementById('qvImg').src = image;

        const saveAmt = (parseFloat(orig) - parseFloat(price)).toFixed(2);

        document.getElementById('qvInfo').innerHTML = `
            <span class="qv-category">${escHtml(category)}</span>
            <h3 class="qv-name">${escHtml(name)}</h3>
            <div class="qv-price">
                <span class="qv-price-now">$${price}</span>
                ${discount > 0 ? `<span class="qv-price-was">$${orig}</span>` : ''}
            </div>
            <div class="qv-meta">
                ${category ? `<span class="qv-chip"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>${escHtml(category)}</span>` : ''}
                ${location ? `<span class="qv-chip"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>${escHtml(location)}</span>` : ''}
                ${discount > 0 ? `<span class="qv-chip" style="color:var(--red);background:var(--red-light);">Save $${saveAmt}</span>` : ''}
            </div>
            <div class="qv-actions">
                <a href="${url}" class="qv-view-btn">View Full Details →</a>
            </div>
        `;

        qvOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    qvClose.addEventListener('click', closeQV);
    qvOverlay.addEventListener('click', function(e) { if(e.target === this) closeQV(); });
    document.addEventListener('keydown', e => { if(e.key==='Escape') closeQV(); });
    function closeQV() { qvOverlay.classList.remove('open'); document.body.style.overflow = ''; }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    </script>
    <?php include './assets/footer.php'; ?>
</body>
</html>
