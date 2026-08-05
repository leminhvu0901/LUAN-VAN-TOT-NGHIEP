// ============================================================
// checkout.js — Toàn bộ logic trang Thanh toán (Checkout)
// Bao gồm: bản đồ địa chỉ, geocode, mã giảm giá, điểm thưởng,
// tính phí ship, chọn phương thức thanh toán, đặt hàng.
// ============================================================

let map;
let marker;

// Dữ liệu Tỉnh/Thành và Phường/Xã từ server
let provincesData = null;
let provincesLoading = false;
let wardsDataByProvince = {};
let wardsLoading = false;

// Dữ liệu cho 2 combobox có tìm kiếm. Select ẩn vẫn giữ code hành chính chính thức;
// ô search chỉ đảm nhiệm hiển thị và lọc tên (kể cả khi khách gõ không dấu).
const areaSearchItems = { province: [], ward: [] };

// Các biến trạng thái chọn vị trí, bản đồ và bộ đếm thời gian
let locationMethod = 'map';
let areaUserSelected = false;
let specificUserEdited = false;
let pendingMapLatLng = null;
let mapReverseTimer = null;

// Đọc key Geoapify đã truyền từ Blade qua window.checkoutConfig — đọc lại mỗi lần gọi (không cache
// vào hằng số ở module scope) vì script này có thể chạy trước khi checkoutConfig kịp gán.
function geoapifyKey() {
    return (window.checkoutConfig && window.checkoutConfig.geoapifyKey) || '';
}

// Cập nhật badge trạng thái xác định vị trí (mục 7).
function setLocStatus(state, extraText) {
    const iconEl = document.getElementById('locStatusIcon');
    const textEl = document.getElementById('locStatusText');
    const boxEl = document.getElementById('locStatus');
    if (!iconEl || !textEl || !boxEl) return;

    const map = {
        idle: ['location_searching', 'Chưa xác định vị trí', 'text-on-surface-variant'],
        manual: ['edit_location_alt', 'Nhập đầy đủ khu vực + địa chỉ cụ thể — hệ thống sẽ tự ghim vị trí lên bản đồ để bạn kiểm tra', 'text-on-surface-variant'],
        locating: ['pending', 'Đang xác định vị trí...', 'text-amber-600'],
        ok: ['check_circle', 'Đã xác định vị trí', 'text-primary'],
        notfound: ['error', 'Không tìm thấy địa chỉ', 'text-error'],
        outofrange: ['wrong_location', 'Ngoài phạm vi giao hàng', 'text-error'],
    };
    const cfg = map[state] || map.idle;
    iconEl.textContent = cfg[0];
    textEl.textContent = extraText || cfg[1];
    boxEl.className = 'mb-4 flex items-center gap-2 text-sm font-medium rounded-xl px-3 py-2.5 bg-surface-container-lowest ' + cfg[2];
}

// Chuyển đổi phương thức tìm vị trí (GPS, Bản đồ, hoặc Nhập tay)
function setLocationMethod(method) {
    if (!['gps', 'map', 'manual'].includes(method)) method = 'map';
    const methodChanged = method !== locationMethod;
    locationMethod = method;
    if (methodChanged) {
        areaUserSelected = false;
        specificUserEdited = false;
    }
    const hidden = document.getElementById('addr_location_method');
    if (hidden) hidden.value = method;

    // Segmented control active state
    document.querySelectorAll('.loc-method-btn').forEach(function (btn) {
        const active = btn.dataset.method === method;
        btn.classList.toggle('bg-primary', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('shadow-sm', active);
        btn.classList.toggle('text-on-surface-variant', !active);
    });

    const gpsBlock = document.getElementById('gpsBlock');
    const mapColumn = document.getElementById('mapColumn');
    const mapHint = document.getElementById('mapHint');
    const manualMapHint = document.getElementById('manualMapHint');
    const grid = document.getElementById('addressGrid');
    const confirmBtn = document.getElementById('btnConfirmMapLocation');

    if (gpsBlock) gpsBlock.classList.toggle('hidden', method !== 'gps');
    if (mapHint) mapHint.classList.toggle('hidden', method !== 'map');
    if (manualMapHint) manualMapHint.classList.toggle('hidden', method !== 'manual');
    if (confirmBtn) confirmBtn.classList.add('hidden');

    // Bản đồ giờ hiện ở CẢ 3 mode (kể cả 'manual') — trước đây mode 'manual' ẩn bản đồ đi và gửi mù
    // toạ độ để backend tự dò khi lưu, khách không có cách nào biết hệ thống hiểu đúng địa chỉ hay
    // chưa (nguyên nhân chính gây tính sai khoảng cách/phí ship). Xem scheduleManualForwardGeocode().
    if (mapColumn) mapColumn.classList.remove('hidden');
    if (grid) { grid.classList.add('lg:grid-cols-2'); grid.classList.remove('lg:grid-cols-1'); }
    setTimeout(function () {
        initMapIfNeeded();
        if (map) {
            map.invalidateSize();
            const lat = parseFloat(document.getElementById('addr_lat').value) || 10.73809;
            const lng = parseFloat(document.getElementById('addr_lng').value) || 106.67812;
            map.setView([lat, lng], 15);
            if (marker) marker.setLatLng([lat, lng]);
        }
    }, 50);

    if (method === 'manual') {
        setLocStatus(document.getElementById('addr_lat').value ? 'ok' : 'manual');
        scheduleManualForwardGeocode();
    } else {
        setLocStatus(document.getElementById('addr_lat').value ? 'ok' : 'idle');
    }

    updateSaveButtonState();
}

// Khởi tạo bản đồ Leaflet lần đầu (chỉ chạy 1 lần — nếu đã khởi tạo rồi thì bỏ qua).
// Dùng tile Geoapify, đặt marker có thể kéo thả ở trung tâm mặc định (Q.8, HCM).
function initMapIfNeeded() {
    if (map) return;
    const mapEl = document.getElementById('addressMap');
    if (!mapEl) return;
    const key = geoapifyKey();
    if (!key) return;

    // Default coordinates: Chánh Hưng, Q.8 (10.7433, 106.6738)
    const lat = 10.7433;
    const lng = 106.6738;

    map = L.map('addressMap').setView([lat, lng], 14);
    L.tileLayer(`https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png?apiKey=${key}`, {
        attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank" rel="noopener">Geoapify</a> | © OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function () {
        const position = marker.getLatLng();
        onMapPointPicked(position.lat, position.lng);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        onMapPointPicked(e.latlng.lat, e.latlng.lng);
    });
}

// Xử lý khi chọn/kéo marker trên bản đồ (GPS lưu ngay, Bản đồ hiển thị xem trước)
function onMapPointPicked(lat, lng) {
    if (locationMethod === 'gps') {
        document.getElementById('addr_lat').value = lat.toFixed(6);
        document.getElementById('addr_lng').value = lng.toFixed(6);
        reverseGeocode(lat, lng);
        if (!flagOutOfRange(lat, lng)) {
            setLocStatus('ok', 'Đã xác định vị trí hiện tại');
        }
        updateSaveButtonState();
        return;
    }

    // mode 'map'
    pendingMapLatLng = { lat: lat, lng: lng };
    const confirmBtn = document.getElementById('btnConfirmMapLocation');
    if (confirmBtn) confirmBtn.classList.remove('hidden');
    setLocStatus('locating', 'Đã chọn 1 điểm — bấm "Xác nhận vị trí này" để dùng.');

    // Preview khu vực (debounce), không commit tọa độ.
    if (mapReverseTimer) clearTimeout(mapReverseTimer);
    mapReverseTimer = setTimeout(function () { reverseGeocode(lat, lng); }, 500);
}

// Commit điểm đang preview ở mode 'map' sau khi khách bấm "Xác nhận vị trí này".
function confirmMapLocation() {
    if (!pendingMapLatLng) return;
    document.getElementById('addr_lat').value = pendingMapLatLng.lat.toFixed(6);
    document.getElementById('addr_lng').value = pendingMapLatLng.lng.toFixed(6);
    reverseGeocode(pendingMapLatLng.lat, pendingMapLatLng.lng);
    const confirmBtn = document.getElementById('btnConfirmMapLocation');
    if (confirmBtn) confirmBtn.classList.add('hidden');
    if (!flagOutOfRange(pendingMapLatLng.lat, pendingMapLatLng.lng)) {
        setLocStatus('ok', 'Đã xác định vị trí trên bản đồ');
    }
    updateSaveButtonState();
}

// Tự động tìm vị trí (Geocode) khi người dùng nhập địa chỉ bằng tay ở mode 'manual'
let manualGeocodeTimer = null;
const MANUAL_GEOCODE_MIN_CONFIDENCE = 0.3;

// Tạo chuỗi địa chỉ đầy đủ để tìm vị trí
function buildManualAddressQuery() {
    const specific = (document.getElementById('addr_specific').value || '').trim();
    const wardName = (document.getElementById('addr_ward_search').value || '').trim();
    const provinceName = (document.getElementById('addr_province_search').value || '').trim();
    if (!specific || !wardName || !provinceName) return null;

    let query = [specific, wardName, provinceName].join(', ');
    query = query.replace(/phường\s*\d+/giu, '');
    query = query.replace(/,\s*,/g, ',');
    query = query.replace(/,\s*$/, '').trim() + ', Việt Nam';
    return query;
}

// Lên lịch chạy forward-geocode sau 900ms kể từ lần gõ cuối cùng (debounce),
// tránh gọi API liên tục khi khách đang gõ dở địa chỉ.
function scheduleManualForwardGeocode() {
    if (locationMethod !== 'manual') return;
    if (manualGeocodeTimer) clearTimeout(manualGeocodeTimer);
    manualGeocodeTimer = setTimeout(runManualForwardGeocode, 900);
}

// Thực sự gọi Geoapify Forward-Geocode: ghép địa chỉ từ form → tìm toạ độ → ghim bản đồ.
// Chạy trong nền sau khi scheduleManualForwardGeocode() kích hoạt.
function runManualForwardGeocode() {
    if (locationMethod !== 'manual') return;
    const query = buildManualAddressQuery();
    if (!query) return;

    const key = geoapifyKey();
    if (!key) return;

    const cfg = window.checkoutConfig || {};
    const biasLat = cfg.shopLat || 10.73809;
    const biasLng = cfg.shopLng || 106.67812;

    setLocStatus('locating', 'Đang xác định vị trí từ địa chỉ đã nhập...');

    const url = `https://api.geoapify.com/v1/geocode/search?text=${encodeURIComponent(query)}&lang=vi&limit=1&bias=proximity:${biasLng},${biasLat}&apiKey=${key}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Khách đã đổi sang mode khác trong lúc chờ mạng trả về -> bỏ qua kết quả trễ này.
            if (locationMethod !== 'manual') return;

            const props = data && data.features && data.features[0] && data.features[0].properties;
            if (!props || props.lat === undefined || props.lon === undefined) {
                setLocStatus('notfound', 'Không tìm thấy vị trí cho địa chỉ này. Vui lòng kiểm tra lại hoặc chạm/kéo ghim trên bản đồ bên dưới để chọn thủ công.');
                return;
            }

            const lat = props.lat;
            const lng = props.lon;
            const confidence = (props.rank && props.rank.confidence) || 0;

            initMapIfNeeded();
            if (map) {
                map.setView([lat, lng], 16);
                if (marker) marker.setLatLng([lat, lng]);
            }
            document.getElementById('addr_lat').value = lat.toFixed(6);
            document.getElementById('addr_lng').value = lng.toFixed(6);
            document.getElementById('addr_formatted').value = props.formatted || '';

            if (flagOutOfRange(lat, lng)) {
                // flagOutOfRange() đã tự đặt locStatus phù hợp.
            } else if (confidence < MANUAL_GEOCODE_MIN_CONFIDENCE) {
                setLocStatus('locating', 'Chưa chắc chắn vị trí này đúng — vui lòng nhìn kỹ ghim trên bản đồ, kéo lại nếu chưa đúng.');
            } else {
                setLocStatus('ok', 'Đã xác định vị trí — kiểm tra ghim trên bản đồ, kéo chỉnh nếu chưa đúng.');
            }
            updateSaveButtonState();
        })
        .catch(() => {
            if (locationMethod !== 'manual') return;
            setLocStatus('notfound', 'Không thể xác định vị trí lúc này. Vui lòng chạm/kéo ghim trên bản đồ để chọn thủ công.');
        });
}

// Tính khoảng cách đường chim bay phục vụ cảnh báo vị trí
function straightLineKm(lat1, lng1, lat2, lng2) {
    const toRad = function (d) { return d * Math.PI / 180; };
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// Cảnh báo nếu địa chỉ nằm ngoài bán kính giao hàng
function flagOutOfRange(lat, lng) {
    const cfg = window.checkoutConfig || {};
    if (!cfg.shopLat || !cfg.shopLng || !cfg.shippingMaxDistanceKm) return false;
    const km = straightLineKm(cfg.shopLat, cfg.shopLng, lat, lng);
    if (km > cfg.shippingMaxDistanceKm) {
        setLocStatus('outofrange', 'Ngoài phạm vi giao hàng (khoảng ' + km.toFixed(1) + ' km, tối đa ' + cfg.shippingMaxDistanceKm + ' km)');
        return true;
    }
    return false;
}

// Điền thông tin Tỉnh/Thành/Phường/Xã lấy từ toạ độ bản đồ
function applyLocationProperties(props) {
    if (areaUserSelected) return;
    if (!provincesData) return;

    const provinceGuess = normalizeVN(props.state || '');
    if (!provinceGuess) return;
    const province = provincesData.find(p => normalizeVN(p.name) === provinceGuess);
    if (!province) return;

    const provinceSel = document.getElementById('addr_province_select');
    if (provinceSel && provinceSel.value !== String(province.code)) {
        provinceSel.value = String(province.code);
        setAreaSearchValue('province', province.name);
        document.getElementById('addr_province_code').value = String(province.code);
        showAreaError('province', '');
    }

    const wardGuess = normalizeVN(props.suburb || props.district || '');
    loadWardsFor(province.code).then(wards => {
        if (!wards || areaUserSelected || !wardGuess) return;
        const ward = wards.find(w => normalizeVN(w.name) === wardGuess);
        if (!ward) return;
        const wardSel = document.getElementById('addr_ward_select');
        if (wardSel) {
            wardSel.value = String(ward.code);
            setAreaSearchValue('ward', ward.name);
            document.getElementById('addr_ward_code').value = String(ward.code);
            showAreaError('ward', '');
        }
        updateSaveButtonState();
    });

    updateSaveButtonState();
}

// 1 lần gọi reverse geocode Geoapify. type='building' -> ưu tiên trả toà nhà gần nhất (CÓ số nhà);
// type=null -> mặc định (thường chỉ ra tên đường/hẻm, không số nhà). Trả về properties hoặc null.
function fetchReverse(lat, lng, type) {
    const key = geoapifyKey();
    let url = `https://api.geoapify.com/v1/geocode/reverse?lat=${lat}&lon=${lng}&lang=vi&apiKey=${key}`;
    if (type) url += `&type=${type}`;
    return fetch(url)
        .then(res => res.json())
        .then(data => (data && data.features && data.features[0] && data.features[0].properties) || null);
}

// Lấy thông tin địa chỉ (số nhà, tên đường, khu vực) từ tọa độ
function reverseGeocode(lat, lng) {
    const key = geoapifyKey();
    if (!key) return;
    fetchReverse(lat, lng, 'building')
        .then(props => props || fetchReverse(lat, lng, null))
        .then(props => {
            if (!props) return;
            applyLocationProperties(props);

            const specificParts = [props.housenumber, props.street].filter(Boolean);
            const specificEl = document.getElementById('addr_specific');
            if (specificEl && !specificUserEdited && specificParts.length > 0) {
                specificEl.value = specificParts.join(' ');
                updateSaveButtonState();
            }
        })
        .catch(err => console.error(err));
}

// Chuẩn hoá tên hành chính để so khớp: bỏ dấu, hạ chữ thường, bỏ tiền tố loại đơn vị (Tỉnh/Thành
// phố/Phường/Xã/Thị trấn) — vd "Phường Chánh Hưng" và "phường chánh hưng " cùng ra "chanh hung".
function normalizeVN(str) {
    return (str || '')
        .toString()
        .toLowerCase()
        .replace(/đ/g, 'd')
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/\b(thanh pho|tinh|phuong|xa|thi tran)\b/g, ' ')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}

// ============================================================
// COMBOBOX TÌM KIẾM TỈNH / PHƯỜNG
// Gồm: ô search có lọc không dấu, dropdown tùy chỉnh, select ẩn giữ code hành chính.
// ============================================================

// Trả về object chứa các phần tử DOM của combobox 'province' hoặc 'ward'.
function areaSearchElements(which) {
    return {
        search: document.getElementById(which === 'province' ? 'addr_province_search' : 'addr_ward_search'),
        select: document.getElementById(which === 'province' ? 'addr_province_select' : 'addr_ward_select'),
        dropdown: document.getElementById(which === 'province' ? 'addr_province_dropdown' : 'addr_ward_dropdown'),
        options: document.getElementById(which === 'province' ? 'addr_province_options' : 'addr_ward_options'),
        empty: document.getElementById(which === 'province' ? 'addr_province_empty' : 'addr_ward_empty'),
    };
}

// Gán danh sách items mới vào combobox và vẽ lại toàn bộ options.
function setAreaSearchItems(which, items) {
    areaSearchItems[which] = Array.isArray(items) ? items : [];
    renderAreaOptions(which, '');
}

// Cập nhật nội dung ô search (chỉ hiển thị, không ảnh hưởng select ẩn).
function setAreaSearchValue(which, value) {
    const { search } = areaSearchElements(which);
    if (search) search.value = value || '';
}

// Vẽ lại danh sách option trong dropdown (có lọc theo query, so khớp không dấu).
function renderAreaOptions(which, query) {
    const { options, empty, select } = areaSearchElements(which);
    if (!options || !empty) return;

    const normalizedQuery = normalizeVN(query || '');
    const filtered = areaSearchItems[which].filter(item =>
        !normalizedQuery || normalizeVN(item.name).includes(normalizedQuery)
    );
    options.innerHTML = '';

    filtered.forEach(item => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.code = String(item.code);
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', select && select.value === String(item.code) ? 'true' : 'false');
        button.className = 'w-full text-left px-3 py-2.5 rounded-lg text-sm hover:bg-primary-container/20 focus:bg-primary-container/20 focus:outline-none transition-colors';
        if (select && select.value === String(item.code)) {
            button.classList.add('bg-primary-container/20', 'text-primary', 'font-bold');
        }
        button.textContent = item.name;
        button.addEventListener('click', function () {
            chooseAreaOption(which, item.code, item.name);
        });
        options.appendChild(button);
    });

    empty.classList.toggle('hidden', filtered.length > 0);
}

function openAreaSearch(which) {
    const { search, dropdown } = areaSearchElements(which);
    if (!search || !dropdown || search.disabled) return;

    ['province', 'ward'].forEach(other => {
        if (other !== which) closeAreaSearch(other);
    });
    // Khi vừa mở, luôn hiện toàn bộ danh sách; gõ vào ô sẽ lọc ngay.
    renderAreaOptions(which, '');
    dropdown.classList.remove('hidden');
    search.setAttribute('aria-expanded', 'true');
    requestAnimationFrame(function () { search.select(); });
}

function closeAreaSearch(which) {
    const { search, select, dropdown } = areaSearchElements(which);
    if (!dropdown) return;
    dropdown.classList.add('hidden');
    if (search) {
        search.setAttribute('aria-expanded', 'false');
        const selected = areaSearchItems[which].find(item => select && String(item.code) === select.value);
        search.value = selected ? selected.name : '';
    }
}

function toggleAreaSearch(which) {
    const { search, dropdown } = areaSearchElements(which);
    if (!search || !dropdown || search.disabled) return;
    if (dropdown.classList.contains('hidden')) {
        search.focus();
        openAreaSearch(which);
    } else {
        closeAreaSearch(which);
    }
}

function filterAreaOptions(which) {
    const { search, dropdown } = areaSearchElements(which);
    if (!search || !dropdown) return;

    renderAreaOptions(which, search.value);
    dropdown.classList.remove('hidden');
    search.setAttribute('aria-expanded', 'true');
}

function chooseAreaOption(which, code, name) {
    const { search, select, dropdown } = areaSearchElements(which);
    if (!search || !select) return;

    const oldCode = select.value;
    select.value = String(code);
    search.value = name;
    if (dropdown) dropdown.classList.add('hidden');
    search.setAttribute('aria-expanded', 'false');

    // Chọn lại đúng option hiện tại không được reset dữ liệu ngoài ý muốn.
    if (oldCode === String(code)) {
        updateSaveButtonState();
        return;
    }
    if (which === 'province') onProvinceChange();
    else onWardChange();
}

function handleAreaSearchKeydown(event, which) {
    if (event.key === 'Escape') {
        closeAreaSearch(which);
        event.target.blur();
        return;
    }
    if (event.key !== 'Enter') return;

    const { options, dropdown } = areaSearchElements(which);
    const first = options ? options.querySelector('button') : null;
    if (first && dropdown && !dropdown.classList.contains('hidden')) {
        event.preventDefault();
        first.click();
    }
}

// Mã hoá ký tự đặc biệt HTML để tránh XSS khi nhúng tên tỉnh/phường vào innerHTML.
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

// Dựng khối HTML hiển thị chi tiết mã giảm giá vừa áp dụng thành công: số tiền được giảm thực tế,
// mô tả điều kiện (do Admin nhập), phạm vi áp dụng (nếu giới hạn theo sản phẩm/danh mục) và hạn dùng.
// Mọi giá trị chèn vào đều qua escapeHtml vì description/scope_label là dữ liệu từ Admin, không nên
// tin tưởng tuyệt đối khi ghép trực tiếp vào innerHTML.
function renderCouponSuccessInfo(data, code) {
    const lines = [];
    lines.push(
        '<p class="flex items-center gap-1 text-primary font-bold">' +
        '<span class="material-symbols-outlined text-sm">check_circle</span>' +
        'Áp dụng thành công mã ' + escapeHtml(code) + '!' +
        '</p>'
    );

    const discountAmount = parseFloat(data.discount_amount);
    if (!isNaN(discountAmount) && discountAmount > 0) {
        lines.push('<p class="text-on-surface font-semibold mt-1">Giảm ' + discountAmount.toLocaleString('vi-VN') + 'đ</p>');
    }

    if (data.description) {
        lines.push('<p class="text-on-surface-variant font-normal mt-0.5">' + escapeHtml(data.description) + '</p>');
    }

    if (data.scope_label) {
        lines.push('<p class="text-on-surface-variant font-normal mt-0.5">' + escapeHtml(data.scope_label) + '</p>');
    }

    if (data.end_at) {
        lines.push('<p class="text-on-surface-variant font-normal mt-0.5">Hạn dùng: ' + escapeHtml(data.end_at) + '</p>');
    }

    return lines.join('');
}

// Hiển thị hoặc ẩn thông báo lỗi của khu vực chọn
function showAreaError(which, msg) {
    const help = document.getElementById(which === 'province' ? 'provinceHelpText' : 'wardHelpText');
    const sel = document.getElementById(which === 'province' ? 'addr_province_select' : 'addr_ward_select');
    const search = document.getElementById(which === 'province' ? 'addr_province_search' : 'addr_ward_search');
    if (help) { help.textContent = msg || ''; help.classList.toggle('hidden', !msg); }
    if (sel) sel.setAttribute('aria-invalid', msg ? 'true' : 'false');
    if (search) search.setAttribute('aria-invalid', msg ? 'true' : 'false');
}

// Tải danh sách Tỉnh/Thành phố từ server
function loadProvinces() {
    if (provincesData) {
        setAreaSearchItems('province', provincesData);
        return Promise.resolve(provincesData);
    }

    provincesLoading = true;
    const sel = document.getElementById('addr_province_select');
    const search = document.getElementById('addr_province_search');
    if (sel) {
        sel.disabled = true;
        sel.innerHTML = '<option value="">Đang tải tỉnh/thành phố...</option>';
    }
    if (search) {
        search.disabled = true;
        search.value = '';
        search.placeholder = 'Đang tải tỉnh/thành phố...';
    }
    updateSaveButtonState();

    return fetch('/administrative/provinces')
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            provincesLoading = false;
            if (!ok || !data.success) {
                showAreaError('province', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
                if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
                if (search) { search.disabled = true; search.placeholder = 'Không tải được dữ liệu'; }
                updateSaveButtonState();
                return null;
            }
            provincesData = data.data;
            if (sel) {
                sel.disabled = false;
                sel.innerHTML = '<option value="">Chọn tỉnh/thành phố</option>' +
                    provincesData.map(p => `<option value="${p.code}">${escapeHtml(p.name)}</option>`).join('');
            }
            setAreaSearchItems('province', provincesData);
            if (search) {
                search.disabled = false;
                search.placeholder = 'Tìm tỉnh/thành phố...';
            }
            updateSaveButtonState();
            return provincesData;
        })
        .catch(() => {
            provincesLoading = false;
            showAreaError('province', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
            if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
            if (search) { search.disabled = true; search.placeholder = 'Không tải được dữ liệu'; }
            updateSaveButtonState();
            return null;
        });
}

// Tải danh sách Phường/Xã thuộc một tỉnh/thành
function loadWardsFor(provinceCode) {
    const fillWardOptions = function (wards) {
        const provinceSelect = document.getElementById('addr_province_select');
        // Tránh ghi đè nếu người dùng đã đổi sang tỉnh khác trước khi tải xong
        if (provinceSelect && provinceSelect.value !== String(provinceCode)) return;
        const wardSelect = document.getElementById('addr_ward_select');
        const wardSearch = document.getElementById('addr_ward_search');
        if (wardSelect) {
            wardSelect.disabled = false;
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>' +
                wards.map(w => `<option value="${w.code}">${escapeHtml(w.name)}</option>`).join('');
        }
        setAreaSearchItems('ward', wards);
        if (wardSearch) {
            wardSearch.disabled = false;
            wardSearch.placeholder = 'Tìm phường/xã...';
        }
    };

    if (wardsDataByProvince[provinceCode]) {
        fillWardOptions(wardsDataByProvince[provinceCode]);
        return Promise.resolve(wardsDataByProvince[provinceCode]);
    }

    wardsLoading = true;
    const sel = document.getElementById('addr_ward_select');
    const search = document.getElementById('addr_ward_search');
    if (sel) {
        sel.disabled = true;
        sel.innerHTML = '<option value="">Đang tải phường/xã...</option>';
    }
    if (search) {
        search.disabled = true;
        search.value = '';
        search.placeholder = 'Đang tải phường/xã...';
    }
    updateSaveButtonState();

    return fetch(`/administrative/provinces/${provinceCode}/wards`)
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            wardsLoading = false;
            if (!ok || !data.success) {
                showAreaError('ward', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
                if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
                if (search) { search.disabled = true; search.placeholder = 'Không tải được dữ liệu'; }
                updateSaveButtonState();
                return null;
            }
            wardsDataByProvince[provinceCode] = data.data;
            fillWardOptions(data.data);
            updateSaveButtonState();
            return data.data;
        })
        .catch(() => {
            wardsLoading = false;
            showAreaError('ward', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
            if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
            if (search) { search.disabled = true; search.placeholder = 'Không tải được dữ liệu'; }
            updateSaveButtonState();
            return null;
        });
}

// Xử lý khi chọn Tỉnh/Thành phố
function onProvinceChange() {
    areaUserSelected = true;
    const sel = document.getElementById('addr_province_select');
    const code = sel && sel.value ? sel.value : '';
    document.getElementById('addr_province_code').value = code;
    showAreaError('province', '');

    // Đổi tỉnh -> reset phường/xã
    document.getElementById('addr_ward_code').value = '';
    showAreaError('ward', '');
    const wardSel = document.getElementById('addr_ward_select');
    if (wardSel) {
        wardSel.disabled = true;
        wardSel.innerHTML = '<option value="">Vui lòng chọn tỉnh/thành phố trước</option>';
    }
    const wardSearch = document.getElementById('addr_ward_search');
    if (wardSearch) {
        wardSearch.disabled = true;
        wardSearch.value = '';
        wardSearch.placeholder = 'Chọn tỉnh/thành phố trước';
    }
    setAreaSearchItems('ward', []);

    // Reset tọa độ cũ để yêu cầu định vị lại
    document.getElementById('addr_lat').value = '';
    document.getElementById('addr_lng').value = '';
    pendingMapLatLng = null;
    const confirmBtn = document.getElementById('btnConfirmMapLocation');
    if (confirmBtn) confirmBtn.classList.add('hidden');
    setLocStatus('idle');

    updateSaveButtonState();
    if (code) loadWardsFor(parseInt(code, 10));
}

// Xử lý khi chọn Phường/Xã
function onWardChange() {
    areaUserSelected = true;
    const sel = document.getElementById('addr_ward_select');
    document.getElementById('addr_ward_code').value = sel && sel.value ? sel.value : '';
    showAreaError('ward', '');
    updateSaveButtonState();
    // Tự động tìm tọa độ khi đã có đủ thông tin địa chỉ
    scheduleManualForwardGeocode();
}

// Reset các ô chọn khu vực về rỗng
function resetAreaSelects() {
    areaUserSelected = false;
    document.getElementById('addr_province_code').value = '';
    document.getElementById('addr_ward_code').value = '';
    showAreaError('province', '');
    showAreaError('ward', '');
    const provinceSel = document.getElementById('addr_province_select');
    if (provinceSel && provincesData) provinceSel.value = '';
    setAreaSearchValue('province', '');
    const wardSel = document.getElementById('addr_ward_select');
    if (wardSel) {
        wardSel.disabled = true;
        wardSel.innerHTML = '<option value="">Vui lòng chọn tỉnh/thành phố trước</option>';
    }
    const wardSearch = document.getElementById('addr_ward_search');
    if (wardSearch) {
        wardSearch.disabled = true;
        wardSearch.value = '';
        wardSearch.placeholder = 'Chọn tỉnh/thành phố trước';
    }
    setAreaSearchItems('ward', []);
}

// Tự động chọn khu vực theo tên (phục vụ chế độ Sửa địa chỉ)
function preselectAreaByName(provinceName, wardName) {
    loadProvinces().then(provinces => {
        if (!provinces) return;
        const provinceGuess = normalizeVN(provinceName);
        const province = provinces.find(p => normalizeVN(p.name) === provinceGuess);
        if (!province) return;

        const provinceSel = document.getElementById('addr_province_select');
        if (provinceSel) provinceSel.value = String(province.code);
        setAreaSearchValue('province', province.name);
        document.getElementById('addr_province_code').value = String(province.code);

        loadWardsFor(province.code).then(wards => {
            if (!wards) return;
            const wardGuess = normalizeVN(wardName);
            const ward = wards.find(w => normalizeVN(w.name) === wardGuess);
            if (!ward) return;
            const wardSel = document.getElementById('addr_ward_select');
            if (wardSel) wardSel.value = String(ward.code);
            setAreaSearchValue('ward', ward.name);
            document.getElementById('addr_ward_code').value = String(ward.code);
            updateSaveButtonState();
        });
    });
}

// Cập nhật trạng thái nút lưu địa chỉ dựa trên tính hợp lệ của dữ liệu đầu vào
function updateSaveButtonState() {
    const btn = document.getElementById('btnSaveAddress');
    if (!btn) return;
    const fullname = (document.getElementById('addr_fullname').value || '').trim();
    const phone = (document.getElementById('addr_phone').value || '').trim();
    const specific = (document.getElementById('addr_specific').value || '').trim();
    const hasCoords = !!document.getElementById('addr_lat').value;
    const phoneOk = /^(0[3|5|7|8|9])+([0-9]{8})$/.test(phone);
    const areaOk = !!(document.getElementById('addr_province_code').value && document.getElementById('addr_ward_code').value);
    const locationOk = hasCoords || locationMethod === 'manual';
    const notLoading = !provincesLoading && !wardsLoading;

    // Báo lỗi nếu định dạng số điện thoại sai
    const phoneErrorEl = document.getElementById('addr_phone_error');
    if (phoneErrorEl) phoneErrorEl.classList.toggle('hidden', phone === '' || phoneOk);

    btn.disabled = !(fullname && phoneOk && areaOk && specific && locationOk && notLoading);
}

// Lấy vị trí GPS hiện tại của trình duyệt
function getCurrentLocation() {
    if (!navigator.geolocation) {
        setLocStatus('notfound', 'Trình duyệt không hỗ trợ định vị GPS');
        if (window.FrontendAlert) window.FrontendAlert.error('Trình duyệt của bạn không hỗ trợ định vị GPS. Vui lòng chọn trên bản đồ hoặc nhập địa chỉ.'); else alert('Trình duyệt của bạn không hỗ trợ định vị GPS. Vui lòng chọn trên bản đồ hoặc nhập địa chỉ.');
        return;
    }

    setLocStatus('locating', 'Đang xác định vị trí hiện tại...');

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy; // mét

            document.getElementById('addr_lat').value = lat.toFixed(6);
            document.getElementById('addr_lng').value = lng.toFixed(6);

            initMapIfNeeded();
            if (map) {
                map.setView([lat, lng], 16);
                if (marker) marker.setLatLng([lat, lng]);
            }
            reverseGeocode(lat, lng);

            if (!flagOutOfRange(lat, lng)) {
                if (accuracy && accuracy > 100) {
                    setLocStatus('ok', 'Đã xác định vị trí (độ chính xác thấp ~' + Math.round(accuracy) + 'm) — hãy kéo ghim để chỉnh lại');
                } else {
                    setLocStatus('ok', 'Đã xác định vị trí hiện tại');
                }
            }
            updateSaveButtonState();
        },
        (error) => {
            let msg = 'Không thể lấy vị trí hiện tại.';
            if (error.code === error.PERMISSION_DENIED) {
                msg = 'Bạn đã từ chối quyền truy cập vị trí. Vui lòng cấp quyền, hoặc chọn trên bản đồ / nhập địa chỉ thủ công.';
            } else if (error.code === error.TIMEOUT) {
                msg = 'Xác định vị trí quá lâu (hết thời gian chờ). Vui lòng thử lại, hoặc chọn trên bản đồ / nhập địa chỉ.';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                msg = 'Không lấy được vị trí (thiết bị/mạng không hỗ trợ). Vui lòng chọn trên bản đồ / nhập địa chỉ.';
            }
            setLocStatus('notfound', 'Không lấy được vị trí GPS');
            if (window.FrontendAlert) window.FrontendAlert.error(msg); else alert(msg);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// Đặt loại địa chỉ ('home' hoặc 'office') và cập nhật giao diện nút chọn.
function setAddrType(type) {
    document.getElementById('addr_type').value = type;
    const homeBtn = document.getElementById('btnTypeHome');
    const officeBtn = document.getElementById('btnTypeOffice');
    [[homeBtn, 'home'], [officeBtn, 'office']].forEach(([btn, btnType]) => {
        if (!btn) return;
        const isActive = btnType === type;
        btn.classList.toggle('bg-primary', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('border-primary', isActive);
    });
}

// Mở modal thêm hoặc sửa địa chỉ (isEdit = true để chỉnh sửa)
function openAddressModal(isEdit = false, data = null) {
    const modal = document.getElementById('addressModal');
    if (!modal) return;
    modal.classList.remove('hidden');

    pendingMapLatLng = null;
    const confirmBtn = document.getElementById('btnConfirmMapLocation');
    if (confirmBtn) confirmBtn.classList.add('hidden');

    // Tải danh sách Tỉnh/Thành phố ngay khi mở modal
    loadProvinces();

    if (isEdit && data) {
        document.getElementById('addressModalTitle').textContent = 'Sửa địa chỉ';
        document.getElementById('addr_id').value = data.id || '';
        document.getElementById('addr_fullname').value = data.fullname || '';
        document.getElementById('addr_phone').value = data.phone || '';
        document.getElementById('addr_specific').value = data.specificAddress || '';
        document.getElementById('addr_lat').value = data.latitude || '';
        document.getElementById('addr_lng').value = data.longitude || '';
        document.getElementById('addr_formatted').value = '';
        document.getElementById('addr_default').checked = String(data.isDefault) === '1' || data.isDefault === true;

        specificUserEdited = false;
        areaUserSelected = false;
        setAddrType(data.type === 'office' ? 'office' : 'home');

        preselectAreaByName(data.province || '', data.ward || '');
    } else {
        document.getElementById('addressModalTitle').textContent = 'Thêm địa chỉ mới';
        document.getElementById('addr_id').value = '';
        document.getElementById('addr_fullname').value = '';
        document.getElementById('addr_phone').value = '';
        document.getElementById('addr_specific').value = '';
        document.getElementById('addr_lat').value = '';
        document.getElementById('addr_lng').value = '';
        document.getElementById('addr_formatted').value = '';
        document.getElementById('addr_default').checked = false;

        specificUserEdited = false;
        resetAreaSelects();
        setAddrType('home');
    }

    // Mở bản đồ ở chế độ mặc định hoặc theo địa chỉ cũ
    setLocationMethod(isEdit && data && data.locationMethod ? data.locationMethod : 'map');
    updateSaveButtonState();
}

// Đóng modal thêm/sửa địa chỉ (thêm class 'hidden' vào overlay).
function closeAddressModal() {
    const modal = document.getElementById('addressModal');
    if (modal) modal.classList.add('hidden');
}

// Ủy quyền click mở modal thêm hoặc sửa địa chỉ
document.addEventListener('click', function (event) {
    const addBtn = event.target.closest('.add-address-btn');
    if (addBtn) {
        openAddressModal(false);
        return;
    }

    const editBtn = event.target.closest('.edit-address-btn');
    if (editBtn) {
        openAddressModal(true, {
            id: editBtn.dataset.addressId,
            fullname: editBtn.dataset.fullname,
            phone: editBtn.dataset.phone,
            province: editBtn.dataset.province,
            district: editBtn.dataset.district,
            ward: editBtn.dataset.ward,
            specificAddress: editBtn.dataset.specificAddress,
            type: editBtn.dataset.type,
            isDefault: editBtn.dataset.isDefault,
            latitude: editBtn.dataset.latitude,
            longitude: editBtn.dataset.longitude,
            locationMethod: editBtn.dataset.locationMethod,
        });
    }
});

// Lưu địa chỉ (thêm mới hoặc cập nhật): đọc toàn bộ giá trị từ form modal,
// validate phía client, sau đó POST lên /profile/address (hoặc /profile/address/{id}).
// Thành công → reload trang để cập nhật danh sách địa chỉ.
function saveAddress() {
    const id = document.getElementById('addr_id').value;
    const fullname = document.getElementById('addr_fullname').value.trim();
    const phone = document.getElementById('addr_phone').value.trim();
    const specific = document.getElementById('addr_specific').value.trim();
    const lat = document.getElementById('addr_lat').value;
    const lng = document.getElementById('addr_lng').value;
    const type = document.getElementById('addr_type').value;
    const isDefault = document.getElementById('addr_default').checked ? 1 : 0;
    const method = document.getElementById('addr_location_method').value || 'map';
    const formatted = document.getElementById('addr_formatted').value || '';
    const provinceCode = document.getElementById('addr_province_code').value;
    const wardCode = document.getElementById('addr_ward_code').value;

    if (!fullname || !phone || !specific) {
        if (window.FrontendAlert) window.FrontendAlert.error('Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ cụ thể.'); else alert('Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ cụ thể.');
        return;
    }
    if (!provinceCode || !wardCode) {
        if (!provinceCode) showAreaError('province', 'Vui lòng chọn Tỉnh/Thành phố.');
        if (!wardCode) showAreaError('ward', 'Vui lòng chọn Phường/Xã.');
        return;
    }
    // gps/map yêu cầu tọa độ, manual để server tự dò khi lưu
    if (method !== 'manual' && !lat) {
        if (window.FrontendAlert) window.FrontendAlert.error('Vui lòng xác định vị trí trên bản đồ (bấm "Xác nhận vị trí này") hoặc bấm "Lấy vị trí hiện tại".'); else alert('Vui lòng xác định vị trí trên bản đồ (bấm "Xác nhận vị trí này") hoặc bấm "Lấy vị trí hiện tại".');
        return;
    }

    const payload = {
        fullname,
        phone,
        specific_address: specific,
        province_code: parseInt(provinceCode, 10),
        ward_code: parseInt(wardCode, 10),
        latitude: lat || null,
        longitude: lng || null,
        location_method: method,
        formatted_address: formatted,
        type,
        is_default: isDefault,
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };

    const url = id ? `/profile/address/${id}` : '/profile/address';

    // Lưu trạng thái địa chỉ chọn vào localStorage
    const stateObj = {
        selected_address_id: id || 'new',
        weather: document.getElementById('weather_select') ? document.getElementById('weather_select').value : null,
        is_peak_hour: document.getElementById('peak_hour_select') ? document.getElementById('peak_hour_select').value : null
    };
    localStorage.setItem('checkout_address_state', JSON.stringify(stateObj));

    // Modal địa chỉ nằm lồng trong <form id="checkout-form"> chính nên không thể thêm 1 <form> nữa vào
    // đây — dựng tạm 1 form ẩn độc lập rồi submit thật (không phải AJAX/fetch), tải lại trang checkout
    // sau khi lưu xong để hiện đúng danh sách địa chỉ mới (lỗi server trả về, vd không xác định được
    // tọa độ, sẽ hiện qua $errors sau khi tải lại — xem addressError() trong ProfileController).
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    Object.keys(payload).forEach(function (key) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = payload[key] === null || payload[key] === undefined ? '' : payload[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}

// Bind helper function to window to expose to inline events
window.getCurrentLocation = getCurrentLocation;
window.setAddrType = setAddrType;
window.closeAddressModal = closeAddressModal;
window.saveAddress = saveAddress;
window.onProvinceChange = onProvinceChange;
window.onWardChange = onWardChange;
window.setLocationMethod = setLocationMethod;
window.confirmMapLocation = confirmMapLocation;
window.updateSaveButtonState = updateSaveButtonState;
window.openAreaSearch = openAreaSearch;
window.closeAreaSearch = closeAreaSearch;
window.toggleAreaSearch = toggleAreaSearch;
window.filterAreaOptions = filterAreaOptions;
window.handleAreaSearchKeydown = handleAreaSearchKeydown;

// Lắng nghe thay đổi trên các ô nhập để cập nhật nút lưu địa chỉ
document.addEventListener('DOMContentLoaded', function () {
    ['addr_fullname', 'addr_phone', 'addr_specific'].forEach(function (elId) {
        const el = document.getElementById(elId);
        if (el) el.addEventListener('input', updateSaveButtonState);
    });
    const specificEl = document.getElementById('addr_specific');
    if (specificEl) {
        // Đánh dấu người dùng tự nhập địa chỉ cụ thể để không bị reverseGeocode ghi đè
        specificEl.addEventListener('input', function () {
            specificUserEdited = true;
            scheduleManualForwardGeocode();
        });
    }
    const provSel = document.getElementById('addr_province_select');
    if (provSel) provSel.addEventListener('change', function () { onProvinceChange(); updateSaveButtonState(); });
    const wardSel = document.getElementById('addr_ward_select');
    if (wardSel) wardSel.addEventListener('change', function () { onWardChange(); updateSaveButtonState(); });

    document.addEventListener('click', function (event) {
        ['province', 'ward'].forEach(function (which) {
            const root = document.querySelector(`[data-area-search-root="${which}"]`);
            if (root && !root.contains(event.target)) closeAreaSearch(which);
        });
    });
});

// ============================================================
// TÍNH TIỀN, MÃ GIẢM GIÁ, ĐIỂM THƯỞNG, PHÍ SHIP, CHỌN ĐỊA CHỈ
// Toàn bộ chạy sau khi DOM đã sẵn sàng (DOMContentLoaded).
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const priceSummaryEl = document.getElementById('price-summary');
    if (!priceSummaryEl) return;

    const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
    let discount = 0;
    let pointsDiscount = 0;
    // Giảm giá từ chương trình combo
    const comboDiscount = parseInt(priceSummaryEl.dataset.comboDiscount) || 0;

    const shippingDistanceText = document.getElementById('summary-shipping-distance-text');
    const weatherFeeText = document.getElementById('summary-weather-fee-text');

    const discountRow = document.getElementById('summary-discount-row');
    const discountText = document.getElementById('summary-discount-text');
    const totalText = document.getElementById('summary-total-text');
    const couponInput = document.getElementById('coupon_code_input');
    const applyCouponBtn = document.getElementById('apply_coupon_btn');
    const couponMessage = document.getElementById('coupon_message');
    const orderBtn = document.getElementById('order-submit-btn');

    // Points Redemption Inputs
    const pointsInput = document.getElementById('points_to_redeem_input');
    const applyPointsBtn = document.getElementById('apply_points_btn');
    const pointsMessage = document.getElementById('points_message');
    const maxRedeemablePointsText = document.getElementById('max-redeemable-points');

    // Load configs or default
    const config = window.checkoutConfig || {
        shippingBaseFee: 15000,
        shippingFeePerKm: 5000,
        shippingMaxDistanceKm: 15,
        freeShippingMinimum: 150000
    };

    // Payment method selection border update
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const checkoutForm = document.getElementById('checkout-form');

    // Chặn phím Enter tự động submit form khi đang nhập liệu
    if (checkoutForm) {
        checkoutForm.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
            }
        });
    }

    // Cập nhật action của form checkout theo phương thức thanh toán đang chọn
    // (COD → /checkout, MoMo → /checkout/momo, VNPay → /checkout/vnpay).
    function updateFormAction() {
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
        if (selectedPayment && checkoutForm) {
            if (selectedPayment.value === 'momo' && checkoutForm.dataset.momoUrl) {
                checkoutForm.action = checkoutForm.dataset.momoUrl;
            } else if (selectedPayment.value === 'vnpay' && checkoutForm.dataset.vnpayUrl) {
                checkoutForm.action = checkoutForm.dataset.vnpayUrl;
            } else if (checkoutForm.dataset.codUrl) {
                checkoutForm.action = checkoutForm.dataset.codUrl;
            }
        }
    }

    // Form đặt hàng submit thật (tải lại trang) — action đổi đúng cổng thanh toán qua updateFormAction()
    // ở trên; server (CustomerOrderController::store()/MomoController/VnpayController::createPayment(),
    // đều dùng HandlesCheckoutResponse) tự redirect tới đích hoặc quay lại kèm lỗi qua $errors.

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateBorders(paymentRadios);
            updateFormAction();
            if (orderBtn) {
                if (radio.value === 'momo') {
                    orderBtn.innerText = 'Chuyển khoản (MoMo)';
                } else if (radio.value === 'vnpay') {
                    orderBtn.innerText = 'Chuyển khoản (VNPay)';
                } else {
                    orderBtn.innerText = 'Đặt hàng (COD)';
                }
            }
        });
    });

    // Set initial form action on load
    updateFormAction();

    // === CẬP NHẬT HIỂN THỊ VIỀN KHI CHỌN PHƯƠNG THỨC THANH TOÁN (PAYMENT METHODS BORDER) ===
    // Giúp tô màu viền nổi bật (màu xanh lá chủ đạo) cho phương thức thanh toán đang được tích chọn (COD, MoMo, VNPAY...)
    function updateBorders(radios) {
        radios.forEach(radio => {
            const label = radio.closest('label'); // Tìm thẻ label bọc ngoài input radio
            if (label) {
                if (radio.checked) {
                    // Nếu đang được chọn: Đổi viền dày hơn, màu xanh lá primary và nền xanh lá nhạt
                    label.classList.add('border-2', 'border-primary', 'bg-primary-container/10');
                    label.classList.remove('border-outline-variant');
                } else {
                    // Nếu không được chọn: Phục hồi viền mỏng xám nhạt mặc định
                    label.classList.remove('border-2', 'border-primary', 'bg-primary-container/10');
                    label.classList.add('border-outline-variant');
                }
            }
        });
    }

    // Khởi chạy đồng bộ viền ban đầu khi trang thanh toán vừa tải xong
    updateBorders(paymentRadios);

    // === XỬ LÝ ÁP DỤNG MÃ GIẢM GIÁ (APPLY COUPON LOGIC VIA AJAX FETCH) ===
    let discountPercent = 0; // Tỷ lệ giảm giá (%) nếu mã áp dụng theo tỷ lệ
    let maxDiscountAmount = 0; // Số tiền giảm tối đa (đối với mã giảm theo % có kèm trần giảm)
    // Phạm vi áp dụng của mã giảm giá ('order' - Toàn đơn, 'product' - Theo sản phẩm, 'category' - Theo danh mục)
    let couponScope = 'order';

    // === XỬ LÝ CLICK CHIP MÃ GỢI Ý → TỰ ĐỘNG ĐIỀN VÀO INPUT VÀ GỌI VALIDATE ===
    document.querySelectorAll('.coupon-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const code = chip.dataset.code;
            if (!code || !couponInput) return;

            // Điền mã vào ô nhập
            couponInput.value = code;

            // Đánh dấu chip đang active, bỏ active chip cũ
            document.querySelectorAll('.coupon-chip').forEach(c => {
                c.classList.remove('!bg-primary', '!border-primary', '!text-white', 'ring-2', 'ring-primary/30');
            });
            chip.classList.add('!bg-primary', '!border-primary', '!text-white', 'ring-2', 'ring-primary/30');

            // Tự động click nút Áp dụng để validate ngay
            if (applyCouponBtn) applyCouponBtn.click();
        });
    });

    if (applyCouponBtn && couponInput) {
        // Khi người dùng gõ tay vào ô mã → bỏ active chip đang được chọn
        couponInput.addEventListener('input', () => {
            document.querySelectorAll('.coupon-chip').forEach(c => {
                c.classList.remove('!bg-primary', '!border-primary', '!text-white', 'ring-2', 'ring-primary/30');
            });
        });

        // Lắng nghe sự kiện click nút "Áp dụng" mã giảm giá
        applyCouponBtn.addEventListener('click', () => {
            // Chuẩn hóa chuỗi mã: xóa khoảng trắng thừa ở hai đầu và chuyển sang chữ IN HOA
            const code = couponInput.value.trim().toUpperCase();

            // Trường hợp 1: Nếu người dùng để trống ô nhập mã và bấm Áp dụng (hoặc xóa sạch mã cũ đi)
            if (code === '') {
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponScope = 'order';
                couponMessage.innerText = ''; // Xóa thông báo lỗi/thành công cũ
                discountRow.classList.add('hidden'); // Ẩn dòng hiển thị số tiền giảm ở bảng tóm tắt hóa đơn
                document.getElementById('hidden_coupon_code').value = ''; // Xóa sạch mã trong input ẩn gửi lên form đặt hàng
                calculateTotal(); // Tính toán lại tổng tiền gốc
                return;
            }

            const token = document.querySelector('input[name="_token"]').value; // Lấy mã token CSRF bảo mật của Laravel
            const currentSubtotal = subtotal; // Đọc tổng tiền hàng hiện tại (chưa tính ship)

            // Hiển thị trạng thái đang xử lý để phản hồi người dùng
            couponMessage.innerText = 'Đang kiểm tra...';
            couponMessage.className = 'text-xs text-on-surface-variant font-medium mt-1';

            // Gửi request POST AJAX kiểm tra tính hợp lệ của mã giảm giá trên hệ thống cơ sở dữ liệu
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
            .then(res => res.json()) // Chuyển đổi dữ liệu phản hồi từ máy chủ sang dạng JSON
            .then(data => {
                // Trường hợp 2: Nếu mã giảm giá hợp lệ và được server chấp nhận
                if (data.valid) {
                    discount = parseFloat(data.discount_amount) || 0; // Nhận số tiền giảm thực tế từ server tính toán
                    couponScope = data.scope || 'order'; // Phạm vi áp dụng của mã giảm
                    
                    // Nếu là hình thức giảm giá theo phần trăm (%)
                    if (data.discount_type === 'percent') {
                        discountPercent = data.discount_value; // Lưu tỷ lệ phần trăm (ví dụ: 10 đại diện cho 10%)
                        maxDiscountAmount = data.max_discount_amount ? parseFloat(data.max_discount_amount) : 0; // Trần giảm giá (ví dụ: tối đa 50k)
                    } else {
                        // Nếu giảm theo số tiền mặt cố định (ví dụ: giảm thẳng 20k)
                        discountPercent = 0;
                        maxDiscountAmount = 0;
                    }

                    // Hiển thị khối thông tin chi tiết của mã: số tiền được giảm thực tế, mô tả điều
                    // kiện áp dụng (từ Admin), phạm vi áp dụng (sản phẩm/danh mục) và hạn dùng nếu có.
                    couponMessage.innerHTML = renderCouponSuccessInfo(data, code);
                    couponMessage.className = 'text-xs mt-1.5';
                } else {
                    // Trường hợp 3: Mã giảm giá không hợp lệ (hết hạn, không đủ điều kiện đơn tối thiểu, nhập sai...)
                    discount = 0;
                    discountPercent = 0;
                    couponScope = 'order';
                    // Hiển thị thông báo lỗi màu đỏ từ Backend trả về
                    couponMessage.innerText = data.message;
                    couponMessage.className = 'text-xs text-error font-bold mt-1';
                }
                
                // Luôn gọi hàm tính toán lại tổng tiền để cập nhật bảng tóm tắt hóa đơn mới
                calculateTotal();
            })
            .catch(err => {
                // Trường hợp 4: Lỗi kết nối mạng, mất mạng hoặc server gặp sự cố ngoài ý muốn
                console.error('Lỗi kiểm tra mã giảm giá:', err);
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponMessage.innerText = 'Có lỗi xảy ra khi kiểm tra mã.';
                couponMessage.className = 'text-xs text-error font-bold mt-1';
                calculateTotal();
            });
        });
    }

    // Points Redemption Handling
    if (pointsInput) {
        const pointsBalance = parseInt(pointsInput.dataset.pointsBalance || 0);
        const pointValue = parseFloat(pointsInput.dataset.pointValue || 1000);
        const maxRedeemPercent = parseFloat(pointsInput.dataset.maxRedeemPercent || 100);
        const minPointsToRedeem = parseInt(pointsInput.dataset.minPointsToRedeem || 10);

        const maxDiscountMoney = subtotal * (maxRedeemPercent / 100);
        const maxPointsRedeemable = Math.min(pointsBalance, Math.floor(maxDiscountMoney / pointValue));

        if (maxRedeemablePointsText) {
            maxRedeemablePointsText.innerText = maxPointsRedeemable;
        }

        if (applyPointsBtn) {
            applyPointsBtn.addEventListener('click', () => {
                const enteredPoints = parseInt(pointsInput.value || 0);
                if (isNaN(enteredPoints) || enteredPoints < 0) {
                    pointsDiscount = 0;
                    pointsMessage.innerText = 'Số điểm không hợp lệ.';
                    pointsMessage.className = 'text-xs text-error font-bold mt-1';
                    calculateTotal();
                    return;
                }

                if (enteredPoints > pointsBalance) {
                    pointsDiscount = 0;
                    pointsMessage.innerText = 'Số điểm nhập vượt quá số dư hiện có.';
                    pointsMessage.className = 'text-xs text-error font-bold mt-1';
                    calculateTotal();
                    return;
                }

                if (enteredPoints > maxPointsRedeemable) {
                    pointsDiscount = 0;
                    pointsMessage.innerText = 'Số điểm vượt quá giới hạn tối đa cho đơn này (' + maxPointsRedeemable + ' điểm).';
                    pointsMessage.className = 'text-xs text-error font-bold mt-1';
                    calculateTotal();
                    return;
                }

                if (enteredPoints > 0 && enteredPoints < minPointsToRedeem) {
                    pointsDiscount = 0;
                    pointsMessage.innerText = 'Số điểm tối thiểu để được đổi là ' + minPointsToRedeem + ' điểm.';
                    pointsMessage.className = 'text-xs text-error font-bold mt-1';
                    calculateTotal();
                    return;
                }

                pointsDiscount = enteredPoints * pointValue;
                if (enteredPoints > 0) {
                    pointsMessage.innerText = 'Áp dụng đổi ' + enteredPoints + ' điểm thành công (Giảm -' + pointsDiscount.toLocaleString('vi-VN') + 'đ)';
                    pointsMessage.className = 'text-xs text-primary font-bold mt-1';
                } else {
                    pointsMessage.innerText = '';
                }
                calculateTotal();
            });
        }
    }

    // Tính lại tổng tiền và cập nhật UI tóm tắt đơn hàng:
    // Tổng = subtotal + phí ship (theo km) + phụ phí thời tiết - giảm giá coupon - đổi điểm - giảm combo.
    // Tính lại tổng tiền và cập nhật giao diện tóm tắt hóa đơn
    function calculateTotal() {
        // Lấy khoảng cách (mặc định 2.5km nếu chưa định vị xong)
        const hiddenDist = document.getElementById('hidden_distance_km');
        const distanceKm = hiddenDist ? parseFloat(hiddenDist.value) : 2.5;

        // Lấy phụ phí thời tiết xấu (nếu có)
        const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
        const weatherFee = hiddenWeatherFee ? parseFloat(hiddenWeatherFee.value) : 0;

        // Kiểm tra xem đơn hàng có đủ điều kiện Freeship không
        const freeShipThreshold = config.freeShippingMinimum;
        const freeShip = subtotal >= freeShipThreshold;

        // Tính phí vận chuyển (2km đầu tính phí cơ bản, các km sau tính thêm phí/km)
        let distanceFee = 0;
        if (!freeShip) {
            if (distanceKm <= 2) {
                distanceFee = config.shippingBaseFee;
            } else {
                distanceFee = config.shippingBaseFee + (distanceKm - 2) * config.shippingFeePerKm;
            }
        }
        distanceFee = Math.round(distanceFee);

        // Tính số tiền giảm theo phần trăm của mã coupon
        if (discountPercent > 0 && couponScope === 'order') {
            discount = Math.round(subtotal * (discountPercent / 100));
            if (maxDiscountAmount && maxDiscountAmount > 0 && discount > maxDiscountAmount) {
                discount = maxDiscountAmount;
            }
            if (discount > subtotal) discount = subtotal;
        }

        // Tổng tất cả các khoản giảm giá (Mã coupon + Điểm tích lũy + Giảm combo)
        const totalDiscount = discount + pointsDiscount + comboDiscount;

        // Công thức tính tổng tiền cuối cùng (Tổng = Hàng + Ship + Thời tiết - Giảm giá)
        const total = Math.max(0, subtotal + distanceFee + (freeShip ? 0 : weatherFee) - totalDiscount);

        // Hiển thị số km khoảng cách lên giao diện
        const distValEl = document.getElementById('summary-distance-km-val');
        if (distValEl) distValEl.innerText = distanceKm.toFixed(1);

        const freeShipRow = document.getElementById('summary-free-ship-row');
        const shippingDistRow = document.getElementById('summary-shipping-distance-row');

        // Nếu vượt quá khoảng cách giao hàng tối đa của quán -> Khóa không cho đặt hàng
        if (distanceKm > config.shippingMaxDistanceKm) {
            if (orderBtn) {
                orderBtn.disabled = true;
                orderBtn.innerText = 'Chỉ giao trong ' + config.shippingMaxDistanceKm + 'km';
                orderBtn.classList.add('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600');
                orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary', 'bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
            }
            if (shippingDistanceText) shippingDistanceText.innerHTML = '<span class="text-error font-bold">Không hỗ trợ giao quá ' + config.shippingMaxDistanceKm + 'km</span>';
            if (totalText) totalText.innerHTML = '<span class="text-error font-bold">---</span>';

            const weatherRowOver = document.getElementById('summary-weather-fee-row');
            if (weatherRowOver) weatherRowOver.classList.add('hidden');
            if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
        } else {
            // Trong phạm vi giao hàng -> Mở khóa nút đặt hàng và đổi nhãn nút theo phương thức thanh toán
            if (orderBtn) {
                const isClosed = document.getElementById('order-submit-btn').dataset.closed === '1';
                if (!isClosed) {
                    orderBtn.disabled = false;
                    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                    orderBtn.classList.remove('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600', 'opacity-50');
                    if (selectedPayment && selectedPayment.value === 'momo') {
                        orderBtn.innerText = 'Chuyển khoản (MoMo)';
                        orderBtn.classList.add('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                        orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary', 'bg-[#003c71]', 'hover:bg-[#002e57]');
                    } else if (selectedPayment && selectedPayment.value === 'vnpay') {
                        orderBtn.innerText = 'Chuyển khoản (VNPay)';
                        orderBtn.classList.add('bg-[#003c71]', 'hover:bg-[#002e57]', 'text-white');
                        orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary', 'bg-[#ae2070]', 'hover:bg-[#8b1a5a]');
                    } else {
                        orderBtn.innerText = 'Đặt hàng (COD)';
                        orderBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                        orderBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white', 'bg-[#003c71]', 'hover:bg-[#002e57]');
                    }
                }
            }

            // Hiển thị phí ship và phụ phí thời tiết phù hợp lên giao diện
            if (freeShip) {
                // Được Freeship -> Ẩn dòng tiền ship, hiện dòng thông báo Freeship
                if (freeShipRow) freeShipRow.classList.remove('hidden');
                if (shippingDistRow) shippingDistRow.classList.add('hidden');
                if (shippingDistanceText) shippingDistanceText.innerText = '0đ';

                const weatherRow = document.getElementById('summary-weather-fee-row');
                if (weatherRow) weatherRow.classList.add('hidden');
                if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
            } else {
                // Không được Freeship -> Hiện số tiền ship và tiền phụ thu thời tiết nếu có
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

            // Hiển thị tổng số tiền khách cần thanh toán
            if (totalText) totalText.innerText = total.toLocaleString('vi-VN') + 'đ';
        }

        // Hiển thị hoặc ẩn dòng tóm tắt số tiền được giảm giá
        if (totalDiscount > 0) {
            if (discountRow) discountRow.classList.remove('hidden');
            if (discountText) discountText.innerText = '-' + totalDiscount.toLocaleString('vi-VN') + 'đ';
        } else {
            if (discountRow) discountRow.classList.add('hidden');
        }
    }

    // Sự kiện ẩn/hiện danh sách đổi địa chỉ nhận hàng
    const changeAddressBtn = document.getElementById('change-address-btn');
    const addressListPanel = document.getElementById('address-list-panel');
    if (changeAddressBtn && addressListPanel) {
        changeAddressBtn.addEventListener('click', () => {
            addressListPanel.classList.toggle('hidden');
        });
    }

    // Sự kiện khi người dùng đổi địa chỉ nhận hàng khác trên danh sách
    const addressRadios = document.querySelectorAll('input[name="address_selector"]');
    const hiddenAddressIdInput = document.getElementById('selected_address_id');
    const activeAddressName = document.getElementById('active-address-name');
    const activeAddressPhone = document.getElementById('active-address-phone');
    const activeAddressDetails = document.getElementById('active-address-details');

    // Hàm gọi API của Laravel để tính khoảng cách thực tế đến địa chỉ mới chọn
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

                    updateWeatherFeeForAddress(addressId, distanceKm);//tính phí thời tiết

                    calculateTotal();// Tính lại tổng tiền

                    // Hiển thị ghi chú cách tính tiền ship và nguồn tính (thực tế hay giả lập)
                    const calcDesc = document.getElementById('distance-calc-desc');
                    if (calcDesc) {
                        const fmtBase = config.shippingBaseFee.toLocaleString('vi-VN');
                        const fmtPerKm = config.shippingFeePerKm.toLocaleString('vi-VN');
                        const text = `Phí vận chuyển: ${fmtBase}đ (2km đầu) + ${fmtPerKm}đ / km tiếp theo.`;
                        if (data.is_mock) {
                            calcDesc.innerHTML = `<span style="color:#d97706; font-weight: 600;">⚠️ ${data.message}</span><br>${text}`;
                        } else {
                            calcDesc.innerHTML = `<span style="color:#15803d; font-weight: 600;">✅ Khoảng cách được tính thực tế bằng Geoapify Routing API.</span><br>${text}`;
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

    // Lấy phụ phí thời tiết xấu (mưa/bão) từ backend (/checkout/weather-fee).
    // Phụ phí được tính dựa trên điều kiện thời tiết thực tế tại vị trí giao hàng.
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

            updateDistanceForAddress(radio.value);

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

// Xóa địa chỉ giao hàng ngay từ trang Checkout (POST /profile/address/{id}/delete) — dựng form ẩn độc
// lập rồi submit thật (cùng lý do/pattern với saveAddress() ở trên: nút xóa nằm lồng trong form chính
// nên không thể thêm <form> bao quanh trực tiếp).
function deleteAddressCheckout(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/profile/address/' + id + '/delete';
    form.style.display = 'none';

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(tokenInput);

    document.body.appendChild(form);
    form.submit();
}
