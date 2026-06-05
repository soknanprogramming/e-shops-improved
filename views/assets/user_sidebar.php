<div class="sidebar">
    <div class="sidebar-brand">
        <a href="home.php">Sana</a>
    </div>
    <nav class="sidebar-menu">
        <a href="home.php" class="sidebar-link">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span>Back to Shop</span>
        </a>
        <a href="user_dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <span>My Products</span>
        </a>
        <a href="user_profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span>My Profile</span>
        </a>
        <a href="product_create.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'product_create.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Post Product</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-link logout-link">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
    .sidebar {
        width: 240px;
        background-color: #ffffff;
        border-right: 1px solid rgba(74, 69, 56, 0.12);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        height: 100vh;
        position: sticky;
        top: 0;
        overflow-y: auto;
        font-family: 'Public Sans', sans-serif;
    }

    .sidebar-brand {
        padding: 1.25rem 1.25rem 1rem;
        border-bottom: 1px solid rgba(74, 69, 56, 0.1);
    }

    .sidebar-brand a {
        color: #1a3325;
        text-decoration: none;
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: -0.05em;
        font-family: 'Manrope', sans-serif;
    }

    .sidebar-menu {
        padding: 0.5rem 0.75rem;
        flex-grow: 1;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        margin-bottom: 2px;
        color: #4a4538;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        font-family: 'Public Sans', sans-serif;
        transition: all 0.15s ease;
    }

    .sidebar-link:hover {
        background-color: #faf7f2;
        color: #1a3325;
    }

    .sidebar-link.active {
        background-color: rgba(26, 51, 37, 0.08);
        color: #1a3325;
        font-weight: 600;
    }

    .sidebar-link svg {
        flex-shrink: 0;
        opacity: 0.6;
        transition: opacity 0.15s;
    }

    .sidebar-link:hover svg,
    .sidebar-link.active svg {
        opacity: 1;
    }

    .sidebar-footer {
        padding: 0.75rem;
        border-top: 1px solid rgba(74, 69, 56, 0.1);
    }

    .logout-link:hover {
        background-color: rgba(126, 0, 10, 0.06) !important;
        color: #7e000a !important;
    }
</style>