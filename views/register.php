<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$old = $_SESSION['register_input'] ?? [];
unset($_SESSION['register_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Sana</title>
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
    -webkit-font-smoothing: antialiased;
}

/* ── Form side (left) ── */
.form-side {
    flex: 1;
    display: flex; align-items: center; justify-content: center;
    padding: 2.5rem 2rem;
    background: var(--cream);
    position: relative;
    overflow: hidden;
    overflow-y: auto;
}
.form-side::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(var(--border) 1px, transparent 1px),
        linear-gradient(90deg, var(--border) 1px, transparent 1px);
    background-size: 36px 36px;
    pointer-events: none;
}
.form-side::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -60px;
    width: 240px; height: 240px;
    background: radial-gradient(circle, rgba(184,137,42,.07) 0%, transparent 65%);
    border-radius: 50%;
    pointer-events: none;
}

.form-card {
    position: relative; z-index: 1;
    width: 100%; max-width: 440px;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1.5px solid rgba(255,255,255,.75);
    border-radius: var(--r-xl);
    padding: 2.5rem;
    box-shadow:
        0 2px 4px rgba(26,51,37,.03),
        0 8px 24px rgba(26,51,37,.07),
        0 32px 64px rgba(26,51,37,.09);
    animation: cardIn .8s var(--ease) both;
}
.form-card::before {
    content: '';
    position: absolute;
    top: 0; left: 2rem; right: 2rem;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    border-radius: 0 0 2px 2px;
    opacity: .6;
}

.fc-header { margin-bottom: 2rem; animation: fadeUp .6s var(--ease) .1s both; }
.fc-header-eyebrow {
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: var(--gold); margin-bottom: .6rem;
}
.fc-header h1 {
    font-family: var(--font-display);
    font-size: 1.75rem; font-weight: 800;
    color: var(--forest);
    letter-spacing: -.02em; line-height: 1.1;
    margin-bottom: .4rem;
}
.fc-header p { font-size: .85rem; color: var(--ink-ghost); }

/* Error */
.err-box {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 14px;
    background: #fff0f0;
    border: 1.5px solid rgba(139,26,26,.15);
    border-radius: var(--r-sm);
    margin-bottom: 1.5rem;
    animation: shake .38s ease;
}
.err-box svg { width: 16px; height: 16px; color: var(--red); flex-shrink: 0; margin-top: 1px; }
.err-box p { font-size: .82rem; color: var(--red); font-weight: 600; }

/* Fields */
.fg { margin-bottom: 1rem; }
.fg label {
    display: block;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .09em;
    color: var(--ink-ghost); margin-bottom: .45rem;
}
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
@media (max-width:460px) { .row-2 { grid-template-columns: 1fr; } }

.inp-wrap { position: relative; }
.inp-icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%);
    width: 16px; height: 16px;
    color: var(--ink-ghost); opacity: .35;
    pointer-events: none;
    transition: opacity .2s, color .25s, transform .25s var(--spring);
}
.inp-wrap input {
    width: 100%;
    padding: 12px 42px;
    background: var(--cream);
    border: 1.5px solid var(--border-md);
    border-radius: var(--r-xs);
    font-size: .875rem; font-family: var(--font-body);
    color: var(--ink); outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s, transform .2s var(--ease);
}
.inp-wrap input:focus {
    border-color: var(--forest);
    box-shadow: 0 0 0 3px rgba(26,51,37,.09), 0 4px 12px rgba(26,51,37,.04);
    background: var(--white);
    transform: translateY(-1px);
}
.inp-wrap input:focus + .inp-icon { opacity: .9; color: var(--forest); transform: translateY(-50%) scale(1.1); }
.inp-wrap input::placeholder { color: rgba(26,51,37,.22); }

.eye-btn {
    position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; padding: 4px;
    color: var(--ink-ghost); opacity: .35;
    display: flex; align-items: center;
    transition: opacity .2s, transform .2s var(--spring);
}
.eye-btn:hover { opacity: .75; transform: translateY(-50%) scale(1.1); }
.eye-btn svg { width: 15px; height: 15px; }

/* Strength meter */
.strength-wrap { margin-top: .5rem; display: none; }
.strength-wrap.show { display: block; }
.strength-bars { display: flex; gap: 4px; margin-bottom: 5px; }
.strength-bar { flex: 1; height: 3px; border-radius: 9999px; background: var(--border-md); transition: background .3s; }
.strength-bar.s1 { background: #ef4444; }
.strength-bar.s2 { background: #f97316; }
.strength-bar.s3 { background: #eab308; }
.strength-bar.s4 { background: #22c55e; }
.strength-lbl { font-size: .65rem; font-weight: 600; color: var(--ink-ghost); }

/* Submit */
.btn-reg {
    width: 100%; padding: 14px;
    background: var(--forest); color: #fff; border: none;
    border-radius: var(--r-sm);
    font-family: var(--font-display);
    font-size: .95rem; font-weight: 700;
    letter-spacing: .01em; cursor: pointer;
    position: relative; overflow: hidden;
    transition: background .25s, transform .25s var(--spring), box-shadow .25s;
    box-shadow: 0 4px 16px rgba(26,51,37,.18);
    margin-top: .375rem;
}
.btn-reg::before {
    content: '';
    position: absolute; top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);
    transition: left .5s ease;
}
.btn-reg:hover { background: var(--forest-mid); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(26,51,37,.24); }
.btn-reg:hover::before { left: 100%; }
.btn-reg:active { transform: translateY(0); }

.divider {
    display: flex; align-items: center; gap: 12px;
    margin: 1.5rem 0 1.25rem;
}
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border-md); }
.divider span { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-ghost); }

.login-cta {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    text-decoration: none; padding: 13px;
    background: var(--cream); border: 1.5px solid var(--border-md);
    border-radius: var(--r-sm);
    font-size: .875rem; font-weight: 600; color: var(--ink-mid);
    transition: all .25s var(--ease);
}
.login-cta:hover {
    background: var(--forest-pale); border-color: var(--forest);
    color: var(--forest); transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(26,51,37,.06);
}
.login-cta svg { width: 14px; height: 14px; transition: transform .3s var(--spring); }
.login-cta:hover svg { transform: translateX(5px); }

/* ── Brand panel (right) ── */
.brand {
    flex: 1;
    background: var(--forest-deep);
    position: relative; overflow: hidden;
    display: flex; flex-direction: column;
    justify-content: space-between;
    padding: 3rem;
}
.brand-blob {
    position: absolute; border-radius: 50%;
    filter: blur(55px); pointer-events: none;
}
.brand-blob-1 {
    width: 420px; height: 420px;
    background: radial-gradient(circle, rgba(184,137,42,.2) 0%, transparent 70%);
    top: -100px; left: -80px;
    animation: drift1 14s ease-in-out infinite alternate;
}
.brand-blob-2 {
    width: 320px; height: 320px;
    background: radial-gradient(circle, rgba(42,80,56,.7) 0%, transparent 70%);
    bottom: -80px; right: -60px;
    animation: drift2 10s ease-in-out infinite alternate;
}
@keyframes drift1 { to { transform: translate(15px, 20px) scale(1.06); } }
@keyframes drift2 { to { transform: translate(-12px, -18px) scale(1.04); } }
.brand-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
    background-size: 22px 22px; pointer-events: none;
}

.brand-top { position: relative; z-index: 2; animation: fadeUp .7s var(--ease) .1s both; }
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
.brand-logo-mark:hover { background: rgba(255,255,255,.14); transform: rotate(-6deg) scale(1.08); }
.brand-logo-mark svg { width: 22px; height: 22px; color: #fff; }
.brand-logo-text { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: #fff; }

.brand-center { position: relative; z-index: 2; animation: fadeUp .7s var(--ease) .2s both; }
.brand-tagline {
    font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .14em;
    color: var(--gold); margin-bottom: 1rem;
}
.brand-headline {
    font-family: var(--font-display);
    font-size: clamp(2rem, 3vw, 3rem);
    font-weight: 800; color: #fff;
    line-height: 1.08; letter-spacing: -.025em;
    margin-bottom: 1.5rem;
}
.brand-headline em { font-style: italic; color: var(--gold); }
.brand-desc { font-size: .9rem; color: rgba(255,255,255,.5); line-height: 1.8; max-width: 300px; }

.brand-bottom { position: relative; z-index: 2; animation: fadeUp .7s var(--ease) .35s both; }
.steps { display: flex; flex-direction: column; gap: .875rem; }
.step { display: flex; align-items: flex-start; gap: .875rem; }
.step-num {
    width: 30px; height: 30px; flex-shrink: 0;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 700; color: var(--gold);
}
.step-info { padding-top: 4px; }
.step-title { font-size: .82rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
.step-desc { font-size: .72rem; color: rgba(255,255,255,.42); }

/* Toast */
#toasts {
    position: fixed; bottom: 1.5rem; right: 1.5rem;
    z-index: 9999; display: flex; flex-direction: column; gap: 8px;
    pointer-events: none;
}
.toast {
    padding: 10px 17px; border-radius: var(--r-sm);
    color: #fff; font-size: .8rem; font-weight: 600;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    animation: tin .28s var(--ease) forwards, tout .28s var(--ease) 3.2s forwards;
}
.toast-ok  { background: var(--forest); }
.toast-err { background: var(--red); }
@keyframes tin  { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
@keyframes tout { to   { opacity:0; transform:translateY(12px); } }

/* Animations */
@keyframes cardIn {
    from { opacity:0; transform: translateY(24px) scale(.97); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}
@keyframes fadeUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes shake {
    0%,100% { transform:translateX(0); }
    20%,60%  { transform:translateX(-5px); }
    40%,80%  { transform:translateX(5px); }
}

/* Responsive */
@media (max-width: 900px) {
    body { flex-direction: column; overflow: auto; }
    .brand { order: 1; flex: none; padding: 2rem 1.5rem 2.5rem; }
    .form-side { order: 2; background-image: none; padding: 2rem 1.25rem 3rem; }
    .form-card { background: var(--white); border: none; box-shadow: none; }
}
</style>
</head>
<body>

<!-- Form Panel -->
<div class="form-side">
    <div class="form-card">
        <div class="fc-header">
            <div class="fc-header-eyebrow">Join the community</div>
            <h1>Create your account</h1>
            <p>Start buying and selling in minutes — it's free</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
        <div class="err-box">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p><?php echo htmlspecialchars($_GET['error']); ?></p>
        </div>
        <?php endif; ?>

        <form action="../controllers/auth.php" method="POST" id="regForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']??''); ?>">

            <div class="fg">
                <label for="name">Username</label>
                <div class="inp-wrap">
                    <input type="text" id="name" name="name" required placeholder="Choose a username"
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($old['name']??''); ?>">
                    <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>

            <div class="fg">
                <div class="row-2">
                    <div>
                        <label for="first_name">First Name</label>
                        <div class="inp-wrap">
                            <input type="text" id="first_name" name="first_name" required placeholder="First"
                                   value="<?php echo htmlspecialchars($old['first_name']??''); ?>">
                            <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label for="last_name">Last Name</label>
                        <div class="inp-wrap">
                            <input type="text" id="last_name" name="last_name" required placeholder="Last"
                                   value="<?php echo htmlspecialchars($old['last_name']??''); ?>">
                            <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fg">
                <label for="email">Email Address</label>
                <div class="inp-wrap">
                    <input type="email" id="email" name="email" required placeholder="you@example.com"
                           autocomplete="email"
                           value="<?php echo htmlspecialchars($old['email']??''); ?>">
                    <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            <div class="fg">
                <label for="password">Password</label>
                <div class="inp-wrap">
                    <input type="password" id="password" name="password" required minlength="6"
                           placeholder="Create a strong password" autocomplete="new-password"
                           oninput="checkStrength(this.value)">
                    <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <button type="button" class="eye-btn" onclick="togglePass('password','e1','e1off')">
                        <svg id="e1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg id="e1off" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
                <div class="strength-wrap" id="strengthWrap">
                    <div class="strength-bars">
                        <div class="strength-bar" id="sb1"></div>
                        <div class="strength-bar" id="sb2"></div>
                        <div class="strength-bar" id="sb3"></div>
                        <div class="strength-bar" id="sb4"></div>
                    </div>
                    <span class="strength-lbl" id="sLabel">Enter at least 6 characters</span>
                </div>
            </div>

            <div class="fg">
                <label for="confirm_password">Confirm Password</label>
                <div class="inp-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Repeat your password" autocomplete="new-password">
                    <svg class="inp-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <button type="button" class="eye-btn" onclick="togglePass('confirm_password','e2','e2off')">
                        <svg id="e2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg id="e2off" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" name="register" class="btn-reg">Create Account</button>
        </form>

        <div class="divider"><span>Already a member?</span></div>
        <a href="login.php" class="login-cta">
            Sign in to your account
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>
</div>

<!-- Brand Panel -->
<div class="brand">
    <div class="brand-blob brand-blob-1"></div>
    <div class="brand-blob brand-blob-2"></div>
    <div class="brand-dots"></div>

    <div class="brand-top">
        <a href="home.php" class="brand-logo">
            <div class="brand-logo-mark">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
            </div>
            <span class="brand-logo-text">Sana</span>
        </a>
    </div>

    <div class="brand-center">
        <div class="brand-tagline">Get started today</div>
        <h2 class="brand-headline">Start selling<br>in <em>minutes.</em></h2>
        <p class="brand-desc">Join a growing community of trusted buyers and sellers. Free, safe, and easy to get going.</p>
    </div>

    <div class="brand-bottom">
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-info">
                    <div class="step-title">Create your account</div>
                    <div class="step-desc">Fill out the form — takes under a minute</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-info">
                    <div class="step-title">List your first product</div>
                    <div class="step-desc">Add photos, set a price, and go live</div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-info">
                    <div class="step-title">Connect with buyers</div>
                    <div class="step-desc">Chat, negotiate, and close deals confidently</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toasts"></div>

<?php if(isset($_GET['success'])): ?>
<script>window.addEventListener('load',()=>showToast('<?php echo htmlspecialchars($_GET['success']); ?>','ok'));</script>
<?php endif; ?>

<script>
function togglePass(id, onId, offId) {
    const inp = document.getElementById(id);
    const on  = document.getElementById(onId);
    const off = document.getElementById(offId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    on.style.display  = inp.type === 'password' ? 'block' : 'none';
    off.style.display = inp.type === 'text'     ? 'block' : 'none';
}
function checkStrength(val) {
    const wrap  = document.getElementById('strengthWrap');
    const bars  = ['sb1','sb2','sb3','sb4'].map(id => document.getElementById(id));
    const label = document.getElementById('sLabel');
    if (!val) { wrap.classList.remove('show'); return; }
    wrap.classList.add('show');
    let s = 0;
    if (val.length >= 6)  s++;
    if (val.length >= 10) s++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    bars.forEach((b,i) => { b.className = 'strength-bar'; if(i < s) b.classList.add('s'+s); });
    label.textContent = ['Enter at least 6 characters','Too short','Weak','Good','Strong'][s] || 'Strong';
}
function showToast(msg, type='ok') {
    const c = document.getElementById('toasts');
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3800);
}
</script>
</body>
</html>