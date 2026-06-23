@extends('layouts.app')

{{-- Khai báo class CSS riêng cho thẻ body của trang này --}}
@section('body_class', 'profile-body')

@section('content')
{{-- Import thư viện CSS của Leaflet (Dùng để vẽ Bản đồ GPS) --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- ============================================== -->
<!-- 1. GIAO DIỆN MÁY TÍNH (DESKTOP VIEW)           -->
<!-- Các class như 'hidden md:flex' nghĩa là: Mặc định giấu đi (hidden), nhưng màn hình từ cỡ trung bình (md) trở lên thì sẽ hiện ra kiểu flex. -->
<!-- ============================================== -->
<div class="hidden md:flex min-h-screen bg-background text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container">
    
    <!-- Cột Menu Bên Trái (SideNavBar) -->
    <aside class="w-[280px] flex-shrink-0 bg-tertiary-fixed border-r border-outline-variant flex flex-col py-stack_lg">
        <div class="px-6 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <!-- Khung viền tròn hiển thị Ảnh Đại Diện (Avatar) -->
                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary bg-white">
                    {{-- Nếu user đã từng úp ảnh đại diện thì tải từ thư mục 'images/avatars/' --}}
                    @if(Auth::user()->avatar)
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}">
                    {{-- Nếu chưa có ảnh, tự động sinh ra một cái ảnh có tên viết tắt (VD: Lê Minh Vũ -> LMV) từ trang ui-avatars.com --}}
                    @else
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=006e01&color=fff">
                    @endif
                </div>
                <div>
                    <!-- In tên của người dùng đang đăng nhập -->
                    <h3 class="font-label-md text-label-md text-on-surface">{{ Auth::user()->name }}</h3>
                    <p class="text-xs text-on-surface-variant">
                        {{-- Hiển thị hạng thành viên dựa vào dữ liệu DB --}}
                        @switch(Auth::user()->membership_level ?? 'new')
                            @case('silver') Thành viên hạng Bạc @break
                            @case('gold') Thành viên hạng Vàng @break
                            @case('diamond') Thành viên Kim Cương @break
                            @default Thành viên Mới
                        @endswitch
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Các Nút Chuyển Tab (Menu) -->
        <nav class="flex-1">
            <!-- Nút Tab "Thông tin tài khoản" - Hàm onclick="showTab('profile')" sẽ nhờ Javascript ẩn hiện nội dung -->
            <a id="tab-profile-link" class="bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md" href="#profile" onclick="showTab('profile'); return false;">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
                Thông tin tài khoản
            </a>
            
            <!-- Nút "Đơn hàng của tôi" - Link này chuyển hẳn sang một trang web khác (routes 'orders') -->
            <a class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('orders') }}">
                <span class="material-symbols-outlined">shopping_bag</span>
                Đơn hàng của tôi
            </a>

            <!-- Nút Tab "Đổi mật khẩu" -->
            <a id="tab-password-link" class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="#password" onclick="showTab('password'); return false;">
                <span class="material-symbols-outlined">lock</span>
                Đổi mật khẩu
            </a>
            
            <!-- Nút Đăng xuất -->
            <a class="text-error hover:bg-error-container/20 px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('logout') }}">
                <span class="material-symbols-outlined">logout</span>
                Đăng xuất
            </a>
        </nav>
    </aside>

    <!-- Khu vực Nội dung Chính Bên Phải (Main Content) -->
    <main class="flex-1 p-stack_lg">
        <div class="max-w-4xl mx-auto">
            
            <!-- Thông báo màu xanh (Success) nếu lưu thông tin/đổi mật khẩu thành công -->
            @if(session('success'))
            <div class="bg-[#c1e9d5] border border-[#0aad0a] text-[#005301] px-4 py-3 rounded-xl mb-6 shadow-sm">
                {{ session('success') }}
            </div>
            @endif

            <!-- ---------------------------------------------------------------- -->
            <!-- KHU VỰC 1: TAB THÔNG TIN CÁ NHÂN (PROFILE CONTENT)               -->
            <!-- ---------------------------------------------------------------- -->
            <div id="desktop-profile-content">
                <!-- Tiêu đề -->
                <div class="mb-stack_lg">
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Thông tin cá nhân</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Quản lý thông tin hồ sơ của bạn để bảo mật tài khoản</p>
                </div>
            
            <!-- Form cập nhật thông tin cá nhân. (enctype="multipart/form-data" là bắt buộc để tải file/ảnh lên Server) -->
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-stack_lg">
                    <!-- Bên Trái: Khu vực sửa Ảnh Đại Diện (Avatar) -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl border border-outline-variant p-stack_lg flex flex-col items-center justify-center text-center shadow-sm h-full">
                            <div class="relative group mb-stack_md">
                                <div class="w-40 h-40 rounded-full overflow-hidden border-4 border-surface-container p-1 ring-2 ring-primary/20 bg-white">
                                    @if(Auth::user()->avatar)
                                        <img id="avatarPreview" alt="Large User Avatar" class="w-full h-full object-cover rounded-full" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}">
                                    @else
                                        <img id="avatarPreview" alt="Large User Avatar" class="w-full h-full object-cover rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=006e01&color=fff">
                                    @endif
                                </div>
                                <!-- Nút cây Bút để bấm chọn ảnh. Khi bấm sẽ gọi hàm click() của thẻ input file bị giấu phía dưới -->
                                <button type="button" class="absolute bottom-1 right-1 bg-primary text-on-primary w-10 h-10 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform active:scale-95" onclick="document.getElementById('avatarInput').click()">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                
                                <!-- Thẻ input ẩn để khách hàng chọn file ảnh từ máy tính. Thuộc tính onchange="previewAvatar(event)" sẽ bật tool Cắt ảnh Cropper.js -->
                                <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="previewAvatar(event)">
                                
                                <!-- Thẻ input ẩn chứa mã Base64 của ảnh SAU KHI ĐÃ CẮT (Được gán bằng Javascript) -->
                                <input type="hidden" name="cropped_avatar" id="croppedAvatarInput">
                            </div>
                            <h3 class="font-headline-md text-headline-md text-on-surface">{{ Auth::user()->name }}</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant mb-stack_md">Thành viên từ {{ Auth::user()->created_at ? Auth::user()->created_at->format('Y') : '2023' }}</p>
                            
                            <!-- Bảng tóm tắt Điểm số và Đơn hàng -->
                            <div class="flex gap-2 w-full mt-auto">
                                <div class="flex-1 bg-tertiary-fixed rounded-lg py-2">
                                    <span class="block font-bold text-primary text-lg">12</span>
                                    <span class="text-xs text-on-tertiary-fixed-variant">Đơn hàng</span>
                                </div>
                                <div class="flex-1 bg-secondary-container rounded-lg py-2">
                                    <span class="block font-bold text-on-secondary-container text-lg">2.5k</span>
                                    <span class="text-xs text-on-secondary-container">Điểm thưởng</span>
                                </div>
                            </div>
                            <!-- In lỗi nếu ảnh bị vi phạm dung lượng hoặc định dạng -->
                            @error('avatar') <small class="text-error mt-2 block text-center">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Bên Phải: Các trường nhập liệu Họ Tên, Số Điện Thoại -->
                    <div class="lg:col-span-2 space-y-stack_lg">
                        <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full">
                            <div class="space-y-stack_md h-full flex flex-col justify-between">
                                <div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack_md mb-stack_md">
                                        <!-- Ô Tên -->
                                        <div class="space-y-1">
                                            <label class="font-label-md text-label-md text-on-surface-variant px-1">Họ và tên</label>
                                            <input name="name" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-transform" type="text" value="{{ Auth::user()->name }}" required>
                                            @error('name') <small class="text-error mt-1 block">{{ $message }}</small> @enderror
                                        </div>
                                        <!-- Ô SĐT -->
                                        <div class="space-y-1">
                                            <label class="font-label-md text-label-md text-on-surface-variant px-1">Số điện thoại</label>
                                            <input name="phone" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-transform" type="tel" value="{{ Auth::user()->phone ?? '' }}">
                                            @error('phone') <small class="text-error mt-1 block">{{ $message }}</small> @enderror
                                        </div>
                                    </div>
                                    <!-- Ô Email (Cố định, không cho sửa -> Thuộc tính disabled) -->
                                    <div class="space-y-1">
                                        <label class="font-label-md text-label-md text-on-surface-variant px-1">Email</label>
                                        <input class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none opacity-70" type="email" value="{{ Auth::user()->email }}" disabled>
                                    </div>
                                </div>

                                <div class="pt-stack_md mt-4">
                                    <button type="submit" class="bg-primary-container hover:bg-[#008f00] text-on-primary font-bold px-8 py-3 rounded-lg shadow-sm active:scale-95 transition-all w-full md:w-auto">
                                        Lưu thay đổi
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>

            </div>



            <!-- ---------------------------------------------------------------- -->
            <!-- KHU VỰC 2: TAB ĐỔI MẬT KHẨU (PASSWORD CONTENT)                   -->
            <!-- Bị giấu đi mặc định (class="hidden"), chỉ hiện khi khách bấm Tab -->
            <!-- ---------------------------------------------------------------- -->
            <div id="desktop-password-content" class="hidden">
                <div class="mb-stack_lg">
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Đổi mật khẩu</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Quản lý và thay đổi mật khẩu để bảo vệ tài khoản của bạn</p>
                </div>

                {{-- Nếu khách đăng nhập bằng Nút Google thì họ không có mật khẩu trong Database -> Không cho đổi ở đây --}}
                @if(session('login_method') === 'google')
                <div class="bg-white rounded-xl border border-outline-variant p-10 shadow-sm text-center">
                    <div class="w-20 h-20 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-on-surface-variant text-[40px]">account_circle</span>
                    </div>
                    <h3 class="font-headline-md text-[20px] text-on-surface mb-2 font-bold">Tài khoản Google</h3>
                    <p class="text-body-md text-on-surface-variant max-w-lg mx-auto">Tài khoản của bạn đang được liên kết và đăng nhập thông qua Google. Bạn không thể đổi mật khẩu trực tiếp tại đây.</p>
                    <p class="text-body-md text-on-surface-variant max-w-lg mx-auto mt-2">Nếu muốn thiết lập mật khẩu riêng để đăng nhập bằng Email, vui lòng đăng xuất và sử dụng chức năng <b>Quên mật khẩu</b>.</p>
                </div>
                @else
                
                {{-- Form nhập Mật khẩu cho tài khoản thường --}}
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-stack_lg">
                    <!-- Khu vực 3 cột bên Trái: Nhập mật khẩu -->
                    <div class="lg:col-span-3">
                        <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full flex flex-col justify-between">
                            <form action="{{ route('profile.change-password') }}" method="POST" class="space-y-stack_md">
                                @csrf
                                <div class="space-y-4">
                                    <!-- Ô Nhập Mật khẩu hiện tại -->
                                    <div class="space-y-1.5">
                                        <label class="font-label-md text-label-md text-on-surface-variant px-1">Mật khẩu hiện tại</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-4 text-outline select-none">lock</span>
                                            <input id="current_password_desk" name="current_password" class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200" type="password" required placeholder="Nhập mật khẩu hiện tại">
                                            <!-- Nút "Mắt" dùng JS để ẩn/hiện chữ mật khẩu thay vì dấu chấm tròn -->
                                            <button type="button" class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="current_password_desk">
                                                <span class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                            </button>
                                        </div>
                                        @error('current_password') <small class="text-error mt-1 block px-1">{{ $message }}</small> @enderror
                                    </div>

                                    <!-- Ô Nhập Mật khẩu mới -->
                                    <div class="space-y-1.5">
                                        <label class="font-label-md text-label-md text-on-surface-variant px-1">Mật khẩu mới</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-4 text-outline select-none">lock_open</span>
                                            <!-- Nơi Javascript (profile.js) sẽ "Bắt" phím gõ để chấm điểm Độ mạnh -->
                                            <input id="new_password_desk" name="new_password" class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200" type="password" required placeholder="Tạo mật khẩu mới">
                                            <button type="button" class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="new_password_desk">
                                                <span class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                            </button>
                                        </div>
                                        @error('new_password') <small class="text-error mt-1 block px-1">{{ $message }}</small> @enderror
                                    </div>

                                    <!-- Ô Xác nhận mật khẩu mới -->
                                    <div class="space-y-1.5">
                                        <label class="font-label-md text-label-md text-on-surface-variant px-1">Xác nhận mật khẩu mới</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-4 text-outline select-none">enhanced_encryption</span>
                                            <input id="new_password_confirmation_desk" name="new_password_confirmation" class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200" type="password" required placeholder="Nhập lại mật khẩu mới">
                                            <button type="button" class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="new_password_confirmation_desk">
                                                <span class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                            </button>
                                        </div>
                                        @error('new_password_confirmation') <small class="text-error mt-1 block px-1">{{ $message }}</small> @enderror
                                    </div>
                                </div>

                                <div class="pt-4 mt-6">
                                    <button type="submit" class="bg-primary-container hover:bg-[#008f00] text-on-primary font-bold px-8 py-3.5 rounded-lg shadow-sm active:scale-95 transition-all w-full md:w-auto flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[20px]">vpn_key</span>
                                        Đổi mật khẩu
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>

                    <!-- Khu vực 2 cột bên Phải: Chấm điểm mật khẩu -->
                    <div class="lg:col-span-2">
                        <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full flex flex-col justify-between space-y-6">
                            <div>
                                <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2 mb-4 text-[18px] font-bold">
                                    <span class="material-symbols-outlined text-primary text-[24px]">verified_user</span>
                                    Tiêu chuẩn bảo mật
                                </h3>
                                <p class="text-sm text-on-surface-variant mb-6 font-body-md">Mật khẩu cần tuân thủ các quy tắc bảo mật dưới đây để bảo vệ tài khoản một cách tốt nhất.</p>
                                
                                <!-- Thanh màu chạy đo độ dài/mạnh (Được điều khiển bằng Javascript file profile.js) -->
                                <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl mb-6">
                                    <div class="flex justify-between items-center mb-2 text-sm font-label-md text-on-surface-variant">
                                        <span>Độ mạnh mật khẩu:</span>
                                        <span id="strength-label-desk" class="font-bold text-outline">Chưa nhập</span>
                                    </div>
                                    <div class="h-2.5 w-full bg-surface-container rounded-full overflow-hidden">
                                        <!-- width sẽ biến thiên 0% -> 33% -> 66% -> 100% bằng JS -->
                                        <div id="strength-bar-desk" class="h-full w-0 bg-outline transition-all duration-300 rounded-full"></div>
                                    </div>
                                </div>

                                <!-- Checklist Xanh / Đỏ các yêu cầu mật khẩu -->
                                <div class="space-y-3.5">
                                    <div id="req-length-desk" class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                        <span class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                        <span>Ít nhất 8 ký tự</span>
                                    </div>
                                    <div id="req-case-desk" class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                        <span class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                        <span>Có chữ hoa và chữ thường</span>
                                    </div>
                                    <div id="req-number-desk" class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                        <span class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                        <span>Có chữ số hoặc ký tự đặc biệt</span>
                                    </div>
                                    <div id="req-match-desk" class="flex items-center gap-3 text-sm text-on-surface-variant font-label-md transition-colors duration-200">
                                        <span class="material-symbols-outlined text-outline select-none text-[20px]">radio_button_unchecked</span>
                                        <span>Mật khẩu xác nhận trùng khớp</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Khung gợi ý Mẹo đặt pass -->
                            <div class="bg-secondary-container/30 border border-secondary/20 p-4 rounded-xl flex gap-3 items-start">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">lightbulb</span>
                                <p class="text-xs text-on-secondary-container leading-relaxed"><b>Mẹo:</b> Sử dụng cụm từ dễ nhớ nhưng viết xen kẽ ký tự đặc biệt (ví dụ: <i>H@ppyDay2026</i>) để tăng bảo mật.</p>
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
<!-- 2. GIAO DIỆN ĐIỆN THOẠI (MOBILE VIEW)          -->
<!-- Class md:hidden giúp giấu nó đi trên Màn hình máy tính lớn. -->
<!-- Các tính năng và id y hệt bên Desktop, chỉ là Thiết kế (Layout) dọc lại cho vừa ngón tay cái -->
<!-- ============================================== -->
<div class="md:hidden bg-background text-on-surface font-body-md min-h-[100dvh] pb-24 relative">
    
    <!-- Thanh Header Cố Định (TopAppBar) -->
    <header class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant flex items-center px-4 h-16 w-full shadow-sm">
        <a id="mobile-back-btn" href="{{ url()->previous() }}" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-primary">arrow_back</span>
        </a>
        <h1 id="mobile-header-title" class="ml-2 font-headline-md text-headline-md-mobile text-primary">Tài khoản</h1>
    </header>

    <main class="pt-20 px-4 max-w-md mx-auto relative z-10">
        
        <!-- Khung báo thành công cho bản Mobile -->
        @if(session('success'))
        <div class="bg-secondary-container border border-primary text-on-primary-container px-4 py-3 rounded-xl mb-6 shadow-sm font-label-md">
            {{ session('success') }}
        </div>
        @endif

        <div id="mobile-profile-content">

        <!-- Avatar & Tên User (Bản Mobile) -->
        <section class="flex flex-col items-center mb-8">
            <div class="relative mb-4">
                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-md bg-white">
                    @if(Auth::user()->avatar)
                        <img id="avatarPreviewMobile" class="w-full h-full object-cover" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}"/>
                    @else
                        <img id="avatarPreviewMobile" class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=006e01&color=fff"/>
                    @endif
                </div>
                <!-- Nút cắt Avatar bản Mobile -->
                <button type="button" class="absolute bottom-0 right-0 bg-primary-container p-1.5 rounded-full text-white shadow-sm ring-2 ring-white active:scale-90 transition-transform" onclick="document.getElementById('avatarInput').click()">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">edit</span>
                </button>
            </div>
            
            <h2 class="font-headline text-headline-md text-on-surface">{{ Auth::user()->name }}</h2>
            <span class="mt-1 px-3 py-1 bg-secondary-container text-on-secondary-fixed-variant rounded-full font-label-md text-label-md">
                @switch(Auth::user()->membership_level ?? 'new')
                    @case('silver') Thành viên hạng Bạc @break
                    @case('gold') Thành viên hạng Vàng @break
                    @case('diamond') Thành viên Kim Cương @break
                    @default Thành viên Mới
                @endswitch
            </span>
            <div class="flex gap-4 mt-6 w-full">
                <div class="flex-1 organic-gradient p-4 rounded-xl border border-outline-variant flex flex-col items-center text-center shadow-sm">
                    <span class="font-headline text-headline-md text-primary">12</span>
                    <span class="text-label-md font-label-md text-on-surface-variant">Đơn hàng</span>
                </div>
                <div class="flex-1 organic-gradient p-4 rounded-xl border border-outline-variant flex flex-col items-center text-center shadow-sm">
                    <span class="font-headline text-headline-md text-primary">2.5k</span>
                    <span class="text-label-md font-label-md text-on-surface-variant">Điểm thưởng</span>
                </div>
            </div>
        </section>

        <!-- Form Thông tin cá nhân Bản Mobile -->
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <section class="space-y-4 mb-10">
                <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2">Thông tin cá nhân</h3>
                <div class="space-y-1">
                    <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Họ và tên</label>
                    <div class="relative group">
                        <input name="name" class="w-full h-12 px-4 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary-container transition-all text-body-md" type="text" value="{{ Auth::user()->name }}" required/>
                        @error('name') <small class="text-error block mt-1 ml-1">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Số điện thoại</label>
                    <div class="relative group">
                        <input name="phone" class="w-full h-12 px-4 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary-container transition-all text-body-md" type="tel" value="{{ Auth::user()->phone ?? '' }}"/>
                        @error('phone') <small class="text-error block mt-1 ml-1">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Email</label>
                    <div class="relative group">
                        <input class="w-full h-12 px-4 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary-container transition-all text-body-md opacity-70" type="email" value="{{ Auth::user()->email }}" disabled/>
                    </div>
                </div>
                <button type="submit" class="w-full h-12 bg-primary-container text-white font-label-md text-label-md rounded-lg active:scale-95 transition-transform shadow-md mt-4 hover:shadow-lg">
                    Lưu thay đổi
                </button>
            </section>
        </form>

        <!-- Danh sách các Menu (Đơn hàng, Mật Khẩu...) bấm để chuyển Tab trên Mobile -->
        <section class="space-y-3 mb-8">
            <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2">Cài đặt khác</h3>
            <a href="{{ route('orders') }}" class="flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-primary-container/10 transition-colors group shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary-container">receipt_long</span>
                    <span class="font-label-md text-label-md text-on-surface">Đơn hàng của tôi</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
            </a>

            <!-- Hàm showTab() sẽ giấu khung Profile đi và mở khung Password lên -->
            <a href="#password" onclick="showTab('password'); return false;" class="flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-primary-container/10 transition-colors group shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary-container">lock_reset</span>
                    <span class="font-label-md text-label-md text-on-surface">Đổi mật khẩu</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
            </a>
            
            <a href="{{ route('logout') }}" class="w-full flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-error-container/20 transition-colors group shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-error">logout</span>
                    <span class="font-label-md text-label-md text-error">Đăng xuất</span>
                </div>
            </a>
        </section>
        </div>

        <!-- Khung Đổi mật khẩu cho Bản Mobile -->
        <div id="mobile-password-content" class="hidden">
            @if(session('login_method') === 'google')
            <div class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm text-center mb-8 mt-2">
                <div class="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-on-surface-variant text-[32px]">account_circle</span>
                </div>
                <h3 class="font-headline-md text-[18px] text-on-surface mb-2 font-bold">Tài khoản Google</h3>
                <p class="text-body-md text-on-surface-variant">Tài khoản của bạn đang được liên kết và đăng nhập thông qua Google. Không thể đổi mật khẩu tại đây.</p>
                <p class="text-body-md text-on-surface-variant mt-2">Sử dụng chức năng <b>Quên mật khẩu</b> ở trang đăng nhập nếu muốn đặt mật khẩu riêng.</p>
            </div>
            @else
            <form action="{{ route('profile.change-password') }}" method="POST">
                @csrf
                <section class="space-y-4 mb-6 bg-white rounded-xl border border-outline-variant p-4 shadow-sm">
                    <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">lock_reset</span>
                        Đổi mật khẩu
                    </h3>
                    
                    <div class="space-y-1">
                        <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Mật khẩu hiện tại</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">lock</span>
                            <!-- Để ý _mob đằng sau các biến ID, tránh nhầm lẫn (xung đột JS) với các ID _desk trên bản Desktop -->
                            <input id="current_password_mob" name="current_password" class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none" type="password" required placeholder="Mật khẩu hiện tại"/>
                            <button type="button" class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="current_password_mob">
                                <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                            </button>
                        </div>
                        @error('current_password') <small class="text-error block mt-1 ml-1">{{ $message }}</small> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Mật khẩu mới</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">lock_open</span>
                            <input id="new_password_mob" name="new_password" class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none" type="password" required placeholder="Mật khẩu mới"/>
                            <button type="button" class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="new_password_mob">
                                <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                            </button>
                        </div>
                        @error('new_password') <small class="text-error block mt-1 ml-1">{{ $message }}</small> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Xác nhận mật khẩu mới</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">enhanced_encryption</span>
                            <input id="new_password_confirmation_mob" name="new_password_confirmation" class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none" type="password" required placeholder="Xác nhận mật khẩu mới"/>
                            <button type="button" class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="new_password_confirmation_mob">
                                <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                            </button>
                        </div>
                        @error('new_password_confirmation') <small class="text-error block mt-1 ml-1">{{ $message }}</small> @enderror
                    </div>
                </section>

                <section class="space-y-4 mb-10 bg-white rounded-xl border border-outline-variant p-4 shadow-sm">
                    <div class="bg-surface-container-low border border-outline-variant p-3.5 rounded-lg">
                        <div class="flex justify-between items-center mb-1.5 text-xs font-label-md text-on-surface-variant">
                            <span>Độ mạnh mật khẩu:</span>
                            <span id="strength-label-mob" class="font-bold text-outline">Chưa nhập</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container rounded-full overflow-hidden">
                            <div id="strength-bar-mob" class="h-full w-0 bg-outline transition-all duration-300 rounded-full"></div>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <div id="req-length-mob" class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                            <span class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                            <span>Ít nhất 8 ký tự</span>
                        </div>
                        <div id="req-case-mob" class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                            <span class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                            <span>Có chữ hoa và chữ thường</span>
                        </div>
                        <div id="req-number-mob" class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                            <span class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                            <span>Có chữ số hoặc ký tự đặc biệt</span>
                        </div>
                        <div id="req-match-mob" class="flex items-center gap-2.5 text-xs text-on-surface-variant font-label-md transition-colors">
                            <span class="material-symbols-outlined text-outline select-none text-[18px]">radio_button_unchecked</span>
                            <span>Mật khẩu xác nhận trùng khớp</span>
                        </div>
                    </div>
                </section>

                <button type="submit" class="w-full h-12 bg-primary text-white font-label-md text-label-md rounded-lg active:scale-95 transition-transform shadow-md hover:bg-primary-container flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">vpn_key</span>
                    Đổi mật khẩu
                </button>
            </form>
            @endif
        </div>
    </main>

    <!-- Thanh Menu Nổi Nằm Dưới Đáy Màn Hình Điện thoại (BottomNavBar) -->
    <!-- Sử dụng fix bottom-0 để dính chặt dưới đáy. -->
    <!-- Thêm pb-safe cho các máy iPhone tai thỏ không bị cấn phím Home ảo -->
    <nav class="fixed bottom-0 left-0 w-full z-50 bg-surface rounded-t-xl border-t border-outline-variant shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex justify-around items-center px-2 py-3 pb-safe">
        <a href="{{ url('/') }}" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined">home</span>
            <span class="font-label-md text-[11px] mt-1">Trang chủ</span>
        </a>
        <a href="{{ url('/products') }}" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined">eco</span>
            <span class="font-label-md text-[11px] mt-1">Sản phẩm</span>
        </a>
        <a href="{{ route('orders') }}" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined">receipt_long</span>
            <span class="font-label-md text-[11px] mt-1">Đơn hàng</span>
        </a>
        <!-- Nút Tab Tài khoản đang được chọn (Highlighted màu xanh) -->
        <a href="{{ route('profile') }}" class="flex flex-col items-center justify-center bg-primary-container/10 text-primary-container rounded-xl px-4 py-1.5 active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
            <span class="font-label-md text-[12px] font-bold mt-0.5">Tài khoản</span>
        </a>
    </nav>
</div>


<!-- ============================================== -->
<!-- 3. MODALS (Khung Cắt Ảnh) & NHÚNG JAVASCRIPT   -->
<!-- Code phần này dùng chung cho cả Bản Máy Tính và Bản Mobile để tiết kiệm dung lượng -->
<!-- ============================================== -->

<!-- Cái Modal chứa thư viện cắt ảnh Cropper.js. Đang bị giấu đi (display:none) -->
<div id="cropperModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; padding: 1rem; box-sizing: border-box;">
    <div style="background:#fff; padding:20px; border-radius:12px; width:100%; max-width:500px; text-align:center; position: relative; max-height: 90vh; overflow-y: auto;">
        <h4 style="margin-bottom:15px; font-weight: 600; color: #374151;">Chỉnh sửa ảnh đại diện</h4>
        <div style="width:100%; max-height:400px; overflow:hidden; margin-bottom:15px; background: #f3f4f6; border-radius: 8px;">
            <!-- Ảnh sau khi tải từ máy lên sẽ bay vào thẻ img này để vẽ ra khung caro cho khách cắt -->
            <img id="imageToCrop" style="max-width:100%; display:block;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="closeCropperModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #374151; font-weight: 500; cursor:pointer;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="cropImage()" style="padding: 8px 16px; border-radius: 6px; background: #10b981; color:#fff; border:none; font-weight: 500; cursor:pointer;">Cắt & Lưu</button>
        </div>
    </div>
</div>

<!-- Lệnh @push('scripts') của Blade: Bưng nguyên đống script này nhét xuống tận cùng Thẻ <body> của trang gốc (app.blade.php) để tăng tốc độ tải web -->
@push('scripts')
<!-- Kéo thư viện Leaflet (Vẽ Bản Đồ Vệ Tinh) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Kéo thư viện Cropper (Công cụ cắt ảnh vuông) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<!-- Gọi file Javascript tự viết từ nãy giờ (profile.js) vào. -->
<!-- Chữ ?v=time() là mẹo để trình duyệt khách hàng ko bao giờ lưu bộ đệm (cache) cái js cũ. Luôn lấy file mới nhất -->
<script src="{{ asset('js/frontend/profile.js') }}?v={{ time() }}"></script>

{{-- Nếu server trả về lỗi liên quan mật khẩu, chạy Javascript tự động MỞ NGAY Tab mật khẩu cho khách xem lỗi --}}
@if($errors->has('current_password') || $errors->has('new_password'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showTab('password');
    });
</script>
@endif
@endpush
@endsection
