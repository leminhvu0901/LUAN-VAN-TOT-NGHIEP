// checkout.js - Frontend Checkout Logic

let map;
let marker;

// Danh mục hành chính (Tỉnh/Thành + Phường/Xã) — nguồn tin cậy CHÍNH cho khu vực, tải qua AJAX từ
// backend (KHÔNG hard-code ở đây). provincesData: [{code,name}] | null (chưa tải/lỗi).
// wardsDataByProvince: cache theo province_code -> [{code,name,province_code}] | null.
let provincesData = null;
let provincesLoading = false;
let wardsDataByProvince = {};
let wardsLoading = false;

// Dữ liệu cho 2 combobox có tìm kiếm. Select ẩn vẫn giữ code hành chính chính thức;
// ô search chỉ đảm nhiệm hiển thị và lọc tên (kể cả khi khách gõ không dấu).
const areaSearchItems = { province: [], ward: [] };

// Phương thức xác định vị trí đang chọn: 'gps' | 'map' | 'manual'.
let locationMethod = 'map';
// Khách đã tự CHỌN (change thật, không phải set bằng code) 1 trong 2 select Tỉnh/Thành-Phường/Xã
// trong phiên mở modal này chưa -> nếu rồi, gợi ý từ Geoapify KHÔNG được tự chọn lại đè lên (mục 6).
// Mở modal Sửa với dữ liệu đã lưu KHÔNG bật cờ này (chỉ preselect, không phải khách tự chọn).
let areaUserSelected = false;
// Tương tự cho ô "Địa chỉ cụ thể": chỉ chặn geocode tự điền khi khách đã gõ tay, không phải vì ô đang
// có sẵn giá trị cũ từ địa chỉ đã lưu (mở modal Sửa).
let specificUserEdited = false;
// Ở mode 'map': tọa độ khách vừa chấm nhưng CHƯA bấm "Xác nhận" (chỉ commit sau khi xác nhận).
let pendingMapLatLng = null;
// Debounce reverse geocode khi kéo/chấm marker liên tục.
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

// Chuyển phương thức xác định vị trí. KHÔNG reset họ tên/SĐT/loại địa chỉ, KHÔNG tự gửi form.
// CÓ reset cờ "khách đã tự sửa khu vực/địa chỉ cụ thể" khi thực sự đổi sang 1 phương thức KHÁC:
// các cờ này chỉ nhằm chặn Geoapify tự đè lên trong lúc khách đang thao tác ở CÙNG 1 phương thức
// (mục 5/6); nếu để nguyên khi khách chủ động chuyển tab thì địa chỉ cũ (gõ tay ở tab "Nhập địa chỉ"
// chẳng hạn) sẽ bị khoá cứng, không nhảy theo vị trí mới chấm ở tab "Vị trí hiện tại"/"Chọn trên bản
// đồ" nữa — đúng bug đã gặp.
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
        // Đủ dữ liệu sẵn rồi (vd quay lại mode này, hoặc mở form Sửa) -> dò lại luôn không cần đợi gõ thêm.
        scheduleManualForwardGeocode();
    } else {
        // Trạng thái: đã có tọa độ -> ok, chưa có -> idle.
        setLocStatus(document.getElementById('addr_lat').value ? 'ok' : 'idle');
    }

    updateSaveButtonState();
}

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

// Xử lý khi khách chấm/kéo marker — hành vi theo mode:
//   - gps: khách đang chỉnh lại vị trí GPS của mình -> commit ngay (đặt lat/lng + reverse geocode).
//   - map: chỉ PREVIEW, lưu vào pendingMapLatLng + hiện nút "Xác nhận vị trí này". Chỉ commit lat/lng
//     sau khi bấm xác nhận (mục 3). Reverse geocode preview khu vực (debounce) nhưng không commit tọa độ.
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

// ==============================================================================================
// MODE 'manual': trước đây gõ xong địa chỉ là gửi mù, chỉ có backend tự dò tọa độ lúc lưu (khách
// không thấy được kết quả dò đúng hay sai -> hay bị tính sai khoảng cách/phí ship). Giờ tự dò NGAY
// khi khách gõ đủ khu vực + địa chỉ cụ thể, ghim lên bản đồ (đã hiện sẵn ở mode này) để khách nhìn
// thấy và TỰ kéo ghim chỉnh lại nếu sai — cùng cơ chế commit-ngay-khi-kéo mà onMapPointPicked() đã
// có sẵn cho mode 'gps' (không cần bấm "Xác nhận" thêm lần nữa, vì gõ địa chỉ tay đã là 1 lần xác nhận).
// ==============================================================================================
let manualGeocodeTimer = null;
// Cùng ngưỡng với backend (ProfileController::GEOCODE_MIN_CONFIDENCE) — dưới mức này vẫn ghim (để
// khách còn thấy mà tự sửa) nhưng cảnh báo rõ là chưa chắc đúng, thay vì âm thầm coi như đã đúng.
const MANUAL_GEOCODE_MIN_CONFIDENCE = 0.3;

// Ghép chuỗi địa chỉ để forward-geocode — CÙNG QUY TẮC với backend (resolveLocation() trong
// ProfileController): bỏ "Phường <số>" (Geoapify hiểu sai dạng số), luôn thêm "Việt Nam" ở cuối
// (thiếu quốc gia dễ ra 0 kết quả hoặc lạc sang nước khác).
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

function scheduleManualForwardGeocode() {
    if (locationMethod !== 'manual') return;
    if (manualGeocodeTimer) clearTimeout(manualGeocodeTimer);
    manualGeocodeTimer = setTimeout(runManualForwardGeocode, 900);
}

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

// Khoảng cách đường chim bay (km) — CHỈ để cảnh báo mềm "ngoài phạm vi" ở modal, KHÔNG dùng tính phí
// ship (phí ship tính bằng Geoapify Routing ở server, công thức không đổi).
function straightLineKm(lat1, lng1, lat2, lng2) {
    const toRad = function (d) { return d * Math.PI / 180; };
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// Nếu vị trí vượt bán kính giao hàng tối đa -> hiện trạng thái "Ngoài phạm vi giao hàng" (cảnh báo
// mềm, KHÔNG chặn lưu — enforcement thật vẫn ở bước chọn địa chỉ khi checkout). Trả true nếu ngoài phạm vi.
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

// Áp dữ liệu geocode vào ô "Khu vực" — CHỈ khi khách CHƯA tự sửa (mục 5: Geoapify không ghi đè khu
// vực khách nhập). CỐ Ý KHÔNG dùng props.county/props.city — đã xác nhận qua test thật: dữ liệu
// quận/huyện của Geoapify cho TP.HCM sai. Chỉ props.state (tỉnh/thành) + props.suburb (phường/xã) đáng tin.
// Gợi ý Tỉnh/Thành + Phường/Xã từ Geoapify (reverse geocode) — CHỈ tự chọn option khi đối chiếu CHẮC
// CHẮN (so khớp tên đã chuẩn hoá) với danh mục hành chính đã tải; không đối chiếu được thì bỏ qua,
// để khách tự chọn (mục 6). Không bao giờ tự chọn nếu khách đã tự chọn tay trong phiên này.
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

function reverseGeocode(lat, lng) {
    const key = geoapifyKey();
    if (!key) return;
    // Ưu tiên type=building để lấy SỐ NHÀ + tên đường của toà nhà gần nhất (đã xác nhận qua test thật:
    // reverse mặc định chỉ ra "Hẻm ... Bông Sao" không số, còn type=building ra "252 Bông Sao"). Không
    // có toà nhà gần -> fallback reverse mặc định (vẫn đủ khu vực + tên đường).
    fetchReverse(lat, lng, 'building')
        .then(props => props || fetchReverse(lat, lng, null))
        .then(props => {
            if (!props) return;
            applyLocationProperties(props);

            // Gợi ý "Địa chỉ cụ thể" (số nhà + tên đường) CHỈ khi khách CHƯA tự gõ tay ô này trong phiên
            // hiện tại — không đè text khách đã gõ. Ô đang có sẵn giá trị cũ (vd mở modal Sửa) vẫn được
            // cập nhật khi khách chấm lại điểm mới, vì đó không phải khách tự gõ.
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

function areaSearchElements(which) {
    return {
        search: document.getElementById(which === 'province' ? 'addr_province_search' : 'addr_ward_search'),
        select: document.getElementById(which === 'province' ? 'addr_province_select' : 'addr_ward_select'),
        dropdown: document.getElementById(which === 'province' ? 'addr_province_dropdown' : 'addr_ward_dropdown'),
        options: document.getElementById(which === 'province' ? 'addr_province_options' : 'addr_ward_options'),
        empty: document.getElementById(which === 'province' ? 'addr_province_empty' : 'addr_ward_empty'),
    };
}

function setAreaSearchItems(which, items) {
    areaSearchItems[which] = Array.isArray(items) ? items : [];
    renderAreaOptions(which, '');
}

function setAreaSearchValue(which, value) {
    const { search } = areaSearchElements(which);
    if (search) search.value = value || '';
}

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

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

// Hiện/ẩn lỗi ngay dưới đúng select (mục 8) + đánh dấu aria-invalid (mục 10).
function showAreaError(which, msg) {
    const help = document.getElementById(which === 'province' ? 'provinceHelpText' : 'wardHelpText');
    const sel = document.getElementById(which === 'province' ? 'addr_province_select' : 'addr_ward_select');
    const search = document.getElementById(which === 'province' ? 'addr_province_search' : 'addr_ward_search');
    if (help) { help.textContent = msg || ''; help.classList.toggle('hidden', !msg); }
    if (sel) sel.setAttribute('aria-invalid', msg ? 'true' : 'false');
    if (search) search.setAttribute('aria-invalid', msg ? 'true' : 'false');
}

// Tải danh sách Tỉnh/Thành phố (1 lần, cache lại) — nguồn hành chính chính thức qua backend proxy,
// KHÔNG hard-code danh sách ở đây (mục 3).
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

// Tải danh sách Phường/Xã CỦA 1 tỉnh (chỉ tải theo tỉnh, không tải cả nước — mục 3.4), cache theo
// province_code.
function loadWardsFor(provinceCode) {
    const fillWardOptions = function (wards) {
        const provinceSelect = document.getElementById('addr_province_select');
        // Nếu khách đã đổi sang tỉnh khác trong lúc request đang chạy, chỉ cache kết quả cũ,
        // không được vẽ nhầm danh sách phường/xã của tỉnh trước đó.
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

// Khách CHỌN tay Tỉnh/Thành phố (change thật trên select).
function onProvinceChange() {
    areaUserSelected = true;
    const sel = document.getElementById('addr_province_select');
    const code = sel && sel.value ? sel.value : '';
    document.getElementById('addr_province_code').value = code;
    showAreaError('province', '');

    // Đổi tỉnh -> reset phường/xã + yêu cầu chọn lại (mục 3.3).
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

    // Tọa độ cũ (nếu có) không còn chắc khớp với tỉnh mới -> xóa, yêu cầu xác định lại vị trí (mục 3.3).
    document.getElementById('addr_lat').value = '';
    document.getElementById('addr_lng').value = '';
    pendingMapLatLng = null;
    const confirmBtn = document.getElementById('btnConfirmMapLocation');
    if (confirmBtn) confirmBtn.classList.add('hidden');
    setLocStatus('idle');

    updateSaveButtonState();
    if (code) loadWardsFor(parseInt(code, 10));
}

// Khách CHỌN tay Phường/Xã (change thật trên select).
function onWardChange() {
    areaUserSelected = true;
    const sel = document.getElementById('addr_ward_select');
    document.getElementById('addr_ward_code').value = sel && sel.value ? sel.value : '';
    showAreaError('ward', '');
    updateSaveButtonState();
    // Chọn xong Phường/Xã là địa chỉ đã đủ 3 phần (khu vực + địa chỉ cụ thể) -> dò vị trí luôn, không
    // cần đợi khách gõ thêm gì (trường hợp khách gõ "Địa chỉ cụ thể" TRƯỚC rồi mới chọn khu vực sau).
    scheduleManualForwardGeocode();
}

// Reset 2 select về trạng thái ban đầu (chế độ Thêm mới).
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

// Chế độ Sửa: dữ liệu cũ chỉ có TÊN tỉnh/phường (không có code) -> đối chiếu theo tên đã chuẩn hoá để
// tự chọn đúng option (mục 7.5). Không khớp được (tên đã đổi/sáp nhập) -> để trống, yêu cầu khách tự
// chọn lại (mục 9) — KHÔNG bật areaUserSelected vì đây là preselect, không phải khách tự chọn.
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

// Bật/tắt nút "Hoàn thành" theo tính hợp lệ (mục 7). Hợp lệ khi: họ tên + SĐT (đúng định dạng VN) +
// khu vực (đã chọn cả 2 select) + địa chỉ cụ thể + (đã có tọa độ HOẶC mode manual có thể geocode khi
// lưu) + KHÔNG đang trong lúc tải tỉnh/phường (mục 9).
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

    // Chỉ báo lỗi SĐT khi khách ĐÃ gõ gì đó mà sai định dạng — không báo ngay khi ô còn trống, tránh
    // dọa người dùng trước khi họ kịp gõ. Trước đây nút Lưu chỉ âm thầm bị disable, không giải thích
    // lý do, khiến người dùng gõ sai mà không biết vì sao không lưu được.
    const phoneErrorEl = document.getElementById('addr_phone_error');
    if (phoneErrorEl) phoneErrorEl.classList.toggle('hidden', phone === '' || phoneOk);

    btn.disabled = !(fullname && phoneOk && areaOk && specific && locationOk && notLoading);
}

// GPS (mục 2): xin quyền vị trí, hiển thị trạng thái, đặt marker + reverse geocode, cho kéo chỉnh.
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

// isEdit=false: mở modal ở chế độ thêm mới (form trống). isEdit=true + data: mở ở chế độ sửa,
// điền sẵn dữ liệu từ data-* của nút "Sửa" đã bấm (fullname/phone/specific/province/district/ward/...).
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

    // Mặc định mở ở mode "Chọn trên bản đồ" (setLocationMethod cũng lo init/định vị lại bản đồ).
    setLocationMethod(isEdit && data && data.locationMethod ? data.locationMethod : 'map');
    updateSaveButtonState();
}

function closeAddressModal() {
    const modal = document.getElementById('addressModal');
    if (modal) modal.classList.add('hidden');
}

// Ủy quyền sự kiện: bấm bất kỳ nút .add-address-btn nào (kể cả ở khối "chưa có địa chỉ") mở modal
// thêm mới; bấm .edit-address-btn đọc dữ liệu từ data-* của chính nút đó để mở modal ở chế độ sửa.
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
    // gps/map bắt buộc phải có tọa độ; manual để backend tự geocode khi lưu.
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

    // Store state in localStorage to restore simulator values on reload
    const stateObj = {
        selected_address_id: id || 'new',
        weather: document.getElementById('weather_select') ? document.getElementById('weather_select').value : null,
        is_peak_hour: document.getElementById('peak_hour_select') ? document.getElementById('peak_hour_select').value : null
    };
    localStorage.setItem('checkout_address_state', JSON.stringify(stateObj));

    const saveBtn = document.getElementById('btnSaveAddress');
    if (saveBtn) saveBtn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json().then(data => ({ ok: res.ok, data: data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                window.location.reload();
            } else {
                // Backend không geocode được (mode manual mơ hồ) -> báo trạng thái + gợi ý, không đóng modal.
                setLocStatus('notfound', 'Không tìm thấy địa chỉ');
                if (window.FrontendAlert) window.FrontendAlert.error(data.message || 'Không xác định được vị trí. Vui lòng kiểm tra lại hoặc chọn trên bản đồ.'); else alert(data.message || 'Không xác định được vị trí. Vui lòng kiểm tra lại hoặc chọn trên bản đồ.');
                updateSaveButtonState();
            }
        })
        .catch(err => {
            console.error(err);
            if (window.FrontendAlert) window.FrontendAlert.error('Có lỗi xảy ra, vui lòng thử lại.'); else alert('Có lỗi xảy ra, vui lòng thử lại.');
            updateSaveButtonState();
        });
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

// Bật/tắt nút "Hoàn thành" theo thời gian thực khi khách gõ các trường bắt buộc.
document.addEventListener('DOMContentLoaded', function () {
    ['addr_fullname', 'addr_phone', 'addr_specific'].forEach(function (elId) {
        const el = document.getElementById(elId);
        if (el) el.addEventListener('input', updateSaveButtonState);
    });
    const specificEl = document.getElementById('addr_specific');
    if (specificEl) {
        // Khách tự gõ ô "Địa chỉ cụ thể" -> đánh dấu, để reverseGeocode() không tự điền đè lên nữa.
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

document.addEventListener('DOMContentLoaded', function () {
    const priceSummaryEl = document.getElementById('price-summary');
    if (!priceSummaryEl) return;

    const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
    let discount = 0;
    let pointsDiscount = 0;
    // Giảm giá combo (nếu có) đã được server tự động áp dụng sẵn (không cần khách nhập mã) — cộng vào
    // tổng giảm giá khi tính lại total ở calculateTotal(), y hệt cách discount/pointsDiscount đã làm.
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

    // Mọi ô nhập (địa chỉ, mã giảm giá, điểm...) đều nằm TRONG form checkout -> nhấn Enter ở 1 ô text
    // đơn dòng sẽ submit form = ĐẶT HÀNG NGAY ngoài ý muốn. Chặn Enter-submit ở các ô <input> (textarea
    // giữ nguyên để xuống dòng bình thường). Việc đặt hàng chỉ được thực hiện qua nút "Đặt hàng".
    if (checkoutForm) {
        checkoutForm.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
            }
        });
    }

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

    // Đặt hàng qua fetch thay vì form POST cổ điển — trước đây khi tạo đơn thất bại (hết hàng, cửa
    // hàng đóng cửa, phiên thanh toán hết hạn...) trình duyệt tải lại cả trang checkout đột ngột, mất
    // vị trí cuộn/trạng thái đang thao tác dở. Submit thành công (COD lẫn MoMo) đều kết thúc bằng điều
    // hướng thật (sang /orders hoặc sang cổng MoMo) nên vẫn giữ đúng trải nghiệm "đi tới trang khác",
    // chỉ riêng trường hợp LỖI mới ở lại trang hiện tại thay vì tải lại toàn bộ.
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const btn = document.getElementById('order-submit-btn');
            if (btn) btn.disabled = true;

            fetch(checkoutForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: new FormData(checkoutForm),
            })
                .then(function (response) {
                    return response.json().then(function (data) { return { status: response.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status >= 400) {
                        const errors = result.data && result.data.errors ? result.data.errors : {};
                        const firstError = Object.values(errors)[0];
                        if (window.FrontendAlert) window.FrontendAlert.error((firstError && firstError[0]) || result.data.message || 'Không thể đặt hàng, vui lòng kiểm tra lại.'); else alert((firstError && firstError[0]) || result.data.message || 'Không thể đặt hàng, vui lòng kiểm tra lại.');
                        if (btn) btn.disabled = false;
                        return;
                    }

                    if (result.data && result.data.redirect_url) {
                        window.location.href = result.data.redirect_url;
                        return;
                    }

                    if (btn) btn.disabled = false;
                })
                .catch(function () {
                    if (window.FrontendAlert) window.FrontendAlert.error('Không thể kết nối máy chủ, vui lòng thử lại.'); else alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                    if (btn) btn.disabled = false;
                });
        });
    }

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

    // Initialize borders
    updateBorders(paymentRadios);

    // Apply coupon
    let discountPercent = 0;
    let maxDiscountAmount = 0;
    // Phạm vi mã đang áp dụng ('order' | 'product' | 'category') — quyết định calculateTotal() có
    // được phép tự tính lại số giảm theo % hay phải giữ nguyên số server đã tính (xem calculateTotal).
    let couponScope = 'order';
    if (applyCouponBtn && couponInput) {
        applyCouponBtn.addEventListener('click', () => {
            const code = couponInput.value.trim().toUpperCase();

            if (code === '') {
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponScope = 'order';
                couponMessage.innerText = '';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
                calculateTotal();
                return;
            }

            const token = document.querySelector('input[name="_token"]').value;
            const currentSubtotal = subtotal;

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
                        couponScope = data.scope || 'order';
                        if (data.discount_type === 'percent') {
                            discountPercent = data.discount_value;
                            maxDiscountAmount = data.max_discount_amount ? parseFloat(data.max_discount_amount) : 0;
                        } else {
                            discountPercent = 0;
                            maxDiscountAmount = 0;
                        }

                        // Mã theo sản phẩm/danh mục -> nói rõ áp dụng cho món/danh mục nào.
                        couponMessage.innerText = data.scope_label
                            ? data.message + ' ' + data.scope_label
                            : data.message;
                        couponMessage.className = 'text-xs text-primary font-bold mt-1';
                    } else {
                        discount = 0;
                        discountPercent = 0;
                        couponScope = 'order';
                        couponMessage.innerText = data.message;
                        couponMessage.className = 'text-xs text-error font-bold mt-1';
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

    function calculateTotal() {
        const hiddenDist = document.getElementById('hidden_distance_km');
        const distanceKm = hiddenDist ? parseFloat(hiddenDist.value) : 2.5;

        const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
        const weatherFee = hiddenWeatherFee ? parseFloat(hiddenWeatherFee.value) : 0;

        const freeShipThreshold = config.freeShippingMinimum;
        const freeShip = subtotal >= freeShipThreshold;

        // Calculate shipping base + per km fee dynamic calculation
        let distanceFee = 0;
        if (!freeShip) {
            if (distanceKm <= 2) {
                distanceFee = config.shippingBaseFee;
            } else {
                distanceFee = config.shippingBaseFee + (distanceKm - 2) * config.shippingFeePerKm;
            }
        }
        distanceFee = Math.round(distanceFee);

        // Tính lại số tiền giảm theo % — CHỈ đúng với mã giảm TOÀN ĐƠN, vì chỉ khi đó phần trăm mới
        // áp trên toàn bộ subtotal. Với mã theo sản phẩm/danh mục, số giảm phải tính trên riêng phần
        // hàng hợp lệ (server đã tính sẵn và trả về discount_amount) — tính lại ở đây sẽ ra số lớn
        // hơn thực tế, hiện sai cho khách. Nên các mã đó giữ nguyên số server trả về.
        if (discountPercent > 0 && couponScope === 'order') {
            discount = Math.round(subtotal * (discountPercent / 100));
            if (maxDiscountAmount && maxDiscountAmount > 0 && discount > maxDiscountAmount) {
                discount = maxDiscountAmount;
            }
            if (discount > subtotal) discount = subtotal;
        }

        const totalDiscount = discount + pointsDiscount + comboDiscount;

        // Calculate final total
        const total = Math.max(0, subtotal + distanceFee + (freeShip ? 0 : weatherFee) - totalDiscount);

        // Update UI
        const distValEl = document.getElementById('summary-distance-km-val');
        if (distValEl) distValEl.innerText = distanceKm.toFixed(1);

        const freeShipRow = document.getElementById('summary-free-ship-row');
        const shippingDistRow = document.getElementById('summary-shipping-distance-row');

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
            if (orderBtn) {
                // Ensure button is not disabled due to hours
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

            // Free ship logic
            if (freeShip) {
                if (freeShipRow) freeShipRow.classList.remove('hidden');
                if (shippingDistRow) shippingDistRow.classList.add('hidden');
                if (shippingDistanceText) shippingDistanceText.innerText = '0đ';

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

        if (totalDiscount > 0) {
            if (discountRow) discountRow.classList.remove('hidden');
            if (discountText) discountText.innerText = '-' + totalDiscount.toLocaleString('vi-VN') + 'đ';
        } else {
            if (discountRow) discountRow.classList.add('hidden');
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
            if (window.FrontendAlert) window.FrontendAlert.error(json.message || "Có lỗi xảy ra"); else alert(json.message || "Có lỗi xảy ra");
        }
    } catch (e) { if (window.FrontendAlert) window.FrontendAlert.error("Có lỗi xảy ra"); else alert("Có lỗi xảy ra"); }
}
