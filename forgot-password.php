<?php
/**
 * Forgot Password Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to profile
if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$pageTitle = "Menha Boutique - Forgot Password";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'Reset Password';
$pageTopbarSubtitle = 'We\'ll send you a reset link';
$pageTopbarBack     = 'login.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<main class="main-content container section-padding" style="max-width: 500px; padding-top: 0; padding-bottom: 5rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="width: 80px; height: 80px; background: var(--color-light-gray); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--color-primary);">
            <i data-lucide="key-round" style="width: 40px; height: 40px;"></i>
        </div>
        <h1 style="font-size: 2rem; color: var(--color-primary-dark); font-weight: 800; letter-spacing: -0.5px;">Reset Password</h1>
        <p style="color: var(--color-text-light);">We'll help you get back in</p>
    </div>

    <!-- Step 1: Enter Email -->
    <div id="step-1" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div id="step1-error" style="color: red; font-size: 0.9rem; display: none; text-align: center;"></div>
        <div id="step1-success" style="color: var(--color-primary); font-size: 0.9rem; display: none; text-align: center; background: #f0fdf4; padding: 12px; border-radius: 10px;"></div>
        <div>
            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem;">Email Address</label>
            <input type="email" id="fp-email" placeholder="Enter your email" required
                style="width: 100%; padding: 14px 20px; border-radius: 12px; border: 2px solid var(--color-border); outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem;">
        </div>
        <button id="step1-btn" onclick="submitEmail()"
            style="background: var(--color-primary); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,77,64,0.3); transition: var(--transition-normal);">
            Send OTP
        </button>
        <div style="text-align: center;">
            <a href="login.php" style="color: var(--color-text-light); font-size: 0.9rem; text-decoration: none;">Back to Login</a>
        </div>
    </div>

    <!-- Step 2: Enter OTP -->
    <div id="step-2" style="display: none; flex-direction: column; gap: 1.5rem;">
        <div id="step2-error" style="color: red; font-size: 0.9rem; display: none; text-align: center;"></div>
        <div style="background: #f0fdf4; border-radius: 10px; padding: 12px; text-align: center; font-size: 0.88rem; color: var(--color-primary);">
            If this email exists, an OTP has been sent. Check your email.
        </div>
        <div>
            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem;">Enter OTP</label>
            <input type="text" id="fp-otp" placeholder="Enter 6-character OTP" maxlength="10" required
                style="width: 100%; padding: 14px 20px; border-radius: 12px; border: 2px solid var(--color-border); outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem; letter-spacing: 4px; text-transform: uppercase; text-align: center;">
        </div>
        <button id="step2-btn" onclick="submitOTP()"
            style="background: var(--color-primary); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,77,64,0.3); transition: var(--transition-normal);">
            Verify OTP
        </button>
        <div style="text-align: center;">
            <a href="javascript:void(0)" onclick="goToStep(1)" style="color: var(--color-text-light); font-size: 0.9rem; text-decoration: none;">Try a different email</a>
        </div>
    </div>

    <!-- Step 3: New Password -->
    <div id="step-3" style="display: none; flex-direction: column; gap: 1.5rem;">
        <div id="step3-error" style="color: red; font-size: 0.9rem; display: none; text-align: center;"></div>
        <form id="step3-form" onsubmit="event.preventDefault(); submitNewPassword();" autocomplete="on" style="display: contents;">
            <input type="hidden" id="fp-email-hidden" autocomplete="username">
            <div>
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem;">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="fp-newpass" placeholder="••••••••" required minlength="6" autocomplete="new-password"
                        style="width: 100%; padding: 14px 20px; border-radius: 12px; border: 2px solid var(--color-border); outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem;">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('fp-newpass', this)" aria-label="Toggle password visibility">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem;">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="fp-confirmpass" placeholder="••••••••" required minlength="6" autocomplete="new-password"
                        style="width: 100%; padding: 14px 20px; border-radius: 12px; border: 2px solid var(--color-border); outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem;">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('fp-confirmpass', this)" aria-label="Toggle password visibility">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" id="step3-btn"
                style="background: var(--color-primary); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,77,64,0.3); transition: var(--transition-normal);">
                Update Password
            </button>
        </form>
    </div>
</main>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    let currentEmail = '';
    let currentOTP = '';

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => input.style.borderColor = 'var(--color-primary)');
            input.addEventListener('blur', () => input.style.borderColor = 'var(--color-border)');
        });
    });

    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.innerHTML = isHidden
            ? '<i data-lucide="eye-off"></i>'
            : '<i data-lucide="eye"></i>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function goToStep(step) {
        document.getElementById('step-1').style.display = 'none';
        document.getElementById('step-2').style.display = 'none';
        document.getElementById('step-3').style.display = 'none';
        document.getElementById('step-' + step).style.display = 'flex';
    }

    async function submitEmail() {
        const emailInput = document.getElementById('fp-email');
        const email = emailInput.value.trim();
        const errorDiv = document.getElementById('step1-error');
        const successDiv = document.getElementById('step1-success');
        const btn = document.getElementById('step1-btn');

        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errorDiv.innerText = 'Please enter a valid email address.';
            errorDiv.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.innerText = 'Sending...';

        try {
            await MainAPI.requestPasswordReset(email);
            currentEmail = email;
            goToStep(2);
        } catch (e) {
            errorDiv.innerText = 'Something went wrong. Please try again.';
            errorDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.innerText = 'Send OTP';
        }
    }

    async function submitOTP() {
        const otp = document.getElementById('fp-otp').value.trim();
        const errorDiv = document.getElementById('step2-error');
        const btn = document.getElementById('step2-btn');

        errorDiv.style.display = 'none';

        if (!otp || otp.length < 6) {
            errorDiv.innerText = 'Please enter the OTP sent to your email.';
            errorDiv.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.innerText = 'Verifying...';

        try {
            const valid = await MainAPI.verifyPasswordOTP(currentEmail, otp);
            if (valid) {
                currentOTP = otp.toUpperCase();
                document.getElementById('fp-email-hidden').value = currentEmail;
                goToStep(3);
            } else {
                errorDiv.innerText = 'Invalid or expired OTP. Please try again.';
                errorDiv.style.display = 'block';
            }
        } catch (e) {
            errorDiv.innerText = 'Verification failed. Please try again.';
            errorDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.innerText = 'Verify OTP';
        }
    }

    async function submitNewPassword() {
        const newPass = document.getElementById('fp-newpass').value;
        const confirmPass = document.getElementById('fp-confirmpass').value;
        const errorDiv = document.getElementById('step3-error');
        const btn = document.getElementById('step3-btn');

        errorDiv.style.display = 'none';

        if (!newPass || newPass.length < 6) {
            errorDiv.innerText = 'Password must be at least 6 characters.';
            errorDiv.style.display = 'block';
            return;
        }
        if (newPass !== confirmPass) {
            errorDiv.innerText = 'Passwords do not match.';
            errorDiv.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.innerText = 'Updating...';

        try {
            await MainAPI.updatePassword(currentEmail, currentOTP, newPass);
            showToast('Password updated successfully! Please login.', 'success', 4000);
            setTimeout(() => { window.location.href = 'login.php'; }, 1500);
        } catch (e) {
            errorDiv.innerText = e.message || 'Failed to update password. Please try again.';
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.innerText = 'Update Password';
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
