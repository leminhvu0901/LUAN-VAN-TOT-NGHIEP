// ─── Xử lý chuyển đổi phương thức thanh toán (COD ↔ MoMo) ────────────────
(function () {
    const form       = document.getElementById('checkout-form');
    const submitBtn  = document.getElementById('order-submit-btn');
    const submitText = document.getElementById('submit-btn-text');
    if (!form || !submitBtn || !submitText) return;

    const codUrl  = form.dataset.codUrl;
    const momoUrl = form.dataset.momoUrl;

    function updateFormByPayment(method) {
        if (method === 'momo') {
            form.action = momoUrl;
            submitText.textContent = '💳 Thanh toán qua MoMo';
            submitBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]');
            submitBtn.classList.add('bg-[#ae2070]', 'hover:bg-[#8b1a5a]');
        } else {
            form.action = codUrl;
            submitText.textContent = 'Đặt hàng (COD)';
            submitBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]');
            submitBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]');
        }
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateFormByPayment(this.value);
        });
    });

    // Khởi tạo theo lựa chọn mặc định
    const defaultChecked = document.querySelector('input[name="payment_method"]:checked');
    if (defaultChecked) updateFormByPayment(defaultChecked.value);
})();

// ----- Location & Address Modal State -----
let locState = { province: null, ward: null, province_name: '', ward_name: '', currentTab: 'province' };

async function fetchProvinces() {
    renderLocLoading();
    try {
        const res = await fetch('https://provinces.open-api.vn/api/v2/p/');
        const data = await res.json();
        renderLocItems(data, 'province');
    } catch (e) { console.error(e); }
}

async function fetchWards(provinceCode) {
    renderLocLoading();
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/v2/p/${provinceCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.wards, 'ward');
    } catch (e) { console.error(e); }
}

function renderLocLoading() {
    const list = document.getElementById('locList');
    if (list) list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Đang tải...</div>';
}

let currentLocItems = [];
let currentLocType = '';

function renderLocItems(items, type) {
    currentLocItems = items;
    currentLocType = type;
    displayLocItems(items, type);
}

function displayLocItems(items, type) {
    const list = document.getElementById('locList');
    if (!list) return;
    list.innerHTML = '';
    if (!items || items.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Không có dữ liệu</div>';
        return;
    }

    let sortedItems = items;
    if (type === 'ward') {
        sortedItems = [...items].sort((a, b) => {
            const aIsPhuong = a.name.toLowerCase().startsWith('phường');
            const bIsPhuong = b.name.toLowerCase().startsWith('phường');
            const aIsXa = a.name.toLowerCase().startsWith('xã');
            const bIsXa = b.name.toLowerCase().startsWith('xã');
            
            if (aIsPhuong && !bIsPhuong) return -1;
            if (!aIsPhuong && bIsPhuong) return 1;
            if (aIsXa && !bIsXa) return -1;
            if (!aIsXa && bIsXa) return 1;
            return a.name.localeCompare(b.name);
        });
    }

    sortedItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-surface-container-lowest cursor-pointer transition-colors border-b border-outline-variant/50 last:border-0 loc-item';
        div.textContent = item.name;
        div.onclick = () => selectLocItem(item, type);
        list.appendChild(div);
    });
}

function removeAccents(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

function filterLocItems(keyword) {
    if (!keyword) {
        displayLocItems(currentLocItems, currentLocType);
        return;
    }
    const kw = removeAccents(keyword);
    const filtered = currentLocItems.filter(item => removeAccents(item.name).includes(kw));
    displayLocItems(filtered, currentLocType);
}

function selectLocItem(item, type) {
    if (type === 'province') {
        locState.province = item.code; locState.province_name = item.name;
        locState.ward = null; locState.ward_name = '';
        document.getElementById('addr_province').value = item.name;
        document.getElementById('addr_district').value = '';
        document.getElementById('addr_ward').value = '';
        switchLocTab('ward');
    } else if (type === 'ward') {
        locState.ward = item.code; locState.ward_name = item.name;
        document.getElementById('addr_ward').value = item.name;
        // Trick backend to pass validation/shipping by duplicating ward into district
        document.getElementById('addr_district').value = item.name;
        updateLocPickerText();
        document.getElementById('locPanel').style.display = 'none';
        geocodeAndUpdateMap();
    }
}

function geocodeAndUpdateMap() {
    const province = document.getElementById('addr_province').value;
    const district = document.getElementById('addr_district').value;
    const ward = document.getElementById('addr_ward').value;
    const specific = document.getElementById('addr_specific').value;

    let parts = [];
    if (specific) parts.push(specific);
    if (ward) parts.push(ward);
    if (province) parts.push(province);

    if (parts.length > 0) {
        const addressStr = parts.join(', ');
        geocodeAddress(addressStr).then(coords => {
            if (coords) {
                initLeafletMap(coords.lat, coords.lng);
            }
        });
    }
}

function updateLocPickerText() {
    const inputEl = document.getElementById('locPickerInputText');
    if(!inputEl) return;
    if (locState.province_name && locState.ward_name) {
        inputEl.value = `${locState.province_name}, ${locState.ward_name}`;
    } else if (locState.province_name) {
        inputEl.value = `${locState.province_name}`;
    } else {
        inputEl.value = '';
    }
}

function switchLocTab(tab) {
    locState.currentTab = tab;
    
    ['province', 'ward'].forEach(t => {
        const el = document.getElementById(`tab_${t}`);
        if(el) {
            if(t === tab) el.classList.add('bg-surface-container-low', 'text-primary');
            else el.classList.remove('bg-surface-container-low', 'text-primary');
        }
    });

    if (tab === 'province') {
        document.getElementById('locSearchInput').value = '';
        fetchProvinces();
    } else if (tab === 'ward') {
        document.getElementById('locSearchInput').value = '';
        if (locState.province) fetchWards(locState.province);
        else document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Tỉnh/Thành Phố trước</div>';
    }
}

function toggleLocPanel() {
    const panel = document.getElementById('locPanel');
    if (!panel) return;
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
    } else {
        panel.style.display = 'block';
        if (!locState.province) switchLocTab('province');
        else if (!locState.ward) switchLocTab('ward');
        else switchLocTab('province');
    }
}

// Click outside locPanel to close
document.addEventListener('click', function (e) {
    if (!document.body.contains(e.target)) return;
    const container = document.getElementById('locPickerContainer');
    if (container && !container.contains(e.target) && document.body.contains(e.target)) {
        const panel = document.getElementById('locPanel');
        if (panel) panel.style.display = 'none';
    }
});

let leafletMap = null;
let leafletMarker = null;

function initLeafletMap(lat, lng) {
    const defaultLat = lat || 10.73809;
    const defaultLng = lng || 106.67812;

    document.getElementById('addr_lat').value = defaultLat;
    document.getElementById('addr_lng').value = defaultLng;

    if (leafletMap) {
        leafletMap.setView([defaultLat, defaultLng], 15);
        if (leafletMarker) {
            leafletMarker.setLatLng([defaultLat, defaultLng]);
        } else {
            leafletMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(leafletMap);
            setupMarkerEvents(leafletMarker);
        }
        return;
    }

    leafletMap = L.map('leafletMap').setView([defaultLat, defaultLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(leafletMap);

    leafletMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(leafletMap);
    setupMarkerEvents(leafletMarker);

    leafletMap.on('click', function (e) {
        const newLat = e.latlng.lat;
        const newLng = e.latlng.lng;
        leafletMarker.setLatLng([newLat, newLng]);
        updateCoordinates(newLat, newLng);
        reverseGeocode(newLat, newLng);
    });
}

function setupMarkerEvents(marker) {
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
        reverseGeocode(position.lat, position.lng);
    });
}

function updateCoordinates(lat, lng) {
    document.getElementById('addr_lat').value = lat;
    document.getElementById('addr_lng').value = lng;
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

async function reverseGeocode(lat, lng) {
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
            
            document.getElementById('addr_province').value = province;
            document.getElementById('addr_district').value = district;
            document.getElementById('addr_ward').value = ward;
            
            let specific = data.display_name;
            if (addr.road) {
                specific = addr.road;
                if (addr.house_number) specific = addr.house_number + ' ' + specific;
            } else if (addr.village || addr.hamlet) {
                specific = addr.village || addr.hamlet;
            } else {
                specific = '';
            }
            document.getElementById('addr_specific').value = specific;

            locState.province_name = province;
            locState.district_name = district;
            locState.ward_name = ward;
            locState.province = null;
            locState.district = null;
            locState.ward = null;
            updateLocPickerText();
        }
    } catch (e) {
        console.error("Reverse geocoding error:", e);
    }
}

function openAddressModal(isEdit = false) {
    document.getElementById('addressModal').classList.remove('hidden');
    document.getElementById('address-info-block')?.classList.add('hidden');
    document.getElementById('address-action-buttons')?.classList.add('hidden');
    document.getElementById('address-list-panel')?.classList.add('hidden');
    
    if (!isEdit) {
        document.getElementById('addressModalTitle').textContent = 'Thêm địa chỉ mới';
        document.getElementById('addr_id').value = '';
        document.getElementById('addr_fullname').value = '';
        document.getElementById('addr_phone').value = '';
        document.getElementById('addr_specific').value = '';
        document.getElementById('addr_province').value = '';
        document.getElementById('addr_district').value = '';
        document.getElementById('addr_ward').value = '';
        document.getElementById('addr_lat').value = '';
        document.getElementById('addr_lng').value = '';

        locState = { province: null, ward: null, province_name: '', ward_name: '', currentTab: 'province' };
        updateLocPickerText();
        
        document.getElementById('locPickerContainer').style.display = 'block';
        document.getElementById('addr_specific').readOnly = false;
        document.getElementById('addr_default').checked = false;
        
        setAddrType('home');
        
        setTimeout(() => {
            initLeafletMap(10.73809, 106.67812);
            if (leafletMap) leafletMap.invalidateSize();
        }, 100);
    } else {
        setTimeout(() => {
            const lat = parseFloat(document.getElementById('addr_lat').value) || 10.73809;
            const lng = parseFloat(document.getElementById('addr_lng').value) || 106.67812;
            initLeafletMap(lat, lng);
            if (leafletMap) leafletMap.invalidateSize();
        }, 100);
    }
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.add('hidden');
    document.getElementById('address-info-block')?.classList.remove('hidden');
    document.getElementById('address-action-buttons')?.classList.remove('hidden');
}

function setAddrType(type) {
    document.getElementById('addr_type').value = type;
    const btnHome = document.getElementById('btnTypeHome');
    const btnOffice = document.getElementById('btnTypeOffice');
    
    if (type === 'home') {
        btnHome.classList.add('border-primary', 'bg-primary-container/10', 'text-primary');
        btnHome.classList.remove('text-on-surface-variant');
        btnOffice.classList.remove('border-primary', 'bg-primary-container/10', 'text-primary');
        btnOffice.classList.add('text-on-surface-variant');
    } else {
        btnOffice.classList.add('border-primary', 'bg-primary-container/10', 'text-primary');
        btnOffice.classList.remove('text-on-surface-variant');
        btnHome.classList.remove('border-primary', 'bg-primary-container/10', 'text-primary');
        btnHome.classList.add('text-on-surface-variant');
    }
}

function resetToManual() {
    document.getElementById('locPickerContainer').style.display = 'block';
    document.getElementById('addr_specific').readOnly = false;
    document.getElementById('addr_specific').value = '';
    
    locState = { province: null, ward: null, province_name: '', ward_name: '', currentTab: 'province' };
    updateLocPickerText();
    document.getElementById('addr_province').value = '';
    document.getElementById('addr_district').value = '';
    document.getElementById('addr_ward').value = '';
}

function getCurrentLocation(btn) {
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
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=vi&email=test@example.com`);
            const data = await res.json();

            if (data && data.display_name) {
                const addr = data.address || {};
                const province = addr.city || addr.state || addr.province || '';
                const district = addr.county || addr.city_district || addr.suburb || addr.town || '';
                const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

                document.getElementById('addr_province').value = province;
                document.getElementById('addr_district').value = district;
                document.getElementById('addr_ward').value = ward;
                
                let specific = data.display_name;
                if (addr.road) {
                    specific = addr.road;
                    if (addr.house_number) specific = addr.house_number + ' ' + specific;
                } else if (addr.village || addr.hamlet) {
                    specific = addr.village || addr.hamlet;
                } else {
                    specific = '';
                }
                document.getElementById('addr_specific').value = specific;

                locState.province_name = province;
                locState.district_name = district;
                locState.ward_name = ward;
                locState.province = null;
                locState.district = null;
                locState.ward = null;
                updateLocPickerText();

                // Initialize/update Leaflet map and marker
                initLeafletMap(lat, lon);
                if (leafletMap) leafletMap.invalidateSize();

                alert("Đã tự động điền địa chỉ dựa trên GPS!");
            } else {
                alert("Không thể chuyển đổi tọa độ thành địa chỉ.");
            }
        } catch (error) {
            console.error(error);
            alert("Có lỗi xảy ra khi gọi API bản đồ: " + (error.message || error));
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

async function saveAddress() {
    const id = document.getElementById('addr_id').value;
    const data = {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        fullname: document.getElementById('addr_fullname').value.trim(),
        phone: document.getElementById('addr_phone').value.trim(),
        province: document.getElementById('addr_province').value.trim(),
        district: document.getElementById('addr_district').value.trim(),
        ward: document.getElementById('addr_ward').value.trim(),
        specific_address: document.getElementById('addr_specific').value.trim(),
        type: document.getElementById('addr_type').value,
        is_default: document.getElementById('addr_default').checked ? 1 : 0,
        latitude: document.getElementById('addr_lat').value || null,
        longitude: document.getElementById('addr_lng').value || null
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
            // Save state to localStorage to restore after reload
            const state = {
                selected_address_id: json.id || id // use new ID or edited ID
            };
            localStorage.setItem('checkout_address_state', JSON.stringify(state));
            
            // Reload page to refresh blade address template lists
            window.location.reload();
        } else {
            alert(json.message || "Có lỗi xảy ra khi lưu địa chỉ.");
        }
    } catch (e) { 
        console.error(e);
        alert("Có lỗi xảy ra."); 
    }
}

// Bind helper function to window to expose to inline events
window.toggleLocPanel = toggleLocPanel;
window.switchLocTab = switchLocTab;
window.resetToManual = resetToManual;
window.getCurrentLocation = getCurrentLocation;
window.setAddrType = setAddrType;
window.closeAddressModal = closeAddressModal;
window.saveAddress = saveAddress;

document.addEventListener('DOMContentLoaded', function() {
    const priceSummaryEl = document.getElementById('price-summary');
    if (!priceSummaryEl) return;

    const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
    let discount = 0;


    const shippingBaseText = document.getElementById('summary-shipping-base-text');
    const shippingDistanceText = document.getElementById('summary-shipping-distance-text');
    const weatherFeeText = document.getElementById('summary-weather-fee-text');
    const peakHourFeeText = document.getElementById('summary-peak-hour-fee-text');

    const discountRow = document.getElementById('summary-discount-row');
    const discountText = document.getElementById('summary-discount-text');
    const totalText = document.getElementById('summary-total-text');
    const couponInput = document.getElementById('coupon_code_input');
    const applyCouponBtn = document.getElementById('apply_coupon_btn');
    const couponMessage = document.getElementById('coupon_message');
    const orderBtn = document.getElementById('order-submit-btn');

    // Simulator inputs
    const weatherSelect = document.getElementById('weather_select');
    const peakHourSelect = document.getElementById('peak_hour_select');

    // Auto Geocode on blur or enter
    const specificInput = document.getElementById('addr_specific');
    if (specificInput) {
        specificInput.addEventListener('blur', geocodeAndUpdateMap);
        specificInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent new line in textarea
                geocodeAndUpdateMap();
            }
        });
    }

    // Setup Edit / Add Address Triggers
    const editBtns = document.querySelectorAll('.edit-address-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const dataset = btn.dataset;
            document.getElementById('addressModalTitle').textContent = 'Cập nhật địa chỉ';
            document.getElementById('addr_id').value = dataset.addressId;
            document.getElementById('addr_fullname').value = dataset.fullname;
            document.getElementById('addr_phone').value = dataset.phone;
            document.getElementById('addr_specific').value = dataset.specificAddress;
            document.getElementById('addr_province').value = dataset.province;
            document.getElementById('addr_district').value = dataset.district;
            document.getElementById('addr_ward').value = dataset.ward;

            const lat = dataset.latitude ? parseFloat(dataset.latitude) : null;
            const lng = dataset.longitude ? parseFloat(dataset.longitude) : null;
            document.getElementById('addr_lat').value = lat || '';
            document.getElementById('addr_lng').value = lng || '';

            locState.province_name = dataset.province;
            locState.district_name = dataset.district;
            locState.ward_name = dataset.ward;
            locState.province = null; locState.district = null; locState.ward = null;
            updateLocPickerText();

            document.getElementById('locPickerContainer').style.display = 'block';
            document.getElementById('addr_specific').readOnly = false;

            setAddrType(dataset.type);
            document.getElementById('addr_default').checked = dataset.isDefault == '1';
            
            openAddressModal(true);
        });
    });

    const addBtns = document.querySelectorAll('.add-address-btn');
    addBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openAddressModal(false);
        });
    });

    // Restore simulator options and selected address from localStorage if saved
    const savedStateStr = localStorage.getItem('checkout_address_state');
    if (savedStateStr) {
        try {
            const state = JSON.parse(savedStateStr);
            localStorage.removeItem('checkout_address_state'); // Clear it immediately


            // 2. Select correct weather option
            if (weatherSelect && state.weather) {
                weatherSelect.value = state.weather;
                const hiddenWeather = document.getElementById('hidden_weather');
                if (hiddenWeather) hiddenWeather.value = state.weather;
            }

            // 3. Select correct peak hour option
            if (peakHourSelect && state.is_peak_hour !== undefined) {
                peakHourSelect.value = state.is_peak_hour;
                const hiddenPeak = document.getElementById('hidden_is_peak_hour');
                if (hiddenPeak) hiddenPeak.value = state.is_peak_hour;
            }

            // 4. Restore selected address id
            if (state.selected_address_id) {
                const addrRadio = document.querySelector(`input[name="address_selector"][value="${state.selected_address_id}"]`);
                const selectedAddressIdInput = document.getElementById('selected_address_id');
                if (selectedAddressIdInput) {
                    selectedAddressIdInput.value = state.selected_address_id;
                }
                if (addrRadio) {
                    addrRadio.checked = true;
                    // Highlight correct card initially by dispatching change
                    setTimeout(() => {
                        addrRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    }, 50);
                }
            }
        } catch (e) {
            console.error('Error restoring checkout state:', e);
        }
    }

    // Setup input visual borders
    function updateBorders(radios) {
        radios.forEach(radio => {
            const label = radio.closest('label');
            if (label) {
                if (radio.checked) {
                    label.classList.add('border-2', 'border-primary', 'bg-primary-container/10');
                    label.classList.remove('border-outline-variant');
                } else {
                    label.classList.remove('border-2', 'border-primary', 'bg-primary-container/10');
                    label.classList.add('border-outline-variant');
                }
            }
        });
    }


    // Payment method selection border update
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateBorders(paymentRadios);
            if (orderBtn) {
                if (radio.value === 'momo') {
                    orderBtn.innerText = 'Thanh toán qua MoMo';
                } else {
                    orderBtn.innerText = 'Đặt hàng (COD)';
                }
            }
        });
    });





    // Initialize borders
    updateBorders(paymentRadios);

    // Apply coupon
    let discountPercent = 0;
    let maxDiscountAmount = 0;
    if (applyCouponBtn && couponInput) {
        applyCouponBtn.addEventListener('click', () => {
            const code = couponInput.value.trim().toUpperCase();
            
            if (code === '') {
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponMessage.innerText = '';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
                calculateTotal();
                return;
            }

            const token = document.querySelector('input[name="_token"]').value;
            const priceSummaryEl = document.getElementById('price-summary');
            const currentSubtotal = priceSummaryEl ? parseInt(priceSummaryEl.dataset.subtotal) : 0;

            couponMessage.innerText = 'Đang kiểm tra...';
            couponMessage.className = 'text-xs text-on-surface-variant font-medium mt-1';

            fetch('/checkout/validate-coupon', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    coupon_code: code,
                    subtotal: currentSubtotal
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.valid) {
                    discount = parseFloat(data.discount_amount) || 0;
                    if (data.discount_type === 'percent') {
                        discountPercent = data.discount_value;
                        maxDiscountAmount = data.max_discount_amount ? parseFloat(data.max_discount_amount) : 0;
                    } else {
                        discountPercent = 0;
                        maxDiscountAmount = 0;
                    }

                    couponMessage.innerText = data.message;
                    couponMessage.className = 'text-xs text-primary font-bold mt-1';
                    discountRow.classList.remove('hidden');
                    discountText.innerText = '-' + discount.toLocaleString('vi-VN') + 'đ';
                    document.getElementById('hidden_coupon_code').value = data.coupon_code;
                } else {
                    discount = 0;
                    discountPercent = 0;
                    couponMessage.innerText = data.message;
                    couponMessage.className = 'text-xs text-error font-bold mt-1';
                    discountRow.classList.add('hidden');
                    document.getElementById('hidden_coupon_code').value = '';
                }
                calculateTotal();
            })
            .catch(err => {
                console.error(err);
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponMessage.innerText = 'Có lỗi xảy ra khi kiểm tra mã.';
                couponMessage.className = 'text-xs text-error font-bold mt-1';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
                calculateTotal();
            });
        });
    }

    function calculateTotal() {
        const hiddenDist = document.getElementById('hidden_distance_km');
        const distanceKm = hiddenDist ? parseFloat(hiddenDist.value) : 2.5;

        const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
        const weatherFee = hiddenWeatherFee ? parseFloat(hiddenWeatherFee.value) : 0;

        // Free shipping for orders >= 150,000
        const freeShip = subtotal >= 150000;

        // Shipping fee: 3000 VND per km (free if order >= 150,000)
        const distanceFee = freeShip ? 0 : Math.round(distanceKm * 3000);

        // Calculate dynamic discount if it's percent based
        if (discountPercent > 0) {
            discount = Math.round(subtotal * (discountPercent / 100));
            if (maxDiscountAmount && maxDiscountAmount > 0 && discount > maxDiscountAmount) {
                discount = maxDiscountAmount;
            }
            if (discount > subtotal) discount = subtotal;
            if (discountText) {
                discountText.innerText = '-' + discount.toLocaleString('vi-VN') + 'đ';
            }
        }

        // Calculate final total
        const total = Math.max(0, subtotal + distanceFee + (freeShip ? 0 : weatherFee) - discount);

        // Update UI
        if (shippingBaseText) shippingBaseText.innerText = '0đ';
        const distValEl = document.getElementById('summary-distance-km-val');
        if (distValEl) distValEl.innerText = distanceKm.toFixed(1);

        const freeShipRow = document.getElementById('summary-free-ship-row');
        const shippingDistRow = document.getElementById('summary-shipping-distance-row');

        if (distanceKm > 10) {
            if (orderBtn) {
                orderBtn.disabled = true;
                orderBtn.innerText = 'Chỉ giao trong 10km';
                orderBtn.classList.add('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600');
                orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary', 'bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
            }
            if (shippingDistanceText) shippingDistanceText.innerHTML = '<span class="text-error font-bold">Không hỗ trợ giao quá 10km</span>';
            if (totalText) totalText.innerHTML = '<span class="text-error font-bold">---</span>';
            // Reset weather fee - ẩn hàng phụ thu và reset giá trị cũ khi địa chỉ quá xa
            const weatherRowOver = document.getElementById('summary-weather-fee-row');
            if (weatherRowOver) weatherRowOver.classList.add('hidden');
            if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
        } else {
            if (orderBtn) {
                orderBtn.disabled = false;
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                orderBtn.classList.remove('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600', 'opacity-50');
                if (selectedPayment && selectedPayment.value === 'momo') {
                    orderBtn.innerText = 'Thanh toán qua MoMo';
                    orderBtn.classList.add('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                    orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                } else {
                    orderBtn.innerText = 'Đặt hàng (COD)';
                    orderBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                    orderBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                }
            }

            // Free ship logic
            if (freeShip) {
                if (freeShipRow) freeShipRow.classList.remove('hidden');
                if (shippingDistRow) shippingDistRow.classList.add('hidden');
                if (shippingDistanceText) shippingDistanceText.innerText = '0đ';
                // Ẩn phụ thu thời tiết khi miễn ship
                const weatherRow = document.getElementById('summary-weather-fee-row');
                if (weatherRow) weatherRow.classList.add('hidden');
                if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
            } else {
                if (freeShipRow) freeShipRow.classList.add('hidden');
                if (shippingDistRow) shippingDistRow.classList.remove('hidden');
                if (shippingDistanceText) shippingDistanceText.innerText = distanceFee.toLocaleString('vi-VN') + 'đ';

                const weatherRow = document.getElementById('summary-weather-fee-row');
                if (weatherFee > 0) {
                    if (weatherRow) weatherRow.classList.remove('hidden');
                    const weatherText = document.getElementById('summary-weather-fee-text');
                    if (weatherText) weatherText.innerText = '+' + weatherFee.toLocaleString('vi-VN') + 'đ';
                } else {
                    if (weatherRow) weatherRow.classList.add('hidden');
                }
            }

            if (totalText) totalText.innerText = total.toLocaleString('vi-VN') + 'đ';
        }
    }

    // Address list toggle
    const changeAddressBtn = document.getElementById('change-address-btn');
    const addressListPanel = document.getElementById('address-list-panel');
    if (changeAddressBtn && addressListPanel) {
        changeAddressBtn.addEventListener('click', () => {
            addressListPanel.classList.toggle('hidden');
        });
    }

    // Selecting alternative address
    const addressRadios = document.querySelectorAll('input[name="address_selector"]');
    const hiddenAddressIdInput = document.getElementById('selected_address_id');
    const activeAddressName = document.getElementById('active-address-name');
    const activeAddressPhone = document.getElementById('active-address-phone');
    const activeAddressDetails = document.getElementById('active-address-details');

    function updateDistanceForAddress(addressId) {
        if (!addressId) return;
        
        const distanceValText = document.getElementById('distance-calc-desc');
        if (distanceValText) distanceValText.innerText = 'Đang tính toán...';
        
        fetch(`/checkout/distance?address_id=${addressId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const distanceKm = data.distance_km;
                    const hiddenDist = document.getElementById('hidden_distance_km');
                    if (hiddenDist) {
                        hiddenDist.value = distanceKm;
                    }
                    
                    updateWeatherFeeForAddress(addressId, distanceKm);
                    
                    calculateTotal();
                    
                    const calcDesc = document.getElementById('distance-calc-desc');
                    if (calcDesc) {
                        if (data.is_mock) {
                            calcDesc.innerHTML = `<span style="color:#d97706; font-weight: 600;">⚠️ ${data.message}</span><br>Phí vận chuyển: 3.000đ / km.`;
                        } else {
                            calcDesc.innerHTML = `<span style="color:#15803d; font-weight: 600;">✅ Khoảng cách được tính thực tế bằng OpenRouteService API.</span><br>Phí vận chuyển: 3.000đ / km.`;
                        }
                    }
                }
            })
            .catch(err => {
                console.error('Error fetching distance:', err);
                const errValText = document.getElementById('distance-calc-desc');
                if (errValText) errValText.innerText = 'Lỗi kết nối';
            });
    }

    function updateWeatherFeeForAddress(addressId, distanceKm) {
        if (!addressId) return;

        fetch(`/checkout/weather-fee?address_id=${addressId}&distance_km=${distanceKm}&subtotal=${subtotal}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const fee = data.fee;
                    const condition = data.condition;

                    const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
                    if (hiddenWeatherFee) hiddenWeatherFee.value = fee;

                    const conditionValEl = document.getElementById('summary-weather-condition-val');
                    if (conditionValEl) conditionValEl.innerText = condition;

                    calculateTotal();
                }
            })
            .catch(err => {
                console.error('Error fetching weather fee:', err);
            });
    }

    // Run initial calculate and fetch initial address distance
    calculateTotal();
    if (hiddenAddressIdInput && hiddenAddressIdInput.value) {
        updateDistanceForAddress(hiddenAddressIdInput.value);
    }

    addressRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (hiddenAddressIdInput) hiddenAddressIdInput.value = radio.value;
            if (activeAddressName) activeAddressName.innerText = radio.dataset.fullname;
            if (activeAddressPhone) activeAddressPhone.innerText = radio.dataset.phone;
            if (activeAddressDetails) activeAddressDetails.innerText = radio.dataset.address;
            
            // Fetch real distance on selection change, which then fetches weather
            updateDistanceForAddress(radio.value);
            
            // Highlight selected address card
            addressRadios.forEach(r => {
                const card = r.closest('.address-card');
                if (card) {
                    if (r.checked) {
                        card.classList.add('border-primary', 'bg-primary-container/5');
                        card.classList.remove('border-outline-variant');
                    } else {
                        card.classList.remove('border-primary', 'bg-primary-container/5');
                        card.classList.add('border-outline-variant');
                    }
                }
            });
        });
    });
});

async function deleteAddressCheckout(id) {
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
        } else {
            alert(json.message || "Có lỗi xảy ra");
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}
