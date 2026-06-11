// Micro-interactions for inputs (Mobile & Desktop)
document.querySelectorAll('input:not([type="file"]):not([type="hidden"]):not([type="checkbox"])').forEach(input => {
    input.addEventListener('focus', () => {
        if (input.parentElement && input.parentElement.classList) {
            input.parentElement.classList.add('scale-[1.01]', 'leaf-indicator');
            input.parentElement.style.transition = 'transform 0.2s ease';
        }
    });
    input.addEventListener('blur', () => {
        if (input.parentElement && input.parentElement.classList) {
            input.parentElement.classList.remove('scale-[1.01]', 'leaf-indicator');
        }
    });
});

const primaryBtns = document.querySelectorAll('button[type="submit"], button.bg-primary-container, button.bg-primary');
primaryBtns.forEach(btn => {
    btn.addEventListener('mousedown', () => btn.classList.add('scale-95'));
    btn.addEventListener('mouseup', () => btn.classList.remove('scale-95'));
    btn.addEventListener('mouseleave', () => btn.classList.remove('scale-95'));
    // Touch events for mobile
    btn.addEventListener('touchstart', () => btn.classList.add('scale-95'), { passive: true });
    btn.addEventListener('touchend', () => btn.classList.remove('scale-95'), { passive: true });
});

// ----- Address Logic -----
let locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };

async function fetchProvinces() {
    renderLocLoading();
    try {
        const res = await fetch('https://provinces.open-api.vn/api/p/');
        const data = await res.json();
        renderLocItems(data, 'province');
    } catch (e) { console.error(e); }
}

async function fetchDistricts(provinceCode) {
    renderLocLoading();
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.districts, 'district');
    } catch (e) { console.error(e); }
}

async function fetchWards(districtCode) {
    renderLocLoading();
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.wards, 'ward');
    } catch (e) { console.error(e); }
}

function renderLocLoading() {
    document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Đang tải...</div>';
}

function renderLocItems(items, type) {
    const list = document.getElementById('locList');
    list.innerHTML = '';
    if (!items || items.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Không có dữ liệu</div>';
        return;
    }
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'loc-item';
        div.textContent = item.name;
        div.onclick = () => selectLocItem(item, type);
        list.appendChild(div);
    });
}

function selectLocItem(item, type) {
    if (type === 'province') {
        locState.province = item.code; locState.province_name = item.name;
        locState.district = null; locState.district_name = '';
        locState.ward = null; locState.ward_name = '';
        document.getElementById('addr_province').value = item.name;
        document.getElementById('addr_district').value = '';
        document.getElementById('addr_ward').value = '';
        switchLocTab('district');
    } else if (type === 'district') {
        locState.district = item.code; locState.district_name = item.name;
        locState.ward = null; locState.ward_name = '';
        document.getElementById('addr_district').value = item.name;
        document.getElementById('addr_ward').value = '';
        switchLocTab('ward');
    } else if (type === 'ward') {
        locState.ward = item.code; locState.ward_name = item.name;
        document.getElementById('addr_ward').value = item.name;
        updateLocPickerText();
        document.getElementById('locPanel').style.display = 'none';
    }
}

function updateLocPickerText() {
    const textEl = document.getElementById('locPickerText');
    const inputEl = document.getElementById('locPickerInput');
    if (locState.province_name && locState.district_name && locState.ward_name) {
        textEl.textContent = `${locState.province_name}, ${locState.district_name}, ${locState.ward_name}`;
        inputEl.classList.add('has-value');
    } else if (locState.province_name) {
        textEl.textContent = `${locState.province_name}` + (locState.district_name ? `, ${locState.district_name}` : '');
        inputEl.classList.add('has-value');
    } else {
        textEl.textContent = 'Tỉnh/Thành Phố, Quận/Huyện, Phường/Xã';
        inputEl.classList.remove('has-value');
    }
}

function switchLocTab(tab) {
    locState.currentTab = tab;
    document.getElementById('tab_province').classList.toggle('active', tab === 'province');
    document.getElementById('tab_district').classList.toggle('active', tab === 'district');
    document.getElementById('tab_ward').classList.toggle('active', tab === 'ward');

    if (tab === 'province') {
        fetchProvinces();
    } else if (tab === 'district') {
        if (locState.province) fetchDistricts(locState.province);
        else document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Tỉnh/Thành Phố trước</div>';
    } else if (tab === 'ward') {
        if (locState.district) fetchWards(locState.district);
        else document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Quận/Huyện trước</div>';
    }
}

function toggleLocPanel() {
    const panel = document.getElementById('locPanel');
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
    } else {
        panel.style.display = 'block';
        if (!locState.province) switchLocTab('province');
        else if (!locState.district) switchLocTab('district');
        else if (!locState.ward) switchLocTab('ward');
        else switchLocTab('province');
    }
}

document.addEventListener('click', function (e) {
    const container = document.getElementById('locPickerContainer');
    if (container && !container.contains(e.target) && document.body.contains(e.target)) {
        const panel = document.getElementById('locPanel');
        if (panel) panel.style.display = 'none';
    }
});

function openAddressModal() {
    document.getElementById('addressModalTitle').textContent = 'Địa chỉ mới';
    document.getElementById('addr_id').value = '';
    document.getElementById('addr_fullname').value = '';
    document.getElementById('addr_phone').value = '';
    document.getElementById('addr_specific').value = '';
    document.getElementById('addr_province').value = '';
    document.getElementById('addr_district').value = '';
    document.getElementById('addr_ward').value = '';

    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };
    updateLocPickerText();

    document.getElementById('locPickerContainer').style.display = 'block';
    document.getElementById('fakeMapBox').style.display = 'flex';
    document.getElementById('resetGpsBtn').style.display = 'none';
    document.getElementById('addr_specific').readOnly = false;

    setAddrType('home');
    document.getElementById('addr_default').checked = false;
    document.getElementById('addressModal').style.display = 'flex';
}

function editAddress(addr) {
    document.getElementById('addressModalTitle').textContent = 'Cập nhật địa chỉ';
    document.getElementById('addr_id').value = addr.id;
    document.getElementById('addr_fullname').value = addr.fullname;
    document.getElementById('addr_phone').value = addr.phone;
    document.getElementById('addr_specific').value = addr.specific_address;
    document.getElementById('addr_province').value = addr.province;
    document.getElementById('addr_district').value = addr.district;
    document.getElementById('addr_ward').value = addr.ward;

    locState.province_name = addr.province;
    locState.district_name = addr.district;
    locState.ward_name = addr.ward;
    locState.province = null; locState.district = null; locState.ward = null;
    updateLocPickerText();

    if (addr.specific_address && addr.province && addr.specific_address.includes(addr.province)) {
        document.getElementById('locPickerContainer').style.display = 'none';
        document.getElementById('fakeMapBox').style.display = 'none';
        document.getElementById('resetGpsBtn').style.display = 'flex';
        document.getElementById('addr_specific').readOnly = true;
    } else {
        document.getElementById('locPickerContainer').style.display = 'block';
        document.getElementById('fakeMapBox').style.display = 'flex';
        document.getElementById('resetGpsBtn').style.display = 'none';
        document.getElementById('addr_specific').readOnly = false;
    }

    setAddrType(addr.type);
    document.getElementById('addr_default').checked = addr.is_default == 1;
    document.getElementById('addressModal').style.display = 'flex';
}

function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
}

function setAddrType(type) {
    document.getElementById('addr_type').value = type;
    document.getElementById('btnTypeHome').classList.toggle('active', type === 'home');
    document.getElementById('btnTypeOffice').classList.toggle('active', type === 'office');
}

async function saveAddress() {
    const id = document.getElementById('addr_id').value;
    const data = {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        fullname: document.getElementById('addr_fullname').value,
        phone: document.getElementById('addr_phone').value,
        province: document.getElementById('addr_province').value,
        district: document.getElementById('addr_district').value,
        ward: document.getElementById('addr_ward').value,
        specific_address: document.getElementById('addr_specific').value,
        type: document.getElementById('addr_type').value,
        is_default: document.getElementById('addr_default').checked ? 1 : 0
    };

    if (!data.fullname || !data.phone || !data.province || !data.district || !data.ward || !data.specific_address) {
        alert("Vui lòng điền đầy đủ thông tin.");
        return;
    }

    const url = id ? `/profile/address/${id}` : '/profile/address';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || "Có lỗi xảy ra");
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}

async function deleteAddress(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) return;
    try {
        const res = await fetch(`/profile/address/${id}/delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content') })
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}

async function setDefaultAddress(id) {
    try {
        const res = await fetch(`/profile/address/${id}/default`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content') })
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}

async function getCurrentLocation(btn) {
    const currentProvince = document.getElementById('addr_province').value;
    const currentSpecific = document.getElementById('addr_specific').value.trim();

    if (currentProvince || currentSpecific) {
        if (!confirm("Bạn đã nhập địa chỉ thủ công. Bạn có chắc muốn dùng địa chỉ GPS để thay thế không?")) return;
    }

    const textSpan = document.getElementById('gps-btn-text');
    const originalText = textSpan.innerText;

    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
        return;
    }

    btn.disabled = true;
    textSpan.innerText = "Đang lấy vị trí...";
    const originalBg = btn.style.backgroundColor;
    btn.style.backgroundColor = '#f3f4f6';
    btn.style.cursor = 'wait';

    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=vi`);
            const data = await res.json();

            if (data && data.display_name) {
                const addr = data.address || {};
                const province = addr.city || addr.state || addr.province || '';
                const district = addr.county || addr.city_district || addr.suburb || addr.town || '';
                const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

                document.getElementById('addr_province').value = province;
                document.getElementById('addr_district').value = district;
                document.getElementById('addr_ward').value = ward;
                document.getElementById('addr_specific').value = data.display_name;

                document.getElementById('locPickerContainer').style.display = 'none';
                document.getElementById('fakeMapBox').style.display = 'none';
                document.getElementById('resetGpsBtn').style.display = 'flex';
                document.getElementById('addr_specific').readOnly = true;
                alert("Đã tự động điền địa chỉ dựa trên GPS!");
            } else {
                alert("Không thể chuyển đổi tọa độ thành địa chỉ.");
            }
        } catch (error) {
            console.error(error);
            alert("Có lỗi xảy ra khi gọi API bản đồ.");
        } finally {
            btn.disabled = false;
            textSpan.innerText = originalText;
            btn.style.backgroundColor = originalBg;
            btn.style.cursor = 'pointer';
        }
    }, (error) => {
        let msg = "Không thể lấy vị trí.";
        switch (error.code) {
            case error.PERMISSION_DENIED: msg = "Bạn đã từ chối quyền truy cập vị trí."; break;
            case error.POSITION_UNAVAILABLE: msg = "Thông vị trí không khả dụng."; break;
            case error.TIMEOUT: msg = "Yêu cầu lấy vị trí quá thời gian."; break;
        }
        alert(msg);
        btn.disabled = false;
        textSpan.innerText = originalText;
        btn.style.backgroundColor = originalBg;
        btn.style.cursor = 'pointer';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
}

function resetToManual() {
    document.getElementById('locPickerContainer').style.display = 'block';
    document.getElementById('fakeMapBox').style.display = 'flex';
    document.getElementById('resetGpsBtn').style.display = 'none';
    document.getElementById('addr_specific').readOnly = false;

    document.getElementById('addr_province').value = '';
    document.getElementById('addr_district').value = '';
    document.getElementById('addr_ward').value = '';
    document.getElementById('addr_specific').value = '';

    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };
    updateLocPickerText();
}

// ----- Avatar Cropper Logic -----
let cropper;

function previewAvatar(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('imageToCrop').src = e.target.result;
            document.getElementById('cropperModal').style.display = 'flex';

            if (cropper) cropper.destroy();
            cropper = new Cropper(document.getElementById('imageToCrop'), {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        }
        reader.readAsDataURL(file);
    }
}

function closeCropperModal() {
    document.getElementById('cropperModal').style.display = 'none';
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    document.getElementById('avatarInput').value = '';
}

function cropImage() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    const base64Image = canvas.toDataURL('image/jpeg', 0.9);
    if (document.getElementById('avatarPreview')) {
        document.getElementById('avatarPreview').src = base64Image;
    }
    if (document.getElementById('avatarPreviewMobile')) {
        document.getElementById('avatarPreviewMobile').src = base64Image;
    }
    document.getElementById('croppedAvatarInput').value = base64Image;
    closeCropperModal();
}

function showTab(tab) {
    const deskProfile = document.getElementById('desktop-profile-content');
    const deskPass = document.getElementById('desktop-password-content');
    const deskAddress = document.getElementById('desktop-address-content');
    
    const mobProfile = document.getElementById('mobile-profile-content');
    const mobPass = document.getElementById('mobile-password-content');
    const mobAddress = document.getElementById('mobile-address-content');

    const profileLink = document.getElementById('tab-profile-link');
    const passwordLink = document.getElementById('tab-password-link');
    const addressLink = document.getElementById('tab-address-link');

    const headerTitle = document.getElementById('mobile-header-title');
    const backBtn = document.getElementById('mobile-back-btn');

    // Reset visibility
    if (deskProfile) deskProfile.classList.add('hidden');
    if (deskPass) deskPass.classList.add('hidden');
    if (deskAddress) deskAddress.classList.add('hidden');
    
    if (mobProfile) mobProfile.classList.add('hidden');
    if (mobPass) mobPass.classList.add('hidden');
    if (mobAddress) mobAddress.classList.add('hidden');

    function resetLink(link) {
        if (!link) return;
        link.className = "text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md";
        const icon = link.querySelector('.material-symbols-outlined');
        if (icon) icon.style.fontVariationSettings = "";
    }
    
    function activeLink(link) {
        if (!link) return;
        link.className = "bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md";
        const icon = link.querySelector('.material-symbols-outlined');
        if (icon) icon.style.fontVariationSettings = "'FILL' 1";
    }

    resetLink(profileLink);
    resetLink(passwordLink);
    resetLink(addressLink);

    if (tab === 'password') {
        if (deskPass) deskPass.classList.remove('hidden');
        if (mobPass) mobPass.classList.remove('hidden');
        activeLink(passwordLink);
        if (headerTitle) headerTitle.textContent = "Đổi mật khẩu";
        window.location.hash = 'password';
    } else if (tab === 'address') {
        if (deskAddress) deskAddress.classList.remove('hidden');
        if (mobAddress) mobAddress.classList.remove('hidden');
        activeLink(addressLink);
        if (headerTitle) headerTitle.textContent = "Số địa chỉ";
        window.location.hash = 'address';
    } else {
        if (deskProfile) deskProfile.classList.remove('hidden');
        if (mobProfile) mobProfile.classList.remove('hidden');
        activeLink(profileLink);
        if (headerTitle) headerTitle.textContent = "Tài khoản";
        window.location.hash = 'profile';
    }

    if (tab !== 'profile') {
        if (backBtn) {
            backBtn.href = "#profile";
            backBtn.onclick = function (e) {
                e.preventDefault();
                showTab('profile');
            };
        }
    } else {
        if (backBtn) {
            backBtn.href = backBtn.dataset.prevUrl || 'javascript:history.back()';
            backBtn.onclick = null;
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const backBtn = document.getElementById('mobile-back-btn');
    if (backBtn) {
        backBtn.dataset.prevUrl = backBtn.getAttribute('href');
    }

    if (window.location.hash === '#password' || window.location.hash === '#change-password') {
        showTab('password');
    } else if (window.location.hash === '#address') {
        showTab('address');
    } else {
        showTab('profile');
    }

    // 1. Password Visibility Toggle
    const toggleButtons = document.querySelectorAll('.toggle-password-visibility');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            
            const iconSpan = this.querySelector('.material-symbols-outlined');
            if (input.type === 'password') {
                input.type = 'text';
                if (iconSpan) iconSpan.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                if (iconSpan) iconSpan.textContent = 'visibility';
            }
        });
    });

    // 2. Real-time Password Validation
    const newPassDesk = document.getElementById('new_password_desk');
    const newPassMob = document.getElementById('new_password_mob');
    const confirmPassDesk = document.getElementById('new_password_confirmation_desk');
    const confirmPassMob = document.getElementById('new_password_confirmation_mob');

    function checkPasswordStrength() {
        const val = this.value;
        if (this === newPassDesk && newPassMob) newPassMob.value = val;
        if (this === newPassMob && newPassDesk) newPassDesk.value = val;

        const confirmVal = (this === newPassDesk || this === newPassMob) 
            ? (confirmPassDesk ? confirmPassDesk.value : '') 
            : this.value;
            
        if (this === confirmPassDesk && confirmPassMob) confirmPassMob.value = this.value;
        if (this === confirmPassMob && confirmPassDesk) confirmPassDesk.value = this.value;

        const password = newPassDesk ? newPassDesk.value : (newPassMob ? newPassMob.value : '');

        // Rules
        const hasLength = password.length >= 8;
        const hasCase = /[a-z]/.test(password) && /[A-Z]/.test(password);
        const hasNumberOrSymbol = /[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password);
        const matches = password === confirmVal && password.length > 0;

        // Calculate score
        let score = 0;
        if (password.length > 0) {
            if (hasLength) score++;
            if (hasCase) score++;
            if (hasNumberOrSymbol) score++;
        }

        // Update indicators
        updateIndicator('req-length', hasLength);
        updateIndicator('req-case', hasCase);
        updateIndicator('req-number', hasNumberOrSymbol);
        updateIndicator('req-match', matches);

        // Update strength meters
        updateStrengthMeter(score, password.length > 0);
    }

    function updateIndicator(idPrefix, isValid) {
        ['desk', 'mob'].forEach(suffix => {
            const el = document.getElementById(`${idPrefix}-${suffix}`);
            if (!el) return;
            const icon = el.querySelector('.material-symbols-outlined');
            if (isValid) {
                el.classList.remove('text-on-surface-variant');
                el.classList.add('text-primary');
                if (icon) {
                    icon.textContent = 'check_circle';
                    icon.classList.remove('text-outline');
                    icon.classList.add('text-primary');
                }
            } else {
                el.classList.remove('text-primary');
                el.classList.add('text-on-surface-variant');
                if (icon) {
                    icon.textContent = 'radio_button_unchecked';
                    icon.classList.remove('text-primary');
                    icon.classList.add('text-outline');
                }
            }
        });
    }

    function updateStrengthMeter(score, hasInput) {
        const labels = {
            0: { text: 'Yếu', colorClass: 'text-error', barClass: 'bg-error', width: '33%' },
            1: { text: 'Yếu', colorClass: 'text-error', barClass: 'bg-error', width: '33%' },
            2: { text: 'Trung bình', colorClass: 'text-amber-500', barClass: 'bg-amber-500', width: '66%' },
            3: { text: 'Mạnh', colorClass: 'text-primary', barClass: 'bg-primary', width: '100%' }
        };

        const config = hasInput ? labels[score] : { text: 'Chưa nhập', colorClass: 'text-outline', barClass: 'bg-outline', width: '0%' };

        ['desk', 'mob'].forEach(suffix => {
            const labelEl = document.getElementById(`strength-label-${suffix}`);
            const barEl = document.getElementById(`strength-bar-${suffix}`);
            if (labelEl) {
                labelEl.textContent = config.text;
                labelEl.className = `font-bold ${config.colorClass}`;
            }
            if (barEl) {
                barEl.style.width = config.width;
                barEl.classList.remove('bg-outline', 'bg-error', 'bg-amber-500', 'bg-primary');
                barEl.classList.add(config.barClass);
            }
        });
    }

    // Attach listeners
    [newPassDesk, newPassMob, confirmPassDesk, confirmPassMob].forEach(input => {
        if (input) {
            input.addEventListener('input', checkPasswordStrength);
        }
    });
});

window.showTab = showTab;