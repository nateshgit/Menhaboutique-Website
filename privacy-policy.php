<?php
/**
 * Privacy Policy Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';

$pageTitle = "Menha Boutique - Privacy Policy";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'Privacy Policy';
$pageTopbarSubtitle = 'Your privacy matters to us';
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

    .policy-section h3 {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--color-primary-dark);
        margin: 1.25rem 0 0.5rem;
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
        margin-bottom: 1.25rem;
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
            <span>Privacy Policy</span>
        </nav>

        <p style="color:var(--color-text-light); margin-bottom:2rem; line-height:1.8;">
            This Privacy Policy describes how <strong>Menha Boutique</strong> collects, uses, and discloses your
            personal information when you visit, make a purchase from, or interact with our website. By using our
            services, you agree to the terms of this policy.
        </p>

        <!-- Section 1 -->
        <div class="policy-section">
            <h2><i data-lucide="database" style="width:20px;color:var(--color-primary);"></i> 1. Information We Collect</h2>

            <h3>A. Information You Provide Directly</h3>
            <p>When you place an order, create an account, or contact us, you voluntarily provide:</p>
            <ul>
                <li><strong>Contact Information:</strong> Name, shipping address, billing address, email address,
                    and phone number.</li>
                <li><strong>Account Information:</strong> Username and password (stored securely and encrypted).</li>
                <li><strong>Payment Information:</strong> Payment method details. Note: This is typically processed
                    directly by a third-party payment processor and is usually not stored on our servers.</li>
                <li><strong>Preference Information (Optional):</strong> Size preferences, style preferences relevant
                    to the products you purchase.</li>
            </ul>

            <h3>B. Information Collected Automatically</h3>
            <p>When you access the site, we automatically collect:</p>
            <ul>
                <li><strong>Device Information:</strong> IP address, web browser type, time zone, and certain
                    cookies installed on your device.</li>
                <li><strong>Usage Data:</strong> Details about how you browse the site, web pages you view, search
                    terms that referred you, and time spent on pages.</li>
            </ul>
        </div>

        <!-- Section 2 -->
        <div class="policy-section">
            <h2><i data-lucide="settings" style="width:20px;color:var(--color-primary);"></i> 2. How We Use Your Personal Information</h2>
            <ul>
                <li><strong>Fulfilling Orders:</strong> To process your payment, ship your items, provide invoices
                    and order confirmations.</li>
                <li><strong>Account Management:</strong> To create, maintain, and manage your user account on our
                    platform.</li>
                <li><strong>Communication:</strong> To communicate about your order status, respond to inquiries,
                    and send administrative messages.</li>
                <li><strong>Marketing:</strong> To send promotional emails or newsletters you may be interested in,
                    consistent with your preferences.</li>
                <li><strong>Improvement of Services:</strong> To analyze website usage, measure campaign
                    effectiveness, and improve website functionality.</li>
            </ul>
        </div>

        <!-- Section 3 -->
        <div class="policy-section">
            <h2><i data-lucide="share-2" style="width:20px;color:var(--color-primary);"></i> 3. Sharing Your Personal Information</h2>
            <p>We share your personal information with third parties only to help us provide our services:</p>
            <ul>
                <li><strong>Shipping & Logistics Companies:</strong> To deliver your order.</li>
                <li><strong>Payment Processors:</strong> To securely process your payment details.</li>
                <li><strong>Analytics Providers:</strong> Such as Google Analytics, to understand how our customers
                    use the site.</li>
                <li><strong>Legal Compliance:</strong> We may share information to comply with applicable laws and
                    regulations.</li>
                <li><strong>Business Transfers:</strong> In the event of a merger or acquisition, your personal
                    information may be transferred as part of the transaction.</li>
            </ul>
        </div>

        <!-- Section 4 -->
        <div class="policy-section">
            <h2><i data-lucide="shield-check" style="width:20px;color:var(--color-primary);"></i> 4. Your Rights</h2>
            <ul>
                <li><strong>Right to Access:</strong> The right to request copies of personal data we hold about
                    you.</li>
                <li><strong>Right to Rectification:</strong> The right to request correction of any inaccurate
                    information.</li>
                <li><strong>Right to Erasure:</strong> The right to request erasure of your personal data under
                    certain conditions.</li>
                <li><strong>Right to Opt-Out of Marketing:</strong> You can unsubscribe from marketing emails by
                    clicking the "unsubscribe" link in any email.</li>
            </ul>
            <p>To exercise any of these rights, please contact us using the details below.</p>
        </div>

        <!-- Section 5 -->
        <div class="policy-section">
            <h2><i data-lucide="lock" style="width:20px;color:var(--color-primary);"></i> 5. Data Security and Retention</h2>
            <ul>
                <li>We use reasonable technical and organizational measures to protect your personal information.
                    However, no electronic transmission or storage is 100% secure.</li>
                <li>We will retain your personal information only for as long as necessary to fulfill the purposes
                    for which it was collected, including for legal, accounting, or reporting requirements.</li>
            </ul>
        </div>

        <!-- Section 6 -->
        <div class="policy-section">
            <h2><i data-lucide="refresh-cw" style="width:20px;color:var(--color-primary);"></i> 6. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time to reflect changes to our practices or for other
                operational, legal, or regulatory reasons. We encourage you to review this policy periodically.</p>
        </div>

        <!-- Contact Card -->
        <div class="policy-contact-card">
            <h2>7. Contact Us</h2>
            <p>For more information about our privacy practices, questions, or complaints, please contact us:</p>
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
