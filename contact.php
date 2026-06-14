<?php
/**
 * Contact Us Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = "Menha Boutique - Contact Us";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'Contact Us';
$pageTopbarSubtitle = 'We\'d love to hear from you';
$pageTopbarBack     = 'index.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<style>
    .contact-hero {
        background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
        padding: 3.5rem 0;
        text-align: center;
        color: #fff;
        margin-bottom: 3rem;
    }
    .contact-hero h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; }
    .contact-hero p { font-size: 1.05rem; opacity: 0.85; }

    .contact-layout {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 2.5rem;
        align-items: flex-start;
        margin-bottom: 4rem;
    }
    @media (max-width: 768px) { .contact-layout { grid-template-columns: 1fr; } }

    .contact-info-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid var(--color-border);
        padding: 2rem;
    }
    .contact-info-card h2 {
        font-size: 1.3rem; font-weight: 800;
        color: var(--color-primary-dark); margin-bottom: 1.5rem;
    }
    .info-item {
        display: flex; gap: 14px; align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    .info-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: rgba(0,77,64,0.09);
        color: var(--color-primary);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .info-icon svg { width: 20px; height: 20px; }
    .info-label { font-size: 0.78rem; font-weight: 600; color: var(--color-text-light); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
    .info-value { font-size: 0.95rem; font-weight: 600; color: var(--color-primary-dark); }

    .contact-form-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid var(--color-border);
        padding: 2rem;
    }
    .contact-form-card h2 {
        font-size: 1.3rem; font-weight: 800;
        color: var(--color-primary-dark); margin-bottom: 1.5rem;
    }
    .form-field { margin-bottom: 1.25rem; }
    .form-field label {
        display: block; font-size: 0.82rem; font-weight: 600;
        color: var(--color-text-light); margin-bottom: 6px;
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .form-field input,
    .form-field select,
    .form-field textarea {
        width: 100%; padding: 12px 16px;
        border: 2px solid var(--color-border);
        border-radius: 10px;
        font-family: inherit; font-size: 0.95rem;
        background: #fff;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(0,77,64,0.08);
    }
    .form-field textarea { min-height: 120px; resize: vertical; }
    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 480px) { .form-row-2 { grid-template-columns: 1fr; } }
    .submit-btn {
        width: 100%; background: var(--color-primary); color: #fff;
        border: none; padding: 14px; border-radius: 12px;
        font-size: 1rem; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 4px 15px rgba(0,77,64,0.25);
        transition: 0.2s; font-family: inherit;
    }
    .submit-btn:hover { background: var(--color-primary-dark); }
    .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    #contact-success {
        background: #f0fdf4; border: 1px solid #bbf7d0;
        color: #15803d; border-radius: 12px; padding: 1rem 1.25rem;
        text-align: center; font-weight: 600; display: none; margin-bottom: 1rem;
    }
</style>

<main class="main-content container" style="padding-top:0; padding-bottom: 4rem;">
    <div class="contact-layout">
        <!-- Left: Contact Info -->
        <div class="contact-info-card">
            <h2>Get in Touch</h2>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="map-pin"></i></div>
                <div>
                    <div class="info-label">Address</div>
                    <div class="info-value">No 9. East Street<br>Madhampatti, Coimbatore 641010</div>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="phone"></i></div>
                <div>
                    <div class="info-label">Phone</div>
                    <div class="info-value">Call: 9500600525<br>WhatsApp: 8973355559<br>Customer Care: 7708853119</div>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="mail"></i></div>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-value">menhaboutique@gmail.com</div>
                </div>
            </div>

            <div class="info-item">
                <div class="info-icon"><i data-lucide="clock"></i></div>
                <div>
                    <div class="info-label">Business Hours</div>
                    <div class="info-value">Mon – Sat: 9 AM – 7 PM<br><span style="font-size:0.85rem;color:var(--color-text-light);">Sunday: Closed</span></div>
                </div>
            </div>

            <div style="margin-top:1.5rem; border-top:1px solid var(--color-border); padding-top:1.5rem;">
                <div class="info-label" style="margin-bottom:0.75rem;">Follow Us</div>
                <div class="social-links">
                    <a href="https://www.facebook.com/share/1Cc8K6GPUV/" target="_blank" rel="noopener noreferrer" class="social-link" style="background:rgba(0,77,64,0.12);color:var(--color-primary);" title="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="https://www.instagram.com/menhaboutique" target="_blank" rel="noopener noreferrer" class="social-link" style="background:rgba(0,77,64,0.12);color:var(--color-primary);" title="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    <a href="https://www.youtube.com/menhaboutique" target="_blank" rel="noopener noreferrer" class="social-link" style="background:rgba(0,77,64,0.12);color:var(--color-primary);" title="YouTube"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.1C5.12 19.54 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg></a>
                </div>
            </div>
        </div>

        <!-- Right: Contact Form -->
        <div class="contact-form-card">
            <h2>Send a Message</h2>
            <div id="contact-success">
                <i data-lucide="check-circle" style="display:inline;vertical-align:middle;margin-right:6px;"></i>
                Thank you! Your message has been sent. We'll get back to you shortly.
            </div>
            <form id="contact-form" onsubmit="submitContactForm(event)">
                <div class="form-row-2">
                    <div class="form-field">
                        <label>First Name *</label>
                        <input type="text" id="cf-fname" placeholder="John" required>
                    </div>
                    <div class="form-field">
                        <label>Last Name</label>
                        <input type="text" id="cf-lname" placeholder="Doe">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-field">
                        <label>Email *</label>
                        <input type="email" id="cf-email" placeholder="john@example.com" required>
                    </div>
                    <div class="form-field">
                        <label>Phone Number</label>
                        <input type="tel" id="cf-phone" placeholder="9876543210">
                    </div>
                </div>
                <div class="form-field">
                    <label>Subject *</label>
                    <select id="cf-subject" required>
                        <option value="">Select a subject</option>
                        <option>Order Inquiry</option>
                        <option>Product Question</option>
                        <option>Shipping & Delivery</option>
                        <option>Return / Refund</option>
                        <option>Feedback</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Message *</label>
                    <textarea id="cf-message" placeholder="Write your message here..." required></textarea>
                </div>
                <div id="cf-error" style="color:red;font-size:0.88rem;margin-bottom:0.75rem;display:none;"></div>
                <button type="submit" class="submit-btn" id="cf-submit">
                    <i data-lucide="send" style="width:18px;height:18px;"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    async function submitContactForm(e) {
        e.preventDefault();
        const btn = document.getElementById('cf-submit');
        const errDiv = document.getElementById('cf-error');
        const successDiv = document.getElementById('contact-success');
        errDiv.style.display = 'none';

        const phone = document.getElementById('cf-phone').value.trim();
        if (phone) {
            let p = phone.replace(/\s/g, '');
            if (p.startsWith('+91')) p = p.slice(3);
            else if (p.startsWith('91') && p.length === 12) p = p.slice(2);
            if (p && !/^\d{10}$/.test(p)) {
                errDiv.innerText = 'Please enter a valid 10-digit phone number.';
                errDiv.style.display = 'block';
                return;
            }
        }

        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader" style="width:18px;height:18px;"></i> Sending...';
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const response = await fetch('api/contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    first_name: document.getElementById('cf-fname').value.trim(),
                    last_name: document.getElementById('cf-lname').value.trim(),
                    email: document.getElementById('cf-email').value.trim(),
                    phone: document.getElementById('cf-phone').value.trim(),
                    subject: document.getElementById('cf-subject').value,
                    message: document.getElementById('cf-message').value.trim()
                })
            });

            const res = await response.json();
            if (!response.ok) {
                throw new Error(res.error || 'Failed to submit form');
            }

            document.getElementById('contact-form').style.display = 'none';
            successDiv.style.display = 'block';
        } catch(err) {
            errDiv.innerText = err.message || 'An error occurred. Please try again.';
            errDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" style="width:18px;height:18px;"></i> Send Message';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
