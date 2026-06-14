<?php
/**
 * User Profile Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php?from=profile.php');
    exit;
}

$pageTitle = "Menha Boutique - My Profile";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'My Profile';
$pageTopbarSubtitle = 'Manage your account and preferences';
$pageTopbarBack     = 'index.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<style>
    body { background: #f5f7f6; }

    /* Hero card */
    .profile-hero {
        position: relative;
        margin: 1.25rem auto 1.75rem;
        max-width: 920px;
        background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
        border-radius: 24px;
        padding: 2rem 2rem 2.25rem;
        color: #fff;
        box-shadow: 0 10px 30px rgba(0, 77, 64, 0.18);
        overflow: hidden;
    }
    .profile-hero::before,
    .profile-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        pointer-events: none;
    }
    .profile-hero::before {
        width: 200px; height: 200px;
        top: -60px; right: -60px;
    }
    .profile-hero::after {
        width: 140px; height: 140px;
        bottom: -50px; left: -30px;
    }
    .hero-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    .profile-avatar {
        width: 84px; height: 84px;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        border: 3px solid rgba(255, 255, 255, 0.45);
        backdrop-filter: blur(8px);
        flex-shrink: 0;
    }
    .hero-info { flex: 1; min-width: 0; }
    .hero-info h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 4px;
        line-height: 1.2;
    }
    .hero-info .email {
        font-size: 0.92rem;
        opacity: 0.85;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.18);
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-top: 8px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .role-chip.admin {
        background: rgba(255, 179, 0, 0.92);
        color: var(--color-primary-dark);
        border-color: rgba(255, 179, 0, 0.7);
    }
    .role-chip i { width: 12px; height: 12px; }

    /* Quick stats */
    .stats-row {
        max-width: 920px;
        margin: 0 auto 1.75rem;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    .stat-tile {
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        text-align: center;
        transition: 0.2s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }
    .stat-tile:hover {
        transform: translateY(-2px);
        border-color: var(--color-primary);
        box-shadow: 0 6px 16px rgba(0, 77, 64, 0.08);
    }
    .stat-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: rgba(0, 77, 64, 0.08);
        color: var(--color-primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .stat-icon i { width: 18px; height: 18px; }
    .stat-value {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        line-height: 1;
    }
    .stat-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--color-text-light);
        font-weight: 600;
    }

    /* Menu */
    .section-label {
        max-width: 920px;
        margin: 0 auto 0.75rem;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: var(--color-text-light);
        padding: 0 4px;
    }
    .menu-list {
        max-width: 920px;
        margin: 0 auto 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .menu-card {
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        color: var(--color-primary-dark);
        transition: 0.2s;
    }
    .menu-card:hover {
        border-color: var(--color-primary);
        box-shadow: 0 4px 12px rgba(0, 77, 64, 0.06);
        transform: translateX(2px);
    }
    .menu-card .menu-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        background: rgba(0, 77, 64, 0.08);
        color: var(--color-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .menu-card .menu-icon i { width: 20px; height: 20px; }
    .menu-card .menu-text { flex: 1; min-width: 0; }
    .menu-card .menu-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--color-primary-dark);
        margin: 0 0 2px;
    }
    .menu-card .menu-desc {
        font-size: 0.8rem;
        color: var(--color-text-light);
        margin: 0;
        line-height: 1.4;
    }
    .menu-card .menu-arrow {
        color: #cbd5e0;
        flex-shrink: 0;
        transition: 0.2s;
    }
    .menu-card:hover .menu-arrow {
        color: var(--color-primary);
        transform: translateX(2px);
    }

    .menu-card.admin {
        background: linear-gradient(135deg, #fffaeb, #fff8e1);
        border-color: #fde68a;
    }
    .menu-card.admin .menu-icon {
        background: rgba(255, 179, 0, 0.15);
        color: #b45309;
    }
    .menu-card.admin .menu-title { color: #92400e; }

    /* Logout */
    .logout-row {
        max-width: 920px;
        margin: 0 auto 3rem;
        padding: 0;
    }
    .logout-btn {
        width: 100%;
        background: #fff;
        border: 1px solid #fecaca;
        color: #b91c1c;
        padding: 14px 16px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: 0.2s;
        font-family: inherit;
    }
    .logout-btn:hover {
        background: #fef2f2;
        border-color: #f87171;
        color: #991b1b;
    }
    .logout-btn i { width: 18px; height: 18px; }

    @media (max-width: 600px) {
        .profile-hero { padding: 1.5rem 1.25rem 1.75rem; margin: 1rem; border-radius: 20px; }
        .profile-avatar { width: 68px; height: 68px; font-size: 1.6rem; border-width: 2px; }
        .hero-info h1 { font-size: 1.2rem; }
        .stats-row, .menu-list, .logout-row, .section-label { margin-left: 1rem; margin-right: 1rem; }
        .stat-value { font-size: 1.1rem; }
        .stat-label { font-size: 0.65rem; }
    }
</style>

<main class="container" style="max-width: 960px; padding-top: 1rem; padding-bottom: 4rem;">
    <!-- Hero -->
    <section class="profile-hero">
        <div class="hero-row">
            <div class="profile-avatar" id="profile-initials">U</div>
            <div class="hero-info">
                <h1 id="profile-name">Loading…</h1>
                <div class="email"><i data-lucide="mail" style="width:14px;height:14px;"></i> <span id="profile-email">…</span></div>
                <span id="role-chip" class="role-chip"><i data-lucide="user"></i> Customer</span>
            </div>
        </div>
    </section>

    <!-- Quick stats -->
    <div class="section-label">Overview</div>
    <div class="stats-row">
        <a href="orders.php" class="stat-tile">
            <span class="stat-icon"><i data-lucide="package"></i></span>
            <span class="stat-value" id="stat-orders">—</span>
            <span class="stat-label">Orders</span>
        </a>
        <a href="cart.php" class="stat-tile">
            <span class="stat-icon"><i data-lucide="shopping-bag"></i></span>
            <span class="stat-value" id="stat-cart">—</span>
            <span class="stat-label">Cart Items</span>
        </a>
        <a href="addresses.php" class="stat-tile">
            <span class="stat-icon"><i data-lucide="map-pin"></i></span>
            <span class="stat-value" id="stat-addresses">—</span>
            <span class="stat-label">Addresses</span>
        </a>
    </div>

    <!-- Menu -->
    <div class="section-label">Account</div>
    <div class="menu-list">
        <a href="orders.php" class="menu-card">
            <span class="menu-icon"><i data-lucide="package"></i></span>
            <div class="menu-text">
                <h3 class="menu-title">My Orders</h3>
                <p class="menu-desc">Track and review past purchases</p>
            </div>
            <i data-lucide="chevron-right" class="menu-arrow"></i>
        </a>
        <a href="cart.php" class="menu-card">
            <span class="menu-icon"><i data-lucide="shopping-bag"></i></span>
            <div class="menu-text">
                <h3 class="menu-title">My Cart</h3>
                <p class="menu-desc">View and checkout items in your cart</p>
            </div>
            <i data-lucide="chevron-right" class="menu-arrow"></i>
        </a>
        <a href="addresses.php" class="menu-card">
            <span class="menu-icon"><i data-lucide="map-pin"></i></span>
            <div class="menu-text">
                <h3 class="menu-title">My Addresses</h3>
                <p class="menu-desc">Manage delivery addresses</p>
            </div>
            <i data-lucide="chevron-right" class="menu-arrow"></i>
        </a>
        <a href="admin.php" id="admin-menu" class="menu-card admin" style="display: none;">
            <span class="menu-icon"><i data-lucide="shield-check"></i></span>
            <div class="menu-text">
                <h3 class="menu-title">Admin Panel</h3>
                <p class="menu-desc">Manage products, orders, and settings</p>
            </div>
            <i data-lucide="chevron-right" class="menu-arrow"></i>
        </a>
    </div>

    <!-- Logout -->
    <div class="logout-row">
        <button type="button" class="logout-btn" id="logout-btn">
            <i data-lucide="log-out"></i> Sign Out
        </button>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const user = window.MB_AUTH && window.MB_AUTH.user ? window.MB_AUTH.user : {};

        // Profile info
        const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
        document.getElementById('profile-name').textContent = fullName;
        document.getElementById('profile-email').textContent = user.email || '';
        document.getElementById('profile-initials').textContent = (user.first_name || user.email || 'U').charAt(0).toUpperCase();

        // Role chip
        const roleChip = document.getElementById('role-chip');
        if (user.role === 'admin') {
            roleChip.classList.add('admin');
            roleChip.innerHTML = '<i data-lucide="shield-check"></i> Admin';
            document.getElementById('admin-menu').style.display = 'flex';
        } else {
            roleChip.innerHTML = '<i data-lucide="user"></i> Customer';
        }

        // Load stats
        try {
            const [orders, addresses] = await Promise.all([
                MainAPI.getOrders(),
                MainAPI.getUserAddresses()
            ]);

            document.getElementById('stat-orders').textContent = orders.length;
            document.getElementById('stat-cart').textContent = CartManager.getTotalItems();
            document.getElementById('stat-addresses').textContent = addresses.length;
        } catch (e) {
            document.getElementById('stat-orders').textContent = '0';
            document.getElementById('stat-cart').textContent = CartManager.getTotalItems() || '0';
            document.getElementById('stat-addresses').textContent = '0';
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Logout
        document.getElementById('logout-btn').addEventListener('click', () => {
            if (confirm('Sign out of your account?')) {
                MainAPI.logout();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
