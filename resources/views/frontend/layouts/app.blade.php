<!DOCTYPE html>
<html lang="vi">

<head>

    <title>@yield('title', 'Happy Tea')</title>


    @include('frontend.components.head')
</head>

<body class="@yield('body_class')">

    {{-- Nạp thanh điều hướng phía trên, hiển thị chung --}}
    @include('frontend.components.navbar')

    {{-- Phần thân chính của trang web --}}
    <main>

        @yield('content')
    </main>

    {{-- Nạp chân trang, hiển thị chung ở cuối mọi trang --}}
    @include('frontend.components.footer')

    {{-- Modals xác thực --}}

    @include('frontend.auth.login')
    @include('frontend.auth.register')
    @include('frontend.auth.forgot-password')
    @include('frontend.auth.verify-otp')
    @include('frontend.auth.reset-password')

    {{-- Bong bóng nổi Giỏ hàng + Zalo --}}
    @include('frontend.components.floating-bubbles')

    {{-- Scripts --}}
    <!-- Thư viện Javascript bên thứ 3 -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Các hàm tiện ích toàn cục frontend --}}
    <script>
        'use strict';

        // Thông báo toàn cục frontend
        window.FrontendAlert = {
            success: function (message, timer = 3000) {
                if (typeof Swal === 'undefined') { alert(message); return; }
                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: timer,
                    timerProgressBar: true,
                    didOpen: function (toast) {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    },
                }).fire({ icon: 'success', title: message });
            },
            error: function (message, timer = 4000) {
                if (typeof Swal === 'undefined') { alert(message); return; }
                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: timer,
                    timerProgressBar: true,
                    didOpen: function (toast) {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    },
                }).fire({ icon: 'error', title: message });
            },
            // Hộp thoại nhập liệu có xác nhận
            prompt: function (options) {
                options = options || {};
                const title = options.title || '';
                const text = options.text || '';
                const placeholder = options.placeholder || '';
                const defaultValue = options.defaultValue || '';
                const minLength = options.minLength || 0;
                const confirmText = options.confirmText || 'Xác nhận';

                if (typeof Swal === 'undefined') {
                    const value = prompt(text ? title + '\n\n' + text : title, defaultValue);
                    return Promise.resolve({ isConfirmed: value !== null, value: value });
                }

                return Swal.fire({
                    title: title,
                    text: text,
                    input: 'text',
                    inputValue: defaultValue,
                    inputPlaceholder: placeholder,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Hủy',
                    buttonsStyling: false,
                    returnFocus: false,
                    customClass: {
                        popup: 'fa-prompt-popup',
                        title: 'fa-prompt-title',
                        htmlContainer: 'fa-prompt-text',
                        input: 'fa-prompt-input',
                        validationMessage: 'fa-prompt-validation',
                        actions: 'fa-prompt-actions',
                        confirmButton: 'fa-prompt-confirm',
                        cancelButton: 'fa-prompt-cancel',
                    },
                    inputValidator: function (value) {
                        if (!value || !value.trim()) return 'Vui lòng nhập thông tin.';
                        if (minLength && value.trim().length < minLength) {
                            return 'Vui lòng nhập ít nhất ' + minLength + ' ký tự.';
                        }
                    },
                });
            },
            // Hộp thoại xác nhận đồng ý hoặc hủy
            confirm: function (options) {
                options = options || {};
                const title = options.title || 'Xác nhận';
                const text = options.text || '';
                const confirmText = options.confirmText || 'Xác nhận';

                if (typeof Swal === 'undefined') {
                    return Promise.resolve({ isConfirmed: window.confirm(text ? title + '\n\n' + text : title) });
                }

                return Swal.fire({
                    title: title,
                    text: text,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Hủy',
                    buttonsStyling: false,
                    returnFocus: false,
                    customClass: {
                        popup: 'fa-prompt-popup',
                        title: 'fa-prompt-title',
                        htmlContainer: 'fa-prompt-text',
                        actions: 'fa-prompt-actions',
                        confirmButton: 'fa-prompt-confirm',
                        cancelButton: 'fa-prompt-cancel',
                    },
                });
            },
        };

        // Gửi yêu cầu bật hoặc tắt yêu thích sản phẩm
        window.toggleFavorite = function (btn, productId) {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            fetch('/favorite/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                },
                body: JSON.stringify({ product_id: productId })
            })
                .then(res => {
                    // Nếu Server báo 401, chuyển hướng tới trang Đăng nhập
                    if (res.status === 401 || res.redirected) {
                        window.location.href = '/login';
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.success) {
                        // Đảo class 'is-active' để nút tim đổi màu đỏ/xám
                        btn.classList.toggle('is-active');

                        // Tạo hiệu ứng rung/nhún cho nút tim
                        btn.classList.remove('badge-pop');
                        void btn.offsetWidth; // Cú pháp ép trình duyệt vẽ lại (reflow) để chạy lại animation
                        btn.classList.add('badge-pop');

                        // Cập nhật lại ngăn kéo Yêu Thích với dữ liệu mới nhất
                        if (typeof updateWishlistUI === 'function') {
                            updateWishlistUI(data);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        };

        // Hàm gửi API để xóa sản phẩm khỏi danh sách yêu thích
        window.removeFromWishlist = function (productId) {
            // Lấy mã CSRF Token bảo mật của Laravel để cho phép gọi API post
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            fetch('/favorite/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                },
                body: JSON.stringify({ product_id: productId }) // Gửi ID sản phẩm cần xóa
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success) {
                        updateWishlistUI(data); // Cập nhật lại ngăn kéo

                        // Tìm và tắt biểu tượng tim trên Trang chủ nếu có
                        var heartBtn = document.querySelector('.home-prod-card__wishlist[data-id="' + productId + '"]');
                        if (heartBtn) {
                            heartBtn.classList.remove('is-active');
                        }

                        // Tìm và tắt biểu tượng tim trên Trang chi tiết sản phẩm nếu có
                        var pdHeartBtn = document.querySelector('#pd-wishlist-btn[data-id="' + productId + '"]');
                        if (pdHeartBtn) {
                            pdHeartBtn.classList.remove('is-active');
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        };

        // Hàm cập nhật giao diện của ngăn kéo Yêu Thích sau khi gọi API
        window.updateWishlistUI = function (data) {
            if (!data || !data.success) return;

            // Cập nhật số đếm trên navbar
            var badge = document.getElementById('wishlist-badge');
            var subtitle = document.querySelector('.wl-drawer__subtitle');
            if (badge) badge.innerText = data.count;
            if (subtitle) subtitle.innerText = data.count + ' sản phẩm đã lưu';

            // Cập nhật danh sách yêu thích
            var listBody = document.getElementById('wishlist-list');
            if (listBody) {
                if (data.count === 0) {
                    // Nếu rỗng thì hiện câu thông báo
                    listBody.innerHTML = '<div class="wl-empty-msg"><p>Bạn chưa lưu sản phẩm nào.</p></div>';
                } else {
                    // Nếu có dữ liệu, dùng vòng lặp nối chuỗi HTML
                    let html = '';
                    data.items.forEach(item => {
                        let formattedPrice = new Intl.NumberFormat('vi-VN').format(item.base_price) + 'đ';

                        // Vẽ số sao đánh giá động
                        let avgRating = parseFloat(item.avg_rating) || 0;
                        let roundedRating = Math.round(avgRating);
                        let starsHtml = '';
                        for (let s = 1; s <= 5; s++) {
                            if (s <= roundedRating) {
                                starsHtml += '<span class="wl-item__star--active">★</span>';
                            } else {
                                starsHtml += '<span class="wl-item__star--inactive">★</span>';
                            }
                        }
                        let ratingText = avgRating > 0 ? avgRating.toFixed(1) : 'Chưa có';

                        // Nối thẻ HTML của từng sản phẩm
                        let isOos = item.is_active == 0;
                        let imgWrapClass = isOos ? 'wl-item__img-wrap wl-item__img-wrap--oos' : 'wl-item__img-wrap';
                        let oosBadge = isOos ? '<span class="wl-item__oos-badge">Hết hàng</span>' : '';
                        let cartBtnDisabled = isOos ? 'disabled title="Sản phẩm đã hết hàng"' : 'title="Thêm vào giỏ"';
                        let cartBtnOnclick = isOos ? '' : `onclick="addToCart(${item.id})"`;

                        // Nối thẻ HTML của từng sản phẩm
                        html += `
                    <div class="wl-item">
                        <div class="${imgWrapClass}">
                            <img src="/images/${item.image}" alt="${item.name}" onerror="this.src='/images/products/placeholder.jpg'">
                            ${oosBadge}
                        </div>
                        <div class="wl-item__info">
                            <p class="wl-item__name">${item.name}</p>
                            <div class="wl-item__rating">
                                <span class="wl-item__stars">${starsHtml}</span>
                                <span class="wl-item__rating-value">${ratingText}</span>
                            </div>
                            <span class="wl-item__price">${formattedPrice}</span>
                        </div>
                        <div class="wl-item__actions">
                            <button title="Xóa khỏi yêu thích" class="wl-item__remove-btn" onclick="removeFromWishlist(${item.id})">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <button class="wl-item__cart-btn" ${cartBtnDisabled} ${cartBtnOnclick}>
                                <i class="fa-solid fa-cart-shopping"></i>
                            </button>
                        </div>
                    </div>`;
                    });
                    listBody.innerHTML = html; // Đổ toàn bộ mã HTML mới vào ngăn kéo
                }
            }
        };

        // Thêm sản phẩm vào giỏ hàng
        window.addToCart = function (productId, quantity = 1, options = {}) {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity,
                    size_name: options.size_name || null,
                    sugar_level: options.sugar_level || null,
                    ice_level: options.ice_level || null,
                    toppings: options.toppings || []
                })
            })
                .then(res => {
                    // Xử lý báo lỗi chưa đăng nhập
                    if (res.status === 401) {
                        const loginModal = document.getElementById('login-modal');
                        // Nếu có Popup đăng nhập thì hiện lên, không thì đẩy về trang /login
                        if (loginModal) {
                            loginModal.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            window.location.href = '/login';
                        }
                        return;
                    }
                    return res.json().then(data => ({ ok: res.ok, data }));
                })
                .then(result => {
                    if (!result) return;
                    const { ok, data } = result;

                    // Thông báo khi backend từ chối
                    if (!ok || data.success === false) {
                        const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Không thể thêm sản phẩm vào giỏ hàng.');
                        if (window.FrontendAlert) window.FrontendAlert.error(msg); else alert(msg);
                        return;
                    }

                    updateCartUI(data); // Cập nhật lại HTML giỏ hàng
                    triggerCartAddedAnimation(); // Hiệu ứng nẩy số + rung bong bóng giỏ hàng
                })
                .catch(err => console.error(err));
        };

        // Hàm thêm TẤT CẢ sản phẩm yêu thích vào giỏ
        window.addAllToCart = function () {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            fetch('/cart/add-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                }
            })
                .then(res => {
                    if (res.status === 401) {
                        const loginModal = document.getElementById('login-modal');
                        if (loginModal) {
                            loginModal.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            window.location.href = '/login';
                        }
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (!data) return;
                    if (data.success === false) {
                        if (window.FrontendAlert) window.FrontendAlert.error(data.message || 'Có lỗi xảy ra'); else alert(data.message || 'Có lỗi xảy ra');
                        return;
                    }
                    updateCartUI(data);
                    if (window.FrontendAlert) window.FrontendAlert.success('Đã thêm tất cả sản phẩm yêu thích vào giỏ hàng!'); else alert('Đã thêm tất cả sản phẩm yêu thích vào giỏ hàng!');
                    triggerCartAddedAnimation(); // Hiệu ứng nẩy số + rung bong bóng giỏ hàng
                })
                .catch(err => console.error(err));
        };

        // Hàm cập nhật số lượng của 1 sản phẩm trong giỏ
        window.updateCartItem = function (itemId, quantity) {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            // Nếu số lượng giảm xuống nhỏ hơn 1 thì gọi hàm Xóa luôn
            if (quantity < 1) {
                removeFromCart(itemId);
                return;
            }

            fetch('/cart/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                },
                body: JSON.stringify({ item_id: itemId, quantity: quantity })
            })
                .then(res => {
                    if (res.status === 401) {
                        const loginModal = document.getElementById('login-modal');
                        if (loginModal) {
                            loginModal.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            window.location.href = '/login';
                        }
                        return;
                    }
                    return res.json().then(data => ({ ok: res.ok, data }));
                })
                .then(result => {
                    if (!result) return;
                    const { ok, data } = result;

                    // Hiện thông báo lỗi khi backend từ chối (vd: vượt giới hạn 10 ly)
                    if (!ok || data.success === false) {
                        const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Không thể cập nhật số lượng.');
                        if (window.FrontendAlert) window.FrontendAlert.error(msg); else alert(msg);
                        return;
                    }

                    updateCartUI(data);
                })
                .catch(err => console.error(err));
        };

        // Hàm xóa hẳn 1 sản phẩm khỏi giỏ
        window.removeFromCart = function (itemId) {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            fetch('/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                },
                body: JSON.stringify({ item_id: itemId })
            })
                .then(res => {
                    if (res.status === 401) {
                        const loginModal = document.getElementById('login-modal');
                        if (loginModal) {
                            loginModal.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            window.location.href = '/login';
                        }
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (!data) return;
                    updateCartUI(data);
                })
                .catch(err => console.error(err));
        };

        // Hàm gọi API lấy dữ liệu giỏ hàng ngay khi trang vừa tải xong
        window.loadCart = function () {
            // Đánh dấu đây là request nền. Nhờ vậy việc cập nhật badge trên trang
            // checkout không bị hiểu nhầm là người dùng đã rời luồng "Mua lại".
            fetch('/cart', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(updateCartUI)
                .catch(err => console.error(err));
        };

        // Hiệu ứng phản hồi trực quan khi thêm sản phẩm vào giỏ
        function triggerCartAddedAnimation() {
            var badge = document.getElementById('cart-badge');
            if (badge) {
                badge.classList.remove('badge-pop');
                void badge.offsetWidth; // buộc trình duyệt reflow để animation chạy lại được từ đầu
                badge.classList.add('badge-pop');
            }

            var floatingCartBtn = document.getElementById('floating-cart-btn');
            if (floatingCartBtn) {
                floatingCartBtn.classList.remove('floating-bubble--shake');
                void floatingCartBtn.offsetWidth;
                floatingCartBtn.classList.add('floating-bubble--shake');
            }
        }

        // Hàm cập nhật giao diện Giỏ hàng sau khi nhận phản hồi từ Backend
        const updateCartUI = (data) => {
            if (!data || !data.success) return;

            // Cập nhật số lượng trên icon giỏ hàng
            var badge = document.getElementById('cart-badge');
            if (badge) {
                badge.innerText = data.count;
                badge.classList.toggle('cart-badge--hidden', !(data.count > 0));
            }

            // Cập nhật badge giỏ hàng nổi
            var floatingBadge = document.getElementById('floating-cart-badge');
            if (floatingBadge) {
                floatingBadge.innerText = data.count;
                floatingBadge.classList.toggle('floating-bubble__badge--hidden', !(data.count > 0));
            }

            // Cập nhật số lượng sản phẩm
            var subtitle = document.getElementById('cart-drawer-subtitle');
            if (subtitle) subtitle.innerText = data.count + ' sản phẩm';

            // Render danh sách sản phẩm trong giỏ
            var list = document.getElementById('cart-list');
            var selectAllBar = document.getElementById('cart-select-all-bar');
            if (list) {
                if (data.count === 0) {
                    list.innerHTML = '<div class="cart-empty-msg"><p>Giỏ hàng của bạn đang trống.</p></div>';
                    if (selectAllBar) selectAllBar.style.display = 'none';
                    var checkoutBtn = document.getElementById('cart-checkout-btn');
                    var totalEl2 = document.getElementById('cart-drawer-total');
                    var countEl2 = document.getElementById('cart-selected-item-count');
                    var hintEl = document.getElementById('cart-selected-count-hint');
                    if (checkoutBtn) { checkoutBtn.disabled = true; checkoutBtn.style.opacity = '0.5'; checkoutBtn.style.cursor = 'not-allowed'; }
                    if (totalEl2) totalEl2.innerText = '0đ';
                    if (countEl2) countEl2.innerText = '0';
                    if (hintEl) hintEl.innerText = '0 đã chọn';
                } else {
                    const unselectedIds = {};
                    document.querySelectorAll('.cart-item-checkbox').forEach(function (chk) {
                        if (!chk.checked) unselectedIds[chk.getAttribute('data-item-id')] = true;
                    });

                    let html = '';
                    data.items.forEach(item => {
                        let formattedPrice = new Intl.NumberFormat('vi-VN').format(item.unit_price) + 'đ';

                        let optionTags = '';
                        if (item.size_name) {
                            optionTags += `<span class="cart-item__tag cart-item__tag--size">Size ${item.size_name}</span>`;
                        }
                        const sugarMap = { '100': '100% đường', '70': '70% đường', '50': '50% đường', '0': 'Không đường' };
                        const iceMap = { 'full': 'Đá riêng', 'normal': 'Đá chung', 'less': 'Ít đá', 'none': 'Không đá' };
                        if (item.sugar_level && sugarMap[item.sugar_level]) {
                            optionTags += `<span class="cart-item__tag cart-item__tag--sugar">${sugarMap[item.sugar_level]}</span>`;
                        }
                        if (item.ice_level && iceMap[item.ice_level]) {
                            optionTags += `<span class="cart-item__tag cart-item__tag--ice">${iceMap[item.ice_level]}</span>`;
                        }

                        if (item.toppings && item.toppings.length > 0) {
                            let tops = item.toppings.map(t => t.name).join(', ');
                            optionTags += `<span class="cart-item__tag cart-item__tag--topping">+ ${tops}</span>`;
                        }

                        let itemTotal = item.unit_price * item.quantity;

                        html += `
                    <div class="cart-item" data-item-id="${item.id}">
                        <label class="cart-item__checkbox-wrap" title="Chọn sản phẩm này">
                            <input type="checkbox" class="cart-item-checkbox"
                                   data-item-id="${item.id}"
                                   data-item-price="${itemTotal}"
                                   ${unselectedIds[item.id] ? '' : 'checked'}
                                   onchange="syncCartSelectionUI()">
                        </label>
                        <img src="/images/${item.image}" alt="${item.name}" onerror="this.src='/images/products/placeholder.jpg'" class="cart-item__img">
                        <div class="cart-item__info">
                            <h4 class="cart-item__name">${item.name}</h4>
                            ${optionTags ? `<div class="cart-item__tags">${optionTags}</div>` : ''}
                            <span class="cart-item__price">${formattedPrice}</span>
                            <div class="cart-item__actions">
                                <div class="cart-item__qty-control">
                                    <button onclick="updateCartItem(${item.id}, ${item.quantity - 1})" class="cart-item__qty-btn">-</button>
                                    <span class="cart-item__qty-value">${item.quantity}</span>
                                    <button onclick="updateCartItem(${item.id}, ${item.quantity + 1})" class="cart-item__qty-btn">+</button>
                                </div>
                                <button onclick="removeFromCart(${item.id})" class="cart-item__remove-btn">Xóa</button>
                            </div>
                        </div>
                    </div>`;
                    });
                    // Giỏ hàng không hiện quà combo nữa: quà chỉ xuất hiện ở
                    list.innerHTML = html;

                    if (selectAllBar) selectAllBar.style.display = 'flex';

                    var masterChk = document.getElementById('cart-select-all-chk');
                    if (masterChk) {
                        masterChk.onchange = function () {
                            document.querySelectorAll('.cart-item-checkbox').forEach(function (chk) {
                                chk.checked = masterChk.checked;
                                var cartItem = chk.closest('.cart-item');
                                if (cartItem) cartItem.classList.toggle('is-unselected', !masterChk.checked);
                            });
                            syncCartSelectionUI();
                        };
                    }

                    syncCartSelectionUI();
                }
            }
        };

        // Khởi tạo giỏ hàng khi tải xong trang
        document.addEventListener('DOMContentLoaded', function () {
            loadCart();
        });

        // Tải lại trang khi khôi phục từ bộ nhớ đệm trình duyệt
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Đồng bộ trạng thái chọn sản phẩm trong giỏ hàng
        window.syncCartSelectionUI = function () {
            var checkboxes = document.querySelectorAll('.cart-item-checkbox');
            var masterChk = document.getElementById('cart-select-all-chk');
            var checkoutBtn = document.getElementById('cart-checkout-btn');
            var totalEl = document.getElementById('cart-drawer-total');
            var countEl = document.getElementById('cart-selected-item-count');
            var hintEl = document.getElementById('cart-selected-count-hint');

            var selectedTotal = 0;
            var selectedCount = 0;
            var allChecked = true;
            var anyChecked = false;

            checkboxes.forEach(function (chk) {
                var cartItem = chk.closest('.cart-item');
                if (chk.checked) {
                    selectedTotal += parseFloat(chk.getAttribute('data-item-price') || 0);
                    selectedCount++;
                    anyChecked = true;
                    if (cartItem) cartItem.classList.remove('is-unselected');
                } else {
                    allChecked = false;
                    if (cartItem) cartItem.classList.add('is-unselected');
                }
            });

            if (masterChk) {
                masterChk.checked = allChecked;
                masterChk.indeterminate = anyChecked && !allChecked;
            }

            if (totalEl) {
                totalEl.innerText = new Intl.NumberFormat('vi-VN').format(selectedTotal) + 'đ';
            }

            if (countEl) countEl.innerText = selectedCount;
            if (hintEl) hintEl.innerText = selectedCount + ' đã chọn';

            if (checkoutBtn) {
                if (selectedCount > 0) {
                    checkoutBtn.disabled = false;
                    checkoutBtn.style.opacity = '1';
                    checkoutBtn.style.cursor = 'pointer';
                } else {
                    checkoutBtn.disabled = true;
                    checkoutBtn.style.opacity = '0.5';
                    checkoutBtn.style.cursor = 'not-allowed';
                }
            }

            var removeSelectedBtn = document.getElementById('cart-remove-selected-btn');
            if (removeSelectedBtn) removeSelectedBtn.disabled = selectedCount === 0;
        };

        // Lấy id các món khách đang tích chọn để thanh toán
        function getSelectedCartItemIds() {
            var ids = [];
            document.querySelectorAll('.cart-item-checkbox:checked').forEach(function (chk) {
                var id = parseInt(chk.getAttribute('data-item-id'), 10);
                if (!isNaN(id)) ids.push(id);
            });
            return ids;
        }

        // Xóa các món đang được tích chọn khỏi giỏ
        window.removeSelectedFromCart = function () {
            var ids = getSelectedCartItemIds();
            if (ids.length === 0) return;

            window.FrontendAlert.confirm({
                title: 'Xóa sản phẩm đã chọn?',
                text: 'Xóa ' + ids.length + ' sản phẩm đang được chọn khỏi giỏ hàng.',
                confirmText: 'Xóa',
            }).then(function (result) {
                if (!result.isConfirmed) return;

                var token = document.querySelector('meta[name="csrf-token"]');
                if (!token) return;

                fetch('/cart/remove-many', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token.getAttribute('content')
                    },
                    body: JSON.stringify({ item_ids: ids })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data) return;
                        updateCartUI(data);
                    })
                    .catch(function (err) { console.error(err); });
            });
        };

        // Xóa sạch toàn bộ giỏ hàng
        window.clearCart = function () {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            window.FrontendAlert.confirm({
                title: 'Xóa toàn bộ giỏ hàng?',
                text: 'Toàn bộ sản phẩm trong giỏ hàng sẽ bị xóa.',
                confirmText: 'Xóa tất cả',
            }).then(function (result) {
                if (!result.isConfirmed) return;

                fetch('/cart/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token.getAttribute('content')
                    },
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data) return;
                        updateCartUI(data);
                    })
                    .catch(function (err) { console.error(err); });
            });
        };

        // Gửi danh sách món đã tích lên server rồi chuyển sang trang thanh toán
        window.cartProceedToCheckout = function () {
            var ids = getSelectedCartItemIds();
            if (ids.length === 0) return;

            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) {
                window.location.href = '/checkout';
                return;
            }

            var checkoutBtn = document.getElementById('cart-checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.disabled = true;
                checkoutBtn.style.opacity = '0.7';
                checkoutBtn.innerText = 'Đang xử lý...';
            }

            fetch('/cart/set-selected', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content')
                },
                body: JSON.stringify({ selected_item_ids: ids })
            })
                .then(function (res) {
                    if (res.status === 401) {
                        var loginModal = document.getElementById('login-modal');
                        if (loginModal) {
                            loginModal.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        } else {
                            window.location.href = '/login';
                        }
                        return null;
                    }
                    return res.json();
                })
                .then(function (data) {
                    if (!data) return;
                    if (data.success) {
                        window.location.href = '/checkout';
                    } else {
                        console.error('set-selected error', data);
                        window.location.href = '/checkout';
                    }
                })
                .catch(function (err) {
                    console.error('cartProceedToCheckout error:', err);
                    window.location.href = '/checkout';
                });
        };

        // Bật tắt ẩn hiện mật khẩu dùng chung cho mọi form
        document.querySelectorAll('.toggle-password-visibility').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;

                var iconSpan = this.querySelector('.material-symbols-outlined');
                var iconFa = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (iconSpan) iconSpan.textContent = 'visibility_off';
                    if (iconFa) { iconFa.classList.remove('fa-eye'); iconFa.classList.add('fa-eye-slash'); }
                } else {
                    input.type = 'password';
                    if (iconSpan) iconSpan.textContent = 'visibility';
                    if (iconFa) { iconFa.classList.remove('fa-eye-slash'); iconFa.classList.add('fa-eye'); }
                }
            });
        });

        // Chặn copy/cắt nội dung trong các ô mật khẩu để tránh lộ mật khẩu ra clipboard.
        ['copy', 'cut', 'contextmenu'].forEach(function (eventName) {
            document.addEventListener(eventName, function (e) {
                if (e.target && e.target.matches && e.target.matches('.has-password-toggle')) {
                    e.preventDefault();
                }
            });
        });
    </script>


    @if(session('success'))
        <script>window.FrontendAlert.success(@json(session('success')), 3500);</script>
    @endif

    {{-- Script vendors / plugins dùng chung --}}
    <script>
        // Bộ đếm ngược thời gian
        document.querySelectorAll('[data-countdown]').forEach(function (element) {
            var finalDate = element.getAttribute('data-countdown');
            // Đồng hồ đếm ngược cho các chương trình khuyến mãi có hạn
            function updateCountdown() {
                var now = new Date().getTime();
                var distance = new Date(finalDate) - now;
                if (distance <= 0) {
                    clearInterval(interval);
                    element.innerHTML = 'Hết thời gian';
                    return;
                }
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                element.innerHTML =
                    '<span class="countdown-section"><span class="countdown-amount hover-up">' + days + '</span><span class="countdown-period"> ngày </span></span>' +
                    '<span class="countdown-section"><span class="countdown-amount hover-up">' + hours + '</span><span class="countdown-period"> giờ </span></span>' +
                    '<span class="countdown-section"><span class="countdown-amount hover-up">' + minutes + '</span><span class="countdown-period"> phút </span></span>' +
                    '<span class="countdown-section"><span class="countdown-amount hover-up">' + seconds + '</span><span class="countdown-period"> giây </span></span>';
            }
            updateCountdown();
            var interval = setInterval(updateCountdown, 1000);
        });

        // Tiny Slider sản phẩm & modal
        if (typeof tns === 'function') {
            if (document.querySelectorAll('.productModal').length > 0) {
                tns({
                    container: '#productModal', items: 1, startIndex: 0,
                    navContainer: '#productModalThumbnails', navAsThumbnails: true,
                    autoplay: false, autoplayTimeout: 1500, swipeAngle: false, speed: 1500, controls: false, autoplayButtonOutput: false, loop: false
                });
            }
            if (document.querySelectorAll('.product').length > 1) {
                tns({
                    container: '#product', items: 1, startIndex: 0,
                    navContainer: '#productThumbnails', navAsThumbnails: true,
                    autoplay: false, autoplayTimeout: 1500, swipeAngle: false, speed: 1500, controls: false, autoplayButtonOutput: false
                });
            }
        }

        // Phóng to ảnh chi tiết sản phẩm khi hover
        function zoom(e) {
            var zoomer = e.currentTarget;
            var offsetX = e.offsetX ? e.offsetX : e.touches[0].pageX;
            var offsetY = e.offsetY ? e.offsetY : e.touches[0].pageY;
            var x = (offsetX / zoomer.offsetWidth) * 100;
            var y = (offsetY / zoomer.offsetHeight) * 100;
            zoomer.style.backgroundPosition = x + '% ' + y + '%';
        }

        // Khởi tạo Swiper Carousel tự động
        function initializeSwiperCarousels() {
            if (typeof Swiper !== 'function') return;
            const swiperContainers = document.querySelectorAll('.swiper-container');
            swiperContainers.forEach((swiperContainer) => {
                const speed = swiperContainer.getAttribute('data-speed') || 400;
                const spaceBetween = swiperContainer.getAttribute('data-space-between') || 100;
                const paginationEnabled = swiperContainer.getAttribute('data-pagination') === 'true';
                const navigationEnabled = swiperContainer.getAttribute('data-navigation') === 'true';
                const autoplayEnabled = swiperContainer.getAttribute('data-autoplay') === 'true';
                const autoplayDelay = swiperContainer.getAttribute('data-autoplay-delay') || 3000;
                const paginationType = swiperContainer.getAttribute('data-pagination-type') || 'bullets';
                const effect = swiperContainer.getAttribute('data-effect') || 'slide';

                const breakpointsData = swiperContainer.getAttribute('data-breakpoints');
                let breakpoints = {};
                if (breakpointsData) {
                    try { breakpoints = JSON.parse(breakpointsData); } catch (error) { }
                }

                const swiperOptions = { speed: parseInt(speed), spaceBetween: parseInt(spaceBetween), breakpoints: breakpoints, effect: effect };
                if (effect === 'fade') { swiperOptions.fadeEffect = { crossFade: true }; }
                if (paginationEnabled) {
                    const paginationEl = swiperContainer.querySelector('.swiper-pagination');
                    if (paginationEl) {
                        swiperOptions.pagination = { el: paginationEl, type: paginationType, dynamicBullets: true, clickable: true };
                    }
                }
                if (navigationEnabled) {
                    swiperOptions.navigation = { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' };
                }
                if (autoplayEnabled) {
                    swiperOptions.autoplay = { delay: parseInt(autoplayDelay) };
                }
                new Swiper(swiperContainer, swiperOptions);
            });
        }
        document.addEventListener('DOMContentLoaded', initializeSwiperCarousels);

        // Validate form phía client
        (() => {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>


    @stack('scripts')

    <!-- tài khoản của người dùng đang đăng nhập nhưng vừa bị Admin KHÓA -->
    @if (Auth::check() && Auth::user()->is_active == 0)
        <link rel="stylesheet" href="{{ asset('css/frontend/users.css') }}">

        {{-- Đẩy dữ liệu an toàn từ PHP sang JS --}}
        @php
            $lockedUserData = [
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'reason' => Auth::user()->lock_reason
            ];
        @endphp
        <script>window.lockedUserData = @json($lockedUserData);</script>
    @endif

</body>

</html>