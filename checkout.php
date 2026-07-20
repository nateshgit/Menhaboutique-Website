<?php
/**
 * Checkout Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$db = getDBConnection();

$pageTitle = "Menha Boutique - Checkout";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'Checkout';
$pageTopbarSubtitle = 'Complete your order securely';
$pageTopbarBack     = 'cart.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<style>
    /* Checkout Layout styles */
    .checkout-shell { max-width: 1200px; margin: 0 auto; padding: 0.5rem 0 4rem; }
    .checkout-grid-v2 {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(0, 1fr);
        gap: 1.75rem;
        align-items: flex-start;
    }

    .checkout-step {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--color-border);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.25rem;
    }
    .step-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 1.25rem;
    }
    .step-number {
        width: 30px; height: 30px;
        border-radius: 50%;
        background: var(--color-primary);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem;
        flex-shrink: 0;
    }
    .step-title {
        font-size: 1.05rem; font-weight: 700;
        color: var(--color-primary-dark); margin: 0;
    }

    /* Toggle pill (Existing / New address) */
    .addr-toggle {
        display: inline-flex; gap: 4px;
        background: var(--color-light-gray);
        padding: 4px; border-radius: 12px;
        margin-bottom: 1.25rem;
    }
    .addr-toggle label {
        padding: 7px 18px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 0.85rem; font-weight: 600;
        color: var(--color-text-light);
        transition: 0.2s;
    }
    .addr-toggle input { display: none; }
    .addr-toggle input:checked + span {
        color: var(--color-primary);
        font-weight: 700;
    }

    /* Saved address card */
    .addr-list { display: flex; flex-direction: column; gap: 10px; }
    .saved-address {
        border: 1.5px solid var(--color-border);
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: 0.2s;
        position: relative;
        display: flex; gap: 14px; align-items: flex-start;
    }
    .saved-address:hover { border-color: var(--color-primary-light); }
    .saved-address.selected {
        border-color: var(--color-primary);
        background: rgba(0, 77, 64, 0.03);
    }
    .saved-address .radio {
        width: 18px; height: 18px; border-radius: 50%;
        border: 2px solid var(--color-border);
        flex-shrink: 0; margin-top: 4px;
        display: flex; align-items: center; justify-content: center;
    }
    .saved-address.selected .radio {
        border-color: var(--color-primary);
    }
    .saved-address.selected .radio::after {
        content: ''; width: 9px; height: 9px;
        border-radius: 50%; background: var(--color-primary);
    }
    .saved-address-name {
        font-weight: 700; color: var(--color-primary-dark);
        margin-bottom: 4px; font-size: 0.95rem;
    }
    .saved-address-body {
        font-size: 0.85rem; color: #475569; line-height: 1.5;
    }
    .saved-address-body .phone { color: var(--color-text-light); }
    .saved-address .delete-btn {
        background: transparent; border: none; cursor: pointer;
        color: #cbd5e0; padding: 4px;
        transition: color 0.2s;
        display: flex; align-items: center; justify-content: center;
    }
    .saved-address .delete-btn:hover { color: #e53e3e; }

    /* Form inputs */
    .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .form-row .full { grid-column: span 2; }
    .form-group { display: flex; flex-direction: column; }
    .form-label {
        font-size: 0.78rem; font-weight: 600;
        color: #475569; margin-bottom: 6px;
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .form-input, .form-select {
        width: 100%; padding: 11px 14px;
        border: 1px solid var(--color-border);
        border-radius: 10px;
        font-family: inherit; font-size: 0.92rem;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(0, 77, 64, 0.08);
    }

    /* Courier option */
    .courier-option {
        border: 1.5px solid var(--color-border);
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 8px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: space-between;
        transition: 0.2s;
    }
    .courier-option:hover { border-color: var(--color-primary-light); }
    .courier-option.selected {
        border-color: var(--color-primary);
        background: rgba(0, 77, 64, 0.03);
    }
    .courier-option .left-grp { display: flex; align-items: center; gap: 10px; }
    .courier-option .radio {
        width: 18px; height: 18px; border-radius: 50%;
        border: 2px solid var(--color-border);
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .courier-option.selected .radio { border-color: var(--color-primary); }
    .courier-option.selected .radio::after {
        content: ''; width: 9px; height: 9px;
        border-radius: 50%; background: var(--color-primary);
    }

    /* Right summary */
    .summary-card-v2 {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--color-border);
        padding: 1.5rem 1.75rem;
        position: sticky; top: 1rem;
    }
    .summary-card-v2 h3 {
        font-size: 1.05rem; margin: 0 0 1rem;
        color: var(--color-primary-dark); font-weight: 700;
    }
    .cart-line {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 12px 0;
        border-bottom: 1px dashed var(--color-border);
    }
    .cart-line:last-of-type { border-bottom: none; }
    .cart-line .thumb {
        width: 56px; height: 56px;
        object-fit: contain;
        background: var(--color-light-gray);
        border-radius: 10px; padding: 4px;
        flex-shrink: 0;
        border: 1px solid var(--color-border);
    }
    .cart-line .info { flex: 1; min-width: 0; }
    .cart-line .info .title {
        font-size: 0.9rem; font-weight: 600;
        color: var(--color-primary-dark); margin: 0 0 2px;
        line-height: 1.35;
    }
    .cart-line .info .price {
        font-weight: 700; color: var(--color-primary);
        font-size: 0.9rem;
    }
    .qty-stepper {
        display: inline-flex; align-items: center; gap: 6px;
        background: none;
        padding: 3px 0;
        margin-top: 6px;
    }
    .qty-stepper button {
        background: none; border: none;
        cursor: pointer; color: var(--color-primary);
        font-weight: 700; font-size: 1rem; line-height: 1;
        width: 22px; height: 22px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .qty-stepper span {
        min-width: 18px; text-align: center;
        font-weight: 700; color: var(--color-primary-dark);
        font-size: 0.85rem;
    }
    .cart-line .remove-btn {
        background: none; border: none; cursor: pointer;
        color: #cbd5e0; padding: 2px;
    }
    .cart-line .remove-btn:hover { color: #e53e3e; }

    .summary-divider {
        border-top: 1px solid var(--color-border);
        margin: 12px 0;
    }
    .summary-row {
        display: flex; justify-content: space-between;
        padding: 4px 0; font-size: 0.9rem; color: #475569;
    }
    .summary-row.total {
        font-size: 0.95rem; font-weight: 700;
        color: var(--color-primary-dark);
        border-top: 1px solid var(--color-border);
        margin-top: 8px; padding-top: 14px;
    }
    .summary-row.total .amount {
        color: var(--color-primary); font-size: 1.5rem; font-weight: 800;
    }
    .payment-strip {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 12px;
        background: var(--color-light-gray);
        border-radius: 8px; font-size: 0.82rem;
        margin-top: 12px;
        color: var(--color-primary-dark);
    }
    .payment-strip strong { font-weight: 700; }

    .place-order-btn {
        width: 100%;
        background: var(--color-primary);
        color: #fff; border: none;
        padding: 14px;
        border-radius: 12px;
        font-size: 1rem; font-weight: 700;
        cursor: pointer;
        margin-top: 1rem;
        transition: 0.2s;
        display: flex; align-items: center; justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0, 77, 64, 0.18);
    }
    .place-order-btn:hover { background: var(--color-primary-dark); }
    .place-order-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    .trust-row {
        display: flex; flex-direction: column; gap: 10px;
        margin-top: 1rem; padding-top: 1rem;
        border-top: 1px solid var(--color-border);
    }
    .trust-item {
        display: flex; align-items: flex-start; gap: 10px;
        font-size: 0.8rem; color: #475569; line-height: 1.5;
    }
    .trust-item i {
        color: var(--color-primary); width: 16px; height: 16px;
        flex-shrink: 0; margin-top: 2px;
    }

    .login-banner {
        text-align: center; padding: 2.5rem 1.5rem;
        border: 2px dashed var(--color-border);
        border-radius: 12px; background: #fafafa;
    }
    .login-banner h4 {
        margin: 0.75rem 0; color: var(--color-primary-dark);
        font-size: 1.1rem;
    }
    .login-banner a {
        background: var(--color-primary); color: #fff;
        padding: 12px 32px; border-radius: 10px;
        font-weight: 600; display: inline-flex;
        align-items: center; gap: 8px; margin-top: 8px;
    }

    /* Selected address display */
    .selected-addr-wrap { display: flex; flex-direction: column; gap: 8px; }
    .selected-addr-label {
        display: flex; align-items: center; gap: 5px;
        font-size: 0.72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.6px;
        color: var(--color-primary);
        margin-bottom: 2px;
    }
    .change-addr-btn {
        align-self: flex-start;
        background: none;
        border: 1.5px solid var(--color-primary);
        color: var(--color-primary);
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.78rem; font-weight: 700;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 5px;
        transition: background 0.18s;
        margin-top: 2px;
    }
    .change-addr-btn:hover { background: rgba(0,77,64,0.06); }

    /* Save address button in checkout form */
    .save-addr-btn {
        margin-top: 1.25rem;
        width: 100%;
        background: none;
        border: 2px solid var(--color-primary);
        color: var(--color-primary);
        padding: 12px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 0.9rem;
        transition: 0.2s;
    }
    .save-addr-btn:hover {
        background: var(--color-light-gray);
    }

    /* Mobile styles */
    @media (max-width: 900px) {
        .checkout-grid-v2 { grid-template-columns: 1fr; }
        .summary-card-v2 { position: static; }
        .form-row { grid-template-columns: 1fr; }
        .form-row .full { grid-column: span 1; }
        .checkout-step { padding: 1.25rem; }
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
    }

    .receipt-modal {
        background: #fff;
        width: 90%;
        max-width: 450px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: receiptPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes receiptPop {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .receipt-header {
        background: #f8fafc;
        padding: 1.5rem;
        text-align: center;
        border-bottom: 1px dashed #e2e8f0;
    }

    .receipt-body {
        padding: 2rem;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }

    .receipt-total {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 2px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        font-weight: 800;
        font-size: 1.25rem;
        color: #1e293b;
    }
</style>

<main class="main-content container" style="min-height: 70vh; padding-top: 2rem; padding-bottom: 4rem;">
    <div id="cart-workspace">
        <!-- Dynamic Content -->
    </div>
</main>

<!-- Receipt Modal HTML -->
<div id="payment-modal" class="modal-overlay">
    <div class="receipt-modal">
        <div class="receipt-header">
            <h3 style="margin:0; color: #334155;">Order Confirmation</h3>
            <p style="margin: 5px 0 0; font-size: 0.85rem; color: #64748b;">Review your order details</p>
        </div>
        <div class="receipt-body" id="receipt-content">
            <!-- Dynamic Receipt Body -->
        </div>
        <div style="padding: 0 2rem 2rem; display: flex; flex-direction: column; gap: 10px;">
            <div id="gateway-selection" style="margin-bottom: 1rem; display: none;">
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; font-weight: 600;">Payment Method</p>
                <div id="gateways-list" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Gateways will be injected here -->
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="closeReceipt()"
                    style="flex:1; padding: 12px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 10px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button id="final-pay-btn" onclick="confirmAndPlaceOrder()"
                    style="flex:2; padding: 12px; border: none; background: var(--color-primary); color: #fff; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-lucide="credit-card" style="width: 18px; height: 18px;"></i> Pay Now
                </button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    let activeGateway = null;
    let activeCourier = null;
    let allAvailableCouriers = [];
    let checkoutState = 'Tamil Nadu';
    let addressMode = 'existing'; // 'existing' or 'new'
    let selectedAddressId = null;
    let isCartLoading = false;
    let lastRenderId = 0;
    let lastDeliveryId = 0;

    // Persists new-address form values across renderCart() rebuilds
    let newAddrData = {
        first_name: '', last_name: '', email: '', phone: '',
        address1: '', address2: '', country: '', state: '',
        state_id: '', city: '', postcode: '', alt_phone: ''
    };
    let cachedCountries = [];
    let cachedStates = [];   // states for currently selected country
    let cachedCities = [];   // cities for currently selected state

    function getCheckoutItems() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('buynow') === '1') {
            const buynow = sessionStorage.getItem('mb_buynow');
            if (buynow) return JSON.parse(buynow);
        }
        return CartManager.getCart();
    }

    function getCheckoutTotal(items) {
        return items.reduce((sum, item) => sum + (MainAPI.getProductPrice(item.product) * item.quantity), 0);
    }

    function updateBuynowQty(productId, variantId, newQty) {
        if (newQty < 1) return;
        const items = JSON.parse(sessionStorage.getItem('mb_buynow') || '[]');
        const item = items.find(i => i.product.id == productId && (i.product.variant_id || '') == variantId);
        if (item) {
            item.quantity = newQty;
            sessionStorage.setItem('mb_buynow', JSON.stringify(items));
            renderCart();
        }
    }

    function collectNewAddrFormState() {
        const f = id => (document.getElementById(id) || {}).value || '';
        newAddrData.first_name = f('cust-first-name');
        newAddrData.last_name  = f('cust-last-name');
        newAddrData.email      = f('cust-email');
        newAddrData.phone      = f('cust-phone');
        newAddrData.address1   = f('cust-address');
        newAddrData.address2   = f('cust-address2');
        newAddrData.postcode   = f('cust-postcode');
        newAddrData.alt_phone  = f('cust-alt-phone');
        const stateEl = document.getElementById('cust-state');
        if (stateEl && stateEl.value) {
            newAddrData.state = stateEl.value;
            const selOpt = stateEl.options[stateEl.selectedIndex];
            if (selOpt) newAddrData.state_id = selOpt.dataset.id || '';
        }
        newAddrData.country = f('cust-country');
        newAddrData.city    = f('cust-city');
    }

    function restoreNewAddrFormState() {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
        set('cust-first-name', newAddrData.first_name);
        set('cust-last-name',  newAddrData.last_name);
        set('cust-email',      newAddrData.email);
        set('cust-phone',      newAddrData.phone);
        set('cust-address',    newAddrData.address1);
        set('cust-address2',   newAddrData.address2);
        set('cust-postcode',   newAddrData.postcode);
        set('cust-alt-phone',  newAddrData.alt_phone);
        if (newAddrData.country) {
            const cs = document.getElementById('cust-country');
            if (cs) cs.value = newAddrData.country;
        }
    }

    let savedComments = '';
    function collectComments() {
        const el = document.getElementById('order-comments');
        if (el) savedComments = el.value;
    }
    function restoreComments() {
        const el = document.getElementById('order-comments');
        if (el && savedComments) el.value = savedComments;
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
        await fetchGateways();
        await fetchCouriers();
        await renderCart();

        window.addEventListener('cartUpdated', async () => {
            collectNewAddrFormState();
            collectComments();
            const isBuynow = new URLSearchParams(window.location.search).get('buynow') === '1';
            if (!isBuynow) {
                // Update each cart-line's qty span + line price directly
                CartManager.getCart().forEach(item => {
                    const pid = String(item.product.id);
                    const vid = String(item.product.variant_id || '');
                    const lineEl = document.querySelector(`.cart-line[data-pid="${pid}"][data-vid="${vid}"]`);
                    if (!lineEl) return;
                    const qtySpan = lineEl.querySelector('.qty-stepper span');
                    const priceDiv = lineEl.querySelector('.info .price');
                    const price = MainAPI.getProductPrice(item.product);
                    if (qtySpan) qtySpan.textContent = item.quantity;
                    if (priceDiv) priceDiv.textContent = `₹${(price * item.quantity).toFixed(2)}`;
                    // Refresh onclick for qty buttons with new quantities
                    const btns = lineEl.querySelectorAll('.qty-stepper button');
                    if (btns[0]) btns[0].setAttribute('onclick', `CartManager.update('${pid}',${item.quantity - 1},'${vid}')`);
                    if (btns[1]) btns[1].setAttribute('onclick', `CartManager.update('${pid}',${item.quantity + 1},'${vid}')`);
                });
                // Remove lines for items no longer in cart
                document.querySelectorAll('.cart-line[data-pid]').forEach(el => {
                    const pid = el.dataset.pid;
                    const vid = el.dataset.vid || '';
                    const stillInCart = CartManager.getCart().some(
                        i => String(i.product.id) === pid && String(i.product.variant_id || '') === vid
                    );
                    if (!stillInCart) el.remove();
                });
            }
            await updateSummaryPanel();
            restoreNewAddrFormState();
            restoreComments();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        checkPaymentStatus();
    });

    async function fetchGateways() {
        const gateways = await MainAPI.getAvailableGateways();
        if (gateways && gateways.length > 0) {
            activeGateway = gateways.find(g => g.is_active) || gateways[0];
        }
    }

    async function fetchCouriers() {
        allAvailableCouriers = await MainAPI.getAvailableCouriers();
        if (allAvailableCouriers.length > 0) {
            activeCourier = allAvailableCouriers[0];
        }
    }

    async function checkPaymentStatus() {
        const params = new URLSearchParams(window.location.search);
        const status = params.get('status');
        const transactionId = params.get('transactionId') || params.get('payment_id');

        if (status === 'success' && transactionId) {
            try {
                const pendingOrder = JSON.parse(sessionStorage.getItem('mb_pending_order'));
                if (!pendingOrder) return;

                pendingOrder.payment_status = 'paid';
                pendingOrder.gateway_transaction_id = transactionId;

                const response = await MainAPI.createOrder(pendingOrder);
                if (response.success) {
                    sessionStorage.removeItem('mb_pending_order');
                    const isBuynowOrder = new URLSearchParams(window.location.search).get('buynow') === '1';
                    if (isBuynowOrder) {
                        sessionStorage.removeItem('mb_buynow');
                    } else {
                        CartManager.clear();
                    }
                    showOrderSuccess(response.order.order_number, transactionId);
                }
            } catch (e) {
                console.error("Error finalizing order:", e);
                showToast('Order creation failed after payment. Please contact support.', 'error', 6000);
            }
        } else if (status === 'failed') {
            showToast('Payment failed. Please try again.', 'error');
        }
    }

    function showOrderSuccess(orderNum, paymentId) {
        const workspace = document.getElementById('cart-workspace');
        workspace.innerHTML = `
            <div style="text-align:center; padding: 5rem 2rem; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto;">
                <div style="width: 100px; height: 100px; border-radius: 50%; background: #f0fdf4; color: #22c55e; display: flex; align-items:center; justify-content:center; margin: 0 auto 1.5rem;">
                    <i data-lucide="check-circle" style="width: 50px; height: 50px;"></i>
                </div>
                <h2 style="color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem; font-weight: 800;">Order Success!</h2>
                <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 1.1rem;">Thank you for your purchase. Your order has been placed successfully.</p>
                
                <div style="background: #f8fafc; border-radius: 12px; padding: 1.5rem; margin-bottom: 2.5rem; text-align: left; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem;">
                        <span style="color: #64748b;">Order Number:</span>
                        <strong style="color: #1e293b;">#${orderNum}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Payment ID:</span>
                        <strong style="color: #1e293b;">${paymentId || 'N/A'}</strong>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="window.location.href='index.php'" style="background: white; color: #1e293b; padding: 16px 30px; border-radius: 12px; font-weight: 700; border: 1px solid #e2e8f0; cursor:pointer; font-size: 1rem; transition: 0.3s;">
                        Continue Shopping
                    </button>
                    <button onclick="window.location.href='orders.php'" style="background: var(--color-primary); color: white; padding: 16px 35px; border-radius: 12px; font-weight: 700; border:none; cursor:pointer; font-size: 1rem; transition: 0.3s; box-shadow: 0 4px 12px rgba(0,77,64,0.3);">
                        My Orders
                    </button>
                </div>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function toggleAddressMode(mode) {
        addressMode = mode;
        renderCart();
    }

    function toggleChangeAddrPanel() {
        const panel = document.getElementById('change-addr-panel');
        const btn   = document.getElementById('change-addr-btn');
        if (!panel) return;
        const open = panel.style.display === 'none';
        panel.style.display = open ? 'block' : 'none';
        if (btn) btn.textContent = open ? '✕ Cancel' : '⇄ Change Address';
    }

    function selectAddress(id) {
        selectedAddressId = id;
        const addresses = JSON.parse(sessionStorage.getItem('mb_addresses') || '[]');
        const addr = addresses.find(a => a.id == id);
        if (addr) {
            checkoutState = addr.state_name || addr.state;
            renderCart();
        }
    }

    async function deleteAddressUI(id, event) {
        if (event) event.stopPropagation();
        if (!confirm('Are you sure you want to delete this address?')) return;
        try {
            await MainAPI.deleteAddress(id);
            if (selectedAddressId == id) selectedAddressId = null;
            renderCart();
        } catch (e) {
            showToast('Error deleting address', 'error');
        }
    }

    async function updateStatesUI() {
        collectNewAddrFormState();
        const countryName = document.getElementById('cust-country').value;
        const stateSelect = document.getElementById('cust-state');
        stateSelect.innerHTML = '<option value="">Select State</option>';
        const citySelect = document.getElementById('cust-city');
        if (citySelect) citySelect.innerHTML = '<option value="">Select City</option>';
        newAddrData.state = ''; newAddrData.state_id = ''; newAddrData.city = '';
        cachedStates = []; cachedCities = [];

        const country = cachedCountries.find(c => c.name === countryName);
        if (!country) return;

        const states = await MainAPI.getStates(country.id);
        cachedStates = states;
        states.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.name;
            opt.textContent = s.name;
            opt.dataset.id = s.id;
            stateSelect.appendChild(opt);
        });
    }

    async function updateCitiesUI() {
        collectNewAddrFormState();
        const stateSelect = document.getElementById('cust-state');
        const selectedOpt = stateSelect.options[stateSelect.selectedIndex];
        const stateId = selectedOpt ? selectedOpt.dataset.id : null;

        if (stateSelect.value) {
            checkoutState = stateSelect.value;
            newAddrData.state = stateSelect.value;
            newAddrData.state_id = stateId || '';
        }

        const citySelect = document.getElementById('cust-city');
        citySelect.innerHTML = '<option value="">Select City</option>';
        newAddrData.city = '';
        cachedCities = [];

        if (stateId) {
            const cities = await MainAPI.getCities(stateId);
            cachedCities = cities;
            cities.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.name;
                opt.textContent = c.name;
                citySelect.appendChild(opt);
            });
        }

        await updateDeliveryUI();
    }

    async function updateDeliveryUI() {
        const currentId = ++lastDeliveryId;
        const cartItems = getCheckoutItems();
        const charge = await MainAPI.calculateDeliveryCharge(checkoutState, cartItems);

        if (currentId !== lastDeliveryId) return;

        const subtotal = getCheckoutTotal(cartItems);
        const total = subtotal + charge;

        const chargeEl = document.getElementById('summary-delivery-charge');
        const totalEl  = document.getElementById('summary-total-payable');
        const placeBtn = document.getElementById('place-order-btn');

        if (chargeEl) chargeEl.textContent = `₹${charge.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `₹${total.toFixed(2)}`;
        if (placeBtn) {
            placeBtn.innerHTML = `<i data-lucide="shield-check" style="width:18px;height:18px;"></i> Place Order — ₹${total.toFixed(2)}`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    async function updateSummaryPanel() {
        const cartItems = getCheckoutItems();
        const subtotal = getCheckoutTotal(cartItems);
        const currentDeliveryCharge = await MainAPI.calculateDeliveryCharge(checkoutState, cartItems);
        const totalPayable = subtotal + currentDeliveryCharge;

        const subEl = document.getElementById('summary-subtotal');
        const delEl = document.getElementById('summary-delivery-charge');
        const totEl = document.getElementById('summary-total-payable');
        const btnEl = document.getElementById('place-order-btn');

        if (subEl) subEl.textContent = `₹${subtotal.toFixed(2)}`;
        if (delEl) delEl.textContent = `₹${currentDeliveryCharge.toFixed(2)}`;
        if (totEl) totEl.textContent = `₹${totalPayable.toFixed(2)}`;
        if (btnEl) {
            btnEl.innerHTML = `<i data-lucide="shield-check" style="width:18px;height:18px;"></i> Place Order — ₹${totalPayable.toFixed(2)}`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    async function saveAndUseNewAddress() {
        const f = id => (document.getElementById(id) || {}).value?.trim() || '';
        const payload = {
            first_name:     f('cust-first-name'),
            last_name:      f('cust-last-name'),
            address_line1:  f('cust-address'),
            address_line2:  f('cust-address2') || null,
            phone_number:   f('cust-phone'),
            alternate_phone:f('cust-alt-phone') || null,
            state:          f('cust-state'),
            city:           f('cust-city'),
            zip_code:       f('cust-postcode'),
            country:        f('cust-country') || 'India',
            is_default:     true
        };

        if (!payload.first_name || !payload.phone_number || !payload.address_line1 ||
            !payload.state || !payload.city || !payload.zip_code) {
            showToast('Please fill all required address fields (*)', 'error');
            return;
        }

        const btn = document.getElementById('save-address-btn');
        btn.disabled = true;
        btn.innerHTML = 'Saving Address...';

        try {
            const saved = await MainAPI.upsertUserAddress(payload);
            selectedAddressId = saved.id;
            checkoutState = saved.state;
            addressMode = 'existing';
            showToast('Address saved successfully!', 'success');
            await renderCart();
        } catch (e) {
            showToast(e.message || 'Failed to save address', 'error');
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="save" style="width:15px;height:15px;"></i> Save &amp; Use This Address`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function selectGateway(gwId) {
        const choiceLabels = document.querySelectorAll('#gateways-list label');
        choiceLabels.forEach(lbl => {
            lbl.style.borderColor = '#e2e8f0';
            lbl.style.background = '#fff';
        });

        const selectedInput = document.querySelector(`input[name="payment-method-choice"][value="${gwId}"]`);
        if (selectedInput) {
            const lbl = selectedInput.closest('label');
            if (lbl) {
                lbl.style.borderColor = 'var(--color-primary)';
                lbl.style.background = '#f5f3ff';
            }
        }

        const gateways = JSON.parse(sessionStorage.getItem('mb_gateways') || '[]');
        if (!gateways.length) {
            MainAPI.getAvailableGateways().then(gws => {
                sessionStorage.setItem('mb_gateways', JSON.stringify(gws));
                const found = gws.find(g => g.id === gwId);
                if (found) {
                    activeGateway = found;
                    updateSummaryPanel();
                }
            });
        } else {
            const found = gateways.find(g => g.id === gwId);
            if (found) {
                activeGateway = found;
                updateSummaryPanel();
            }
        }
    }

    function selectCourier(courierId) {
        const optionCards = document.querySelectorAll('.courier-option');
        optionCards.forEach(card => card.classList.remove('selected'));

        const selectedCard = document.querySelector(`.courier-option[data-courier-id="${courierId}"]`);
        if (selectedCard) selectedCard.classList.add('selected');

        const found = allAvailableCouriers.find(c => c.id === courierId);
        if (found) {
            activeCourier = found;
        }
    }

    function openEditAddressModal(addrId) {
        const addresses = JSON.parse(sessionStorage.getItem('mb_addresses') || '[]');
        const addr = addresses.find(a => a.id == addrId);
        if (!addr) return;

        const modal = document.createElement('div');
        modal.id = 'inline-edit-modal';
        modal.className = 'modal-overlay';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="receipt-modal" style="max-width:500px;padding:2rem;">
                <h3 style="margin-top:0;color:var(--color-primary-dark);font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">Edit Address</h3>
                <div class="form-row" style="max-height:60vh;overflow-y:auto;padding-right:5px;gap:0.75rem;margin-bottom:1.5rem;">
                    <div class="form-group"><label class="form-label">First Name *</label>
                        <input class="form-input" id="ied-first" value="${addr.first_name||''}" required></div>
                    <div class="form-group"><label class="form-label">Last Name</label>
                        <input class="form-input" id="ied-last" value="${addr.last_name||''}"></div>
                    <div class="form-group full"><label class="form-label">Address Line 1 *</label>
                        <input class="form-input" id="ied-line1" value="${addr.address_line1||''}" required></div>
                    <div class="form-group full"><label class="form-label">Address Line 2</label>
                        <input class="form-input" id="ied-line2" value="${addr.address_line2||''}"></div>
                    <div class="form-group"><label class="form-label">Phone *</label>
                        <input class="form-input" id="ied-phone" value="${addr.phone_number||''}" required></div>
                    <div class="form-group"><label class="form-label">Alt Phone</label>
                        <input class="form-input" id="ied-alt-phone" value="${addr.alternate_phone||''}"></div>
                    <div class="form-group"><label class="form-label">State *</label>
                        <input class="form-input" id="ied-state" value="${addr.state||''}" required></div>
                    <div class="form-group"><label class="form-label">City *</label>
                        <input class="form-input" id="ied-city" value="${addr.city||''}" required></div>
                    <div class="form-group"><label class="form-label">Zip Code *</label>
                        <input class="form-input" id="ied-zip" value="${addr.zip_code||addr.postal_code||''}" required></div>
                    <div class="form-group"><label class="form-label">Country</label>
                        <input class="form-input" id="ied-country" value="${addr.country||'India'}"></div>
                </div>
                <div style="display:flex;gap:10px;">
                    <button onclick="document.getElementById('inline-edit-modal').remove()"
                        style="flex:1;padding:12px;background:#f4f4f4;border:none;border-radius:10px;font-weight:600;cursor:pointer;color:#64748b;">Cancel</button>
                    <button id="ied-save-btn" onclick="saveInlineEdit('${addrId}')"
                        style="flex:2;padding:12px;background:var(--color-primary);color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i data-lucide="save" style="width:16px;height:16px;"></i> Save Changes
                    </button>
                </div>
            </div>`;
        document.body.appendChild(modal);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function saveInlineEdit(addrId) {
        const btn = document.getElementById('ied-save-btn');
        const f = id => (document.getElementById(id)||{}).value?.trim() || '';
        const payload = {
            id: addrId,
            first_name:     f('ied-first'),
            last_name:      f('ied-last'),
            address_line1:  f('ied-line1'),
            address_line2:  f('ied-line2') || null,
            phone_number:   f('ied-phone'),
            alternate_phone:f('ied-alt-phone') || null,
            state:          f('ied-state'),
            city:           f('ied-city'),
            zip_code:       f('ied-zip'),
            country:        f('ied-country') || 'India'
        };
        if (!payload.first_name || !payload.address_line1 || !payload.phone_number ||
            !payload.state || !payload.city || !payload.zip_code) {
            showToast('Fill all required fields', 'error'); return;
        }
        btn.disabled = true;
        btn.innerHTML = 'Saving...';
        try {
            await MainAPI.upsertUserAddress(payload);
            document.getElementById('inline-edit-modal').remove();
            checkoutState = payload.state;
            showToast('Address updated!', 'success');
            await renderCart();
            await updateDeliveryUI();
        } catch (e) {
            showToast(e.message || 'Save failed', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="save" style="width:16px;height:16px;"></i> Save Changes';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    async function renderCart() {
        if (addressMode === 'new') collectNewAddrFormState();
        collectComments();

        const currentId = ++lastRenderId;
        isCartLoading = true;
        const isBuynow = new URLSearchParams(window.location.search).get('buynow') === '1';

        const workspace = document.getElementById('cart-workspace');
        if (!workspace) return;
        
        const cartItems = getCheckoutItems();

        if (cartItems.length === 0) {
            if (isBuynow) { sessionStorage.removeItem('mb_buynow'); window.location.href = 'index.php'; return; }
            workspace.innerHTML = `
                <div style="text-align:center; padding: 4rem 1rem;">
                    <i data-lucide="shopping-cart" style="width:60px;height:60px;color:var(--color-border);margin-bottom:1rem;"></i>
                    <h2 style="color:var(--color-primary-dark);margin-bottom:0.5rem;">Your Cart is Empty</h2>
                    <a href="cart.php" style="background:var(--color-primary);color:white;padding:12px 30px;border-radius:10px;font-weight:600;display:inline-flex;align-items:center;gap:8px;margin-top:1rem;text-decoration:none;">
                        <i data-lucide="arrow-right"></i> Go to Cart
                    </a>
                </div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            isCartLoading = false;
            return;
        }

        const cartTotal = getCheckoutTotal(cartItems);
        const gatewayNameDisplay = activeGateway ? activeGateway.name.toUpperCase() : 'PREPAID';

        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        let addresses = [];

        // Load addresses BEFORE calculating delivery so checkoutState is correct
        if (isLoggedIn) {
            addresses = await MainAPI.getUserAddresses();
            sessionStorage.setItem('mb_addresses', JSON.stringify(addresses));
            if (addresses.length > 0 && selectedAddressId === null) {
                selectedAddressId = addresses[0].id;
                checkoutState = addresses[0].state_name || addresses[0].state || checkoutState;
            }
            if (!cachedCountries.length) {
                cachedCountries = await MainAPI.getCountries();
                sessionStorage.setItem('mb_countries', JSON.stringify(cachedCountries));
            }
            if (!newAddrData.country && cachedCountries.length > 0) {
                const india = cachedCountries.find(c => c.name.toLowerCase().trim() === 'india');
                if (india) {
                    newAddrData.country = india.name;
                    if (!cachedStates.length) {
                        cachedStates = await MainAPI.getStates(india.id);
                    }
                }
            }
        }

        const currentDeliveryCharge = await MainAPI.calculateDeliveryCharge(checkoutState, cartItems);

        if (currentId !== lastRenderId) return;
        isCartLoading = false;

        const totalPayable = cartTotal + currentDeliveryCharge;

        let addressBodyHtml = '';
        if (!isLoggedIn) {
            addressBodyHtml = `
                <div class="login-banner">
                    <i data-lucide="lock" style="width:40px;height:40px;color:var(--color-text-light);"></i>
                    <h4>Sign in to checkout</h4>
                    <p style="color:var(--color-text-light);font-size:0.9rem;margin-bottom:0;">Access your saved addresses and track your orders.</p>
                    <a href="login.php" style="text-decoration:none;"><i data-lucide="log-in" style="width:16px;height:16px;"></i> Sign In / Sign Up</a>
                </div>`;
        } else if (addressMode === 'existing') {
            if (addresses.length === 0) {
                addressBodyHtml = `
                    <div class="login-banner" style="padding:2rem;">
                        <i data-lucide="map-pin" style="width:36px;height:36px;color:var(--color-text-light);"></i>
                        <h4 style="margin-bottom:0.25rem;">No saved addresses</h4>
                        <p style="color:var(--color-text-light);font-size:0.88rem;margin:0;">Switch to "New address" above to add one.</p>
                    </div>`;
            } else {
                const selAddr = addresses.find(a => a.id == selectedAddressId) || addresses[0];
                const otherAddrs = addresses.filter(a => a.id != selAddr.id);

                // ── Selected address (shown at top, prominent) ──────────
                addressBodyHtml = `
                    <div class="selected-addr-wrap">
                        <div class="selected-addr-label">
                            <i data-lucide="check-circle" style="width:14px;height:14px;color:var(--color-primary);"></i>
                            Delivering to
                        </div>
                        <div class="saved-address selected" style="margin-bottom:0;cursor:default;">
                            <div class="radio"></div>
                            <div style="flex:1;min-width:0;">
                                <div class="saved-address-name">${selAddr.first_name} ${selAddr.last_name || ''}</div>
                                <div class="saved-address-body">
                                    ${selAddr.address_line1 || ''}<br>
                                    ${selAddr.city}, ${selAddr.state_name || selAddr.state} — ${selAddr.postal_code || selAddr.zip_code}<br>
                                    ${selAddr.country || 'India'}
                                    ${selAddr.phone_number ? `<div class="phone" style="margin-top:4px;"><i data-lucide="phone" style="width:11px;height:11px;color:#e53e3e;vertical-align:middle;"></i> ${selAddr.phone_number}</div>` : ''}
                                </div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                                <button class="delete-btn" style="background:#f0fdf4;border:none;border-radius:6px;padding:5px 7px;cursor:pointer;color:var(--color-primary);display:flex;align-items:center;gap:3px;font-size:0.75rem;font-weight:600;"
                                    onclick="openEditAddressModal('${selAddr.id}')" title="Edit address">
                                    <i data-lucide="pencil" style="width:13px;height:13px;"></i>
                                </button>
                            </div>
                        </div>
                        ${otherAddrs.length > 0 ? `
                        <button class="change-addr-btn" onclick="toggleChangeAddrPanel()" id="change-addr-btn">
                            <i data-lucide="map-pin" style="width:13px;height:13px;"></i> Change Address
                        </button>` : ''}
                    </div>`;

                // ── Collapsed panel with other addresses ────────────────
                if (otherAddrs.length > 0) {
                    addressBodyHtml += `<div id="change-addr-panel" style="display:none;margin-top:10px;">
                        <div style="font-size:0.78rem;color:var(--color-text-light);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Select another address</div>
                        <div class="addr-list">`;
                    otherAddrs.forEach(addr => {
                        addressBodyHtml += `
                            <div class="saved-address" onclick="selectAddress('${addr.id}')" style="cursor:pointer;">
                                <div class="radio"></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="saved-address-name">${addr.first_name} ${addr.last_name || ''}</div>
                                    <div class="saved-address-body">
                                        ${addr.address_line1 || ''}<br>
                                        ${addr.city}, ${addr.state_name || addr.state} — ${addr.postal_code || addr.zip_code}
                                        ${addr.phone_number ? `<div class="phone" style="margin-top:3px;"><i data-lucide="phone" style="width:11px;height:11px;color:#e53e3e;vertical-align:middle;"></i> ${addr.phone_number}</div>` : ''}
                                    </div>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                                    <button class="delete-btn" style="background:#f0fdf4;border:none;border-radius:6px;padding:5px 7px;cursor:pointer;color:var(--color-primary);display:flex;align-items:center;"
                                        onclick="event.stopPropagation();openEditAddressModal('${addr.id}')" title="Edit">
                                        <i data-lucide="pencil" style="width:13px;height:13px;"></i>
                                    </button>
                                    <button class="delete-btn" onclick="deleteAddressUI('${addr.id}', event)" title="Delete">
                                        <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                    </button>
                                </div>
                            </div>`;
                    });
                    addressBodyHtml += `</div></div>`;
                }
            }
        } else {
            const countryOpts = cachedCountries.map(c =>
                `<option value="${c.name}" ${newAddrData.country === c.name ? 'selected' : ''}>${c.name}</option>`
            ).join('');
            const stateOpts = cachedStates.map(s =>
                `<option value="${s.name}" data-id="${s.id}" ${newAddrData.state === s.name ? 'selected' : ''}>${s.name}</option>`
            ).join('');
            const cityOpts = cachedCities.map(c =>
                `<option value="${c.name}" ${newAddrData.city === c.name ? 'selected' : ''}>${c.name}</option>`
            ).join('');

            addressBodyHtml = `
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input class="form-input" type="text" id="cust-first-name" placeholder="John" value="${newAddrData.first_name}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input class="form-input" type="text" id="cust-last-name" placeholder="Doe" value="${newAddrData.last_name}">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Email</label>
                        <input class="form-input" type="email" id="cust-email" placeholder="you@example.com" value="${newAddrData.email}">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Phone Number *</label>
                        <input class="form-input" type="tel" id="cust-phone" placeholder="10-digit mobile" value="${newAddrData.phone}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alternate Phone <span style="text-transform:none;color:var(--color-text-light);font-weight:500;">(optional)</span></label>
                        <input class="form-input" type="tel" id="cust-alt-phone" placeholder="Alt contact" value="${newAddrData.alt_phone}">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Address Line 1 *</label>
                        <input class="form-input" type="text" id="cust-address" placeholder="House no, street, locality" value="${newAddrData.address1}" required>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Address Line 2 <span style="text-transform:none;color:var(--color-text-light);font-weight:500;">(optional)</span></label>
                        <input class="form-input" type="text" id="cust-address2" placeholder="Apartment, landmark" value="${newAddrData.address2}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country *</label>
                        <select class="form-select" id="cust-country" onchange="updateStatesUI()" required>
                            <option value="">Select Country</option>
                            ${countryOpts}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">State *</label>
                        <select class="form-select" id="cust-state" onchange="updateCitiesUI()" required>
                            <option value="">Select State</option>
                            ${stateOpts}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City *</label>
                        <select class="form-select" id="cust-city" required>
                            <option value="">Select City</option>
                            ${cityOpts}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Post Code *</label>
                        <input class="form-input" type="text" id="cust-postcode" placeholder="000000" value="${newAddrData.postcode}" required>
                    </div>
                </div>
                <button type="button" id="save-address-btn" class="save-addr-btn" onclick="saveAndUseNewAddress()">
                    <i data-lucide="save" style="width:15px;height:15px;"></i> Save &amp; Use This Address
                </button>`;
        }

        let cartLinesHtml = '';
        cartItems.forEach(item => {
            const img = MainAPI.getProductImage(item.product);
            const title = item.product.title || item.product.name;
            const price = MainAPI.getProductPrice(item.product);
            const variantId = item.product.variant_id || '';
            const qtyControl = isBuynow
                ? `<div class="qty-stepper">
                        <button onclick="updateBuynowQty('${item.product.id}','${variantId}',${item.quantity-1})">−</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateBuynowQty('${item.product.id}','${variantId}',${item.quantity+1})">+</button>
                    </div>`
                : `<div class="qty-stepper">
                        <button onclick="CartManager.update('${item.product.id}',${item.quantity-1},'${variantId}')">−</button>
                        <span>${item.quantity}</span>
                        <button onclick="CartManager.update('${item.product.id}',${item.quantity+1},'${variantId}')">+</button>
                    </div>`;
            const removeBtn = isBuynow ? '' :
                `<button class="remove-btn" onclick="CartManager.remove('${item.product.id}','${variantId}')" title="Remove">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>`;
            cartLinesHtml += `
                <div class="cart-line" data-pid="${item.product.id}" data-vid="${variantId}">
                    <img src="${img}" class="thumb" alt="${title}">
                    <div class="info">
                        <h4 class="title">${title}</h4>
                        ${item.product.selected_variant ? `<div style="font-size:0.78rem;color:var(--color-text-light);">${item.product.selected_variant}</div>` : ''}
                        <div class="price">₹${(price * item.quantity).toFixed(2)}</div>
                        ${qtyControl}
                    </div>
                    ${removeBtn}
                </div>`;
        });

        let couriersHtml = '';
        if (allAvailableCouriers.length === 0) {
            couriersHtml = '<p style="font-size:0.88rem;color:var(--color-text-light);margin:0;">Standard delivery will be used.</p>';
        } else {
            couriersHtml = allAvailableCouriers.map(c => {
                const sel = activeCourier && activeCourier.id === c.id;
                return `<div class="courier-option ${sel ? 'selected' : ''}" data-courier-id="${c.id}" onclick="selectCourier('${c.id}')">
                    <div class="left-grp">
                        <div class="radio"></div>
                        <span style="font-weight:600;color:var(--color-primary-dark);font-size:0.92rem;">${c.name}</span>
                    </div>
                    <i data-lucide="truck" style="width:16px;height:16px;color:var(--color-text-light);"></i>
                </div>`;
            }).join('');
        }

        const html = `
            <div class="checkout-shell">
                <div class="checkout-grid-v2">
                    <div>
                        <section class="checkout-step">
                            <div class="step-header">
                                <span class="step-number">1</span>
                                <h2 class="step-title">Shipping Address</h2>
                            </div>
                            ${isLoggedIn ? `
                                <div class="addr-toggle">
                                    <label>
                                        <input type="radio" name="addr-mode" value="existing" ${addressMode==='existing'?'checked':''} onchange="toggleAddressMode('existing')">
                                        <span>Saved address</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="addr-mode" value="new" ${addressMode==='new'?'checked':''} onchange="toggleAddressMode('new')">
                                        <span>New address</span>
                                    </label>
                                </div>` : ''}
                            ${addressBodyHtml}
                        </section>

                        <section class="checkout-step">
                            <div class="step-header">
                                <span class="step-number">2</span>
                                <h2 class="step-title">Delivery Method</h2>
                            </div>
                            ${couriersHtml}
                        </section>

                        <section class="checkout-step">
                            <div class="step-header">
                                <span class="step-number">3</span>
                                <h2 class="step-title">Order Notes <span style="color:var(--color-text-light);font-weight:500;font-size:0.85rem;">(optional)</span></h2>
                            </div>
                            <textarea id="order-comments" class="form-input" placeholder="Anything we should know about your order?" style="min-height:100px;resize:vertical;"></textarea>
                        </section>
                    </div>

                    <aside>
                        <div class="summary-card-v2">
                            <h3>Order Summary</h3>
                            <div>${cartLinesHtml}</div>
                            <div class="summary-divider"></div>
                            <div class="summary-row"><span>Subtotal</span><span id="summary-subtotal">₹${cartTotal.toFixed(2)}</span></div>
                            <div class="summary-row"><span>Delivery</span><span id="summary-delivery-charge">₹${currentDeliveryCharge.toFixed(2)}</span></div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span class="amount" id="summary-total-payable">₹${totalPayable.toFixed(2)}</span>
                            </div>
                            <div class="payment-strip">
                                <span>Payment Method</span>
                                <strong>${gatewayNameDisplay}</strong>
                            </div>
                            <button type="button" id="place-order-btn" class="place-order-btn" onclick="checkout()">
                                <i data-lucide="shield-check" style="width:18px;height:18px;"></i>
                                Place Order — ₹${totalPayable.toFixed(2)}
                            </button>
                            <div class="trust-row">
                                <div class="trust-item"><i data-lucide="truck"></i> All-India shipping; delivery within 6 working days.</div>
                                <div class="trust-item"><i data-lucide="alert-circle"></i> Damage claims must be reported within 24 hours of delivery.</div>
                                <div class="trust-item"><i data-lucide="rotate-ccw"></i> No returns, no refunds policy.</div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>`;

        workspace.innerHTML = html;
        restoreComments();
        if (typeof lucide !== 'undefined') lucide.createIcons();
        isCartLoading = false;
    }

    let pendingOrderPayload = null;

    function closeReceipt() {
        document.getElementById('payment-modal').style.display = 'none';
    }

    async function checkout() {
        const user = MainAPI.getUser();
        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            window.location.href = 'login.php';
            return;
        }

        try {
            const cartItems = getCheckoutItems();
            const items = cartItems.map(i => ({
                productId: i.product.id,
                variantId: i.product.variant_id || null,
                quantity: i.quantity,
                price: MainAPI.getProductPrice(i.product),
                title: i.product.title || i.product.name
            }));

            let useAddressId = null;
            let newAddress = null;

            if (addressMode === 'existing') {
                if (!selectedAddressId) throw new Error('Please select an address before placing order');
                useAddressId = selectedAddressId;
            } else {
                const f = id => (document.getElementById(id) || {}).value?.trim() || '';
                newAddress = {
                    first_name:     f('cust-first-name'),
                    last_name:      f('cust-last-name'),
                    email:          f('cust-email'),
                    phone_number:   f('cust-phone'),
                    alternate_phone:f('cust-alt-phone') || null,
                    address_line1:  f('cust-address'),
                    address_line2:  f('cust-address2') || null,
                    country:        f('cust-country') || 'India',
                    state:          f('cust-state'),
                    city:           f('cust-city'),
                    postal_code:    f('cust-postcode')
                };
                newAddress.address_line = newAddress.address_line1;
                if (!newAddress.first_name || !newAddress.phone_number || !newAddress.address_line1 ||
                    !newAddress.city || !newAddress.state || !newAddress.postal_code) {
                    throw new Error('Please fill all required address fields (*) before placing order');
                }
                if (!/^\d{10}$/.test(newAddress.phone_number.replace(/\D/g, ''))) {
                    throw new Error('Please enter a valid 10-digit phone number');
                }
            }

            const currentDeliveryCharge = await MainAPI.calculateDeliveryCharge(checkoutState, cartItems);
            const cartTotal = getCheckoutTotal(cartItems);
            const totalAmount = cartTotal + currentDeliveryCharge;

            pendingOrderPayload = {
                email: user.email,
                name: `${user.first_name} ${user.last_name || ''}`.trim(),
                phone: user.phone_number,
                shippingAddressId: useAddressId,
                newAddress: newAddress,
                shippingMethod: 'free',
                paymentMethod: activeGateway ? activeGateway.name : 'prepaid',
                deliveryCharge: currentDeliveryCharge,
                total_price: totalAmount,
                items: items,
                comments: document.getElementById('order-comments').value,
                courier_id: activeCourier ? activeCourier.id : null,
                courier_name: activeCourier ? activeCourier.name : 'Standard'
            };

            const receiptDiv = document.getElementById('receipt-content');
            let itemsHtml = items.map(item => `
                <div class="receipt-row">
                    <span style="color:#64748b;">${item.title} (x${item.quantity})</span>
                    <span style="font-weight:600;">₹${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `).join('');

            receiptDiv.innerHTML = `
                <div style="max-height: 200px; overflow-y: auto; margin-bottom: 1.5rem; padding-right: 5px;">
                    ${itemsHtml}
                </div>
                <div class="receipt-row">
                    <span style="color:#64748b;">Subtotal</span>
                    <span>₹${cartTotal.toFixed(2)}</span>
                </div>
                <div class="receipt-row">
                    <span style="color:#64748b;">Delivery</span>
                    <span>₹${currentDeliveryCharge.toFixed(2)}</span>
                </div>
                <div class="receipt-total">
                    <span>Grand Total</span>
                    <span>₹${totalAmount.toFixed(2)}</span>
                </div>
            `;

            const gateways = await MainAPI.getAvailableGateways();
            const gwContainer = document.getElementById('gateway-selection');
            const gwList = document.getElementById('gateways-list');
            
            if (gateways.length > 0) {
                gwContainer.style.display = 'block';
                gwList.innerHTML = gateways.map(gw => `
                    <label style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid ${activeGateway && activeGateway.id === gw.id ? 'var(--color-primary)' : '#e2e8f0'}; border-radius: 8px; cursor: pointer; transition: 0.2s; background: ${activeGateway && activeGateway.id === gw.id ? '#f5f3ff' : '#fff'};">
                        <input type="radio" name="payment-method-choice" value="${gw.id}" ${activeGateway && activeGateway.id === gw.id ? 'checked' : ''} onchange="selectGateway('${gw.id}')" style="accent-color: var(--color-primary);">
                        <span style="font-size: 0.9rem; font-weight: 600; color: #1e293b;">${gw.name}</span>
                        ${gw.is_test_mode ? '<span style="font-size: 0.65rem; background: #fee2e2; color: #ef4444; padding: 2px 6px; border-radius: 4px; margin-left: auto;">Test</span>' : ''}
                    </label>
                `).join('');
            } else {
                gwContainer.style.display = 'none';
            }

            document.getElementById('payment-modal').style.display = 'flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();

        } catch (err) {
            showToast(err.message || 'Something went wrong', 'error');
        }
    }

    async function confirmAndPlaceOrder() {
        if (!pendingOrderPayload) return;
        const btn = document.getElementById('final-pay-btn');
        btn.disabled = true;
        btn.innerText = 'Redirecting to Payment...';

        try {
            sessionStorage.setItem('mb_pending_order', JSON.stringify(pendingOrderPayload));

            const gateways = await MainAPI.getAvailableGateways();
            const gateway = gateways.find(g => g.name === pendingOrderPayload.paymentMethod);
            
            if (gateway) {
                const result = await MainAPI.initiateGatewayPayment(pendingOrderPayload, gateway);
                if (result.type === 'redirect') {
                    window.location.href = result.url;
                } else if (result.type === 'function') {
                    result.fn();
                }
                return;
            } else {
                throw new Error('Selected payment gateway configuration not found');
            }

        } catch (err) {
            showToast(err.message || 'Payment failed', 'error');
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="credit-card" style="width: 18px; height: 18px;"></i> Pay Now`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
