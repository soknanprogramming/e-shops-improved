<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once '../configs/connect.php';
require_once '../repos/UserRepository.php';
require_once '../repos/ProfileRepository.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userRepo = new UserRepository($conn);
$profileRepo = new ProfileRepository($conn);

$user = $userRepo->findById($_SESSION['user_id']);
$profile = $profileRepo->getByUserId($_SESSION['user_id']);

// Default values if profile doesn't exist yet
$phone1 = $profile['phone1'] ?? '';
$phone2 = $profile['phone2'] ?? '';
$bio = $profile['bio'] ?? '';
$userImage = $profile['user_image'] ?? '';
$backgroundImage = $profile['background_image'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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

        /* Profile Banner */
        .profile-banner {
            position: relative;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-container) 100%);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 1.25rem;
            min-height: 200px;
        }

        .profile-banner-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.25;
            pointer-events: none;
        }

        .profile-banner-content {
            position: relative;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: flex-end;
            gap: 1.25rem;
        }

        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar svg {
            width: 40px;
            height: 40px;
            color: #fff;
            opacity: 0.6;
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--secondary);
            color: #fff;
            border: 2px solid #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .avatar-edit-btn input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .profile-info {
            padding-bottom: 0.5rem;
        }

        .profile-info .display-name {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 0.125rem;
        }

        .profile-info .user-email {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            margin: 0;
        }

        .banner-upload {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(0,0,0,0.4);
            color: #fff;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: background 0.2s;
            border: none;
        }

        .banner-upload:hover { background: rgba(0,0,0,0.6); }

        /* Form Section */
        .form-section {
            background: var(--surface);
            border: 1px solid var(--outline);
            border-radius: var(--radius-md);
            padding: 1.5rem;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title svg {
            width: 18px;
            height: 18px;
            color: var(--secondary);
            opacity: 0.8;
        }

        /* Form Fields */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 576px) { .form-row { grid-template-columns: 1fr; } }

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

        .form-group label .hint {
            text-transform: none;
            letter-spacing: normal;
            font-weight: 400;
            font-size: 0.7rem;
            color: var(--on-surface-variant);
            opacity: 0.7;
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

        .form-control.disabled {
            background: #f0ede6;
            color: var(--on-surface-variant);
            cursor: not-allowed;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 90px;
        }

        /* Input with icon */
        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--on-surface-variant);
            opacity: 0.4;
            pointer-events: none;
        }

        .input-wrapper .form-control {
            padding-left: 36px;
        }

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
        <div class="page-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and settings</p>
        </div>

        <div class="form-wrapper">
            <form action="../controllers/profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <!-- Profile Banner -->
                <div class="profile-banner" id="profileBanner">
                    <?php if (!empty($backgroundImage)): ?>
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($backgroundImage); ?>" class="profile-banner-bg" alt="Background" id="bannerPreview">
                    <?php endif; ?>
                    <button type="button" class="banner-upload" id="bannerBtn">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Change Cover
                    </button>
                    <input type="file" name="background_image" id="backgroundImageInput" accept="image/*" style="display:none;">
                    <div class="profile-banner-content">
                        <div class="profile-avatar-wrapper">
                            <div class="profile-avatar" id="avatarDisplay">
                                <?php if (!empty($userImage)): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($userImage); ?>" alt="Avatar">
                                <?php else: ?>
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <?php endif; ?>
                            </div>
                            <label class="avatar-edit-btn">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                <input type="file" name="user_image" id="avatarInput" accept="image/*">
                            </label>
                        </div>
                        <div class="profile-info">
                            <p class="display-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                            <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="form-layout">
                    <!-- Left: Personal Info -->
                    <div>
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Personal Information
                            </h3>
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" class="form-control disabled" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="bio">Bio / About Me <span class="hint">(optional)</span></label>
                                <textarea id="bio" name="bio" class="form-control" rows="5" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <!-- Right: Contact Details -->
                    <div>
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                Contact Details
                            </h3>
                            <div class="form-group">
                                <label for="phone1">Phone Number 1 <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <input type="text" id="phone1" name="phone1" class="form-control" value="<?php echo htmlspecialchars($phone1); ?>" required placeholder="012345678">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone2">Phone Number 2 <span class="hint">(optional)</span></label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <input type="text" id="phone2" name="phone2" class="form-control" value="<?php echo htmlspecialchars($phone2); ?>" placeholder="012345678">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
        <div class="toast <?php echo isset($_GET['success']) ? 'toast-success' : 'toast-error'; ?>">
            <?php echo htmlspecialchars($_GET['success'] ?? $_GET['error'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <script>
        // Banner button triggers file input
        document.getElementById('bannerBtn').addEventListener('click', function() {
            document.getElementById('backgroundImageInput').click();
        });

        // Avatar preview
        document.getElementById('avatarInput').addEventListener('change', function() {
            const avatar = document.getElementById('avatarDisplay');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatar.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Banner preview
        document.getElementById('backgroundImageInput').addEventListener('change', function() {
            const banner = document.getElementById('profileBanner');
            let bgImg = document.getElementById('bannerPreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (!bgImg) {
                        bgImg = document.createElement('img');
                        bgImg.id = 'bannerPreview';
                        bgImg.className = 'profile-banner-bg';
                        banner.insertBefore(bgImg, banner.firstChild);
                    }
                    bgImg.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>