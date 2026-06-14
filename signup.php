<?php
/**
 * Signup Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$signupSuccess = false;
$token = '';
$userObj = null;

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

// Helper to normalize phone number
function normalizePhone($raw) {
    $p = str_replace(' ', '', $raw);
    if (strpos($p, '+91') === 0) {
        $p = substr($p, 3);
    } elseif (strpos($p, '91') === 0 && strlen($p) === 12) {
        $p = substr($p, 2);
    } elseif (strpos($p, '0') === 0) {
        $p = substr($p, 1);
    }
    return $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $rawPhone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $countryId = isset($_POST['country']) ? trim($_POST['country']) : '';
    $stateId = isset($_POST['state']) ? trim($_POST['state']) : '';
    $cityId = isset($_POST['city']) ? trim($_POST['city']) : '';
    $postcode = isset($_POST['postcode']) ? trim($_POST['postcode']) : '';
    $address1 = isset($_POST['address']) ? trim($_POST['address']) : '';
    $address2 = isset($_POST['address2']) ? trim($_POST['address2']) : '';
    
    $phone = normalizePhone($rawPhone);
    
    if (empty($firstName) || empty($email) || empty($phone) || empty($password) || empty($countryId) || empty($stateId) || empty($cityId) || empty($postcode) || empty($address1)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Please enter a valid 10-digit mobile number.';
    } else {
        try {
            // Check if email already exists
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                // Look up country, state, city names from their IDs
                $stmtC = $db->prepare("SELECT name FROM countries WHERE id = ?");
                $stmtC->execute([$countryId]);
                $countryName = $stmtC->fetchColumn() ?: '';
                
                $stmtS = $db->prepare("SELECT name FROM states WHERE id = ?");
                $stmtS->execute([$stateId]);
                $stateName = $stmtS->fetchColumn() ?: '';
                
                $stmtCi = $db->prepare("SELECT name FROM cities WHERE id = ?");
                $stmtCi->execute([$cityId]);
                $cityName = $stmtCi->fetchColumn() ?: '';
                
                // Create user
                $userId = generateUUID();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmtUser = $db->prepare("
                    INSERT INTO users (id, email, password_hash, first_name, last_name, phone_number, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'customer')
                ");
                $stmtUser->execute([$userId, $email, $passwordHash, $firstName, $lastName, $phone]);
                
                // Create address
                $addressId = generateUUID();
                $stmtAddr = $db->prepare("
                    INSERT INTO addresses (id, user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone_number, is_default) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmtAddr->execute([$addressId, $userId, $firstName, $lastName, $address1, $address2 ?: null, $cityName, $stateName, $postcode, $countryName, $phone]);
                
                // Auto login user
                $stmtGet = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmtGet->execute([$userId]);
                $user = $stmtGet->fetch(PDO::FETCH_ASSOC);
                
                $_SESSION['user'] = $user;
                $loginSuccess = true;
                $token = 'mock-jwt-token-' . $userId;
                $userObj = $user;
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menha Boutique - Sign Up</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.407.0/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=3.4.0">
    <style>
        body { background: #f0f4f3; min-height: 100vh; }

        .signup-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .signup-topbar {
            background: var(--color-white);
            border-bottom: 1px solid var(--color-border);
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            position: sticky;
            top: 0;
            z-index: 50;
            gap: 0.75rem;
        }

        .signup-topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .signup-topbar-brand img {
            height: 36px;
            width: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .signup-topbar-brand span {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--color-primary);
        }

        .signup-back-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--color-text-light);
            font-size: 0.88rem;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .signup-back-btn:hover {
            background: var(--color-light-gray);
            color: var(--color-primary);
        }

        .signup-body {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2.5rem 1rem 4rem;
        }

        .signup-card {
            background: var(--color-white);
            border-radius: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.10);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
        }

        .signup-card-hero {
            background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
            padding: 2.5rem 2.5rem 2rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .signup-hero-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .signup-card-hero h1 {
            font-size: 1.7rem;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: -0.3px;
        }

        .signup-card-hero p {
            margin: 0;
            opacity: 0.78;
            font-size: 0.92rem;
        }

        .signup-form-body {
            padding: 2.5rem;
        }

        .signup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .signup-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--color-primary);
            background: rgba(0,77,64,0.07);
            padding: 5px 12px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: #9ca3af;
            pointer-events: none;
            flex-shrink: 0;
        }

        .input-wrap input,
        .input-wrap select {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border-radius: 10px;
            border: 1.5px solid var(--color-border);
            font-family: inherit;
            font-size: 0.92rem;
            color: var(--color-text);
            background: #fafafa;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .input-wrap input:focus,
        .input-wrap select:focus {
            border-color: var(--color-primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,77,64,0.08);
        }

        .input-wrap input::placeholder { color: #c4cad3; }

        /* Password toggle */
        .input-wrap .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 2px;
            display: flex;
            align-items: center;
        }

        .input-wrap .pw-toggle:hover { color: var(--color-primary); }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .signup-divider {
            height: 1px;
            background: var(--color-border);
            margin: 0.5rem 0 2rem;
        }

        .signup-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .signup-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 0.86rem;
            font-weight: 500;
            width: 100%;
            text-align: center;
            margin-bottom: 1rem;
        }

        .signup-submit-btn {
            width: 100%;
            background: var(--color-primary);
            color: #fff;
            border: none;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(0,77,64,0.28);
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            font-family: inherit;
        }

        .signup-submit-btn:hover:not(:disabled) {
            background: var(--color-primary-dark);
            box-shadow: 0 6px 20px rgba(0,77,64,0.35);
        }

        .signup-submit-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .signup-login-link {
            font-size: 0.88rem;
            color: var(--color-text-light);
        }

        .signup-login-link a {
            color: var(--color-primary);
            font-weight: 700;
            text-decoration: none;
        }

        .signup-login-link a:hover { text-decoration: underline; }

        @media (max-width: 700px) {
            .signup-body { padding: 1rem 0.5rem 3rem; }
            .signup-card { border-radius: 18px; }
            .signup-card-hero { padding: 1.75rem 1.25rem 1.5rem; gap: 1rem; }
            .signup-card-hero h1 { font-size: 1.35rem; }
            .signup-hero-icon { width: 48px; height: 48px; border-radius: 12px; }
            .signup-form-body { padding: 1.5rem 1.25rem; }
            .signup-grid { grid-template-columns: 1fr; gap: 0; }
            .form-row { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 380px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="signup-page">

    <!-- Top bar -->
    <div class="page-topbar">
        <div class="container page-topbar-inner" style="justify-content:space-between;">
            <a href="login.php" class="page-topbar-back" aria-label="Go back">
                <i data-lucide="arrow-left"></i>
            </a>
            <a href="index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;position:absolute;left:50%;transform:translateX(-50%);">
                <img src="assets/images/logo.jpg" alt="Menha Boutique" style="height:34px;width:34px;border-radius:50%;object-fit:cover;">
                <span style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--color-primary-dark);">Menha Boutique</span>
            </a>
            <div style="width:40px;"></div>
        </div>
    </div>

    <!-- Main -->
    <div class="signup-body">
        <div class="signup-card">

            <!-- Hero strip -->
            <div class="signup-card-hero">
                <div class="signup-hero-icon">
                    <i data-lucide="user-plus" style="width:28px;height:28px;color:#fff;"></i>
                </div>
                <div>
                    <h1>Create your account</h1>
                    <p>Join Menha Boutique — it only takes a minute</p>
                </div>
            </div>

            <!-- Form -->
            <div class="signup-form-body">
                <form id="signup-form" method="POST" action="signup.php" autocomplete="on">
                    
                    <?php if (!empty($error)): ?>
                        <div class="signup-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="signup-grid">

                        <!-- Left: Account details -->
                        <div>
                            <div class="signup-section-title">Account Details</div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="signup-fname">First Name <span style="color:#ef4444;">*</span></label>
                                    <div class="input-wrap">
                                        <i data-lucide="user" class="input-icon"></i>
                                        <input type="text" id="signup-fname" name="first_name" placeholder="First name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required autocomplete="given-name">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="signup-lname">Last Name</label>
                                    <div class="input-wrap">
                                        <i data-lucide="user" class="input-icon"></i>
                                        <input type="text" id="signup-lname" name="last_name" placeholder="Last name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" autocomplete="family-name">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="signup-email">Email Address <span style="color:#ef4444;">*</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="mail" class="input-icon"></i>
                                    <input type="email" id="signup-email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required autocomplete="email">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="signup-phone">Mobile Number <span style="color:#ef4444;">*</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="smartphone" class="input-icon"></i>
                                    <input type="tel" id="signup-phone" name="phone" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($rawPhone ?? ''); ?>" required autocomplete="tel" maxlength="10" minlength="10" pattern="[6-9][0-9]{9}" inputmode="numeric">
                                </div>
                                <span id="signup-phone-msg" style="display:none;color:#ef4444;font-size:0.75rem;margin-top:4px;"></span>
                            </div>

                            <div class="form-group">
                                <label for="signup-password">Password <span style="color:#ef4444;">*</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="lock" class="input-icon"></i>
                                    <input type="password" id="signup-password" name="password" placeholder="Min. 6 characters" required minlength="6" autocomplete="new-password" style="padding-right:40px;">
                                    <button type="button" class="pw-toggle" id="pw-toggle-btn" aria-label="Show password">
                                        <i data-lucide="eye" style="width:16px;height:16px;" id="pw-toggle-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Address details -->
                        <div>
                            <div class="signup-section-title">Delivery Address</div>

                            <div class="form-group">
                                <label for="signup-country">Country <span style="color:#ef4444;">*</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="globe" class="input-icon"></i>
                                    <select id="signup-country" name="country" required autocomplete="country">
                                        <option value="">Select Country</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="signup-state">State <span style="color:#ef4444;">*</span></label>
                                    <div class="input-wrap">
                                        <i data-lucide="map" class="input-icon"></i>
                                        <select id="signup-state" name="state" required disabled autocomplete="address-level1">
                                            <option value="">Select State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="signup-city">City <span style="color:#ef4444;">*</span></label>
                                    <div class="input-wrap">
                                        <i data-lucide="map-pin" class="input-icon"></i>
                                        <select id="signup-city" name="city" required disabled autocomplete="address-level2">
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="signup-postcode">Postal / PIN Code <span style="color:#ef4444;">*</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="hash" class="input-icon"></i>
                                    <input type="text" id="signup-postcode" name="postcode" placeholder="e.g. 400001" value="<?php echo htmlspecialchars($postcode ?? ''); ?>" required autocomplete="postal-code">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="signup-address">Address Line 1 <span style="color:#ef4444;">*</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="home" class="input-icon"></i>
                                    <input type="text" id="signup-address" name="address" placeholder="House / Flat No., Street" value="<?php echo htmlspecialchars($address1 ?? ''); ?>" required autocomplete="address-line1">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="signup-address2">Address Line 2 <span style="color:#6b7280;font-weight:400;">(Optional)</span></label>
                                <div class="input-wrap">
                                    <i data-lucide="building" class="input-icon"></i>
                                    <input type="text" id="signup-address2" name="address2" placeholder="Landmark, Area, Colony" value="<?php echo htmlspecialchars($address2 ?? ''); ?>" autocomplete="address-line2">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="signup-divider"></div>

                    <div class="signup-actions">
                        <button type="submit" id="signup-submit" class="signup-submit-btn">
                            <i data-lucide="user-check" style="width:18px;height:18px;"></i>
                            Create Account
                        </button>

                        <p class="signup-login-link">
                            Already have an account? <a href="login.php">Sign in</a>
                        </p>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

    <script src="assets/js/api.js?v=2.0.0"></script>
    <script src="assets/js/app.js?v=2.0.0"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            lucide.createIcons();

            // Password show/hide
            const pwInput = document.getElementById('signup-password');
            const pwIcon = document.getElementById('pw-toggle-icon');
            document.getElementById('pw-toggle-btn').addEventListener('click', () => {
                const show = pwInput.type === 'password';
                pwInput.type = show ? 'text' : 'password';
                pwIcon.setAttribute('data-lucide', show ? 'eye-off' : 'eye');
                lucide.createIcons();
            });

            // Location dropdowns
            const countrySelect = document.getElementById('signup-country');
            const stateSelect = document.getElementById('signup-state');
            const citySelect = document.getElementById('signup-city');

            // Load countries
            const countries = await MainAPI.getCountries();
            countries.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                // If it was selected before submit, preserve it
                if (c.id === '<?php echo htmlspecialchars($countryId ?? ''); ?>') {
                    opt.selected = true;
                }
                countrySelect.appendChild(opt);
            });

            // If country was selected, load states
            if (countrySelect.value) {
                const states = await MainAPI.getStates(countrySelect.value);
                states.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    if (s.id === '<?php echo htmlspecialchars($stateId ?? ''); ?>') {
                        opt.selected = true;
                    }
                    stateSelect.appendChild(opt);
                });
                stateSelect.disabled = false;
            }

            // If state was selected, load cities
            if (stateSelect.value) {
                const cities = await MainAPI.getCities(stateSelect.value);
                cities.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    if (c.id === '<?php echo htmlspecialchars($cityId ?? ''); ?>') {
                        opt.selected = true;
                    }
                    citySelect.appendChild(opt);
                });
                citySelect.disabled = false;
            }

            countrySelect.addEventListener('change', async (e) => {
                stateSelect.innerHTML = '<option value="">Select State</option>';
                citySelect.innerHTML = '<option value="">Select City</option>';
                stateSelect.disabled = citySelect.disabled = true;
                if (e.target.value) {
                    const states = await MainAPI.getStates(e.target.value);
                    states.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id; opt.textContent = s.name;
                        stateSelect.appendChild(opt);
                    });
                    stateSelect.disabled = false;
                }
            });

            stateSelect.addEventListener('change', async (e) => {
                citySelect.innerHTML = '<option value="">Select City</option>';
                citySelect.disabled = true;
                if (e.target.value) {
                    const cities = await MainAPI.getCities(e.target.value);
                    cities.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id; opt.textContent = c.name;
                        citySelect.appendChild(opt);
                    });
                    citySelect.disabled = false;
                }
            });

            // Focus decoration
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('focus', () => input.style.borderColor = 'var(--color-primary)');
                input.addEventListener('blur', () => input.style.borderColor = 'var(--color-border)');
            });

            // Phone: strip non-digits, cap at 10
            const phoneInput = document.getElementById('signup-phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', () => {
                    let v = phoneInput.value.replace(/\D/g, '');
                    if (v.length > 10) v = v.slice(0, 10);
                    phoneInput.value = v;
                    const msg = document.getElementById('signup-phone-msg');
                    if (msg) {
                        if (v.length > 0 && (v.length < 10 || !/^[6-9]/.test(v))) {
                            msg.textContent = v.length < 10 ? 'Enter a 10-digit mobile number.' : 'Number must start with 6, 7, 8, or 9.';
                            msg.style.cssText = 'color:#ef4444;font-size:0.75rem;margin-top:4px;display:block;';
                            phoneInput.style.borderColor = '#ef4444';
                        } else {
                            msg.textContent = '';
                            msg.style.display = 'none';
                            phoneInput.style.borderColor = '';
                        }
                    }
                });
            }
        });
    </script>

    <?php if ($signupSuccess): ?>
        <script>
            // Set client local storage to match server side authentication
            const token = '<?php echo $token; ?>';
            const user = <?php echo json_encode($userObj); ?>;
            
            localStorage.setItem('menha_token', token);
            localStorage.setItem('login_user', JSON.stringify({
                token: token,
                user: user,
                storedAt: new Date().getTime()
            }));
            
            // Sync guest cart before redirecting
            (async () => {
                if (window.CartManager) {
                    await window.CartManager.mergeGuestCartOnLogin().catch(() => {});
                }
                window.location.href = 'index.php';
            })();
        </script>
    <?php endif; ?>

</body>
</html>
