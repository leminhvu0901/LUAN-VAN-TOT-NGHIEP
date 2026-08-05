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

});

// Form nhập mã OTP submit thật (tải lại trang) — sai thì quay lại kèm lỗi ($errors->has('otp_error')),
// otp-modal tự mở lại nhờ data-show-otp; đúng thì tùy trường hợp: luồng quên mật khẩu server flash
// show_reset_password=true (modal Đặt lại mật khẩu tự mở, xem reset-password.js), luồng đăng ký
// redirect thật tới trang chủ (đã tự đăng nhập).

// Đóng modal OTP = hủy xác thực: nhấp ra ngoài vùng nền tối cũng submit luôn form ẩn "cancel-otp-form"
// (xem verify-otp.blade.php) để dọn session OTP trên server — điều hướng thật, không còn sendBeacon/fetch.
document.addEventListener('click', function (e) {
    const overlay = e.target.closest('#otp-overlay');
    if (overlay) {
        const cancelForm = document.getElementById('cancel-otp-form');
        if (cancelForm) cancelForm.submit();
    }
});
