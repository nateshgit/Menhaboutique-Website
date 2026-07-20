<?php
/**
 * Product Details Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/product_helper.php';

$db = getDBConnection();
$productId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$productId) {
    header("Location: index.php");
    exit;
}

// Helper to generate UUIDs
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Check if user has already reviewed
$userReview = null;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    $stmtCheckReview = $db->prepare("SELECT * FROM product_reviews WHERE product_id = ? AND user_id = ? LIMIT 1");
    $stmtCheckReview->execute([$productId, $currentUser['id']]);
    $userReview = $stmtCheckReview->fetch();
}

// 1. Handle Review Submission if POSTed
$reviewError = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    if (!isLoggedIn()) {
        $reviewError = 'You must be logged in to submit a review.';
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $user = getCurrentUser();
        
        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Please select a rating between 1 and 5 stars.';
        } elseif (empty($comment)) {
            $reviewError = 'Please write a comment for your review.';
        } else {
            try {
                if ($userReview) {
                    $stmtUpdate = $db->prepare("UPDATE product_reviews SET rating = ?, comment = ? WHERE id = ?");
                    $stmtUpdate->execute([$rating, $comment, $userReview['id']]);
                    $reviewSuccess = 'Your review has been updated!';
                } else {
                    $reviewId = generateUUID();
                    $stmtInsert = $db->prepare("INSERT INTO product_reviews (id, product_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
                    $stmtInsert->execute([$reviewId, $productId, $user['id'], $rating, $comment]);
                    $reviewSuccess = 'Thank you for your review!';
                }
                
                // Update product average rating
                $stmtAvg = $db->prepare("UPDATE products SET rating = (SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE product_id = ?) WHERE id = ?");
                $stmtAvg->execute([$productId, $productId]);
                
                // Refresh userReview
                if (isLoggedIn()) {
                    $stmtCheckReview->execute([$productId, $currentUser['id']]);
                    $userReview = $stmtCheckReview->fetch();
                }
            } catch (PDOException $e) {
                $reviewError = 'Failed to submit review: ' . $e->getMessage();
            }
        }
    }
}

// 2. Fetch Product Info
$stmtProd = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmtProd->execute([$productId]);
$product = $stmtProd->fetch();

if (!$product || !$product['is_active']) {
    $pageTitle = "Product Not Found";
    require_once __DIR__ . '/includes/header.php';
    echo '<main class="main-content container section-padding" style="min-height:50vh; display:flex; align-items:center; justify-content:center; flex-direction:column;">
            <div style="text-align:center;padding:4rem;">
                <h2>Product Not Found</h2>
                <a href="index.php" style="color:var(--color-primary);font-weight:bold;margin-top:1rem;display:inline-block;">Go Back Home</a>
            </div>
          </main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 3. Fetch Product Attributes / Variants
$stmtVariants = $db->prepare("SELECT * FROM product_attributes WHERE product_id = ? AND is_active = 1 ORDER BY is_default DESC, display_order ASC");
$stmtVariants->execute([$productId]);
$variants = $stmtVariants->fetchAll();

// 4. Fetch Additional Images
$stmtImages = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC");
$stmtImages->execute([$productId]);
$additionalImages = $stmtImages->fetchAll();

// 5. Fetch Reviews
$stmtReviews = $db->prepare("
    SELECT pr.*, u.first_name, u.last_name 
    FROM product_reviews pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.product_id = ? 
    ORDER BY pr.created_at DESC
");
$stmtReviews->execute([$productId]);
$reviews = $stmtReviews->fetchAll();

// 6. Fetch Related Products
// Same category, limit 4
$stmtRelated = $db->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY sequence ASC LIMIT 4");
$stmtRelated->execute([$product['category_id'], $productId]);
$relatedProducts = $stmtRelated->fetchAll();

// You Might Also Like (other categories), limit 4
$stmtOthers = $db->prepare("SELECT * FROM products WHERE category_id != ? AND id != ? ORDER BY rand() LIMIT 4");
$stmtOthers->execute([$product['category_id'], $productId]);
$otherProducts = $stmtOthers->fetchAll();

// Prep Image Gallery
$mainImg = $product['primary_image'] ?: 'https://via.placeholder.com/500x500?text=No+Image';
$galleryImages = [$mainImg];
foreach ($additionalImages as $ai) {
    if ($ai['image_url'] && !in_array($ai['image_url'], $galleryImages)) {
        $galleryImages[] = $ai['image_url'];
    }
}
foreach ($variants as $v) {
    if ($v['image_url'] && !in_array($v['image_url'], $galleryImages)) {
        $galleryImages[] = $v['image_url'];
    }
}

// Stock class setup
$stockStatus = $product['status'] ?: 'In Stock';
if ($stockStatus !== 'Coming Soon') {
    $stockStatus = ((int)$product['stock_quantity'] > 0) ? 'In Stock' : 'Out of Stock';
}
$stockClass = $stockStatus === 'In Stock' ? 'in-stock' : ($stockStatus === 'Coming Soon' ? 'coming-soon' : 'out-of-stock');
$canBuy = $stockStatus === 'In Stock';

// Setup pricing & default variant
$selectedVariant = !empty($variants) ? $variants[0] : [
    'id' => null,
    'price' => $product['new_price'],
    'old_price' => $product['old_price'],
    'attribute_value' => $product['weight'] ?: '1 unit'
];

$pageTitle = "Menha Boutique - " . $product['title'];
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = $product['title'];
$pageTopbarSubtitle = $product['category_name'] ?: 'Product Details';
$pageTopbarBack     = !empty($product['category_id'])
    ? 'products.php?category_id=' . urlencode($product['category_id'])
    : 'products.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<style>
    /* ── Page wrapper ─────────────────────────────────── */
    #product-detail-container {
        padding: 0;
        max-width: 100%;
    }

    /* ── Layout ───────────────────────────────────────── */
    .pd-layout {
        display: flex;
        flex-direction: column;
        background: #f4f6f8;
    }

    /* ── Image gallery ────────────────────────────────── */
    .pd-gallery {
        background: #fff;
        position: relative;
    }

    .pd-main-img-wrap {
        width: 100%;
        aspect-ratio: 1 / 1;
        max-height: 380px;
        overflow: hidden;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        touch-action: pan-y;
        position: relative;
    }

    .pd-main-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.15s ease-out;
        pointer-events: none;
        display: block;
    }

    .pd-thumbs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        padding: 10px 14px 12px;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }
    .pd-thumbs::-webkit-scrollbar { display: none; }

    .pd-thumb {
        width: 64px;
        height: 64px;
        flex-shrink: 0;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid transparent;
        cursor: pointer;
        background: #f8f9fa;
        padding: 3px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        transition: border-color 0.15s;
    }
    .pd-thumb.active,
    .pd-thumb:hover { border-color: var(--color-primary); }

    /* ── Info panel ───────────────────────────────────── */
    .pd-info {
        padding: 16px 14px 100px;
        background: #fff;
        margin-top: 8px;
    }

    .pd-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .pd-category {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--color-primary);
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .pd-stock {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 8px;
        border: 1.5px solid transparent;
    }
    .pd-stock.in-stock  { color: var(--color-primary); border-color: var(--color-primary); background: rgba(0,77,64,0.06); }
    .pd-stock.out-of-stock { color: #e53935; border-color: #e53935; background: rgba(229,57,53,0.07); }
    .pd-stock.coming-soon  { color: #b45309; border-color: #f59e0b; background: rgba(245,158,11,0.09); }

    .pd-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        line-height: 1.35;
        margin: 0 0 10px;
    }

    .pd-price-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 14px;
    }

    .pd-price {
        font-size: 1.55rem;
        font-weight: 800;
        color: var(--color-primary);
    }

    .pd-old-price {
        font-size: 1rem;
        color: #999;
        text-decoration: line-through;
        font-weight: 500;
    }

    /* ── Variants ─────────────────────────────────────── */
    .pd-variant-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 8px;
    }

    .pd-variant-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .pd-chip {
        padding: 8px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        color: #475569;
        background: #fff;
        transition: 0.15s;
        font-family: inherit;
    }
    .pd-chip:hover  { border-color: var(--color-primary); color: var(--color-primary); }
    .pd-chip.active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }

    /* ── Quantity ─────────────────────────────────────── */
    .pd-qty-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 16px;
        margin-bottom: 16px;
    }

    .pd-qty-label {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--color-primary-dark);
    }

    .pd-qty-ctrl {
        display: flex;
        align-items: center;
        gap: 0;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }

    .pd-qty-btn {
        background: none;
        border: none;
        width: 36px;
        height: 36px;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--color-primary);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: unset;
    }

    .pd-qty-val {
        font-size: 0.95rem;
        font-weight: 800;
        min-width: 32px;
        text-align: center;
        color: var(--color-primary-dark);
    }

    /* ── Description ──────────────────────────────────── */
    .pd-desc-section {
        margin-top: 8px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
    }

    .pd-desc-title {
        font-size: 0.88rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .pd-desc-text-wrapper {
        position: relative;
        overflow: hidden;
        max-height: none;
        transition: max-height 0.35s ease;
    }

    .pd-desc-text-wrapper.collapsed {
        max-height: 95px; /* height that fits around 3 lines of description */
    }

    /* Elegant gradient fade for collapsed description */
    .pd-desc-text-wrapper.collapsed::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 40px;
        background: linear-gradient(to bottom, rgba(248, 250, 252, 0), rgba(248, 250, 252, 1));
        pointer-events: none;
    }

    .pd-desc-text {
        font-size: 0.88rem;
        color: #64748b;
        line-height: 1.75;
    }

    .pd-desc-toggle {
        display: inline-flex;
        align-items: center;
        background: none;
        border: none;
        color: var(--color-primary);
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 8px;
        padding: 4px 0;
        transition: all 0.2s ease;
        font-family: inherit;
        outline: none;
    }

    .pd-desc-toggle:hover {
        color: var(--color-primary-dark);
        transform: translateY(1px);
    }

    /* ── Sticky bottom CTA ────────────────────────────── */
    .pd-sticky-cta {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 900;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        padding: 10px 14px calc(10px + env(safe-area-inset-bottom));
        display: flex;
        gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
    }

    .pd-btn-cart {
        flex: 1;
        padding: 13px 10px;
        background: #fff;
        color: var(--color-primary);
        border: 2px solid var(--color-primary);
        border-radius: 12px;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        transition: 0.15s;
        font-family: inherit;
        min-height: unset;
    }
    .pd-btn-cart:hover:not(:disabled) { background: var(--color-primary); color: #fff; }
    .pd-btn-cart:disabled { opacity: 0.4; cursor: not-allowed; }

    .pd-btn-buy {
        flex: 1;
        padding: 13px 10px;
        background: var(--color-primary);
        color: #fff;
        border: 2px solid var(--color-primary);
        border-radius: 12px;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        transition: 0.15s;
        font-family: inherit;
        min-height: unset;
    }
    .pd-btn-buy:hover:not(:disabled) { background: var(--color-primary-dark); border-color: var(--color-primary-dark); }
    .pd-btn-buy:disabled { opacity: 0.4; cursor: not-allowed; }

    .pd-btn-wishlist {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
        outline: none;
    }
    .pd-btn-wishlist:hover {
        border-color: #cbd5e1;
        color: #334155;
        background: #f8fafc;
    }
    .pd-btn-wishlist.active {
        border-color: #fee2e2;
        background: #fff5f5;
        color: #ef4444;
    }
    .pd-btn-wishlist svg {
        transition: transform 0.2s ease;
    }
    .pd-btn-wishlist.active svg {
        fill: #ef4444;
        transform: scale(1.1);
    }

    /* ── Reviews ──────────────────────────────────────── */
    .pd-reviews {
        margin-top: 8px;
        background: #fff;
        padding: 18px 14px 24px;
    }

    .pd-reviews-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 6px;
    }

    .pd-reviews-title {
        font-size: 1rem;
        font-weight: 800;
        color: var(--color-primary-dark);
    }

    .pd-avg-rating {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 4px 12px;
        border-radius: 10px;
    }

    .pd-avg-score {
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--color-primary);
    }

    .pd-review-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        border: 1px solid #e2e8f0;
    }

    .pd-reviewer {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .pd-avatar {
        width: 36px;
        height: 36px;
        background: rgba(0,77,64,0.12);
        color: var(--color-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .pd-reviewer-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--color-primary-dark);
    }

    .pd-review-date {
        font-size: 0.72rem;
        color: #94a3b8;
    }

    .pd-stars {
        display: flex;
        gap: 2px;
        margin-bottom: 6px;
    }

    .pd-review-text {
        font-size: 0.85rem;
        color: #64748b;
        line-height: 1.65;
    }

    /* ── Review form ──────────────────────────────────── */
    .pd-review-form {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 14px;
        padding: 16px;
        margin-top: 16px;
    }

    .pd-form-title {
        font-size: 0.92rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        margin-bottom: 12px;
    }

    .pd-star-row {
        display: flex;
        gap: 6px;
        margin-bottom: 14px;
    }

    .pd-star {
        cursor: pointer;
        color: #cbd5e1;
        transition: color 0.15s;
    }
    .pd-star.active { color: #f59e0b; }

    .pd-review-textarea {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        font-family: inherit;
        font-size: 0.88rem;
        resize: vertical;
        min-height: 90px;
        outline: none;
        margin-bottom: 12px;
        background: #fff;
        box-sizing: border-box;
        transition: border-color 0.15s;
    }
    .pd-review-textarea:focus { border-color: var(--color-primary); }

    .pd-submit-btn {
        background: var(--color-primary);
        color: #fff;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: 0.15s;
        min-height: unset;
    }
    .pd-submit-btn:hover { background: var(--color-primary-dark); }

    .pd-login-prompt {
        text-align: center;
        padding: 16px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        color: #92400e;
        font-size: 0.88rem;
        margin-top: 12px;
    }

    /* ── Desktop overrides (768px+) ───────────────────── */
    @media (min-width: 768px) {
        #product-detail-container {
            max-width: 1400px;
            padding: 2rem 1rem;
        }

        .pd-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            background: transparent;
            min-height: unset;
            align-items: start;
        }

        .pd-gallery {
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
        }

        .pd-main-img-wrap {
            aspect-ratio: auto;
            max-height: none;
            height: 420px;
            cursor: zoom-in;
        }

        .pd-info {
            padding: 0 0 2rem;
            background: transparent;
            margin-top: 0;
        }

        .pd-title { font-size: 1.8rem; margin-bottom: 12px; }
        .pd-price { font-size: 1.9rem; }
        .pd-sticky-cta { display: none; }

        /* Inline CTA on desktop */
        .pd-inline-cta {
            display: flex !important;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .pd-reviews {
            grid-column: 1 / -1;
            background: #fff;
            border-radius: 18px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
    }

    /* Hide inline CTA on mobile (use sticky bar instead) */
    .pd-inline-cta { display: none; }

    /* Mobile zoom hint badge */
    .pd-zoom-hint {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0,0,0,0.42);
        color: #fff;
        border-radius: 20px;
        padding: 4px 10px 4px 8px;
        font-size: 0.7rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        pointer-events: none;
        transition: opacity 0.25s;
        z-index: 2;
    }
    @media (min-width: 768px) {
        .pd-zoom-hint { display: none; }
    }
</style>

<main id="product-detail-container" class="container">
    <?php if ($reviewSuccess): ?>
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 10px; margin-bottom: 1rem; text-align: center; font-weight: 600;">
            <?php echo htmlspecialchars($reviewSuccess); ?>
        </div>
    <?php endif; ?>
    <?php if ($reviewError): ?>
        <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 1rem; text-align: center; font-weight: 600;">
            <?php echo htmlspecialchars($reviewError); ?>
        </div>
    <?php endif; ?>

    <div class="pd-layout">
        <!-- Gallery -->
        <div class="pd-gallery">
            <div class="pd-main-img-wrap" id="zoom-container">
                <img src="<?php echo htmlspecialchars($mainImg); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" id="main-product-img">
                <div class="pd-zoom-hint" id="zoom-hint">
                    <i data-lucide="zoom-in" style="width:13px;height:13px;pointer-events:none;"></i> Tap to zoom
                </div>
            </div>
            
            <?php if (count($galleryImages) > 1): ?>
                <div class="pd-thumbs">
                    <?php foreach ($galleryImages as $i => $imgUrl): ?>
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" 
                             class="pd-thumb<?php echo $i === 0 ? ' active' : ''; ?>" 
                             onclick="switchMainImage('<?php echo htmlspecialchars($imgUrl); ?>', this)" 
                             loading="lazy" 
                             alt="View <?php echo $i + 1; ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="pd-info">
            <div class="pd-meta-row">
                <span class="pd-category"><?php echo htmlspecialchars($product['category_name'] ?: 'General'); ?></span>
                <span class="pd-stock <?php echo $stockClass; ?>"><?php echo htmlspecialchars($stockStatus); ?></span>
            </div>
            
            <h1 class="pd-title"><?php echo htmlspecialchars($product['title']); ?></h1>
            
            <div class="pd-price-row">
                <span class="pd-price" id="p-price">
                    ₹<?php echo number_format($selectedVariant['price'], 2); ?>
                    <?php if ($selectedVariant['old_price'] && $selectedVariant['old_price'] > $selectedVariant['price']): ?>
                        <span class="pd-old-price">₹<?php echo number_format($selectedVariant['old_price'], 2); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- Variants -->
            <?php if (!empty($variants)): ?>
                <?php 
                $isSize = false;
                foreach ($variants as $v) {
                    $type = strtolower($v['attribute_type'] ?? '');
                    $val = strtolower($v['attribute_value'] ?? '');
                    if ($type === 'size' || in_array($val, ['xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl', 'free size'])) {
                        $isSize = true;
                        break;
                    }
                }
                $label = $isSize ? (count($variants) > 1 ? 'Available Sizes' : 'Available Size') : 'Available Options';
                ?>
                <div class="pd-variant-label"><?php echo $label; ?></div>
                <div class="pd-variant-chips">
                    <?php foreach ($variants as $i => $v): 
                        // Encode variant data as JSON attributes to read in JS
                        $vJson = json_encode($v);
                    ?>
                        <button class="pd-chip<?php echo $i === 0 ? ' active' : ''; ?>" 
                                data-variant='<?php echo htmlspecialchars($vJson, ENT_QUOTES); ?>'
                                onclick="selectVariant(<?php echo $i; ?>, this)">
                            <?php echo htmlspecialchars($v['attribute_value']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($product['weight'])): ?>
                <?php 
                $wVal = strtolower($product['weight']);
                $isSize = in_array($wVal, ['xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl', 'free size']);
                $label = $isSize ? 'Available Size' : 'Available Option';
                ?>
                <div class="pd-variant-label"><?php echo $label; ?></div>
                <div class="pd-variant-chips">
                    <button class="pd-chip active" style="cursor: default;">
                        <?php echo htmlspecialchars($product['weight']); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Quantity Selection -->
            <div class="pd-qty-section">
                <span class="pd-qty-label">Quantity</span>
                <div class="pd-qty-ctrl">
                    <button class="pd-qty-btn" onclick="updateQty(-1)">−</button>
                    <span class="pd-qty-val" id="p-qty">1</span>
                    <button class="pd-qty-btn" onclick="updateQty(1)">+</button>
                </div>
            </div>
            
            <!-- Desktop Buttons -->
            <div class="pd-inline-cta" style="display:flex;gap:10px;align-items:center;">
                <button class="pd-btn-cart" id="add-to-cart-action" <?php echo $canBuy ? '' : 'disabled'; ?>>
                    <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i>
                    <?php echo $canBuy ? 'Add to Cart' : ($stockStatus === 'Coming Soon' ? 'Coming Soon' : 'Out of Stock'); ?>
                </button>
                <button class="pd-btn-buy" id="buy-now-action" <?php echo $canBuy ? '' : 'disabled'; ?>>
                    <i data-lucide="zap" style="width:18px;height:18px;"></i> Buy Now
                </button>
                <button class="pd-btn-wishlist" id="wishlist-toggle-btn" title="Add to Wishlist">
                    <i data-lucide="heart" style="width:20px;height:20px;"></i>
                </button>
            </div>
            
            <!-- Description -->
            <div class="pd-desc-section">
                <div class="pd-desc-title">Description</div>
                <div class="pd-desc-text-wrapper collapsed" id="desc-wrapper">
                    <div class="pd-desc-text" id="desc-text">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available.')); ?>
                    </div>
                </div>
                <button id="desc-toggle-btn" class="pd-desc-toggle" style="display: none;">
                    <span class="show-more-text">Show More</span>
                    <span class="show-less-text" style="display: none;">Show Less</span>
                    <span class="toggle-icon-down" style="display: inline-flex; align-items: center;"><i data-lucide="chevron-down" style="width:14px;height:14px;margin-left:4px;"></i></span>
                    <span class="toggle-icon-up" style="display: none; align-items: center;"><i data-lucide="chevron-up" style="width:14px;height:14px;margin-left:4px;"></i></span>
                </button>
            </div>
        </div>

        <!-- Reviews -->
        <div class="pd-reviews">
            <div class="pd-reviews-header">
                <span class="pd-reviews-title">Customer Reviews (<?php echo count($reviews); ?>)</span>
                <?php if (count($reviews) > 0): 
                    $avgRating = $product['rating'] ?: 0.0;
                ?>
                    <div class="pd-avg-rating">
                        <span class="pd-avg-score"><?php echo number_format($avgRating, 1); ?></span>
                        <div class="pd-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i data-lucide="star" style="width:13px;height:13px;<?php echo $s <= round($avgRating) ? 'fill:#f59e0b;color:#f59e0b;' : 'color:#e2e8f0;'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p style="text-align:center;padding:1.5rem 0;color:#94a3b8;font-size:0.88rem;">No reviews yet. Be the first to review!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): 
                        $initial = strtoupper(substr($rev['first_name'] ?: 'U', 0, 1));
                        $fullName = trim($rev['first_name'] . ' ' . $rev['last_name']);
                        if (empty($fullName)) $fullName = 'Valued Customer';
                    ?>
                        <div class="pd-review-card">
                            <div class="pd-reviewer">
                                <div class="pd-avatar"><?php echo $initial; ?></div>
                                <div>
                                    <div class="pd-reviewer-name"><?php echo htmlspecialchars($fullName); ?></div>
                                    <div class="pd-review-date"><?php echo date('d-M-Y', strtotime($rev['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="pd-stars">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i data-lucide="star" style="width:13px;height:13px;<?php echo $s <= $rev['rating'] ? 'fill:#f59e0b;color:#f59e0b;' : 'color:#e2e8f0;'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="pd-review-text"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Review form -->
            <?php if (isLoggedIn()): ?>
                <form class="pd-review-form" method="POST" action="product.php?id=<?php echo urlencode($productId); ?>">
                    <input type="hidden" name="action" value="add_review">
                    <input type="hidden" name="rating" id="form-rating-val" value="<?php echo $userReview ? (int)$userReview['rating'] : 5; ?>">
                    
                    <div class="pd-form-title"><?php echo $userReview ? 'Edit Your Review' : 'Write a Review'; ?></div>
                    <div style="font-size:0.78rem;font-weight:600;color:#64748b;margin-bottom:8px;">YOUR RATING</div>
                    <div class="pd-star-row">
                        <?php 
                        $currentRating = $userReview ? (int)$userReview['rating'] : 5;
                        for ($s = 1; $s <= 5; $s++): 
                        ?>
                            <span class="pd-star <?php echo $s <= $currentRating ? 'active' : ''; ?>" onclick="setRating(<?php echo $s; ?>)" data-star="<?php echo $s; ?>">
                                <i data-lucide="star" style="width:28px;height:28px;<?php echo $s <= $currentRating ? 'fill:#f59e0b;color:#f59e0b;' : 'color:#cbd5e1;'; ?>"></i>
                            </span>
                        <?php endfor; ?>
                    </div>
                    
                    <div style="font-size:0.78rem;font-weight:600;color:#64748b;margin-bottom:6px;">YOUR COMMENT</div>
                    <textarea name="comment" class="pd-review-textarea" placeholder="Share your experience…" required><?php echo htmlspecialchars($userReview ? $userReview['comment'] : ''); ?></textarea>
                    <button type="submit" class="pd-submit-btn"><?php echo $userReview ? 'Update Review' : 'Submit Review'; ?></button>
                </form>
            <?php else: ?>
                <div class="pd-login-prompt">
                    Please <a href="login.php" style="font-weight:800;text-decoration:underline;color:inherit;">login</a> to write a review.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pd-sticky-cta">
        <button class="pd-btn-cart" id="add-to-cart-action-mob" <?php echo $canBuy ? '' : 'disabled'; ?>>
            <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i>
            <?php echo $canBuy ? 'Add to Cart' : 'Out of Stock'; ?>
        </button>
        <button class="pd-btn-buy" id="buy-now-action-mob" <?php echo $canBuy ? '' : 'disabled'; ?>>
            <i data-lucide="zap" style="width:18px;height:18px;"></i> Buy Now
        </button>
        <button class="pd-btn-wishlist" id="wishlist-toggle-btn-mob" title="Add to Wishlist">
            <i data-lucide="heart" style="width:20px;height:20px;"></i>
        </button>
    </div>
</main>

<!-- Related Products -->
<?php if (!empty($relatedProducts) || !empty($otherProducts)): ?>
    <div class="container" id="related-products-section" style="padding-bottom:6rem; margin-top:2rem;">
        <?php if (!empty($relatedProducts)): ?>
            <div class="section-header" style="margin-bottom:1.25rem;">
                <h2 class="section-title">More from this Category</h2>
                <a href="products.php?category_id=<?php echo urlencode($product['category_id']); ?>" class="view-all-btn">View All</a>
            </div>
            <div class="product-grid" style="margin-bottom:2.5rem;">
                <?php foreach ($relatedProducts as $rel) {
                    echo renderProductCard($rel, $db);
                } ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($otherProducts)): ?>
            <div class="section-header" style="margin-bottom:1.25rem;">
                <h2 class="section-title">You Might Also Like</h2>
                <a href="products.php" class="view-all-btn">View All</a>
            </div>
            <div class="product-grid">
                <?php foreach ($otherProducts as $oth) {
                    echo renderProductCard($oth, $db);
                } ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    // Product script variables injected from PHP
    const baseProduct = <?php echo json_encode([
        'id' => $product['id'],
        'title' => $product['title'],
        'sku' => $product['sku'],
        'new_price' => $product['new_price'],
        'primary_image' => $product['primary_image'],
        'status' => $stockStatus
    ]); ?>;
    
    let currentQty = 1;
    let selectedVariant = <?php echo json_encode($selectedVariant); ?>;

    function updateQty(delta) {
        currentQty = Math.max(1, currentQty + delta);
        document.getElementById('p-qty').innerText = currentQty;
    }

    function selectVariant(index, btn) {
        selectedVariant = JSON.parse(btn.getAttribute('data-variant'));
        document.querySelectorAll('.pd-chip').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        
        const priceEl = document.getElementById('p-price');
        let html = `₹` + parseFloat(selectedVariant.price).toFixed(2);
        if (selectedVariant.old_price && parseFloat(selectedVariant.old_price) > parseFloat(selectedVariant.price)) {
            html += ` <span class="pd-old-price">₹` + parseFloat(selectedVariant.old_price).toFixed(2) + `</span>`;
        }
        priceEl.innerHTML = html;
        
        if (selectedVariant.image_url) {
            const mainImg = document.getElementById('main-product-img');
            if (mainImg) mainImg.src = selectedVariant.image_url;
            document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
        }
    }

    function switchMainImage(url, thumb) {
        document.getElementById('main-product-img').src = url;
        document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
        if (thumb) thumb.classList.add('active');
    }

    function setRating(rating) {
        document.getElementById('form-rating-val').value = rating;
        document.querySelectorAll('.pd-star').forEach((s, i) => {
            const starNum = parseInt(s.getAttribute('data-star'));
            const ico = s.querySelector('i');
            if (starNum <= rating) {
                s.classList.add('active');
                ico.setAttribute('fill', '#f59e0b');
                ico.style.color = '#f59e0b';
            } else {
                s.classList.remove('active');
                ico.removeAttribute('fill');
                ico.style.color = '#cbd5e1';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Description Expand/Collapse Logic
        const descWrapper = document.getElementById('desc-wrapper');
        const descText = document.getElementById('desc-text');
        const descToggleBtn = document.getElementById('desc-toggle-btn');

        if (descWrapper && descText && descToggleBtn) {
            // Measure scrollHeight to determine if we need the show more toggle
            const collapsedHeight = 95;
            // 15px buffer to avoid showing button for minor overflow
            if (descText.scrollHeight > collapsedHeight + 15) {
                descToggleBtn.style.display = 'inline-flex';
                descWrapper.classList.add('collapsed');
            } else {
                descWrapper.classList.remove('collapsed');
                descToggleBtn.style.display = 'none';
            }

            descToggleBtn.addEventListener('click', () => {
                const isCollapsed = descWrapper.classList.contains('collapsed');
                if (isCollapsed) {
                    descWrapper.classList.remove('collapsed');
                    descToggleBtn.querySelector('.show-more-text').style.display = 'none';
                    descToggleBtn.querySelector('.show-less-text').style.display = 'inline';
                    descToggleBtn.querySelector('.toggle-icon-down').style.display = 'none';
                    descToggleBtn.querySelector('.toggle-icon-up').style.display = 'inline-flex';
                } else {
                    descWrapper.classList.add('collapsed');
                    descToggleBtn.querySelector('.show-more-text').style.display = 'inline';
                    descToggleBtn.querySelector('.show-less-text').style.display = 'none';
                    descToggleBtn.querySelector('.toggle-icon-down').style.display = 'inline-flex';
                    descToggleBtn.querySelector('.toggle-icon-up').style.display = 'none';
                }
            });
        }

        // Zoom and Pan Logic
        const zoomContainer = document.getElementById('zoom-container');
        const mainImg = document.getElementById('main-product-img');
        if (zoomContainer && mainImg) {
            zoomContainer.addEventListener('mousemove', e => {
                if (window.innerWidth < 768) return;
                const r = zoomContainer.getBoundingClientRect();
                mainImg.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
                mainImg.style.transform = 'scale(2.5)';
            });
            zoomContainer.addEventListener('mouseleave', () => { mainImg.style.transform = 'scale(1)'; });

            let mobileZoomed = false;
            let touchStartX = 0, touchStartY = 0;

            zoomContainer.addEventListener('touchstart', e => {
                if (window.innerWidth >= 768) return;
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }, { passive: true });

            zoomContainer.addEventListener('touchmove', e => {
                if (!mobileZoomed || window.innerWidth >= 768) return;
                const t = e.touches[0];
                const r = zoomContainer.getBoundingClientRect();
                mainImg.style.transformOrigin = `${((t.clientX - r.left) / r.width) * 100}% ${((t.clientY - r.top) / r.height) * 100}%`;
                e.preventDefault();
            }, { passive: false });

            zoomContainer.addEventListener('touchend', e => {
                if (window.innerWidth >= 768) return;
                const t = e.changedTouches[0];
                const dx = Math.abs(t.clientX - touchStartX);
                const dy = Math.abs(t.clientY - touchStartY);
                if (!mobileZoomed && dx < 10 && dy < 10) {
                    const r = zoomContainer.getBoundingClientRect();
                    mainImg.style.transformOrigin = `${((t.clientX - r.left) / r.width) * 100}% ${((t.clientY - r.top) / r.height) * 100}%`;
                    mainImg.style.transform = 'scale(2.2)';
                    zoomContainer.style.touchAction = 'none';
                    mobileZoomed = true;
                    const hint = document.getElementById('zoom-hint');
                    if (hint) hint.style.opacity = '0';
                } else if (mobileZoomed && dx < 10 && dy < 10) {
                    mainImg.style.transform = 'scale(1)';
                    zoomContainer.style.touchAction = 'pan-y';
                    mobileZoomed = false;
                    const hint = document.getElementById('zoom-hint');
                    if (hint) hint.style.opacity = '1';
                }
            }, { passive: true });
        }

        // Add to Cart / Buy Now triggers
        function buildCartItem() {
            const qty = parseInt(document.getElementById('p-qty').innerText) || 1;
            const item = { 
                id: baseProduct.id,
                title: baseProduct.title,
                sku: baseProduct.sku,
                primary_image: baseProduct.primary_image,
                status: baseProduct.status,
                price: parseFloat(selectedVariant.price), 
                selected_variant: selectedVariant.attribute_value, 
                variant_id: selectedVariant.id 
            };
            return { item, qty };
        }

        function handleAddToCart(btn) {
            const { item, qty } = buildCartItem();
            if (window.CartManager) {
                window.CartManager.add(item, qty);
                const orig = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" style="width:18px;height:18px;"></i> Added ₹' + (parseFloat(selectedVariant.price) * qty).toFixed(2);
                btn.style.background = '#16a34a';
                btn.style.borderColor = '#16a34a';
                btn.style.color = '#fff';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = orig;
                    btn.style.background = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }, 1800);
            }
        }

        function handleBuyNow() {
            const { item, qty } = buildCartItem();
            sessionStorage.setItem('mb_buynow', JSON.stringify([{ product: item, quantity: qty }]));
            window.location.href = 'checkout.php?buynow=1';
        }

        ['add-to-cart-action', 'add-to-cart-action-mob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', function() { handleAddToCart(this); });
        });
        ['buy-now-action', 'buy-now-action-mob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', handleBuyNow);
        });

        // Wishlist Toggle Logic
        function updateWishlistButtons() {
            const isWish = window.WishlistManager && window.WishlistManager.has(baseProduct.id);
            ['wishlist-toggle-btn', 'wishlist-toggle-btn-mob'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.toggle('active', isWish);
                }
            });
        }
        window.addEventListener('wishlistUpdated', updateWishlistButtons);
        ['wishlist-toggle-btn', 'wishlist-toggle-btn-mob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', async function() {
                    if (window.WishlistManager) {
                        el.disabled = true;
                        try {
                            const pItem = {
                                id: baseProduct.id,
                                title: baseProduct.title,
                                sku: baseProduct.sku,
                                primary_image: baseProduct.primary_image,
                                status: baseProduct.status,
                                new_price: baseProduct.new_price,
                                rating: baseProduct.rating || '0.0',
                                weight: baseProduct.weight
                            };
                            await window.WishlistManager.toggle(pItem);
                        } finally {
                            el.disabled = false;
                        }
                    }
                });
            }
        });
        setTimeout(updateWishlistButtons, 100);
        setTimeout(updateWishlistButtons, 500);
    });
</script>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
