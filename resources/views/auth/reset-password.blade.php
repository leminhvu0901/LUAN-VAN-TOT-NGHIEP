{{-- Khung bao ngoài chứa toàn bộ bố cục trang, giới hạn chiều rộng trên thiết bị lớn --}}
<div class="reset-container">
    {{-- Header của trang gồm nút quay về trang chủ '/' và tiêu đề chính --}}
    <div class="reset-header">
        <a href="/" class="reset-back">
            {{-- Icon mũi tên quay lại --}}
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <h1 class="reset-title">Đặt lại mật khẩu</h1>
    </div>

    {{-- Phần hiển thị Icon đồ họa ổ khóa và dấu tích xanh biểu thị giao diện bảo mật --}}
    <div class="reset-icon-wrap">
        <div class="reset-icon-bg">
            {{-- Icon ổ khóa --}}
            <svg width="40" height="40" fill="none" stroke="#1e4a38" stroke-width="2.5" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0110 0v4"></path>
            </svg>
        </div>
        <div class="reset-icon-badge">
            {{-- Icon dấu tích xanh (Checkmark) --}}
            <svg width="14" height="14" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
    </div>

    {{-- Tiêu đề chính của form và phần văn bản mô tả quy định độ phức tạp mật khẩu --}}
    <h2 class="reset-main-title">Mật khẩu mới</h2>
    <p class="reset-desc">
        Mật khẩu của bạn phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
    </p>

    {{-- Form thực hiện gửi yêu cầu POST mật khẩu mới lên server --}}
    <form action="{{ route('reset.password.post') }}" method="POST" class="reset-form">
        {{-- Token bảo mật tránh tấn công giả mạo yêu cầu chéo CSRF --}}
        @csrf

        {{-- Nhóm nhập mật khẩu mới --}}
        <div class="form-group">
            <label class="form-label" for="password">Mật khẩu mới</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Nhập mật khẩu mới"
                required>
            {{-- Nút bấm ẩn/hiện mật khẩu, kích hoạt hàm JS toggleVisibility --}}
            <button type="button" class="toggle-password" onclick="toggleVisibility('password')">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                    </path>
                </svg>
            </button>
            {{-- Hiển thị thông báo lỗi nếu Laravel Validation trả về lỗi cho trường password --}}
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        {{-- Nhóm nhập lại mật khẩu để xác nhận trùng khớp --}}
        <div class="form-group">
            <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input"
                placeholder="Nhập lại mật khẩu mới" required>
            {{-- Nút bấm ẩn/hiện mật khẩu xác nhận --}}
            <button type="button" class="toggle-password" onclick="toggleVisibility('password_confirmation')">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                    </path>
                </svg>
            </button>
            {{-- Hiển thị thông báo lỗi nếu Laravel Validation trả về lỗi cho trường password_confirmation --}}
            @error('password_confirmation')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        {{-- Phần nút bấm xác nhận cập nhật thông tin --}}
        <div class="reset-submit-wrap">
            <button type="submit" class="reset-submit">Đặt lại mật khẩu</button>
        </div>
    </form>
</div>

{{-- Nhúng mã JS xử lý logic ẩn/hiện mật khẩu (hàm toggleVisibility) --}}
<script src="{{ asset('js/frontend/reset-password.js') }}"></script>