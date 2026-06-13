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
let locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province', activePrefix: 'desk' };

async function fetchProvinces(prefix) {
    renderLocLoading(prefix);
    try {
        const res = await fetch('https://provinces.open-api.vn/api/v2/p/');
        const data = await res.json();
        renderLocItems(data, 'province', prefix);
    } catch (e) { console.error(e); }
}

async function fetchWards(provinceCode, prefix) {
    renderLocLoading(prefix);
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/v2/p/${provinceCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.wards, 'ward', prefix);
    } catch (e) { console.error(e); }
}

function renderLocLoading(prefix) {
    const list = document.getElementById(`${prefix}_locList`);
    if (list) list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Đang tải...</div>';
}

function renderLocItems(items, type, prefix) {
    const list = document.getElementById(`${prefix}_locList`);
    if (!list) return;
    list.innerHTML = '';
    if (!items || items.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Không có dữ liệu</div>';
        return;
    }
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-surface-container-lowest cursor-pointer transition-colors border-b border-outline-variant/50 last:border-0';
        div.textContent = item.name;
        div.onclick = () => selectLocItem(item, type, prefix);
        list.appendChild(div);
    });
}

function selectLocItem(item, type, prefix) {
    if (type === 'province') {
        locState.province = item.code; locState.province_name = item.name;
        locState.ward = null; locState.ward_name = '';
        document.getElementById(`${prefix}_addr_province`).value = item.name;
        document.getElementById(`${prefix}_addr_district`).value = '';
        document.getElementById(`${prefix}_addr_ward`).value = '';
        switchLocTab(prefix, 'ward');
    } else if (type === 'ward') {
        locState.ward = item.code; locState.ward_name = item.name;
        document.getElementById(`${prefix}_addr_ward`).value = item.name;
        // Trick backend by setting district equal to ward
        document.getElementById(`${prefix}_addr_district`).value = item.name;
        updateLocPickerText(prefix);
        document.getElementById(`${prefix}_locPanel`).style.display = 'none';
        geocodeAndUpdateMap(prefix);
    }
}

function geocodeAndUpdateMap(prefix) {
    const province = document.getElementById(`${prefix}_addr_province`).value;
    const district = document.getElementById(`${prefix}_addr_district`).value;
    const ward = document.getElementById(`${prefix}_addr_ward`).value;
    const specific = document.getElementById(`${prefix}_addr_specific`).value;

    let parts = [];
    if (specific) parts.push(specific);
    if (ward) parts.push(ward);
    if (province) parts.push(province);

    if (parts.length > 0) {
        const addressStr = parts.join(', ');
        geocodeAddress(addressStr).then(coords => {
            if (coords) {
                initLeafletMap(prefix, coords.lat, coords.lng);
            }
        });
    }
}

// Attach blur and enter events to specific address inputs to trigger map update
document.addEventListener('DOMContentLoaded', () => {
    ['desk', 'mob'].forEach(prefix => {
        const specificInput = document.getElementById(`${prefix}_addr_specific`);
        if (specificInput) {
            specificInput.addEventListener('blur', () => geocodeAndUpdateMap(prefix));
            specificInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    geocodeAndUpdateMap(prefix);
                }
            });
        }
    });
});

function updateLocPickerText(prefix) {
    const inputEl = document.getElementById(`${prefix}_locPickerInputText`);
    if (!inputEl) return;
    if (locState.province_name && locState.ward_name) {
        inputEl.value = `${locState.province_name}, ${locState.ward_name}`;
    } else if (locState.province_name) {
        inputEl.value = `${locState.province_name}`;
    } else {
        inputEl.value = '';
    }
}

function switchLocTab(prefix, tab) {
    locState.currentTab = tab;

    ['province', 'ward'].forEach(t => {
        const el = document.getElementById(`${prefix}_tab_${t}`);
        if (el) {
            if (t === tab) el.classList.add('bg-surface-container-low', 'text-primary');
            else el.classList.remove('bg-surface-container-low', 'text-primary');
        }
    });

    if (tab === 'province') {
        fetchProvinces(prefix);
    } else if (tab === 'ward') {
        if (locState.province) fetchWards(locState.province, prefix);
        else document.getElementById(`${prefix}_locList`).innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Tỉnh/Thành Phố trước</div>';
    }
}

function toggleLocPanel(prefix) {
    const panel = document.getElementById(`${prefix}_locPanel`);
    if (!panel) return;
    locState.activePrefix = prefix;
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
    } else {
        document.querySelectorAll('.loc-panel').forEach(p => p.style.display = 'none');
        panel.style.display = 'block';
        if (!locState.province) switchLocTab(prefix, 'province');
        else if (!locState.ward) switchLocTab(prefix, 'ward');
        else switchLocTab(prefix, 'province');
    }
}

document.addEventListener('click', function (e) {
    // Prevent hiding if the clicked element was removed from the DOM (e.g. during selectLocItem)
    if (!document.body.contains(e.target)) return;

    let clickedInside = false;
    ['desk', 'mob'].forEach(prefix => {
        const input = document.getElementById(`${prefix}_locPickerInputText`);
        const panel = document.getElementById(`${prefix}_locPanel`);
        if (input && input.contains(e.target)) clickedInside = true;
        if (panel && panel.contains(e.target)) clickedInside = true;
    });

    if (!clickedInside) {
        document.querySelectorAll('.loc-panel').forEach(p => p.style.display = 'none');
    }
});

let leafletMapDesk = null;
let leafletMarkerDesk = null;
let leafletMapMob = null;
let leafletMarkerMob = null;

function initLeafletMap(prefix, lat, lng) {
    const defaultLat = lat || 10.73809;
    const defaultLng = lng || 106.67812;

    document.getElementById(`${prefix}_addr_lat`).value = defaultLat;
    document.getElementById(`${prefix}_addr_lng`).value = defaultLng;

    let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
    let marker = prefix === 'desk' ? leafletMarkerDesk : leafletMarkerMob;
    const mapId = `${prefix}_leafletMap`;

    if (map) {
        map.setView([defaultLat, defaultLng], 15);
        if (marker) {
            marker.setLatLng([defaultLat, defaultLng]);
        } else {
            marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
            setupMarkerEvents(prefix, marker);
            if (prefix === 'desk') leafletMarkerDesk = marker;
            else leafletMarkerMob = marker;
        }
        return;
    }

    map = L.map(mapId).setView([defaultLat, defaultLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
    setupMarkerEvents(prefix, marker);

    if (prefix === 'desk') {
        leafletMapDesk = map;
        leafletMarkerDesk = marker;
    } else {
        leafletMapMob = map;
        leafletMarkerMob = marker;
    }

    map.on('click', function (e) {
        const newLat = e.latlng.lat;
        const newLng = e.latlng.lng;
        marker.setLatLng([newLat, newLng]);
        updateCoordinates(prefix, newLat, newLng);
        reverseGeocode(prefix, newLat, newLng);
    });
}

function setupMarkerEvents(prefix, marker) {
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateCoordinates(prefix, position.lat, position.lng);
        reverseGeocode(prefix, position.lat, position.lng);
    });
}

function updateCoordinates(prefix, lat, lng) {
    document.getElementById(`${prefix}_addr_lat`).value = lat;
    document.getElementById(`${prefix}_addr_lng`).value = lng;
}

async function geocodeAddress(addressString) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addressString)}&addressdetails=1&limit=1&email=test@example.com`);
        const data = await res.json();
        if (data && data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
    } catch (e) {
        console.error("Geocoding error:", e);
    }
    return null;
}

async function reverseGeocode(prefix, lat, lng) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi&email=test@example.com`);
        const data = await res.json();
        if (data && data.address) {
            const addr = data.address;
            const province = addr.state || addr.province || addr.city || '';
            
            let district = addr.county || addr.city_district || addr.suburb || addr.town || '';
            if (!district && addr.city && addr.city !== province) {
                district = addr.city;
            }
            const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

            let specific = data.display_name;
            if (addr.road) {
                specific = (addr.house_number ? addr.house_number + ' ' : '') + addr.road;
            }

            document.getElementById(`${prefix}_addr_province`).value = province;
            
            // Combine Nominatim's district and ward into our single 'ward' field
            const combinedWard = [district, ward].filter(Boolean).join(', ');
            
            document.getElementById(`${prefix}_addr_ward`).value = combinedWard;
            // Trick backend again
            document.getElementById(`${prefix}_addr_district`).value = combinedWard;
            document.getElementById(`${prefix}_addr_specific`).value = specific;

            locState.province_name = province;
            locState.ward_name = combinedWard;
            locState.province = null;
            locState.ward = null;
            updateLocPickerText(prefix);
        }
    } catch (e) {
        console.error("Reverse geocoding error:", e);
    }
}

function openAddressModal(prefix) {
    const isDesktop = prefix === 'desk';

    if (isDesktop) {
        document.getElementById('desktop-address-content').classList.add('hidden');
        document.getElementById('desktop-address-form-content').classList.remove('hidden');
    } else {
        document.getElementById('mobile-address-content').classList.add('hidden');
        document.getElementById('mobile-address-form-content').classList.remove('hidden');
    }

    document.getElementById(`${prefix}_addressModalTitle`).textContent = 'Thêm địa chỉ mới';
    document.getElementById(`${prefix}_addressModalTitleBc`).textContent = 'Thêm địa chỉ mới';
    document.getElementById(`${prefix}_addr_id`).value = '';
    document.getElementById(`${prefix}_addr_fullname`).value = '';
    document.getElementById(`${prefix}_addr_phone`).value = '';
    document.getElementById(`${prefix}_addr_specific`).value = '';
    document.getElementById(`${prefix}_addr_province`).value = '';
    document.getElementById(`${prefix}_addr_district`).value = '';
    document.getElementById(`${prefix}_addr_ward`).value = '';
    document.getElementById(`${prefix}_addr_lat`).value = '';
    document.getElementById(`${prefix}_addr_lng`).value = '';

    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province', activePrefix: prefix };
    updateLocPickerText(prefix);

    setAddrType(prefix, 'home');
    document.getElementById(`${prefix}_addr_default`).checked = false;

    setTimeout(() => {
        initLeafletMap(prefix);
        let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
        if (map) {
            map.invalidateSize();
        }
    }, 200);
}

function editAddress(prefix, addr) {
    const isDesktop = prefix === 'desk';

    if (isDesktop) {
        document.getElementById('desktop-address-content').classList.add('hidden');
        document.getElementById('desktop-address-form-content').classList.remove('hidden');
    } else {
        document.getElementById('mobile-address-content').classList.add('hidden');
        document.getElementById('mobile-address-form-content').classList.remove('hidden');
    }

    document.getElementById(`${prefix}_addressModalTitle`).textContent = 'Cập nhật địa chỉ';
    document.getElementById(`${prefix}_addressModalTitleBc`).textContent = 'Cập nhật địa chỉ';
    document.getElementById(`${prefix}_addr_id`).value = addr.id;
    document.getElementById(`${prefix}_addr_fullname`).value = addr.fullname;
    document.getElementById(`${prefix}_addr_phone`).value = addr.phone;
    document.getElementById(`${prefix}_addr_specific`).value = addr.specific_address;
    document.getElementById(`${prefix}_addr_province`).value = addr.province;
    document.getElementById(`${prefix}_addr_district`).value = addr.district;
    document.getElementById(`${prefix}_addr_ward`).value = addr.ward;

    const lat = addr.latitude ? parseFloat(addr.latitude) : null;
    const lng = addr.longitude ? parseFloat(addr.longitude) : null;
    document.getElementById(`${prefix}_addr_lat`).value = lat || '';
    document.getElementById(`${prefix}_addr_lng`).value = lng || '';

    locState.province_name = addr.province;
    locState.district_name = addr.district;
    locState.ward_name = addr.ward;
    locState.province = null; locState.district = null; locState.ward = null;
    updateLocPickerText(prefix);

    setAddrType(prefix, addr.type);
    document.getElementById(`${prefix}_addr_default`).checked = addr.is_default == 1;

    setTimeout(() => {
        if (lat && lng) {
            initLeafletMap(prefix, lat, lng);
            let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
            if (map) map.invalidateSize();
        } else {
            const addressStr = `${addr.specific_address}, ${addr.ward}, ${addr.district}, ${addr.province}`;
            geocodeAddress(addressStr).then(coords => {
                if (coords) {
                    initLeafletMap(prefix, coords.lat, coords.lng);
                } else {
                    initLeafletMap(prefix);
                }
                let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
                if (map) map.invalidateSize();
            });
        }
    }, 200);
}

function closeAddressModal(prefix) {
    const isDesktop = prefix === 'desk';
    if (isDesktop) {
        document.getElementById('desktop-address-form-content').classList.add('hidden');
        document.getElementById('desktop-address-content').classList.remove('hidden');
    } else {
        document.getElementById('mobile-address-form-content').classList.add('hidden');
        document.getElementById('mobile-address-content').classList.remove('hidden');
    }
}

function setAddrType(prefix, type) {
    document.getElementById(`${prefix}_addr_type`).value = type;
    ['home', 'office', 'family'].forEach(t => {
        const btn = document.getElementById(`${prefix}_btnType${t.charAt(0).toUpperCase() + t.slice(1)}`);
        if (btn) {
            if (t === type) {
                btn.classList.add('bg-primary/10', 'border-primary', 'text-primary');
                btn.classList.remove('bg-surface-container-lowest', 'border-outline-variant', 'text-on-surface-variant');
            } else {
                btn.classList.remove('bg-primary/10', 'border-primary', 'text-primary');
                btn.classList.add('bg-surface-container-lowest', 'border-outline-variant', 'text-on-surface-variant');
            }
        }
    });
}

async function saveAddress(prefix) {
    const id = document.getElementById(`${prefix}_addr_id`).value;
    const data = {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        fullname: document.getElementById(`${prefix}_addr_fullname`).value,
        phone: document.getElementById(`${prefix}_addr_phone`).value,
        province: document.getElementById(`${prefix}_addr_province`).value,
        district: document.getElementById(`${prefix}_addr_district`).value,
        ward: document.getElementById(`${prefix}_addr_ward`).value,
        specific_address: document.getElementById(`${prefix}_addr_specific`).value,
        type: document.getElementById(`${prefix}_addr_type`).value,
        is_default: document.getElementById(`${prefix}_addr_default`).checked ? 1 : 0,
        latitude: document.getElementById(`${prefix}_addr_lat`).value || null,
        longitude: document.getElementById(`${prefix}_addr_lng`).value || null
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

async function getCurrentLocation(prefix, btn) {
    const currentProvince = document.getElementById(`${prefix}_addr_province`).value;
    const currentSpecific = document.getElementById(`${prefix}_addr_specific`).value.trim();

    if (currentProvince || currentSpecific) {
        if (!confirm("Bạn đã nhập địa chỉ thủ công. Bạn có chắc muốn dùng địa chỉ GPS để thay thế không?")) return;
    }

    const textSpan = document.getElementById(`${prefix}_gps-btn-text`);
    const originalText = textSpan.innerText;

    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
        return;
    }

    btn.disabled = true;
    textSpan.innerText = "Đang lấy vị trí...";
    btn.style.opacity = '0.5';
    btn.style.cursor = 'wait';

    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=vi&email=test@example.com`);
            const data = await res.json();

            if (data && data.display_name) {
                const addr = data.address || {};
                const province = addr.city || addr.state || addr.province || '';
                const district = addr.county || addr.city_district || addr.suburb || addr.town || '';
                const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

                document.getElementById(`${prefix}_addr_province`).value = province;
                document.getElementById(`${prefix}_addr_district`).value = district;
                document.getElementById(`${prefix}_addr_ward`).value = ward;

                let specific = data.display_name;
                if (addr.road) {
                    specific = (addr.house_number ? addr.house_number + ' ' : '') + addr.road;
                }
                document.getElementById(`${prefix}_addr_specific`).value = specific;

                locState.province_name = province;
                locState.district_name = district;
                locState.ward_name = ward;
                locState.province = null;
                locState.district = null;
                locState.ward = null;
                updateLocPickerText(prefix);

                // Initialize/update Leaflet map and marker
                initLeafletMap(prefix, lat, lon);
                let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
                if (map) map.invalidateSize();

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
            btn.style.opacity = '1';
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
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
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