/**
 * File Javascript điều khiển giao diện và hành vi cho Modal xác thực mã OTP
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Tự động hiển thị và kích hoạt Modal OTP nếu có cờ chỉ định từ Backend (qua thuộc tính data-show-otp)
    const otpModal = document.getElementById('otp-modal');
    if (otpModal && otpModal.getAttribute('data-show-otp') === 'true') {
        // Hiển thị modal bằng cách thay đổi thuộc tính display
        otpModal.style.display = 'block';
        // Khóa cuộn trang web bên dưới modal để người dùng tập trung vào xác thực
        document.body.style.overflow = 'hidden';
        
        // Tự động focus (nhấp con trỏ) vào ô nhập mã OTP đầu tiên sau một khoảng trễ cực nhỏ
        setTimeout(() => {
            const firstInput = otpModal.querySelector('.otp-input');
            if(firstInput) firstInput.focus();
        }, 100);
    }

    // Lấy tất cả các ô nhập số OTP (mỗi ô nhận 1 chữ số)
    const inputs = document.querySelectorAll('.otp-input');
    
    // Thiết lập các sự kiện tương tác trên từng ô nhập liệu OTP
    inputs.forEach((input, index) => {
        // Sự kiện xảy ra khi người dùng gõ/nhập dữ liệu
        input.addEventListener('input', function(e) {
            // Chỉ cho phép nhập các ký tự số từ 0 đến 9, loại bỏ mọi ký tự khác
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Nếu đã nhập xong 1 chữ số và vẫn chưa tới ô cuối cùng, tự động chuyển con trỏ sang ô tiếp theo
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Sự kiện xảy ra khi người dùng nhấn phím (phục vụ xử lý nút xóa Backspace)
        input.addEventListener('keydown', function(e) {
            // Nếu người dùng bấm phím Backspace, và ô hiện tại đang trống, và không phải ô đầu tiên
            if (e.key === 'Backspace' && !this.value && index > 0) {
                // Quay lại ô nhập liệu phía trước
                inputs[index - 1].focus();
                // Xóa dữ liệu của ô phía trước
                inputs[index - 1].value = '';
            }
        });
        
        // Sự kiện xảy ra khi người dùng thực hiện dán (Paste) dữ liệu từ Clipboard
        input.addEventListener('paste', function(e) {
            // Ngăn chặn hành vi dán mặc định của trình duyệt
            e.preventDefault();
            
            // Lấy chuỗi văn bản từ clipboard, lọc chỉ lấy số và cắt lấy tối đa 4 chữ số đầu tiên
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 4);
            if (pastedData) {
                // Lần lượt phân bổ các chữ số đã copy vào từng ô nhập liệu tương ứng
                for (let i = 0; i < pastedData.length; i++) {
                    if (inputs[i]) {
                        inputs[i].value = pastedData[i];
                        // Tự động di chuyển tiêu điểm focus sang ô tiếp theo
                        if (i < 3) inputs[i + 1].focus();
                    }
                }
            }
        });
    });

    // 2. Logic đồng hồ đếm ngược phục vụ gửi lại mã OTP (Resend Timer)
    let timeLeft = 58; // Thời gian đếm ngược mặc định (58 giây)
    const timerEl = document.getElementById('timer');
    const timerText = document.getElementById('timer-text');
    const resendBtn = document.getElementById('resend-btn');
    let countdownInterval = null;

    // Nếu Modal OTP đang hiển thị, bắt đầu chạy đồng hồ đếm ngược ngay lập tức
    if (otpModal && otpModal.style.display !== 'none') {
        startTimer();
    }

    /**
     * Hàm kích hoạt bộ đếm ngược thời gian gửi lại mã
     */
    function startTimer() {
        // Reset bộ đếm ngược cũ nếu có
        if (countdownInterval) clearInterval(countdownInterval);
        
        timeLeft = 58;
        // Vô hiệu hóa nút gửi lại bằng cách loại bỏ class hoạt động
        resendBtn.classList.remove('active');
        // Hiển thị dòng chữ thông báo thời gian đếm ngược
        timerText.style.display = 'block';
        
        // Cứ mỗi 1 giây (1000ms), trừ đi 1 giây và cập nhật giao diện
        countdownInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                // Khi hết thời gian: dừng đếm ngược, ẩn thông báo đếm ngược và kích hoạt lại nút gửi mã
                clearInterval(countdownInterval);
                timerText.style.display = 'none';
                resendBtn.classList.add('active');
            } else {
                // Định dạng giây hiển thị kiểu hai chữ số (ví dụ: 09 thay vì 9)
                const seconds = timeLeft < 10 ? '0' + timeLeft : timeLeft;
                if(timerEl) timerEl.textContent = '00:' + seconds;
            }
        }, 1000);
    }

    // Cho phép openOtpModal() (gọi khi mở modal bằng JS thuần sau khi đăng ký/quên mật khẩu submit
    // qua fetch, không tải lại trang) khởi động lại đúng bộ đếm ngược này.
    window.startOtpTimer = startTimer;

    // Submit form nhập mã OTP qua fetch — trước đây nhập sai/hết hạn thì tải lại cả trang, phải gõ
    // lại từ đầu. Giờ sai thì modal đứng yên, hiện lỗi tại chỗ; đúng thì tùy trường hợp: luồng quên
    // mật khẩu chuyển thẳng sang modal Đặt lại mật khẩu bằng JS (không tải lại trang, xem
    // reset-password.js), luồng đăng ký điều hướng thật tới trang chủ (đã tự đăng nhập).
    const otpForm = otpModal ? otpModal.querySelector('form') : null;
    if (otpForm) {
        otpForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const btn = otpForm.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(otpForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: new FormData(otpForm),
            })
                .then(function (response) {
                    return response.json().then(function (data) { return { status: response.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status >= 400) {
                        const errors = (result.data && result.data.errors) || {};
                        const firstError = Object.values(errors)[0];
                        const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Mã OTP không chính xác.';
                        const errorEl = document.getElementById('otp-error-alert');
                        if (errorEl) {
                            errorEl.textContent = message;
                            errorEl.classList.remove('hidden');
                        } else {
                            alert(message);
                        }
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (result.data && result.data.show_reset_password) {
                        otpModal.style.display = 'none';
                        if (typeof window.openResetPasswordModal === 'function') window.openResetPasswordModal();
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
                    alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                    if (btn) btn.disabled = false;
                });
        });
    }
});

// Mở modal OTP bằng JS thuần (không tải lại trang) — dùng chung cho cả luồng "Đăng ký" và "Quên mật
// khẩu" ngay sau khi form tương ứng submit thành công qua fetch (xem register.js/forgot-password.js).
// Trước đây phải tải lại trang để server render lại cờ show_otp + session('verify_email'); giờ JS tự
// mở modal + tự điền email vừa nhập, không cần vòng qua server thêm lần nữa.
window.openOtpModal = function (email) {
    const otpModal = document.getElementById('otp-modal');
    if (!otpModal) return;

    const emailDisplay = document.getElementById('otp-email-display');
    if (emailDisplay && email) emailDisplay.textContent = email;

    document.querySelectorAll('.otp-input').forEach(function (input) { input.value = ''; });
    const errorEl = document.getElementById('otp-error-alert');
    if (errorEl) errorEl.classList.add('hidden');

    otpModal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    const firstInput = otpModal.querySelector('.otp-input');
    if (firstInput) setTimeout(function () { firstInput.focus(); }, 100);

    if (typeof window.startOtpTimer === 'function') window.startOtpTimer();
};

// Hàm gửi yêu cầu để xóa session OTP khi người dùng tắt modal hoặc hủy xác thực
function cancelOTPSession() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Ưu tiên sendBeacon: nếu người dùng bấm Đóng rồi F5/điều hướng đi ngay lập tức, một fetch() thường
    // có thể bị trình duyệt hủy giữa chừng trước khi server kịp xóa session, khiến modal OTP hiện lại
    // sau khi tải lại trang (session('verify_email') vẫn còn). sendBeacon được trình duyệt đảm bảo gửi
    // đi dù trang đang unload. Không set được header tùy ý nên gửi _token qua body để CSRF vẫn hợp lệ
    // (Laravel đọc _token từ input trước khi xét tới header X-CSRF-TOKEN).
    if (navigator.sendBeacon) {
        const body = new URLSearchParams({ _token: csrfToken || '' });
        navigator.sendBeacon('/cancel-otp', body);
        return;
    }

    fetch('/cancel-otp', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        // Tải lại trang hoặc cập nhật trạng thái nếu cần thiết
        console.log('OTP Session cleared successfully.');
    })
    .catch(err => console.error('Error clearing OTP session:', err));
}

// 3. Logic đóng Modal khi tương tác với nút đóng (X) hoặc nhấp ra ngoài vùng nền tối (Overlay)
document.addEventListener('click', function(e) {
    const modal = document.getElementById('otp-modal');
    if (!modal) return;

    // Nhấp vào nút có ID close-otp hoặc nằm trong thẻ của nó
    const closeBtn = e.target.closest('#close-otp');
    if (closeBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        // Khôi phục lại trạng thái cuộn trang web bình thường
        document.body.style.overflow = '';
        // Gọi hàm xóa session OTP trên server
        cancelOTPSession();
        return;
    }

    // Nhấp vào vùng nền tối bao quanh modal
    const overlay = e.target.closest('#otp-overlay');
    if (overlay) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        // Gọi hàm xóa session OTP trên server
        cancelOTPSession();
        return;
    }
});
