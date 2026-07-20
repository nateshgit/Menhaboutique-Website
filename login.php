<?php
/**
 * Login Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// Safe pages users can be redirected back to after login
$_allowed_from = ['profile.php','orders.php','addresses.php','cart.php','checkout.php'];
$_from = '';
if (isset($_GET['from']) && in_array($_GET['from'], $_allowed_from)) {
    $_from = $_GET['from'];
} elseif (isset($_POST['from']) && in_array($_POST['from'], $_allowed_from)) {
    $_from = $_POST['from'];
}

if (isLoggedIn()) {
    header("Location: " . ($_from ?: 'index.php'));
    exit;
}

$error = '';
$loginSuccess = false;
$token = '';
$userObj = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $db = getDBConnection();
            
            // Clean identifier to search phone numbers robustly (remove all non-digits)
            $cleanIdentifier = preg_replace('/[^0-9]/', '', $identifier);
            $likeClean = '%' . $cleanIdentifier;
            
            // Query users comparing email, exact cleaned phone, or suffix matched phone (for 10-digit login inputs)
            $stmt = $db->prepare("
                SELECT * FROM users 
                WHERE email = ? 
                   OR REPLACE(REPLACE(REPLACE(phone_number, '+', ''), ' ', ''), '-', '') = ?
                   OR (LENGTH(?) >= 10 AND REPLACE(REPLACE(REPLACE(phone_number, '+', ''), ' ', ''), '-', '') LIKE ?)
                LIMIT 1
            ");
            $stmt->execute([$identifier, $cleanIdentifier, $cleanIdentifier, $likeClean]);
            $user = $stmt->fetch();
            
            if ($user && (password_verify($password, $user['password_hash']) || $user['password_hash'] === $password)) {
                // Success! Set session
                $_SESSION['user'] = $user;

                // Migrate plain-text password to bcrypt if needed
                if ($user['password_hash'] === $password) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                }

                // Merge any guest cart items into the user's DB cart
                if (!empty($_SESSION['guest_cart'])) {
                    require_once __DIR__ . '/includes/cart_merge.php';
                    mergeGuestCartToDb($db, $user['id'], $_SESSION['guest_cart']);
                    unset($_SESSION['guest_cart']);
                }

                header('Location: ' . ($_from ?: 'index.php'));
                exit;
            } else {
                $error = 'Invalid credentials. Please check your username and password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menha Boutique - Profile Login</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.407.0/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=3.4.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="background: var(--color-white);">

    <div class="page-topbar">
        <div class="container page-topbar-inner" style="justify-content:space-between;">
            <a href="index.php" class="page-topbar-back" aria-label="Go home">
                <i data-lucide="arrow-left"></i>
            </a>
            <a href="index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;position:absolute;left:50%;transform:translateX(-50%);">
                <img src="assets/images/logo.jpg" alt="Menha Boutique" style="height:34px;width:34px;border-radius:50%;object-fit:cover;">
                <span style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--color-primary-dark);">Menha Boutique</span>
            </a>
            <div style="width:40px;"></div>
        </div>
    </div>

    <main class="main-content container section-padding" style="max-width: 500px; padding-top: 2rem;">
        <div style="text-align: center; margin-bottom: 3rem;">
            <div style="width: 80px; height: 80px; background: var(--color-light-gray); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--color-primary);">
                <i data-lucide="user" style="width: 40px; height: 40px;"></i>
            </div>
            <h1 style="font-size: 2rem; color: var(--color-primary-dark); font-weight: 800; letter-spacing: -0.5px;">Welcome!</h1>
            <p style="color: var(--color-text-light);">Sign in to continue</p>
        </div>

        <form style="display: flex; flex-direction: column; gap: 1.5rem;" id="login-form" method="POST" action="login.php<?php echo $_from ? '?from=' . htmlspecialchars($_from) : ''; ?>">
            <?php if (!empty($error)): ?>
                <div style="color: #b91c1c; font-size: 0.95rem; text-align: center; background: #fee2e2; padding: 10px; border-radius: 8px; border: 1px solid #fca5a5;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div>
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem;">Email or Mobile Number</label>
                <input type="text" name="identifier" id="login-identifier" placeholder="Email or Mobile" required
                    style="width: 100%; padding: 14px 20px; border-radius: 12px; border: 2px solid var(--color-border); outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem;">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="login-password" placeholder="••••••••" required
                        style="width: 100%; padding: 14px 50px 14px 20px; border-radius: 12px; border: 2px solid var(--color-border); outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem; box-sizing: border-box;">
                    <button type="button" id="toggle-password" aria-label="Toggle password visibility"
                        style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--color-text-light); padding: 4px; display: flex; align-items: center; transition: color 0.2s;">
                        <i data-lucide="eye" id="eye-icon" style="width: 20px; height: 20px;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" id="login-submit"
                style="background: var(--color-primary); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 1rem; box-shadow: 0 4px 15px rgba(0,77,64,0.3); transition: var(--transition-normal);">
                Login
            </button>

            <div style="text-align: center; margin-top: -0.5rem;">
                <a href="forgot-password.php" style="color: var(--color-text-light); font-size: 0.9rem; text-decoration: none;">Forgot Password?</a>
            </div>

            <div style="text-align: center; margin-top: 1rem;">
                <span style="color: var(--color-text-light);">Don't have an account?</span>
                <a href="signup.php" style="color: var(--color-primary); font-weight: 600;">Sign up</a>
            </div>
        </form>
    </main>

    <script src="assets/js/api.js?v=2.0.0"></script>
    <script src="assets/js/app.js?v=2.0.0"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', () => input.style.borderColor = 'var(--color-primary)');
                input.addEventListener('blur', () => input.style.borderColor = 'var(--color-border)');
            });

            const toggleBtn = document.getElementById('toggle-password');
            const pwdInput = document.getElementById('login-password');
            const eyeIconEl = document.getElementById('eye-icon');
            toggleBtn.addEventListener('click', () => {
                const isPassword = pwdInput.type === 'password';
                pwdInput.type = isPassword ? 'text' : 'password';
                eyeIconEl.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                lucide.createIcons();
            });
        });
    </script>


</body>
</html>
