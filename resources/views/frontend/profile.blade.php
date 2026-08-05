@extends('frontend.layouts.app')

{{-- Khai báo class CSS riêng biệt cho thẻ body của trang này để tránh xung đột với các trang khác --}}
@section('body_class', 'profile-body')

@section('content')

    @php
        // Đăng nhập Google lưu avatar là URL đầy đủ (https://lh3.googleusercontent.com/...), còn avatar tự
        // tải lên chỉ lưu tên file trong storage cục bộ -> phải phân biệt, không thể luôn ghép
        // asset('images/avatars/'.avatar) (khớp cách các trang admin đã xử lý ở str_starts_with pattern).
        $userAvatarUrl = Auth::user()->avatar
            ? avatar_url(Auth::user()->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=006e01&color=fff';
    @endphp

    <!-- ============================================== -->
    <!-- 1. GIAO DIỆN TRÊN MÁY TÍNH (DESKTOP VIEW)      -->
    <!-- Chỉ hiển thị từ màn hình kích thước trung bình (md) trở lên -->
    <!-- ============================================== -->
    <div
        class="hidden md:flex min-h-screen bg-background text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container">

        <!-- Cột Menu Điều hướng bên trái (SideNavBar) -->
        <aside class="w-[280px] flex-shrink-0 bg-tertiary-fixed border-r border-outline-variant flex flex-col py-stack_lg">
            <div class="px-6 mb-8">
                <div class="flex items-center gap-3 mb-4">
                    {{-- Hiển thị Ảnh đại diện (Avatar) hình tròn của người dùng đang đăng nhập --}}
                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary bg-white">
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="{{ $userAvatarUrl }}">
                    </div>
                    <div>
                        {{-- Tên người dùng và Hạng thành viên --}}
                        <h3 class="font-label-md text-label-md text-on-surface">{{ Auth::user()->name }}</h3>
                        <p class="text-xs text-on-surface-variant">
                            @switch(Auth::user()->membership_level ?? 'new')
                                @case('silver')
                                    Thành viên hạng Bạc
                                @break

                                @case('gold')
                                    Thành viên hạng Vàng
                                @break

                                @case('diamond')
                                    Thành viên Kim Cương
                                @break

                                @default
                                    Thành viên Mới
                            @endswitch
                        </p>
                    </div>
                </div>
            </div>

            {{-- Các tab chức năng bên Desktop: onclick="showTab(...)" dùng JS để ẩn/hiện các tab tương ứng --}}
            <nav class="flex-1">
                <a id="tab-profile-link"
                    class="bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md"
                    href="#profile" onclick="showTab('profile'); return false;">
                    <span class="material-symbols-outlined material-filled">person</span>
                    Thông tin tài khoản
                </a>
                <a id="tab-password-link"
                    class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md"
                    href="#password" onclick="showTab('password'); return false;">
                    <span class="material-symbols-outlined">lock</span>
                    Đổi mật khẩu
                </a>
                <a class="text-error hover:bg-error-container/20 px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md"
                    href="{{ route('logout') }}">
                    <span class="material-symbols-outlined">logout</span>
                    Đăng xuất
                </a>
            </nav>
        </aside>

        <!-- Khu vực nội dung chính bên phải (Main Content Area) -->
        <main class="flex-1 p-stack_lg">
            <div class="max-w-4xl mx-auto">

                {{-- Hiển thị thông báo thành công màu xanh (nếu có từ Session Redirect) --}}
                @if (session('success'))
                    <div class="bg-[#c1e9d5] border border-[#0aad0a] text-[#005301] px-4 py-3 rounded-xl mb-6 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- 1.1 TAB THÔNG TIN CÁ NHÂN (Desktop) --}}
                <div id="desktop-profile-content">
                    <div class="mb-stack_lg">
                        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Thông tin cá nhân</h1>
                        <p class="font-body-md text-body-md text-on-surface-variant">Quản lý thông tin hồ sơ của bạn để bảo
                            mật tài khoản</p>
                    </div>

                    {{-- Form cập nhật Hồ sơ cá nhân (Dùng Multipart form-data để upload ảnh đại diện) --}}
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-stack_lg">

                            <!-- Phần bên trái: Sửa ảnh đại diện (Avatar) -->
                            <div class="lg:col-span-1">
                                <div
                                    class="bg-white rounded-xl border border-outline-variant p-stack_lg flex flex-col items-center justify-center text-center shadow-sm h-full">
                                    <div class="relative group mb-stack_md">
                                        {{-- Preview hình ảnh Avatar hiện tại --}}
                                        <div
                                            class="w-40 h-40 rounded-full overflow-hidden border-4 border-surface-container p-1 ring-2 ring-primary/20 bg-white">
                                            <img id="avatarPreview" alt="Large User Avatar"
                                                class="w-full h-full object-cover rounded-full" src="{{ $userAvatarUrl }}">
                                        </div>
                                        {{-- Nút bút chì màu xanh: Nhấp vào để kích hoạt hành động chọn file từ input ẩn --}}
                                        <button type="button"
                                            class="absolute bottom-1 right-1 bg-primary text-on-primary w-10 h-10 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform active:scale-95"
                                            onclick="document.getElementById('avatarInput').click()">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        {{-- Input chọn file ảnh ẩn --}}
                                        <input type="file" name="avatar" id="avatarInput" accept="image/*"
                                            class="hidden" onchange="previewAvatar(event)">
                                        {{-- Input ẩn lưu chuỗi ảnh Base64 sau khi cắt bằng thư viện Cropper.js --}}
                                        <input type="hidden" name="cropped_avatar" id="croppedAvatarInput">
                                    </div>
                                    <h3 id="profileNameDisplayDesktop"
                                        class="font-headline-md text-headline-md text-on-surface">{{ Auth::user()->name }}
                                    </h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant mb-stack_md">Thành viên từ
                                        {{ Auth::user()->created_at ? Auth::user()->created_at->format('Y') : '2023' }}</p>

                                    {{-- Thống kê nhanh đơn hàng và điểm thưởng --}}
                                    <div class="flex gap-2 w-full mt-auto">
                                        <div class="flex-1 bg-tertiary-fixed rounded-lg py-2">
                                            <span class="block font-bold text-primary text-lg">{{ $ordersCount }}</span>
                                            <span class="text-xs text-on-tertiary-fixed-variant">Đơn hàng</span>
                                        </div>
                                        <div class="flex-1 bg-secondary-container rounded-lg py-2">
                                            <span
                                                class="block font-bold text-on-secondary-container text-lg">{{ Auth::user()->points ?? 0 }}</span>
                                            <span class="text-xs text-on-secondary-container">Điểm tích lũy</span>
                                        </div>
                                    </div>
                                    @error('avatar')
                                        <small class="text-error mt-2 block text-center">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Phần bên phải: Các ô nhập văn bản (Form Fields) -->
                            <div class="lg:col-span-2 space-y-stack_lg">
                                <section
                                    class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full">
                                    <div class="space-y-stack_md h-full flex flex-col justify-between">
                                        <div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-stack_md mb-stack_md">
                                                {{-- Ô nhập Họ tên --}}
                                                <div class="space-y-1">
                                                    <label
                                                        class="font-label-md text-label-md text-on-surface-variant px-1">Họ
                                                        và tên</label>
                                                    <input id="name-input-desktop" name="name" maxlength="30"
                                                        class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-transform"
                                                        type="text" value="{{ old('name', Auth::user()->name) }}"
                                                        required>
                                                    {{-- Luôn render sẵn (không @if/@error) + toggle bằng class "hidden" — để profile.js tìm thấy
                                            và điền lỗi vào đây khi submit qua fetch (AJAX không tải lại trang nên @error không tự
                                            kích hoạt được, chỉ có tác dụng cho lần tải trang đầu/submit cổ điển không JS). --}}
                                                    <small id="name-error-desktop"
                                                        class="text-error mt-1 block {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</small>
                                                </div>
                                                {{-- Ô nhập Số điện thoại --}}
                                                <div class="space-y-1">
                                                    <label
                                                        class="font-label-md text-label-md text-on-surface-variant px-1">Số
                                                        điện thoại</label>
                                                    <input id="phone-input-desktop" name="phone"
                                                        class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-transform"
                                                        type="tel"
                                                        value="{{ old('phone', Auth::user()->phone ?? '') }}">
                                                    <small id="phone-error-desktop"
                                                        class="text-error mt-1 block {{ $errors->has('phone') ? '' : 'hidden' }}">{{ $errors->first('phone') }}</small>
                                                </div>
                                            </div>
                                            {{-- Ô hiển thị Email (Bị khóa không cho sửa đổi trực tiếp) --}}
                                            <div class="space-y-1">
                                                <label
                                                    class="font-label-md text-label-md text-on-surface-variant px-1">Email</label>
                                                <input
                                                    class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none opacity-70"
                                                    type="email" value="{{ Auth::user()->email }}" disabled>
                                            </div>
                                        </div>

                                        <div class="pt-stack_md mt-4">
                                            <button type="submit"
                                                class="bg-primary-container hover:bg-[#008f00] text-on-primary font-bold px-8 py-3 rounded-lg shadow-sm active:scale-95 transition-all w-full md:w-auto">
                                                Lưu thay đổi
                                            </button>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </form>

                </div>



                {{-- 1.2 TAB ĐỔI MẬT KHẨU (Desktop) - Ẩn mặc định (class="hidden") --}}
                <div id="desktop-password-content" class="hidden">
                    <div class="mb-stack_lg">
                        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Đổi mật khẩu</h1>
                        <p class="font-body-md text-body-md text-on-surface-variant">Quản lý và thay đổi mật khẩu để bảo vệ
                            tài khoản của bạn</p>
                    </div>

                    {{-- Nếu đăng nhập thông qua Google, không cho phép đổi mật khẩu tại đây --}}
                    @if (session('login_method') === 'google')
                        <div class="bg-white rounded-xl border border-outline-variant p-10 shadow-sm text-center">
                            <div
                                class="w-20 h-20 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4">
                                <span
                                    class="material-symbols-outlined text-on-surface-variant text-[40px]">account_circle</span>
                            </div>
                            <h3 class="font-headline-md text-[20px] text-on-surface mb-2 font-bold">Tài khoản Google</h3>
                            <p class="text-body-md text-on-surface-variant max-w-lg mx-auto">Tài khoản của bạn đang được
                                liên kết và đăng nhập thông qua Google. Bạn không thể đổi mật khẩu trực tiếp tại đây.</p>
                            <p class="text-body-md text-on-surface-variant max-w-lg mx-auto mt-2">Nếu muốn thiết lập mật
                                khẩu riêng để đăng nhập bằng Email, vui lòng đăng xuất và sử dụng chức năng <b>Quên mật
                                    khẩu</b>.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-stack_lg">
                            <!-- Ô nhập mật khẩu: Cột rộng bên trái (Col-span 3) -->
                            <div class="lg:col-span-3">
                                <section
                                    class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full flex flex-col justify-between">
                                    <form action="{{ route('profile.change-password') }}" method="POST"
                                        class="space-y-stack_md">
                                        @csrf
                                        <div class="space-y-4">
                                            <!-- Ô nhập mật khẩu hiện tại -->
                                            <div class="space-y-1.5">
                                                <label class="font-label-md text-label-md text-on-surface-variant px-1">Mật
                                                    khẩu hiện tại</label>
                                                <div class="relative flex items-center">
                                                    <span
                                                        class="material-symbols-outlined absolute left-4 text-outline select-none">lock</span>
                                                    <input id="current_password_desk" name="current_password"
                                                        class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200"
                                                        type="password" required placeholder="Nhập mật khẩu hiện tại">
                                                    {{-- Nút biểu tượng mắt nhấp chuột dùng JS toggle ẩn/hiện ký tự mật khẩu --}}
                                                    <button type="button"
                                                        class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility"
                                                        data-target="current_password_desk">
                                                        <span
                                                            class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                                    </button>
                                                </div>
                                                @error('current_password')
                                                    <small class="text-error mt-1 block px-1">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <!-- Ô nhập mật khẩu mới -->
                                            <div class="space-y-1.5">
                                                <label class="font-label-md text-label-md text-on-surface-variant px-1">Mật
                                                    khẩu mới</label>
                                                <div class="relative flex items-center">
                                                    <span
                                                        class="material-symbols-outlined absolute left-4 text-outline select-none">lock_open</span>
                                                    <input id="new_password_desk" name="new_password"
                                                        class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200"
                                                        type="password" required placeholder="Tạo mật khẩu mới">
                                                    <button type="button"
                                                        class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility"
                                                        data-target="new_password_desk">
                                                        <span
                                                            class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                                    </button>
                                                </div>
                                                @error('new_password')
                                                    <small class="text-error mt-1 block px-1">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <!-- Ô xác nhận lại mật khẩu mới -->
                                            <div class="space-y-1.5">
                                                <label class="font-label-md text-label-md text-on-surface-variant px-1">Xác
                                                    nhận mật khẩu mới</label>
                                                <div class="relative flex items-center">
                                                    <span
                                                        class="material-symbols-outlined absolute left-4 text-outline select-none">enhanced_encryption</span>
                                                    <input id="new_password_confirmation_desk"
                                                        name="new_password_confirmation"
                                                        class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200"
                                                        type="password" required placeholder="Nhập lại mật khẩu mới">
                                                    <button type="button"
                                                        class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility"
                                                        data-target="new_password_confirmation_desk">
                                                        <span
                                                            class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                                    </button>
                                                </div>
                                                @error('new_password_confirmation')
                                                    <small class="text-error mt-1 block px-1">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="pt-4 mt-6">
                                            <button type="submit"
                                                class="bg-primary-container hover:bg-[#008f00] text-on-primary font-bold px-8 py-3.5 rounded-lg shadow-sm active:scale-95 transition-all w-full md:w-auto flex items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-[20px]">vpn_key</span>
                                                Đổi mật khẩu
                                            </button>
                                        </div>
                                    </form>
                                </section>
                            </div>

                            <!-- Bảng kiểm tra tiêu chuẩn và độ mạnh mật khẩu (Col-span 2) -->
                            <div class="lg:col-span-2">
                                <section
                                    class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full flex flex-col justify-between space-y-6">
                                    <div>
                                        <h3
                                            class="font-headline-md text-headline-md text-on-surface flex items-center gap-2 mb-4 text-[18px] font-bold">
                                            <span
                                                class="material-symbols-outlined text-primary text-[24px]">verified_user</span>
                                            Tiêu chuẩn bảo mật
                                        </h3>
                                        <p class="text-sm text-on-surface-variant mb-6 font-body-md">Mật khẩu cần tuân thủ
                                            các quy tắc bảo mật dưới đây để bảo vệ tài khoản một cách tốt nhất.</p>

                                        <!-- Thanh hiển thị trực quan mức độ bảo mật mật khẩu -->
                                        <div
                                            class="bg-surface-container-low border border-outline-variant p-4 rounded-xl mb-6">
                                            <div
                                                class="flex justify-between items-center mb-2 text-sm font-label-md text-on-surface-variant">
                                                <span>Độ mạnh mật khẩu:</span>
                                                <span id="strength-label-desk" class="font-bold text-outline">Chưa
                                                    nhập</span>
                                            </div>
                                            <div class="h-2.5 w-full bg-surface-container rounded-full overflow-hidden">
                                                <div id="strength-bar-desk"
                                                    class="h-full w-0 bg-outline transition-all duration-300 rounded-full">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Danh sách checklist điều kiện (Thay đổi màu khi người dùng nhập đúng chuẩn thông qua JS) -->
                                        <div class="space-y-3.5">
                                            <div id="req-length-desk"
                                                class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                                <span
                                                    class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                                <span>Ít nhất 8 ký tự</span>
                                            </div>
                                            <div id="req-case-desk"
                                                class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                                <span
                                                    class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                                <span>Có chữ hoa và chữ thường</span>
                                            </div>
                                            <div id="req-number-desk"
                                                class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                                <span
                                                    class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                                <span>Có chữ số hoặc ký tự đặc biệt</span>
                                            </div>
                                            <div id="req-match-desk"
                                                class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                                <span
                                                    class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                                <span>Mật khẩu xác nhận trùng khớp</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Khung gợi ý mẹo đặt mật khẩu an toàn -->
                                    <div
                                        class="bg-secondary-container/30 border border-secondary/20 p-4 rounded-xl flex gap-3 items-start">
                                        <span
                                            class="material-symbols-outlined text-primary text-[20px] mt-0.5">lightbulb</span>
                                        <p class="text-xs text-on-secondary-container leading-relaxed"><b>Mẹo:</b> Sử dụng
                                            cụm từ dễ nhớ nhưng viết xen kẽ ký tự đặc biệt (ví dụ: <i>H@ppyDay2026</i>) để
                                            tăng bảo mật.</p>
                                    </div>
                                </section>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <!-- ============================================== -->
    <!-- 2. GIAO DIỆN TRÊN ĐIỆN THOẠI (MOBILE VIEW)     -->
    <!-- Chỉ hiển thị trên thiết bị di động (md:hidden giúp ẩn khi trên màn hình máy tính lớn) -->
    <!-- ============================================== -->
    <div class="md:hidden bg-background text-on-surface font-body-md min-h-[100dvh] pb-24 relative">

        <!-- Thanh Header tiêu đề trên cùng điện thoại -->
        <header
            class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant flex items-center px-4 h-16 w-full shadow-sm">
            <a id="mobile-back-btn" href="{{ url('/') }}"
                class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-primary">arrow_back</span>
            </a>
            <h1 id="mobile-header-title" class="ml-2 font-headline-md text-headline-md-mobile text-primary">Tài khoản</h1>
        </header>

        <main class="pt-20 px-4 max-w-md mx-auto relative z-10">

            {{-- Alert thành công bản Mobile --}}
            @if (session('success'))
                <div
                    class="bg-secondary-container border border-primary text-on-primary-container px-4 py-3 rounded-xl mb-6 shadow-sm font-label-md">
                    {{ session('success') }}
                </div>
            @endif

            <div id="mobile-profile-content">

                <!-- Phần Avatar & Tên (Mobile) -->
                <section class="flex flex-col items-center mb-8">
                    <div class="relative mb-4">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-md bg-white">
                            <img id="avatarPreviewMobile" class="w-full h-full object-cover"
                                src="{{ $userAvatarUrl }}" />
                        </div>
                        <button type="button"
                            class="absolute bottom-0 right-0 bg-primary-container p-1.5 rounded-full text-white shadow-sm ring-2 ring-white active:scale-90 transition-transform"
                            onclick="document.getElementById('avatarInput').click()">
                            <span class="material-symbols-outlined text-sm material-filled">edit</span>
                        </button>
                    </div>

                    <h2 id="profileNameDisplayMobile" class="font-headline text-headline-md text-on-surface">
                        {{ Auth::user()->name }}</h2>
                    <span
                        class="mt-1 px-3 py-1 bg-secondary-container text-on-secondary-fixed-variant rounded-full font-label-md text-label-md">
                        @switch(Auth::user()->membership_level ?? 'new')
                            @case('silver')
                                Thành viên hạng Bạc
                            @break

                            @case('gold')
                                Thành viên hạng Vàng
                            @break

                            @case('diamond')
                                Thành viên Kim Cương
                            @break

                            @default
                                Thành viên Mới
                        @endswitch
                    </span>
                    <div class="flex gap-4 mt-6 w-full">
                        <div
                            class="flex-1 organic-gradient p-4 rounded-xl border border-outline-variant flex flex-col items-center text-center shadow-sm">
                            <span class="font-headline text-headline-md text-primary">{{ $ordersCount }}</span>
                            <span class="text-label-md font-label-md text-on-surface-variant">Đơn hàng</span>
                        </div>
                        <div
                            class="flex-1 organic-gradient p-4 rounded-xl border border-outline-variant flex flex-col items-center text-center shadow-sm">
                            <span
                                class="font-headline text-headline-md text-primary">{{ Auth::user()->points ?? 0 }}</span>
                            <span class="text-label-md font-label-md text-on-surface-variant">Điểm tích lũy</span>
                        </div>
                    </div>
                </section>

                <!-- Form nhập thông tin cá nhân (Mobile) -->
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <section class="space-y-4 mb-10">
                        <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2">Thông tin cá nhân</h3>
                        <div class="space-y-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Họ và tên</label>
                            <div class="relative group">
                                <input id="name-input-mobile" name="name" maxlength="30"
                                    class="w-full h-12 px-4 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary-container transition-all text-body-md"
                                    type="text" value="{{ old('name', Auth::user()->name) }}" required />
                                <small id="name-error-mobile"
                                    class="text-error block mt-1 ml-1 {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</small>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Số điện
                                thoại</label>
                            <div class="relative group">
                                <input id="phone-input-mobile" name="phone"
                                    class="w-full h-12 px-4 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary-container transition-all text-body-md"
                                    type="tel" value="{{ old('phone', Auth::user()->phone ?? '') }}" />
                                <small id="phone-error-mobile"
                                    class="text-error block mt-1 ml-1 {{ $errors->has('phone') ? '' : 'hidden' }}">{{ $errors->first('phone') }}</small>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Email</label>
                            <div class="relative group">
                                <input
                                    class="w-full h-12 px-4 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary-container transition-all text-body-md opacity-70"
                                    type="email" value="{{ Auth::user()->email }}" disabled />
                            </div>
                        </div>
                        {{-- Ảnh đại diện vừa cắt (JS: cropImage() trong profile.js) — nút sửa avatar ở khối
                mobile phía trên dùng CHUNG modal cắt ảnh với bản desktop (#avatarInput/#croppedAvatarInput
                chỉ khai báo 1 lần trong form desktop), nhưng form desktop bị ẩn trên mobile nên
                new FormData() của form NÀY sẽ không lấy được giá trị từ input nằm ở form khác. Cần 1
                input ẩn RIÊNG cho form mobile, được cropImage() đồng bộ giá trị song song. --}}
                        <input type="hidden" name="cropped_avatar" id="croppedAvatarInputMobile">
                        <button type="submit"
                            class="w-full h-12 bg-primary-container text-white font-label-md text-label-md rounded-lg active:scale-95 transition-transform shadow-md mt-4 hover:shadow-lg">
                            Lưu thay đổi
                        </button>
                    </section>
                </form>

                <section class="space-y-3 mb-8">
                    <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2">Cài đặt khác</h3>
                    <a href="#password" onclick="showTab('password'); return false;"
                        class="flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-primary-container/10 transition-colors group shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary-container">lock_reset</span>
                            <span class="font-label-md text-label-md text-on-surface">Đổi mật khẩu</span>
                        </div>
                        <span
                            class="material-symbols-outlined text-on-surface-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                    </a>
                    <a href="{{ route('logout') }}"
                        class="w-full flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-error-container/20 transition-colors group shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-error">logout</span>
                            <span class="font-label-md text-label-md text-error">Đăng xuất</span>
                        </div>
                    </a>
                </section>
            </div>


            {{-- Form đổi mật khẩu bản Mobile (Ẩn mặc định) --}}
            <div id="mobile-password-content" class="hidden">
                @if (session('login_method') === 'google')
                    <div class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm text-center mb-8 mt-2">
                        <div
                            class="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4">
                            <span
                                class="material-symbols-outlined text-on-surface-variant text-[32px]">account_circle</span>
                        </div>
                        <h3 class="font-headline-md text-[18px] text-on-surface mb-2 font-bold">Tài khoản Google</h3>
                        <p class="text-body-md text-on-surface-variant">Tài khoản của bạn đang được liên kết và đăng nhập
                            thông qua Google. Không thể đổi mật khẩu tại đây.</p>
                        <p class="text-body-md text-on-surface-variant mt-2">Sử dụng chức năng <b>Quên mật khẩu</b> ở trang
                            đăng nhập nếu muốn đặt mật khẩu riêng.</p>
                    </div>
                @else
                    <form action="{{ route('profile.change-password') }}" method="POST">
                        @csrf
                        <section class="space-y-4 mb-6 bg-white rounded-xl border border-outline-variant p-4 shadow-sm">
                            <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">lock_reset</span>
                                Đổi mật khẩu
                            </h3>

                            <!-- Mật khẩu hiện tại (Mobile) -->
                            <div class="space-y-1">
                                <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Mật khẩu hiện
                                    tại</label>
                                <div class="relative flex items-center">
                                    <span
                                        class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">lock</span>
                                    <input id="current_password_mob" name="current_password"
                                        class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none"
                                        type="password" required placeholder="Mật khẩu hiện tại" />
                                    <button type="button"
                                        class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility"
                                        data-target="current_password_mob">
                                        <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                                    </button>
                                </div>
                                @error('current_password')
                                    <small class="text-error block mt-1 ml-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Mật khẩu mới (Mobile) -->
                            <div class="space-y-1">
                                <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Mật khẩu
                                    mới</label>
                                <div class="relative flex items-center">
                                    <span
                                        class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">lock_open</span>
                                    <input id="new_password_mob" name="new_password"
                                        class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none"
                                        type="password" required placeholder="Mật khẩu mới" />
                                    <button type="button"
                                        class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility"
                                        data-target="new_password_mob">
                                        <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                                    </button>
                                </div>
                                @error('new_password')
                                    <small class="text-error block mt-1 ml-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Xác nhận mật khẩu mới (Mobile) -->
                            <div class="space-y-1">
                                <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Xác nhận mật
                                    khẩu mới</label>
                                <div class="relative flex items-center">
                                    <span
                                        class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">enhanced_encryption</span>
                                    <input id="new_password_confirmation_mob" name="new_password_confirmation"
                                        class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none"
                                        type="password" required placeholder="Xác nhận mật khẩu mới" />
                                    <button type="button"
                                        class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility"
                                        data-target="new_password_confirmation_mob">
                                        <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                                    </button>
                                </div>
                                @error('new_password_confirmation')
                                    <small class="text-error block mt-1 ml-1">{{ $message }}</small>
                                @enderror
                            </div>
                        </section>

                        <!-- Checklist tiêu chuẩn độ mạnh bản di động -->
                        <section class="space-y-4 mb-10 bg-white rounded-xl border border-outline-variant p-4 shadow-sm">
                            <div class="bg-surface-container-low border border-outline-variant p-3.5 rounded-lg">
                                <div
                                    class="flex justify-between items-center mb-1.5 text-xs font-label-md text-on-surface-variant">
                                    <span>Độ mạnh mật khẩu:</span>
                                    <span id="strength-label-mob" class="font-bold text-outline">Chưa nhập</span>
                                </div>
                                <div class="h-2 w-full bg-surface-container rounded-full overflow-hidden">
                                    <div id="strength-bar-mob"
                                        class="h-full w-0 bg-outline transition-all duration-300 rounded-full"></div>
                                </div>
                            </div>

                            <div class="space-y-2.5">
                                <div id="req-length-mob"
                                    class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                                    <span
                                        class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                                    <span>Ít nhất 8 ký tự</span>
                                </div>
                                <div id="req-case-mob"
                                    class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                                    <span
                                        class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                                    <span>Có chữ hoa và chữ thường</span>
                                </div>
                                <div id="req-number-mob"
                                    class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                                    <span
                                        class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                                    <span>Có chữ số hoặc ký tự đặc biệt</span>
                                </div>
                                <div id="req-match-mob"
                                    class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                                    <span
                                        class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                                    <span>Mật khẩu xác nhận trùng khớp</span>
                                </div>
                            </div>
                        </section>

                        <button type="submit"
                            class="w-full h-12 bg-primary text-white font-label-md text-label-md rounded-lg active:scale-95 transition-transform shadow-md hover:bg-primary-container flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">vpn_key</span>
                            Đổi mật khẩu
                        </button>
                    </form>
                @endif
            </div>
        </main>

        @include('frontend.components.bottom-nav')
    </div>


    <!-- ============================================== -->
    <!-- HỘP THOẠI POPUP & KỊCH BẢN (MODALS & SCRIPTS)   -->
    <!-- ============================================== -->

    {{-- Modal cắt ảnh (Cropper Modal) hiển thị dưới dạng overlay che toàn màn hình --}}
    <div id="cropperModal" class="cropper-modal-overlay">
        <div class="cropper-modal-content">
            <h4 class="cropper-modal-title">Chỉnh sửa ảnh đại diện</h4>
            <div class="cropper-image-container">
                <img id="imageToCrop" class="cropper-image-preview">
            </div>
            <div class="cropper-modal-actions">
                <button type="button" class="cropper-btn-cancel" onclick="closeCropperModal()">Hủy</button>
                <button type="button" class="cropper-btn-save" onclick="cropImage()">Cắt & Lưu</button>
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- Nạp thư viện hỗ trợ cắt ảnh tỉ lệ vuông (Cropper.js) --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
        <script>
            document.querySelectorAll('input:not([type="file"]):not([type="hidden"]):not([type="checkbox"])').forEach(input => {
                input.addEventListener('focus', () => {
                    if (input.parentElement && input.parentElement.classList) {
                        input.parentElement.classList.add('scale-[1.01]', 'leaf-indicator');
                        input.parentElement.style.transition = 'transform 0.2s ease';
                    }
                });
                input.addEventListener('blur', () => {
                    if (input.parentElement && input.parentElement.classList) {
                        input.parentElement.classList.remove('scale-[1.01]', 'leaf-indicator');
                    }
                });
            });

            const primaryBtns = document.querySelectorAll(
                'button[type="submit"], button.bg-primary-container, button.bg-primary');
            primaryBtns.forEach(btn => {
                btn.addEventListener('mousedown', () => btn.classList.add('scale-95'));
                btn.addEventListener('mouseup', () => btn.classList.remove('scale-95'));
                btn.addEventListener('mouseleave', () => btn.classList.remove('scale-95'));
                btn.addEventListener('touchstart', () => btn.classList.add('scale-95'), {
                    passive: true
                });
                btn.addEventListener('touchend', () => btn.classList.remove('scale-95'), {
                    passive: true
                });
            });

            let cropper;

            function previewAvatar(event) {
                const input = event.target;
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    if (file.size > 5 * 1024 * 1024) {
                        if (window.FrontendAlert) {
                            window.FrontendAlert.error('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
                        } else {
                            if (window.FrontendAlert) window.FrontendAlert.error(
                                'Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
                            else alert('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
                        }
                        input.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('imageToCrop').src = e.target.result;
                        document.getElementById('cropperModal').style.display = 'flex';

                        if (cropper) cropper.destroy();
                        cropper = new Cropper(document.getElementById('imageToCrop'), {
                            aspectRatio: 1,
                            viewMode: 1,
                            dragMode: 'move',
                            autoCropArea: 1,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: false,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                        });
                    }
                    reader.readAsDataURL(file);
                }
            }

            function closeCropperModal() {
                document.getElementById('cropperModal').style.display = 'none';
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                document.getElementById('avatarInput').value = '';
            }

            function cropImage() {
                if (!cropper) return;

                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                const base64Image = canvas.toDataURL('image/jpeg', 0.9);

                if (document.getElementById('avatarPreview')) {
                    document.getElementById('avatarPreview').src = base64Image;
                }
                if (document.getElementById('avatarPreviewMobile')) {
                    document.getElementById('avatarPreviewMobile').src = base64Image;
                }

                const croppedInputDesktop = document.getElementById('croppedAvatarInput');
                if (croppedInputDesktop) croppedInputDesktop.value = base64Image;
                const croppedInputMobile = document.getElementById('croppedAvatarInputMobile');
                if (croppedInputMobile) croppedInputMobile.value = base64Image;

                closeCropperModal();
            }

            function showTab(tab) {
                const deskProfile = document.getElementById('desktop-profile-content');
                const deskPass = document.getElementById('desktop-password-content');

                const mobProfile = document.getElementById('mobile-profile-content');
                const mobPass = document.getElementById('mobile-password-content');

                const profileLink = document.getElementById('tab-profile-link');
                const passwordLink = document.getElementById('tab-password-link');

                const headerTitle = document.getElementById('mobile-header-title');
                const backBtn = document.getElementById('mobile-back-btn');

                if (deskProfile) deskProfile.classList.add('hidden');
                if (deskPass) deskPass.classList.add('hidden');

                if (mobProfile) mobProfile.classList.add('hidden');
                if (mobPass) mobPass.classList.add('hidden');

                function resetLink(link) {
                    if (!link) return;
                    link.className =
                        "text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md";
                    const icon = link.querySelector('.material-symbols-outlined');
                    if (icon) icon.style.fontVariationSettings = "";
                }

                function activeLink(link) {
                    if (!link) return;
                    link.className =
                        "bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md";
                    const icon = link.querySelector('.material-symbols-outlined');
                    if (icon) icon.style.fontVariationSettings = "'FILL' 1";
                }

                resetLink(profileLink);
                resetLink(passwordLink);

                if (tab === 'password') {
                    if (deskPass) deskPass.classList.remove('hidden');
                    if (mobPass) mobPass.classList.remove('hidden');
                    activeLink(passwordLink);
                    if (headerTitle) headerTitle.textContent = "Đổi mật khẩu";
                    window.location.hash = 'password';
                } else {
                    if (deskProfile) deskProfile.classList.remove('hidden');
                    if (mobProfile) mobProfile.classList.remove('hidden');
                    activeLink(profileLink);
                    if (headerTitle) headerTitle.textContent = "Tài khoản";
                    window.location.hash = 'profile';
                }

                if (tab !== 'profile') {
                    if (backBtn) {
                        backBtn.href = "#profile";
                        backBtn.onclick = function(e) {
                            e.preventDefault();
                            showTab('profile');
                        };
                    }
                } else {
                    if (backBtn) {
                        backBtn.href = backBtn.dataset.prevUrl || 'javascript:history.back()';
                        backBtn.onclick = null;
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const backBtn = document.getElementById('mobile-back-btn');
                if (backBtn) {
                    backBtn.dataset.prevUrl = backBtn.getAttribute('href');
                }

                if (window.location.hash === '#password' || window.location.hash === '#change-password') {
                    showTab('password');
                } else {
                    showTab('profile');
                }

                const newPassDesk = document.getElementById('new_password_desk');
                const newPassMob = document.getElementById('new_password_mob');
                const confirmPassDesk = document.getElementById('new_password_confirmation_desk');
                const confirmPassMob = document.getElementById('new_password_confirmation_mob');

                function checkPasswordStrength() {
                    const val = this.value;
                    if (this === newPassDesk && newPassMob) newPassMob.value = val;
                    if (this === newPassMob && newPassDesk) newPassDesk.value = val;

                    const confirmVal = (this === newPassDesk || this === newPassMob) ?
                        (confirmPassDesk ? confirmPassDesk.value : '') :
                        this.value;

                    if (this === confirmPassDesk && confirmPassMob) confirmPassMob.value = this.value;
                    if (this === confirmPassMob && confirmPassDesk) confirmPassDesk.value = this.value;

                    const password = newPassDesk ? newPassDesk.value : (newPassMob ? newPassMob.value : '');

                    const hasLength = password.length >= 8;
                    const hasCase = /[a-z]/.test(password) && /[A-Z]/.test(password);
                    const hasNumberOrSymbol = /[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password);
                    const matches = password === confirmVal && password.length > 0;

                    let score = 0;
                    if (password.length > 0) {
                        if (hasLength) score++;
                        if (hasCase) score++;
                        if (hasNumberOrSymbol) score++;
                    }

                    updateIndicator('req-length', hasLength);
                    updateIndicator('req-case', hasCase);
                    updateIndicator('req-number', hasNumberOrSymbol);
                    updateIndicator('req-match', matches);

                    updateStrengthMeter(score, password.length > 0);
                }

                function updateIndicator(idPrefix, isValid) {
                    ['desk', 'mob'].forEach(suffix => {
                        const el = document.getElementById(`${idPrefix}-${suffix}`);
                        if (!el) return;
                        const icon = el.querySelector('.material-symbols-outlined');
                        if (isValid) {
                            el.classList.remove('text-on-surface-variant');
                            el.classList.add('text-primary');
                            if (icon) {
                                icon.textContent = 'check_circle';
                                icon.classList.remove('text-outline');
                                icon.classList.add('text-primary');
                            }
                        } else {
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

                function updateStrengthMeter(score, hasInput) {
                    const labels = {
                        0: {
                            text: 'Yếu',
                            colorClass: 'text-error',
                            barClass: 'bg-error',
                            width: '33%'
                        },
                        1: {
                            text: 'Yếu',
                            colorClass: 'text-error',
                            barClass: 'bg-error',
                            width: '33%'
                        },
                        2: {
                            text: 'Trung bình',
                            colorClass: 'text-amber-500',
                            barClass: 'bg-amber-500',
                            width: '66%'
                        },
                        3: {
                            text: 'Mạnh',
                            colorClass: 'text-primary',
                            barClass: 'bg-primary',
                            width: '100%'
                        }
                    };

                    const config = hasInput ? labels[score] : {
                        text: 'Chưa nhập',
                        colorClass: 'text-outline',
                        barClass: 'bg-outline',
                        width: '0%'
                    };

                    ['desk', 'mob'].forEach(suffix => {
                        const labelEl = document.getElementById(`strength-label-${suffix}`);
                        const barEl = document.getElementById(`strength-bar-${suffix}`);
                        if (labelEl) {
                            labelEl.textContent = config.text;
                            labelEl.className = `font-bold ${config.colorClass}`;
                        }
                        if (barEl) {
                            barEl.style.width = config.width;
                            barEl.classList.remove('bg-outline', 'bg-error', 'bg-amber-500', 'bg-primary');
                            barEl.classList.add(config.barClass);
                        }
                    });
                }

                [newPassDesk, newPassMob, confirmPassDesk, confirmPassMob].forEach(input => {
                    if (input) {
                        input.addEventListener('input', checkPasswordStrength);
                    }
                });
            });

            window.showTab = showTab;
        </script>

        @if ($errors->has('current_password') || $errors->has('new_password') || session('active_tab') === 'password')
            {{-- Tự mở lại tab Đổi mật khẩu sau khi tải lại trang: hoặc do lỗi nhập mật khẩu, hoặc vừa đổi mật
khẩu thành công (URL hash #password không được gửi lên server nên không tự giữ được). --}}
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showTab('password');
                });
            </script>
        @endif
    @endpush
@endsection
