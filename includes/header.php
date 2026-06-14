<?php
/**
 * Header template - Menha Boutique PHP
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = isset($pageTitle) ? $pageTitle : "Menha Boutique - Shopping";

// Resolve cart count from DB / guest session — rendered server-side so badge is correct on first paint
$_cartCount = 0;
try {
    if (isLoggedIn()) {
        $_u  = getCurrentUser();
        $_db = getDBConnection();
        // Use first active cart only — same as cart.php — so badge count always matches
        $_st = $_db->prepare("SELECT id FROM carts WHERE user_id=? AND status='active' ORDER BY created_at ASC LIMIT 1");
        $_st->execute([$_u['id']]);
        $_cid = $_st->fetchColumn();
        if ($_cid) {
            $_st2 = $_db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE cart_id=?");
            $_st2->execute([$_cid]);
            $_cartCount = (int)$_st2->fetchColumn();
        }
    } elseif (!empty($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $_gi) $_cartCount += (int)($_gi['quantity'] ?? 1);
    }
} catch (Exception $_e) { $_cartCount = 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.407.0/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=3.4.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>

    <!-- Page Loader -->
    <div id="page-loader">
        <div class="loader-inner">
            <div class="loader-logo-ring">
                <img src="assets/images/logo.jpg" alt="Menha Boutique" class="loader-logo-img">
            </div>
        </div>
    </div>
    <style>
        #page-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(255,255,255,0.92);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.45s ease, visibility 0.45s ease;
        }
        #page-loader.loader-hidden {
            opacity: 0;
            visibility: hidden;
        }
        .loader-inner {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .loader-logo-ring {
            position: relative;
            width: 72px;
            height: 72px;
        }
        .loader-logo-ring::before {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #004D40;
            border-right-color: #C9A84C;
            animation: loaderSpin 0.9s linear infinite;
        }
        .loader-logo-img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        @keyframes loaderSpin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            var loader = document.getElementById('page-loader');
            if (!loader) return;
            var elapsed = Date.now() - window._loaderStart;
            var delay = Math.max(0, 500 - elapsed);
            setTimeout(function () {
                loader.classList.add('loader-hidden');
                setTimeout(function () { loader.remove(); }, 460);
            }, delay);
        });
        window._loaderStart = Date.now();
    </script>

    <!-- PHP-injected auth state — JS reads this, never localStorage -->
    <script>
    window.MB_AUTH = {
        isLoggedIn: <?php echo isLoggedIn() ? 'true' : 'false'; ?>,
        user: <?php echo isLoggedIn() ? (json_encode(getCurrentUser(), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?: 'null') : 'null'; ?>
    };
    </script>

    <!-- Mobile Drawer (server-side rendered — auth state is always correct) -->
    <div id="mobile-drawer-backdrop" class="mobile-drawer-backdrop" onclick="toggleMobileDrawer(false)"></div>
    <aside id="mobile-drawer" class="mobile-drawer">
        <div class="mobile-drawer-header">
            <span class="brand-text">Menha Boutique</span>
            <button type="button" class="mobile-drawer-close" aria-label="Close menu" onclick="toggleMobileDrawer(false)">
                <i data-lucide="x"></i>
            </button>
        </div>
        <nav class="mobile-drawer-nav" id="mobile-drawer-nav">
            <a href="index.php"><i data-lucide="home"></i> Home</a>
            <a href="products.php"><i data-lucide="grid"></i> All Products</a>
            <a href="categories.php"><i data-lucide="layers"></i> Categories</a>
            <a href="cart.php"><i data-lucide="shopping-bag"></i> Cart</a>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php"><i data-lucide="user"></i> My Profile</a>
                <a href="orders.php"><i data-lucide="package"></i> My Orders</a>
                <a href="addresses.php"><i data-lucide="map-pin"></i> Addresses</a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php"><i data-lucide="shield"></i> Admin Portal</a>
                <?php endif; ?>
                <a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i data-lucide="log-in"></i> Login / Sign Up</a>
            <?php endif; ?>
        </nav>
        <?php if (isLoggedIn()): $u = getCurrentUser(); ?>
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--color-border-light);display:flex;align-items:center;gap:12px;background:var(--color-off-white);margin-top:auto;">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;flex-shrink:0;">
                <?php echo strtoupper(substr($u['first_name'] ?? 'U', 0, 1)); ?>
            </div>
            <div style="min-width:0;">
                <p style="font-weight:600;font-size:0.9rem;color:var(--color-text);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))); ?></p>
                <p style="font-size:0.78rem;color:var(--color-text-muted);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </aside>
    <script>
    // Highlight the current page in the drawer
    (function () {
        var path = (location.pathname.split('/').pop() || 'index.php').toLowerCase();
        document.querySelectorAll('#mobile-drawer-nav a').forEach(function (a) {
            if ((a.getAttribute('href') || '').toLowerCase() === path) a.classList.add('active');
        });
    })();
    </script>

    <!-- Header -->
    <header class="header" id="header">
        <div class="container header-container">

            <!-- Left: hamburger + products icon (desktop) -->
            <div class="header-left">
                <button type="button" class="mobile-menu-btn" aria-label="Open menu" onclick="toggleMobileDrawer(true)">
                    <i data-lucide="menu"></i>
                </button>
                <a href="products.php" class="action-btn tooltip desktop-only" title="Products">
                    <i data-lucide="grid"></i>
                </a>
            </div>

            <!-- Center: brand -->
            <div class="header-center">
                <a href="index.php" class="brand">
                    <img src="assets/images/logo.jpg" alt="Menha Boutique" class="logo-img">
                    <span class="brand-text">Menha Boutique</span>
                </a>
            </div>

            <!-- Right: profile + cart -->
            <div class="header-right">
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="action-btn tooltip" title="Profile">
                        <i data-lucide="user"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="action-btn tooltip" title="Login">
                        <i data-lucide="user"></i>
                    </a>
                <?php endif; ?>
                <div class="cart-container">
                    <a href="cart.php" class="action-btn" id="cart-btn">
                        <i data-lucide="shopping-bag"></i>
                        <span class="badge" id="cart-badge" <?php echo $_cartCount > 0 ? '' : 'style="display:none;"'; ?>><?php echo $_cartCount; ?></span>
                    </a>
                </div>
            </div>

        </div>
    </header>

    <script>
        // Scroll → add .scrolled class for elevated header effect
        (function () {
            var header = document.getElementById('header');
            if (!header) return;
            function onScroll() {
                header.classList.toggle('scrolled', window.scrollY > 10);
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();
    </script>
