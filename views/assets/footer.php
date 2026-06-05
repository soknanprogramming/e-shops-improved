<footer class="site-footer">
    <div class="footer-inner">

        <!-- Top section -->
        <div class="footer-top">

            <!-- Brand col -->
            <div class="footer-col footer-brand-col">
                <a href="home.php" class="footer-logo">
                    <span class="footer-logo-mark">S</span>
                    <span class="footer-logo-text">Sana</span>
                </a>
                <p class="footer-tagline">Your trusted marketplace for buying and selling locally. New listings every day.</p>
                <div class="footer-social">
                    <!-- Telegram -->
                    <a href="#" class="social-btn" aria-label="Telegram">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/></svg>
                    </a>
                    <!-- Facebook -->
                    <a href="#" class="social-btn" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                    </a>
                    <!-- Instagram -->
                    <a href="#" class="social-btn" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Explore col -->
            <div class="footer-col">
                <h4 class="footer-col-title">Explore</h4>
                <ul class="footer-links">
                    <li><a href="home.php">All Products</a></li>
                    <?php if (isset($categories) && !empty($categories)):
                        $footerCats = array_slice($categories, 0, 5);
                        foreach ($footerCats as $fc): ?>
                        <li><a href="home.php?category_id=<?php echo $fc['id']; ?>"><?php echo htmlspecialchars($fc['name']); ?></a></li>
                    <?php endforeach; endif; ?>
                    <li><a href="home.php?sort=newest">New Arrivals</a></li>
                </ul>
            </div>

            <!-- Account col -->
            <div class="footer-col">
                <h4 class="footer-col-title">Account</h4>
                <ul class="footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="user_dashboard.php">My Dashboard</a></li>
                        <li><a href="home.php?liked_only=1">Saved Items</a></li>
                        <li><a href="product_create.php">Post a Product</a></li>
                        <li><a href="user_profile.php">My Profile</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Log In</a></li>
                        <li><a href="register.php">Create Account</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Newsletter col -->
            <div class="footer-col footer-newsletter-col">
                <h4 class="footer-col-title">Stay Updated</h4>
                <p class="footer-newsletter-desc">Get notified about new listings and deals in your area.</p>
                <form class="footer-newsletter-form" onsubmit="handleNewsletterSubmit(event)">
                    <input type="email" placeholder="your@email.com" class="footer-input" required>
                    <button type="submit" class="footer-subscribe-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </button>
                </form>
                <p class="footer-newsletter-note">No spam. Unsubscribe anytime.</p>
            </div>

        </div><!-- /footer-top -->

        <!-- Divider -->
        <div class="footer-divider"></div>

        <!-- Bottom bar -->
        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; <?php echo date('Y'); ?> <strong>Sana Marketplace</strong>. All rights reserved.
            </p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <span class="footer-dot">·</span>
                <a href="#">Terms of Use</a>
                <span class="footer-dot">·</span>
                <a href="#">Contact</a>
            </div>
            <div class="footer-made">
                Made with <span class="footer-heart">♥</span> in Cambodia
            </div>
        </div>

    </div>
</footer>

<style>
/* ═══════════════════════════════════════════
   SANA FOOTER — matches home.php design system
   (Playfair Display + Plus Jakarta Sans)
═══════════════════════════════════════════ */
.site-footer {
    background: linear-gradient(160deg, #0f1e15 0%, #1a3325 60%, #0d1a10 100%);
    color: rgba(255,255,255,.75);
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    margin-top: 5rem;
    position: relative;
    overflow: hidden;
}

/* Decorative top edge */
.site-footer::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg,
        transparent 0%,
        #c49a3c 20%,
        #e0c55a 50%,
        #c49a3c 80%,
        transparent 100%);
}

/* Ambient glow */
.site-footer::after {
    content: '';
    position: absolute;
    top: -120px; right: -80px;
    width: 420px; height: 420px;
    background: radial-gradient(circle, rgba(196,154,60,.07) 0%, transparent 70%);
    pointer-events: none;
}

.footer-inner {
    max-width: 1480px;
    margin: 0 auto;
    padding: 4rem 4rem 2rem;
    position: relative; z-index: 1;
}
@media (max-width:1200px) { .footer-inner { padding: 3.5rem 2.5rem 2rem; } }
@media (max-width:768px)  { .footer-inner { padding: 2.5rem 1.25rem 1.5rem; } }

/* ── Top grid ── */
.footer-top {
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1.5fr;
    gap: 3rem;
    margin-bottom: 3rem;
}
@media (max-width:1100px) { .footer-top { grid-template-columns: 1.4fr 1fr 1fr; } .footer-newsletter-col { grid-column: 1 / -1; max-width: 480px; } }
@media (max-width:680px)  { .footer-top { grid-template-columns: 1fr 1fr; gap: 2rem; } .footer-brand-col { grid-column: 1 / -1; } .footer-newsletter-col { grid-column: 1 / -1; max-width: 100%; } }
@media (max-width:420px)  { .footer-top { grid-template-columns: 1fr; } }

/* ── Brand col ── */
.footer-logo {
    display: inline-flex; align-items: center; gap: 10px;
    text-decoration: none; margin-bottom: 1rem;
}
.footer-logo-mark {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #c49a3c 0%, #e0c55a 100%);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.2rem; font-weight: 800;
    color: #0f1e15;
    box-shadow: 0 4px 12px rgba(196,154,60,.3);
    flex-shrink: 0;
}
.footer-logo-text {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.5rem; font-weight: 800;
    color: #fff; letter-spacing: -.02em;
}

.footer-tagline {
    font-size: .84rem;
    color: rgba(255,255,255,.45);
    line-height: 1.75;
    max-width: 280px;
    margin-bottom: 1.5rem;
}

/* Social buttons */
.footer-social { display: flex; gap: 8px; }
.social-btn {
    width: 36px; height: 36px; border-radius: 9px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.55);
    text-decoration: none;
    transition: all .22s cubic-bezier(.16,1,.3,1);
}
.social-btn svg { width: 16px; height: 16px; }
.social-btn:hover {
    background: rgba(196,154,60,.15);
    border-color: rgba(196,154,60,.3);
    color: #c49a3c;
    transform: translateY(-2px);
}

/* ── Column titles ── */
.footer-col-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: rgba(255,255,255,.9);
    margin: 0 0 1.125rem;
    padding-bottom: .625rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    position: relative;
}
.footer-col-title::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0;
    width: 28px; height: 2px;
    background: #c49a3c;
    border-radius: 2px;
}

/* ── Links ── */
.footer-links {
    list-style: none; margin: 0; padding: 0;
    display: flex; flex-direction: column; gap: .5rem;
}
.footer-links li a {
    font-size: .84rem;
    color: rgba(255,255,255,.45);
    text-decoration: none;
    transition: all .18s;
    display: inline-flex; align-items: center; gap: 5px;
}
.footer-links li a:hover {
    color: rgba(255,255,255,.9);
    padding-left: 4px;
}

/* ── Newsletter ── */
.footer-newsletter-desc {
    font-size: .82rem;
    color: rgba(255,255,255,.42);
    line-height: 1.65;
    margin-bottom: 1rem;
}
.footer-newsletter-form {
    display: flex; gap: 0;
    background: rgba(255,255,255,.06);
    border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
    margin-bottom: .625rem;
}
.footer-newsletter-form:focus-within {
    border-color: rgba(196,154,60,.4);
    box-shadow: 0 0 0 3px rgba(196,154,60,.07);
}
.footer-input {
    flex: 1; padding: 11px 15px;
    background: transparent; border: none;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    font-size: .84rem;
    color: rgba(255,255,255,.85);
    outline: none;
}
.footer-input::placeholder { color: rgba(255,255,255,.25); }
.footer-subscribe-btn {
    padding: 0 16px;
    background: linear-gradient(135deg, #c49a3c 0%, #e0c55a 100%);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #0f1e15;
    transition: opacity .2s, transform .15s;
    flex-shrink: 0;
}
.footer-subscribe-btn svg { width: 16px; height: 16px; }
.footer-subscribe-btn:hover { opacity: .88; transform: scale(1.05); }

.footer-newsletter-note {
    font-size: .7rem;
    color: rgba(255,255,255,.25);
}

/* ── Divider ── */
.footer-divider {
    height: 1px;
    background: rgba(255,255,255,.07);
    margin-bottom: 1.75rem;
}

/* ── Bottom bar ── */
.footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .875rem;
}
.footer-copy {
    font-size: .78rem;
    color: rgba(255,255,255,.32);
}
.footer-copy strong { color: rgba(255,255,255,.55); font-weight: 700; }

.footer-bottom-links {
    display: flex; align-items: center; gap: .625rem;
}
.footer-bottom-links a {
    font-size: .76rem;
    color: rgba(255,255,255,.32);
    text-decoration: none;
    transition: color .18s;
}
.footer-bottom-links a:hover { color: rgba(255,255,255,.7); }
.footer-dot { color: rgba(255,255,255,.18); font-size: .7rem; }

.footer-made {
    font-size: .76rem;
    color: rgba(255,255,255,.25);
    display: flex; align-items: center; gap: 4px;
}
.footer-heart {
    color: #c0392b;
    animation: heartbeat 1.4s ease-in-out infinite;
    display: inline-block;
}
@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    14% { transform: scale(1.25); }
    28% { transform: scale(1); }
    42% { transform: scale(1.15); }
    56% { transform: scale(1); }
}

@media (max-width:680px) {
    .footer-bottom { flex-direction: column; align-items: flex-start; gap: .5rem; }
    .footer-bottom-links { flex-wrap: wrap; }
}

/* Newsletter toast feedback */
.footer-toast-msg {
    font-size: .78rem;
    color: #e0c55a;
    margin-top: .25rem;
    display: none;
    animation: fadeIn .3s ease;
}
@keyframes fadeIn { from{opacity:0;transform:translateY(4px);} to{opacity:1;transform:none;} }
</style>

<script>
function handleNewsletterSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const input = form.querySelector('.footer-input');
    input.value = '';
    // Show inline feedback
    let msg = form.parentElement.querySelector('.footer-toast-msg');
    if (!msg) {
        msg = document.createElement('p');
        msg.className = 'footer-toast-msg';
        msg.textContent = '✓ You\'re subscribed! We\'ll keep you posted.';
        form.after(msg);
    }
    msg.style.display = 'block';
    setTimeout(() => { msg.style.display = 'none'; }, 4000);
}
</script>
