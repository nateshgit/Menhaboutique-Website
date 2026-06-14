<?php
/**
 * All Products / Category List page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/product_helper.php';

$db = getDBConnection();

// Get filter inputs
$selectedCatId = isset($_GET['category_id']) ? $_GET['category_id'] : (isset($_GET['category']) ? $_GET['category'] : null);
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// 1. Fetch Categories for filter list
try {
    $stmtCats = $db->query("SELECT * FROM categories ORDER BY sequence ASC, name ASC");
    $categories = $stmtCats->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 2. Fetch selected category details if set
$selectedCategory = null;
if ($selectedCatId) {
    foreach ($categories as $cat) {
        if ($cat['id'] === $selectedCatId) {
            $selectedCategory = $cat;
            break;
        }
    }
}

// 3. Build SQL query for filtered products
$sql = "SELECT * FROM products WHERE is_active = 1";
$params = [];

if ($selectedCatId) {
    $sql .= " AND category_id = :category_id";
    $params[':category_id'] = $selectedCatId;
}

$sql .= " ORDER BY sequence ASC, created_at DESC";

try {
    $stmtProds = $db->prepare($sql);
    $stmtProds->execute($params);
    $products = $stmtProds->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Pre-fetch all product attributes in one query to avoid N+1
$attrMap = prefetchProductAttributes($db, array_column($products, 'id'));

$pageTitle = $selectedCategory ? "Menha Boutique - " . $selectedCategory['name'] : "Menha Boutique - All Products";
require_once __DIR__ . '/includes/header.php';
?>

<?php
$pageTopbarTitle    = $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'All Products';
$pageTopbarSubtitle = $selectedCategory
    ? count($products) . ' product' . (count($products) !== 1 ? 's' : '') . ' available'
    : 'Explore our complete collection';
$pageTopbarBack     = $selectedCatId ? 'categories.php' : 'index.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<main class="main-content container section-padding" style="min-height:50vh;padding-top:0;">
    <div class="products-layout">
        <!-- Sidebar -->
        <aside class="filter-sidebar" id="filter-sidebar">
            <h3><i data-lucide="filter" style="width:16px;height:16px;"></i> Categories</h3>
            <a href="products.php<?php echo $searchQuery ? '?search=' . urlencode($searchQuery) : ''; ?>" class="filter-category-item<?php echo !$selectedCatId ? ' active' : ''; ?>">
                <i data-lucide="grid" style="width:20px;height:20px;"></i> All Products
            </a>
            <div id="category-filter-list">
                <?php foreach ($categories as $cat): 
                    $imgAttr = $cat['image'] ? '<img src="' . htmlspecialchars($cat['image']) . '" alt="' . htmlspecialchars($cat['name']) . '">' : '<i data-lucide="tag" style="width:20px;height:20px;"></i>';
                    $url = 'products.php?category_id=' . urlencode($cat['id']) . ($searchQuery ? '&search=' . urlencode($searchQuery) : '');
                ?>
                    <a href="<?php echo $url; ?>" class="filter-category-item<?php echo $selectedCatId === $cat['id'] ? ' active' : ''; ?>">
                        <?php echo $imgAttr; ?> <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Products -->
        <div>
            <!-- Mobile category dropdown -->
            <div class="mobile-cat-dropdown">
                <select id="mobile-cat-select" onchange="window.location.href = this.value;">
                    <option value="products.php<?php echo $searchQuery ? '?search=' . urlencode($searchQuery) : ''; ?>">All Categories</option>
                    <?php foreach ($categories as $cat): 
                        $url = 'products.php?category_id=' . urlencode($cat['id']) . ($searchQuery ? '&search=' . urlencode($searchQuery) : '');
                    ?>
                        <option value="<?php echo $url; ?>" <?php echo $selectedCatId === $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="products-header">
                <div>
                    <h2 id="products-section-title"><?php echo $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'All Products'; ?></h2>
                    <span class="products-count"><?php echo count($products) . ' product' . (count($products) !== 1 ? 's' : ''); ?></span>
                </div>
            </div>
            
            <div style="position:relative; margin-bottom:1.25rem;">
                <input id="products-search-input" type="search" placeholder="Search products by name or SKU..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                    style="width:100%;padding:11px 42px 11px 42px;border-radius:10px;border:2px solid var(--color-border);font-family:inherit;font-size:0.92rem;outline:none;transition:0.2s;"
                    onfocus="this.style.borderColor='var(--color-primary)'"
                    onblur="this.style.borderColor='var(--color-border)'"
                    autocomplete="off">
                <i data-lucide="search" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--color-text-light);pointer-events:none;"></i>
                <button type="button" id="search-clear-btn" onclick="clearSearch()" style="display:none;position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-text-light);padding:4px;min-height:unset;">
                    <i data-lucide="x" style="width:15px;height:15px;pointer-events:none;"></i>
                </button>
            </div>
            
            <div class="product-grid" id="products-grid">
                <?php if (empty($products)): ?>
                    <div style="grid-column:1/-1;text-align:center;padding:3rem;">
                        <p style="color:#64748b;">No products found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $prod) {
                        echo renderProductCard($prod, $db, $attrMap[$prod['id']] ?? false);
                    } ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
(function () {
    var input      = document.getElementById('products-search-input');
    var clearBtn   = document.getElementById('search-clear-btn');
    var grid       = document.getElementById('products-grid');
    var countEl    = document.querySelector('.products-count');
    var timer      = null;

    function filterProducts(query) {
        var q = query.trim().toLowerCase();
        var cards = grid.querySelectorAll('.product-card');
        var visible = 0;

        cards.forEach(function (card) {
            var title = (card.dataset.title || '');
            var sku   = (card.dataset.sku   || '');
            var match = !q || title.indexOf(q) !== -1 || sku.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (countEl) {
            countEl.textContent = visible + ' product' + (visible !== 1 ? 's' : '');
        }

        var emptyMsg = grid.querySelector('.search-empty-msg');
        if (visible === 0 && q) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'search-empty-msg';
                emptyMsg.style.cssText = 'grid-column:1/-1;text-align:center;padding:3rem;';
                grid.appendChild(emptyMsg);
            }
            emptyMsg.innerHTML = '<p style="color:#64748b;">No products found for &ldquo;<strong>' + query.trim() + '</strong>&rdquo;.</p>';
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        clearBtn.style.display = q ? 'block' : 'none';
    }

    window.clearSearch = function () {
        input.value = '';
        filterProducts('');
        input.focus();
    };

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { filterProducts(input.value); }, 180);
    });

    // Apply initial filter if URL had ?search= param
    if (input.value.trim()) {
        filterProducts(input.value);
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
