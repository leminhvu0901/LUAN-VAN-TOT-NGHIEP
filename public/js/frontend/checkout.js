// checkout.js - Frontend Checkout Logic

let map;
let marker;
let routePolyline;
let locState = {
    province: null,
    district: null,
    ward: null,
    province_name: '',
    district_name: '',
    ward_name: ''
};

function toggleLocPanel() {
    const p = document.getElementById('locationPanel');
    if (p) p.classList.toggle('hidden');
}

function switchLocTab(tab) {
    document.querySelectorAll('.loc-tab').forEach(el => el.classList.remove('active', 'border-primary', 'text-primary'));
    document.querySelectorAll('.loc-pane').forEach(el => el.classList.add('hidden'));

    const activeTab = document.getElementById(`tab-${tab}`);
    if (activeTab) activeTab.classList.add('active', 'border-primary', 'text-primary');

    const activePane = document.getElementById(`pane-${tab}`);
    if (activePane) activePane.classList.remove('hidden');

    if (tab === 'map') {
        setTimeout(initMapIfNeeded, 100);
    }
}

function initMapIfNeeded() {
    if (map) return;
    const mapEl = document.getElementById('map-container');
    if (!mapEl) return;

    // Default coordinates: HCMC (10.73809, 106.67812)
    const lat = 10.73809;
    const lng = 106.67812;

    map = L.map('map-container').setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        document.getElementById('addr_lat').value = position.lat.toFixed(6);
        document.getElementById('addr_lng').value = position.lng.toFixed(6);
        reverseGeocode(position.lat, position.lng);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        document.getElementById('addr_lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('addr_lng').value = e.latlng.lng.toFixed(6);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
}

function reverseGeocode(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi`)
        .then(res => res.json())
        .then(data => {
            if (data && data.address) {
                const addr = data.address;
                locState.province_name = addr.city || addr.state || addr.province || '';
                locState.district_name = addr.suburb || addr.quarter || addr.district || '';
                locState.ward_name = addr.suburb || addr.ward || '';
                updateLocPickerText();
            }
        }).catch(err => console.error(err));
}

function updateLocPickerText() {
    const textEl = document.getElementById('selected-loc-text');
    if (textEl) {
        const parts = [locState.ward_name, locState.district_name, locState.province_name].filter(Boolean);
        textEl.innerText = parts.length > 0 ? parts.join(', ') : 'Chưa chọn';
    }
}

function resetToManual() {
    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '' };
    updateLocPickerText();
    const mapPane = document.getElementById('pane-map');
    if (mapPane) mapPane.classList.add('hidden');
    const textEl = document.getElementById('selected-loc-text');
    if (textEl) textEl.innerText = 'Chưa chọn';
}

function getCurrentLocation() {
    if (!navigator.geolocation) {
        alert("Trình duyệt không hỗ trợ định vị GPS.");
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById('addr_lat').value = lat.toFixed(6);
            document.getElementById('addr_lng').value = lng.toFixed(6);

            if (map) {
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
            }
            reverseGeocode(lat, lng);
        },
        (error) => {
            alert("Không thể lấy vị trí hiện tại. Vui lòng cấp quyền GPS.");
        }
    );
}

function setAddrType(type) {
    document.querySelectorAll('.addr-type-btn').forEach(btn => {
        if (btn.dataset.type === type) {
            btn.classList.add('bg-primary', 'text-white');
            btn.classList.remove('bg-surface-container', 'text-on-surface');
        } else {
            btn.classList.remove('bg-primary', 'text-white');
            btn.classList.add('bg-surface-container', 'text-on-surface');
        }
    });
    document.getElementById('addr_type').value = type;
}

function openAddressModal(isEdit = false) {
    const modal = document.getElementById('address-modal');
    if (modal) {
        modal.classList.remove('hidden');
        if (!isEdit) {
            document.getElementById('addressModalTitle').textContent = 'Thêm địa chỉ mới';
            document.getElementById('addr_id').value = '';
            document.getElementById('addr_fullname').value = '';
            document.getElementById('addr_phone').value = '';
            document.getElementById('addr_specific').value = '';
            document.getElementById('addr_lat').value = '';
            document.getElementById('addr_lng').value = '';
            resetToManual();
            setAddrType('home');
            document.getElementById('addr_default').checked = false;
        }
    }
}

function closeAddressModal() {
    const modal = document.getElementById('address-modal');
    if (modal) modal.classList.add('hidden');
}

function geocodeAndUpdateMap() {
    const specific = document.getElementById('addr_specific').value.trim();
    if (specific.length < 5) return;

    const parts = [specific, locState.ward_name, locState.district_name, locState.province_name].filter(Boolean);
    const query = encodeURIComponent(parts.join(', '));

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`)
        .then(res => res.json())
        .then(results => {
            if (results && results.length > 0) {
                const lat = parseFloat(results[0].lat);
                const lng = parseFloat(results[0].lon);
                document.getElementById('addr_lat').value = lat.toFixed(6);
                document.getElementById('addr_lng').value = lng.toFixed(6);
                if (map) {
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                }
            }
        })
        .catch(err => console.error(err));
}

function saveAddress() {
    const id = document.getElementById('addr_id').value;
    const fullname = document.getElementById('addr_fullname').value.trim();
    const phone = document.getElementById('addr_phone').value.trim();
    const specific = document.getElementById('addr_specific').value.trim();
    const lat = document.getElementById('addr_lat').value;
    const lng = document.getElementById('addr_lng').value;
    const type = document.getElementById('addr_type').value;
    const isDefault = document.getElementById('addr_default').checked ? 1 : 0;

    if (!fullname || !phone || !specific) {
        alert("Vui lòng điền đầy đủ các trường thông tin chính.");
        return;
    }

    const payload = {
        fullname,
        phone,
        specific_address: specific,
        province: locState.province_name,
        district: locState.district_name,
        ward: locState.ward_name,
        latitude: lat || null,
        longitude: lng || null,
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

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || "Có lỗi xảy ra.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Có lỗi xảy ra.");
        });
}

// Bind helper function to window to expose to inline events
window.toggleLocPanel = toggleLocPanel;
window.switchLocTab = switchLocTab;
window.resetToManual = resetToManual;
window.getCurrentLocation = getCurrentLocation;
window.setAddrType = setAddrType;
window.closeAddressModal = closeAddressModal;
window.saveAddress = saveAddress;

document.addEventListener('DOMContentLoaded', function () {
    const priceSummaryEl = document.getElementById('price-summary');
    if (!priceSummaryEl) return;

    const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
    let discount = 0;
    let pointsDiscount = 0;

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
    
    function updateFormAction() {
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
        if (selectedPayment && checkoutForm) {
            if (selectedPayment.value === 'momo' && checkoutForm.dataset.momoUrl) {
                checkoutForm.action = checkoutForm.dataset.momoUrl;
            } else if (checkoutForm.dataset.codUrl) {
                checkoutForm.action = checkoutForm.dataset.codUrl;
            }
        }
    }

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateBorders(paymentRadios);
            updateFormAction();
            if (orderBtn) {
                if (radio.value === 'momo') {
                    orderBtn.innerText = 'Thanh toán qua MoMo';
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
                        if (data.discount_type === 'percent') {
                            discountPercent = data.discount_value;
                            maxDiscountAmount = data.max_discount_amount ? parseFloat(data.max_discount_amount) : 0;
                        } else {
                            discountPercent = 0;
                            maxDiscountAmount = 0;
                        }

                        couponMessage.innerText = data.message;
                        couponMessage.className = 'text-xs text-primary font-bold mt-1';
                    } else {
                        discount = 0;
                        discountPercent = 0;
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

        // Calculate dynamic coupon discount
        if (discountPercent > 0) {
            discount = Math.round(subtotal * (discountPercent / 100));
            if (maxDiscountAmount && maxDiscountAmount > 0 && discount > maxDiscountAmount) {
                discount = maxDiscountAmount;
            }
            if (discount > subtotal) discount = subtotal;
        }

        const totalDiscount = discount + pointsDiscount;

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
                        orderBtn.innerText = 'Thanh toán qua MoMo';
                        orderBtn.classList.add('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                        orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                    } else {
                        orderBtn.innerText = 'Đặt hàng (COD)';
                        orderBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                        orderBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
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
                            calcDesc.innerHTML = `<span style="color:#15803d; font-weight: 600;">✅ Khoảng cách được tính thực tế bằng OpenRouteService API.</span><br>${text}`;
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
            alert(json.message || "Có lỗi xảy ra");
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}
