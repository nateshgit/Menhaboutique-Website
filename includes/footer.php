<?php
/**
 * Footer template - Menha Boutique PHP
 */
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-container">

                <!-- Brand -->
                <div class="footer-block footer-brand">
                    <img src="assets/images/logo.jpg" alt="Menha Boutique" class="logo-img">
                    <span class="brand-text">Menha Boutique</span>
                    <p>Experience the best in self-care with our premium range of personal care and wellness solutions. Committed to quality, crafted with love.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/1Cc8K6GPUV/" target="_blank" rel="noopener noreferrer" class="social-link" title="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/menhaboutique" target="_blank" rel="noopener noreferrer" class="social-link" title="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        </a>
                        <a href="https://www.youtube.com/menhaboutique" target="_blank" rel="noopener noreferrer" class="social-link" title="YouTube">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.1C5.12 19.54 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-block">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="cart.php">Shopping Cart</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div class="footer-block">
                    <h3 class="footer-title">Customer Service</h3>
                    <ul class="footer-links">
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms &amp; Conditions</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-block">
                    <h3 class="footer-title">Get in Touch</h3>
                    <ul class="footer-links">
                        <li style="display:flex;align-items:flex-start;gap:9px;color:rgba(255,255,255,0.55);margin-bottom:0.8rem;">
                            <i data-lucide="map-pin" style="width:15px;height:15px;flex-shrink:0;margin-top:3px;color:var(--color-accent);pointer-events:none;"></i>
                            <span style="font-size:0.86rem;line-height:1.6;">No 9. East Street, Madhampatti,<br>Coimbatore 641010</span>
                        </li>
                        <li style="display:flex;align-items:flex-start;gap:9px;color:rgba(255,255,255,0.55);margin-bottom:0.8rem;">
                            <i data-lucide="phone" style="width:15px;height:15px;flex-shrink:0;margin-top:3px;color:var(--color-accent);pointer-events:none;"></i>
                            <span style="font-size:0.86rem;line-height:1.8;">9500600525<br>WhatsApp: 8973355559<br>Care: 7708853119</span>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom">
                &copy; <span id="current-year"></span> Menha Boutique. All rights reserved. Crafted with <span style="color:var(--color-accent);">♥</span>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('current-year').textContent = new Date().getFullYear();
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        function handleLogout(e) {
            e.preventDefault();
            localStorage.removeItem('menha_session');
            localStorage.removeItem('menha_token');
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
