@extends('backend.layouts.app')

@section('title', 'Cài đặt hệ thống')

@section('content')
    <div class="settings-page p-4 sm:p-6 space-y-6" data-active-section="{{ session('active_section', session('error_section', 'store')) }}">

        <!-- 1. Tiêu đề trang -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Cài đặt hệ thống</h2>
                <p class="text-gray-500 text-sm mt-1">Quản lý các cấu hình chung, đơn hàng, giao hàng và các kênh thanh toán của cửa hàng.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm animate-fade-in">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-medium space-y-1 shadow-sm">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>Vui lòng kiểm tra lại thông tin cấu hình:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- 2. Khung Tab cài đặt -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            
            <!-- Mobile Navigation Dropdown (lg:hidden) -->
            <div class="lg:hidden w-full relative z-10">
                <button type="button" id="mobile-tab-selector-btn" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm flex items-center justify-between font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 active:bg-gray-50 transition-all">
                    <span class="flex items-center gap-2" id="mobile-tab-active-label">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">storefront</span>
                        <span>Thông tin cửa hàng</span>
                    </span>
                    <span class="material-symbols-outlined text-[20px] text-gray-500">expand_more</span>
                </button>
                <div id="mobile-tab-dropdown-menu" class="hidden absolute top-full left-0 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-lg z-10 py-1.5 overflow-hidden">
                    <div class="mobile-tab-option px-4 py-3 hover:bg-gray-50 flex items-center gap-3 cursor-pointer text-sm font-semibold text-gray-700 active:bg-gray-100" data-value="store">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">storefront</span>
                        <span>Thông tin cửa hàng</span>
                    </div>
                    <div class="mobile-tab-option px-4 py-3 hover:bg-gray-50 flex items-center gap-3 cursor-pointer text-sm font-semibold text-gray-700 active:bg-gray-100" data-value="orders">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">shopping_bag</span>
                        <span>Cài đặt đơn hàng</span>
                    </div>
                    <div class="mobile-tab-option px-4 py-3 hover:bg-gray-50 flex items-center gap-3 cursor-pointer text-sm font-semibold text-gray-700 active:bg-gray-100" data-value="shipping">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">local_shipping</span>
                        <span>Cài đặt giao hàng</span>
                    </div>
                    <div class="mobile-tab-option px-4 py-3 hover:bg-gray-50 flex items-center gap-3 cursor-pointer text-sm font-semibold text-gray-700 active:bg-gray-100" data-value="payment">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">credit_card</span>
                        <span>Cài đặt thanh toán</span>
                    </div>
                    <div class="mobile-tab-option px-4 py-3 hover:bg-gray-50 flex items-center gap-3 cursor-pointer text-sm font-semibold text-gray-700 active:bg-gray-100" data-value="loyalty">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">award_star</span>
                        <span>Điểm tích lũy</span>
                    </div>
                    <div class="mobile-tab-option px-4 py-3 hover:bg-gray-50 flex items-center gap-3 cursor-pointer text-sm font-semibold text-gray-700 active:bg-gray-100" data-value="notifications">
                        <span class="material-symbols-outlined text-[20px] text-emerald-500 shrink-0">notifications</span>
                        <span>Thông báo</span>
                    </div>
                </div>
            </div>

            <!-- Desktop Navigation Sidebar (lg:block hidden) -->
            <div class="hidden lg:block lg:col-span-1 bg-white p-3 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex flex-col gap-1.5" role="tablist">
                    <button type="button" data-target="store" 
                        class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer text-left w-full justify-start active-tab">
                        <span class="material-symbols-outlined text-[20px] shrink-0">storefront</span>
                        <span>Thông tin cửa hàng</span>
                    </button>

                    <button type="button" data-target="orders" 
                        class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer text-left w-full justify-start text-gray-500 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[20px] shrink-0">shopping_bag</span>
                        <span>Cài đặt đơn hàng</span>
                    </button>

                    <button type="button" data-target="shipping" 
                        class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer text-left w-full justify-start text-gray-500 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[20px] shrink-0">local_shipping</span>
                        <span>Cài đặt giao hàng</span>
                    </button>

                    <button type="button" data-target="payment" 
                        class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer text-left w-full justify-start text-gray-500 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[20px] shrink-0">credit_card</span>
                        <span>Cài đặt thanh toán</span>
                    </button>

                    <button type="button" data-target="loyalty" 
                        class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer text-left w-full justify-start text-gray-500 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[20px] shrink-0">award_star</span>
                        <span>Điểm tích lũy</span>
                    </button>

                    <button type="button" data-target="notifications" 
                        class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer text-left w-full justify-start text-gray-500 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[20px] shrink-0">notifications</span>
                        <span>Thông báo</span>
                    </button>
                </div>
            </div>

            <!-- Nội dung biểu mẫu các Tab -->
            <div class="lg:col-span-3 space-y-6">

                <!-- SECTION 1: THÔNG TIN CỬA HÀNG -->
                <div id="section-store" class="tab-pane bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base">Thông tin cửa hàng</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Cấu hình thông tin liên hệ và hiển thị của cửa hàng.</p>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="store">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="store_name" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tên cửa hàng</label>
                                <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $settings['store_name'] ?? 'Happy Tea') }}" 
                                    class="w-full border {{ $errors->has('store_name') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                @error('store_name') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="store_phone" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Số điện thoại</label>
                                <input type="text" name="store_phone" id="store_phone" value="{{ old('store_phone', $settings['store_phone'] ?? '0123456789') }}" 
                                    class="w-full border {{ $errors->has('store_phone') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                @error('store_phone') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="store_email" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Email liên hệ</label>
                                <input type="email" name="store_email" id="store_email" value="{{ old('store_email', $settings['store_email'] ?? 'admin@happytea.com') }}" 
                                    class="w-full border {{ $errors->has('store_email') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                @error('store_email') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-3 p-4 bg-gray-50 rounded-xl border border-gray-150">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[18px] text-emerald-500 shrink-0">schedule</span>
                                        Giờ hoạt động
                                    </span>
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold">HẰNG NGÀY</span>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mt-1">
                                    <!-- Field Mở cửa -->
                                    <div class="flex-1 flex flex-col gap-1.5">
                                        <label for="store_open_time" class="text-[11px] font-bold text-gray-500 uppercase">Mở cửa</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-3 text-gray-400 text-[18px]">schedule</span>
                                            <input type="text" name="store_open_time" id="store_open_time" value="{{ old('store_open_time', date('H:i', strtotime($settings['store_open_time'] ?? '08:00'))) }}" 
                                                class="settings-time-picker w-full border {{ $errors->has('store_open_time') ? 'input-error' : 'border-gray-200' }} rounded-xl pl-9 pr-3 py-2.5 outline-none text-gray-800 text-sm font-semibold focus:border-emerald-500 transition-colors bg-white cursor-pointer" placeholder="00:00">
                                        </div>
                                        @error('store_open_time') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Arrow or Line connector on Desktop (hidden on mobile) -->
                                    <div class="hidden sm:flex items-center justify-center pt-5 text-gray-300">
                                        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                                    </div>

                                    <!-- Field Đóng cửa -->
                                    <div class="flex-1 flex flex-col gap-1.5">
                                        <label for="store_close_time" class="text-[11px] font-bold text-gray-500 uppercase">Đóng cửa</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-3 text-gray-400 text-[18px]">schedule</span>
                                            <input type="text" name="store_close_time" id="store_close_time" value="{{ old('store_close_time', date('H:i', strtotime($settings['store_close_time'] ?? '22:00'))) }}" 
                                                class="settings-time-picker w-full border {{ $errors->has('store_close_time') ? 'input-error' : 'border-gray-200' }} rounded-xl pl-9 pr-3 py-2.5 outline-none text-gray-800 text-sm font-semibold focus:border-emerald-500 transition-colors bg-white cursor-pointer" placeholder="00:00">
                                        </div>
                                        @error('store_close_time') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <!-- Preview -->
                                <p class="text-[11px] text-gray-400 mt-1 italic animate-fade-in" id="hours-preview-text">
                                    Cửa hàng hoạt động từ {{ date('H:i', strtotime($settings['store_open_time'] ?? '08:00')) }} đến {{ date('H:i', strtotime($settings['store_close_time'] ?? '22:00')) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="store_address" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Địa chỉ cửa hàng</label>
                            <input type="text" name="store_address" id="store_address" value="{{ old('store_address', $settings['store_address'] ?? '180 Cao Lỗ, Phường 4, Quận 8, TP. Hồ Chí Minh') }}" 
                                class="w-full border {{ $errors->has('store_address') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                            @error('store_address') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="store_facebook_url" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Facebook link</label>
                                <input type="text" name="store_facebook_url" id="store_facebook_url" value="{{ old('store_facebook_url', $settings['store_facebook_url'] ?? '') }}" 
                                    class="w-full border {{ $errors->has('store_facebook_url') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors" placeholder="https://facebook.com/trang-cua-hang">
                                @error('store_facebook_url') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="store_zalo_url" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Zalo link</label>
                                <input type="text" name="store_zalo_url" id="store_zalo_url" value="{{ old('store_zalo_url', $settings['store_zalo_url'] ?? '') }}" 
                                    class="w-full border {{ $errors->has('store_zalo_url') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors" placeholder="https://zalo.me/so-dien-thoai">
                                @error('store_zalo_url') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="store_latitude" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tọa độ Vĩ độ (Latitude)</label>
                                <input type="text" name="store_latitude" id="store_latitude" value="{{ old('store_latitude', $settings['store_latitude'] ?? '10.7380') }}" 
                                    class="w-full border {{ $errors->has('store_latitude') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors" placeholder="VD: 10.7380">
                                @error('store_latitude') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="store_longitude" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tọa độ Kinh độ (Longitude)</label>
                                <input type="text" name="store_longitude" id="store_longitude" value="{{ old('store_longitude', $settings['store_longitude'] ?? '106.6778') }}" 
                                    class="w-full border {{ $errors->has('store_longitude') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors" placeholder="VD: 106.6778">
                                @error('store_longitude') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Logo cửa hàng</span>

                            <div class="flex items-center gap-3 mb-1 flex-wrap sm:flex-nowrap">
                                <div class="w-16 h-16 rounded-xl overflow-hidden border-2 border-emerald-400 bg-white shadow-sm shrink-0 flex items-center justify-center">
                                    <img id="logo-preview-img" src="{{ asset($settings['store_logo'] ?? '/images/logo/black.png') }}" class="max-w-full max-h-full object-contain" alt="Logo preview">
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Logo đang dùng</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Tải lên file mới để thay đổi logo cửa hàng.</p>
                                </div>
                            </div>

                            <div class="flex-1 w-full space-y-1.5">
                                <input type="file" name="store_logo" id="store_logo" accept="image/*" class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer w-full">
                                <p class="text-[11px] text-gray-400">Định dạng JPG, JPEG, PNG, WEBP, SVG tối đa 2MB.</p>
                                @error('store_logo') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="settings-page__save-actions flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 h-11 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECTION 2: CÀI ĐẶT ĐƠN HÀNG -->
                <div id="section-orders" class="tab-pane bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5 hidden">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base">Cài đặt đơn hàng</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Thiết lập quy trình tiếp nhận và xử lý đơn hàng.</p>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="orders">

                        <div class="space-y-4">
                            <!-- Toggle Bật/tắt nhận đơn -->
                            <div class="settings-page__setting-row">
                                <div class="space-y-0.5">
                                    <label for="orders_enabled" class="text-sm font-bold text-gray-800">Mở nhận đơn hàng</label>
                                    <p class="text-xs text-gray-500">Bật/tắt tiếp nhận đơn hàng mới của hệ thống.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        {{-- Hidden input gửi "0" khi checkbox bị bỏ tick — HTML không tự gửi field cho checkbox unchecked,
                                             nếu thiếu input này thì backend nhận request rỗng và validate "required" báo lỗi sai. --}}
                                        <input type="hidden" name="orders_enabled" value="0">
                                        <input type="checkbox" name="orders_enabled" id="orders_enabled" value="1" class="sr-only peer" {{ ($settings['orders_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Toggle Hủy đơn online chưa thanh toán -->
                            <div class="settings-page__setting-row">
                                <div class="space-y-0.5">
                                    <label for="auto_cancel_unpaid_enabled" class="text-sm font-bold text-gray-800">Hủy đơn chưa thanh toán online</label>
                                    <p class="text-xs text-gray-500">Tự động hủy các đơn thanh toán trực tuyến chưa hoàn tất giao dịch.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="auto_cancel_unpaid_enabled" value="0">
                                        <input type="checkbox" name="auto_cancel_unpaid_enabled" id="auto_cancel_unpaid_enabled" value="1" class="sr-only peer" {{ ($settings['auto_cancel_unpaid_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Thời gian tự động hủy (Phút) -->
                            <div class="flex flex-col gap-2 transition-all duration-200">
                                <label for="auto_cancel_unpaid_minutes" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Thời gian hủy đơn online</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="auto_cancel_unpaid_minutes" id="auto_cancel_unpaid_minutes" min="0" value="{{ old('auto_cancel_unpaid_minutes', $settings['auto_cancel_unpaid_minutes'] ?? '30') }}" 
                                        class="w-full border {{ $errors->has('auto_cancel_unpaid_minutes') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">phút</span>
                                </div>
                                @error('auto_cancel_unpaid_minutes') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="settings-page__save-actions flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 h-11 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECTION 3: CÀI ĐẶT GIAO HÀNG -->
                <div id="section-shipping" class="tab-pane bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5 hidden">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base">Cài đặt giao hàng</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Thiết lập biểu phí vận chuyển và khoảng cách giới hạn.</p>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="shipping">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="shipping_base_fee" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Phí giao hàng cơ bản (2km đầu)</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="shipping_base_fee" id="shipping_base_fee" min="0" value="{{ old('shipping_base_fee', $settings['shipping_base_fee'] ?? '15000') }}" 
                                        class="w-full border {{ $errors->has('shipping_base_fee') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">VNĐ</span>
                                </div>
                                @error('shipping_base_fee') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="shipping_fee_per_km" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Phí mỗi km tiếp theo</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="shipping_fee_per_km" id="shipping_fee_per_km" min="0" value="{{ old('shipping_fee_per_km', $settings['shipping_fee_per_km'] ?? '5000') }}" 
                                        class="w-full border {{ $errors->has('shipping_fee_per_km') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">VNĐ/km</span>
                                </div>
                                @error('shipping_fee_per_km') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="shipping_max_distance_km" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Khoảng cách giao tối đa</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="shipping_max_distance_km" id="shipping_max_distance_km" min="0" value="{{ old('shipping_max_distance_km', $settings['shipping_max_distance_km'] ?? '15') }}" 
                                        class="w-full border {{ $errors->has('shipping_max_distance_km') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">km</span>
                                </div>
                                @error('shipping_max_distance_km') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="free_shipping_minimum" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Mức miễn phí vận chuyển</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="free_shipping_minimum" id="free_shipping_minimum" min="0" value="{{ old('free_shipping_minimum', $settings['free_shipping_minimum'] ?? '150000') }}" 
                                        class="w-full border {{ $errors->has('free_shipping_minimum') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">VNĐ</span>
                                </div>
                                @error('free_shipping_minimum') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Cấu hình phụ thu thời tiết xấu -->
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100/70 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                                <div class="space-y-0.5">
                                    <span class="text-sm font-bold text-gray-800">Phụ thu phí thời tiết xấu</span>
                                    <p class="text-[10px] text-gray-400">Tự động tăng phí vận chuyển khi trời mưa bão.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="weather_surcharge_enabled" value="0">
                                        <input type="checkbox" name="weather_surcharge_enabled" id="weather_surcharge_enabled" value="1" class="sr-only peer" {{ ($settings['weather_surcharge_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            {{-- Ép tình trạng thời tiết: dùng khi cần trình diễn/kiểm thử mà ngoài trời không mưa.
                                 Để "Tự động" thì hệ thống đọc thời tiết thật tại tọa độ giao hàng. --}}
                            <div class="flex flex-col gap-1.5">
                                <label for="weather_override" class="text-[11px] font-bold text-gray-500 uppercase">Tình trạng thời tiết áp dụng</label>
                                @php $weatherOverride = old('weather_override', $settings['weather_override'] ?? 'auto'); @endphp
                                <select name="weather_override" id="weather_override"
                                    class="w-full border {{ $errors->has('weather_override') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2 outline-none text-gray-700 text-xs focus:border-emerald-500 bg-white">
                                    <option value="auto" {{ $weatherOverride === 'auto' ? 'selected' : '' }}>Tự động theo thời tiết thật</option>
                                    <option value="light_rain" {{ $weatherOverride === 'light_rain' ? 'selected' : '' }}>Ép: Mưa nhỏ</option>
                                    <option value="heavy_rain" {{ $weatherOverride === 'heavy_rain' ? 'selected' : '' }}>Ép: Mưa to</option>
                                    <option value="storm" {{ $weatherOverride === 'storm' ? 'selected' : '' }}>Ép: Giông bão</option>
                                </select>
                                <p class="text-[10px] text-gray-400">Chỉ nên chọn "Ép" khi cần trình diễn. Khi đó hệ thống bỏ qua dịch vụ thời tiết và luôn áp mức đã chọn.</p>
                                @error('weather_override') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="weather_light_rain_percent" class="text-[11px] font-bold text-gray-500 uppercase">Mưa nhỏ</label>
                                    <div class="settings-page__input-suffix-wrapper">
                                        <input type="number" name="weather_light_rain_percent" id="weather_light_rain_percent" min="0" max="100" value="{{ old('weather_light_rain_percent', $settings['weather_light_rain_percent'] ?? '5') }}" 
                                            class="w-full border {{ $errors->has('weather_light_rain_percent') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2 outline-none text-gray-700 text-xs focus:border-emerald-500">
                                        <span class="input-suffix">%</span>
                                    </div>
                                    @error('weather_light_rain_percent') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="weather_heavy_rain_percent" class="text-[11px] font-bold text-gray-500 uppercase">Mưa to</label>
                                    <div class="settings-page__input-suffix-wrapper">
                                        <input type="number" name="weather_heavy_rain_percent" id="weather_heavy_rain_percent" min="0" max="100" value="{{ old('weather_heavy_rain_percent', $settings['weather_heavy_rain_percent'] ?? '10') }}" 
                                            class="w-full border {{ $errors->has('weather_heavy_rain_percent') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2 outline-none text-gray-700 text-xs focus:border-emerald-500">
                                        <span class="input-suffix">%</span>
                                    </div>
                                    @error('weather_heavy_rain_percent') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="weather_storm_percent" class="text-[11px] font-bold text-gray-500 uppercase">Bão / Giông mạnh</label>
                                    <div class="settings-page__input-suffix-wrapper">
                                        <input type="number" name="weather_storm_percent" id="weather_storm_percent" min="0" max="100" value="{{ old('weather_storm_percent', $settings['weather_storm_percent'] ?? '15') }}" 
                                            class="w-full border {{ $errors->has('weather_storm_percent') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2 outline-none text-gray-700 text-xs focus:border-emerald-500">
                                        <span class="input-suffix">%</span>
                                    </div>
                                    @error('weather_storm_percent') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="settings-page__save-actions flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 h-11 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECTION 4: CÀI ĐẶT THANH TOÁN -->
                <div id="section-payment" class="tab-pane bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5 hidden">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base">Cài đặt thanh toán</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Bật tắt các cổng thanh toán và thiết lập môi trường.</p>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="payment">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Chế độ môi trường (Chỉ áp dụng Thử nghiệm Sandbox) -->
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Môi trường thanh toán</label>
                                <input type="hidden" name="payment_environment" id="payment_environment" value="sandbox">
                                <div class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold w-fit">
                                    <span class="material-symbols-outlined text-[18px]">science</span>
                                    Thử nghiệm (Sandbox)
                                </div>
                            </div>
                        </div>

                        <!-- Danh sách các card phương thức thanh toán -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Card COD -->
                            <div class="settings-page__payment-card bg-gray-50 p-4 rounded-xl border border-gray-200/60 flex flex-col justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[20px] text-gray-500">payments</span>
                                            Thanh toán COD
                                        </span>
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold">MẶC ĐỊNH</span>
                                    </div>
                                    <p class="text-xs text-gray-500 leading-normal">Cho phép khách hàng thanh toán tiền mặt trực tiếp khi nhận hàng.</p>
                                </div>
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200/50">
                                    <span class="text-xs font-semibold text-gray-500">Kích hoạt</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="cod_enabled" value="0">
                                        <input type="checkbox" name="cod_enabled" id="cod_enabled" value="1" class="sr-only peer" {{ ($settings['cod_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>


                            <!-- Card VNPay -->
                            <div class="settings-page__payment-card bg-gray-50 p-4 rounded-xl border border-gray-200/60 flex flex-col justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between flex-wrap gap-1">
                                        <span class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded bg-[#003c71] flex items-center justify-center text-[10px] text-white font-bold shrink-0">V</span>
                                            VNPay
                                        </span>
                                        @if ($paymentStatus['vnpay'])
                                            <span id="vnpay-config-badge" class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold">ĐÃ CẤU HÌNH</span>
                                        @else
                                            <span id="vnpay-config-badge" class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-lg text-[10px] font-bold">CHƯA CẤU HÌNH</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 leading-normal">Cổng thanh toán VNPay (ATM, Visa/Master/JCB, QR...). Bảo mật các khóa trực tiếp bằng tệp cấu hình hệ thống.</p>
                                </div>
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200/50">
                                    <span class="text-xs font-semibold text-gray-500">Kích hoạt</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="vnpay_enabled" value="0">
                                        <input type="checkbox" name="vnpay_enabled" id="vnpay_enabled" value="1" class="sr-only peer" {{ ($settings['vnpay_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="settings-page__save-actions flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 h-11 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECTION 5: ĐIỂM TÍCH LŨY -->
                <div id="section-loyalty" class="tab-pane bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5 hidden">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base">Điểm tích lũy thành viên</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Thiết lập cơ chế tích lũy và sử dụng điểm của khách hàng.</p>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="loyalty">

                        <!-- Toggle Kích hoạt tích điểm -->
                        <div class="settings-page__setting-row">
                            <div class="space-y-0.5">
                                <label for="loyalty_enabled" class="text-sm font-bold text-gray-800">Chương trình tích điểm</label>
                                <p class="text-xs text-gray-500">Bật/tắt tích điểm thành viên khi mua hàng.</p>
                            </div>
                            <div class="flex items-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="loyalty_enabled" value="0">
                                    <input type="checkbox" name="loyalty_enabled" id="loyalty_enabled" value="1" class="sr-only peer" {{ ($settings['loyalty_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="loyalty_money_per_point" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tỷ lệ tích (Số tiền tương ứng 1 điểm)</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="loyalty_money_per_point" id="loyalty_money_per_point" min="0" value="{{ old('loyalty_money_per_point', $settings['loyalty_money_per_point'] ?? '10000') }}" 
                                        class="w-full border {{ $errors->has('loyalty_money_per_point') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">VNĐ</span>
                                </div>
                                @error('loyalty_money_per_point') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="loyalty_point_value" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Giá trị quy đổi của 1 điểm khi mua</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="loyalty_point_value" id="loyalty_point_value" min="0" value="{{ old('loyalty_point_value', $settings['loyalty_point_value'] ?? '1') }}" 
                                        class="w-full border {{ $errors->has('loyalty_point_value') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">VNĐ</span>
                                </div>
                                @error('loyalty_point_value') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label for="loyalty_max_redeem_percent" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Giảm tối đa / Đơn hàng</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="loyalty_max_redeem_percent" id="loyalty_max_redeem_percent" min="0" max="100" value="{{ old('loyalty_max_redeem_percent', $settings['loyalty_max_redeem_percent'] ?? '100') }}" 
                                        class="w-full border {{ $errors->has('loyalty_max_redeem_percent') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">%</span>
                                </div>
                                @error('loyalty_max_redeem_percent') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="loyalty_min_points_to_redeem" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Số điểm tối thiểu để được đổi</label>
                                <div class="settings-page__input-suffix-wrapper">
                                    <input type="number" name="loyalty_min_points_to_redeem" id="loyalty_min_points_to_redeem" min="0" value="{{ old('loyalty_min_points_to_redeem', $settings['loyalty_min_points_to_redeem'] ?? '10') }}" 
                                        class="w-full border {{ $errors->has('loyalty_min_points_to_redeem') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                    <span class="input-suffix">điểm</span>
                                </div>
                                @error('loyalty_min_points_to_redeem') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Minh họa trực quan -->
                        <div class="flex items-center gap-3 p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 text-indigo-800">
                            <span class="material-symbols-outlined text-[20px] shrink-0 text-indigo-500">info</span>
                            <p class="text-xs font-medium" id="loyalty-illustration-text">
                                Ví dụ: Chi tiêu 10.000 VNĐ nhận được 1 điểm.
                            </p>
                        </div>

                        <div class="settings-page__save-actions flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 h-11 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SECTION 6: THÔNG BÁO -->
                <div id="section-notifications" class="tab-pane bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5 hidden">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-gray-900 text-base">Hệ thống thông báo</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Cấu hình các kịch bản gửi thông báo email hoặc số điện thoại.</p>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="notifications">

                        <div class="space-y-4">
                            <!-- Toggle Gửi email cho khách -->
                            <div class="settings-page__setting-row">
                                <div class="space-y-0.5">
                                    <label for="order_confirmation_email_enabled" class="text-sm font-bold text-gray-800">Gửi Email xác nhận cho khách</label>
                                    <p class="text-xs text-gray-500">Tự động gửi email thông báo chi tiết khi khách đặt hàng thành công.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="order_confirmation_email_enabled" value="0">
                                        <input type="checkbox" name="order_confirmation_email_enabled" id="order_confirmation_email_enabled" value="1" class="sr-only peer" {{ ($settings['order_confirmation_email_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Toggle Gửi email cho admin -->
                            <div class="settings-page__setting-row">
                                <div class="space-y-0.5">
                                    <label for="new_order_admin_notification_enabled" class="text-sm font-bold text-gray-800">Gửi thông báo đơn mới cho Admin</label>
                                    <p class="text-xs text-gray-500">Báo về email hệ thống ngay khi có đơn đặt hàng mới.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="new_order_admin_notification_enabled" value="0">
                                        <input type="checkbox" name="new_order_admin_notification_enabled" id="new_order_admin_notification_enabled" value="1" class="sr-only peer" {{ ($settings['new_order_admin_notification_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Toggle Cảnh báo tồn kho thấp -->
                            <div class="settings-page__setting-row">
                                <div class="space-y-0.5">
                                    <label for="low_stock_notification_enabled" class="text-sm font-bold text-gray-800">Cảnh báo tồn kho nguyên liệu thấp</label>
                                    <p class="text-xs text-gray-500">Gửi cảnh báo khi nguyên liệu chạm ngưỡng tối thiểu.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="low_stock_notification_enabled" value="0">
                                        <input type="checkbox" name="low_stock_notification_enabled" id="low_stock_notification_enabled" value="1" class="sr-only peer" {{ ($settings['low_stock_notification_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Card Email nhận thông báo -->
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100/70 space-y-4">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Cấu hình liên hệ nhận tin</span>
                            <div class="flex flex-col gap-2">
                                <label for="notification_email" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Email nhận thông báo hệ thống</label>
                                <input type="email" name="notification_email" id="notification_email" value="{{ old('notification_email', $settings['notification_email'] ?? 'admin@happytea.com') }}"
                                    class="w-full border {{ $errors->has('notification_email') ? 'input-error' : 'border-gray-200' }} rounded-xl px-3 py-2.5 outline-none text-gray-700 text-sm focus:border-emerald-500 transition-colors">
                                @error('notification_email') <p class="text-red-500 text-xs mt-0.5 field-error-msg">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="settings-page__save-actions flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 h-11 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const mobileBtn = document.getElementById('mobile-tab-selector-btn');
    const mobileMenu = document.getElementById('mobile-tab-dropdown-menu');

    // Chuyển giữa các tab cấu hình
    function switchTab(targetId) {
        localStorage.setItem('active_settings_tab', targetId);
        if (history.pushState) {
            history.pushState(null, null, `#${targetId}`);
        } else {
            window.location.hash = targetId;
        }

        tabButtons.forEach(btn => {
            const btnTarget = btn.getAttribute('data-target');
            if (btnTarget === targetId) {
                btn.classList.add('bg-emerald-50', 'text-emerald-600', 'active-tab', 'border-emerald-200');
                btn.classList.remove('text-gray-500', 'hover:bg-gray-50');
            } else {
                btn.classList.remove('bg-emerald-50', 'text-emerald-600', 'active-tab', 'border-emerald-200');
                btn.classList.add('text-gray-500', 'hover:bg-gray-50');
            }
        });

        const activeBtn = document.querySelector(`.tab-btn[data-target="${targetId}"]`);
        const mobileLabel = document.getElementById('mobile-tab-active-label');
        if (activeBtn && mobileLabel) {
            mobileLabel.innerHTML = activeBtn.innerHTML;
        }

        tabPanes.forEach(pane => {
            const paneId = pane.getAttribute('id');
            if (paneId === `section-${targetId}`) {
                pane.classList.remove('hidden');
            } else {
                pane.classList.add('hidden');
            }
        });

        if (activeBtn && activeBtn.scrollIntoView) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            switchTab(targetId);
        });
    });

    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function () {
            mobileMenu.classList.add('hidden');
        });

        mobileMenu.querySelectorAll('.mobile-tab-option').forEach(opt => {
            opt.addEventListener('click', function () {
                const target = this.getAttribute('data-value');
                switchTab(target);
                mobileMenu.classList.add('hidden');
            });
        });
    }

    const settingsPage = document.querySelector('.settings-page');
    let initialSection = settingsPage ? settingsPage.dataset.activeSection : null;

    if (!initialSection && window.location.hash) {
        const hashVal = window.location.hash.substring(1);
        const validSections = ['store', 'orders', 'shipping', 'payment', 'loyalty', 'notifications'];
        if (validSections.includes(hashVal)) {
            initialSection = hashVal;
        }
    }

    if (!initialSection) {
        const storedTab = localStorage.getItem('active_settings_tab');
        const validSections = ['store', 'orders', 'shipping', 'payment', 'loyalty', 'notifications'];
        if (storedTab && validSections.includes(storedTab)) {
            initialSection = storedTab;
        }
    }

    switchTab(initialSection || 'store');

    // Tự khóa/mở nhóm ô nhập phụ thuộc vào một công tắc bật/tắt
    const bindToggleDependency = (toggle, inputs) => {
        if (!toggle) return;
        // Áp trạng thái khóa/mở cho nhóm ô nhập theo công tắc hiện tại
        const apply = () => {
            const isEnabled = toggle.checked;
            inputs.forEach(input => {
                if (!input) return;
                input.readOnly = !isEnabled;
                const parent = input.closest('.flex-col');
                if (parent) {
                    parent.style.opacity = isEnabled ? '1' : '0.5';
                    parent.style.pointerEvents = isEnabled ? 'auto' : 'none';
                }
            });
        };
        toggle.addEventListener('change', apply);
        apply();
    };

    bindToggleDependency(
        document.getElementById('auto_cancel_unpaid_enabled'),
        [document.getElementById('auto_cancel_unpaid_minutes')]
    );

    bindToggleDependency(
        document.getElementById('weather_surcharge_enabled'),
        [
            document.getElementById('weather_light_rain_percent'),
            document.getElementById('weather_heavy_rain_percent'),
            document.getElementById('weather_storm_percent'),
            document.getElementById('weather_override')
        ]
    );

    const timePickers = document.querySelectorAll('.settings-time-picker');
    const openTimeInput = document.getElementById('store_open_time');
    const closeTimeInput = document.getElementById('store_close_time');
    const hoursPreview = document.getElementById('hours-preview-text');

    // Hiện ngay câu mô tả giờ mở cửa khi admin chỉnh, để thấy trước kết quả
    const updateHoursPreview = () => {
        if (!hoursPreview) return;
        const openVal = openTimeInput ? openTimeInput.value.trim() : '';
        const closeVal = closeTimeInput ? closeTimeInput.value.trim() : '';
        
        if (openVal && closeVal) {
            hoursPreview.textContent = `Cửa hàng hoạt động từ ${openVal} đến ${closeVal}`;
            hoursPreview.classList.remove('text-red-500');
            hoursPreview.classList.add('text-gray-400');
        } else {
            hoursPreview.textContent = `Vui lòng chọn đầy đủ giờ mở cửa và đóng cửa.`;
            hoursPreview.classList.add('text-red-500');
            hoursPreview.classList.remove('text-gray-400');
        }
    };

    if (timePickers.length && typeof flatpickr !== 'undefined') {
        timePickers.forEach(picker => {
            const currentVal = picker.value ? picker.value.trim() : '';
            flatpickr(picker, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 5,
                allowInput: true,
                disableMobile: true,
                defaultDate: currentVal || null,
                onChange: function() {
                    updateHoursPreview();
                }
            });
        });
    }

    updateHoursPreview();

    const loyaltyMoneyInput = document.getElementById('loyalty_money_per_point');
    const loyaltyIllustration = document.getElementById('loyalty-illustration-text');
    if (loyaltyMoneyInput && loyaltyIllustration) {
        // Minh họa tức thì: với tỷ lệ tích điểm vừa nhập thì đơn 100.000đ được bao nhiêu điểm
        const updateLoyaltyIllustration = () => {
            const value = parseFloat(loyaltyMoneyInput.value) || 0;
            const formatted = new Intl.NumberFormat('vi-VN').format(value);
            loyaltyIllustration.textContent = `Ví dụ: Chi tiêu ${formatted} VNĐ nhận được 1 điểm.`;
        };
        loyaltyMoneyInput.addEventListener('input', updateLoyaltyIllustration);
        updateLoyaltyIllustration();
    }

    const logoInput = document.getElementById('store_logo');
    const logoPreview = document.getElementById('logo-preview-img');
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    if (window.AdminAlert) {
                        window.AdminAlert.error('Kích thước logo không được vượt quá 2MB!', 'Tệp quá lớn');
                    } else {
                        alert('Kích thước logo không được vượt quá 2MB!');
                    }
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    logoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush

