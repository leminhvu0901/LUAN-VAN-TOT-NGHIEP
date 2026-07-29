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
            if (window.FrontendAlert) {
                window.FrontendAlert.error('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
            } else {
                alert('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
            }
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

    // Nhét nguyên đoạn mã Base64 này vào thẻ input ẩn (hidden) — CẢ 2 bản desktop lẫn mobile, vì đây
    // là 2 <form> riêng biệt (không dùng chung DOM node), submit form nào thì chỉ input NẰM TRONG
    // form đó mới được gửi lên server. Thiếu bước đồng bộ này thì khi khách sửa ảnh đại diện xong bấm
    // "Lưu thay đổi" ở giao diện mobile, ảnh mới sẽ không hề được gửi lên (dù các trường khác vẫn lưu
    // bình thường, hiện thông báo "thành công" gây hiểu lầm là ảnh cũng đã lưu).
    const croppedInputDesktop = document.getElementById('croppedAvatarInput');
    if (croppedInputDesktop) croppedInputDesktop.value = base64Image;
    const croppedInputMobile = document.getElementById('croppedAvatarInputMobile');
    if (croppedInputMobile) croppedInputMobile.value = base64Image;

    closeCropperModal(); // Đóng popup
}

// ==========================================
// 6. CHUYỂN TAB VÀ ĐỔI MẬT KHẨU
// ==========================================

// Hàm điều khiển ẩn/hiện các khung nội dung (Profile, Password)
function showTab(tab) {
    const deskProfile = document.getElementById('desktop-profile-content');
    const deskPass = document.getElementById('desktop-password-content');

    const mobProfile = document.getElementById('mobile-profile-content');
    const mobPass = document.getElementById('mobile-password-content');

    const profileLink = document.getElementById('tab-profile-link');
    const passwordLink = document.getElementById('tab-password-link');

    const headerTitle = document.getElementById('mobile-header-title');
    const backBtn = document.getElementById('mobile-back-btn');

    // 1. Tắt/ẩn toàn bộ các khu vực nội dung
    if (deskProfile) deskProfile.classList.add('hidden');
    if (deskPass) deskPass.classList.add('hidden');

    if (mobProfile) mobProfile.classList.add('hidden');
    if (mobPass) mobPass.classList.add('hidden');

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

    // 3. Mở khung và tô màu nút ứng với tên Tab khách bấm
    if (tab === 'password') {
        if (deskPass) deskPass.classList.remove('hidden');
        if (mobPass) mobPass.classList.remove('hidden');
        activeLink(passwordLink);
        if (headerTitle) headerTitle.textContent = "Đổi mật khẩu";
        window.location.hash = 'password'; // Cập nhật thanh địa chỉ thành xyz.com/profile#password
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
    } else {
        showTab('profile');
    }

    // Logic nút Mắt (Hiển thị / Giấu mật khẩu) giờ dùng chung cho toàn frontend — xem
    // public/js/frontend/layout/main.js (load sẵn ở mọi trang, không riêng gì trang Hồ sơ này nữa).

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

// ==========================================
// 7. SUBMIT FORM QUA FETCH (Cập nhật thông tin / Đổi mật khẩu)
// ==========================================
// Trước đây 2 form này POST cổ điển -> mỗi lần lưu (kể cả khi lỗi) đều tải lại cả trang, mất vị trí
// tab đang xem (showTab) và cảm giác giật. Submit qua fetch giữ nguyên trạng thái trang, chỉ cập
// nhật đúng phần cần thiết (tên/avatar hiển thị, hoặc xóa trắng ô mật khẩu khi đổi thành công).

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Gửi 1 form qua fetch, gọi onSuccess(data) nếu thành công. Lúc lỗi: gọi onError(errors) nếu form đó
 * có truyền vào (để tự hiện lỗi tại đúng vị trí liên quan, vd dưới từng ô nhập), nếu không thì mới
 * dùng FrontendAlert.error() (toast dùng chung toàn frontend, xem main.js) làm phương án dự phòng
 * chung — dùng chung cho cả form "Cập nhật thông tin" lẫn "Đổi mật khẩu" (cả bản desktop/mobile).
 */
function submitProfileForm(form, onSuccess, onError) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: new FormData(form),
    })
        .then(function (response) {
            return response.json().then(function (data) { return { status: response.status, data: data }; });
        })
        .then(function (result) {
            if (result.status >= 400) {
                const errors = (result.data && result.data.errors) || {};
                if (onError) {
                    onError(errors);
                    return;
                }
                const firstError = Object.values(errors)[0];
                const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Có lỗi xảy ra, vui lòng thử lại.';
                if (window.FrontendAlert) {
                    window.FrontendAlert.error(message);
                } else {
                    alert(message);
                }
                return;
            }
            if (onSuccess) onSuccess(result.data);
        })
        .catch(function () {
            if (window.FrontendAlert) {
                window.FrontendAlert.error('Không thể kết nối máy chủ, vui lòng thử lại.');
            } else {
                alert('Không thể kết nối máy chủ, vui lòng thử lại.');
            }
        })
        .finally(function () {
            if (btn) btn.disabled = false;
        });
}

// Ánh xạ tên field validate ở backend (vd "name") -> id của 2 thẻ <small> hiện lỗi (desktop + mobile)
// tương ứng trong form "Cập nhật thông tin" — xem resources/views/frontend/profile.blade.php.
const PROFILE_FIELD_ERROR_IDS = {
    name: ['name-error-desktop', 'name-error-mobile'],
    phone: ['phone-error-desktop', 'phone-error-mobile'],
};

// Ẩn hết các dòng lỗi cũ trước mỗi lần submit mới — tránh lỗi lần trước còn sót lại trên màn hình dù
// lần này đã sửa đúng.
function clearProfileFieldErrors() {
    Object.values(PROFILE_FIELD_ERROR_IDS).forEach(function (ids) {
        ids.forEach(function (id) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = '';
                el.classList.add('hidden');
            }
        });
    });
}

// Hiện lỗi validate ngay bên dưới đúng ô nhập liên quan (cả bản desktop lẫn mobile, đồng bộ như mọi
// chỗ khác trên trang) thay vì alert() chung chung không rõ lỗi ở ô nào.
function showProfileFieldErrors(errors) {
    clearProfileFieldErrors();
    Object.keys(errors).forEach(function (field) {
        const ids = PROFILE_FIELD_ERROR_IDS[field];
        if (!ids) return; // Field không có chỗ hiện lỗi riêng (hiếm gặp) -> bỏ qua, không vỡ layout
        const message = errors[field] && errors[field][0];
        if (!message) return;
        ids.forEach(function (id) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = message;
                el.classList.remove('hidden');
            }
        });
    });
}

function showProfileFieldError(field, message) {
    const ids = PROFILE_FIELD_ERROR_IDS[field] || [];
    ids.forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = message;
            el.classList.remove('hidden');
        }
    });
}

function hideProfileFieldError(field) {
    const ids = PROFILE_FIELD_ERROR_IDS[field] || [];
    ids.forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = '';
            el.classList.add('hidden');
        }
    });
}

// ==========================================
// 8. KIỂM TRA "NGẦM" (live) — báo lỗi ngay khi đang gõ, không cần đợi bấm "Lưu thay đổi".
// Họ tên: kiểm tra thuần JS (độ dài), không cần hỏi server. Số điện thoại: kiểm tra định dạng thuần
// JS ngay lập tức + gọi AJAX (debounce) hỏi server xem SĐT đã bị người khác đăng ký chưa (cái này
// KHÔNG thể biết được nếu chỉ kiểm tra ở trình duyệt, phải hỏi server).
// ==========================================
(function () {
    const MAX_NAME_LENGTH = 30;
    // Giống hệt regex phía backend (ProfileController::update()/checkPhone()) — tránh 2 nơi lệch nhau.
    const PHONE_REGEX = /^(0[3|5|7|8|9])+([0-9]{8})$/;

    ['name-input-desktop', 'name-input-mobile'].forEach(function (id) {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', function () {
            if (input.value.length > MAX_NAME_LENGTH) {
                showProfileFieldError('name', 'Họ và tên tối đa ' + MAX_NAME_LENGTH + ' ký tự.');
            } else {
                hideProfileFieldError('name');
            }
        });
    });

    let phoneCheckTimer = null;
    let phoneCheckToken = 0; // hủy kết quả của lần gõ trước nếu người dùng gõ tiếp nhanh hơn AJAX trả về

    function checkPhoneLive(value) {
        const myToken = ++phoneCheckToken;
        fetch('/profile/check-phone?phone=' + encodeURIComponent(value), {
            headers: { Accept: 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (myToken !== phoneCheckToken) return; // đã có lần gõ mới hơn, bỏ kết quả cũ
                if (data && data.valid === false) {
                    showProfileFieldError('phone', data.message || 'Số điện thoại không hợp lệ.');
                } else {
                    hideProfileFieldError('phone');
                }
            })
            .catch(function () { /* Mất mạng lúc kiểm tra ngầm -> im lặng, server vẫn kiểm tra lại lúc bấm Lưu */ });
    }

    ['phone-input-desktop', 'phone-input-mobile'].forEach(function (id) {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', function () {
            const value = input.value.trim();
            if (phoneCheckTimer) clearTimeout(phoneCheckTimer);

            if (value === '') {
                hideProfileFieldError('phone');
                return;
            }
            if (!PHONE_REGEX.test(value)) {
                // Sai định dạng thì báo ngay, không cần chờ hỏi server làm gì.
                showProfileFieldError('phone', 'Số điện thoại không đúng định dạng.');
                return;
            }
            // Đúng định dạng rồi -> debounce hỏi server xem có bị trùng với tài khoản khác không.
            phoneCheckTimer = setTimeout(function () { checkPhoneLive(value); }, 500);
        });
    });
})();

// Form "Cập nhật thông tin" (desktop + mobile) — action kết thúc bằng "/profile" (không tính
// "/profile/change-password", vì đó là action khác kết thúc bằng "/change-password").
document.querySelectorAll('form[action$="/profile"]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearProfileFieldErrors();
        submitProfileForm(form, function (data) {
            const user = (data && data.user) || {};
            // Cập nhật tên hiển thị ở cả 2 bản desktop/mobile (form nào submit cũng đồng bộ cả 2, vì
            // chỉ 1 trong 2 tab đang hiển thị nhưng tab kia có thể được xem ngay sau khi đổi màn hình).
            const nameDesktop = document.getElementById('profileNameDisplayDesktop');
            const nameMobile = document.getElementById('profileNameDisplayMobile');
            if (nameDesktop && user.name) nameDesktop.textContent = user.name;
            if (nameMobile && user.name) nameMobile.textContent = user.name;

            // Cập nhật avatar ở mọi nơi đang hiển thị trên trang (preview lớn, preview mobile, navbar).
            if (user.avatar_url) {
                ['avatarPreview', 'avatarPreviewMobile'].forEach(function (id) {
                    const img = document.getElementById(id);
                    if (img) img.src = user.avatar_url;
                });
                const navbarAvatar = document.querySelector('.navbar-avatar');
                if (navbarAvatar) navbarAvatar.src = user.avatar_url;
            }

            const successMessage = (data && data.message) || 'Cập nhật thông tin thành công!';
            if (window.FrontendAlert) {
                window.FrontendAlert.success(successMessage);
            } else {
                alert(successMessage);
            }
        }, showProfileFieldErrors);
    });
});

// Form "Đổi mật khẩu" (desktop + mobile) — action kết thúc bằng "/change-password".
document.querySelectorAll('form[action$="/change-password"]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitProfileForm(form, function (data) {
            form.reset(); // Xóa trắng 3 ô mật khẩu sau khi đổi thành công — không để lộ lại trên màn hình
            const successMessage = (data && data.message) || 'Đổi mật khẩu thành công!';
            if (window.FrontendAlert) {
                window.FrontendAlert.success(successMessage);
            } else {
                alert(successMessage);
            }
        });
    });
});
