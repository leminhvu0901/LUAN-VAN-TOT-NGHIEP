// ==========================================
// 1. HIỆU ỨNG GIAO DIỆN (Micro-interactions)
// Dành cho cả điện thoại (Mobile) và máy tính (Desktop)
// ==========================================

// Tìm tất cả các ô nhập liệu (input) ngoại trừ loại file, hidden, và checkbox
document.querySelectorAll('input:not([type="file"]):not([type="hidden"]):not([type="checkbox"])').forEach(input => {
    // Khi người dùng bấm (focus) vào ô nhập liệu
    input.addEventListener('focus', () => {
        if (input.parentElement && input.parentElement.classList) {
            // Phóng to nhẹ khung chứa ô input lên 1% (scale-[1.01]) để tạo cảm giác nổi lên
            input.parentElement.classList.add('scale-[1.01]', 'leaf-indicator');
            input.parentElement.style.transition = 'transform 0.2s ease'; // Hiệu ứng chuyển động mượt 0.2s
        }
    });
    // Khi người dùng bấm ra ngoài (blur) khỏi ô nhập liệu
    input.addEventListener('blur', () => {
        if (input.parentElement && input.parentElement.classList) {
            // Trả lại kích thước bình thường
            input.parentElement.classList.remove('scale-[1.01]', 'leaf-indicator');
        }
    });
});

// Tìm tất cả các nút bấm chính (nút submit, nút có màu xanh chủ đạo)
const primaryBtns = document.querySelectorAll('button[type="submit"], button.bg-primary-container, button.bg-primary');
primaryBtns.forEach(btn => {
    // Khi nhấn chuột xuống (mousedown) -> Nút lún xuống (scale-95 tức là thu nhỏ còn 95%)
    btn.addEventListener('mousedown', () => btn.classList.add('scale-95'));
    // Khi nhả chuột ra (mouseup) -> Nút nảy lên lại
    btn.addEventListener('mouseup', () => btn.classList.remove('scale-95'));
    // Khi rê chuột ra khỏi nút mà chưa nhả -> Nút nảy lên lại
    btn.addEventListener('mouseleave', () => btn.classList.remove('scale-95'));
    
    // Tương tự cho các thao tác chạm trên màn hình cảm ứng (Mobile)
    btn.addEventListener('touchstart', () => btn.classList.add('scale-95'), { passive: true });
    btn.addEventListener('touchend', () => btn.classList.remove('scale-95'), { passive: true });
});

// ==========================================
// 2. LOGIC QUẢN LÝ ĐỊA CHỈ (Sổ địa chỉ)
// ==========================================

// Biến lưu trữ trạng thái hiện tại đang chọn Tỉnh/Huyện/Xã nào
let locState = { 
    province: null, district: null, ward: null, 
    province_name: '', district_name: '', ward_name: '', 
    currentTab: 'province', activePrefix: 'desk' 
};

// Hàm gọi API lấy danh sách toàn bộ Tỉnh/Thành Phố ở Việt Nam
async function fetchProvinces(prefix) {
    renderLocLoading(prefix); // Hiện chữ "Đang tải..."
    try {
        const res = await fetch('https://provinces.open-api.vn/api/v2/p/');
        const data = await res.json();
        renderLocItems(data, 'province', prefix); // Vẽ danh sách tỉnh ra màn hình
    } catch (e) { console.error(e); }
}

// Hàm gọi API lấy danh sách Phường/Xã dựa trên Tỉnh đã chọn
async function fetchWards(provinceCode, prefix) {
    renderLocLoading(prefix);
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/v2/p/${provinceCode}?depth=2`);
        const data = await res.json();
        // API này trả về Quận/Huyện ở cấp 2. Do Backend của ta gom Quận và Xã chung 1 cột, ta tạm mượn wards của API.
        renderLocItems(data.wards, 'ward', prefix); 
    } catch (e) { console.error(e); }
}

// Hàm hiển thị chữ "Đang tải..." trong lúc chờ API trả kết quả
function renderLocLoading(prefix) {
    const list = document.getElementById(`${prefix}_locList`);
    if (list) list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Đang tải...</div>';
}

// Hàm vẽ danh sách các Tỉnh/Xã ra giao diện HTML
function renderLocItems(items, type, prefix) {
    const list = document.getElementById(`${prefix}_locList`);
    if (!list) return;
    list.innerHTML = ''; // Xóa chữ Đang tải
    
    // Nếu API lỗi không có dữ liệu
    if (!items || items.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Không có dữ liệu</div>';
        return;
    }
    
    // Duyệt qua từng mục (Tỉnh/Xã) và tạo thẻ <div> để khách hàng click
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-surface-container-lowest cursor-pointer transition-colors border-b border-outline-variant/50 last:border-0';
        div.textContent = item.name;
        div.onclick = () => selectLocItem(item, type, prefix); // Gắn sự kiện khi click chọn
        list.appendChild(div);
    });
}

// Hàm xử lý khi người dùng Click chọn 1 Tỉnh hoặc Xã
function selectLocItem(item, type, prefix) {
    if (type === 'province') {
        // Nếu vừa chọn Tỉnh -> Lưu tên tỉnh, Xóa trắng thông tin Huyện/Xã
        locState.province = item.code; 
        locState.province_name = item.name;
        locState.ward = null; 
        locState.ward_name = '';
        
        document.getElementById(`${prefix}_addr_province`).value = item.name;
        document.getElementById(`${prefix}_addr_district`).value = '';
        document.getElementById(`${prefix}_addr_ward`).value = '';
        
        // Tự động chuyển sang Tab chọn Phường/Xã
        switchLocTab(prefix, 'ward');
    } else if (type === 'ward') {
        // Nếu vừa chọn Xã -> Lưu tên Xã
        locState.ward = item.code; 
        locState.ward_name = item.name;
        document.getElementById(`${prefix}_addr_ward`).value = item.name;
        
        // Cú lừa (Trick) Backend: Lưu tên Huyện giống y hệt tên Xã vì DB hiện tại gom chung
        document.getElementById(`${prefix}_addr_district`).value = item.name;
        
        // Cập nhật dòng text hiển thị bên ngoài (VD: Hà Nội, Phường A)
        updateLocPickerText(prefix);
        // Ẩn bảng chọn đi
        document.getElementById(`${prefix}_locPanel`).style.display = 'none';
        // Tự động tìm tọa độ trên bản đồ
        geocodeAndUpdateMap(prefix);
    }
}

// Hàm lấy địa chỉ bằng chữ, gửi lên vệ tinh (OpenStreetMap) để lấy tọa độ cắm cờ
function geocodeAndUpdateMap(prefix) {
    const province = document.getElementById(`${prefix}_addr_province`).value;
    const district = document.getElementById(`${prefix}_addr_district`).value;
    const ward = document.getElementById(`${prefix}_addr_ward`).value;
    const specific = document.getElementById(`${prefix}_addr_specific`).value;

    let parts = [];
    if (specific) parts.push(specific); // VD: 123 Đường A
    if (ward) parts.push(ward);         // VD: Phường B
    if (province) parts.push(province); // VD: Hà Nội

    // Nếu có địa chỉ thì nối lại thành chuỗi "123 Đường A, Phường B, Hà Nội"
    if (parts.length > 0) {
        const addressStr = parts.join(', ');
        geocodeAddress(addressStr).then(coords => {
            if (coords) {
                // Có tọa độ thì khởi tạo/di chuyển bản đồ Leaflet tới đó
                initLeafletMap(prefix, coords.lat, coords.lng);
            }
        });
    }
}

// Bắt sự kiện: Khi khách gõ xong ô "Địa chỉ cụ thể" và bấm ra ngoài (blur) hoặc bấm Enter, tự động cập nhật bản đồ
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

// Hàm hiển thị chữ "Tỉnh A, Phường B" ra ngoài ô input giả
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

// Hàm chuyển đổi giữa Tab chọn "Tỉnh/Thành phố" và Tab chọn "Quận/Huyện/Phường"
function switchLocTab(prefix, tab) {
    locState.currentTab = tab;

    // Đổi màu Tab (Highlight Tab đang chọn)
    ['province', 'ward'].forEach(t => {
        const el = document.getElementById(`${prefix}_tab_${t}`);
        if (el) {
            if (t === tab) el.classList.add('bg-surface-container-low', 'text-primary');
            else el.classList.remove('bg-surface-container-low', 'text-primary');
        }
    });

    // Gọi API tương ứng với Tab được chọn
    if (tab === 'province') {
        fetchProvinces(prefix);
    } else if (tab === 'ward') {
        // Chỉ cho phép chọn Phường/Xã nếu ĐÃ CHỌN Tỉnh
        if (locState.province) fetchWards(locState.province, prefix);
        else document.getElementById(`${prefix}_locList`).innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Tỉnh/Thành Phố trước</div>';
    }
}

// Hàm Tắt/Bật bảng danh sách Tỉnh/Xã khi click vào ô nhập
function toggleLocPanel(prefix) {
    const panel = document.getElementById(`${prefix}_locPanel`);
    if (!panel) return;
    locState.activePrefix = prefix;
    
    if (panel.style.display === 'block') {
        panel.style.display = 'none'; // Đang bật thì tắt
    } else {
        // Tắt tất cả các bảng khác trước
        document.querySelectorAll('.loc-panel').forEach(p => p.style.display = 'none');
        panel.style.display = 'block'; // Hiện bảng lên
        
        // Mở đúng Tab cần thiết
        if (!locState.province) switchLocTab(prefix, 'province');
        else if (!locState.ward) switchLocTab(prefix, 'ward');
        else switchLocTab(prefix, 'province');
    }
}

// Đóng bảng Tỉnh/Xã nếu click chuột ra bên ngoài vùng bảng
document.addEventListener('click', function (e) {
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


// ==========================================
// 3. LOGIC BẢN ĐỒ VÀ TỌA ĐỘ (Leaflet Map)
// ==========================================

let leafletMapDesk = null;
let leafletMarkerDesk = null;
let leafletMapMob = null;
let leafletMarkerMob = null;

// Hàm khởi tạo và vẽ bản đồ lên khung
function initLeafletMap(prefix, lat, lng) {
    // Tọa độ mặc định (Trường ĐH Sài Gòn/STU...) nếu không truyền vào
    const defaultLat = lat || 10.73809;
    const defaultLng = lng || 106.67812;

    // Cập nhật Form ẩn để gửi lên Server
    document.getElementById(`${prefix}_addr_lat`).value = defaultLat;
    document.getElementById(`${prefix}_addr_lng`).value = defaultLng;

    let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
    let marker = prefix === 'desk' ? leafletMarkerDesk : leafletMarkerMob;
    const mapId = `${prefix}_leafletMap`;

    // Nếu bản đồ ĐÃ khởi tạo rồi, chỉ cần dời cây cờ đi chỗ khác
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

    // Nếu chưa khởi tạo, tạo mới bản đồ Leaflet zoom mức 15
    map = L.map(mapId).setView([defaultLat, defaultLng], 15);

    // Dùng hình ảnh bản đồ miễn phí của OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Đặt cây cờ (marker) lên vị trí và cho phép kéo thả (draggable: true)
    marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
    setupMarkerEvents(prefix, marker);

    if (prefix === 'desk') {
        leafletMapDesk = map;
        leafletMarkerDesk = marker;
    } else {
        leafletMapMob = map;
        leafletMarkerMob = marker;
    }

    // Khi click vào vị trí bất kỳ trên bản đồ -> Dời cờ tới đó và dịch ngược ra địa chỉ chữ
    map.on('click', function (e) {
        const newLat = e.latlng.lat;
        const newLng = e.latlng.lng;
        marker.setLatLng([newLat, newLng]);
        updateCoordinates(prefix, newLat, newLng);
        reverseGeocode(prefix, newLat, newLng); // Dịch ngược tọa độ ra chữ
    });
}

// Bắt sự kiện khi kéo thả cờ xong (nhả chuột ra) -> Dịch ngược ra chữ
function setupMarkerEvents(prefix, marker) {
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateCoordinates(prefix, position.lat, position.lng);
        reverseGeocode(prefix, position.lat, position.lng);
    });
}

// Ghi tọa độ vào thẻ input ẩn để gửi lên Laravel lưu Database
function updateCoordinates(prefix, lat, lng) {
    document.getElementById(`${prefix}_addr_lat`).value = lat;
    document.getElementById(`${prefix}_addr_lng`).value = lng;
}

// Geocode: Truyền chữ (VD: Hà Nội) lên API -> Nhận về Tọa độ GPS
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

// Reverse Geocode: Truyền Tọa độ GPS lên API -> Nhận về Số nhà, Phường, Tỉnh bằng chữ
async function reverseGeocode(prefix, lat, lng) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi&email=test@example.com`);
        const data = await res.json();
        if (data && data.address) {
            const addr = data.address;
            
            // Xử lý lấy tên Tỉnh
            const province = addr.state || addr.province || addr.city || '';
            
            // Xử lý lấy tên Huyện (Quận)
            let district = addr.county || addr.city_district || addr.suburb || addr.town || '';
            if (!district && addr.city && addr.city !== province) {
                district = addr.city;
            }
            
            // Xử lý lấy tên Phường (Xã)
            const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

            // Xử lý lấy số nhà / tên đường (Địa chỉ cụ thể)
            let specific = data.display_name;
            if (addr.road) {
                specific = (addr.house_number ? addr.house_number + ' ' : '') + addr.road;
            }

            // Đổ lại dữ liệu chữ vào các ô input của Form để khách khỏi mất công gõ
            document.getElementById(`${prefix}_addr_province`).value = province;
            const combinedWard = [district, ward].filter(Boolean).join(', '); // Ghép Huyện và Xã thành 1 chuỗi
            document.getElementById(`${prefix}_addr_ward`).value = combinedWard;
            document.getElementById(`${prefix}_addr_district`).value = combinedWard; // Trick Backend
            document.getElementById(`${prefix}_addr_specific`).value = specific;

            // Cập nhật lại Trạng thái biến tạm
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

// Nút "Lấy vị trí hiện tại" (Dùng định vị GPS của điện thoại/máy tính)
async function getCurrentLocation(prefix, btn) {
    const currentProvince = document.getElementById(`${prefix}_addr_province`).value;
    const currentSpecific = document.getElementById(`${prefix}_addr_specific`).value.trim();

    // Nếu khách đang gõ dở địa chỉ tay thì hỏi xác nhận kẻo mất dữ liệu
    if (currentProvince || currentSpecific) {
        if (!confirm("Bạn đã nhập địa chỉ thủ công. Bạn có chắc muốn dùng địa chỉ GPS để thay thế không?")) return;
    }

    const textSpan = document.getElementById(`${prefix}_gps-btn-text`);
    const originalText = textSpan.innerText;

    // Kiểm tra xem trình duyệt có hỗ trợ GPS không
    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
        return;
    }

    btn.disabled = true;
    textSpan.innerText = "Đang lấy vị trí...";
    btn.style.opacity = '0.5';
    btn.style.cursor = 'wait';

    // Bật yêu cầu quyền truy cập GPS
    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        try {
            // Lấy được tọa độ thì đem đi dịch ngược ra chữ (Reverse Geocode)
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=vi&email=test@example.com`);
            const data = await res.json();

            if (data && data.display_name) {
                // Điền chữ vào Form y hệt hàm reverseGeocode bên trên
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

                // Vẽ bản đồ ở đúng vị trí GPS vừa lấy
                initLeafletMap(prefix, lat, lon);
                let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
                if (map) map.invalidateSize(); // Reset kích thước map tránh lỗi hiển thị xám 1 góc

                alert("Đã tự động điền địa chỉ dựa trên GPS!");
            } else {
                alert("Không thể chuyển đổi tọa độ thành địa chỉ.");
            }
        } catch (error) {
            console.error(error);
            alert("Có lỗi xảy ra khi gọi API bản đồ.");
        } finally {
            // Dù thành công hay lỗi cũng nhả nút ra
            btn.disabled = false;
            textSpan.innerText = originalText;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        }
    }, (error) => {
        // Xử lý các lỗi từ chối cấp quyền định vị
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
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }); // Yêu cầu GPS độ chính xác cao nhất
}

// ==========================================
// 4. LOGIC MỞ FORM ĐỊA CHỈ & GỌI API LƯU DATA
// ==========================================

// Mở Form "Thêm địa chỉ mới" (Dọn sạch form trắng bóc)
function openAddressModal(prefix) {
    const isDesktop = prefix === 'desk';

    // Ẩn danh sách địa chỉ, Hiện Form nhập liệu
    if (isDesktop) {
        document.getElementById('desktop-address-content').classList.add('hidden');
        document.getElementById('desktop-address-form-content').classList.remove('hidden');
    } else {
        document.getElementById('mobile-address-content').classList.add('hidden');
        document.getElementById('mobile-address-form-content').classList.remove('hidden');
    }

    // Reset lại text thành Thêm mới
    document.getElementById(`${prefix}_addressModalTitle`).textContent = 'Thêm địa chỉ mới';
    document.getElementById(`${prefix}_addressModalTitleBc`).textContent = 'Thêm địa chỉ mới';
    
    // Dọn sạch Form (để chuỗi rỗng)
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

    setAddrType(prefix, 'home'); // Loại địa chỉ mặc định là Nhà riêng
    document.getElementById(`${prefix}_addr_default`).checked = false; // Mặc định không check

    setTimeout(() => {
        initLeafletMap(prefix);
        let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
        if (map) {
            map.invalidateSize(); // Fix lỗi Leaflet xám khung khi nằm trong Modal ẩn
        }
    }, 200);
}

// Mở Form "Cập nhật địa chỉ" (Đổ dữ liệu cũ vào Form để sửa)
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
    
    // Bơm dữ liệu từ cục addr (json) vào các thẻ input
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

    setAddrType(prefix, addr.type); // Nút nhà riêng/công ty
    document.getElementById(`${prefix}_addr_default`).checked = addr.is_default == 1;

    setTimeout(() => {
        // Vẽ bản đồ với tọa độ cũ
        if (lat && lng) {
            initLeafletMap(prefix, lat, lng);
            let map = prefix === 'desk' ? leafletMapDesk : leafletMapMob;
            if (map) map.invalidateSize();
        } else {
            // Nếu hồi xưa lưu địa chỉ mà không có tọa độ GPS -> Tự động Geocode chữ thành GPS
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

// Bấm nút Quay lại -> Tắt Form nhập liệu, mở lại danh sách địa chỉ
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

// Đổi màu Nút Loại Địa chỉ (Nhà / Công ty / Người thân)
function setAddrType(prefix, type) {
    document.getElementById(`${prefix}_addr_type`).value = type;
    ['home', 'office', 'family'].forEach(t => {
        const btn = document.getElementById(`${prefix}_btnType${t.charAt(0).toUpperCase() + t.slice(1)}`);
        if (btn) {
            if (t === type) {
                // Sáng màu nút đang chọn
                btn.classList.add('bg-primary/10', 'border-primary', 'text-primary');
                btn.classList.remove('bg-surface-container-lowest', 'border-outline-variant', 'text-on-surface-variant');
            } else {
                // Tắt màu nút không chọn
                btn.classList.remove('bg-primary/10', 'border-primary', 'text-primary');
                btn.classList.add('bg-surface-container-lowest', 'border-outline-variant', 'text-on-surface-variant');
            }
        }
    });
}

// Gọi API POST để Lưu địa chỉ (Lưu mới hoặc Sửa)
async function saveAddress(prefix) {
    const id = document.getElementById(`${prefix}_addr_id`).value;
    
    // Gom dữ liệu từ Form
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

    // Kiểm tra tính hợp lệ cơ bản (Client-side validation)
    if (!data.fullname || !data.phone || !data.province || !data.district || !data.ward || !data.specific_address) {
        alert("Vui lòng điền đầy đủ thông tin.");
        return;
    }

    // Nếu id có nghĩa là Sửa, không có là Tạo mới
    const url = id ? `/profile/address/${id}` : '/profile/address';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload(); // Tải lại trang khi lưu thành công
        } else {
            alert(json.message || "Có lỗi xảy ra");
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}

// Gọi API Xóa 1 địa chỉ
async function deleteAddress(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) return; // Hiện hộp thoại xác nhận
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

// Gọi API thiết lập "Mặc định" cho 1 địa chỉ
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

// ==========================================
// 5. CÔNG CỤ CẮT ẢNH ĐẠI DIỆN (Cropper.js)
// ==========================================
let cropper;

// Khi khách bấm "Đổi ảnh đại diện" và chọn 1 tấm hình từ máy
function previewAvatar(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        // Bắt lỗi file quá lớn (Vượt 5 Megabytes)
        if (file.size > 5 * 1024 * 1024) {
            alert('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
            input.value = '';
            return;
        }

        // Đọc tấm ảnh vào bộ nhớ tạm để hiển thị nháp (chưa úp lên mạng)
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('imageToCrop').src = e.target.result;
            // Hiện Popup cắt ảnh
            document.getElementById('cropperModal').style.display = 'flex';

            // Xóa bộ cắt cũ nếu có
            if (cropper) cropper.destroy();
            // Khởi tạo thư viện cắt ảnh Cropper.js
            cropper = new Cropper(document.getElementById('imageToCrop'), {
                aspectRatio: 1, // Bắt buộc cắt ra hình Vuông (tỷ lệ 1:1)
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true, // Hiện lưới kẻ Caro
                center: true, // Hiện chữ thập ở giữa
                highlight: false,
                cropBoxMovable: true, // Cho phép kéo khung cắt
                cropBoxResizable: true, // Cho phép phóng to thu nhỏ khung
                toggleDragModeOnDblclick: false,
            });
        }
        reader.readAsDataURL(file); // Bắt đầu đọc file ảnh
    }
}

// Đóng Popup cắt ảnh
function closeCropperModal() {
    document.getElementById('cropperModal').style.display = 'none';
    if (cropper) {
        cropper.destroy(); // Giải phóng bộ nhớ
        cropper = null;
    }
    document.getElementById('avatarInput').value = '';
}

// Bấm nút "Lưu (Cắt)" sau khi đã căn chỉnh khuôn mặt vừa ý
function cropImage() {
    if (!cropper) return;
    
    // Vẽ phần nằm trong ô vuông ra một cái Khung Tranh ảo (Canvas) cỡ 300x300 pixel
    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    // Chuyển bức tranh ảo đó thành 1 đoạn mã Base64 (mã chữ cái đại diện cho hình ảnh jpeg, chất lượng 90%)
    const base64Image = canvas.toDataURL('image/jpeg', 0.9);
    
    // Gắn hình vừa cắt ra ngoài màn hình Hồ sơ để khách xem thử
    if (document.getElementById('avatarPreview')) {
        document.getElementById('avatarPreview').src = base64Image;
    }
    if (document.getElementById('avatarPreviewMobile')) {
        document.getElementById('avatarPreviewMobile').src = base64Image;
    }
    
    // Nhét nguyên đoạn mã Base64 này vào một thẻ input ẩn (hidden). 
    // Tí nữa bấm nút Cập Nhật Hồ Sơ, mã này sẽ bay lên Server thay vì cả 1 cục file ảnh to.
    document.getElementById('croppedAvatarInput').value = base64Image;
    
    closeCropperModal(); // Đóng popup
}

// ==========================================
// 6. CHUYỂN TAB VÀ ĐỔI MẬT KHẨU
// ==========================================

// Hàm điều khiển ẩn/hiện các khung nội dung (Profile, Password, Address)
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

    // 1. Tắt/ẩn toàn bộ các khu vực nội dung
    if (deskProfile) deskProfile.classList.add('hidden');
    if (deskPass) deskPass.classList.add('hidden');
    if (deskAddress) deskAddress.classList.add('hidden');

    if (mobProfile) mobProfile.classList.add('hidden');
    if (mobPass) mobPass.classList.add('hidden');
    if (mobAddress) mobAddress.classList.add('hidden');

    // Hàm trả nút về màu nhạt (Chưa được chọn)
    function resetLink(link) {
        if (!link) return;
        link.className = "text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md";
        const icon = link.querySelector('.material-symbols-outlined');
        if (icon) icon.style.fontVariationSettings = "";
    }

    // Hàm tô đậm màu nút (Đang được chọn)
    function activeLink(link) {
        if (!link) return;
        link.className = "bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md";
        const icon = link.querySelector('.material-symbols-outlined');
        if (icon) icon.style.fontVariationSettings = "'FILL' 1"; // Hiệu ứng làm icon Material đen đặc
    }

    // 2. Reset màu toàn bộ nút
    resetLink(profileLink);
    resetLink(passwordLink);
    resetLink(addressLink);

    // 3. Mở khung và tô màu nút ứng với tên Tab khách bấm
    if (tab === 'password') {
        if (deskPass) deskPass.classList.remove('hidden');
        if (mobPass) mobPass.classList.remove('hidden');
        activeLink(passwordLink);
        if (headerTitle) headerTitle.textContent = "Đổi mật khẩu";
        window.location.hash = 'password'; // Cập nhật thanh địa chỉ thành xyz.com/profile#password
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

    // Logic nút "Quay lại" (Back) trên điện thoại
    if (tab !== 'profile') {
        if (backBtn) {
            backBtn.href = "#profile";
            backBtn.onclick = function (e) {
                e.preventDefault();
                showTab('profile'); // Bấm quay lại thì về màn Hồ sơ
            };
        }
    } else {
        if (backBtn) {
            backBtn.href = backBtn.dataset.prevUrl || 'javascript:history.back()';
            backBtn.onclick = null;
        }
    }
}

// Khi web vừa tải xong DOM HTML
document.addEventListener('DOMContentLoaded', function () {
    const backBtn = document.getElementById('mobile-back-btn');
    if (backBtn) {
        backBtn.dataset.prevUrl = backBtn.getAttribute('href');
    }

    // Đọc cái Hash (#) trên thanh URL để biết phải tự động mở Tab nào
    // VD: người dùng vào bằng link /profile#password thì tự mở Tab Đổi Mật Khẩu luôn
    if (window.location.hash === '#password' || window.location.hash === '#change-password') {
        showTab('password');
    } else if (window.location.hash === '#address') {
        showTab('address');
    } else {
        showTab('profile');
    }

    // Logic nút Mắt (Hiển thị / Giấu mật khẩu thành dấu sao)
    const toggleButtons = document.querySelectorAll('.toggle-password-visibility');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const iconSpan = this.querySelector('.material-symbols-outlined');
            if (input.type === 'password') {
                input.type = 'text'; // Biến thẻ input mật khẩu thành text thường để nhìn thấy chữ
                if (iconSpan) iconSpan.textContent = 'visibility_off'; // Đổi icon cái mắt bị gạch chéo
            } else {
                input.type = 'password';
                if (iconSpan) iconSpan.textContent = 'visibility';
            }
        });
    });

    // ==========================================
    // 7. KIỂM TRA ĐỘ MẠNH MẬT KHẨU (Real-time Validation)
    // ==========================================
    const newPassDesk = document.getElementById('new_password_desk');
    const newPassMob = document.getElementById('new_password_mob');
    const confirmPassDesk = document.getElementById('new_password_confirmation_desk');
    const confirmPassMob = document.getElementById('new_password_confirmation_mob');

    // Hàm chấm điểm Mật Khẩu mỗi khi gõ phím
    function checkPasswordStrength() {
        const val = this.value;
        // Đồng bộ dữ liệu giữa Form của Mobile và Form của Desktop
        if (this === newPassDesk && newPassMob) newPassMob.value = val;
        if (this === newPassMob && newPassDesk) newPassDesk.value = val;

        const confirmVal = (this === newPassDesk || this === newPassMob)
            ? (confirmPassDesk ? confirmPassDesk.value : '')
            : this.value;

        if (this === confirmPassDesk && confirmPassMob) confirmPassMob.value = this.value;
        if (this === confirmPassMob && confirmPassDesk) confirmPassDesk.value = this.value;

        const password = newPassDesk ? newPassDesk.value : (newPassMob ? newPassMob.value : '');

        // CÁC LUẬT KIỂM TRA MẬT KHẨU
        const hasLength = password.length >= 8; // Đạt độ dài 8 ký tự
        const hasCase = /[a-z]/.test(password) && /[A-Z]/.test(password); // Có cả chữ Hoa và chữ thường
        const hasNumberOrSymbol = /[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password); // Có Số HOẶC Ký tự đặc biệt (!@#)
        const matches = password === confirmVal && password.length > 0; // 2 ô mật khẩu có khớp nhau không?

        // Hệ thống Tính Điểm (Tối đa 3 điểm)
        let score = 0;
        if (password.length > 0) {
            if (hasLength) score++;
            if (hasCase) score++;
            if (hasNumberOrSymbol) score++;
        }

        // Cập nhật đổi màu Xanh/Xám cho mấy cái nút tick tròn bên dưới
        updateIndicator('req-length', hasLength);
        updateIndicator('req-case', hasCase);
        updateIndicator('req-number', hasNumberOrSymbol);
        updateIndicator('req-match', matches);

        // Đổ màu thanh đo (Bar) theo số điểm vừa tính
        updateStrengthMeter(score, password.length > 0);
    }

    // Hàm bật/tắt CSS cho các tick xanh (Báo hiệu đạt yêu cầu mật khẩu)
    function updateIndicator(idPrefix, isValid) {
        ['desk', 'mob'].forEach(suffix => {
            const el = document.getElementById(`${idPrefix}-${suffix}`);
            if (!el) return;
            const icon = el.querySelector('.material-symbols-outlined');
            if (isValid) {
                // Nếu thỏa mãn -> Chuyển màu Primary (Xanh ngọc) và thay icon thành hình tròn CÓ tick
                el.classList.remove('text-on-surface-variant');
                el.classList.add('text-primary');
                if (icon) {
                    icon.textContent = 'check_circle';
                    icon.classList.remove('text-outline');
                    icon.classList.add('text-primary');
                }
            } else {
                // Nếu không thỏa -> Xám xịt và icon hình tròn RỖNG ruột
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

    // Đổ màu và kéo giãn Thanh Báo Độ Mạnh theo Điểm (Score)
    function updateStrengthMeter(score, hasInput) {
        const labels = {
            0: { text: 'Yếu', colorClass: 'text-error', barClass: 'bg-error', width: '33%' },        // 0-1 điểm: Yếu (Màu đỏ)
            1: { text: 'Yếu', colorClass: 'text-error', barClass: 'bg-error', width: '33%' },        
            2: { text: 'Trung bình', colorClass: 'text-amber-500', barClass: 'bg-amber-500', width: '66%' }, // 2 điểm: Trung Bình (Màu cam)
            3: { text: 'Mạnh', colorClass: 'text-primary', barClass: 'bg-primary', width: '100%' }   // 3 điểm: Mạnh (Màu xanh full cây)
        };

        const config = hasInput ? labels[score] : { text: 'Chưa nhập', colorClass: 'text-outline', barClass: 'bg-outline', width: '0%' };

        ['desk', 'mob'].forEach(suffix => {
            const labelEl = document.getElementById(`strength-label-${suffix}`); // Dòng chữ "Yếu/Mạnh"
            const barEl = document.getElementById(`strength-bar-${suffix}`); // Cây thanh màu
            if (labelEl) {
                labelEl.textContent = config.text;
                labelEl.className = `font-bold ${config.colorClass}`;
            }
            if (barEl) {
                barEl.style.width = config.width; // Kéo giãn độ rộng thanh (33%, 66%, 100%)
                barEl.classList.remove('bg-outline', 'bg-error', 'bg-amber-500', 'bg-primary'); // Xóa màu cũ
                barEl.classList.add(config.barClass); // Đắp màu mới
            }
        });
    }

    // Gắn sự kiện "input" (Gõ chữ) vào các ô điền mật khẩu
    [newPassDesk, newPassMob, confirmPassDesk, confirmPassMob].forEach(input => {
        if (input) {
            input.addEventListener('input', checkPasswordStrength);
        }
    });
});

// Cho phép các sự kiện onClick từ HTML (như href hoặc onclick) có thể gọi được hàm showTab
window.showTab = showTab;
