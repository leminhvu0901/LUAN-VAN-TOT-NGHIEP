// =========================================================================
// XỬ LÝ CHUNG CHO FORM THÊM/SỬA TÀI KHOẢN NHÂN VIÊN (STAFF ACCOUNTS FORM)
// =========================================================================
document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPlaceholder = document.getElementById('avatar-placeholder');

    // 1. Xử lý tải ảnh đại diện và xem trước (Preview)
    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            // Khống chế kích thước ảnh đại diện nhân viên tối đa 2MB
            if (file.size > 2 * 1024 * 1024) {
                if (window.AdminAlert) {
                    window.AdminAlert.error('Kích thước ảnh không được vượt quá 2MB.', 'Lỗi tải ảnh');
                } else {
                    alert('Kích thước ảnh không được vượt quá 2MB.');
                }
                this.value = ''; // Xóa file lỗi khỏi input
                return;
            }

            // Đọc file ảnh Base64 hiển thị lên khung tròn xem trước
            const reader = new FileReader();
            reader.onload = function (e) {
                avatarPreview.src = e.target.result;
                avatarPreview.classList.remove('hidden');
                if (avatarPlaceholder) {
                    avatarPlaceholder.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // 2. Bật/Tắt ẩn hiện mật khẩu (Mắt thần hiển thị)
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('.material-symbols-outlined');

            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility_off';
            }
        });
    });
});
