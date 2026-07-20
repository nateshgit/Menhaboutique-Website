<?php
/**
 * My Wishlist page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/product_helper.php';

$db = getDBConnection();
$isLoggedIn = isLoggedIn();
$products = [];

if ($isLoggedIn) {
    $u = getCurrentUser();
    try {
        $stmt = $db->prepare("
            SELECT p.* 
            FROM wishlists w 
            JOIN products p ON w.product_id = p.id 
            WHERE w.user_id = ? AND p.is_active = 1
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$u['id']]);
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        $products = [];
    }
}

// Pre-fetch all product attributes in one query
$attrMap = prefetchProductAttributes($db, array_column($products, 'id'));

$pageTitle = "Menha Boutique - My Wishlist";
require_once __DIR__ . '/includes/header.php';
?>

<?php
$pageTopbarTitle    = 'My Wishlist';
$pageTopbarSubtitle = $isLoggedIn 
    ? count($products) . ' item' . (count($products) !== 1 ? 's' : '') . ' saved'
    : 'Your favorite personal care products';
$pageTopbarBack     = 'products.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<main class="main-content container section-padding" style="min-height:55vh;padding-top:0;">
    <div class="products-layout" style="grid-template-columns: 1fr;">
        <div>
            <div class="products-header">
                <div>
                    <h2 id="wishlist-section-title">Saved Items</h2>
                </div>
            </div>
            
            <div class="product-grid" id="wishlist-grid">
                <?php if ($isLoggedIn): ?>
                    <?php if (empty($products)): ?>
                        <div class="wishlist-empty-msg" style="grid-column:1/-1;text-align:center;padding:3rem;">
                            <p style="color:#64748b;margin-bottom:1.5rem;">Your wishlist is currently empty.</p>
                            <a href="products.php" class="btn btn-primary" style="display:inline-block;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:700;">Explore Products</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $prod) {
                            echo renderProductCard($prod, $db, $attrMap[$prod['id']] ?? false);
                        } ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Guest users: Loaded via JavaScript localStorage -->
                    <div class="wishlist-loading" style="grid-column:1/-1;text-align:center;padding:3rem;">
                        <p style="color:#64748b;">Loading saved items...</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    const grid = document.getElementById("wishlist-grid");
    
    if (!isLoggedIn) {
        // Load guest wishlist
        const items = WishlistManager.getWishlist();
        renderGuestWishlist(items);
        
        window.addEventListener("wishlistUpdated", () => {
            renderGuestWishlist(WishlistManager.getWishlist());
        });
    }

    function renderGuestWishlist(items) {
        if (!items || items.length === 0) {
            grid.innerHTML = `
                <div style="grid-column:1/-1;text-align:center;padding:3rem;">
                    <p style="color:#64748b;margin-bottom:1.5rem;">Your wishlist is currently empty.</p>
                    <a href="products.php" class="btn btn-primary" style="display:inline-block;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:700;">Explore Products</a>
                </div>
            `;
            return;
        }

        let html = "";
        items.forEach(item => {
            html += window.productCardHtml(item);
        });
        grid.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
