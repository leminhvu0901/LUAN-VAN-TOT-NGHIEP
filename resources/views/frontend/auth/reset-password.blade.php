<div id="reset-password-modal" data-show-reset-password="{{ (session('show_reset_password') || $errors->has('reset_error') || $errors->has('password')) ? 'true' : 'false' }}">

    <div id="reset-password-overlay"></div>

    <div class="l-modal-wrapper">

        <div id="reset-password-box" class="l-modal-box">

            <button id="close-reset-password" type="button" class="l-close-btn" aria-label="Đóng">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="reset-icon-wrap">
                <div class="reset-icon-bg">
                    <svg width="40" height="40" fill="none" stroke="#1e4a38" stroke-width="2.5" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0110 0v4"></path>
                    </svg>
                </div>
                <div class="reset-icon-badge">
                    <svg width="14" height="14" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <h2 class="reset-main-title">Mật khẩu mới</h2>
            <p class="reset-desc">
                Mật khẩu của bạn phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
            </p>

            <form action="{{ route('reset.password.post') }}" method="post" novalidate>
                @csrf
                
                <div id="reset-password-error-alert" class="l-error-alert {{ $errors->has('reset_error') ? '' : 'hidden' }}">
                    {{ $errors->first('reset_error') }}
                </div>

                <div class="l-form-group">
                    <label class="l-label" for="reset_password">Mật khẩu mới</label>
                    <div class="l-input-wrap">
                        <input type="password" id="reset_password" name="password" class="l-input has-password-toggle"
                            placeholder="Nhập mật khẩu mới" required>
                        <button type="button" class="toggle-password toggle-password-visibility" data-target="reset_password" aria-label="Hiện/ẩn mật khẩu">
                            <i class="fa-regular fa-eye text-base"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="l-form-group">
                    <label class="l-label" for="reset_password_confirmation">Xác nhận mật khẩu</label>
                    <div class="l-input-wrap">
                        <input type="password" id="reset_password_confirmation" name="password_confirmation" class="l-input has-password-toggle"
                            placeholder="Nhập lại mật khẩu mới" required>
                        <button type="button" class="toggle-password toggle-password-visibility" data-target="reset_password_confirmation" aria-label="Hiện/ẩn mật khẩu">
                            <i class="fa-regular fa-eye text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="reset-submit-wrap">
                    <button type="submit" class="reset-submit">Đặt lại mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const resetModal = document.getElementById('reset-password-modal');
    if (resetModal && resetModal.getAttribute('data-show-reset-password') === 'true') {
        resetModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        const firstInput = resetModal.querySelector('input');
        if (firstInput) setTimeout(function () { firstInput.focus(); }, 100);
    }
});

document.addEventListener('click', function (e) {
    const modal = document.getElementById('reset-password-modal');
    if (!modal) return;

    const closeBtn = e.target.closest('#close-reset-password');
    if (closeBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    const overlay = e.target.closest('#reset-password-overlay');
    if (overlay) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }
});
</script>
