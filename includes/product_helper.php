<?php
/**
 * Product Card rendering helper - Menha Boutique PHP
 */

/**
 * Pre-fetch attributes for a list of products in one query.
 * Returns an array keyed by product_id → first/default attribute row.
 */
function prefetchProductAttributes($db, array $productIds) {
    if (empty($productIds)) return [];
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    try {
        $stmt = $db->prepare(
            "SELECT * FROM product_attributes
             WHERE product_id IN ($placeholders) AND is_active = 1
             ORDER BY product_id, is_default DESC, display_order ASC"
        );
        $stmt->execute(array_values($productIds));
        $map = [];
        foreach ($stmt->fetchAll() as $a) {
            if (!isset($map[$a['product_id']])) {
                $map[$a['product_id']] = $a;
            }
        }
        return $map;
    } catch (Exception $e) {
        return [];
    }
}

function renderProductCard($prod, $db, $preloadedAttr = null) {
    $unit = isset($prod['weight']) ? $prod['weight'] : '';

    // Use pre-fetched attribute if provided, else fall back to single query
    if ($preloadedAttr !== null) {
        $attr = $preloadedAttr;
    } else {
        $stmtAttr = $db->prepare("SELECT * FROM product_attributes WHERE product_id = ? AND is_active = 1 ORDER BY is_default DESC, display_order ASC LIMIT 1");
        $stmtAttr->execute([$prod['id']]);
        $attr = $stmtAttr->fetch();
    }
    
    $price = $prod['new_price'];
    if ($attr) {
        $v = $attr['attribute_value'];
        // If variant value doesn't already contain unit, append or merge
        if ($unit && stripos($v, $unit) === false) {
            $unit = $v;
        } else {
            $unit = $v;
        }
        $price = $attr['price'];
    }
    
    $img = $prod['primary_image'] ? $prod['primary_image'] : 'https://via.placeholder.com/300x300?text=No+Image';
    $stockStatus = isset($prod['status']) ? $prod['status'] : 'In Stock';
    if ($stockStatus !== 'Coming Soon') {
        $stockStatus = ((int)$prod['stock_quantity'] > 0) ? 'In Stock' : 'Out of Stock';
    }
    $stockClass = $stockStatus === 'In Stock' ? 'in-stock' : ($stockStatus === 'Coming Soon' ? 'coming-soon' : 'out-of-stock');
    $canAdd = $stockStatus === 'In Stock';
    $rating = isset($prod['rating']) ? $prod['rating'] : '0.0';
    $key = $prod['id'];
    
    // Format JSON representation of the product for the frontend registry (needed for cart/buy triggers)
    // We encode essential product fields to JS so the local CartManager can add it.
    $jsProd = [
        'id' => $prod['id'],
        'title' => $prod['title'],
        'new_price' => $prod['new_price'],
        'weight' => $prod['weight'],
        'primary_image' => $prod['primary_image'],
        'status' => $stockStatus
    ];
    if ($attr) {
        $jsProd['product_attributes'] = [$attr];
    }
    $jsRegistryJson = json_encode($jsProd);
    
    $btns = '<div class="prod-card-btns" onclick="event.stopPropagation();">';
    if ($canAdd) {
        $btns .= '<button class="prod-add-btn" onclick="event.stopPropagation(); window.addToCartFromRegistry(' . htmlspecialchars($jsRegistryJson) . ');"><i data-lucide="shopping-cart"></i> Cart</button>';
        $btns .= '<button class="prod-buy-btn" onclick="event.stopPropagation(); window.buyNowFromRegistry(' . htmlspecialchars($jsRegistryJson) . ');">Buy Now</button>';
    } else {
        $btns .= '<button class="prod-add-btn disabled" disabled><i data-lucide="shopping-cart"></i> Cart</button>';
        $btns .= '<button class="prod-buy-btn disabled" disabled>Buy Now</button>';
    }
    $btns .= '</div>';
    
    return '
        <div class="product-card fade-in-stagger"
             data-title="' . strtolower(htmlspecialchars($prod['title'])) . '"
             data-sku="' . strtolower(htmlspecialchars(isset($prod['sku']) ? $prod['sku'] : '')) . '"
             onclick="window.location.href=\'product.php?id=' . $prod['id'] . '\';" style="cursor:pointer;">
            <div class="prod-img-box">
                <img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($prod['title']) . '" loading="lazy">
                <button class="card-wishlist-btn" data-product-id="' . $prod['id'] . '" onclick="event.stopPropagation(); window.toggleWishlistFromCard(this, ' . htmlspecialchars($jsRegistryJson) . ');" title="Toggle Wishlist">
                    <i data-lucide="heart" style="width:16px;height:16px;"></i>
                </button>
            </div>
            <div class="prod-info">
                <h3 class="prod-title">' . htmlspecialchars($prod['title']) . '</h3>
                <div class="prod-meta">
                    ' . ($unit ? '<span class="prod-variant-chip">' . htmlspecialchars($unit) . '</span>' : '') . '
                    <div class="rating-pill"><i data-lucide="star"></i> ' . htmlspecialchars($rating) . '</div>
                    <span class="stock-pill ' . $stockClass . '">' . htmlspecialchars($stockStatus) . '</span>
                </div>
                <div class="prod-price">₹' . number_format($price, 2) . '</div>
                ' . $btns . '
            </div>
        </div>';
}
