<?php
/**
 * Orders History Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Menha Boutique - My Orders";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'My Orders';
$pageTopbarSubtitle = 'Track and review your purchases';
$pageTopbarBack     = 'profile.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<style>
    .order-card {
        background: var(--color-white);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.25rem;
        border: 1px solid var(--color-border);
        transition: box-shadow 0.2s ease;
    }
    .order-card:hover { box-shadow: var(--shadow-md); }

    .order-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        user-select: none;
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: none;
    }
    .order-card.is-expanded .order-card-top {
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--color-border);
    }
    .order-card-top .top-meta { flex: 1; min-width: 0; }
    .order-card-top .top-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    .toggle-chevron {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--color-light-gray);
        color: var(--color-primary);
        transition: transform 0.25s ease, background 0.2s ease;
    }
    .order-card.is-expanded .toggle-chevron { transform: rotate(180deg); background: rgba(0, 77, 64, 0.1); }

    .order-details-collapsible {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.35s ease, opacity 0.25s ease, margin 0.25s ease;
    }
    .order-card.is-expanded .order-details-collapsible {
        max-height: 2000px;
        opacity: 1;
    }
    .order-id-line {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--color-text-light);
        letter-spacing: 0.4px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .order-date-line {
        font-size: 1rem;
        font-weight: 700;
        color: var(--color-primary-dark);
    }

    .status-badge {
        padding: 5px 14px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border: 1px solid transparent;
    }
    .status-pending    { background: #fff8e1; color: #b45309; border-color: #fde68a; }
    .status-processing { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
    .status-shipped    { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
    .status-delivered  { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .status-cancelled  { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

    .items-list { display: flex; flex-direction: column; gap: 0.75rem; }
    .order-item {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    .order-item-img {
        width: 56px;
        height: 56px;
        background: var(--color-light-gray);
        border-radius: 10px;
        padding: 4px;
        flex-shrink: 0;
        border: 1px solid var(--color-border);
    }
    .order-item-img img { width: 100%; height: 100%; object-fit: contain; }
    .order-item-title { font-weight: 600; color: var(--color-primary-dark); margin: 0; font-size: 0.95rem; }
    .order-item-qty { color: var(--color-text-light); font-size: 0.85rem; margin-top: 2px; }
    .order-item-price { font-weight: 700; color: var(--color-primary-dark); font-size: 0.95rem; }

    .order-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed var(--color-border);
    }
    .order-section-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--color-text-light);
        font-weight: 700;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .order-section-body {
        font-size: 0.9rem;
        color: var(--color-primary-dark);
        line-height: 1.55;
    }
    .order-section-body strong { font-weight: 600; }

    .tracking-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 10px 14px;
        border-radius: 10px;
        margin-top: 1rem;
    }
    .tracking-row .track-id { font-size: 0.85rem; color: #166534; font-weight: 600; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .tracking-row .track-link {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--color-primary);
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fff;
        border: 1.5px solid #bbf7d0;
        color: #166534;
        border-radius: 8px;
        padding: 3px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s, color 0.15s;
        min-height: unset;
    }
    .copy-btn:hover { background: #dcfce7; }
    .copy-btn.copied { background: #16a34a; color: #fff; border-color: #16a34a; }

    .order-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--color-border);
        gap: 1rem;
        flex-wrap: wrap;
    }
    .order-card.is-expanded .order-footer { margin-top: 1.25rem; }
    .footer-total .label {
        font-size: 0.72rem;
        color: var(--color-text-light);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
    }
    .footer-total .amount {
        font-weight: 800;
        color: var(--color-primary);
        font-size: 1.4rem;
        margin-top: 2px;
    }
    .footer-meta {
        display: flex;
        gap: 1.25rem;
        align-items: center;
        font-size: 0.8rem;
    }
    .footer-meta .meta-block { text-align: right; }
    .footer-meta .meta-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: var(--color-text-light);
        letter-spacing: 0.4px;
        font-weight: 600;
    }
    .footer-meta .meta-value {
        font-weight: 700;
        color: var(--color-primary-dark);
        margin-top: 2px;
        font-size: 0.88rem;
    }
    .pay-paid { color: #16a34a; }
    .pay-unpaid { color: #d97706; }

    @media (max-width: 600px) {
        .order-card { padding: 1.25rem; }
        .order-footer { flex-direction: column; align-items: flex-start; }
        .footer-meta { width: 100%; justify-content: space-between; }
        .footer-meta .meta-block { text-align: left; }
    }
</style>

<main class="main-content container section-padding" style="min-height: 70vh; max-width: 900px; padding-top: 0; padding-bottom: 4rem;">
    <div id="orders-workspace">
        <div style="text-align: center; padding: 4rem 1rem;">
            <p style="color: var(--color-text-light);">Loading orders...</p>
        </div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const workspace = document.getElementById('orders-workspace');

        // Initial skeleton state
        workspace.innerHTML = Array.from({ length: 3 }).map(() => `
            <div class="skeleton-row" style="padding:1.25rem;">
                <div class="skeleton-meta">
                    <div class="skeleton skeleton-line lg w-40"></div>
                    <div class="skeleton skeleton-line w-60"></div>
                    <div class="skeleton skeleton-line w-30"></div>
                </div>
            </div>
        `).join('');

        try {
            const orders = await MainAPI.getOrders();

            if (orders.length === 0) {
                workspace.innerHTML = `
                    <div style="text-align:center; padding: 4rem 1rem;">
                        <i data-lucide="package" style="width: 60px; height: 60px; color: var(--color-border); margin-bottom: 1rem;"></i>
                        <h2 style="color: var(--color-primary-dark); margin-bottom: 0.5rem;">No Orders Found</h2>
                        <p style="color: var(--color-text-light); margin-bottom: 2rem;">You haven't placed any orders yet.</p>
                        <a href="index.php" style="background: var(--color-primary); color: white; padding: 12px 30px; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                            <i data-lucide="arrow-right"></i> Start Shopping
                        </a>
                    </div>
                `;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }

            function buildTimeline(status) {
                const s = (status || 'pending').toLowerCase();
                if (s === 'cancelled' || s === 'failed') {
                    return `
                        <div class="order-timeline cancelled">
                            <div class="order-timeline-track">
                                <div class="order-timeline-progress" style="width:90%; background:#ef4444;"></div>
                                <div class="timeline-step done"><div class="timeline-dot"><i data-lucide="x"></i></div><div class="timeline-label">Cancelled</div></div>
                            </div>
                        </div>`;
                }
                const steps = [
                    { key: 'pending',    label: 'Placed',    icon: 'shopping-cart' },
                    { key: 'processing', label: 'Processing', icon: 'package' },
                    { key: 'shipped',    label: 'Shipped',    icon: 'truck' },
                    { key: 'delivered',  label: 'Delivered',  icon: 'check' }
                ];
                let currentIdx = steps.findIndex(st => st.key === s);
                if (currentIdx === -1) currentIdx = 0;
                const progressPct = currentIdx === 0 ? 0 : (currentIdx / (steps.length - 1)) * 100;
                const stepsHtml = steps.map((st, idx) => {
                    let cls = '';
                    if (idx < currentIdx) cls = 'done';
                    else if (idx === currentIdx) cls = s === 'delivered' ? 'done' : 'current';
                    return `
                        <div class="timeline-step ${cls}">
                            <div class="timeline-dot"><i data-lucide="${st.icon}"></i></div>
                            <div class="timeline-label">${st.label}</div>
                        </div>`;
                }).join('');
                return `
                    <div class="order-timeline">
                        <div class="order-timeline-track">
                            <div class="order-timeline-progress" style="width: calc(${progressPct}% * 0.9);"></div>
                            ${stepsHtml}
                        </div>
                    </div>`;
            }

            let html = '';
            orders.forEach(o => {
                const date = new Date(o.created_at || o.createdAt).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                let statusClass = 'status-pending';
                const s = (o.status || 'pending').toLowerCase();

                if (s === 'processing' || s === 'confirmed') statusClass = 'status-processing';
                if (s === 'shipped') statusClass = 'status-shipped';
                if (s === 'delivered') statusClass = 'status-delivered';
                if (s === 'cancelled' || s === 'failed') statusClass = 'status-cancelled';

                let itemsHtml = '';
                if (o.items && Array.isArray(o.items)) {
                    o.items.forEach(item => {
                        const img = MainAPI.getProductImage(item.product || {});
                        const title = (item.product || {}).title || 'Product';
                        const q = item.quantity || 1;
                        const price = item.unit_price || item.price || 0;

                        itemsHtml += `
                            <div class="order-item">
                                <div class="order-item-img"><img src="${img}" alt="${title}" loading="lazy"></div>
                                <div style="flex:1;">
                                    <h4 class="order-item-title">${title}</h4>
                                    <div class="order-item-qty">Qty: ${q}</div>
                                </div>
                                <div class="order-item-price">₹${price * q}</div>
                            </div>
                        `;
                    });
                }

                const addrHtml = o.address ? `
                    <strong>${o.address.first_name} ${o.address.last_name || ''}</strong><br>
                    ${o.address.address_line1 || ''}<br>
                    ${o.address.city}, ${o.address.state} - ${o.address.zip_code || ''}<br>
                    ${o.address.country}<br>
                    <span style="color: var(--color-text-light); font-size: 0.85rem;">Phone: ${o.address.phone_number || ''}</span>
                ` : '<span style="color: var(--color-text-light); font-style: italic;">Address details not available</span>';

                const trackingHtml = (o.tracking_id || o.tracking_url) ? `
                    <div class="tracking-row">
                        <div class="track-id">
                            <i data-lucide="truck" style="width:14px;height:14px;flex-shrink:0;"></i>
                            ${o.tracking_id ? `Tracking: <strong>${o.tracking_id}</strong>
                            <button class="copy-btn" onclick="copyTrackingId('${o.tracking_id}', this)">
                                <i data-lucide="copy" style="width:11px;height:11px;"></i> Copy
                            </button>` : 'Tracking available'}
                        </div>
                        ${o.tracking_url ? `<a class="track-link" href="${o.tracking_url}" target="_blank" rel="noopener">Track shipment <i data-lucide="external-link" style="width:14px;height:14px;"></i></a>` : ''}
                    </div>
                ` : '';

                const payClass = o.payment_status === 'paid' ? 'pay-paid' : 'pay-unpaid';
                const itemCount = (o.items && Array.isArray(o.items)) ? o.items.reduce((n, it) => n + (it.quantity || 1), 0) : 0;

                html += `
                    <div class="order-card fade-in-stagger" data-collapsible>
                        <div class="order-card-top" data-toggle>
                            <div class="top-meta">
                                <div class="order-id-line">Order #${o.order_number || o.id.substring(0, 8)}</div>
                                <div class="order-date-line">Placed on ${date}</div>
                            </div>
                            <div class="top-actions">
                                <div class="status-badge ${statusClass}">${s}</div>
                                <span class="toggle-chevron" aria-label="Toggle details"><i data-lucide="chevron-down" style="width:18px;height:18px;"></i></span>
                            </div>
                        </div>

                        <div class="order-details-collapsible">
                            ${buildTimeline(s)}

                            <div class="items-list">
                                ${itemsHtml || '<p style="color: var(--color-text-light); font-style: italic;">No items found in this order.</p>'}
                            </div>

                            <div class="order-section">
                                <div class="order-section-label"><i data-lucide="map-pin" style="width:14px;height:14px;"></i> Shipping Address</div>
                                <div class="order-section-body">${addrHtml}</div>
                            </div>

                            ${trackingHtml}
                        </div>

                        <div class="order-footer">
                            <div class="footer-total">
                                <div class="label">Total Amount</div>
                                <div class="amount">₹${o.total_price || 0}</div>
                            </div>
                            <div class="footer-meta">
                                <div class="meta-block">
                                    <div class="meta-label">Items</div>
                                    <div class="meta-value">${itemCount || '—'}</div>
                                </div>
                                <div class="meta-block">
                                    <div class="meta-label">Courier</div>
                                    <div class="meta-value">${o.courier_name || 'Standard'}</div>
                                </div>
                                <div class="meta-block">
                                    <div class="meta-label">Payment</div>
                                    <div class="meta-value" style="text-transform:uppercase;">${o.payment_method || 'COD'}</div>
                                    <div class="meta-value ${payClass}" style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.4px;">${o.payment_status || 'unpaid'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            workspace.innerHTML = html;
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Wire collapsible toggle (event delegation)
            workspace.addEventListener('click', (e) => {
                const toggle = e.target.closest('[data-toggle]');
                if (!toggle) return;
                if (e.target.closest('a, button')) return;
                const card = toggle.closest('[data-collapsible]');
                if (card) card.classList.toggle('is-expanded');
            });

        } catch (err) {
            console.error(err);
            workspace.innerHTML = `<div style="text-align:center; padding: 2rem; color: red;">Failed to load orders. Please try again later.</div>`;
        }
    });

    function copyTrackingId(id, btn) {
        navigator.clipboard.writeText(id).then(() => {
            btn.innerHTML = '<i data-lucide="check" style="width:11px;height:11px;"></i> Copied!';
            btn.classList.add('copied');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            setTimeout(() => {
                btn.innerHTML = '<i data-lucide="copy" style="width:11px;height:11px;"></i> Copy';
                btn.classList.remove('copied');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 2000);
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
