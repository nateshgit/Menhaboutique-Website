<?php
/**
 * Homepage - Menha Boutique PHP
 */

$pageTitle = "Menha Boutique - Premium Self-Care & Wellness";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/product_helper.php';

$db = getDBConnection();

try {
    $stmtBanners = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sequence ASC");
    $banners = $stmtBanners->fetchAll();
} catch (PDOException $e) { $banners = []; }

try {
    $stmtCategories = $db->query("SELECT * FROM categories ORDER BY sequence ASC, name ASC");
    $categories = $stmtCategories->fetchAll();
} catch (PDOException $e) { $categories = []; }

try {
    $stmtProducts = $db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY sequence ASC, created_at DESC LIMIT 12");
    $products = $stmtProducts->fetchAll();
} catch (PDOException $e) { $products = []; }

// Pre-fetch attributes for homepage products in one query
$homeAttrMap = prefetchProductAttributes($db, array_column($products, 'id'));

try {
    $db->exec("CREATE TABLE IF NOT EXISTS `home_reviews` (
        `id` VARCHAR(36) PRIMARY KEY,
        `reviewer_name` VARCHAR(255) NOT NULL,
        `review_text` TEXT DEFAULT NULL,
        `rating` INT NOT NULL DEFAULT 5,
        `media_url` VARCHAR(500) DEFAULT NULL,
        `media_type` VARCHAR(10) NOT NULL DEFAULT 'image',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `sequence` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $stmtReviews = $db->query("SELECT * FROM home_reviews WHERE is_active = 1 ORDER BY sequence ASC, created_at DESC");
    $homeReviews = $stmtReviews->fetchAll();
} catch (PDOException $e) { $homeReviews = []; }
?>

<main class="main-content">

    <!-- ── Banner ─────────────────────────────────────────── -->
    <section class="banner-section">
        <div class="banner-wrapper">
            <div class="banner-carousel" id="banner-container">
                <?php if (empty($banners)): ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-primary-dark),var(--color-primary));display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;text-align:center;">
                        <p style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:#fff;opacity:0.9;">Discover Premium Wellness</p>
                        <p style="color:rgba(255,255,255,0.6);margin-top:0.5rem;font-size:0.95rem;">Curated self-care collections, just for you.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($banners as $i => $banner):
                        $img  = $banner['image_url'];
                        $link = $banner['link_url'];
                        $imgTag = $img
                            ? '<img src="'.htmlspecialchars($img).'" alt="Banner '.($i+1).'" class="banner-img" loading="'.($i===0?'eager':'lazy').'" decoding="async" onerror="this.parentElement.style.background=\'linear-gradient(135deg,#00251A,#004D40)\';this.style.display=\'none\';">'
                            : '<div style="width:100%;height:100%;background:linear-gradient(135deg,#00251A,#004D40);"></div>';
                        $inner = $link
                            ? '<a href="'.htmlspecialchars($link).'" style="display:block;width:100%;height:100%;">'.$imgTag.'</a>'
                            : $imgTag;
                    ?>
                        <div class="banner-slide<?php echo $i===0?' active':''; ?>">
                            <?php echo $inner; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($banners) > 1): ?>
                <button class="carousel-btn prev" onclick="carouselPrev()"><i data-lucide="chevron-left"></i></button>
                <button class="carousel-btn next" onclick="carouselNext()"><i data-lucide="chevron-right"></i></button>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Feature Strip (scrolling marquee) ────────────────── -->
    <div class="feature-strip">
        <div class="feature-marquee-track">
            <div class="feature-marquee-inner" id="feature-marquee">
                <span class="feature-item"><i data-lucide="truck"></i> Free Delivery on Orders ₹899+</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="shield-check"></i> 100% Authentic Products</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="headphones"></i> 24/7 Customer Support</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="gift"></i> Exclusive Offers Every Week</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="star"></i> Premium Quality Guaranteed</span>
                <span class="feature-sep">✦</span>
                <!-- duplicate for seamless loop -->
                <span class="feature-item"><i data-lucide="truck"></i> Free Delivery on Orders ₹899+</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="shield-check"></i> 100% Authentic Products</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="headphones"></i> 24/7 Customer Support</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="gift"></i> Exclusive Offers Every Week</span>
                <span class="feature-sep">✦</span>
                <span class="feature-item"><i data-lucide="star"></i> Premium Quality Guaranteed</span>
                <span class="feature-sep">✦</span>
            </div>
        </div>
    </div>

    <!-- ── Categories ─────────────────────────────────────── -->
    <section class="category-section" style="padding-top: var(--section-v); padding-bottom: 0;">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <h2 class="section-title">Categories</h2>
                </div>
                <a href="categories.php" class="view-all-btn">View All</a>
            </div>
        </div>

        <?php if (empty($categories)): ?>
            <div class="container"><p style="color:var(--color-text-muted);">No categories found.</p></div>
        <?php else: ?>
            <?php
            // Build the set of circular category items
            ob_start();
            foreach ($categories as $cat):
                $img  = $cat['image'] ?: 'https://via.placeholder.com/100?text=Category';
                $name = $cat['name'];
            ?>
                <a href="products.php?category_id=<?php echo urlencode($cat['id']); ?>" class="category-circle-item" aria-label="<?php echo htmlspecialchars($name); ?>">
                    <div class="category-circle-img-wrapper">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($name); ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/100?text=<?php echo urlencode($name); ?>'">
                    </div>
                    <span class="category-circle-label"><?php echo htmlspecialchars($name); ?></span>
                </a>
            <?php endforeach;
            $catItemsHtml = ob_get_clean();
            ?>
            <div class="container">
                <div class="category-static-grid reveal">
                    <?php echo $catItemsHtml; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ── Divider ────────────────────────────────────────── -->
    <div style="background:var(--color-white);border-top:1px solid var(--color-border-light);"></div>

    <!-- ── All Products ───────────────────────────────────── -->
    <section class="section-padding" style="background:var(--color-off-white); padding-top: 2.2rem;">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <h2 class="section-title">Our Collection</h2>
                    <p class="section-subtitle">Handpicked products for your daily wellness routine</p>
                </div>
                <a href="products.php" class="view-all-btn">View All</a>
            </div>
            <div class="product-grid" id="product-container">
                <?php if (empty($products)): ?>
                    <p style="color:var(--color-text-muted);grid-column:1/-1;text-align:center;">No products found.</p>
                <?php else: ?>
                    <?php foreach ($products as $prod) { echo renderProductCard($prod, $db, $homeAttrMap[$prod['id']] ?? false); } ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <!-- ── Happy Customers (Stats Section) ────────────────── -->
    <section class="stats-section reveal">
        <div class="container">
            <div class="stats-grid-layout">
                <div class="stat-item">
                    <span class="stat-number" data-target="15000" data-suffix="+">0</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="150" data-suffix="+">0</span>
                    <span class="stat-label">Premium Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="99" data-suffix="%">0</span>
                    <span class="stat-label">Satisfaction Rate</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="100" data-suffix="%">0</span>
                    <span class="stat-label">Natural & Organic</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Why Choose Us ──────────────────────────────────── -->
    <section class="section-padding" style="background:var(--color-white);">
        <div class="container">
            <div class="section-header reveal" style="justify-content:center;text-align:center;display:block;margin-bottom:2.5rem;">
                <h2 class="section-title">Why Menha Boutique?</h2>
                <p class="section-subtitle">We believe in quality you can feel</p>
            </div>
            <div class="why-grid-layout">
                <?php
                $whys = [
                    ['star','Premium Quality','Every product is hand-selected for quality and authenticity.'],
                    ['leaf','Natural Ingredients','We prioritize clean, skin-friendly formulations.'],
                    ['shield-check','Trusted Brand','Serving thousands of happy customers across India.'],
                    ['heart','With Love','Crafted with care for your daily self-care ritual.'],
                ];
                foreach ($whys as $i => $w): ?>
                <div class="reveal reveal-delay-<?php echo $i+1; ?> why-card">
                    <div class="why-icon-wrap">
                        <i data-lucide="<?php echo $w[0]; ?>" class="why-icon"></i>
                    </div>
                    <h3 class="why-card-title"><?php echo $w[1]; ?></h3>
                    <p class="why-card-desc"><?php echo $w[2]; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ── Customer Reviews ───────────────────────────────── -->
    <?php if (!empty($homeReviews)): ?>
    <section class="section-padding home-reviews-section">
        <div class="container">
            <div class="section-header reveal" style="justify-content:center;text-align:center;display:block;margin-bottom:2.5rem;">
                <h2 class="section-title">What Our Customers Say</h2>
                <p class="section-subtitle">Real stories from our happy community</p>
            </div>
            <div class="home-reviews-scroll" id="home-reviews-scroll">
                <?php foreach ($homeReviews as $i => $rev):
                    $mediaUrl = $rev['media_url'] ?? '';
                    $mediaType = $rev['media_type'] ?? 'image';
                    $name = htmlspecialchars($rev['reviewer_name'] ?? '');
                    $text = htmlspecialchars($rev['review_text'] ?? '');
                    $rating = (int)($rev['rating'] ?? 5);
                    $stars = str_repeat('<span class="hr-star hr-star-filled">★</span>', min($rating, 5))
                           . str_repeat('<span class="hr-star hr-star-empty">★</span>', max(0, 5 - $rating));
                ?>
                <div class="home-review-card reveal reveal-delay-<?php echo ($i % 4) + 1; ?>">
                    <?php if ($mediaUrl): ?>
                        <div class="hr-media-wrap">
                            <?php if ($mediaType === 'video' || $mediaType === 'video_url'): ?>
                                <video src="<?php echo htmlspecialchars($mediaUrl); ?>" class="hr-media" controls playsinline muted preload="none"
                                    onerror="this.closest('.hr-media-wrap').style.display='none'"></video>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($mediaUrl); ?>" alt="<?php echo $name; ?>" class="hr-media"
                                    loading="lazy" onerror="this.closest('.hr-media-wrap').style.display='none'">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="hr-body">
                        <div class="hr-stars"><?php echo $stars; ?></div>
                        <?php if ($text): ?><p class="hr-text">"<?php echo $text; ?>"</p><?php endif; ?>
                        <div class="hr-author">
                            <div class="hr-avatar"><?php echo mb_strtoupper(mb_substr($rev['reviewer_name'], 0, 1)); ?></div>
                            <span class="hr-name"><?php echo $name; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Banner carousel
    var slides = document.querySelectorAll('.banner-slide');
    if (slides.length <= 1) return;
    var current = 0;

    function goTo(index) {
        var prev = current;
        current = (index + slides.length) % slides.length;
        if (prev === current) return;
        slides[current].style.transition = 'none';
        slides[current].classList.remove('active', 'slide-out');
        void slides[current].offsetWidth;
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                slides[current].style.transition = '';
                slides[current].classList.add('active');
            });
        });
        slides[prev].classList.add('slide-out');
        slides[prev].classList.remove('active');
        setTimeout(function () {
            slides[prev].style.transition = 'none';
            slides[prev].classList.remove('slide-out');
            void slides[prev].offsetWidth;
            slides[prev].style.transition = '';
        }, 680);
    }

    window.carouselNext = function () { goTo(current + 1); resetTimer(); };
    window.carouselPrev = function () { goTo(current - 1); resetTimer(); };

    function resetTimer() { clearInterval(timer); timer = setInterval(window.carouselNext, 4500); }
    var timer = setInterval(window.carouselNext, 4500);

    var bc = document.getElementById('banner-container');
    if (bc) {
        bc.addEventListener('mouseenter', function () { clearInterval(timer); });
        bc.addEventListener('mouseleave', resetTimer);
        var tx = 0;
        bc.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; clearInterval(timer); }, { passive: true });
        bc.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - tx;
            if (Math.abs(dx) > 50) { dx < 0 ? goTo(current + 1) : goTo(current - 1); }
            resetTimer();
        }, { passive: true });
    }

    // Stats Counting Animation
    const stats = document.querySelectorAll('.stat-number');
    function animateCounter(el) {
        const target = parseInt(el.getAttribute('data-target'), 10);
        const suffix = el.getAttribute('data-suffix') || '';
        const duration = 1800; // 1.8s duration
        let startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            const progress = timestamp - startTime;
            const percentage = Math.min(progress / duration, 1);
            const easedProgress = percentage * (2 - percentage); // Ease out quad
            const current = Math.floor(easedProgress * target);

            if (target >= 1000) {
                el.textContent = current.toLocaleString('en-US') + suffix;
            } else {
                el.textContent = current + suffix;
            }

            if (progress < duration) {
                requestAnimationFrame(step);
            } else {
                if (target >= 1000) {
                    el.textContent = target.toLocaleString('en-US') + suffix;
                } else {
                    el.textContent = target + suffix;
                }
            }
        }
        requestAnimationFrame(step);
    }

    if (stats.length > 0) {
        const statsObserver = new IntersectionObserver((entries, observerInstance) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observerInstance.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        stats.forEach(stat => statsObserver.observe(stat));
    }
});
</script>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
