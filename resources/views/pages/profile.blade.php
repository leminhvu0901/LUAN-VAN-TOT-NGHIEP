@extends('layouts.app')

@section('body_class', 'profile-body')

@section('content')


<!-- ============================================== -->
<!-- 1. DESKTOP VIEW (Chỉ hiển thị trên màn hình lớn) -->
<!-- ============================================== -->
<div class="hidden md:flex min-h-screen bg-background text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container">
    
    <!-- SideNavBar -->
    <aside class="w-[280px] flex-shrink-0 bg-tertiary-fixed border-r border-outline-variant flex flex-col py-stack_lg">
        <div class="px-6 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary bg-white">
                    @if(Auth::user()->avatar)
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}">
                    @else
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=006e01&color=fff">
                    @endif
                </div>
                <div>
                    <h3 class="font-label-md text-label-md text-on-surface">{{ Auth::user()->name }}</h3>
                    <p class="text-xs text-on-surface-variant">
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
        <nav class="flex-1">
            <a id="tab-profile-link" class="bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md" href="#profile" onclick="showTab('profile'); return false;">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
                Thông tin tài khoản
            </a>
            <a class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('orders') }}">
                <span class="material-symbols-outlined">shopping_bag</span>
                Đơn hàng của tôi
            </a>
            <a id="tab-address-link" class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="#address" onclick="showTab('address'); return false;">
                <span class="material-symbols-outlined">location_on</span>
                Số địa chỉ
            </a>
            <a id="tab-password-link" class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="#password" onclick="showTab('password'); return false;">
                <span class="material-symbols-outlined">lock</span>
                Đổi mật khẩu
            </a>
            <a class="text-error hover:bg-error-container/20 px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('logout') }}">
                <span class="material-symbols-outlined">logout</span>
                Đăng xuất
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-stack_lg">
        <div class="max-w-4xl mx-auto">
            
            @if(session('success'))
            <div class="bg-[#c1e9d5] border border-[#0aad0a] text-[#005301] px-4 py-3 rounded-xl mb-6 shadow-sm">
                {{ session('success') }}
            </div>
            @endif

            <div id="desktop-profile-content">
                <!-- Heading -->
                <div class="mb-stack_lg">
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Thông tin cá nhân</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Quản lý thông tin hồ sơ của bạn để bảo mật tài khoản</p>
                </div>
            
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-stack_lg">
                    <!-- Left: Profile Picture -->
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
                                <button type="button" class="absolute bottom-1 right-1 bg-primary text-on-primary w-10 h-10 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform active:scale-95" onclick="document.getElementById('avatarInput').click()">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="previewAvatar(event)">
                                <input type="hidden" name="cropped_avatar" id="croppedAvatarInput">
                            </div>
                            <h3 class="font-headline-md text-headline-md text-on-surface">{{ Auth::user()->name }}</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant mb-stack_md">Thành viên từ {{ Auth::user()->created_at ? Auth::user()->created_at->format('Y') : '2023' }}</p>
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
                            @error('avatar') <small class="text-error mt-2 block text-center">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Right: Form Fields -->
                    <div class="lg:col-span-2 space-y-stack_lg">
                        <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full">
                            <div class="space-y-stack_md h-full flex flex-col justify-between">
                                <div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack_md mb-stack_md">
                                        <div class="space-y-1">
                                            <label class="font-label-md text-label-md text-on-surface-variant px-1">Họ và tên</label>
                                            <input name="name" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-transform" type="text" value="{{ Auth::user()->name }}" required>
                                            @error('name') <small class="text-error mt-1 block">{{ $message }}</small> @enderror
                                        </div>
                                        <div class="space-y-1">
                                            <label class="font-label-md text-label-md text-on-surface-variant px-1">Số điện thoại</label>
                                            <input name="phone" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-transform" type="tel" value="{{ Auth::user()->phone ?? '' }}">
                                            @error('phone') <small class="text-error mt-1 block">{{ $message }}</small> @enderror
                                        </div>
                                    </div>
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

            <!-- Address Section (Desktop) -->
            <div id="desktop-address-content" class="hidden">
                <div class="mb-stack_lg">
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Số địa chỉ</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Quản lý thông tin các địa chỉ nhận hàng của bạn</p>
                </div>
                
                <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm">
                    <div class="flex justify-between items-center border-b border-outline-variant pb-4 mb-4">
                        <h2 class="font-headline-md text-[20px] text-on-surface font-bold">Địa Chỉ Của Tôi</h2>
                        <button type="button" class="text-primary font-label-md flex items-center hover:opacity-80 transition" onclick="openAddressModal()">
                            <span class="material-symbols-outlined mr-1" style="font-size: 20px;">add</span>
                            Thêm địa chỉ mới
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($addresses as $addr)
                        <div class="flex justify-between items-start border border-outline-variant rounded-lg p-5 hover:bg-surface-container-lowest transition shadow-sm">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="font-bold text-on-surface">{{ $addr->fullname }}</span>
                                    <span class="text-outline-variant">|</span>
                                    <span class="text-on-surface-variant">{{ $addr->phone }}</span>
                                </div>
                                <div class="text-sm text-on-surface-variant mb-1">
                                    {{ $addr->specific_address }}
                                </div>
                                @if(!($addr->province && str_contains($addr->specific_address, $addr->province)))
                                <div class="text-sm text-on-surface-variant mb-3">
                                    {{ $addr->ward }}, {{ $addr->district }}, {{ $addr->province }}
                                </div>
                                @endif
                                <div class="flex gap-2 mt-2">
                                    @if($addr->is_default)
                                        <span class="border border-primary text-primary px-2 py-0.5 text-xs rounded-sm bg-primary/10">Mặc định</span>
                                    @endif
                                    @if($addr->type == 'home')
                                        <span class="border border-outline-variant text-on-surface-variant px-2 py-0.5 text-xs rounded-sm">Nhà Riêng</span>
                                    @else
                                        <span class="border border-outline-variant text-on-surface-variant px-2 py-0.5 text-xs rounded-sm">Văn Phòng</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-4">
                                <div class="flex gap-4">
                                    <button type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition" onclick='editAddress(@json($addr))'>Cập nhật</button>
                                    @if(!$addr->is_default)
                                    <button type="button" class="text-error hover:text-red-700 text-sm font-medium transition" onclick="deleteAddress({{ $addr->id }})">Xóa</button>
                                    @endif
                                </div>
                                @if(!$addr->is_default)
                                <button type="button" class="border border-outline-variant bg-white text-on-surface-variant px-3 py-1.5 rounded-sm text-sm hover:bg-surface-container-low transition shadow-sm" onclick="setDefaultAddress({{ $addr->id }})">Thiết lập mặc định</button>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-on-surface-variant text-sm bg-surface-container-low rounded-lg">
                            Bạn chưa có địa chỉ nào.
                        </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <!-- Change Password Section (Desktop) -->
            <div id="desktop-password-content" class="hidden">
                <!-- Heading -->
                <div class="mb-stack_lg">
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Đổi mật khẩu</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Quản lý và thay đổi mật khẩu để bảo vệ tài khoản của bạn</p>
                </div>

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
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-stack_lg">
                    <!-- Column 1: Form (Col-span 3) -->
                    <div class="lg:col-span-3">
                        <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full flex flex-col justify-between">
                            <form action="{{ route('profile.change-password') }}" method="POST" class="space-y-stack_md">
                                @csrf
                                <div class="space-y-4">
                                    <!-- Mật khẩu hiện tại -->
                                    <div class="space-y-1.5">
                                        <label class="font-label-md text-label-md text-on-surface-variant px-1">Mật khẩu hiện tại</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-4 text-outline select-none">lock</span>
                                            <input id="current_password_desk" name="current_password" class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200" type="password" required placeholder="Nhập mật khẩu hiện tại">
                                            <button type="button" class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="current_password_desk">
                                                <span class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                            </button>
                                        </div>
                                        @error('current_password') <small class="text-error mt-1 block px-1">{{ $message }}</small> @enderror
                                    </div>

                                    <!-- Mật khẩu mới -->
                                    <div class="space-y-1.5">
                                        <label class="font-label-md text-label-md text-on-surface-variant px-1">Mật khẩu mới</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-4 text-outline select-none">lock_open</span>
                                            <input id="new_password_desk" name="new_password" class="w-full bg-surface-container-low border-none rounded-lg pl-12 pr-12 py-3.5 focus:ring-2 focus:ring-primary text-body-md font-body-md outline-none transition-all duration-200" type="password" required placeholder="Tạo mật khẩu mới">
                                            <button type="button" class="absolute right-4 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="new_password_desk">
                                                <span class="material-symbols-outlined select-none text-[22px] align-middle">visibility</span>
                                            </button>
                                        </div>
                                        @error('new_password') <small class="text-error mt-1 block px-1">{{ $message }}</small> @enderror
                                    </div>

                                    <!-- Xác nhận mật khẩu mới -->
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

                    <!-- Column 2: Password Checklist (Col-span 2) -->
                    <div class="lg:col-span-2">
                        <section class="bg-white rounded-xl border border-outline-variant p-stack_lg shadow-sm h-full flex flex-col justify-between space-y-6">
                            <div>
                                <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2 mb-4 text-[18px] font-bold">
                                    <span class="material-symbols-outlined text-primary text-[24px]">verified_user</span>
                                    Tiêu chuẩn bảo mật
                                </h3>
                                <p class="text-sm text-on-surface-variant mb-6 font-body-md">Mật khẩu cần tuân thủ các quy tắc bảo mật dưới đây để bảo vệ tài khoản một cách tốt nhất.</p>
                                
                                <!-- Password Strength Meter -->
                                <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl mb-6">
                                    <div class="flex justify-between items-center mb-2 text-sm font-label-md text-on-surface-variant">
                                        <span>Độ mạnh mật khẩu:</span>
                                        <span id="strength-label-desk" class="font-bold text-outline">Chưa nhập</span>
                                    </div>
                                    <div class="h-2.5 w-full bg-surface-container rounded-full overflow-hidden">
                                        <div id="strength-bar-desk" class="h-full w-0 bg-outline transition-all duration-300 rounded-full"></div>
                                    </div>
                                </div>

                                <!-- Security Checklist -->
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

                            <!-- Tips banner -->
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
<!-- 2. MOBILE VIEW (Chỉ hiển thị trên điện thoại) -->
<!-- ============================================== -->
<div class="md:hidden bg-background text-on-surface font-body-md min-h-[100dvh] pb-24 relative">
    
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant flex items-center px-4 h-16 w-full shadow-sm">
        <a id="mobile-back-btn" href="{{ url()->previous() }}" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-primary">arrow_back</span>
        </a>
        <h1 id="mobile-header-title" class="ml-2 font-headline-md text-headline-md-mobile text-primary">Tài khoản</h1>
    </header>

    <main class="pt-20 px-4 max-w-md mx-auto relative z-10">
        
        @if(session('success'))
        <div class="bg-secondary-container border border-primary text-on-primary-container px-4 py-3 rounded-xl mb-6 shadow-sm font-label-md">
            {{ session('success') }}
        </div>
        @endif

        <div id="mobile-profile-content">

        <!-- Profile Section -->
        <section class="flex flex-col items-center mb-8">
            <div class="relative mb-4">
                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-md bg-white">
                    @if(Auth::user()->avatar)
                        <img id="avatarPreviewMobile" class="w-full h-full object-cover" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}"/>
                    @else
                        <img id="avatarPreviewMobile" class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=006e01&color=fff"/>
                    @endif
                </div>
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

        <!-- Form Thông tin cá nhân -->
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

        <!-- List items settings -->
        <section class="space-y-3 mb-8">
            <h3 class="font-headline text-headline-md-mobile text-on-surface mb-2">Cài đặt khác</h3>
            <a href="{{ route('orders') }}" class="flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-primary-container/10 transition-colors group shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary-container">receipt_long</span>
                    <span class="font-label-md text-label-md text-on-surface">Đơn hàng của tôi</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
            </a>
            <a href="#address" onclick="showTab('address'); return false;" class="flex items-center justify-between p-4 bg-white rounded-xl border border-outline-variant active:bg-primary-container/10 transition-colors group shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary-container">location_on</span>
                    <span class="font-label-md text-label-md text-on-surface">Số địa chỉ</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
            </a>
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

        <!-- Address Section (Mobile) -->
        <div id="mobile-address-content" class="hidden">
            <div class="flex justify-between items-center mb-6">
                <button type="button" class="w-full flex items-center justify-center gap-2 bg-primary-container text-white py-3 rounded-xl font-label-md text-label-md shadow-sm active:scale-95 transition-transform" onclick="openAddressModal()">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Thêm địa chỉ mới
                </button>
            </div>
            
            <div class="space-y-4 mb-8">
                @forelse($addresses as $addr)
                <div class="bg-white border border-outline-variant rounded-xl p-5 shadow-sm relative">
                    <div class="flex flex-col gap-1 mb-3 pr-8">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-on-surface text-label-md">{{ $addr->fullname }}</span>
                            <span class="text-outline-variant">|</span>
                            <span class="text-on-surface-variant text-body-md">{{ $addr->phone }}</span>
                        </div>
                        <div class="text-body-md text-on-surface-variant">
                            {{ $addr->specific_address }}
                        </div>
                        @if(!($addr->province && str_contains($addr->specific_address, $addr->province)))
                        <div class="text-body-md text-on-surface-variant">
                            {{ $addr->ward }}, {{ $addr->district }}, {{ $addr->province }}
                        </div>
                        @endif
                    </div>
                    
                    <div class="flex gap-2 mb-4">
                        @if($addr->is_default)
                            <span class="border border-primary text-primary px-2 py-0.5 text-[10px] rounded bg-primary/10 font-medium">Mặc định</span>
                        @endif
                        @if($addr->type == 'home')
                            <span class="border border-outline-variant text-on-surface-variant px-2 py-0.5 text-[10px] rounded font-medium">Nhà Riêng</span>
                        @else
                            <span class="border border-outline-variant text-on-surface-variant px-2 py-0.5 text-[10px] rounded font-medium">Văn Phòng</span>
                        @endif
                    </div>
                    
                    <div class="flex gap-4 border-t border-outline-variant pt-4 mt-2">
                        <button type="button" class="flex-1 text-center text-primary font-label-md text-sm py-2 hover:bg-primary-container/10 rounded-lg transition-colors" onclick='editAddress(@json($addr))'>Sửa</button>
                        @if(!$addr->is_default)
                        <div class="w-[1px] bg-outline-variant my-1"></div>
                        <button type="button" class="flex-1 text-center text-error font-label-md text-sm py-2 hover:bg-error-container/20 rounded-lg transition-colors" onclick="deleteAddress({{ $addr->id }})">Xóa</button>
                        @endif
                    </div>
                    
                    @if(!$addr->is_default)
                    <button type="button" class="w-full mt-2 border border-outline-variant bg-surface-container-lowest text-on-surface px-3 py-2.5 rounded-lg text-sm font-label-md hover:bg-surface-container-low transition-colors shadow-sm" onclick="setDefaultAddress({{ $addr->id }})">Thiết lập mặc định</button>
                    @endif
                </div>
                @empty
                <div class="text-center py-10 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm">
                    <span class="material-symbols-outlined text-outline text-[48px] mb-2">location_off</span>
                    <p class="text-on-surface-variant text-body-md">Bạn chưa có địa chỉ nào.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Form Đổi mật khẩu (Mobile) -->
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
                    
                    <!-- Mật khẩu hiện tại -->
                    <div class="space-y-1">
                        <label class="block font-label-md text-label-md text-on-surface-variant ml-1">Mật khẩu hiện tại</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-outline select-none text-[20px]">lock</span>
                            <input id="current_password_mob" name="current_password" class="w-full h-12 pl-11 pr-11 rounded-lg bg-[#F0F9F0] border-0 ring-1 ring-outline-variant focus:ring-2 focus:ring-primary transition-all text-body-md outline-none" type="password" required placeholder="Mật khẩu hiện tại"/>
                            <button type="button" class="absolute right-3.5 text-outline hover:text-primary transition-colors focus:outline-none toggle-password-visibility" data-target="current_password_mob">
                                <span class="material-symbols-outlined select-none text-[20px]">visibility</span>
                            </button>
                        </div>
                        @error('current_password') <small class="text-error block mt-1 ml-1">{{ $message }}</small> @enderror
                    </div>

                    <!-- Mật khẩu mới -->
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

                    <!-- Xác nhận mật khẩu mới -->
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

                <!-- Checklist & Strength Meter Mobile -->
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

    <!-- BottomNavBar -->
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
        <!-- Active Tab: Tài khoản -->
        <a href="{{ route('profile') }}" class="flex flex-col items-center justify-center bg-primary-container/10 text-primary-container rounded-xl px-4 py-1.5 active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
            <span class="font-label-md text-[12px] font-bold mt-0.5">Tài khoản</span>
        </a>
    </nav>
</div>


<!-- ============================================== -->
<!-- MODALS & SCRIPTS (Dùng chung cho cả 2 view)  -->
<!-- ============================================== -->

<!-- Address Modal UI -->
<style>
@media (max-width: 640px) {
    .addr-row {
        flex-direction: column !important;
        gap: 10px !important;
    }
    .loc-tabs {
        flex-wrap: wrap;
    }
    .loc-tab {
        padding: 8px 10px !important;
        font-size: 13px !important;
        flex: 1 1 30%;
        text-align: center;
    }
    .addr-footer {
        flex-direction: column;
        gap: 10px;
    }
    .addr-footer button {
        width: 100%;
    }
}
</style>
<div class="addr-modal-overlay" id="addressModal" style="padding: 1rem; box-sizing: border-box;">
    <div class="addr-modal" style="width: 100%;">
        <div class="addr-header" id="addressModalTitle">Địa chỉ mới</div>
        <div class="addr-body">
            <input type="hidden" id="addr_id">
            <div class="addr-row">
                <input type="text" id="addr_fullname" class="addr-input" placeholder="Họ và tên">
                <input type="tel" id="addr_phone" class="addr-input" placeholder="Số điện thoại">
            </div>
            <div class="loc-picker-container" id="locPickerContainer">
                <div class="loc-picker-input" id="locPickerInput" onclick="toggleLocPanel()">
                    <span id="locPickerText">Tỉnh/Thành Phố, Quận/Huyện, Phường/Xã</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor"><path d="M2 4L6 8L10 4"></path></svg>
                </div>
                <div class="loc-panel" id="locPanel">
                    <div class="loc-tabs">
                        <div class="loc-tab active" id="tab_province" onclick="switchLocTab('province')">Tỉnh/Thành Phố</div>
                        <div class="loc-tab" id="tab_district" onclick="switchLocTab('district')">Quận/Huyện</div>
                        <div class="loc-tab" id="tab_ward" onclick="switchLocTab('ward')">Phường/Xã</div>
                    </div>
                    <div class="loc-list" id="locList"></div>
                </div>
                <input type="hidden" id="addr_province">
                <input type="hidden" id="addr_district">
                <input type="hidden" id="addr_ward">
            </div>
            <div class="addr-row">
                <textarea id="addr_specific" class="addr-input" rows="3" placeholder="Địa chỉ cụ thể" style="resize: none;"></textarea>
            </div>
            <button type="button" id="resetGpsBtn" style="display:none; margin-bottom: 15px; color: #dc2626; background: none; border: none; font-size: 14px; font-weight: 500; cursor: pointer; align-items: center; padding: 0;" onclick="resetToManual()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Hủy vị trí GPS, nhập lại thủ công
            </button>
            <div class="fake-map-box" id="fakeMapBox">
                <button type="button" class="fake-map-btn" id="btn-get-gps" onclick="getCurrentLocation(this)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span id="gps-btn-text">Thêm vị trí</span>
                </button>
            </div>
            <div style="margin-bottom: 10px; font-size: 14px; color: #555;">Loại địa chỉ:</div>
            <div class="addr-type-btns">
                <button type="button" class="addr-type-btn active" id="btnTypeHome" onclick="setAddrType('home')">Nhà Riêng</button>
                <button type="button" class="addr-type-btn" id="btnTypeOffice" onclick="setAddrType('office')">Văn Phòng</button>
            </div>
            <input type="hidden" id="addr_type" value="home">
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="addr_default" style="width: 16px; height: 16px; cursor: pointer;">
                <label for="addr_default" style="font-size: 14px; cursor: pointer; color: #555; user-select: none; margin: 0;">Đặt làm địa chỉ mặc định</label>
            </div>
        </div>
        <div class="addr-footer">
            <button type="button" class="btn-cancel" onclick="closeAddressModal()">Trở Lại</button>
            <button type="button" class="btn-submit" onclick="saveAddress()">Hoàn thành</button>
        </div>
    </div>
</div>

<div id="cropperModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; padding: 1rem; box-sizing: border-box;">
    <div style="background:#fff; padding:20px; border-radius:12px; width:100%; max-width:500px; text-align:center; position: relative; max-height: 90vh; overflow-y: auto;">
        <h4 style="margin-bottom:15px; font-weight: 600; color: #374151;">Chỉnh sửa ảnh đại diện</h4>
        <div style="width:100%; max-height:400px; overflow:hidden; margin-bottom:15px; background: #f3f4f6; border-radius: 8px;">
            <img id="imageToCrop" style="max-width:100%; display:block;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="closeCropperModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #374151; font-weight: 500; cursor:pointer;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="cropImage()" style="padding: 8px 16px; border-radius: 6px; background: #10b981; color:#fff; border:none; font-weight: 500; cursor:pointer;">Cắt & Lưu</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="{{ asset('js/profile.js') }}"></script>
@if($errors->has('current_password') || $errors->has('new_password'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showTab('password');
    });
</script>
@endif
@endpush
@endsection
