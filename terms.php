<?php
/**
 * Terms and Conditions Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';

$pageTitle = "Menha Boutique - Terms & Conditions";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'Terms & Conditions';
$pageTopbarSubtitle = 'Please read our terms carefully before using our services';
$pageTopbarBack     = 'index.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<style>
    .policy-hero {
        background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
        color: white;
        padding: 3rem 0;
        text-align: center;
    }

    .policy-hero h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
    }

    .policy-hero p {
        opacity: 0.85;
        font-size: 1rem;
    }

    .policy-content {
        max-width: 860px;
        margin: 0 auto;
        padding: 2rem 1.5rem 4rem;
    }

    .policy-section {
        background: var(--color-white);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        border-left: 4px solid var(--color-primary);
    }

    .policy-section h2 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-primary-dark);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .policy-section p,
    .policy-section li {
        color: var(--color-text-light);
        line-height: 1.85;
        font-size: 0.97rem;
    }

    .policy-section ul {
        padding-left: 1.5rem;
        margin-top: 0.5rem;
    }

    .policy-section ul li {
        margin-bottom: 0.5rem;
    }

    .policy-contact-card {
        background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
        color: white;
        border-radius: 16px;
        padding: 2rem 2.5rem;
        text-align: center;
        margin-top: 2rem;
    }

    .policy-contact-card h2 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .policy-contact-card p {
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }

    .policy-contact-card a {
        color: white;
        text-decoration: underline;
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem 0;
        font-size: 0.9rem;
        color: var(--color-text-light);
        border-bottom: 1px solid var(--color-border);
        margin-bottom: 2rem;
    }

    .breadcrumb a {
        color: var(--color-primary);
        font-weight: 500;
    }
</style>

<main class="main-content">
    <div class="policy-content">
        <nav class="breadcrumb">
            <a href="index.php">Home</a>
            <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
            <span>Terms &amp; Conditions</span>
        </nav>

        <p style="color:var(--color-text-light); margin-bottom:2rem; line-height:1.8;">
            Welcome to <strong>Menha Boutique</strong>. By accessing and using this website, you agree to comply
            with and be bound by the following terms and conditions. If you disagree with any part of these terms,
            please do not use our website.
        </p>

        <!-- Section 1 -->
        <div class="policy-section">
            <h2><i data-lucide="info" style="width:20px;color:var(--color-primary);"></i> 1. General Terms</h2>
            <p>
                By accessing and using the Menha Boutique website, you confirm that you are at least 18 years of
                age, or that you are using this site under the supervision of a parent or legal guardian. We reserve
                the right to update or modify these terms at any time without prior notice. Your continued use of
                the site following any changes constitutes your acceptance of the revised terms.
            </p>
        </div>

        <!-- Section 2 -->
        <div class="policy-section">
            <h2><i data-lucide="tag" style="width:20px;color:var(--color-primary);"></i> 2. Products &amp; Authenticity</h2>
            <p>
                Our products are carefully curated and sourced to ensure quality. As fashion items may vary in color
                slightly due to screen display settings, these variations are natural and do not constitute a
                defect. We strive to provide accurate product descriptions, including sizes and materials.
            </p>
        </div>

        <!-- Section 3 -->
        <div class="policy-section">
            <h2><i data-lucide="credit-card" style="width:20px;color:var(--color-primary);"></i> 3. Pricing &amp; Payments</h2>
            <p>
                All prices listed on our website are current and subject to change without notice. We accept various
                payment methods, and all transactions are processed securely. We reserve the right to cancel any
                order if there are issues with payment or stock availability. In the event of a pricing error, we
                will notify you before processing your order.
            </p>
        </div>

        <!-- Section 4 -->
        <div class="policy-section">
            <h2><i data-lucide="truck" style="width:20px;color:var(--color-primary);"></i> 4. Shipping Policy</h2>
            <ul>
                <li>We strive to deliver your orders within the estimated timeframes. Delivery times may vary due to
                    external factors.</li>
                <li>Please ensure that your shipping address is accurate to avoid delays.</li>
                <li>We are not responsible for packages lost due to incorrect addresses provided by the customer.</li>
                <li>All India shipping is available. The product will be delivered within 6 working days to your
                    registered address from the date of order.</li>
            </ul>
        </div>

        <!-- Section 5 -->
        <div class="policy-section">
            <h2><i data-lucide="rotate-ccw" style="width:20px;color:var(--color-primary);"></i> 5. Returns &amp; Refunds</h2>
            <ul>
                <li>No returns or refunds will be provided once the product is delivered, due to the nature of
                    fashion items we sell.</li>
                <li>In case of valid damage claims, these must be reported within <strong>24 hours</strong> of
                    product delivery for replacement, with photographic evidence.</li>
                <li>At our discretion, we may offer a replacement only. The new item will be delivered within 4–5
                    working days. No exchange will be accepted.</li>
                <li>If any refund is approved under any circumstances, the amount will be credited back to your
                    original payment method within <strong>5–10 working days</strong>.</li>
            </ul>
        </div>

        <!-- Section 6 -->
        <div class="policy-section">
            <h2><i data-lucide="user" style="width:20px;color:var(--color-primary);"></i> 6. User Accounts</h2>
            <p>
                If you create an account on our website, you are responsible for maintaining the confidentiality of
                your account and password. You agree to accept responsibility for all activities that occur under
                your account. Please notify us immediately of any unauthorized use of your account.
            </p>
        </div>

        <!-- Section 7 -->
        <div class="policy-section">
            <h2><i data-lucide="shield" style="width:20px;color:var(--color-primary);"></i> 7. Intellectual Property</h2>
            <p>
                All content on this website, including images, text, logos, and design, is the property of Menha
                Boutique and is protected by applicable intellectual property laws. You may not reproduce,
                distribute, or use any content from this site without our express written permission.
            </p>
        </div>

        <!-- Section 8 -->
        <div class="policy-section">
            <h2><i data-lucide="lock" style="width:20px;color:var(--color-primary);"></i> 8. Privacy Policy</h2>
            <p>
                Your privacy is important to us. We collect and use your personal information in accordance with our
                <a href="privacy-policy.php" style="color:var(--color-primary);font-weight:600;">Privacy Policy</a>.
                We do not sell or share your data with third parties for marketing purposes without your consent.
            </p>
        </div>

        <!-- Contact Card -->
        <div class="policy-contact-card">
            <h2>Have Questions?</h2>
            <p>If you have any questions about these Terms &amp; Conditions, please contact us:</p>
            <p>📧 <a href="mailto:info@menhaboutique.com">info@menhaboutique.com</a></p>
            <a href="index.php"
                style="display:inline-block; margin-top:1rem; background:white; color:var(--color-primary); padding:12px 30px; border-radius:50px; font-weight:700; text-decoration:none;">
                Back to Home
            </a>
        </div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
