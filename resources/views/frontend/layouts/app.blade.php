<!DOCTYPE html>
<html lang="vi">

<head>
    {{-- @yield('title'): Nơi các trang con sẽ truyền tiêu đề (title) vào. Nếu không truyền, mặc định sẽ là 'Happy Tea'
    --}}
    <title>@yield('title', 'Happy Tea')</title>

    {{-- @include: Nạp nội dung từ file resources/views/components/head.blade.php vào đây (thường chứa các thẻ meta,
    link CSS) --}}
    @include('frontend.components.head')
</head>

{{-- @yield('body_class'): Cho phép trang con tự thêm class CSS riêng vào thẻ body (vd: class 'home-page' cho trang chủ)
--}}

<body class="@yield('body_class')">

    {{-- Nạp thanh điều hướng phía trên (Header/Navbar) - hiển thị chung cho mọi trang --}}
    @include('frontend.components.navbar')

    {{-- Phần thân chính của trang web --}}
    <main>
        {{-- @yield('content'): Điểm nối vô cùng quan trọng. Nội dung chính của các trang con (home, products,
        profile...) sẽ được "nhét" vào đúng vị trí này --}}
        @yield('content')
    </main>

    {{-- Nạp chân trang (Footer) - hiển thị chung ở cuối mọi trang --}}
    @include('frontend.components.footer')

    {{-- ===== MODALS XÁC THỰC ===== --}}
    {{-- Các file này chứa mã HTML của các hộp thoại (popup) bị ẩn đi. Chúng chỉ hiện lên khi JavaScript kích hoạt. Nạp
    sẵn ở layout để trang nào cũng có thể gọi popup đăng nhập --}}
    @include('frontend.auth.login')
    @include('frontend.auth.register')
    @include('frontend.auth.forgot-password')
    @include('frontend.auth.verify-otp')
    @include('frontend.auth.reset-password')

    {{-- Bong bóng nổi Giỏ hàng + Zalo --}}
    @include('frontend.components.floating-bubbles')



    {{-- ===== SCRIPTS ===== --}}
    <!-- Thư viện Javascript bên thứ 3 (Thanh cuộn mượt, Slider/Carousel) -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>
    {{-- SweetAlert2 — trước đây CHỈ nạp khi tài khoản bị khóa (is_active==0), nên window.FrontendAlert
    (main.js) và mọi chỗ khác lỡ gọi Swal trực tiếp trên toàn frontend đều lặng lẽ không hiện được gì
    cho >99% tài khoản bình thường. Nạp sẵn ở đây cho MỌI trang để dùng được toast thông báo thống nhất. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- CÁC HÀM JS TOÀN CỤC DÙNG CHUNG CHO TOÀN BỘ FRONTEND (thông báo toast, giỏ hàng, yêu thích) -
    trước đây nằm ở file riêng public/js/frontend/layout/main.js, giờ nhúng thẳng vào đây (layout dùng
    chung mọi trang khách hàng) để mọi trang đều có sẵn window.FrontendAlert/addToCart/toggleFavorite...
    mà không cần khai báo lại. --}}
    <script>
    'use strict';

    // ---------------------------------------------------------
    // THÔNG BÁO TOÀN CỤC CHO TOÀN BỘ FRONTEND (khách hàng) — toast góc trên-phải, tự tắt sau vài giây,
    // dùng SweetAlert2 đã nạp sẵn ở MỌI trang. Cùng tinh thần với window.AdminAlert bên khu vực quản
    // trị/nhân viên — tránh mỗi trang tự viết Swal.fire()/alert() rải rác khác kiểu nhau.
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
        // Hộp thoại nhập liệu có xác nhận (vd lý do hủy đơn) - thay cho prompt() gốc của trình duyệt.
        // Dùng class CSS THUẦN (fa-prompt-*, xem users.css), KHÔNG dùng Tailwind: script này chạy trên
        // MỌI trang, kể cả những trang không nạp Tailwind (xem head.blade.php).
        // Trả về Promise giống hệt Swal.fire() gốc: { isConfirmed, value }.
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
        // Hộp thoại xác nhận đồng ý/hủy đơn giản (không cần nhập liệu, khác prompt() ở trên) - dùng lại
        // đúng bộ class fa-prompt-* (chỉ bỏ phần input) để đồng bộ giao diện. Trả về Promise { isConfirmed }.
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

    // ==========================================
    // CÁC HÀM TOÀN CỤC: YÊU THÍCH (WISHLIST)
    // Gắn vào 'window' để có thể gọi trực tiếp từ HTML (onclick)
    // ==========================================

    // Hàm gửi API để BẬT/TẮT yêu thích (Dùng cho nút bấm ngoài trang chủ)
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
                // Nếu Server báo 401 (Chưa đăng nhập), chuyển hướng tới trang Đăng nhập
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

                    // Tạo hiệu ứng rung/nhún (Pop animation) cho nút tim
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

    // Hàm gửi API để XÓA sản phẩm khỏi danh sách yêu thích
    window.removeFromWishlist = function (productId) {
        // Lấy mã CSRF Token bảo mật của Laravel để cho phép gọi API POST
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

                    // Tìm và tắt biểu tượng tim (đổi màu về xám) trên Trang chủ nếu có
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

    // Hàm cập nhật giao diện (UI) của ngăn kéo Yêu Thích sau khi gọi API
    window.updateWishlistUI = function (data) {
        if (!data || !data.success) return;

        // 1. Cập nhật các con số đếm trên thanh điều hướng (counters)
        var badge = document.getElementById('wishlist-badge');
        var subtitle = document.querySelector('.wl-drawer__subtitle');
        if (badge) badge.innerText = data.count;
        if (subtitle) subtitle.innerText = data.count + ' sản phẩm đã lưu';

        // 2. Cập nhật lại danh sách HTML hiển thị trong ngăn kéo
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

                    // Vẽ số sao đánh giá động (Tô màu sao tùy thuộc điểm avg_rating)
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
                                <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <button class="wl-item__cart-btn" ${cartBtnDisabled} ${cartBtnOnclick}>
                                <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                                </svg>
                            </button>
                        </div>
                    </div>`;
                });
                listBody.innerHTML = html; // Đổ toàn bộ mã HTML mới vào ngăn kéo
            }
        }
    };

    // ==========================================
    // CÁC HÀM TOÀN CỤC: GIỎ HÀNG (CART LOGIC)
    // ==========================================

    // Hàm thêm một sản phẩm vào giỏ
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
                return res.json();
            })
            .then(data => {
                if (!data) return;

                // Thông báo khi backend từ chối (ví dụ: sản phẩm hết hàng)
                if (data.success === false) {
                    if (window.FrontendAlert) window.FrontendAlert.error(data.message || 'Không thể thêm sản phẩm vào giỏ hàng.'); else alert(data.message || 'Không thể thêm sản phẩm vào giỏ hàng.');
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

    // Hàm cập nhật số lượng của 1 sản phẩm trong giỏ (Kích hoạt khi bấm nút + / -)
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
                return res.json();
            })
            .then(data => {
                if (!data) return;
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
        fetch('/cart')
            .then(res => res.json())
            .then(updateCartUI)
            .catch(err => console.error(err));
    };

    // Hiệu ứng phản hồi trực quan khi thêm sản phẩm vào giỏ hàng thành công — nẩy số trên badge của
    // navbar VÀ rung bong bóng nổi "Giỏ hàng" (floating-bubbles.blade.php, nếu trang đang có). Tách hàm
    // dùng chung vì addToCart() và addAllToCart() đều cần gọi y hệt nhau, tránh lặp code 2 nơi.
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

        // 1. Cập nhật con số hiển thị trên icon giỏ hàng của Navbar (đỏm đỏ)
        var badge = document.getElementById('cart-badge');
        if (badge) {
            badge.innerText = data.count;
            badge.classList.toggle('cart-badge--hidden', !(data.count > 0));
        }

        // 1b. Cùng logic ở trên nhưng cho badge trên bong bóng nổi "Giỏ hàng"
        var floatingBadge = document.getElementById('floating-cart-badge');
        if (floatingBadge) {
            floatingBadge.innerText = data.count;
            floatingBadge.classList.toggle('floating-bubble__badge--hidden', !(data.count > 0));
        }

        // 2. Cập nhật câu phụ đề trên ngăn kéo (ví dụ: "3 sản phẩm")
        var subtitle = document.getElementById('cart-drawer-subtitle');
        if (subtitle) subtitle.innerText = data.count + ' sản phẩm';

        // 3. Vẽ lại (Render) danh sách mã HTML các sản phẩm trong giỏ
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
                // Giỏ hàng không hiện quà combo nữa: quà chỉ xuất hiện ở trang thanh toán sau khi khách
                // tự bấm chọn mã combo, nên ở đây chưa biết khách sẽ chọn mã nào.

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

    // ==========================================
    // KÍCH HOẠT BAN ĐẦU (Init)
    // ==========================================
    document.addEventListener('DOMContentLoaded', () => {
        loadCart();
    });

    // Nút back/forward của trình duyệt có thể được phục vụ từ bfcache — khôi phục nguyên DOM đã lưu
    // trong bộ nhớ thay vì tải lại trang từ server, khiến dữ liệu/thông báo cũ hiện lại sai. Ép tải lại
    // thật khi phát hiện trang được phục hồi từ bfcache (cùng cách sửa với khu vực admin/nhân viên).
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // ==========================================
    // CHỌN SẢN PHẨM TRONG GIỎ HÀNG ĐỂ THANH TOÁN
    // ==========================================

    // Đồng bộ các ô tích chọn món trong giỏ với tổng tiền hiển thị bên dưới
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

    // ==========================================
    // NÚT MẮT HIỆN/ẨN MẬT KHẨU — dùng chung cho MỌI ô mật khẩu ở MỌI trang (đăng nhập, đăng ký, đặt
    // lại mật khẩu, đổi mật khẩu trong hồ sơ...), chỉ cần thêm class .toggle-password-visibility +
    // data-target="id-của-input-mật-khẩu" là có ngay, không cần viết lại JS riêng cho từng trang.
    document.querySelectorAll('.toggle-password-visibility').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;

            var iconSpan = this.querySelector('.material-symbols-outlined');
            if (input.type === 'password') {
                input.type = 'text';
                if (iconSpan) iconSpan.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                if (iconSpan) iconSpan.textContent = 'visibility';
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

    {{-- Hiện toast cho flash message session('success') dùng CHUNG cho mọi trang - tránh phải tự thêm
    @if(session('success')) + gọi FrontendAlert riêng lẻ ở từng trang (dễ quên, dễ sót như đã xảy ra
    với trang đăng ký/xác nhận OTP). Chỉ áp dụng cho 'success': 'error' KHÔNG đưa vào đây vì
    orders/index.blade.php và checkout.blade.php đã tự hiển thị session('error') riêng bằng banner
    tĩnh, thêm toast nữa sẽ bị hiện trùng 2 lần. --}}
    @if(session('success'))
        <script>window.FrontendAlert.success(@json(session('success')), 3500);</script>
    @endif

    {{-- CÁC SCRIPT VENDORS / PLUGINS DÙNG CHUNG TOÀN FRONTEND --}}
    <script>
    // 1. Bộ đếm ngược thời gian (Countdown Timer)
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

    // 2. Tiny Slider (TNS) sản phẩm & modal
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

    // 3. Phóng to ảnh chi tiết sản phẩm khi hover
    function zoom(e) {
        var zoomer = e.currentTarget;
        var offsetX = e.offsetX ? e.offsetX : e.touches[0].pageX;
        var offsetY = e.offsetY ? e.offsetY : e.touches[0].pageY;
        var x = (offsetX / zoomer.offsetWidth) * 100;
        var y = (offsetY / zoomer.offsetHeight) * 100;
        zoomer.style.backgroundPosition = x + '% ' + y + '%';
    }

    // 4. Khởi tạo Swiper Carousel tự động
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
                try { breakpoints = JSON.parse(breakpointsData); } catch (error) {}
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

    // 5. Client Form Validation
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


    {{-- @stack('scripts'): Khác với @yield, @stack cho phép nhiều trang con cùng "đẩy" (@push) các đoạn mã script bổ
    sung vào vị trí này. Rất hữu ích khi một trang cụ thể cần chạy một file JS riêng biệt --}}
    @stack('scripts')

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