(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value;

    function showAlert(message, type) {
        const area = document.getElementById('pos-alert-area');
        area.innerHTML = '<div class="mb-4 p-3 rounded-xl text-sm font-medium ' +
            (type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') +
            '">' + message + '</div>';
        setTimeout(() => { area.innerHTML = ''; }, 4000);
    }

    function formatMoney(value) {
        return Number(value).toLocaleString('vi-VN') + 'đ';
    }

    // ───────────────────────── Tìm/chọn khách hàng (hoặc khách vãng lai) ─────────────────────────

    (function () {
        const searchInput = document.getElementById('pos-customer-search');
        const resultsBox = document.getElementById('pos-customer-results');
        const searchWrap = document.getElementById('pos-customer-search-wrap');
        const selectedBox = document.getElementById('pos-customer-selected');
        const selectedName = document.getElementById('pos-customer-selected-name');
        const selectedPhone = document.getElementById('pos-customer-selected-phone');
        const customerIdInput = document.getElementById('pos-customer-id');
        const clearBtn = document.getElementById('pos-customer-clear');
        const pointsBalanceEl = document.getElementById('pos-customer-points-balance');
        const pointsInput = document.getElementById('pos-points-to-redeem');

        let searchTimer = null;

        function selectCustomer(customer) {
            customerIdInput.value = customer.id;
            selectedName.textContent = customer.name;
            selectedPhone.textContent = customer.phone || 'Chưa có SĐT';
            pointsBalanceEl.textContent = customer.points || 0;
            pointsInput.value = 0;
            pointsInput.max = customer.points || 0;
            selectedBox.classList.remove('hidden');
            searchWrap.classList.add('hidden');
            resultsBox.classList.add('hidden');
            searchInput.value = '';
            refreshPreviewTotal();
        }

        function clearCustomer() {
            customerIdInput.value = '';
            pointsInput.value = 0;
            selectedBox.classList.add('hidden');
            searchWrap.classList.remove('hidden');
            refreshPreviewTotal();
        }

        clearBtn.addEventListener('click', clearCustomer);

        // Kiểm tra AJAX ngay khi lễ tân gõ số điểm — không cần chờ tới lúc bấm "Tạo đơn" mới biết
        // có hợp lệ hay không (debounce 400ms để không gọi API dồn dập từng phím gõ).
        let pointsTimer = null;
        pointsInput.addEventListener('input', function () {
            clearTimeout(pointsTimer);
            pointsTimer = setTimeout(refreshPreviewTotal, 400);
        });

        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(searchTimer);

            if (query.length < 2) {
                resultsBox.classList.add('hidden');
                resultsBox.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(function () {
                fetch(window.posCustomerSearchUrl + '?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        if (!data.results || data.results.length === 0) {
                            resultsBox.innerHTML = '<p class="px-3 py-2 text-sm text-gray-400">Không tìm thấy khách hàng nào.</p>';
                            resultsBox.classList.remove('hidden');
                            return;
                        }
                        data.results.forEach(function (customer) {
                            const row = document.createElement('button');
                            row.type = 'button';
                            row.className = 'w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-50 last:border-0';
                            row.innerHTML = '<span class="font-semibold text-gray-800">' + customer.name + '</span>' +
                                '<span class="text-gray-400 ml-1">' + (customer.phone || '') + '</span>';
                            row.addEventListener('click', function () { selectCustomer(customer); });
                            resultsBox.appendChild(row);
                        });
                        resultsBox.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', function (event) {
            if (!searchWrap.contains(event.target)) {
                resultsBox.classList.add('hidden');
            }
        });
    })();

    // ───────────────────────── Giỏ hàng + tổng tiền xem trước ─────────────────────────

    // Số món trong giỏ ở lần refreshCart() gần nhất — dùng để hiển thị trên thanh giỏ hàng nổi
    // (mobile/tablet). Không gọi lại API riêng cho thanh nổi, tránh 2 nguồn dữ liệu dễ lệch nhau.
    let lastCartItemCount = 0;

    function updateMobileCartBar(total) {
        const bar = document.getElementById('pos-mobile-cart-bar');
        if (!bar) return;

        if (lastCartItemCount <= 0) {
            bar.classList.add('hidden');
            return;
        }

        bar.classList.remove('hidden');
        const countEl = document.getElementById('pos-mobile-cart-count');
        const totalEl = document.getElementById('pos-mobile-cart-total');
        if (countEl) countEl.textContent = lastCartItemCount;
        if (totalEl) totalEl.textContent = formatMoney(total);
    }

    function updatePreviewTotal(subtotal, discount, promotionLabel, shippingFee, finalAmount, gifts, membershipDiscount, pointsDiscount) {
        document.getElementById('pos-cart-subtotal').textContent = formatMoney(subtotal);

        const discountRow = document.getElementById('pos-cart-discount-row');
        if (discount > 0) {
            document.getElementById('pos-cart-discount-label').textContent = promotionLabel || 'Khuyến mãi';
            document.getElementById('pos-cart-discount').textContent = '-' + formatMoney(discount);
            discountRow.classList.remove('hidden');
        } else {
            discountRow.classList.add('hidden');
        }

        const membershipRow = document.getElementById('pos-cart-membership-row');
        if (membershipDiscount > 0) {
            document.getElementById('pos-cart-membership-discount').textContent = '-' + formatMoney(membershipDiscount);
            membershipRow.classList.remove('hidden');
        } else {
            membershipRow.classList.add('hidden');
        }

        const pointsRow = document.getElementById('pos-cart-points-row');
        if (pointsDiscount > 0) {
            document.getElementById('pos-cart-points-discount').textContent = '-' + formatMoney(pointsDiscount);
            pointsRow.classList.remove('hidden');
        } else {
            pointsRow.classList.add('hidden');
        }

        const shippingRow = document.getElementById('pos-cart-shipping-row');
        if (shippingFee > 0) {
            document.getElementById('pos-cart-shipping').textContent = formatMoney(shippingFee);
            shippingRow.classList.remove('hidden');
        } else {
            shippingRow.classList.add('hidden');
        }

        // Hiển thị danh sách quà tặng Mua X Tặng Y
        const giftsRow = document.getElementById('pos-cart-gifts-row');
        const giftsList = document.getElementById('pos-cart-gifts-list');
        if (giftsRow && giftsList) {
            if (gifts && gifts.length > 0) {
                giftsList.innerHTML = gifts.map(function (g) {
                    const stockNote = g.stock_limited
                        ? ' <span class="text-orange-500">(giới hạn kho)</span>'
                        : '';
                    return '<li class="flex items-center justify-between text-xs text-gray-600">' +
                        '<span class="flex items-center gap-1">' +
                        '<span class="text-amber-500">&#127873;</span>' +
                        '<span class="font-medium">' + g.gift_product_name + '</span>' +
                        stockNote +
                        '</span>' +
                        '<span class="font-bold text-amber-600">x' + g.quantity + ' Miễn phí</span>' +
                        '</li>';
                }).join('');
                giftsRow.classList.remove('hidden');
            } else {
                giftsRow.classList.add('hidden');
                giftsList.innerHTML = '';
            }
        }

        document.getElementById('pos-cart-total').textContent = formatMoney(finalAmount);
        updateMobileCartBar(finalAmount);
    }

    function getCurrentOrderType() {
        const checked = document.querySelector('.pos-order-type-option input[name="order_type"]:checked');
        return checked ? checked.value : 'dine_in';
    }

    function refreshPreviewTotal() {
        // Tính trước tổng tiền THẬT (khuyến mãi — mã nhập tay nếu có, hoặc tự động chọn nếu để
        // trống) — hiển thị ngay trên giao diện trước khi lễ tân bấm "Tạo đơn", dùng đúng logic
        // giống lúc tạo đơn thật. POS chỉ tạo đơn tại quầy/mang đi nên không có phí giao hàng.
        const customerIdInput = document.getElementById('pos-customer-id');
        const couponInput = document.getElementById('pos-coupon-code');
        const feedbackEl = document.getElementById('pos-coupon-feedback');
        const pointsInput = document.getElementById('pos-points-to-redeem');
        const pointsFeedbackEl = document.getElementById('pos-points-feedback');

        const params = new URLSearchParams();
        if (customerIdInput && customerIdInput.value) params.set('customer_id', customerIdInput.value);
        if (couponInput && couponInput.value.trim()) params.set('coupon_code', couponInput.value.trim());
        // Điểm chỉ có ý nghĩa khi đã chọn khách hàng — khớp với ràng buộc thật ở OrderService::create().
        if (customerIdInput && customerIdInput.value && pointsInput && Number(pointsInput.value) > 0) {
            params.set('points_to_redeem', pointsInput.value);
        }
        // Chống cache: khi khách/mã/điểm không đổi, URL gọi lại y hệt lần trước (vd. sau khi xóa 1 món
        // trong giỏ) — nếu không có tham số phân biệt, trình duyệt hoặc proxy trung gian có thể trả về
        // response JSON CŨ thay vì gọi lại server, khiến tổng tiền không cập nhật dù giỏ hàng đã đổi.
        params.set('_ts', Date.now());

        fetch(window.posPreviewTotalUrl + '?' + params.toString(), { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                updatePreviewTotal(
                    data.subtotal, data.discount, data.promotion_label, data.shipping_fee || 0, data.final_amount,
                    data.gifts || [], data.membership_discount || 0, data.points_discount || 0
                );

                if (feedbackEl) {
                    if (data.coupon_error) {
                        feedbackEl.textContent = data.coupon_error;
                        feedbackEl.className = 'text-xs mt-1 text-red-600';
                    } else if (couponInput && couponInput.value.trim() && data.promotion_code) {
                        feedbackEl.textContent = 'Áp dụng thành công: -' + formatMoney(data.discount);
                        feedbackEl.className = 'text-xs mt-1 text-emerald-600';
                    } else {
                        feedbackEl.textContent = '';
                    }
                }

                if (pointsFeedbackEl) {
                    if (data.points_error) {
                        pointsFeedbackEl.textContent = data.points_error;
                        pointsFeedbackEl.className = 'text-xs text-red-600';
                    } else if (pointsInput && Number(pointsInput.value) > 0 && data.points_discount > 0) {
                        pointsFeedbackEl.textContent = 'Áp dụng thành công: -' + formatMoney(data.points_discount);
                        pointsFeedbackEl.className = 'text-xs text-emerald-600';
                    } else {
                        pointsFeedbackEl.textContent = '';
                    }
                }
            });
    }

    const couponApplyBtn = document.getElementById('pos-coupon-apply');
    const couponCodeInput = document.getElementById('pos-coupon-code');
    if (couponApplyBtn && couponCodeInput) {
        couponApplyBtn.addEventListener('click', refreshPreviewTotal);
        couponCodeInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                refreshPreviewTotal();
            }
        });
    }

    function describeVariant(item) {
        const parts = [];
        if (item.size_name) parts.push('Size ' + item.size_name);
        if (item.sugar_level !== null && item.sugar_level !== undefined) parts.push('Đường ' + item.sugar_level + '%');
        if (item.ice_level) {
            const iceLabels = { normal: 'Đá chung', full: 'Đá riêng', less: 'Ít đá', none: 'Không đá' };
            parts.push(iceLabels[item.ice_level] || item.ice_level);
        }
        if (item.toppings && item.toppings.length > 0) {
            parts.push('+ ' + item.toppings.map(t => t.name).join(', '));
        }
        return parts.join(' • ');
    }

    function refreshCart() {
        fetch('/cart', { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('pos-cart-items');

                if (!data.items || data.items.length === 0) {
                    // Không tái sử dụng lại node #pos-cart-empty gốc (lấy qua getElementById) — nó đã
                    // bị "container.innerHTML = ''" xóa vĩnh viễn khỏi DOM ngay từ lần đầu giỏ hàng có
                    // sản phẩm, nên ở lần rỗng tiếp theo getElementById trả về null, appendChild(null)
                    // ném lỗi ngầm khiến updatePreviewTotal() phía dưới không bao giờ chạy tới (tổng
                    // tiền bị kẹt lại giá trị cũ dù giỏ đã trống). Dựng lại HTML placeholder trực tiếp
                    // thay vì gắn lại node cũ.
                    container.innerHTML = '<p class="text-sm text-gray-400 text-center py-4" id="pos-cart-empty">Chưa có sản phẩm nào.</p>';
                    lastCartItemCount = 0;
                    updatePreviewTotal(0, 0, null, 0, 0, [], 0, 0);
                    return;
                }

                lastCartItemCount = data.items.reduce(function (sum, item) { return sum + (item.quantity || 0); }, 0);

                container.innerHTML = '';
                data.items.forEach(function (item) {
                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between gap-2 pb-2.5 mb-2.5 border-b border-gray-100 last:border-0 last:pb-0 last:mb-0';
                    const variant = describeVariant(item);
                    // Không dùng 'truncate' cho dòng tùy chọn (size/đường/đá/topping) — lễ tân cần
                    // đọc ĐẦY ĐỦ để xác nhận đúng món trước khi tạo đơn, nhất là khi khách chọn nhiều
                    // topping. Cho phép xuống dòng (break-words) thay vì cắt ngắn kèm "...".
                    row.innerHTML = '<div class="flex-1 min-w-0">' +
                        '<p class="text-base font-bold text-gray-900 truncate">' + item.name + ' <span class="text-primary">x' + item.quantity + '</span></p>' +
                        (variant ? '<p class="text-xs text-gray-500 break-words leading-snug mt-0.5">' + variant + '</p>' : '') +
                        '</div>' +
                        '<span class="text-base font-black text-emerald-600 shrink-0">' + formatMoney(item.unit_price * item.quantity) + '</span>' +
                        '<button type="button" class="pos-remove-btn text-red-400 hover:text-red-600 shrink-0" data-item-id="' + item.id + '">' +
                        '<span class="material-symbols-outlined text-[18px]">close</span></button>';
                    container.appendChild(row);
                });

                refreshPreviewTotal();
            });
    }

    // ───────────────────────── Tìm kiếm + lọc danh mục ─────────────────────────

    let activeCategoryId = '';

    function applyProductFilters() {
        const needle = document.getElementById('pos-product-search').value.trim().toLowerCase();
        document.querySelectorAll('.pos-product-card').forEach(function (card) {
            const matchesName = card.dataset.name.includes(needle);
            const matchesCategory = activeCategoryId === '' || card.dataset.categoryId === activeCategoryId;
            card.style.display = (matchesName && matchesCategory) ? '' : 'none';
        });
    }

    document.getElementById('pos-product-search').addEventListener('input', applyProductFilters);

    document.getElementById('pos-category-filter').addEventListener('click', function (event) {
        const chip = event.target.closest('.pos-category-chip');
        if (!chip) return;

        activeCategoryId = chip.dataset.categoryId;
        document.querySelectorAll('.pos-category-chip').forEach(function (c) {
            const active = c === chip;
            c.classList.toggle('is-active', active);
            c.classList.toggle('border-primary', active);
            c.classList.toggle('bg-primary', active);
            c.classList.toggle('text-white', active);
            c.classList.toggle('border-gray-200', !active);
            c.classList.toggle('text-gray-600', !active);
        });
        applyProductFilters();
    });

    // ───────────────────────── Modal chọn size/topping/đường/đá ─────────────────────────

    const modal = document.getElementById('pos-product-modal');
    let currentProduct = null;
    let selectedSize = null;
    let selectedSugar = '100';
    let selectedIce = 'normal';
    let selectedToppingIds = [];
    let quantity = 1;

    function setActiveChip(container, value) {
        container.querySelectorAll('.pos-chip-btn').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.dataset.value === value);
        });
    }

    function computeModalPrice() {
        let price = currentProduct.base_price;
        if (selectedSize) {
            const size = currentProduct.sizes.find(s => s.size_name === selectedSize);
            if (size) price += size.price_adjustment;
        }
        selectedToppingIds.forEach(function (id) {
            const topping = currentProduct.toppings.find(t => t.id === id);
            if (topping) price += topping.price;
        });
        return price * quantity;
    }

    function renderModalPrice() {
        document.getElementById('pos-modal-price').textContent = formatMoney(computeModalPrice());
    }

    function openProductModal(product) {
        currentProduct = product;
        selectedSize = product.sizes.length > 0 ? product.sizes[0].size_name : null;
        selectedSugar = '100';
        selectedIce = 'normal';
        selectedToppingIds = [];
        quantity = 1;

        document.getElementById('pos-modal-product-name').textContent = product.name;
        document.getElementById('pos-modal-qty').textContent = '1';

        const sizeSection = document.getElementById('pos-modal-size-section');
        const sizesContainer = document.getElementById('pos-modal-sizes');
        sizesContainer.innerHTML = '';
        if (product.sizes.length > 0) {
            sizeSection.classList.remove('hidden');
            product.sizes.forEach(function (size, index) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pos-chip-btn' + (index === 0 ? ' is-active' : '');
                btn.dataset.value = size.size_name;
                btn.textContent = 'Size ' + size.size_name + (size.price_adjustment > 0 ? ' (+' + formatMoney(size.price_adjustment) + ')' : '');
                sizesContainer.appendChild(btn);
            });
        } else {
            sizeSection.classList.add('hidden');
        }

        const toppingSection = document.getElementById('pos-modal-topping-section');
        const toppingsContainer = document.getElementById('pos-modal-toppings');
        toppingsContainer.innerHTML = '';
        if (product.toppings.length > 0) {
            toppingSection.classList.remove('hidden');
            product.toppings.forEach(function (topping) {
                const label = document.createElement('label');
                label.className = 'flex items-center justify-between gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer text-sm';
                label.innerHTML = '<span class="flex items-center gap-2"><input type="checkbox" class="pos-modal-topping-checkbox" data-topping-id="' + topping.id + '"> ' + topping.name + '</span>' +
                    '<span class="text-gray-500 font-semibold">+' + formatMoney(topping.price) + '</span>';
                toppingsContainer.appendChild(label);
            });
        } else {
            toppingSection.classList.add('hidden');
        }

        setActiveChip(document.getElementById('pos-modal-sugar'), selectedSugar);
        setActiveChip(document.getElementById('pos-modal-ice'), selectedIce);
        renderModalPrice();

        modal.classList.remove('hidden');
    }

    function closeProductModal() {
        modal.classList.add('hidden');
        currentProduct = null;
    }

    document.getElementById('pos-product-grid').addEventListener('click', function (event) {
        const btn = event.target.closest('.pos-add-btn');
        if (!btn) return;
        openProductModal(JSON.parse(btn.dataset.product));
    });

    document.getElementById('pos-modal-close').addEventListener('click', closeProductModal);
    document.getElementById('pos-modal-backdrop').addEventListener('click', closeProductModal);

    document.getElementById('pos-modal-sizes').addEventListener('click', function (event) {
        const btn = event.target.closest('.pos-chip-btn');
        if (!btn) return;
        selectedSize = btn.dataset.value;
        setActiveChip(this, selectedSize);
        renderModalPrice();
    });

    document.getElementById('pos-modal-sugar').addEventListener('click', function (event) {
        const btn = event.target.closest('.pos-chip-btn');
        if (!btn) return;
        selectedSugar = btn.dataset.value;
        setActiveChip(this, selectedSugar);
    });

    document.getElementById('pos-modal-ice').addEventListener('click', function (event) {
        const btn = event.target.closest('.pos-chip-btn');
        if (!btn) return;
        selectedIce = btn.dataset.value;
        setActiveChip(this, selectedIce);
    });

    document.getElementById('pos-modal-toppings').addEventListener('change', function (event) {
        const checkbox = event.target.closest('.pos-modal-topping-checkbox');
        if (!checkbox) return;
        const id = parseInt(checkbox.dataset.toppingId, 10);
        if (checkbox.checked) {
            selectedToppingIds.push(id);
        } else {
            selectedToppingIds = selectedToppingIds.filter(t => t !== id);
        }
        renderModalPrice();
    });

    document.getElementById('pos-modal-qty-minus').addEventListener('click', function () {
        if (quantity <= 1) return;
        quantity -= 1;
        document.getElementById('pos-modal-qty').textContent = quantity;
        renderModalPrice();
    });

    document.getElementById('pos-modal-qty-plus').addEventListener('click', function () {
        if (quantity >= 99) return;
        quantity += 1;
        document.getElementById('pos-modal-qty').textContent = quantity;
        renderModalPrice();
    });

    document.getElementById('pos-modal-add').addEventListener('click', function () {
        if (!currentProduct) return;
        const addBtn = this;
        addBtn.disabled = true;

        fetch('/cart/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                product_id: currentProduct.id,
                quantity: quantity,
                size_name: selectedSize,
                sugar_level: selectedSugar,
                ice_level: selectedIce,
                toppings: selectedToppingIds,
            }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success === false) {
                    showAlert(data.message || 'Không thể thêm sản phẩm.', 'error');
                } else {
                    closeProductModal();
                    refreshCart();
                }
            })
            .finally(() => { addBtn.disabled = false; });
    });

    // ───────────────────────── Xóa món khỏi giỏ ─────────────────────────

    document.getElementById('pos-cart-items').addEventListener('click', function (event) {
        const btn = event.target.closest('.pos-remove-btn');
        if (!btn) return;

        fetch('/cart/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ item_id: btn.dataset.itemId }),
        }).then(() => refreshCart());
    });

    // ───────────────────────── Giỏ hàng dạng bottom-sheet trên tablet/điện thoại (dưới lg:) ─────────────────────────
    // Ở lg:+ (desktop) cột giỏ hàng luôn hiển thị cố định (class lg:block trong Blade ép hiện, JS
    // không cần và không nên can thiệp) — các hàm dưới đây chỉ có tác dụng thực sự dưới lg:.

    const cartColumn = document.getElementById('pos-cart-column');
    const cartBackdrop = document.getElementById('pos-cart-backdrop');
    const mobileCartOpenBtn = document.getElementById('pos-mobile-cart-open-btn');
    const cartCloseBtn = document.getElementById('pos-cart-close-btn');

    function openMobileCart() {
        if (!cartColumn) return;
        cartColumn.classList.remove('hidden');
        if (cartBackdrop) cartBackdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileCart() {
        if (!cartColumn) return;
        // Chỉ thực sự đóng dưới lg: — từ lg: trở lên class "lg:block" trong Blade đã ép luôn hiển
        // thị (đúng ý, ở desktop đây là cột cố định chứ không phải bottom-sheet để đóng/mở).
        if (window.innerWidth < 1024) {
            cartColumn.classList.add('hidden');
        }
        if (cartBackdrop) cartBackdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (mobileCartOpenBtn) mobileCartOpenBtn.addEventListener('click', openMobileCart);
    if (cartCloseBtn) cartCloseBtn.addEventListener('click', closeMobileCart);
    if (cartBackdrop) cartBackdrop.addEventListener('click', closeMobileCart);

    // Xoay ngang/resize sang desktop trong lúc bottom-sheet đang mở -> đảm bảo không kẹt khóa cuộn nền.
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            document.body.style.overflow = '';
            if (cartBackdrop) cartBackdrop.classList.add('hidden');
        }
    });

    // ───────────────────────── Toggle nút chọn (loại đơn / thanh toán) ─────────────────────────

    function wireToggleGroup(selector, activeClasses) {
        document.querySelectorAll(selector).forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll(selector).forEach(function (r) {
                    const label = r.closest('label');
                    const active = r.checked;
                    activeClasses.forEach(function (cls) { label.classList.toggle(cls, active); });
                    label.classList.toggle('border-gray-200', !active);
                    label.classList.toggle('text-gray-600', !active);
                });
            });
        });
    }

    wireToggleGroup('.pos-payment-option input[name="payment_method"]', ['border-emerald-500', 'bg-emerald-50', 'text-emerald-700']);
    wireToggleGroup('.pos-order-type-option input[name="order_type"]', ['border-primary', 'bg-primary/5', 'text-primary']);

    // ───────────────────────── Loại đơn: Tại quầy / Mang đi ─────────────────────────
    // order_type chỉ là khái niệm phía JS — đồng bộ xuống field thật gửi lên server (pickup_mode).

    function applyOrderType() {
        document.getElementById('pos-pickup-mode').value = getCurrentOrderType();
        refreshPreviewTotal();
    }

    document.querySelectorAll('.pos-order-type-option input[name="order_type"]').forEach(function (radio) {
        radio.addEventListener('change', applyOrderType);
    });

    // ───────────────────────── Tạo đơn (submit qua fetch, không tải lại trang) ─────────────────────────
    // Trước đây form POST kiểu cổ điển: khi tạo đơn lỗi (vd. điểm vượt số dư, cửa hàng đóng cửa...)
    // trình duyệt redirect-back và tải lại toàn trang — khách hàng vừa chọn/số điểm vừa nhập biến
    // mất trắng vì các input đó không dùng old() để khôi phục, tạo cảm giác "mất thông tin khách
    // hàng" dù thực chất chỉ là lỗi validate bình thường. Submit qua fetch giữ nguyên toàn bộ trạng
    // thái trên trang khi có lỗi (chỉ hiện thông báo qua showAlert(), giống hệt cách /cart/add xử lý
    // lỗi), và điều hướng thật (window.location.href) chỉ khi tạo đơn thành công.
    const orderForm = document.getElementById('pos-order-form');
    const submitBtn = document.getElementById('pos-submit-btn');
    if (orderForm) {
        orderForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (submitBtn) submitBtn.disabled = true;

            fetch(orderForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: new FormData(orderForm),
            })
                .then(function (response) {
                    return response.json().then(function (data) { return { status: response.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status >= 400) {
                        const errors = result.data && result.data.errors ? result.data.errors : {};
                        const firstError = Object.values(errors)[0];
                        showAlert((firstError && firstError[0]) || result.data.message || 'Không thể tạo đơn, vui lòng kiểm tra lại.', 'error');
                        if (submitBtn) submitBtn.disabled = false;
                        return;
                    }

                    if (result.data && result.data.redirect_url) {
                        window.location.href = result.data.redirect_url;
                        return;
                    }

                    if (submitBtn) submitBtn.disabled = false;
                })
                .catch(function () {
                    showAlert('Không thể kết nối máy chủ, vui lòng thử lại.', 'error');
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    refreshCart();
})();
