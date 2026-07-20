// Dùng chung cho staff-accounts/create.blade.php và staff-accounts/edit.blade.php
document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPlaceholder = document.getElementById('avatar-placeholder');

    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                if (window.AdminAlert) {
                    window.AdminAlert.error('Kích thước ảnh không được vượt quá 2MB.', 'Lỗi tải ảnh');
                } else {
                    alert('Kích thước ảnh không được vượt quá 2MB.');
                }
                this.value = '';
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
        });
    }

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
