<?php
/**
 * Shopping Cart Page - Menha Boutique PHP
 * Items are fetched from DB server-side and rendered immediately.
 * JS uses the same data via window._cartData — no duplicate fetch.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// ── Fetch cart items from DB / guest session ──────────────────
$_cartItems = [];
$_subtotal  = 0.0;
$_totalQty  = 0;

function _cartImg($p)  { return !empty($p['primary_image']) ? $p['primary_image'] : (!empty($p['image']) ? $p['image'] : 'assets/images/logo.jpg'); }
function _cartPrice($p){ return (float)(!empty($p['new_price']) ? $p['new_price'] : (!empty($p['price']) ? $p['price'] : 0)); }

try {
    if (isLoggedIn()) {
        $_cu = getCurrentUser();
        $_db = getDBConnection();
        $_st = $_db->prepare("SELECT id FROM carts WHERE user_id=? AND status='active' ORDER BY created_at ASC LIMIT 1");
        $_st->execute([$_cu['id']]);
        $_cid = $_st->fetchColumn();
        if ($_cid) {
            $_st2 = $_db->prepare("
                SELECT ci.product_id, ci.variant_id, ci.quantity, ci.product_snapshot,
                       p.title, p.new_price, p.primary_image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = ? ORDER BY ci.created_at ASC
            ");
            $_st2->execute([$_cid]);
            foreach ($_st2->fetchAll() as $_row) {
                $_snap = json_decode($_row['product_snapshot'], true) ?: [];
                $_prod = array_merge(['id' => $_row['product_id'], 'title' => $_row['title'], 'new_price' => (float)$_row['new_price'], 'primary_image' => $_row['primary_image']], $_snap);
                $_qty  = (int)$_row['quantity'];
                $_cartItems[] = ['product' => $_prod, 'quantity' => $_qty];
                $_subtotal   += _cartPrice($_prod) * $_qty;
                $_totalQty   += $_qty;
            }
        }
    } elseif (!empty($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $_gi) {
            $_qty = (int)($_gi['quantity'] ?? 1);
            $_cartItems[] = $_gi;
            $_subtotal += _cartPrice($_gi['product'] ?? []) * $_qty;
            $_totalQty += $_qty;
        }
    }
} catch (Exception $_ex) { $_cartItems = []; }

$pageTitle = "Menha Boutique - My Cart";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'My Cart';
$pageTopbarSubtitle = 'Review your selected items';
$pageTopbarBack     = 'products.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<main class="main-content container" style="min-height:60vh;padding-top:0;padding-bottom:4rem;">
    <div id="cart-items-container">
    <?php if (empty($_cartItems)): ?>
        <div style="text-align:center;padding:4rem 1rem;">
            <i data-lucide="shopping-bag" style="width:70px;height:70px;color:var(--color-border);margin-bottom:1.25rem;display:block;margin-left:auto;margin-right:auto;"></i>
            <h2 style="color:var(--color-primary-dark);margin-bottom:0.5rem;">Your cart is empty</h2>
            <p style="color:var(--color-text-light);margin-bottom:1.5rem;">Looks like you haven't added anything yet.</p>
            <a href="index.php" style="background:var(--color-primary);color:#fff;padding:14px 32px;border-radius:10px;font-weight:700;display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
                <i data-lucide="arrow-right"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($_cartItems as $_item):
            $_prod      = $_item['product'];
            $_qty       = (int)$_item['quantity'];
            $_img       = _cartImg($_prod);
            $_title     = htmlspecialchars($_prod['title'] ?? $_prod['name'] ?? 'Product');
            $_price     = _cartPrice($_prod);
            $_lineTotal = number_format($_price * $_qty, 2);
            $_pid       = htmlspecialchars($_prod['id'] ?? '');
            $_vid       = htmlspecialchars($_prod['variant_id'] ?? '');
            $_variant   = htmlspecialchars($_prod['selected_variant'] ?? '');
        ?>
        <div class="cart-item">
            <img src="<?php echo $_img; ?>" class="cart-item-img" alt="<?php echo $_title; ?>" onerror="this.src='assets/images/logo.jpg'">
            <div class="cart-item-info">
                <div class="cart-item-title"><?php echo $_title; ?></div>
                <?php if ($_variant): ?><div class="cart-item-variant"><?php echo $_variant; ?></div><?php endif; ?>
                <div class="cart-item-price">₹<?php echo $_lineTotal; ?></div>
                <div class="cart-qty-stepper">
                    <button data-action="decrease" data-pid="<?php echo $_pid; ?>" data-vid="<?php echo $_vid; ?>">−</button>
                    <span><?php echo $_qty; ?></span>
                    <button data-action="increase" data-pid="<?php echo $_pid; ?>" data-vid="<?php echo $_vid; ?>">+</button>
                </div>
            </div>
            <button class="cart-remove-btn" onclick="CartManager.remove('<?php echo $_pid; ?>', '<?php echo $_vid; ?>')" title="Remove item">
                <i data-lucide="trash-2" style="width:18px;height:18px;"></i>
            </button>
        </div>
        <?php endforeach; ?>

        <div class="cart-summary-box">
            <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.95rem;color:#475569;">
                <span>Subtotal (<?php echo $_totalQty; ?> item<?php echo $_totalQty !== 1 ? 's' : ''; ?>)</span>
                <span style="font-weight:700;color:var(--color-primary-dark);">₹<?php echo number_format($_subtotal, 2); ?></span>
            </div>
            <p style="font-size:0.82rem;color:var(--color-text-light);margin-bottom:1.25rem;">Delivery charges will be calculated at checkout.</p>
            <div class="cart-action-row">
                <a href="checkout.php" class="checkout-btn">
                    <i data-lucide="shield-check" class="checkout-btn-icon"></i>
                    <span>Proceed to Checkout&nbsp;— ₹<?php echo number_format($_subtotal, 2); ?></span>
                </a>
                <a href="index.php" class="continue-btn">
                    <i data-lucide="plus" style="width:15px;height:15px;flex-shrink:0;"></i>
                    Add More
                </a>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <div class="related-section" id="related-section" style="display:none;">
        <h2>You May Also Like</h2>
        <div class="product-grid" id="related-products-grid"></div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
// PHP already rendered the correct cart from DB.
// Pre-load CartManager._items from server data so app.js skips the duplicate fetch.
window._phpCartItems = <?php echo json_encode(array_values($_cartItems)); ?>;

document.addEventListener('DOMContentLoaded', async () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // CartManager is seeded by app.js CartManager.init() via window._phpCartItems.
    // Just register the listener for user-triggered updates (qty change / remove).
    window.addEventListener('cartUpdated', renderCartPage);

    // Delegated stepper handler — reads current qty from CartManager at click time
    document.getElementById('cart-items-container').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const pid = btn.dataset.pid;
        const vid = btn.dataset.vid || '';
        const action = btn.dataset.action;
        const current = CartManager.getCart().find(
            i => String(i.product.id) === String(pid) && String(i.product.variant_id || '') === String(vid)
        );
        if (!current) return;
        const newQty = action === 'increase' ? current.quantity + 1 : current.quantity - 1;
        CartManager.update(pid, newQty, vid);
    });

    loadRelatedProducts();
});

function renderCartPage() {
    const container = document.getElementById('cart-items-container');
    const cartItems = CartManager.getCart();

    if (cartItems.length === 0) {
        container.innerHTML = `
            <div style="text-align:center;padding:4rem 1rem;">
                <i data-lucide="shopping-bag" style="width:70px;height:70px;color:var(--color-border);margin-bottom:1.25rem;"></i>
                <h2 style="color:var(--color-primary-dark);margin-bottom:0.5rem;">Your cart is empty</h2>
                <p style="color:var(--color-text-light);margin-bottom:1.5rem;">Looks like you haven\'t added anything yet.</p>
                <a href="index.php" style="background:var(--color-primary);color:#fff;padding:14px 32px;border-radius:10px;font-weight:700;display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
                    <i data-lucide="arrow-right"></i> Start Shopping
                </a>
            </div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    let itemsHtml = '';
    cartItems.forEach(item => {
        const img      = MainAPI.getProductImage(item.product);
        const title    = item.product.title || item.product.name;
        const price    = MainAPI.getProductPrice(item.product);
        const variantId = item.product.variant_id || '';
        const variant  = item.product.selected_variant || '';
        itemsHtml += `
            <div class="cart-item">
                <img src="${img}" class="cart-item-img" alt="${title}" onerror="this.src='assets/images/logo.jpg'">
                <div class="cart-item-info">
                    <div class="cart-item-title">${title}</div>
                    ${variant ? `<div class="cart-item-variant">${variant}</div>` : ''}
                    <div class="cart-item-price">₹${(price * item.quantity).toFixed(2)}</div>
                    <div class="cart-qty-stepper">
                        <button data-action="decrease" data-pid="${item.product.id}" data-vid="${variantId}">−</button>
                        <span>${item.quantity}</span>
                        <button data-action="increase" data-pid="${item.product.id}" data-vid="${variantId}">+</button>
                    </div>
                </div>
                <button class="cart-remove-btn" onclick="CartManager.remove('${item.product.id}', '${variantId}')" title="Remove item">
                    <i data-lucide="trash-2" style="width:18px;height:18px;"></i>
                </button>
            </div>`;
    });

    const subtotal   = CartManager.getTotalPrice();
    const totalItems = CartManager.getTotalItems();
    container.innerHTML = itemsHtml + `
        <div class="cart-summary-box">
            <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.95rem;color:#475569;">
                <span>Subtotal (${totalItems} item${totalItems !== 1 ? 's' : ''})</span>
                <span style="font-weight:700;color:var(--color-primary-dark);">₹${subtotal.toFixed(2)}</span>
            </div>
            <p style="font-size:0.82rem;color:var(--color-text-light);margin-bottom:1.25rem;">Delivery charges will be calculated at checkout.</p>
            <div class="cart-action-row">
                <a href="checkout.php" class="checkout-btn">
                    <i data-lucide="shield-check" class="checkout-btn-icon"></i>
                    <span>Proceed to Checkout&nbsp;— ₹${subtotal.toFixed(2)}</span>
                </a>
                <a href="index.php" class="continue-btn">
                    <i data-lucide="plus" style="width:15px;height:15px;flex-shrink:0;"></i>
                    Add More
                </a>
            </div>
        </div>`;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function loadRelatedProducts() {
    const relatedSection = document.getElementById('related-section');
    const grid = document.getElementById('related-products-grid');
    try {
        const products = await MainAPI.fetchProducts();
        if (!products || products.length === 0) return;
        grid.innerHTML = products.slice(0, 4).map(p => window.productCardHtml(p)).join('');
        relatedSection.style.display = 'block';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch(e) { console.error('Related products error:', e); }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
