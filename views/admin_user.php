<?php
session_start();
require_once '../configs/connect.php';
require_once '../repos/UserRepository.php';

// ── 1. Auth Check ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// ── 2. Handle Search and Ordering parameters ───────────────────────────────────
$search  = isset($_GET['search'])  ? trim($_GET['search'])  : '';
$filter  = isset($_GET['filter'])  ? $_GET['filter']        : null;
$orderBy = isset($_GET['order'])   ? $_GET['order']         : 'id_desc';

// ── 3. Fetch Users using Repository ───────────────────────────────────────────
$userRepo = new UserRepository($conn);
$users    = $userRepo->getAllWithFilters($filter, $search, $orderBy);

// Pending count for sidebar badge
$stmtPending = $conn->prepare("SELECT COUNT(*) as total FROM user WHERE request_post_permission = 1 AND (can_post = 0 OR can_post IS NULL)");
$stmtPending->execute();
$pendingCount = $stmtPending->fetch()['total'];

// Load profile images for users so the avatar cell can show photos when available
$stmtProfiles = $conn->prepare("SELECT user_id, user_image FROM user_profile");
$stmtProfiles->execute();
$profileImagesByUserId = [];
while ($profileRow = $stmtProfiles->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($profileRow['user_image'])) {
        $profileImagesByUserId[(int)$profileRow['user_id']] = $profileRow['user_image'];
    }
}

// Avatar colour palette
$avColors = [
    ['bg'=>'#d4f1e4','color'=>'#0f6e56'],
    ['bg'=>'#e8e4ff','color'=>'#534ab7'],
    ['bg'=>'#fde8d8','color'=>'#993c1d'],
    ['bg'=>'#fdf0d4','color'=>'#854f0b'],
    ['bg'=>'#fce4f0','color'=>'#993556'],
    ['bg'=>'#dceeff','color'=>'#185fa5'],
];
function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="icon" href="../icon/e-commerce-logo.png" sizes="any" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ─── TOKENS ───────────────────────────────────────────────────── */
        :root {
            --primary:           #1a3325;
            --primary-container: #2a5038;
            --primary-light:     rgba(26,51,37,.05);
            --secondary:         #9d7c39;
            --secondary-light:   rgba(157,124,57,.10);
            --tertiary:          #7e000a;
            --bg-body:           #faf7f2;
            --surface:           #ffffff;
            --on-surface:        #201b09;
            --on-surface-variant:#6b6355;
            --outline:           rgba(74,69,56,.12);
            --outline-strong:    rgba(74,69,56,.25);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --font-headline: 'Manrope', sans-serif;
            --font-body:    'Public Sans', sans-serif;
        }

        /* ─── RESET ─────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: var(--font-body);
            margin: 0; padding: 0;
            background: var(--bg-body);
            color: var(--on-surface);
            display: flex;
            min-height: 100vh;
        }

        /* ─── LAYOUT ─────────────────────────────────────────────────────── */
        .main-content {
            flex-grow: 1;
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* ─── PAGE HEADER ─────────────────────────────────────────────────── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: .75rem;
        }
        .page-header h1 {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
        }
        .count-badge {
            font-size: .8rem;
            color: var(--on-surface-variant);
            font-weight: 600;
            background: var(--surface);
            border: 1px solid var(--outline);
            padding: 6px 14px;
            border-radius: 20px;
        }

        /* ─── FILTER TABS ─────────────────────────────────────────────────── */
        .filter-tabs {
            display: flex;
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            overflow: hidden;
            width: fit-content;
            margin-bottom: 1.25rem;
        }
        .filter-tab {
            padding: 8px 18px;
            text-decoration: none;
            font-weight: 600;
            font-size: .8rem;
            color: var(--on-surface-variant);
            border-right: 1px solid var(--outline);
            transition: all .2s;
            white-space: nowrap;
        }
        .filter-tab:last-child { border-right: none; }
        .filter-tab:hover  { background: var(--primary-light); color: var(--primary); }
        .filter-tab.active { background: var(--primary); color: #fff; }

        /* ─── SEARCH / ORDER BAR ──────────────────────────────────────────── */
        .search-order-bar {
            display: flex;
            gap: .75rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 9px 14px 9px 40px;
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            background: var(--surface);
            font-family: var(--font-body);
            font-size: .85rem;
            color: var(--on-surface);
            transition: all .2s;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        .search-box input::placeholder { color: var(--on-surface-variant); }
        .search-box svg {
            position: absolute; left: 12px;
            top: 50%; transform: translateY(-50%);
            width: 17px; height: 17px;
            color: var(--on-surface-variant);
            pointer-events: none;
        }
        .order-select {
            padding: 9px 14px;
            border: 1px solid var(--outline);
            border-radius: var(--radius-sm);
            background: var(--surface);
            font-family: var(--font-body);
            font-size: .85rem;
            color: var(--on-surface);
            cursor: pointer;
            min-width: 170px;
            transition: all .2s;
        }
        .order-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border: 1px solid transparent;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-container) 100%);
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            text-decoration: none;
            transition: all .2s ease;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(26, 51, 37, 0.16);
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(26, 51, 37, 0.24);
            filter: brightness(1.05);
        }
        .action-btn:nth-child(2n) {
            background: linear-gradient(135deg, var(--secondary) 0%, #b78b3d 100%);
        }

        /* ─── TABLE CARD ──────────────────────────────────────────────────── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 11px 14px;
            text-align: left;
            background: var(--bg-body);
            font-weight: 700;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--on-surface-variant);
            border-bottom: 1px solid var(--outline);
        }
        th a {
            text-decoration: none;
            color: inherit;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--outline);
            font-size: .84rem;
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }

        /* Clickable row */
        .user-table-row {
            cursor: pointer;
            transition: background .15s;
        }
        .user-table-row:hover { background: var(--primary-light); }
        .user-table-row.row-active {
            background: rgba(26,51,37,.08);
            border-left: 3px solid var(--primary);
        }
        .user-table-row.row-active td:first-child { padding-left: 11px; }

        /* Avatar */
        .avatar-cell {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            flex-shrink: 0;
            user-select: none;
            overflow: hidden;
        }
        .avatar-image {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
        }
        .user-info-wrap { display: flex; align-items: center; gap: 10px; }
        .user-name  { font-weight: 600; color: var(--on-surface); }
        .user-email { font-size: .78rem; color: var(--on-surface-variant); margin-top: 1px; }

        /* Click-hint label on row hover */
        .row-hint {
            font-size: .68rem;
            color: var(--primary);
            opacity: 0;
            font-weight: 600;
            transition: opacity .15s;
            white-space: nowrap;
        }
        .user-table-row:hover .row-hint { opacity: 1; }

        /* Badges */
        .badge {
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-block;
        }
        .badge-admin      { background: rgba(157,124,57,.12); color: var(--secondary); }
        .badge-user       { background: rgba(108,117,125,.12); color: #6c757d; }
        .badge-allowed    { background: rgba(40,167,69,.12);  color: #28a745; }
        .badge-restricted { background: rgba(108,117,125,.12); color: #6c757d; }
        .badge-requesting { background: rgba(255,193,7,.15);  color: #856404; margin-left: 5px; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 3rem 1rem;
            color: var(--on-surface-variant);
        }
        .empty-state svg { width: 44px; height: 44px; opacity: .3; margin-bottom: .75rem; }
        .empty-state p   { margin: 0; font-size: .875rem; }

        /* ─── TOAST ───────────────────────────────────────────────────────── */
        .toast {
            position: fixed; bottom: 24px; right: 24px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            color: #fff; font-weight: 600; font-size: .82rem;
            z-index: 9999;
            animation: toastIn .3s ease, toastOut .3s ease 2.7s forwards;
        }
        .toast-success { background: var(--primary); }
        .toast-error   { background: var(--tertiary); }
        @keyframes toastIn  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes toastOut { from { opacity:1; } to { opacity:0; transform:translateY(10px); } }

        /* ─── RESPONSIVE ──────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .main-content { padding: 1rem; }
            .filter-tabs  { width: 100%; overflow-x: auto; }
            .search-order-bar { flex-direction: column; align-items: stretch; }
            .search-box   { min-width: 100%; }
            .order-select { min-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include './assets/admin_sidebar.php'; ?>

    <div class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <h1>User Management</h1>
            <span class="count-badge"><?php echo count($users); ?> user<?php echo count($users) !== 1 ? 's' : ''; ?></span>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="admin_user.php" class="filter-tab <?php echo !isset($_GET['filter']) ? 'active' : ''; ?>">
                All Users
            </a>
            <a href="admin_user.php?filter=requesting" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter']==='requesting') ? 'active' : ''; ?>">
                Pending Requests
                <?php if ($pendingCount > 0): ?>
                    <span style="margin-left:4px;opacity:.75;">(<?php echo $pendingCount; ?>)</span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Search + Order -->
        <form method="GET" class="search-order-bar" id="userFilterForm">
            <?php if (isset($_GET['filter'])): ?>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($_GET['filter']); ?>">
            <?php endif; ?>

            <div class="search-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search"
                       placeholder="Search by name or email…"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <select name="order" class="order-select" id="orderSelect">
                <option value="id_desc"         <?php echo $orderBy==='id_desc'         ? 'selected':'' ?>>Newest First</option>
                <option value="id_asc"          <?php echo $orderBy==='id_asc'          ? 'selected':'' ?>>Oldest First</option>
                <option value="name_asc"        <?php echo $orderBy==='name_asc'        ? 'selected':'' ?>>Name (A–Z)</option>
                <option value="name_desc"       <?php echo $orderBy==='name_desc'       ? 'selected':'' ?>>Name (Z–A)</option>
                <option value="role_asc"        <?php echo $orderBy==='role_asc'        ? 'selected':'' ?>>Role (User → Admin)</option>
                <option value="role_desc"       <?php echo $orderBy==='role_desc'       ? 'selected':'' ?>>Role (Admin → User)</option>
                <option value="permission_asc"  <?php echo $orderBy==='permission_asc'  ? 'selected':'' ?>>Permission (Restricted → Allowed)</option>
                <option value="permission_desc" <?php echo $orderBy==='permission_desc' ? 'selected':'' ?>>Permission (Allowed → Restricted)</option>
            </select>
        </form>

        <!-- User Table (full width now, no side panel) -->
        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>
                                <a href="?order=<?php echo $orderBy==='name_asc'?'name_desc':'name_asc'; ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?><?php echo isset($_GET['filter'])?'&filter='.htmlspecialchars($_GET['filter']):''; ?>">
                                    Name <?php if(strpos($orderBy,'name')!==false) echo $orderBy==='name_asc'?'↑':'↓'; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?order=<?php echo $orderBy==='role_asc'?'role_desc':'role_asc'; ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?><?php echo isset($_GET['filter'])?'&filter='.htmlspecialchars($_GET['filter']):''; ?>">
                                    Role <?php if(strpos($orderBy,'role')!==false) echo $orderBy==='role_asc'?'↑':'↓'; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?order=<?php echo $orderBy==='permission_asc'?'permission_desc':'permission_asc'; ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?><?php echo isset($_GET['filter'])?'&filter='.htmlspecialchars($_GET['filter']):''; ?>">
                                    Permission <?php if(strpos($orderBy,'permission')!==false) echo $orderBy==='permission_asc'?'↑':'↓'; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?order=<?php echo $orderBy==='id_asc'?'id_desc':'id_asc'; ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?><?php echo isset($_GET['filter'])?'&filter='.htmlspecialchars($_GET['filter']):''; ?>">
                                    Joined <?php if(strpos($orderBy,'id')!==false) echo $orderBy==='id_asc'?'↑':'↓'; ?>
                                </a>
                            </th>
                            <th style="text-align:center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <p>No users found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $user): ?>
                                <?php
                                    $col      = $avColors[$i % count($avColors)];
                                    $initials = getInitials($user['name']);
                                    $joined   = $user['created_at']
                                                ? date('M j, Y', strtotime($user['created_at']))
                                                : '—';
                                    $profileImage = $profileImagesByUserId[(int)$user['id']] ?? null;
                                ?>
                                <tr class="user-table-row"
                                    data-uid="<?php echo $user['id']; ?>"
                                    onclick="if (event && event.target && (event.target.closest('.action-btn') || event.target.closest('.action-group'))) { event.preventDefault(); event.stopPropagation(); return false; } openDetail(<?php echo (int)$user['id']; ?>);"
                                    title="Click to view full profile">
                                    <td>
                                        <?php if (!empty($profileImage)): ?>
                                            <div class="avatar-cell">
                                                <img src="../uploads/profiles/<?php echo htmlspecialchars($profileImage); ?>"
                                                     alt="<?php echo htmlspecialchars($user['name']); ?>"
                                                     class="avatar-image">
                                            </div>
                                        <?php else: ?>
                                            <div class="avatar-cell"
                                                 style="background:<?php echo $col['bg']; ?>;color:<?php echo $col['color']; ?>">
                                                <?php echo $initials; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="user-info-wrap">
                                            <div>
                                                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge badge-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-user">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['can_post']): ?>
                                            <span class="badge badge-allowed">Allowed</span>
                                        <?php else: ?>
                                            <span class="badge badge-restricted">Restricted</span>
                                            <?php if (!empty($user['request_post_permission'])): ?>
                                                <span class="badge badge-requesting">Requesting</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;font-size:.78rem;color:var(--on-surface-variant);">
                                        <?php echo $joined; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <div class="action-group">
                                            <a href="../controllers/user.php?action=toggle_role&id=<?php echo (int)$user['id']; ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?><?php echo isset($filter) && $filter !== null ? '&filter=' . urlencode($filter) : ''; ?>&order=<?php echo urlencode($orderBy); ?>"
                                               class="action-btn"
                                               onclick='event.stopPropagation(); if (!confirm(<?php echo json_encode($user['is_admin'] ? 'Remove admin privileges for this user?' : 'Grant admin privileges to this user?'); ?>)) { event.preventDefault(); }'>
                                                <?php echo $user['is_admin'] ? 'Demote' : 'Make Admin'; ?>
                                            </a>
                                            <a href="../controllers/user.php?action=toggle_permission&id=<?php echo (int)$user['id']; ?><?php echo !empty($search)?'&search='.urlencode($search):''; ?><?php echo isset($filter) && $filter !== null ? '&filter=' . urlencode($filter) : ''; ?>&order=<?php echo urlencode($orderBy); ?>"
                                               class="action-btn"
                                               onclick='event.stopPropagation(); if (!confirm(<?php echo json_encode($user['can_post'] ? 'Revoke posting permission for this user?' : 'Allow this user to post products?'); ?>)) { event.preventDefault(); }'>
                                                <?php echo !empty($user['can_post']) ? 'Revoke Post' : 'Allow Post'; ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /main-content -->

    <!-- Toast Notification -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div class="toast <?php echo isset($_GET['success']) ? 'toast-success' : 'toast-error'; ?>">
            <?php echo htmlspecialchars($_GET['success'] ?? $_GET['error'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <!-- Full Profile Modal (includes its own styles + JS) -->
    <?php include './user_profile_modal.php'; ?>

    <script>
        // Auto-submit on order change
        document.getElementById('orderSelect').addEventListener('change', function() {
            document.getElementById('userFilterForm').submit();
        });

        // Debounce search
        function debounce(fn, ms) {
            let t;
            return function(...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
        }
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function() {
                document.getElementById('userFilterForm').submit();
            }, 500));
        }
    </script>
</body>
</html>
