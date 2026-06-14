<?php
/**
 * User Addresses Page - Menha Boutique PHP
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Menha Boutique - My Addresses";
require_once __DIR__ . '/includes/header.php';
$pageTopbarTitle    = 'My Addresses';
$pageTopbarSubtitle = 'Manage your delivery addresses';
$pageTopbarBack     = 'profile.php';
require_once __DIR__ . '/includes/page_topbar.php';
?>

<style>
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(4px);
    }

    .modal-box {
        background: #E8F0EE;
        width: 90%;
        max-width: 600px;
        border-radius: 20px;
        padding: 2rem;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        border: 1px solid rgba(0, 77, 64, 0.12);
        box-shadow: var(--shadow-lg);
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-primary-dark);
        margin-bottom: 1.5rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 600px) {
        .form-grid { grid-template-columns: 1fr; }
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--color-text);
    }

    .form-group input, .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--color-border);
        border-radius: 10px;
        font-family: inherit;
        outline: none;
        background: white;
    }

    .btn-save {
        background: var(--color-primary);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 10px;
        width: 100%;
        font-weight: 700;
        cursor: pointer;
        margin-top: 1rem;
    }

    .address-actions {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        display: flex;
        gap: 0.5rem;
    }

    .action-icon-btn {
        background: #f4f4f4;
        border: none;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #666;
        transition: 0.2s;
    }

    .action-icon-btn:hover {
        background: var(--color-primary);
        color: white;
    }

    .action-icon-btn.delete:hover {
        background: #ff4d4d;
    }
</style>

<main class="main-content container section-padding" style="min-height: 70vh; max-width: 800px; padding-top: 2rem; padding-bottom: 4rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="color: var(--color-primary-dark);">Saved Addresses</h2>
        <button onclick="openAddressModal()" 
            style="background: var(--color-primary); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="plus"></i> Add New
        </button>
    </div>

    <div id="addresses-workspace">
        <div style="text-align: center; padding: 4rem 1rem;">
            <p style="color: var(--color-text-light);">Loading addresses...</p>
        </div>
    </div>
</main>

<!-- Address Modal -->
<div id="address-modal" class="modal-overlay">
    <div class="modal-box">
        <h3 class="modal-title" id="modal-heading">Add New Address</h3>
        <form id="address-form">
            <input type="hidden" id="addr-id">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="addr-first-name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="addr-last-name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Address Line 1</label>
                <input type="text" id="addr-line1" required>
            </div>
            <div class="form-group">
                <label>Address Line 2 (Optional)</label>
                <input type="text" id="addr-line2">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Postcode / ZIP</label>
                    <input type="text" id="addr-zip" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" id="addr-phone" required maxlength="10" minlength="10" pattern="[6-9][0-9]{9}" inputmode="numeric" placeholder="10-digit mobile number">
                    <span id="addr-phone-msg" style="display:none;color:#ef4444;font-size:0.75rem;margin-top:4px;"></span>
                </div>
            </div>
            <div class="form-group">
                <label>Alternate Phone <span style="font-weight:400;color:var(--color-text-light);">(optional)</span></label>
                <input type="tel" id="addr-alt-phone" placeholder="Alternate contact number">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Country</label>
                    <select id="addr-country" required></select>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <select id="addr-state" required><option value="">Select State</option></select>
                </div>
            </div>
            <div class="form-group">
                <label>City</label>
                <select id="addr-city" required>
                    <option value="">Select City</option>
                </select>
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="addr-default" style="width: auto;">
                <label for="addr-default" style="margin: 0;">Set as default address</label>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="button" onclick="closeAddressModal()" style="flex: 1; background: #eee; border: none; padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 1rem;">Cancel</button>
                <button type="submit" class="btn-save">Save Address</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/api.js?v=2.0.0"></script>
<script src="assets/js/app.js?v=2.0.0"></script>
<script>
    let allAddresses = [];

    document.addEventListener('DOMContentLoaded', async () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        if (!MainAPI.isAuthenticated()) {
            window.location.href = 'login.php';
            return;
        }

        // Phone: strip non-digits, cap at 10, show inline error
        const addrPhoneInput = document.getElementById('addr-phone');
        if (addrPhoneInput) {
            addrPhoneInput.addEventListener('input', () => {
                let v = addrPhoneInput.value.replace(/\D/g, '');
                if (v.length > 10) v = v.slice(0, 10);
                addrPhoneInput.value = v;
                const msg = document.getElementById('addr-phone-msg');
                if (msg) {
                    if (v.length > 0 && (v.length < 10 || !/^[6-9]/.test(v))) {
                        msg.textContent = v.length < 10 ? 'Enter a 10-digit mobile number.' : 'Number must start with 6, 7, 8, or 9.';
                        msg.style.cssText = 'color:#ef4444;font-size:0.75rem;margin-top:4px;display:block;';
                        addrPhoneInput.style.borderColor = '#ef4444';
                    } else {
                        msg.textContent = '';
                        msg.style.display = 'none';
                        addrPhoneInput.style.borderColor = '';
                    }
                }
            });
        }

        await loadAddresses();
        initLocationLogic();
    });

    async function loadAddresses() {
        const workspace = document.getElementById('addresses-workspace');
        try {
            allAddresses = await MainAPI.getUserAddresses();

            if (allAddresses.length === 0) {
                workspace.innerHTML = `
                    <div style="text-align:center; padding: 4rem 1rem;">
                        <i data-lucide="map" style="width: 60px; height: 60px; color: var(--color-border); margin-bottom: 1rem;"></i>
                        <h2 style="color: var(--color-primary-dark); margin-bottom: 0.5rem;">No Addresses Found</h2>
                        <p style="color: var(--color-text-light);">You haven't saved any addresses yet.</p>
                    </div>
                `;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }

            let html = '';
            allAddresses.forEach(addr => {
                html += `
                    <div style="background: #E8F0EE; border-radius: 16px; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid rgba(0, 77, 64, 0.12); position: relative;">
                        ${addr.is_default ? '<span style="position: absolute; top: 1.5rem; left: 1.5rem; background: var(--color-primary); color: white; padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; z-index: 1;">Default</span>' : ''}
                        
                        <div class="address-actions">
                            <button class="action-icon-btn" onclick="openAddressModal('${addr.id}')" title="Edit"><i data-lucide="pencil"></i></button>
                            <button class="action-icon-btn delete" onclick="deleteAddress('${addr.id}')" title="Delete"><i data-lucide="trash-2"></i></button>
                        </div>

                        <div style="${addr.is_default ? 'margin-top: 2rem;' : ''}">
                            <h3 style="margin-bottom: 0.5rem; color: var(--color-primary-dark);">${addr.first_name} ${addr.last_name || ''}</h3>
                            <p style="color: var(--color-text-light); line-height: 1.6;">
                                ${addr.address_line1}<br>
                                ${addr.address_line2 ? addr.address_line2 + '<br>' : ''}
                                ${addr.city}, ${addr.state} - ${addr.zip_code || addr.postal_code}<br>
                                ${addr.country}
                            </p>
                            <p style="margin-top: 1rem; font-weight: 600; font-size: 0.9rem; color: var(--color-primary-dark);">
                                <i data-lucide="phone" style="width: 14px; vertical-align: middle; margin-right: 5px;"></i> ${addr.phone_number}
                            </p>
                        </div>
                    </div>
                `;
            });

            workspace.innerHTML = html;
            if (typeof lucide !== 'undefined') lucide.createIcons();

        } catch (err) {
            console.error(err);
            workspace.innerHTML = `<div style="text-align:center; padding: 2rem; color: red;">Failed to load addresses.</div>`;
        }
    }

    async function openAddressModal(id = null) {
        const modal = document.getElementById('address-modal');
        const form = document.getElementById('address-form');
        const heading = document.getElementById('modal-heading');
        
        form.reset();
        document.getElementById('addr-id').value = id || '';
        heading.textContent = id ? 'Edit Address' : 'Add New Address';

        if (id) {
            const addr = allAddresses.find(a => a.id == id);
            if (addr) {
                document.getElementById('addr-first-name').value = addr.first_name || '';
                document.getElementById('addr-last-name').value = addr.last_name || '';
                document.getElementById('addr-line1').value = addr.address_line1 || '';
                document.getElementById('addr-line2').value = addr.address_line2 || '';
                document.getElementById('addr-zip').value = addr.zip_code || addr.postal_code || '';
                document.getElementById('addr-phone').value = addr.phone_number || '';
                document.getElementById('addr-alt-phone').value = addr.alternate_phone || '';
                document.getElementById('addr-default').checked = !!addr.is_default;

                const countrySelect = document.getElementById('addr-country');
                for (let i = 0; i < countrySelect.options.length; i++) {
                    if (countrySelect.options[i].text === addr.country) {
                        countrySelect.selectedIndex = i;
                        break;
                    }
                }
                await loadStates(countrySelect.value, addr.state, addr.city);
            }
        }

        modal.style.display = 'flex';
    }

    function closeAddressModal() {
        document.getElementById('address-modal').style.display = 'none';
    }

    async function initLocationLogic() {
        const countrySelect = document.getElementById('addr-country');
        const stateSelect = document.getElementById('addr-state');
        
        const countries = await MainAPI.getCountries();
        countrySelect.innerHTML = '<option value="">Select Country</option>' + 
            countries.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        countrySelect.onchange = (e) => loadStates(e.target.value);
        
        stateSelect.onchange = (e) => {
            const selectedOpt = stateSelect.options[stateSelect.selectedIndex];
            const stateId = selectedOpt.dataset.id;
            loadCities(stateId);
        };
    }

    async function loadStates(countryId, selectedStateName = '', selectedCityName = '') {
        const stateSelect = document.getElementById('addr-state');
        const citySelect = document.getElementById('addr-city');
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        if (!countryId) return;

        const states = await MainAPI.getStates(countryId);
        states.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.name;
            opt.textContent = s.name;
            opt.dataset.id = s.id;
            if (s.name === selectedStateName) opt.selected = true;
            stateSelect.appendChild(opt);
        });

        if (selectedStateName) {
            const state = states.find(s => s.name === selectedStateName);
            if (state) {
                await loadCities(state.id, selectedCityName);
            }
        }
    }

    async function loadCities(stateId, selectedCityName = '') {
        const citySelect = document.getElementById('addr-city');
        citySelect.innerHTML = '<option value="">Select City</option>';
        if (!stateId) return;

        const cities = await MainAPI.getCities(stateId);
        cities.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.textContent = c.name;
            if (c.name === selectedCityName) opt.selected = true;
            citySelect.appendChild(opt);
        });
    }

    document.getElementById('address-form').onsubmit = async (e) => {
        e.preventDefault();
        const countrySelect = document.getElementById('addr-country');
        const saveBtn = document.querySelector('#address-form .btn-save');

        const payload = {
            id: document.getElementById('addr-id').value || null,
            first_name: document.getElementById('addr-first-name').value.trim(),
            last_name: document.getElementById('addr-last-name').value.trim(),
            address_line1: document.getElementById('addr-line1').value.trim(),
            address_line2: document.getElementById('addr-line2').value.trim() || null,
            zip_code: document.getElementById('addr-zip').value.trim(),
            phone_number: document.getElementById('addr-phone').value.trim(),
            alternate_phone: document.getElementById('addr-alt-phone').value.trim() || null,
            country: countrySelect.options[countrySelect.selectedIndex]
                ? countrySelect.options[countrySelect.selectedIndex].text
                : 'India',
            state: document.getElementById('addr-state').value,
            city: document.getElementById('addr-city').value,
            is_default: document.getElementById('addr-default').checked
        };

        if (!payload.first_name || !payload.address_line1 || !payload.zip_code ||
            !payload.phone_number || !payload.state || !payload.city) {
            showToast('Please fill all required fields', 'error');
            return;
        }

        if (!/^[6-9][0-9]{9}$/.test(payload.phone_number)) {
            showToast('Enter a valid 10-digit mobile number (starting with 6–9)', 'error');
            const ph = document.getElementById('addr-phone');
            if (ph) ph.focus();
            return;
        }

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
        }

        try {
            await MainAPI.upsertUserAddress(payload);
            closeAddressModal();
            await loadAddresses();
            showToast('Address saved successfully!', 'success');
        } catch (err) {
            showToast('Error saving address: ' + (err.message || 'Please try again'), 'error');
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Address';
            }
        }
    };

    async function deleteAddress(id) {
        if (!confirm('Are you sure you want to delete this address?')) return;
        try {
            await MainAPI.deleteAddress(id);
            await loadAddresses();
            showToast('Address deleted', 'info');
        } catch (err) {
            showToast('Error deleting address: ' + (err.message || ''), 'error');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
