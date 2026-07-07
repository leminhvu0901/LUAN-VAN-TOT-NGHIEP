'use strict';

(function () {
    // Xử lý menu thả xuống (Dropdown) nhiều cấp
    const dropdownLinks = document.querySelectorAll('.dropdown-menu a.dropdown-toggle');
    dropdownLinks.forEach(function (dropdownLink) {
        dropdownLink.addEventListener('click', function (e) {
            // Nếu menu con chưa được mở, đóng tất cả các menu con khác cùng cấp
            if (!this.nextElementSibling.classList.contains('show')) {
                const parentDropdownMenu = this.closest('.dropdown-menu');
                const currentlyOpenSubMenus = parentDropdownMenu.querySelectorAll('.show');
                currentlyOpenSubMenus.forEach(function (openSubMenu) {
                    openSubMenu.classList.remove('show');
                });
            }

            // Bật/tắt trạng thái hiển thị của menu con hiện tại
            const subMenu = this.nextElementSibling;
            subMenu.classList.toggle('show');

            // Ngăn chặn sự kiện click lan truyền lên trên (tránh đóng menu cha)
            e.stopPropagation();
        });
    });

    // Hàm tăng giá trị (dành cho input số lượng)
    function incrementValue(e) {
        e.preventDefault();
        var target = e.target;
        var fieldName = target.getAttribute('data-field');
        var parent = target.closest('div');
        var inputField = parent.querySelector('input[name="' + fieldName + '"]');
        var currentVal = parseInt(inputField.value, 10) || 0;

        inputField.value = currentVal + 1; // Tăng lên 1
    }

    // Hàm giảm giá trị (dành cho input số lượng)
    function decrementValue(e) {
        e.preventDefault();
        var target = e.target;
        var fieldName = target.getAttribute('data-field');
        var parent = target.closest('div');
        var inputField = parent.querySelector('input[name="' + fieldName + '"]');
        var currentVal = parseInt(inputField.value, 10) || 0;

        if (currentVal > 0) {
            inputField.value = currentVal - 1; // Giảm đi 1 nếu đang lớn hơn 0
        }
    }

    // Gắn sự kiện click cho các nút cộng/trừ số lượng
    document.querySelectorAll('.input-group').forEach(function (group) {
        group.addEventListener('click', function (e) {
            if (e.target.classList.contains('button-plus')) {
                incrementValue(e);
            } else if (e.target.classList.contains('button-minus')) {
                decrementValue(e);
            }
        });
    });

    // Khởi tạo thư viện đánh giá sao (raterJs)
    var starRating1;
    var raterElements = document.querySelectorAll('.rater');

    if (typeof window.raterJs === 'function' && raterElements.length) {
        raterElements.forEach(function (element, index) {
            starRating1 = raterJs({
                starSize: 20,
                element: element,
                rateCallback: function rateCallback(rating, done) {
                    this.setRating(rating); // Cập nhật số sao khi người dùng click
                    done();
                },
            });
        });
    }

    // Khởi tạo thư viện chọn ngày tháng (Flatpickr)
    var flatpickrElements = document.querySelectorAll('.flatpickr');

    if (flatpickrElements.length && typeof window.flatpickr === 'function') {
        flatpickrElements.forEach(function (element) {
            flatpickr(element, {
                disableMobile: true, // Tắt giao diện mobile mặc định của flatpickr
            });
        });
    }

    // Ngăn chặn sự kiện đóng collapse cho các thẻ có class stopevent
    var stopeventElements = document.querySelectorAll('.stopevent');

    if (stopeventElements.length) {
        stopeventElements.forEach(function (element) {
            element.addEventListener('off.bs.collapse.data-api', function (e) {
                e.stopPropagation();
            });
        });
    }

    // Tính năng "Chọn tất cả" cho các checkbox
    const checkAll = document.querySelector('#checkAll');

    if (checkAll) {
        checkAll.addEventListener('click', function () {
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function (checkbox) {
                if (checkbox !== checkAll) {
                    checkbox.checked = checkAll.checked; // Gán trạng thái giống với nút checkAll
                }
            });
        });
    }

    // Hiển thị thông báo (Live Alert Placeholder)
    var liveAlertPlaceholder = document.getElementById('liveAlertPlaceholder');

    if (liveAlertPlaceholder) {
        var alert = function (message, type) {
            // Tạo mã HTML cho thông báo
            var wrapper = document.createElement('div');
            wrapper.innerHTML = `
        <div class="alert alert-${type} alert-dismissible" role="alert">
          <div>${message}</div>
          <button type="button" class="btn-close" aria-label="Close"></button>
        </div>`;
            // Gắn sự kiện đóng thông báo
            var closeBtn = wrapper.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    wrapper.remove();
                });
            }
            liveAlertPlaceholder.append(wrapper);
        };

        // Nút test alert
        var alertTrigger = document.getElementById('liveAlertBtn');
        if (alertTrigger) {
            alertTrigger.addEventListener('click', function () {
                alert('Nice, you triggered this alert message!', 'success');
            });
        }
    }

    // Khởi tạo thanh trượt chọn khoảng giá (noUiSlider)
    var priceRangeSlider = document.getElementById('priceRange');

    if (priceRangeSlider && window.noUiSlider && window.wNumb) {
        noUiSlider.create(priceRangeSlider, {
            connect: true,
            behaviour: 'tap',
            start: [49, 199], // Giá trị khởi tạo
            range: {
                min: [6],
                max: [300],
            },
            format: wNumb({
                decimals: 1,
                thousand: '.',
                prefix: '$',
            }),
        });

        // Cập nhật text hiển thị khoảng giá khi kéo thanh trượt
        var priceRangeValueElement = document.getElementById('priceRange-value');
        priceRangeSlider.noUiSlider.on('update', function (values) {
            priceRangeValueElement.innerHTML = values.join(' - ');
        });
    }

    // Xem trước hình ảnh khi upload file (File Input)
    var fileInputs = document.querySelectorAll('.file-input');

    if (fileInputs.length) {
        fileInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var curElement = input.parentElement.parentElement.querySelector('.image');
                var reader = new FileReader();

                reader.onload = function (e) {
                    curElement.setAttribute('src', e.target.result); // Đổi src ảnh thành ảnh vừa chọn
                };

                reader.readAsDataURL(input.files[0]);
            });
        });
    }

    // Hiển thị thông báo nhỏ ở góc màn hình (Toast)
    const toastTrigger = document.getElementById('liveToastBtn');
    const toastLiveExample = document.getElementById('liveToast');

    if (toastTrigger && toastLiveExample) {
        toastTrigger.addEventListener('click', () => {
            toastLiveExample.classList.add('show');
            toastLiveExample.classList.remove('hide');

            // Tự động ẩn toast sau 5 giây
            setTimeout(() => {
                toastLiveExample.classList.remove('show');
                toastLiveExample.classList.add('hide');
            }, 5000);
        });
    }
})();

// ==========================================
// THƯ VIỆN GẮN THẺ (Tagify)
// ==========================================
const tagsInput = document.querySelector('input[name=tags]');
if (tagsInput && window.Tagify) {
    new Tagify(tagsInput); // Chuyển input văn bản thành ô nhập thẻ (tags)
}

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
                alert(data.message || 'Không thể thêm sản phẩm vào giỏ hàng.');
                return;
            }

            updateCartUI(data); // Cập nhật lại HTML giỏ hàng

            // Tạo hiệu ứng nẩy nẩy (Pop) trên số lượng của giỏ hàng
            var badge = document.getElementById('cart-badge');
            if (badge) {
                badge.classList.remove('badge-pop');
                void badge.offsetWidth;
                badge.classList.add('badge-pop');
            }
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
                alert(data.message || 'Có lỗi xảy ra');
                return;
            }
            updateCartUI(data);
            alert('Đã thêm tất cả sản phẩm yêu thích vào giỏ hàng!');

            // Hiệu ứng nảy giỏ hàng
            var badge = document.getElementById('cart-badge');
            if (badge) {
                badge.classList.remove('badge-pop');
                void badge.offsetWidth;
                badge.classList.add('badge-pop');
            }
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

// Hàm cập nhật giao diện Giỏ hàng sau khi nhận phản hồi từ Backend
const updateCartUI = (data) => {
    if (!data || !data.success) return;

    // 1. Cập nhật con số hiển thị trên icon giỏ hàng của Navbar (chấm đỏ)
    var badge = document.getElementById('cart-badge');
    if (badge) {
        badge.innerText = data.count;
        badge.style.display = data.count > 0 ? '' : 'none'; // Ẩn nếu bằng 0
    }

    // 2. Cập nhật câu phụ đề trên ngăn kéo (ví dụ: "3 sản phẩm")
    var subtitle = document.getElementById('cart-drawer-subtitle');
    if (subtitle) subtitle.innerText = data.count + ' sản phẩm';

    // 3. Cập nhật số tiền tổng cộng phía dưới
    var totalEl = document.getElementById('cart-drawer-total');
    if (totalEl) totalEl.innerText = data.formatted_total;

    // 4. Vẽ lại (Render) danh sách mã HTML các sản phẩm trong giỏ
    var list = document.getElementById('cart-list');
    if (list) {
        if (data.count === 0) {
            list.innerHTML = '<div class="cart-empty-msg"><p>Giỏ hàng của bạn đang trống.</p></div>';
        } else {
            let html = '';
            data.items.forEach(item => {
                let formattedPrice = new Intl.NumberFormat('vi-VN').format(item.unit_price) + 'đ';

                // Xây dựng chuỗi thẻ (Tag) để hiển thị tùy chọn: Kích cỡ, lượng đường, đá, topping
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

                // Nối các topping phụ nếu có
                if (item.toppings && item.toppings.length > 0) {
                    let tops = item.toppings.map(t => t.name).join(', ');
                    optionTags += `<span class="cart-item__tag cart-item__tag--topping">+ ${tops}</span>`;
                }

                // Ghép HTML cho 1 sản phẩm trong giỏ
                html += `
                <div class="cart-item">
                    <img src="/images/${item.image}" alt="${item.name}" onerror="this.src=\'/images/products/placeholder.jpg\'" class="cart-item__img">
                    <div class="cart-item__info">
                        <h4 class="cart-item__name">${item.name}</h4>
                        ${optionTags ? `<div class="cart-item__tags">${optionTags}</div>` : ''}
                        <span class="cart-item__price">${formattedPrice}</span>
                        <div class="cart-item__actions">
                            <div class="cart-item__qty-control">
                                <!-- Nút tăng giảm số lượng -->
                                <button onclick="updateCartItem(${item.id}, ${item.quantity - 1})" class="cart-item__qty-btn">-</button>
                                <span class="cart-item__qty-value">${item.quantity}</span>
                                <button onclick="updateCartItem(${item.id}, ${item.quantity + 1})" class="cart-item__qty-btn">+</button>
                            </div>
                            <button onclick="removeFromCart(${item.id})" class="cart-item__remove-btn">Xóa</button>
                        </div>
                    </div>
                </div>`;
            });
            list.innerHTML = html;
        }
    }
};

// ==========================================
// KÍCH HOẠT BAN ĐẦU (Init)
// ==========================================
// Ngay khi trình duyệt tải xong cấu trúc DOM, thì tự động chạy hàm loadCart() để tải dữ liệu giỏ hàng về.
document.addEventListener('DOMContentLoaded', () => {
    loadCart();
});
