<?php
session_start();
require_once '../configs/connect.php';

// ── Cookie-based auto-login ──────────────────────────────────────────────────
// Already logged in via session → skip login page
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Remember-me cookie present → VALIDATE against DB before trusting it
// (Never redirect based on a cookie alone — anyone can forge a cookie value)
if (!empty($_COOKIE['remember_user'])) {
    $stmtCk = $conn->prepare("
        SELECT us.user_id, u.name, u.is_admin, u.can_post
        FROM user_sessions us
        JOIN user u ON u.id = us.user_id
        WHERE us.token = :token AND us.expires_at > NOW()
        LIMIT 1
    ");
    $stmtCk->execute([':token' => $_COOKIE['remember_user']]);
    $ckRow = $stmtCk->fetch(PDO::FETCH_ASSOC);

    if ($ckRow) {
        // Valid token — restore session and go to home
        session_regenerate_id(true);
        $_SESSION['user_id']   = $ckRow['user_id'];
        $_SESSION['user_name'] = $ckRow['name'];
        $_SESSION['is_admin']  = $ckRow['is_admin'];
        $_SESSION['can_post']  = $ckRow['can_post'];
        header("Location: home.php");
        exit();
    } else {
        // Expired or tampered cookie — clear it and show login
        setcookie('remember_user', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Sana</title>
    <link rel="icon" href="../icon/e-commerce-logo.png" sizes="any" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;0,9..144,800;1,9..144,700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --forest:       #1a3325;
            --forest-deep:  #0f1f16;
            --forest-mid:   #2a5038;
            --forest-pale:  #edf4ef;
            --gold:         #b8892a;
            --gold-light:   rgba(184,137,42,.12);
            --cream:        #f8f5ee;
            --white:        #ffffff;
            --ink:          #1a1612;
            --ink-mid:      #4a4438;
            --ink-ghost:    #8a8070;
            --red:          #8b1a1a;
            --border:       rgba(26,51,37,.10);
            --border-md:    rgba(26,51,37,.18);
            --r-xs: 6px; --r-sm: 10px; --r-md: 14px; --r-lg: 20px; --r-xl: 28px;
            --ease: cubic-bezier(.16,1,.3,1);
            --spring: cubic-bezier(.34,1.56,.64,1);
            --font-display: 'Fraunces', Georgia, serif;
            --font-body: 'DM Sans', system-ui, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Brand Side ── */
        .brand {
            flex: 1.15;
            background: var(--forest-deep);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem;
        }

        /* Layered ambient blobs */
        .brand-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
        }
        .brand-blob-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(184,137,42,.22) 0%, transparent 70%);
            top: -120px; right: -120px;
            animation: drift1 12s ease-in-out infinite alternate;
        }
        .brand-blob-2 {
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(26,51,37,.8) 0%, transparent 70%);
            bottom: -80px; left: -80px;
            animation: drift2 16s ease-in-out infinite alternate;
        }
        .brand-blob-3 {
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(42,80,56,.5) 0%, transparent 70%);
            top: 40%; left: 30%;
            animation: drift3 9s ease-in-out infinite alternate;
        }
        @keyframes drift1 { to { transform: translate(20px, 30px) scale(1.05); } }
        @keyframes drift2 { to { transform: translate(-10px, -20px) scale(1.08); } }
        @keyframes drift3 { to { transform: translate(15px, -25px); } }

        /* Fine dot texture */
        .brand-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 22px 22px;
            pointer-events: none;
        }

        /* Thin diagonal lines */
        .brand-lines {
            position: absolute; inset: 0;
            background-image: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 40px,
                rgba(255,255,255,.015) 40px,
                rgba(255,255,255,.015) 41px
            );
            pointer-events: none;
        }

        .brand-top {
            position: relative; z-index: 2;
            animation: fadeUp .7s var(--ease) .1s both;
        }
        .brand-logo {
            display: inline-flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .brand-logo-mark {
            width: 46px; height: 46px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: var(--r-sm);
            display: flex; align-items: center; justify-content: center;
            transition: background .3s var(--spring), transform .3s var(--spring);
        }
        .brand-logo-mark:hover { background: rgba(255,255,255,.14); transform: rotate(6deg) scale(1.08); }
        .brand-logo-mark svg { width: 22px; height: 22px; color: #fff; }
        .brand-logo-text {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.01em;
        }

        .brand-center {
            position: relative; z-index: 2;
            animation: fadeUp .7s var(--ease) .2s both;
        }
        .brand-tagline {
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: var(--gold);
            margin-bottom: 1rem;
        }
        .brand-headline {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 3.2vw, 3.2rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.08;
            letter-spacing: -.025em;
            margin-bottom: 1.5rem;
        }
        .brand-headline em { font-style: italic; color: var(--gold); }
        .brand-desc {
            font-size: .9rem;
            color: rgba(255,255,255,.5);
            line-height: 1.8;
            max-width: 320px;
        }

        .brand-bottom {
            position: relative; z-index: 2;
            animation: fadeUp .7s var(--ease) .35s both;
        }
        .brand-pills {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .brand-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.11);
            border-radius: 9999px;
            font-size: .75rem;
            font-weight: 600;
            color: rgba(255,255,255,.75);
        }
        .brand-pill svg { width: 13px; height: 13px; color: var(--gold); }

        /* ── Form Side ── */
        .form-side {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            padding: 3rem 2.5rem;
            background: var(--cream);
            position: relative;
            overflow: hidden;
        }

        /* Subtle grid bg */
        .form-side::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 36px 36px;
            pointer-events: none;
        }

        /* Corner accent */
        .form-side::after {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 260px; height: 260px;
            background: radial-gradient(circle, rgba(184,137,42,.08) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-card {
            position: relative; z-index: 1;
            width: 100%; max-width: 420px;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(255,255,255,.75);
            border-radius: var(--r-xl);
            padding: 2.75rem;
            box-shadow:
                0 2px 4px rgba(26,51,37,.03),
                0 8px 24px rgba(26,51,37,.07),
                0 32px 64px rgba(26,51,37,.09);
            animation: cardIn .8s var(--ease) both;
        }

        /* Card top accent line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 2rem; right: 2rem;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            border-radius: 0 0 2px 2px;
            opacity: .6;
        }

        .card-header {
            margin-bottom: 2.25rem;
            animation: fadeUp .6s var(--ease) .15s both;
        }
        .card-header-eyebrow {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--gold);
            margin-bottom: .6rem;
        }
        .card-header h2 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--forest);
            letter-spacing: -.02em;
            line-height: 1.1;
            margin-bottom: .4rem;
        }
        .card-header p {
            font-size: .85rem;
            color: var(--ink-ghost);
        }

        /* Error */
        .err-box {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 14px;
            background: #fff0f0;
            border: 1.5px solid rgba(139,26,26,.15);
            border-radius: var(--r-sm);
            margin-bottom: 1.75rem;
            animation: shake .38s ease;
        }
        .err-box svg { width: 16px; height: 16px; color: var(--red); flex-shrink: 0; margin-top: 1px; }
        .err-box p { font-size: .82rem; color: var(--red); font-weight: 600; }

        /* Form */
        .form-group { margin-bottom: 1.1rem; }
        .form-group label {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--ink-ghost);
            margin-bottom: .45rem;
        }
        .inp-wrap { position: relative; }
        .inp-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 17px; height: 17px;
            color: var(--ink-ghost); opacity: .35;
            pointer-events: none;
            transition: opacity .2s, color .25s, transform .25s var(--spring);
        }
        .inp-wrap input {
            width: 100%;
            padding: 13px 44px;
            background: var(--cream);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-sm);
            font-size: .9rem;
            font-family: var(--font-body);
            color: var(--ink);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s, transform .2s var(--ease);
        }
        .inp-wrap input:focus {
            border-color: var(--forest);
            box-shadow: 0 0 0 3px rgba(26,51,37,.09), 0 4px 12px rgba(26,51,37,.04);
            background: var(--white);
            transform: translateY(-1px);
        }
        .inp-wrap input:focus + .inp-icon,
        .inp-wrap input:focus ~ .inp-icon:first-of-type {
            opacity: .9; color: var(--forest);
            transform: translateY(-50%) scale(1.1);
        }
        .inp-wrap input::placeholder { color: rgba(26,51,37,.22); }

        .eye-btn {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 5px;
            color: var(--ink-ghost); opacity: .38;
            display: flex; align-items: center;
            transition: opacity .2s, transform .2s var(--spring);
        }
        .eye-btn:hover { opacity: .75; transform: translateY(-50%) scale(1.1); }
        .eye-btn svg { width: 16px; height: 16px; }

        /* Remember & forgot row */
        .remember-row {
            display: flex; align-items: center; justify-content: space-between;
            margin: 1.35rem 0;
        }
        .remember-label {
            display: flex; align-items: center; gap: 8px;
            cursor: pointer; user-select: none;
        }
        .remember-label input[type="checkbox"] {
            appearance: none; -webkit-appearance: none;
            width: 17px; height: 17px;
            border: 2px solid var(--border-md);
            border-radius: 4px;
            background: var(--cream);
            display: grid; place-content: center;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .2s var(--spring);
        }
        .remember-label input[type="checkbox"]:checked {
            background: var(--forest);
            border-color: var(--forest);
            transform: scale(1.1);
        }
        .remember-label input[type="checkbox"]:checked::before {
            content: '';
            width: 8px; height: 5px;
            border-left: 2px solid #fff;
            border-bottom: 2px solid #fff;
            transform: rotate(-45deg) translate(1px,-1px);
            animation: checkPop .2s var(--spring) forwards;
        }
        .remember-label span {
            font-size: .82rem; font-weight: 500; color: var(--ink-mid);
        }

        /* Submit button */
        .btn-submit {
            width: 100%; padding: 15px;
            background: var(--forest);
            color: #fff; border: none;
            border-radius: var(--r-sm);
            font-family: var(--font-display);
            font-size: .95rem; font-weight: 700;
            letter-spacing: .01em;
            cursor: pointer;
            position: relative; overflow: hidden;
            transition: background .25s, transform .25s var(--spring), box-shadow .25s;
            box-shadow: 0 4px 16px rgba(26,51,37,.18);
        }
        .btn-submit::before {
            content: '';
            position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);
            transition: left .5s ease;
        }
        .btn-submit:hover { background: var(--forest-mid); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(26,51,37,.24); }
        .btn-submit:hover::before { left: 100%; }
        .btn-submit:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 1.75rem 0 1.35rem;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border-md); }
        .divider span { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-ghost); }

        .register-link {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none;
            padding: 13px;
            background: var(--cream);
            border: 1.5px solid var(--border-md);
            border-radius: var(--r-sm);
            font-size: .875rem; font-weight: 600; color: var(--ink-mid);
            transition: all .25s var(--ease);
        }
        .register-link:hover {
            background: var(--forest-pale);
            border-color: var(--forest);
            color: var(--forest);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26,51,37,.06);
        }
        .register-link svg { width: 15px; height: 15px; transition: transform .3s var(--spring); }
        .register-link:hover svg { transform: translateX(5px); }

        /* Animations */
        @keyframes cardIn {
            from { opacity:0; transform: translateY(24px) scale(.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }
        @keyframes fadeUp {
            from { opacity:0; transform: translateY(14px); }
            to   { opacity:1; transform: translateY(0); }
        }
        @keyframes checkPop {
            from { transform: scale(0) rotate(-45deg); opacity:0; }
            to   { transform: scale(1) rotate(-45deg); opacity:1; }
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-5px); }
            40%,80%  { transform: translateX(5px); }
        }

        /* Staggered form items */
        .stagger > * { animation: fadeUp .55s var(--ease) both; }
        .stagger > *:nth-child(1) { animation-delay: .18s; }
        .stagger > *:nth-child(2) { animation-delay: .24s; }
        .stagger > *:nth-child(3) { animation-delay: .30s; }
        .stagger > *:nth-child(4) { animation-delay: .36s; }
        .stagger > *:nth-child(5) { animation-delay: .42s; }
        .stagger > *:nth-child(6) { animation-delay: .48s; }

        /* Responsive */
        @media (max-width: 900px) {
            .brand { display: none; }
            .form-side { background: var(--cream); }
            .login-card { background: var(--white); box-shadow: 0 4px 24px rgba(26,51,37,.08); }
        }
        @media (max-width: 480px) {
            .form-side { padding: 2rem 1.25rem; }
            .login-card { padding: 2rem 1.5rem; border-radius: var(--r-lg); }
        }
    </style>
</head>
<body>
    <!-- ── Brand Panel ── -->
    <div class="brand">
        <div class="brand-blob brand-blob-1"></div>
        <div class="brand-blob brand-blob-2"></div>
        <div class="brand-blob brand-blob-3"></div>
        <div class="brand-dots"></div>
        <div class="brand-lines"></div>

        <div class="brand-top">
            <a href="#" class="brand-logo">
                <div class="brand-logo-mark">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                </div>
                <span class="brand-logo-text">Sana</span>
            </a>
        </div>

        <div class="brand-center">
            <div class="brand-tagline">Trusted Marketplace</div>
            <h1 class="brand-headline">Buy &amp; sell<br>with <em>confidence.</em></h1>
            <p class="brand-desc">Join thousands of buyers and sellers on Sana — the marketplace built on trust, simplicity, and community.</p>
        </div>

        <div class="brand-bottom">
            <div class="brand-pills">
                <div class="brand-pill">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                    Free to list
                </div>
                <div class="brand-pill">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Safe &amp; secure
                </div>
                <div class="brand-pill">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                    Direct messaging
                </div>
            </div>
        </div>
    </div>

    <!-- ── Form Panel ── -->
    <div class="form-side">
        <div class="login-card">
            <div class="card-header">
                <div class="card-header-eyebrow">Welcome back</div>
                <h2>Sign in to Sana</h2>
                <p>Enter your credentials to continue browsing</p>
            </div>

            <form action="../controllers/auth.php" method="POST" class="stagger">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <?php if (isset($_GET['error'])): ?>
                    <div class="err-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="inp-wrap">
                        <input type="email" id="email" name="email" required
                               placeholder="your@email.com" autocomplete="email"
                               value="<?php echo htmlspecialchars($loginEmail); ?>">
                        <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="inp-wrap">
                        <input type="password" id="password" name="password" required
                               placeholder="Your password" autocomplete="current-password">
                        <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <button type="button" class="eye-btn" onclick="togglePass()" aria-label="Toggle password">
                            <svg id="eyeOn" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeOff" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <label class="remember-label" for="remember">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <span>Remember me for 10 days</span>
                    </label>
                </div>

                <button type="submit" name="login" class="btn-submit">Sign In</button>

                <div class="divider"><span>New to Sana?</span></div>

                <a href="register.php" class="register-link">
                    <span>Create a free account</span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </form>
        </div>
    </div>

    <script>
        function togglePass() {
            const inp = document.getElementById('password');
            const on  = document.getElementById('eyeOn');
            const off = document.getElementById('eyeOff');
            if (inp.type === 'password') {
                inp.type = 'text';
                on.style.display  = 'none';
                off.style.display = 'block';
            } else {
                inp.type = 'password';
                on.style.display  = 'block';
                off.style.display = 'none';
            }
        }
    </script>
</body>
</html>
