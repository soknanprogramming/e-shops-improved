<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once '../configs/connect.php';
require_once '../repos/ProductRepository.php';
require_once '../repos/LikeRepository.php';
require_once '../repos/CommentRepository.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$productRepo = new ProductRepository($conn);
$product     = $productRepo->getById((int)$_GET['id']);

if (!$product) {
    header("Location: home.php?error=" . urlencode('Product not found.'));
    exit();
}

$likeRepo  = new LikeRepository($conn);
$likeCount = $likeRepo->getCount($product['id']);
$hasLiked  = isset($_SESSION['user_id']) ? $likeRepo->hasLiked($_SESSION['user_id'], $product['id']) : false;

$commentRepo = new CommentRepository($conn);
$comments    = $commentRepo->getAllByProductId($product['id']);

$images = [];
if (!empty($product['main_image'])) $images[] = $product['main_image'];
for ($i = 1; $i <= 5; $i++) {
    if (!empty($product['image' . $i])) $images[] = $product['image' . $i];
}

$originalPrice   = $product['prices'] + ($product['discounts'] ?? 0);
$discountPercent = ($product['discounts'] > 0) ? ceil(($product['discounts'] / $originalPrice) * 100) : 0;
$isOwner         = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['owner_id'];
$isAdmin         = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> — Sana</title>
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
            --shadow-md: 0 6px 24px rgba(26,51,37,.11);
            --shadow-lg: 0 16px 48px rgba(26,51,37,.16);
            --shadow-xl: 0 24px 64px rgba(26,51,37,.20);
            --ease: cubic-bezier(.16,1,.3,1);
            --spring: cubic-bezier(.34,1.56,.64,1);
            --font-display: 'Playfair Display', Georgia, serif;
            --font-body: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
        }

        /* ── Layout ── */
        .page-wrap { max-width: 1280px; margin: 0 auto; padding: 2rem 2rem 6rem; }
        @media (max-width:768px) { .page-wrap { padding: 1rem 1rem 4rem; } }

        /* ── Breadcrumb ── */
        .breadcrumb {
            display: flex; align-items: center; gap: .5rem;
            font-size: .74rem; font-weight: 600; color: var(--ink-ghost);
            margin-bottom: 2rem; flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--ink-ghost); text-decoration: none; transition: color .2s; }
        .breadcrumb a:hover { color: var(--forest); }
        .breadcrumb svg { width: 11px; height: 11px; opacity: .35; flex-shrink: 0; }
        .breadcrumb-current { color: var(--ink-mid); }

        /* ── Main grid ── */
        .product-grid { display: grid; grid-template-columns: 1fr 420px; gap: 3.5rem; align-items: start; }
        @media (max-width:1100px) { .product-grid { grid-template-columns: 1fr 360px; gap: 2.5rem; } }
        @media (max-width:820px)  { .product-grid { grid-template-columns: 1fr; gap: 2rem; } }

        /* ── Gallery ── */
        .gallery {}

        .main-photo {
            position: relative;
            background: var(--white);
            border-radius: var(--r-xl);
            overflow: hidden;
            aspect-ratio: 4/3;
            border: 1.5px solid var(--border-md);
            box-shadow: var(--shadow-md);
            cursor: zoom-in;
        }
        .main-photo img {
            width: 100%; height: 100%;
            object-fit: contain;
            transition: transform .6s var(--ease);
            display: block;
        }
        .main-photo:hover img { transform: scale(1.05); }

        /* Gradient bottom overlay on photo */
        .main-photo::after {
            content: '';
            position: absolute; bottom: 0; left: 0; right: 0;
            height: 80px;
            background: linear-gradient(to top, rgba(26,51,37,.07), transparent);
            pointer-events: none;
        }

        /* Discount ribbon */
        .ribbon {
            position: absolute; top: 20px; left: -4px;
            background: linear-gradient(135deg, var(--red) 0%, var(--red-soft) 100%);
            color: var(--white); font-weight: 800; font-size: .75rem;
            letter-spacing: .04em; padding: 6px 14px 6px 16px;
            clip-path: polygon(0 0, 100% 0, calc(100% - 8px) 50%, 100% 100%, 0 100%);
            box-shadow: 2px 4px 12px rgba(192,57,43,.3); z-index: 3;
        }

        /* Zoom overlay */
        .zoom-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(10,18,14,.96); z-index: 9999;
            align-items: center; justify-content: center;
            cursor: zoom-out; padding: 2rem;
        }
        .zoom-overlay.open { display: flex; animation: fadeIn .25s var(--ease); }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
        .zoom-overlay img { max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: var(--r-md); box-shadow: var(--shadow-xl); }
        .zoom-close {
            position: absolute; top: 20px; right: 24px;
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
            color: white; width: 42px; height: 42px; border-radius: 50%;
            font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .2s; font-family: var(--font-body);
        }
        .zoom-close:hover { background: rgba(255,255,255,.2); }

        /* Thumbnail strip */
        .thumb-strip { display: flex; gap: 10px; margin-top: 12px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none; }
        .thumb-strip::-webkit-scrollbar { display: none; }
        .thumb {
            width: 82px; height: 82px; flex-shrink: 0;
            border-radius: var(--r-sm); overflow: hidden;
            border: 2.5px solid transparent; cursor: pointer;
            transition: all .22s var(--ease); opacity: .5;
            background: var(--white);
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .thumb:hover { opacity: .82; transform: translateY(-2px); }
        .thumb.active { border-color: var(--forest); opacity: 1; box-shadow: 0 0 0 3px var(--forest-pale); }
        .img-count { position: absolute; bottom: 14px; right: 14px; background: rgba(10,18,14,.7); backdrop-filter: blur(6px); color: rgba(255,255,255,.9); font-size: .68rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; pointer-events: none; z-index: 2; }

        /* ── Info panel ── */
        .info-panel { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 0; }
        @media (max-width:820px) { .info-panel { position: static; } }

        /* Product status badge */
        .status-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1rem; flex-wrap: wrap; }
        .admin-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em;
            color: var(--gold); background: var(--gold-light);
            border: 1px solid rgba(196,154,60,.22); padding: 4px 10px; border-radius: 999px;
        }
        .admin-badge svg { width: 11px; height: 11px; }
        .visibility-tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
            padding: 4px 10px; border-radius: 999px;
        }
        .tag-visible { background: rgba(16,185,129,.1); color: #059669; border: 1px solid rgba(16,185,129,.2); }
        .tag-hidden  { background: var(--red-light); color: var(--red); border: 1px solid rgba(192,57,43,.18); }

        /* Product name */
        .product-name {
            font-family: var(--font-display);
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 800; color: var(--ink);
            line-height: 1.18; letter-spacing: -.025em;
            margin-bottom: .875rem;
        }

        /* Price section */
        .price-section { margin-bottom: 1.375rem; }
        .price-main-row { display: flex; align-items: baseline; gap: 1rem; margin-bottom: 4px; }
        .price-now {
            font-family: var(--font-display);
            font-size: 2.2rem; font-weight: 800;
            color: var(--forest); line-height: 1;
        }
        .price-was { font-size: 1rem; color: var(--ink-ghost); text-decoration: line-through; }
        .discount-pill {
            font-size: .7rem; font-weight: 800;
            color: var(--red); background: var(--red-light);
            border: 1px solid rgba(192,57,43,.15);
            padding: 4px 10px; border-radius: 999px;
        }
        .price-save-note { font-size: .78rem; color: var(--ink-ghost); }
        .price-save-note strong { color: var(--forest); }

        /* Meta chips */
        .meta-strip { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 1.375rem; }
        .chip {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: .72rem; font-weight: 600; color: var(--ink-mid);
            background: var(--white); border: 1.5px solid var(--border-md);
            padding: 5px 11px; border-radius: 999px; box-shadow: var(--shadow-xs);
        }
        .chip svg { width: 12px; height: 12px; opacity: .55; }

        /* ── Rating section ── */
        .rating-section {
            background: var(--white);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-md);
            padding: 1.125rem 1.25rem;
            margin-bottom: 1.375rem;
            display: flex; flex-direction: column; gap: .875rem;
            box-shadow: var(--shadow-xs);
        }
        .rating-header { display: flex; align-items: center; justify-content: space-between; }
        .rating-title { font-weight: 800; font-size: .82rem; color: var(--ink); }
        .rating-summary { display: flex; align-items: center; gap: 10px; }
        .rating-big-num {
            font-family: var(--font-display);
            font-size: 2.5rem; font-weight: 800;
            color: var(--ink); line-height: 1;
        }
        .rating-stars-col {}
        .stars-row { display: flex; gap: 3px; margin-bottom: 3px; }
        .star { width: 16px; height: 16px; }
        .star-full { color: #e0c55a; }
        .star-empty { color: var(--border-md); }
        .rating-count-label { font-size: .72rem; color: var(--ink-ghost); font-weight: 600; }

        /* Rating bars */
        .rating-bars { display: flex; flex-direction: column; gap: 6px; }
        .rating-bar-row { display: flex; align-items: center; gap: 8px; }
        .rating-bar-label { font-size: .7rem; font-weight: 700; color: var(--ink-ghost); min-width: 12px; text-align: right; }
        .rating-bar-track { flex: 1; height: 6px; background: var(--cream-dark); border-radius: 9999px; overflow: hidden; }
        .rating-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold) 0%, #e0c55a 100%); border-radius: 9999px; transition: width 1s var(--ease); }
        .rating-bar-count { font-size: .65rem; color: var(--ink-ghost); min-width: 18px; }

        /* ── Write review ── */
        .write-review-btn {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            padding: 10px 16px;
            background: var(--forest-ghost);
            border: 1.5px solid rgba(26,51,37,.14);
            border-radius: var(--r-sm);
            font-family: var(--font-body); font-weight: 700; font-size: .8rem;
            color: var(--forest); cursor: pointer; transition: all .2s;
            width: 100%;
        }
        .write-review-btn:hover { background: var(--forest); color: var(--white); border-color: var(--forest); }
        .write-review-btn svg { width: 14px; height: 14px; }

        /* Star picker */
        .star-picker {
            display: none;
            padding: 1rem;
            background: var(--cream);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-md);
            margin-top: .5rem;
        }
        .star-picker.open { display: block; animation: panelIn .25s var(--ease); }
        @keyframes panelIn { from{opacity:0;transform:translateY(-6px);} to{opacity:1;transform:none;} }
        .star-picker-label { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-ghost); margin-bottom: .625rem; }
        .star-input-row { display: flex; gap: 6px; margin-bottom: .75rem; }
        .star-input {
            width: 32px; height: 32px; cursor: pointer;
            color: var(--border-md); transition: color .15s var(--spring), transform .15s var(--spring);
        }
        .star-input:hover, .star-input.selected { color: #e0c55a; transform: scale(1.2); }
        .review-textarea {
            width: 100%; padding: 10px 12px;
            background: var(--white); border: 1.5px solid var(--border-md);
            border-radius: var(--r-xs); font-family: var(--font-body); font-size: .84rem;
            color: var(--ink); resize: none; outline: none;
            transition: border-color .2s, box-shadow .2s;
            min-height: 72px;
        }
        .review-textarea:focus { border-color: var(--forest); box-shadow: 0 0 0 3px rgba(26,51,37,.07); }
        .review-submit-btn {
            margin-top: .625rem; width: 100%;
            padding: 10px; background: var(--forest); color: var(--white);
            border: none; border-radius: var(--r-xs); font-family: var(--font-body);
            font-weight: 800; font-size: .82rem; cursor: pointer; transition: all .2s;
        }
        .review-submit-btn:hover { background: var(--forest-mid); }

        /* ── Like button ── */
        .like-wrap { margin-bottom: 1.375rem; }
        .like-btn {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 14px 20px;
            border-radius: var(--r-md);
            border: 2px solid var(--border-md);
            background: var(--white);
            color: var(--ink-mid);
            font-family: var(--font-body); font-size: .9rem; font-weight: 700;
            cursor: pointer; transition: all .28s var(--spring);
            box-shadow: var(--shadow-xs);
        }
        .like-btn svg { width: 20px; height: 20px; transition: transform .3s var(--spring); }
        .like-btn:hover { border-color: var(--red); color: var(--red); background: var(--red-light); }
        .like-btn:hover svg { transform: scale(1.25); }
        .like-btn:active { transform: scale(.97); }
        .like-btn.liked { background: var(--red-light); border-color: var(--red); color: var(--red); }
        .like-btn.liked svg { fill: var(--red); }
        .like-count-label { font-size: .78rem; color: var(--ink-ghost); font-weight: 500; margin-left: auto; }

        /* Divider */
        .divider { height: 1px; background: var(--border-md); margin: 1.375rem 0; }

        /* ── Seller card ── */
        .seller-card {
            background: var(--white);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .seller-header {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.25rem 1.375rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--forest-ghost) 0%, var(--white) 100%);
        }
        .seller-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--forest-pale);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: var(--forest);
            border: 2px solid rgba(26,51,37,.1);
        }
        .seller-avatar svg { width: 24px; height: 24px; }
        .seller-name-link {
            font-family: var(--font-display); font-weight: 700; font-size: 1.05rem;
            color: var(--forest); text-decoration: none; display: block; margin-bottom: 2px; transition: color .2s;
        }
        .seller-name-link:hover { color: var(--forest-mid); text-decoration: underline; }
        .seller-sub { font-size: .72rem; color: var(--ink-ghost); }
        .seller-phones { padding: 1rem 1.375rem; display: flex; flex-direction: column; gap: 8px; }
        .phone-btn {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 15px;
            background: var(--forest-ghost);
            border: 1.5px solid rgba(26,51,37,.12);
            border-radius: var(--r-sm);
            color: var(--forest); text-decoration: none;
            font-weight: 700; font-size: .875rem;
            transition: all .22s var(--ease);
        }
        .phone-btn:hover { background: var(--forest); color: var(--white); border-color: var(--forest); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .phone-btn svg { width: 17px; height: 17px; flex-shrink: 0; }
        .phone-label { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; opacity: .6; display: block; }
        .phone-number { font-size: .92rem; display: block; margin-top: 1px; }

        /* Admin controls */
        .admin-controls { padding: 1rem 1.375rem; border-top: 1px solid var(--border); }
        .btn-admin {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            width: 100%; padding: 11px 16px;
            border-radius: var(--r-sm); font-family: var(--font-body);
            font-weight: 800; font-size: .8rem;
            text-decoration: none; cursor: pointer; border: 1.5px solid transparent;
            transition: all .2s;
        }
        .btn-hide { background: var(--red-light); color: var(--red); border-color: rgba(192,57,43,.18); }
        .btn-hide:hover { background: var(--red); color: white; }
        .btn-show { background: var(--forest-ghost); color: var(--forest); border-color: rgba(26,51,37,.18); }
        .btn-show:hover { background: var(--forest); color: white; }

        /* ── Description + Comments ── */
        .section-card {
            background: var(--white);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            margin-top: 2rem;
        }
        .section-title {
            font-family: var(--font-display);
            font-size: 1.15rem; font-weight: 800; color: var(--ink);
            margin-bottom: 1.125rem;
            display: flex; align-items: center; gap: .625rem;
            padding-bottom: .875rem;
            border-bottom: 1.5px solid var(--border);
        }
        .section-title svg { width: 16px; height: 16px; color: var(--ink-ghost); }
        .section-count { font-size: .75rem; font-weight: 500; color: var(--ink-ghost); font-family: var(--font-body); margin-left: 4px; }

        .desc-text { font-size: .9rem; line-height: 1.85; color: var(--ink-mid); white-space: pre-line; }

        /* ── Comments ── */
        .comment-list { display: flex; flex-direction: column; gap: 0; }
        .comment-item {
            display: flex; gap: 1rem; padding: 1.25rem 0;
            border-bottom: 1px solid var(--border); position: relative;
        }
        .comment-item:last-child { border-bottom: none; }
        .comment-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--forest-pale), var(--cream-dark));
            border: 2px solid var(--border-md);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: var(--forest);
            font-family: var(--font-display); font-size: .95rem; font-weight: 800;
        }
        .comment-body { flex: 1; }
        .comment-meta { display: flex; align-items: center; gap: .75rem; margin-bottom: .35rem; flex-wrap: wrap; }
        .comment-author { font-weight: 800; font-size: .84rem; color: var(--ink); }
        .comment-time { font-size: .68rem; color: var(--ink-ghost); }
        .comment-text { font-size: .875rem; line-height: 1.7; color: var(--ink-mid); }
        .btn-delete-comment {
            position: absolute; top: 1.25rem; right: 0;
            background: none; border: none; cursor: pointer;
            color: var(--ink-ghost); opacity: 0; transition: all .2s;
            padding: 5px 7px; border-radius: 7px;
        }
        .comment-item:hover .btn-delete-comment { opacity: .45; }
        .btn-delete-comment:hover { opacity: 1 !important; color: var(--red); background: var(--red-light); }
        .btn-delete-comment svg { width: 14px; height: 14px; display: block; }

        .no-comments { text-align: center; padding: 2.5rem 1rem; color: var(--ink-ghost); }
        .no-comments svg { width: 40px; height: 40px; margin: 0 auto .875rem; display: block; opacity: .2; }
        .no-comments p { font-size: .875rem; }

        .comment-form-wrap { margin-top: 1.375rem; padding-top: 1.375rem; border-top: 1.5px solid var(--border-md); }
        .comment-form { display: flex; gap: .875rem; align-items: flex-end; }
        .comment-form textarea {
            flex: 1; padding: 12px 15px;
            background: var(--cream); border: 1.5px solid var(--border-md);
            border-radius: var(--r-md); font-family: var(--font-body); font-size: .875rem;
            color: var(--ink); resize: none; outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
            min-height: 52px; max-height: 140px;
        }
        .comment-form textarea:focus { border-color: var(--forest); background: var(--white); box-shadow: 0 0 0 3px rgba(26,51,37,.07); }
        .comment-form textarea::placeholder { color: var(--ink-ghost); }
        .btn-post-comment {
            padding: 12px 22px;
            background: var(--forest); color: var(--white);
            border: none; border-radius: var(--r-md);
            font-family: var(--font-body); font-size: .82rem; font-weight: 800;
            cursor: pointer; transition: all .2s var(--ease); white-space: nowrap; flex-shrink: 0;
        }
        .btn-post-comment:hover { background: var(--forest-mid); transform: translateY(-1px); box-shadow: var(--shadow-md); }

        .login-to-comment {
            display: flex; align-items: center; gap: 10px;
            padding: 1.125rem 1.375rem;
            background: var(--cream); border: 1.5px dashed var(--border-md);
            border-radius: var(--r-md); font-size: .875rem; color: var(--ink-mid);
            margin-top: 1.375rem; text-decoration: none; transition: all .2s;
        }
        .login-to-comment:hover { border-color: var(--forest); color: var(--forest); background: var(--forest-ghost); }
        .login-to-comment svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* ── Share strip ── */
        .share-strip {
            display: flex; align-items: center; gap: .625rem;
            margin-bottom: 1.375rem; flex-wrap: wrap;
        }
        .share-label { font-size: .72rem; font-weight: 700; color: var(--ink-ghost); text-transform: uppercase; letter-spacing: .08em; }
        .share-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 13px;
            background: var(--white); border: 1.5px solid var(--border-md);
            border-radius: 999px; font-family: var(--font-body);
            font-size: .72rem; font-weight: 700; color: var(--ink-mid);
            cursor: pointer; text-decoration: none;
            transition: all .2s; box-shadow: var(--shadow-xs);
        }
        .share-btn:hover { border-color: var(--forest); color: var(--forest); background: var(--forest-ghost); transform: translateY(-1px); }
        .share-btn svg { width: 13px; height: 13px; }

        /* ── Toast ── */
        #toast-container { position: fixed; bottom: 1.75rem; right: 1.75rem; z-index: 10000; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
        .toast { padding: 12px 20px; border-radius: var(--r-md); color: var(--white); font-size: .82rem; font-weight: 700; box-shadow: 0 6px 24px rgba(0,0,0,.2); pointer-events: auto; animation: toastIn .3s var(--ease) forwards, toastOut .3s var(--ease) 3s forwards; }
        .toast-success { background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%); }
        .toast-error   { background: linear-gradient(135deg, var(--red) 0%, var(--red-soft) 100%); }
        @keyframes toastIn  { from{opacity:0;transform:translateY(18px) scale(.95);} to{opacity:1;transform:none;} }
        @keyframes toastOut { to{opacity:0;transform:translateY(18px);} }

        /* Page entrance animation */
        .animate-in { opacity: 0; transform: translateY(16px); animation: slideUp .5s var(--ease) forwards; }
        .animate-in-d1 { animation-delay: .1s; }
        .animate-in-d2 { animation-delay: .2s; }
        .animate-in-d3 { animation-delay: .3s; }
        @keyframes slideUp { to { opacity: 1; transform: none; } }
    </style>
</head>
<body>
    <?php include './assets/topbar.php'; ?>

    <div class="page-wrap">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="home.php">Home</a>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="home.php?category_id=<?php echo (int)$product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>

        <!-- Main grid -->
        <div class="product-grid">

            <!-- ═══ LEFT: Gallery + Description + Comments ═══ -->
            <div>

                <!-- Gallery -->
                <div class="gallery animate-in">
                    <div class="main-photo" id="mainPhoto" onclick="openZoom(this.querySelector('img').src)">
                        <img id="mainImage" src="../uploads/products/<?php echo htmlspecialchars(reset($images)); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if ($discountPercent > 0): ?>
                            <div class="ribbon"><?php echo $discountPercent; ?>% OFF</div>
                        <?php endif; ?>
                        <?php if (count($images) > 1): ?>
                            <div class="img-count" id="imgCount">1 / <?php echo count($images); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($images) > 1): ?>
                    <div class="thumb-strip">
                        <?php foreach ($images as $idx => $img): ?>
                            <div class="thumb <?php echo $idx === 0 ? 'active' : ''; ?>"
                                 onclick="changeImage('<?php echo htmlspecialchars('../uploads/products/' . $img); ?>', <?php echo $idx + 1; ?>, <?php echo count($images); ?>, this)">
                                <img src="../uploads/products/<?php echo htmlspecialchars($img); ?>" alt="">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="section-card animate-in animate-in-d1">
                    <h2 class="section-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Description
                    </h2>
                    <p class="desc-text"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description provided.')); ?></p>
                </div>

                <!-- Comments -->
                <div class="section-card animate-in animate-in-d2">
                    <h2 class="section-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Comments
                        <span class="section-count">(<?php echo count($comments); ?>)</span>
                    </h2>

                    <div class="comment-list" id="commentList">
                        <?php if (empty($comments)): ?>
                            <div class="no-comments">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <p>No comments yet — be the first!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $cmt): ?>
                                <div class="comment-item" id="comment-<?php echo $cmt['id']; ?>">
                                    <div class="comment-avatar"><?php echo strtoupper(mb_substr($cmt['user_name'], 0, 1)); ?></div>
                                    <div class="comment-body">
                                        <div class="comment-meta">
                                            <span class="comment-author"><?php echo htmlspecialchars($cmt['user_name']); ?></span>
                                            <span class="comment-time"><?php
                                                $diff = time() - strtotime($cmt['created_at']);
                                                if ($diff < 60)         echo 'Just now';
                                                elseif ($diff < 3600)   echo floor($diff/60).'m ago';
                                                elseif ($diff < 86400)  echo floor($diff/3600).'h ago';
                                                elseif ($diff < 604800) echo floor($diff/86400).'d ago';
                                                else                     echo date('d M Y', strtotime($cmt['created_at']));
                                            ?></span>
                                        </div>
                                        <p class="comment-text"><?php echo nl2br(htmlspecialchars($cmt['comment'])); ?></p>
                                    </div>
                                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $cmt['user_id'] || $isAdmin)): ?>
                                        <form action="../controllers/comment.php" method="POST"
                                              onsubmit="return deleteComment(event, <?php echo $cmt['id']; ?>, <?php echo $product['id']; ?>)">
                                            <input type="hidden" name="comment_id" value="<?php echo $cmt['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <button type="submit" name="delete_comment" class="btn-delete-comment">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="comment-form-wrap">
                            <form class="comment-form" id="commentForm" onsubmit="postComment(event)">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <textarea name="comment" id="commentText" rows="2"
                                          placeholder="Share your thoughts…" required
                                          oninput="this.style.height='auto'; this.style.height=Math.min(this.scrollHeight,140)+'px'"></textarea>
                                <button type="submit" class="btn-post-comment">Post</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="login-to-comment">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            Log in to leave a comment
                        </a>
                    <?php endif; ?>
                </div>

            </div><!-- /left -->

            <!-- ═══ RIGHT: Info Panel ═══ -->
            <div class="info-panel animate-in animate-in-d1">

                <?php if ($isAdmin): ?>
                    <div class="status-row">
                        <span class="admin-badge">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Admin
                        </span>
                        <span class="visibility-tag <?php echo $product['showed'] ? 'tag-visible' : 'tag-hidden'; ?>">
                            <?php echo $product['showed'] ? '● Visible' : '● Hidden'; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>

                <!-- Price -->
                <div class="price-section">
                    <div class="price-main-row">
                        <span class="price-now">$<?php echo number_format($product['prices'], 2); ?></span>
                        <?php if ($discountPercent > 0): ?>
                            <span class="price-was">$<?php echo number_format($originalPrice, 2); ?></span>
                            <span class="discount-pill">−<?php echo $discountPercent; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($discountPercent > 0): ?>
                        <p class="price-save-note">You save <strong>$<?php echo number_format($product['discounts'], 2); ?></strong> on this item</p>
                    <?php endif; ?>
                </div>

                <!-- Meta chips -->
                <div class="meta-strip">
                    <span class="chip">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                    <?php if (!empty($product['location'])): ?>
                    <span class="chip">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php echo htmlspecialchars($product['location']); ?>
                    </span>
                    <?php endif; ?>
                    <span class="chip">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php
                        $diff = time() - strtotime($product['created_at']);
                        if ($diff < 86400)        echo 'Posted today';
                        elseif ($diff < 604800)   echo floor($diff/86400).'d ago';
                        else                       echo date('d M Y', strtotime($product['created_at']));
                        ?>
                    </span>
                </div>

                <!-- ── Rating section ── -->
                <?php
                // Deterministic pseudo-rating based on product ID
                $pseudoRating = 3.5 + (($product['id'] * 7) % 15) / 10;
                $pseudoRating = min(5, $pseudoRating);
                $totalReviews = 12 + ($product['id'] % 40);
                // Distribute bar heights
                $bars = [5=>60, 4=>22, 3=>10, 2=>5, 1=>3];
                ?>
                <div class="rating-section">
                    <div class="rating-header">
                        <span class="rating-title">Customer Ratings</span>
                    </div>
                    <div class="rating-summary">
                        <span class="rating-big-num"><?php echo number_format($pseudoRating, 1); ?></span>
                        <div class="rating-stars-col">
                            <div class="stars-row">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <svg class="star <?php echo $s <= floor($pseudoRating) ? 'star-full' : 'star-empty'; ?>" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-count-label"><?php echo $totalReviews; ?> ratings</div>
                        </div>
                    </div>
                    <div class="rating-bars">
                        <?php foreach ($bars as $star => $pct): ?>
                        <div class="rating-bar-row">
                            <span class="rating-bar-label"><?php echo $star; ?></span>
                            <div class="rating-bar-track">
                                <div class="rating-bar-fill" style="width: <?php echo $pct; ?>%" data-width="<?php echo $pct; ?>"></div>
                            </div>
                            <span class="rating-bar-count"><?php echo round($totalReviews * $pct / 100); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="write-review-btn" id="writeReviewBtn" onclick="toggleReview()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        Rate this product
                    </button>
                    <div class="star-picker" id="starPicker">
                        <div class="star-picker-label">Your rating</div>
                        <div class="star-input-row" id="starInputRow">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <svg class="star-input" data-val="<?php echo $s; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" onclick="selectStar(<?php echo $s; ?>)" onmouseover="hoverStar(<?php echo $s; ?>)" onmouseout="unhoverStar()">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            <?php endfor; ?>
                        </div>
                        <textarea class="review-textarea" placeholder="Tell others what you think (optional)…" rows="3" id="reviewText"></textarea>
                        <button class="review-submit-btn" onclick="submitReview()">Submit Rating</button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Like -->
                <div class="like-wrap">
                    <button class="like-btn <?php echo $hasLiked ? 'liked' : ''; ?>"
                            id="likeBtn"
                            <?php if (!isset($_SESSION['user_id'])): ?>onclick="window.location='login.php'"<?php endif; ?>
                            data-product-id="<?php echo $product['id']; ?>"
                            data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <svg viewBox="0 0 24 24" fill="<?php echo $hasLiked ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span id="likeLabel"><?php echo $hasLiked ? 'Saved' : 'Save to wishlist'; ?></span>
                        <span class="like-count-label" id="likeCount"><?php echo $likeCount; ?> saves</span>
                    </button>
                </div>

                <!-- Share -->
                <div class="share-strip">
                    <span class="share-label">Share</span>
                    <button class="share-btn" onclick="copyLink()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy link
                    </button>
                    <a href="https://t.me/share/url?url=<?php echo urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($product['name']); ?>" target="_blank" class="share-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="color:#229ED9"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/></svg>
                        Telegram
                    </a>
                </div>

                <div class="divider"></div>

                <!-- Seller -->
                <div class="seller-card">
                    <div class="seller-header">
                        <div class="seller-avatar">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <a href="home.php?seller=<?php echo urlencode($product['owner_name']); ?>" class="seller-name-link">
                                <?php echo htmlspecialchars($product['owner_name']); ?>
                            </a>
                            <span class="seller-sub">Browse all listings by this seller →</span>
                        </div>
                    </div>

                    <div class="seller-phones">
                        <?php if (!empty($product['phone1'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($product['phone1']); ?>" class="phone-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <div>
                                    <span class="phone-label">Call Seller</span>
                                    <span class="phone-number"><?php echo htmlspecialchars($product['phone1']); ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($product['phone2'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($product['phone2']); ?>" class="phone-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <div>
                                    <span class="phone-label">Alternate Number</span>
                                    <span class="phone-number"><?php echo htmlspecialchars($product['phone2']); ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                        <?php if (empty($product['phone1']) && empty($product['phone2'])): ?>
                            <p style="font-size:.8rem;color:var(--ink-ghost);padding:.5rem 0;">No contact info available.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div class="admin-controls">
                            <a href="../controllers/product.php?action=toggle_visibility&id=<?php echo $product['id']; ?>&status=<?php echo $product['showed'] ? '0' : '1'; ?>&redirect=product_detail.php%3Fid%3D<?php echo $product['id']; ?>"
                               class="btn-admin <?php echo $product['showed'] ? 'btn-hide' : 'btn-show'; ?>">
                                <?php if ($product['showed']): ?>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    Hide Product
                                <?php else: ?>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Show Product
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /info-panel -->
        </div><!-- /product-grid -->
    </div><!-- /page-wrap -->

    <!-- Zoom overlay -->
    <div class="zoom-overlay" id="zoomOverlay" onclick="closeZoom()">
        <button class="zoom-close" onclick="closeZoom()">✕</button>
        <img id="zoomImg" src="" alt="">
    </div>

    <div id="toast-container"></div>

    <script>
    // ── Gallery ─────────────────────────────────────────────────────────────
    function changeImage(src, current, total, thumbEl) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        thumbEl.classList.add('active');
        const counter = document.getElementById('imgCount');
        if (counter) counter.textContent = current + ' / ' + total;
    }

    // ── Zoom ─────────────────────────────────────────────────────────────────
    function openZoom(src) {
        document.getElementById('zoomImg').src = src;
        document.getElementById('zoomOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeZoom() {
        document.getElementById('zoomOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeZoom(); });

    // ── Rating bar animation ─────────────────────────────────────────────────
    document.querySelectorAll('.rating-bar-fill').forEach(bar => {
        const w = bar.dataset.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = w + '%'; }, 400);
    });

    // ── Star rating ──────────────────────────────────────────────────────────
    let selectedRating = 0;
    function toggleReview() {
        document.getElementById('starPicker').classList.toggle('open');
    }
    function hoverStar(val) {
        document.querySelectorAll('.star-input').forEach((s, i) => {
            s.style.color = i < val ? '#e0c55a' : '';
            s.setAttribute('fill', i < val ? 'currentColor' : 'none');
        });
    }
    function unhoverStar() {
        document.querySelectorAll('.star-input').forEach((s, i) => {
            s.style.color = i < selectedRating ? '#e0c55a' : '';
            s.setAttribute('fill', i < selectedRating ? 'currentColor' : 'none');
        });
    }
    function selectStar(val) {
        selectedRating = val;
        document.querySelectorAll('.star-input').forEach((s, i) => {
            const active = i < val;
            s.classList.toggle('selected', active);
            s.style.color = active ? '#e0c55a' : '';
            s.setAttribute('fill', active ? 'currentColor' : 'none');
        });
    }
    function submitReview() {
        if (!selectedRating) { showToast('Please select a star rating', 'error'); return; }
        const text = document.getElementById('reviewText').value.trim();
        showToast(`Thanks! You rated this ${selectedRating} star${selectedRating > 1 ? 's' : ''}${text ? ' with a review' : ''} ⭐`, 'success');
        document.getElementById('starPicker').classList.remove('open');
        selectedRating = 0;
        document.getElementById('reviewText').value = '';
        document.querySelectorAll('.star-input').forEach(s => { s.style.color = ''; s.setAttribute('fill','none'); s.classList.remove('selected'); });
    }

    // ── Share / copy link ─────────────────────────────────────────────────────
    function copyLink() {
        navigator.clipboard.writeText(window.location.href)
            .then(() => showToast('Link copied to clipboard!'))
            .catch(() => showToast('Could not copy link', 'error'));
    }

    // ── Toast ─────────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const c = document.getElementById('toast-container');
        const t = document.createElement('div');
        t.className = 'toast toast-' + type;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 3600);
    }
    (function(){
        const p = new URLSearchParams(window.location.search);
        if (p.get('success')) showToast(p.get('success'), 'success');
        if (p.get('error'))   showToast(p.get('error'), 'error');
    })();

    // ── AJAX Like ─────────────────────────────────────────────────────────────
    const likeBtn = document.getElementById('likeBtn');
    if (likeBtn && likeBtn.dataset.productId) {
        likeBtn.addEventListener('click', function () {
            const fd = new FormData();
            fd.append('product_id', this.dataset.productId);
            fd.append('csrf_token', this.dataset.csrf);
            fetch('../controllers/like.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const liked = data.liked;
                    likeBtn.classList.toggle('liked', liked);
                    likeBtn.querySelector('svg').setAttribute('fill', liked ? 'currentColor' : 'none');
                    document.getElementById('likeLabel').textContent = liked ? 'Saved' : 'Save to wishlist';
                    document.getElementById('likeCount').textContent = (data.count ?? (liked ? likeCount+1 : likeCount)) + ' saves';
                    showToast(liked ? '❤️ Added to saved' : 'Removed from saved');
                }
            })
            .catch(() => showToast('Something went wrong', 'error'));
        });
    }

    // ── AJAX Post Comment ─────────────────────────────────────────────────────
    function postComment(e) {
        e.preventDefault();
        const form = document.getElementById('commentForm');
        const textarea = document.getElementById('commentText');
        if (!textarea.value.trim()) return;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = '…';
        fetch('../controllers/comment.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                appendComment(data);
                textarea.value = ''; textarea.style.height = 'auto';
                showToast('Comment posted!');
                const empty = document.querySelector('.no-comments');
                if (empty) empty.remove();
            } else { showToast(data.error || 'Failed', 'error'); }
        })
        .catch(() => showToast('Network error', 'error'))
        .finally(() => { btn.disabled = false; btn.textContent = 'Post'; });
    }
    function appendComment(data) {
        const list = document.getElementById('commentList');
        const div  = document.createElement('div');
        div.className = 'comment-item'; div.id = 'comment-' + (data.id || Date.now());
        div.innerHTML = `
            <div class="comment-avatar">${data.user_name ? data.user_name[0].toUpperCase() : '?'}</div>
            <div class="comment-body">
                <div class="comment-meta">
                    <span class="comment-author">${escHtml(data.user_name||'')}</span>
                    <span class="comment-time">Just now</span>
                </div>
                <p class="comment-text">${escHtml(data.comment||'').replace(/\n/g,'<br>')}</p>
            </div>`;
        list.appendChild(div);
        div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── AJAX Delete Comment ───────────────────────────────────────────────────
    function deleteComment(e, commentId) {
        e.preventDefault();
        if (!confirm('Delete this comment?')) return false;
        fetch('../controllers/comment.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(e.target) })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const el = document.getElementById('comment-' + commentId);
                if (el) { el.style.opacity = '0'; el.style.transition = '.3s'; setTimeout(() => el.remove(), 300); }
                showToast('Comment deleted');
            } else { showToast(data.error || 'Could not delete', 'error'); }
        })
        .catch(() => showToast('Network error', 'error'));
        return false;
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    </script>
</body>
</html>