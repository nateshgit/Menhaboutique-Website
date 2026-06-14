<?php
/**
 * Categories Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = "Menha Boutique - Categories";
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .grid-categories {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1.5rem;
    }
    .grid-category-item {
        background: var(--color-white);
        border-radius: var(--radius-lg);
        padding: 1.5rem 1rem;
        text-align: center;
        box-shadow: var(--shadow-sm);
        transition: var(--transition-normal);
        cursor: pointer;
        border: 1px solid var(--color-border-light);
    }
    .grid-category-item:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
        border-color: transparent;
    }
    .grid-category-item img {
        width: 80px; height: 80px;
        border-radius: 50%; object-fit: cover;
        margin: 0 auto 0.75rem;
        border: 3px solid var(--color-accent);
        display: block;
    }
    .grid-category-item h3 {
        font-size: 0.9rem;
        color: var(--color-primary-dark);
        font-weight: 700;
        word-break: break-word;
    }
    @media (max-width: 768px) {
        .grid-categories { grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
        .grid-category-item { padding: 1rem 0.5rem; border-radius: var(--radius-md); }
        .grid-category-item img { width: 56px; height: 56px; margin-bottom: 0.5rem; }
        .grid-category-item h3 { font-size: 0.78rem; }
    }
    @media (max-width: 360px) {
        .grid-categories { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<?php
$pageTopbarTitle    = 'Categories';
$pageTopbarSubtitle = 'Explore our curated wellness collections';
$pageTopbarBack     = 'index.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>
<div id="cat-topbar-dynamic" style="display:none;"><!-- JS updates page-topbar-title dynamically --></div>

<main class="main-content container section-padding" style="min-height: 50vh; padding-bottom: 4rem;">
    <div class="grid-categories" id="main-content-container">
        <!-- Loader -->
        <div class="loading-shimmer" style="height: 180px; border-radius: 16px;"></div>
        <div class="loading-shimmer" style="height: 180px; border-radius: 16px;"></div>
        <div class="loading-shimmer" style="height: 180px; border-radius: 16px;"></div>
        <div class="loading-shimmer" style="height: 180px; border-radius: 16px;"></div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    let allCategories = [];
    let currentCategoryProducts = [];
    let viewState = 'categories'; // 'categories' | 'products'
    const mainContainer = document.getElementById('main-content-container');
    const pageTitle = document.querySelector('.page-topbar-title');
    const pageSubtitle = document.querySelector('.page-topbar-subtitle');

    document.addEventListener('DOMContentLoaded', async () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Intercept topbar back button to handle in-page back navigation
        const backBtn = document.querySelector('.page-topbar-back');
        if (backBtn) {
            backBtn.addEventListener('click', function (e) {
                if (viewState === 'products') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    showAllCategories();
                }
                // When viewState === 'categories', let the href (index.php) take effect normally
            });
        }

        // Check for category ID in URL
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('id');

        if (categoryId) {
            allCategories = await MainAPI.fetchCategories();
            const cat = allCategories.find(c => String(c.id) === String(categoryId));
            if (cat) {
                await selectCategory(cat.id, cat.name);
            } else {
                await showAllCategories();
            }
        } else {
            await showAllCategories();
        }
    });

    function filterProductSearch(query) {
        const q = query.toLowerCase().trim();
        const filtered = q ? currentCategoryProducts.filter(p =>
            (p.title || '').toLowerCase().includes(q) ||
            (p.sku || '').toLowerCase().includes(q) ||
            (p.description || '').toLowerCase().includes(q)
        ) : currentCategoryProducts;
        renderProductGrid(filtered);
    }

    async function showAllCategories() {
        viewState = 'categories';
        // Restore back button to go home
        const backBtn = document.querySelector('.page-topbar-back');
        if (backBtn) backBtn.href = 'index.php';

        mainContainer.innerHTML = '<div class="loading-shimmer" style="height: 180px; border-radius: 16px;"></div>'.repeat(4);
        if (pageTitle) pageTitle.innerText = 'Categories';
        if (pageSubtitle) pageSubtitle.textContent = 'Explore our curated wellness collections';
        mainContainer.className = 'grid-categories';

        const existingSearch = document.getElementById('product-search-bar');
        if (existingSearch) existingSearch.style.display = 'none';

        allCategories = await MainAPI.fetchCategories();

        if (allCategories && allCategories.length > 0) {
            let html = '';
            allCategories.forEach(cat => {
                const img = MainAPI.getProductImage(cat);
                const name = cat.name || 'Category';
                html += `
                    <div class="grid-category-item" onclick="selectCategory('${cat.id}', '${name}')">
                        <img src="${img}" alt="${name}">
                        <h3>${name}</h3>
                    </div>
                `;
            });
            mainContainer.innerHTML = html;
        } else {
            mainContainer.innerHTML = '<p>No categories found.</p>';
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function renderProductGrid(products) {
        if (products && products.length > 0) {
            mainContainer.innerHTML = products.map(p => window.productCardHtml(p)).join('');
        } else {
            mainContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem;"><p style="color: #64748b;">No products found in this category.</p></div>';
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function selectCategory(categoryId, categoryName) {
        viewState = 'products';
        // Make back button stay on page (handled by the click listener above)
        const backBtn = document.querySelector('.page-topbar-back');
        if (backBtn) backBtn.href = 'categories.php';

        mainContainer.innerHTML = Array.from({ length: 8 }).map(() => `
            <div class="skeleton-card">
                <div class="skeleton skeleton-img"></div>
                <div class="skeleton-body">
                    <div class="skeleton skeleton-line lg w-80"></div>
                    <div class="skeleton skeleton-line w-40"></div>
                    <div class="skeleton skeleton-line w-60"></div>
                </div>
            </div>
        `).join('');
        if (pageTitle) pageTitle.innerText = categoryName;
        if (pageSubtitle) { pageSubtitle.textContent = 'Products in ' + categoryName; pageSubtitle.style.display = ''; }

        // Add search bar — inserted before mainContainer so it always has a valid parent
        let searchBar = document.getElementById('product-search-bar');
        if (!searchBar) {
            searchBar = document.createElement('div');
            searchBar.id = 'product-search-bar';
            searchBar.style.cssText = 'width:100%;margin:0 0 1rem;';
            searchBar.innerHTML = `<div style="position:relative;"><input id="product-search-input" type="text" placeholder="Search products..." style="width:100%;padding:10px 16px 10px 40px;border-radius:10px;border:2px solid var(--color-border);font-family:inherit;font-size:0.92rem;outline:none;transition:0.2s;" oninput="filterProductSearch(this.value)" onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--color-border)'"><i data-lucide="search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--color-text-light);pointer-events:none;"></i></div>`;
            mainContainer.parentElement.insertBefore(searchBar, mainContainer);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            searchBar.style.display = 'block';
            const inp = document.getElementById('product-search-input');
            if (inp) inp.value = '';
        }

        mainContainer.className = 'product-grid';

        const products = await MainAPI.fetchProductsByCategory(categoryId);
        currentCategoryProducts = products || [];
        renderProductGrid(currentCategoryProducts);
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
