<div class="modal fade login-modal" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered login-modal-dialog">
        <div class="modal-content login-modal-content forgot-modal-content">
            <div class="forgot-modal-header">
                <button type="button" class="forgot-back-button" data-bs-toggle="modal"
                    data-bs-target="#loginModal" data-bs-dismiss="modal" aria-label="Quay lại đăng nhập">
                    <svg xmlns="http://www.w3.org/2000/svg" class="forgot-back-icon" width="18" height="18"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M15 6l-6 6l6 6" />
                    </svg>
                </button>
                <h3 id="forgotPasswordModalLabel" class="forgot-modal-title">Quên mật khẩu</h3>
                <span class="forgot-header-spacer" aria-hidden="true"></span>
            </div>

            <div class="forgot-icon">
                <img class="forgot-icon-image" src="{{ asset('images/icons/quenmk.png') }}" alt="Quên mật khẩu" />
            </div>

            <p class="forgot-description">
                Vui lòng nhập email hoặc số điện thoại để nhận mã khôi phục mật khẩu.
            </p>

            <form class="forgot-modal-form needs-validation" novalidate>
                <label for="recoveryContact" class="forgot-label">Email hoặc số điện thoại</label>
                <div class="forgot-input-wrap">
                    <input id="recoveryContact" type="text" class="forgot-input"
                        placeholder="Nhập email hoặc số điện thoại" autocomplete="username" required />
                    <span class="forgot-input-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M3 7l9 6l9 -6" />
                        </svg>
                    </span>
                </div>

                <button type="submit" class="forgot-submit">
                    Gửi mã xác nhận
                    <span class="forgot-submit-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12h14" />
                            <path d="M13 6l6 6l-6 6" />
                        </svg>
                    </span>
                </button>
            </form>

            <div class="forgot-back-link">
                <button type="button" class="forgot-back-link-button" data-bs-toggle="modal"
                    data-bs-target="#loginModal" data-bs-dismiss="modal">
                    Quay lại đăng nhập
                </button>
            </div>

            <div class="forgot-divider"></div>

            <p class="forgot-footer">
                Chưa có tài khoản?
                <a href="{{ route('register') }}">Đăng ký</a>
            </p>
        </div>
    </div>
</div>
