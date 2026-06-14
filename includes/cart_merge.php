<?php
/**
 * Cart merge helper — merges a guest session cart into a user's DB cart.
 * Called server-side after successful login.
 */

function cartMergeUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function mergeGuestCartToDb($db, $userId, array $guestItems) {
    if (empty($guestItems)) return;

    // Get or create active cart
    $stmt = $db->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();

    if (!$cartId) {
        $cartId = cartMergeUUID();
        $db->prepare("INSERT INTO carts (id, user_id, status) VALUES (?, ?, 'active')")->execute([$cartId, $userId]);
    }

    foreach ($guestItems as $item) {
        $product   = $item['product'];
        $quantity  = max(1, (int)($item['quantity'] ?? 1));
        $productId = $product['id'];
        $variantId = $product['variant_id'] ?? null;

        $chk = $db->prepare(
            "SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ?
             AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))"
        );
        $chk->execute([$cartId, $productId, $variantId, $variantId]);
        $existingId = $chk->fetchColumn();

        if ($existingId) {
            $db->prepare("UPDATE cart_items SET quantity = quantity + ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
               ->execute([$quantity, $existingId]);
        } else {
            $price = (float)($product['price'] ?? $product['new_price'] ?? 0);
            $db->prepare("INSERT INTO cart_items (id, cart_id, product_id, variant_id, quantity, unit_price, product_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([cartMergeUUID(), $cartId, $productId, $variantId, $quantity, $price, json_encode($product)]);
        }
    }

    $db->prepare("UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$cartId]);
}
