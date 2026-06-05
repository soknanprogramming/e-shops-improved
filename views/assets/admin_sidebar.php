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

    .admin-sidebar {
        width: 240px;
        background: var(--surface);
        border-right: 1px solid var(--outline);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: sticky;
        top: 0;
        overflow-y: auto;
        font-family: var(--font-body);
    }

    .sidebar-brand {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--outline);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar-brand-icon {
        width: 36px;
        height: 36px;
        background: var(--primary);
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 1.1rem;
        font-family: var(--font-headline);
    }

    .sidebar-brand-text {
        font-family: var(--font-headline);
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
    }

    .sidebar-brand-sub {
        font-size: 0.65rem;
        color: var(--on-surface-variant);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 600;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0.75rem 0;
        margin: 0;
        flex-grow: 1;
    }

    .sidebar-menu li a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 1.5rem;
        color: var(--on-surface-variant);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }

    .sidebar-menu li a:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .sidebar-menu li a.active {
        background: var(--primary-light);
        color: var(--primary);
        border-left-color: var(--primary);
    }

    .sidebar-menu li a svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .sidebar-menu li a .badge-count {
        margin-left: auto;
        background: var(--secondary);
        color: #fff;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 700;
    }

    .sidebar-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--outline);
    }

    .sidebar-footer a {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--tertiary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.825rem;
        transition: opacity 0.2s;
    }

    .sidebar-footer a:hover { opacity: 0.8; }
    .sidebar-footer a svg { width: 18px; height: 18px; }

    /* Mobile responsive sidebar */
    @media (max-width: 768px) {
        .admin-sidebar {
            width: 100%;
            height: auto;
            position: relative;
            border-right: none;
            border-bottom: 1px solid var(--outline);
        }

        .sidebar-brand {
            padding: 1rem 1.25rem;
        }

        .sidebar-menu {
            display: flex;
            overflow-x: auto;
            padding: 0.5rem;
            gap: 4px;
            -webkit-overflow-scrolling: touch;
        }

        .sidebar-menu li {
            flex-shrink: 0;
        }

        .sidebar-menu li a {
            padding: 8px 12px;
            font-size: 0.75rem;
            border-left: none;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            flex-direction: column;
            gap: 4px;
            text-align: center;
        }

        .sidebar-menu li a.active {
            border-left: none;
            border-bottom-color: var(--primary);
        }

        .sidebar-menu li a svg {
            width: 20px;
            height: 20px;
        }

        .sidebar-menu li a .badge-count {
            display: none;
        }

        .sidebar-footer {
            padding: 0.75rem 1.25rem;
        }
    }

    @media (max-width: 480px) {
        .sidebar-menu li a {
            padding: 6px 10px;
            font-size: 0.7rem;
        }

        .sidebar-menu li a svg {
            width: 18px;
            height: 18px;
        }

        .sidebar-brand {
            padding: 0.75rem 1rem;
        }

        .sidebar-brand-text {
            font-size: 1rem;
        }

        .sidebar-brand-sub {
            font-size: 0.6rem;
        }
    }
</style>

<div class="admin-sidebar">
    <div class="sidebar-brand">
        <div>
            <p class="sidebar-brand-text">Sana</p>
            <p class="sidebar-brand-sub">Admin Panel</p>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a></li>
        <li><a href="admin_user.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_user.php' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Users
            <?php if (isset($pendingCount) && $pendingCount > 0): ?>
                <span class="badge-count"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
        </a></li>
        <li><a href="admin_product.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_product.php' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            Products
        </a></li>
        <li><a href="admin_category.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_category.php' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
            Categories
        </a></li>
        <li><a href="home.php">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            View Website
        </a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </div>
</div>
