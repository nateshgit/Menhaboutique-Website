<?php
/**
 * Admin Panel - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menha Admin Panel</title>
<link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.407.0/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="assets/css/style.css?v=3.4.0">
<link rel="stylesheet" href="assets/css/admin.css?v=4.0.0">
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="admin-body">
<script>
window.MB_AUTH = {
    isLoggedIn: true,
    user: <?php echo json_encode(getCurrentUser(), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?: 'null'; ?>
};
</script>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
  <nav class="sidebar-nav">
    <span class="sidebar-section-label">Overview</span>
    <a href="#" class="sidebar-link active" data-tab="dashboard"><i data-lucide="layout-dashboard"></i> Dashboard</a>
    <span class="sidebar-section-label">Catalogue</span>
    <a href="#" class="sidebar-link" data-tab="products"><i data-lucide="package"></i> Products <span class="sb-badge" id="badge-products">—</span></a>
    <a href="#" class="sidebar-link" data-tab="categories"><i data-lucide="grid"></i> Categories</a>
    <a href="#" class="sidebar-link" data-tab="brands"><i data-lucide="award"></i> Brands <span class="sb-badge" id="badge-brands">—</span></a>
    <a href="#" class="sidebar-link" data-tab="banners"><i data-lucide="image"></i> Banners</a>
    <a href="#" class="sidebar-link" data-tab="reviews"><i data-lucide="message-square"></i> Reviews</a>
    <span class="sidebar-section-label">Sales</span>
    <a href="#" class="sidebar-link" data-tab="orders"><i data-lucide="shopping-cart"></i> Orders <span class="sb-badge" id="badge-orders">—</span></a>
    <a href="#" class="sidebar-link" data-tab="users"><i data-lucide="users"></i> Customers</a>
    <a href="#" class="sidebar-link" data-tab="messages"><i data-lucide="mail"></i> Messages</a>
    <span class="sidebar-section-label">Operations</span>
    <a href="#" class="sidebar-link" data-tab="delivery"><i data-lucide="truck"></i> Delivery</a>
    <a href="#" class="sidebar-link" data-tab="couriers"><i data-lucide="send"></i> Couriers</a>
    <a href="#" class="sidebar-link" data-tab="payment"><i data-lucide="credit-card"></i> Payment</a>
    <a href="#" class="sidebar-link" data-tab="locations"><i data-lucide="map-pin"></i> Locations</a>
    <a href="#" class="sidebar-link" data-tab="settings"><i data-lucide="settings"></i> Settings</a>
  </nav>
  <div class="sidebar-footer">
    <a href="index.php" class="sidebar-link"><i data-lucide="arrow-left"></i> Back to Store</a>
    <button class="sidebar-link logout-btn" onclick="handleLogout()"><i data-lucide="log-out"></i> Sign Out</button>
  </div>
</aside>

<!-- Full-width top header -->
<header class="admin-header">
  <div class="ah-left">
    <!-- Brand block (mirrors sidebar top, visible on desktop) -->
    <div class="ah-sidebar-brand">
      <img src="assets/images/logo.jpg" alt="Menha">
      <div>
        <span class="ah-brand-name">Menha Boutique</span>
        <span class="ah-brand-sub">Admin Panel</span>
      </div>
    </div>
    <!-- Hamburger for mobile -->
    <button class="ah-hamburger" onclick="toggleSidebar()"><i data-lucide="menu"></i></button>
    <!-- Breadcrumb -->
    <div class="ah-breadcrumb-area">
      <a href="index.php" class="ah-home-link">Home</a>
      <span class="ah-chevron">›</span>
      <span class="ah-page" id="admin-breadcrumb">Dashboard</span>
    </div>
  </div>
  <div class="ah-right">
    <a href="index.php" target="_blank" class="ah-store-btn">
      <i data-lucide="store"></i>
      <span>View Store</span>
    </a>
    <div class="ah-divider"></div>
    <div class="ah-user">
      <div class="ah-avatar" id="admin-avatar-initials">A</div>
      <div class="ah-user-meta">
        <span id="admin-user-name" class="ah-user-name">Admin</span>
        <span class="ah-user-role">Administrator</span>
      </div>
    </div>
    <button class="ah-logout" onclick="handleLogout()" title="Sign Out">
      <i data-lucide="log-out"></i>
    </button>
  </div>
</header>

<!-- Main -->
<div class="admin-main" id="adminMain">
  <div id="admin-content">
    <div id="admin-toast" class="admin-toast"></div>

    <!-- ── DASHBOARD ─────────────────────────────────── -->
    <section id="tab-dashboard" class="admin-tab active">
      <div class="tab-header">
        <h2>Dashboard</h2>
        <span style="font-size:0.8rem;color:var(--text-3);" id="dash-date"></span>
      </div>

      <div class="stats-grid" id="stats-grid">
        <div class="stat-card accent">
          <div class="stat-icon" style="background:rgba(201,168,76,0.1);color:var(--gold);"><i data-lucide="package"></i></div>
          <div class="stat-body">
            <div class="stat-label">Products</div>
            <div class="stat-value" id="stat-products">—</div>
            <div class="metric-trend up" id="trend-products" style="display:none;"><i data-lucide="trending-up"></i><span></span></div>
          </div>
        </div>
        <div class="stat-card success">
          <div class="stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i data-lucide="grid"></i></div>
          <div class="stat-body">
            <div class="stat-label">Categories</div>
            <div class="stat-value" id="stat-categories">—</div>
          </div>
        </div>
        <div class="stat-card warning">
          <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;"><i data-lucide="shopping-cart"></i></div>
          <div class="stat-body">
            <div class="stat-label">Orders</div>
            <div class="stat-value" id="stat-orders">—</div>
            <div class="metric-trend up" id="trend-orders" style="display:none;"><i data-lucide="trending-up"></i><span></span></div>
          </div>
        </div>
        <div class="stat-card info">
          <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;"><i data-lucide="indian-rupee"></i></div>
          <div class="stat-body">
            <div class="stat-label">Revenue</div>
            <div class="stat-value" id="stat-revenue">—</div>
          </div>
        </div>
      </div>

      <div class="dash-grid">
        <div class="admin-card">
          <div class="card-title" style="justify-content:space-between;">
            <span>Recent Orders</span>
            <a href="#" class="sidebar-link" data-tab="orders" style="font-size:0.75rem;color:var(--green);padding:3px 10px;background:rgba(0,77,64,0.07);border-radius:6px;font-weight:600;width:auto;">View All</a>
          </div>
          <div id="recent-orders-list" class="dash-list">
            <div class="loading-shimmer" style="height:40px;margin-bottom:6px;"></div>
            <div class="loading-shimmer" style="height:40px;margin-bottom:6px;"></div>
            <div class="loading-shimmer" style="height:40px;"></div>
          </div>
        </div>
        <div class="admin-card">
          <div class="card-title">Orders by Status</div>
          <div id="status-dist" class="status-pills"></div>
          <div style="margin-top:1.5rem;">
            <div class="card-title" style="margin-bottom:0.75rem;">Quick Actions</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
              <button class="quick-action-btn" onclick="document.querySelector('[data-tab=products]').click()"><i data-lucide="plus-circle"></i><span>Add Product</span></button>
              <button class="quick-action-btn" onclick="document.querySelector('[data-tab=orders]').click()"><i data-lucide="package-check"></i><span>Manage Orders</span></button>
              <button class="quick-action-btn" onclick="document.querySelector('[data-tab=banners]').click()"><i data-lucide="image-plus"></i><span>Update Banners</span></button>
              <button class="quick-action-btn" onclick="document.querySelector('[data-tab=messages]').click()"><i data-lucide="message-circle"></i><span>Messages</span></button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── PRODUCTS ──────────────────────────────────── -->
    <section id="tab-products" class="admin-tab">
      <div class="tab-header">
        <h2>Products</h2>
        <button class="btn-primary" onclick="openProductModal()"><i data-lucide="plus"></i> Add Product</button>
      </div>
      <div class="search-bar">
        <input type="text" id="product-search" placeholder="Search by title, SKU or description…" oninput="filterProducts()">
        <i data-lucide="search"></i>
      </div>
      <div class="table-filters">
        <select id="product-cat-filter" onchange="filterProducts()">
          <option value="">All Categories</option>
        </select>
        <select id="product-status-filter" onchange="filterProducts()">
          <option value="">All Status</option>
          <option value="In Stock">In Stock</option>
          <option value="Out of Stock">Out of Stock</option>
          <option value="Coming Soon">Coming Soon</option>
        </select>
        <select id="product-active-filter" onchange="filterProducts()">
          <option value="">Active &amp; Inactive</option>
          <option value="1">Active Only</option>
          <option value="0">Inactive Only</option>
        </select>
        <button type="button" class="filter-clear-btn" onclick="clearProductFilters()">Clear</button>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Image</th><th>Title</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th>Active</th><th>Actions</th></tr></thead>
          <tbody id="products-tbody"><tr><td colspan="8" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── BRANDS ──────────────────────────────────────── -->
    <section id="tab-brands" class="admin-tab">
      <div class="tab-header">
        <h2>Brands</h2>
        <button class="btn-primary" onclick="openBrandModal()"><i data-lucide="plus"></i> Add Brand</button>
      </div>
      <div class="search-bar">
        <input type="text" id="brand-search" placeholder="Search brands…" oninput="filterBrands()">
        <i data-lucide="search"></i>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Products</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody id="brands-tbody"><tr><td colspan="4" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── CATEGORIES ────────────────────────────────── -->
    <section id="tab-categories" class="admin-tab">
      <div class="tab-header">
        <h2>Categories</h2>
        <button class="btn-primary" onclick="openCategoryModal()"><i data-lucide="plus"></i> Add Category</button>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Image</th><th>Name</th><th>Slug</th><th>Sequence</th><th>Actions</th></tr></thead>
          <tbody id="categories-tbody"><tr><td colspan="5" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── ORDERS ────────────────────────────────────── -->
    <section id="tab-orders" class="admin-tab">
      <div class="tab-header">
        <h2>Orders</h2>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <div id="bulk-order-actions" style="display:none;align-items:center;gap:8px;padding:5px 12px;background:var(--surface-alt);border-radius:var(--r-md);border:1px solid var(--border);">
            <span style="font-size:0.82rem;font-weight:700;color:var(--text-2);white-space:nowrap;">Bulk:</span>
            <select id="bulk-order-status" style="padding:5px 10px;border-radius:var(--r-sm);border:1px solid var(--border);font-size:0.82rem;">
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <button class="btn-primary" style="padding:5px 12px;font-size:0.82rem;" onclick="bulkUpdateOrderStatus()">Apply</button>
          </div>
          <button class="btn-secondary" onclick="exportOrdersToExcel()"><i data-lucide="download"></i> Export</button>
          <button class="btn-primary" onclick="printSelectedOrders()"><i data-lucide="printer"></i> Print Labels</button>
        </div>
      </div>
      <div class="search-bar" style="flex-wrap:wrap;gap:8px;">
        <input type="text" id="order-search" placeholder="Search order # or email…" oninput="filterOrders()" style="flex:1;min-width:180px;">
        <div class="filter-bar">
          <span>From:</span>
          <input type="datetime-local" id="order-date-from" onchange="filterOrders()">
          <span>To:</span>
          <input type="datetime-local" id="order-date-to" onchange="filterOrders()">
          <button type="button" onclick="clearOrderDateFilter()" style="background:var(--surface-alt);border:1px solid var(--border);color:var(--text-2);font-size:0.72rem;padding:3px 9px;border-radius:var(--r-sm);cursor:pointer;">Clear</button>
        </div>
        <select id="order-status-filter" onchange="filterOrders()" style="padding:7px 12px;border-radius:var(--r-md);border:1.5px solid var(--border);font-size:0.875rem;background:var(--surface);">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:36px;"><input type="checkbox" id="order-select-all" onclick="toggleSelectAllOrders(this.checked)"></th>
              <th>Order #</th><th>Email</th><th>Items</th><th>Total</th><th>Courier</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="orders-tbody"><tr><td colspan="10" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── USERS ─────────────────────────────────────── -->
    <section id="tab-users" class="admin-tab">
      <div class="tab-header"><h2>Customers</h2></div>
      <div class="search-bar">
        <input type="text" id="user-search" placeholder="Search by name or email…" oninput="filterUsers()">
        <i data-lucide="search"></i>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody id="users-tbody"><tr><td colspan="7" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── BANNERS ───────────────────────────────────── -->
    <section id="tab-banners" class="admin-tab">
      <div class="tab-header">
        <h2>Banners</h2>
        <button class="btn-primary" onclick="openBannerModal()"><i data-lucide="plus"></i> Add Banner</button>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Preview</th><th>Title</th><th>Type</th><th>Sequence</th><th>Active</th><th>Actions</th></tr></thead>
          <tbody id="banners-tbody"><tr><td colspan="6" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── REVIEWS ─────────────────────────────────── -->
    <section id="tab-reviews" class="admin-tab">
      <div class="tab-header">
        <h2>Customer Reviews</h2>
        <button class="btn-primary" onclick="openReviewModal()"><i data-lucide="plus"></i> Add Review</button>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Media</th><th>Reviewer</th><th>Rating</th><th>Review Text</th><th>Type</th><th>Seq</th><th>Active</th><th>Actions</th></tr></thead>
          <tbody id="reviews-tbody"><tr><td colspan="8" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
      
      <div class="tab-header" style="margin-top:2.5rem; border-top:1px solid var(--color-border); padding-top:2rem;">
        <h2>Product Reviews</h2>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Product</th><th>Reviewer</th><th>Rating</th><th>Review Comment</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody id="product-reviews-tbody"><tr><td colspan="6" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── DELIVERY ──────────────────────────────────── -->
    <section id="tab-delivery" class="admin-tab">
      <div class="tab-header"><h2>Delivery Tariffs</h2><button class="btn-primary" onclick="openTariffModal()"><i data-lucide="plus"></i> Add Tariff</button></div>

      <div class="admin-card" style="margin-bottom:1.25rem;">
        <div class="card-title"><i data-lucide="settings-2"></i> Calculation Mode</div>
        <div style="display:flex;gap:1.5rem;align-items:center;padding:0.25rem 0;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:0.875rem;">
            <input type="radio" name="delivery-mode" value="WEIGHT" checked onchange="updateDeliveryMode(this.value)" style="width:auto;accent-color:var(--green);"> By Weight (grams)
          </label>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:0.875rem;">
            <input type="radio" name="delivery-mode" value="RATE" onchange="updateDeliveryMode(this.value)" style="width:auto;accent-color:var(--green);"> By Rate (Order Value)
          </label>
        </div>
        <small style="color:var(--text-3);display:block;margin-top:8px;font-size:0.78rem;">Choose how delivery charges are calculated for all orders.</small>
      </div>

      <div class="admin-card" style="margin-bottom:1.25rem;">
        <div class="card-title"><i data-lucide="layers"></i> Tariff Tiers</div>
        <div class="admin-table-wrap" style="box-shadow:none;border:1px solid var(--border);">
          <table class="admin-table">
            <thead><tr><th>Type</th><th>Max Threshold</th><th>TN</th><th>SOUTH</th><th>REST</th><th>NE</th><th>Actions</th></tr></thead>
            <tbody id="tariffs-tbody"><tr><td colspan="7" class="loading-row">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="admin-card">
        <div class="card-title"><i data-lucide="map"></i> State Delivery Zones</div>
        <div class="admin-table-wrap" style="box-shadow:none;border:1px solid var(--border);">
          <table class="admin-table">
            <thead><tr><th>State</th><th>Code</th><th>Zone</th><th>Actions</th></tr></thead>
            <tbody id="states-tbody"><tr><td colspan="4" class="loading-row">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ── COURIERS ───────────────────────────────────── -->
    <section id="tab-couriers" class="admin-tab">
      <div class="tab-header"><h2>Couriers</h2><button class="btn-primary" onclick="openCourierModal()"><i data-lucide="plus"></i> Add Courier</button></div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="couriers-tbody"><tr><td colspan="3" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── PAYMENT ───────────────────────────────────── -->
    <section id="tab-payment" class="admin-tab">
      <div class="tab-header"><h2>Payment Gateways</h2><button class="btn-primary" onclick="openPaymentModal()"><i data-lucide="plus"></i> Add Gateway</button></div>
      <div id="payment-cards" class="payment-cards-grid"></div>
    </section>

    <!-- ── MESSAGES ──────────────────────────────────── -->
    <section id="tab-messages" class="admin-tab">
      <div class="tab-header"><h2>Contact Messages</h2></div>
      <div class="search-bar">
        <input type="text" id="msg-search" placeholder="Search by name, email or subject…" oninput="filterMessages()">
        <i data-lucide="search"></i>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Message</th><th>Actions</th></tr></thead>
          <tbody id="messages-tbody"><tr><td colspan="7" class="loading-row">Loading…</td></tr></tbody>
        </table>
      </div>
    </section>

    <!-- ── LOCATIONS ─────────────────────────────────── -->
    <section id="tab-locations" class="admin-tab">
      <div class="tab-header"><h2>Locations</h2></div>

      <div class="admin-card" style="margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
          <div class="card-title" style="margin:0;"><i data-lucide="globe"></i> Countries</div>
          <button class="btn-primary" onclick="openCountryModal()"><i data-lucide="plus"></i> Add Country</button>
        </div>
        <div class="admin-table-wrap" style="box-shadow:none;border:1px solid var(--border);">
          <table class="admin-table">
            <thead><tr><th>Name</th><th>Code</th><th>Actions</th></tr></thead>
            <tbody id="countries-tbody"><tr><td colspan="3" class="loading-row">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
          <div class="card-title" style="margin:0;"><i data-lucide="map"></i> States &amp; Zones</div>
          <button class="btn-primary" onclick="openStateModal()"><i data-lucide="plus"></i> Add State</button>
        </div>
        <div class="admin-table-wrap" style="box-shadow:none;border:1px solid var(--border);">
          <table class="admin-table">
            <thead><tr><th>Name</th><th>Code</th><th>Zone</th><th>Country</th><th>Actions</th></tr></thead>
            <tbody id="admin-states-tbody"><tr><td colspan="5" class="loading-row">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="admin-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
          <div class="card-title" style="margin:0;"><i data-lucide="building-2"></i> Cities</div>
          <button class="btn-primary" onclick="openCityModal()"><i data-lucide="plus"></i> Add City</button>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:1rem;">
          <select id="city-state-filter" onchange="loadCities(this.value)" style="padding:7px 12px;border-radius:var(--r-md);border:1.5px solid var(--border);font-size:0.875rem;background:var(--surface);">
            <option value="">All States</option>
          </select>
          <div class="search-bar" style="margin:0;flex:1;">
            <input type="text" id="city-search" placeholder="Search cities…" oninput="filterCities()">
            <i data-lucide="search"></i>
          </div>
        </div>
        <div class="admin-table-wrap" style="box-shadow:none;border:1px solid var(--border);">
          <table class="admin-table">
            <thead><tr><th>Name</th><th>State</th><th>Actions</th></tr></thead>
            <tbody id="admin-cities-tbody"><tr><td colspan="3" class="loading-row">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ── SETTINGS ──────────────────────────────────── -->
    <section id="tab-settings" class="admin-tab">
      <div class="tab-header"><h2>Settings</h2></div>
      <div class="admin-card" style="max-width:540px;">
        <div class="card-title"><i data-lucide="hash"></i> Order Number Configuration</div>
        <form id="order-settings-form" onsubmit="saveOrderSettings(event)">
          <div class="field" style="margin-bottom:1.25rem;">
            <label>Order Prefix</label>
            <input type="text" id="setting-order-prefix" placeholder="e.g. MHB" style="max-width:200px;">
            <div class="field-hint">Prefix for order numbers — e.g. MHB-1001</div>
          </div>
          <div class="field" style="margin-bottom:1.5rem;">
            <label>Initial Sequence Number</label>
            <input type="number" id="setting-order-sequence" placeholder="1000" style="max-width:200px;">
            <div class="field-hint">Next sequence number to be used</div>
          </div>
          <button type="submit" class="btn-primary">Save Settings</button>
        </form>
      </div>
    </section>
  </div>
</div>

<!-- ═══════════════ MODALS ════════════════════════════════ -->
<div id="modal-backdrop" class="modal-backdrop" onclick="closeAllModals()"></div>

<!-- Product Modal (full-screen panel) -->
<div id="product-modal" class="modal-panel admin-modal">

  <!-- ── Toolbar ─────────────────────────────────────────── -->
  <div class="pf-toolbar">
    <div class="pf-toolbar-left">
      <button type="button" onclick="closeAllModals()" class="pf-back-btn">
        <i data-lucide="arrow-left"></i>
      </button>
      <div class="pf-toolbar-crumb">
        <span class="pf-crumb-parent">Products</span>
        <i data-lucide="chevron-right" class="pf-crumb-sep"></i>
        <span class="pf-crumb-current" id="product-modal-title">New Product</span>
      </div>
    </div>
    <div class="pf-toolbar-right">
      <span class="form-status-pill new" id="pf-status-pill">Draft</span>
      <button type="button" class="pf-discard-btn" onclick="closeAllModals()">Discard</button>
      <button type="submit" form="product-form" class="pf-save-btn" id="pf-submit">
        <i data-lucide="save"></i> Save
      </button>
    </div>
  </div>

  <!-- ── Status hint bar ─────────────────────────────────── -->
  <div class="pf-hint-bar">
    <i data-lucide="info" style="width:12px;height:12px;color:var(--text-3);flex-shrink:0;"></i>
    <span id="pf-status-hint">Fill in the required fields marked with <span style="color:#dc2626;">*</span> and save.</span>
  </div>

  <!-- ── Body ────────────────────────────────────────────── -->
  <div class="modal-panel-body">
    <form id="product-form" onsubmit="saveProduct(event)" class="pf-form">
      <input type="hidden" id="pf-id">

      <div class="pf-layout">

        <!-- ════ LEFT — Main form card ══════════════════════ -->
        <div class="pf-main">

          <!-- § Product Identity -->
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="package"></i><h4>Product Identity</h4></div>
            <div class="form-section-body">

              <!-- Title — full width, prominent -->
              <div class="field pf-title-field">
                <label>Product Title <span class="req">*</span></label>
                <input type="text" id="pf-title" required placeholder="Enter product name…" class="pf-title-input">
                <div class="field-msg" id="pf-title-msg"></div>
              </div>

              <!-- SKU + Category + Brand in a row -->
              <div class="field-grid-3">
                <div class="field">
                  <label>SKU <span class="req">*</span></label>
                  <input type="text" id="pf-sku" required placeholder="Unique code">
                  <div class="field-msg" id="pf-sku-msg"></div>
                </div>
                <div class="field">
                  <label>Category</label>
                  <select id="pf-category"></select>
                </div>
                <div class="field">
                  <label>Brand</label>
                  <select id="pf-brand"><option value="">No Brand</option></select>
                </div>
              </div>

              <!-- Description -->
              <div class="field">
                <label>Description</label>
                <textarea id="pf-desc" rows="4" placeholder="Describe the product — benefits, ingredients, usage…"></textarea>
              </div>
            </div>
          </div>

          <!-- § Pricing & Inventory -->
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="indian-rupee"></i><h4>Pricing &amp; Inventory</h4></div>
            <div class="form-section-body">

              <!-- Price metrics row -->
              <div class="pf-price-row">
                <div class="pf-price-card pf-price-main">
                  <div class="pf-price-label">Selling Price (₹) <span class="req">*</span></div>
                  <input type="number" step="0.01" min="0" id="pf-price" required placeholder="0.00" class="pf-price-input">
                  <div class="field-msg" id="pf-price-msg"></div>
                </div>
                <div class="pf-price-card">
                  <div class="pf-price-label">MRP / Old Price (₹)</div>
                  <input type="number" step="0.01" min="0" id="pf-old-price" placeholder="0.00" class="pf-price-input">
                  <div class="field-msg" id="pf-old-price-msg"></div>
                </div>
                <div class="pf-price-card">
                  <div class="pf-price-label">Stock Qty <span class="req">*</span></div>
                  <input type="number" min="0" id="pf-stock" required value="0" class="pf-price-input">
                  <div class="field-msg" id="pf-stock-msg"></div>
                </div>
              </div>

              <!-- Secondary fields -->
              <div class="field-grid-3" style="margin-top:1rem;">
                <div class="field">
                  <label>Unit (UOM)</label>
                  <select id="pf-uom">
                    <option value="g">g — grams</option>
                    <option value="kg">kg — kilograms</option>
                    <option value="ml">ml — millilitres</option>
                    <option value="L">L — litres</option>
                    <option value="pcs">pcs — pieces</option>
                    <option value="pack">pack</option>
                  </select>
                </div>
                <div class="field">
                  <label>Stock Status</label>
                  <select id="pf-status">
                    <option value="In Stock">✓ In Stock</option>
                    <option value="Out of Stock">✕ Out of Stock</option>
                    <option value="Coming Soon">⏳ Coming Soon</option>
                  </select>
                </div>
                <div class="field">
                  <label>Rating (0–5)</label>
                  <input type="number" step="0.1" min="0" max="5" id="pf-rating" value="0" placeholder="4.5">
                </div>
              </div>
            </div>
          </div>

          <!-- § Variants / Sizes -->
          <div class="form-section">
            <div class="form-section-head form-section-head-between">
              <div class="form-section-head-left">
                <i data-lucide="layers"></i><h4>Variants / Sizes</h4>
              </div>
              <button type="button" class="btn-sm-primary" onclick="addAttributeRow()">
                <i data-lucide="plus"></i> Add Variant
              </button>
            </div>
            <div class="form-section-body">
              <div id="pf-attributes-container" class="variants-list">
                <div class="pf-empty-variants">
                  <i data-lucide="layers" style="width:28px;height:28px;color:var(--border);"></i>
                  <p>No variants yet. Click <strong>Add Variant</strong> to add sizes or weights.</p>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /pf-main -->

        <!-- ════ RIGHT — Sidebar ════════════════════════════ -->
        <div class="pf-aside">

          <!-- Images card -->
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="image"></i><h4>Product Images</h4></div>
            <div class="form-section-body">
              <div class="pf-upload-zone">
                <label for="pf-image-upload" class="pf-upload-label">
                  <i data-lucide="upload-cloud"></i>
                  <span>Click to upload</span>
                  <small>JPG, PNG, WebP — first image = primary</small>
                </label>
                <input type="file" id="pf-image-upload" multiple accept="image/*" style="display:none;">
                <input type="hidden" id="pf-image">
              </div>
              <p id="pf-file-chosen" class="pf-chosen-text"></p>
              <div id="pf-image-preview" class="pf-image-grid"></div>
            </div>
          </div>

          <!-- Status & Visibility card -->
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="eye"></i><h4>Visibility</h4></div>
            <div class="form-section-body">
              <div class="pf-toggle-list">
                <label class="pf-toggle-row">
                  <div class="pf-toggle-info">
                    <span class="pf-toggle-name">Active</span>
                    <span class="pf-toggle-desc">Show in store</span>
                  </div>
                  <div class="toggle-field" style="gap:0;">
                    <input type="checkbox" class="toggle-input" id="pf-active" checked>
                    <span class="toggle-slider"></span>
                  </div>
                </label>
                <label class="pf-toggle-row">
                  <div class="pf-toggle-info">
                    <span class="pf-toggle-name">Special</span>
                    <span class="pf-toggle-desc">Mark as featured</span>
                  </div>
                  <div class="toggle-field" style="gap:0;">
                    <input type="checkbox" class="toggle-input" id="pf-special">
                    <span class="toggle-slider"></span>
                  </div>
                </label>
                <label class="pf-toggle-row">
                  <div class="pf-toggle-info">
                    <span class="pf-toggle-name">Combo</span>
                    <span class="pf-toggle-desc">Bundle / combo deal</span>
                  </div>
                  <div class="toggle-field" style="gap:0;">
                    <input type="checkbox" class="toggle-input" id="pf-combo">
                    <span class="toggle-slider"></span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Organisation card -->
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="sliders-horizontal"></i><h4>Organisation</h4></div>
            <div class="form-section-body">
              <div class="field">
                <label>Sale Tag</label>
                <input type="text" id="pf-sale-tag" placeholder="e.g. HOT, NEW, SALE">
              </div>
              <div class="field">
                <label>Display Sequence</label>
                <input type="number" id="pf-sequence" value="0" min="0" placeholder="0">
                <div class="field-hint">Lower = appears first in the store.</div>
              </div>
            </div>
          </div>

        </div><!-- /pf-aside -->
      </div><!-- /pf-layout -->
    </form>
  </div><!-- /modal-panel-body -->
</div><!-- /product-modal -->

<!-- Category Modal -->
<div id="category-modal" class="admin-modal">
  <div class="modal-box">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="grid"></i></div><div><div class="mh-title" id="cat-modal-title">Add Category</div><div class="mh-sub">Category details</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="category-form" onsubmit="saveCategory(event)">
      <div class="mb">
        <input type="hidden" id="cf-id">
        <div class="field-grid-2">
          <div class="field">
            <label>Name <span class="req">*</span></label>
            <input type="text" id="cf-name" required oninput="autoSlug()" placeholder="Category name">
            <div class="field-msg" id="cf-name-msg"></div>
          </div>
          <div class="field">
            <label>Slug <span class="req">*</span></label>
            <input type="text" id="cf-slug" required placeholder="auto-generated">
            <div class="field-msg" id="cf-slug-msg"></div>
          </div>
          <div class="field"><label>Sequence</label><input type="number" id="cf-sequence" value="0" min="0"></div>
          <div class="field"><label>Parent Category</label><select id="cf-parent"><option value="">None (Top Level)</option></select></div>
        </div>
        <div class="field"><label>Description</label><textarea id="cf-desc" rows="2" placeholder="Short description…"></textarea></div>
        <div class="upload-zone">
          <div class="upload-row">
            <label for="cf-image-upload" class="upload-trigger"><i data-lucide="upload-cloud"></i> Choose Image</label>
            <span id="cf-file-chosen" class="upload-chosen">No file chosen</span>
          </div>
          <input type="file" id="cf-image-upload" accept="image/*" style="display:none;">
          <div id="cf-image-preview" class="cf-img-preview"></div>
          <input type="hidden" id="cf-image">
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Banner Modal (full-screen panel) -->
<div id="banner-modal" class="modal-panel admin-modal">
  <div class="modal-panel-header">
    <div class="header-left">
      <button type="button" onclick="closeAllModals()" class="btn-icon"><i data-lucide="arrow-left"></i></button>
      <h3 id="banner-modal-title">Add Banner</h3>
    </div>
    <div class="header-right">
      <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
      <button type="submit" form="banner-form" class="btn-primary"><i data-lucide="save"></i> Save Banner</button>
    </div>
  </div>
  <div class="modal-panel-body">
    <form id="banner-form" onsubmit="saveBanner(event)" class="pf-form">
      <div class="pf-layout">
        <div class="pf-main">
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="info"></i><h4>Banner Details</h4></div>
            <div class="form-section-body">
              <input type="hidden" id="bf-id">
              <div class="field-grid-2">
                <div class="field">
                  <label>Title <span class="req">*</span></label>
                  <input type="text" id="bf-title" required placeholder="Banner title">
                  <div class="field-msg" id="bf-title-msg"></div>
                </div>
                <div class="field"><label>Type</label>
                  <select id="bf-type">
                    <option value="main">Main</option>
                    <option value="promo">Promo</option>
                    <option value="side">Side</option>
                  </select>
                </div>
                <div class="field"><label>Sequence</label><input type="number" id="bf-sequence" value="0"></div>
                <div class="field"><label>Link URL</label><input type="text" id="bf-link" placeholder="https://…"></div>
              </div>
              <div class="field pf-desc-field"><label>Subtitle</label><input type="text" id="bf-subtitle" placeholder="Optional subtitle text"></div>
            </div>
          </div>
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="settings-2"></i><h4>Settings</h4></div>
            <div class="form-section-body">
              <div class="toggle-group">
                <label class="toggle-field"><input type="checkbox" class="toggle-input" id="bf-active" checked><span class="toggle-slider"></span><span>Active</span></label>
              </div>
            </div>
          </div>
        </div>
        <div class="pf-aside">
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="image"></i><h4>Banner Images</h4></div>
            <div class="form-section-body">
              <div class="upload-zone">
                <label for="bf-image-upload" class="upload-trigger-full"><i data-lucide="upload-cloud"></i> Upload Images</label>
                <input type="file" id="bf-image-upload" multiple accept="image/*" style="display:none;">
                <p id="bf-file-chosen" class="upload-chosen-text">No files chosen</p>
                <span class="upload-hint">1920×612px recommended. Select multiple = multiple banners.</span>
                <input type="hidden" id="bf-image">
              </div>
              <div id="bf-image-preview" class="image-preview-grid"></div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Review Modal -->
<div id="review-modal" class="modal-panel admin-modal">
  <div class="modal-panel-header">
    <div class="header-left">
      <button type="button" onclick="closeAllModals()" class="btn-icon"><i data-lucide="arrow-left"></i></button>
      <h3 id="review-modal-title">Add Customer Review</h3>
    </div>
    <div class="header-right">
      <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
      <button type="submit" form="review-form" class="btn-primary"><i data-lucide="save"></i> Save Review</button>
    </div>
  </div>
  <div class="modal-panel-body">
    <form id="review-form" onsubmit="saveReview(event)" class="pf-form">
      <div class="pf-layout">
        <div class="pf-main">
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="user"></i><h4>Reviewer Details</h4></div>
            <div class="form-section-body">
              <input type="hidden" id="rv-id">
              <div class="field-grid-2">
                <div class="field">
                  <label>Reviewer Name <span class="req">*</span></label>
                  <input type="text" id="rv-name" required placeholder="e.g. Priya S.">
                  <div class="field-msg" id="rv-name-msg"></div>
                </div>
                <div class="field">
                  <label>Rating (1–5)</label>
                  <select id="rv-rating">
                    <option value="5">★★★★★ (5)</option>
                    <option value="4">★★★★☆ (4)</option>
                    <option value="3">★★★☆☆ (3)</option>
                    <option value="2">★★☆☆☆ (2)</option>
                    <option value="1">★☆☆☆☆ (1)</option>
                  </select>
                </div>
                <div class="field"><label>Sequence</label><input type="number" id="rv-sequence" value="0"></div>
              </div>
              <div class="field pf-desc-field">
                <label>Review Text</label>
                <textarea id="rv-text" rows="3" placeholder="Customer review quote…" style="resize:vertical;"></textarea>
              </div>
            </div>
          </div>
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="settings-2"></i><h4>Settings</h4></div>
            <div class="form-section-body">
              <div class="toggle-group">
                <label class="toggle-field"><input type="checkbox" class="toggle-input" id="rv-active" checked><span class="toggle-slider"></span><span>Active</span></label>
              </div>
            </div>
          </div>
        </div>
        <div class="pf-aside">
          <div class="form-section">
            <div class="form-section-head"><i data-lucide="image"></i><h4>Media (Image or Video)</h4></div>
            <div class="form-section-body">
              <div class="field" style="margin-bottom:0.75rem;">
                <label>Media Type</label>
                <select id="rv-media-type" onchange="toggleReviewMediaInputs()">
                  <option value="image">Image (upload)</option>
                  <option value="video">Video (upload)</option>
                  <option value="video_url">Video (URL / YouTube embed)</option>
                </select>
              </div>
              <div id="rv-upload-wrap" class="upload-zone">
                <label for="rv-media-upload" class="upload-trigger-full"><i data-lucide="upload-cloud"></i> Upload File</label>
                <input type="file" id="rv-media-upload" accept="image/*,video/mp4,video/webm,video/mov" style="display:none;">
                <p id="rv-file-chosen" class="upload-chosen-text">No file chosen</p>
                <span class="upload-hint">Image: JPG/PNG/WebP. Video: MP4/WebM.</span>
                <input type="hidden" id="rv-media-url">
              </div>
              <div id="rv-url-wrap" style="display:none;">
                <div class="field">
                  <label>Video URL</label>
                  <input type="text" id="rv-video-url" placeholder="https://…or YouTube embed URL">
                </div>
              </div>
              <div id="rv-media-preview" style="margin-top:0.75rem;"></div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Order Status Modal -->
<div id="order-modal" class="admin-modal">
  <div class="modal-box modal-box-sm">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="shopping-cart"></i></div><div><div class="mh-title">Update Order</div><div class="mh-sub">Order: <span id="om-order-num" style="color:#C9A84C;"></span></div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <div class="mb">
      <input type="hidden" id="om-order-id">
      <div class="field"><label>Status</label>
        <select id="om-status">
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="field"><label>Tracking ID</label><input type="text" id="om-tracking-id" placeholder="AWB123456789"></div>
      <div class="field"><label>Tracking URL</label><input type="url" id="om-tracking-url" placeholder="https://courier.com/track/…"></div>
    </div>
    <div class="mf">
      <button class="btn-secondary" onclick="closeAllModals()">Cancel</button>
      <button class="btn-primary" onclick="updateOrderStatus()"><i data-lucide="check"></i> Save</button>
    </div>
  </div>
</div>

<!-- Order Details Modal (full-screen panel) -->
<div id="order-details-modal" class="modal-panel admin-modal">
  <div class="modal-panel-header">
    <div class="header-left">
      <button type="button" onclick="closeAllModals()" class="btn-icon"><i data-lucide="arrow-left"></i></button>
      <h3 id="odm-title">Order Details</h3>
    </div>
    <button class="btn-primary" onclick="printSingleOrder()"><i data-lucide="printer"></i> Print Label</button>
  </div>
  <div class="modal-panel-body">
    <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:1.25rem;" class="odm-grid">
      <div style="display:flex;flex-direction:column;gap:1.25rem;">
        <div class="admin-card" style="margin:0;">
          <div class="card-title"><i data-lucide="package"></i> Order Items</div>
          <div id="odm-items-list"></div>
        </div>
        <div class="admin-card" style="margin:0;">
          <div class="card-title"><i data-lucide="receipt"></i> Order Summary</div>
          <div id="odm-summary" style="display:flex;flex-direction:column;gap:10px;"></div>
        </div>
      </div>
      <div class="odm-info-pane admin-card" style="margin:0;padding:0;">
        <div class="odm-info-section"><div class="odm-info-label"><i data-lucide="map-pin"></i> Shipping Address</div><div id="odm-address" class="odm-info-body"></div></div>
        <div class="odm-info-section"><div class="odm-info-label"><i data-lucide="user"></i> Customer</div><div id="odm-customer" class="odm-info-body"></div></div>
        <div class="odm-info-section"><div class="odm-info-label"><i data-lucide="credit-card"></i> Payment</div><div id="odm-payment" class="odm-info-body"></div></div>
        <div class="odm-info-section"><div class="odm-info-label"><i data-lucide="truck"></i> Courier &amp; Tracking</div><div id="odm-courier" class="odm-info-body"></div></div>
      </div>
    </div>
  </div>
</div>

<div id="print-container" style="display:none;"></div>

<!-- User Addresses Modal -->
<div id="user-addresses-modal" class="admin-modal">
  <div class="modal-box">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="map-pin"></i></div><div><div class="mh-title">Saved Addresses</div><div class="mh-sub"><span id="uam-user-name"></span></div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <div class="mb">
      <div id="uam-list" class="uam-list">
        <div class="loading-shimmer" style="height:60px;border-radius:8px;"></div>
      </div>
    </div>
    <div class="mf"><button class="btn-secondary" onclick="closeAllModals()">Close</button></div>
  </div>
</div>

<!-- Tariff Modal -->
<div id="tariff-modal" class="admin-modal">
  <div class="modal-box modal-box-sm">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="truck"></i></div><div><div class="mh-title" id="tariff-modal-title">Add Tariff</div><div class="mh-sub">Delivery rate configuration</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="tariff-form" onsubmit="saveTariff(event)">
      <div class="mb">
        <input type="hidden" id="tf-id">
        <div class="field"><label>Calculation Type *</label>
          <select id="tf-type" onchange="toggleTariffLabel(this.value)" required>
            <option value="WEIGHT">By Weight (grams)</option>
            <option value="RATE">By Rate (Order Value)</option>
          </select>
        </div>
        <div class="field">
          <label id="tf-threshold-label">Max Weight (grams) <span class="req">*</span></label>
          <input type="number" id="tf-weight" required placeholder="e.g. 500" min="1">
          <div class="field-msg" id="tf-weight-msg"></div>
        </div>
        <div class="field-grid-2">
          <div class="field"><label>TN (₹) <span class="req">*</span></label><input type="number" id="tf-tn" required placeholder="0" min="0"></div>
          <div class="field"><label>SOUTH (₹) <span class="req">*</span></label><input type="number" id="tf-south" required placeholder="0" min="0"></div>
          <div class="field"><label>REST (₹) <span class="req">*</span></label><input type="number" id="tf-rest" required placeholder="0" min="0"></div>
          <div class="field"><label>NE (₹) <span class="req">*</span></label><input type="number" id="tf-ne" required placeholder="0" min="0"></div>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save Tariff</button>
      </div>
    </form>
  </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" class="admin-modal">
  <div class="modal-box">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="credit-card"></i></div><div><div class="mh-title" id="pm-modal-title">Add Payment Gateway</div><div class="mh-sub">Configure payment provider</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="payment-form" onsubmit="savePaymentGateway(event)">
      <div class="mb">
        <input type="hidden" id="pmf-id">
        <div class="field-grid-2">
          <div class="field">
            <label>Gateway Name <span class="req">*</span></label>
            <input type="text" id="pmf-name" required placeholder="e.g. Razorpay">
            <div class="field-msg" id="pmf-name-msg"></div>
          </div>
          <div class="field"><label>Gateway Type *</label>
            <select id="pmf-type" required>
              <option value="razorpay">Razorpay</option>
              <option value="phonepe">PhonePe</option>
              <option value="stripe">Stripe</option>
              <option value="cashfree">Cashfree</option>
              <option value="payu">PayU</option>
              <option value="ccavenue">CCAvenue</option>
              <option value="cod">Cash on Delivery</option>
            </select>
          </div>
        </div>
        <div class="field"><label>Credentials (JSON) *</label>
          <textarea id="pmf-creds" rows="4" placeholder='{"key_id":"...","key_secret":"..."}'></textarea>
          <div id="pmf-hint" class="field-hint">Enter gateway credentials in JSON format.</div>
        </div>
        <div class="toggle-group">
          <label class="toggle-field"><input type="checkbox" class="toggle-input" id="pmf-active"><span class="toggle-slider"></span><span>Active</span></label>
          <label class="toggle-field"><input type="checkbox" class="toggle-input" id="pmf-test" checked><span class="toggle-slider"></span><span>Test Mode</span></label>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save Gateway</button>
      </div>
    </form>
  </div>
</div>

<!-- Brand Modal -->
<div id="brand-modal" class="admin-modal">
  <div class="modal-box modal-box-xs">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="award"></i></div><div><div class="mh-title" id="brand-modal-title">Add Brand</div><div class="mh-sub">Brand master</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="brand-form" onsubmit="saveBrand(event)">
      <div class="mb">
        <input type="hidden" id="bf2-id">
        <div class="field">
          <label>Brand Name <span class="req">*</span></label>
          <input type="text" id="bf2-name" required placeholder="e.g. L'Oreal, Himalaya">
          <div class="field-msg" id="bf2-name-msg"></div>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save Brand</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div id="confirm-modal" class="admin-modal">
  <div class="modal-box modal-box-xs">
    <div class="mh mh-danger">
      <div class="mh-left"><div class="mh-icon mh-icon-danger"><i data-lucide="trash-2"></i></div><div><div class="mh-title">Confirm Action</div><div class="mh-sub">This cannot be undone</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <div class="mb">
      <p id="confirm-msg" class="confirm-msg">Are you sure you want to delete this item?</p>
    </div>
    <div class="mf">
      <button class="btn-secondary" onclick="closeAllModals()">Cancel</button>
      <button class="btn-danger" id="confirm-ok-btn"><i data-lucide="trash-2"></i> Delete</button>
    </div>
  </div>
</div>

<!-- Courier Modal -->
<div id="courier-modal" class="admin-modal">
  <div class="modal-box modal-box-xs">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="send"></i></div><div><div class="mh-title" id="cur-modal-title">Add Courier</div><div class="mh-sub">Courier partner details</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="courier-form" onsubmit="saveCourier(event)">
      <div class="mb">
        <input type="hidden" id="cur-id">
        <div class="field">
          <label>Courier Name <span class="req">*</span></label>
          <input type="text" id="cur-name" required placeholder="e.g. BlueDart, DTDC">
          <div class="field-msg" id="cur-name-msg"></div>
        </div>
        <div class="toggle-group">
          <label class="toggle-field"><input type="checkbox" class="toggle-input" id="cur-active" checked><span class="toggle-slider"></span><span>Active</span></label>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save Courier</button>
      </div>
    </form>
  </div>
</div>

<!-- Country Modal -->
<div id="country-modal" class="admin-modal">
  <div class="modal-box modal-box-xs">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="globe"></i></div><div><div class="mh-title" id="country-modal-title">Add Country</div><div class="mh-sub">Location master</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="country-form" onsubmit="saveCountry(event)">
      <div class="mb">
        <input type="hidden" id="countf-id">
        <div class="field-grid-2">
          <div class="field">
            <label>Country Name <span class="req">*</span></label>
            <input type="text" id="countf-name" required placeholder="e.g. India">
            <div class="field-msg" id="countf-name-msg"></div>
          </div>
          <div class="field">
            <label>Country Code <span class="req">*</span></label>
            <input type="text" id="countf-code" required placeholder="e.g. IN" maxlength="5" style="text-transform:uppercase">
            <div class="field-msg" id="countf-code-msg"></div>
          </div>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save Country</button>
      </div>
    </form>
  </div>
</div>

<!-- State Modal -->
<div id="state-modal" class="admin-modal">
  <div class="modal-box modal-box-sm">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="map"></i></div><div><div class="mh-title" id="state-modal-title">Add State</div><div class="mh-sub">Location master</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="state-form" onsubmit="saveState(event)">
      <div class="mb">
        <input type="hidden" id="statef-id">
        <div class="field-grid-2">
          <div class="field">
            <label>State Name <span class="req">*</span></label>
            <input type="text" id="statef-name" required placeholder="e.g. Tamil Nadu">
            <div class="field-msg" id="statef-name-msg"></div>
          </div>
          <div class="field">
            <label>State Code <span class="req">*</span></label>
            <input type="text" id="statef-code" required placeholder="e.g. TN" maxlength="5" style="text-transform:uppercase">
            <div class="field-msg" id="statef-code-msg"></div>
          </div>
          <div class="field"><label>Country <span class="req">*</span></label><select id="statef-country" required></select></div>
          <div class="field"><label>Delivery Zone</label>
            <select id="statef-zone">
              <option value="SOUTH">SOUTH</option><option value="NORTH">NORTH</option>
              <option value="EAST">EAST</option><option value="WEST">WEST</option>
              <option value="CENTRAL">CENTRAL</option><option value="REST">REST</option>
              <option value="NE">NE (North East)</option>
            </select>
          </div>
        </div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save State</button>
      </div>
    </form>
  </div>
</div>

<!-- City Modal -->
<div id="city-modal" class="admin-modal">
  <div class="modal-box modal-box-xs">
    <div class="mh">
      <div class="mh-left"><div class="mh-icon"><i data-lucide="building-2"></i></div><div><div class="mh-title" id="city-modal-title">Add City</div><div class="mh-sub">Location master</div></div></div>
      <button class="mh-close" onclick="closeAllModals()"><i data-lucide="x"></i></button>
    </div>
    <form id="city-form" onsubmit="saveCity(event)">
      <div class="mb">
        <input type="hidden" id="cityf-id">
        <div class="field">
          <label>City Name <span class="req">*</span></label>
          <input type="text" id="cityf-name" required placeholder="e.g. Chennai">
          <div class="field-msg" id="cityf-name-msg"></div>
        </div>
        <div class="field"><label>State <span class="req">*</span></label><select id="cityf-state" required></select></div>
      </div>
      <div class="mf">
        <button type="button" class="btn-secondary" onclick="closeAllModals()">Cancel</button>
        <button type="submit" class="btn-primary"><i data-lucide="save"></i> Save City</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/admin.js?v=3.1.0"></script>
<script>
  // Payment gateway credential hints
  document.getElementById('pmf-type').addEventListener('change', function () {
    const hints = {
      razorpay: ['{"key_id": "rzp_test_...", "key_secret": "..."}', 'Provide your Razorpay Key ID and Key Secret.'],
      phonepe:  ['{"merchantId": "...", "saltKey": "...", "saltIndex": "1"}', 'Provide Merchant ID, Salt Key, and Salt Index.'],
      cod:      ['{}', 'COD does not require credentials.'],
    };
    const [ph, hint] = hints[this.value] || ['{"api_key": "...", ...}', 'Provide keys required for this gateway in JSON format.'];
    document.getElementById('pmf-creds').placeholder = ph;
    document.getElementById('pmf-hint').textContent = hint;
  });

  document.addEventListener('DOMContentLoaded', () => {
    // User name
    const u = window.MB_AUTH && window.MB_AUTH.user;
    if (u && u.first_name) {
      document.getElementById('admin-user-name').textContent = (u.first_name + ' ' + (u.last_name || '')).trim();
    }

    // Dashboard date
    const dd = document.getElementById('dash-date');
    if (dd) dd.textContent = new Date().toLocaleDateString('en-IN', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

    // Breadcrumb
    document.querySelectorAll('.sidebar-link[data-tab]').forEach(link => {
      link.addEventListener('click', () => {
        const bc = document.getElementById('admin-breadcrumb');
        if (bc) bc.textContent = link.textContent.trim();
      });
    });

    if (typeof lucide !== 'undefined') lucide.createIcons();
  });
</script>
</body>
</html>
