document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPlaceholder = document.getElementById('avatar-placeholder');

    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Kiểm tra dung lượng (Max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    if (window.AdminAlert) {
                        window.AdminAlert.error('Kích thước ảnh không được vượt quá 2MB.', 'Lỗi tải ảnh');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Lỗi', 'Kích thước ảnh không được vượt quá 2MB.', 'error');
                    } else {
                        alert('Kích thước ảnh không được vượt quá 2MB.');
                    }
                    this.value = ''; // Reset input
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('hidden');
                    if (avatarPlaceholder) {
                        avatarPlaceholder.classList.add('hidden');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Validate Name Length
    const nameInput = document.getElementById('name');
    const nameError = document.getElementById('name-error');

    if (nameInput && nameError) {
        nameInput.addEventListener('input', function () {
            if (this.value.length >= 100) {
                nameError.textContent = 'Tên không được vượt quá 100 ký tự.';
                nameError.classList.remove('hidden');
            } else {
                nameError.classList.add('hidden');
            }
        });
    }

    // Validate Email Format
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    
    // Regex kiểm tra định dạng email (Giới hạn các đuôi phổ biến như .com, .vn, .net...)
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i;

    if (emailInput && emailError) {
        emailInput.addEventListener('input', function () {
            if (this.value.length > 0 && !emailRegex.test(this.value)) {
                emailError.textContent = 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).';
                emailError.classList.remove('hidden');
            } else {
                emailError.classList.add('hidden');
            }
        });
    }

    // Toggle Password Visibility
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('span');
            
            if (input && input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
                icon.classList.add('text-indigo-600');
            } else if (input) {
                input.type = 'password';
                icon.textContent = 'visibility_off';
                icon.classList.remove('text-indigo-600');
            }
        });
    });

    // Validate Password Match
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const passwordMatchError = document.getElementById('password-match-error');

    function checkPasswordMatch() {
        if (passwordConfirmInput.value.length > 0 && passwordInput.value !== passwordConfirmInput.value) {
            passwordMatchError.textContent = 'Xác nhận mật khẩu không khớp.';
            passwordMatchError.classList.remove('hidden');
        } else {
            passwordMatchError.classList.add('hidden');
        }
    }

    if (passwordInput && passwordConfirmInput && passwordMatchError) {
        passwordInput.addEventListener('input', checkPasswordMatch);
        passwordConfirmInput.addEventListener('input', checkPasswordMatch);
    }

    // Validate Points (Max 900,000)
    const pointsInput = document.getElementById('points');
    const pointsError = document.getElementById('points-error');

    if (pointsInput && pointsError) {
        pointsInput.addEventListener('input', function () {
            const pointsValue = parseInt(this.value, 10);
            if (pointsValue > 900000) {
                pointsError.textContent = 'Điểm tích lũy không được vượt quá 900.000.';
                pointsError.classList.remove('hidden');
            } else {
                pointsError.classList.add('hidden');
            }
        });
    }
});
