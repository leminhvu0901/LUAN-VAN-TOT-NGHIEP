@extends('frontend.layouts.app')

@section('body_class', 'profile-body')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="min-h-screen bg-background text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container pb-24">
    <!-- Header Page (Material Style) -->
    <header class="bg-white border-b border-outline-variant py-4 px-6 md:px-12 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform md:hidden">
                <span class="material-symbols-outlined text-primary">arrow_back</span>
            </a>
            <h1 class="font-headline-lg text-xl md:text-headline-lg text-primary font-bold">Thanh toán đơn hàng</h1>
        </div>
        <p class="hidden md:block text-sm text-on-surface-variant font-medium">Bảo mật &amp; Đáng tin cậy</p>
    </header>

    <div class="max-w-7xl mx-auto px-4 md:px-8 mt-8">
        @if(session('error'))
            <div class="bg-error-container text-on-error-container border border-error p-4 rounded-xl mb-6 flex items-center gap-3 shadow-sm">
                <span class="material-symbols-outlined">error</span>
                <span class="font-bold text-sm">{{ session('error') }}</span>
            </div>
        @endif

        @if(isset($isClosed) && $isClosed)
            <div class="bg-[#ffebee] text-[#c62828] border border-[#ffcdd2] p-4 rounded-xl mb-6 flex items-start gap-3 shadow-sm">
                <span class="material-symbols-outlined text-[#c62828] mt-0.5">schedule</span>
                <div>
                    <span class="font-bold text-sm block">Thông báo từ cửa hàng</span>
                    <span class="text-xs mt-1 block text-[#5d4037]">{{ $closedReason ?? 'Cửa hàng hiện đang tạm ngưng tiếp nhận đơn hàng mới. Quý khách vui lòng quay lại sau!' }}</span>
                </div>
            </div>
        @endif

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form"
              data-cod-url="{{ route('checkout.store') }}"
              data-momo-url="{{ route('momo.pay') }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $checkoutToken }}">
            <input type="hidden" name="distance_km" id="hidden_distance_km" value="2.5">
            <input type="hidden" name="weather_fee" id="hidden_weather_fee" value="0">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Shipping Address & Method & Payment (2 columns on desktop) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Shipping Address Section -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                            <span class="material-symbols-outlined text-primary material-filled">location_on</span>
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Địa chỉ giao hàng</h2>
                        </div>

                        @if($addresses->isEmpty())
                            <div id="empty-address-block" class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-xl flex flex-col items-center text-center">
                                <span class="material-symbols-outlined text-4xl mb-2 text-yellow-600">location_off</span>
                                <p class="font-bold">Bạn chưa có địa chỉ giao hàng!</p>
                                <p class="text-sm mt-1">Vui lòng thêm địa chỉ nhận hàng để hoàn thành đặt hàng.</p>
                                <button type="button" class="add-address-btn mt-4 bg-primary text-white px-6 py-2 rounded-full font-bold text-sm hover:opacity-95 transition-all">
                                    Thêm địa chỉ ngay
                                </button>
                            </div>
                        @else
                            @php 
                                $defaultAddress = $addresses->where('is_default', 1)->first() ?? $addresses->first();
                            @endphp
                            
                            <!-- Active Address Info Block -->
                            <div id="address-info-block" class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60 relative">
                                <div class="flex items-center gap-3 mb-2 flex-wrap">
                                    <span class="font-bold text-on-surface text-base" id="active-address-name">{{ $defaultAddress->fullname }}</span>
                                    <span class="text-outline-variant">|</span>
                                    <span class="text-on-surface-variant font-medium" id="active-address-phone">{{ $defaultAddress->phone }}</span>
                                    
                                    <span id="active-address-default-badge" class="border border-primary text-primary px-2 py-0.5 text-[10px] rounded-sm bg-primary/5 uppercase font-bold {{ $defaultAddress->is_default ? '' : 'hidden' }}">Mặc định</span>
                                    
                                    <button type="button" class="edit-address-btn text-primary hover:text-[#005301] text-xs font-bold transition-all ml-auto"
                                        data-address-id="{{ $defaultAddress->id }}"
                                        data-fullname="{{ $defaultAddress->fullname }}"
                                        data-phone="{{ $defaultAddress->phone }}"
                                        data-province="{{ $defaultAddress->province }}"
                                        data-district="{{ $defaultAddress->district }}"
                                        data-ward="{{ $defaultAddress->ward }}"
                                        data-specific-address="{{ $defaultAddress->specific_address }}"
                                        data-type="{{ $defaultAddress->type }}"
                                        data-is-default="{{ $defaultAddress->is_default }}"
                                        data-latitude="{{ $defaultAddress->latitude }}"
                                        data-longitude="{{ $defaultAddress->longitude }}"
                                        data-location-method="{{ $defaultAddress->location_method }}">
                                        Sửa địa chỉ này
                                    </button>
                                </div>
                                <p class="text-sm text-on-surface-variant" id="active-address-details">
                                    {{ $defaultAddress->specific_address }}, {{ $defaultAddress->ward }}{{ $defaultAddress->district !== $defaultAddress->ward ? ', ' . $defaultAddress->district : '' }}, {{ $defaultAddress->province }}
                                </p>
                                
                                <input type="hidden" name="address_id" id="selected_address_id" value="{{ $defaultAddress->id }}">
                            </div>

                            <div id="address-action-buttons" class="mt-3 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                                <button type="button" class="add-address-btn text-primary hover:text-[#005301] text-sm font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">add</span>
                                    Thêm địa chỉ mới
                                </button>
                                @if($addresses->count() > 1)
                                    <button type="button" id="change-address-btn" class="text-primary hover:text-[#005301] text-sm font-bold flex items-center gap-1 sm:ml-auto">
                                        <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                        Thay đổi địa chỉ
                                    </button>
                                @endif
                            </div>

                            <!-- Collapsible Address Select List -->
                            <div id="address-list-panel" class="hidden mt-4 space-y-3 p-4 border border-outline-variant rounded-xl bg-surface/50">
                                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Chọn địa chỉ nhận hàng:</p>
                                @foreach($addresses as $addr)
                                    <label class="address-card flex items-start gap-3 p-3 border {{ $addr->id == $defaultAddress->id ? 'border-primary bg-primary-container/5' : 'border-outline-variant' }} rounded-lg cursor-pointer hover:bg-surface-container-low transition-all">
                                        <input type="radio" name="address_selector" value="{{ $addr->id }}" 
                                            class="mt-1 text-primary focus:ring-primary"
                                            data-fullname="{{ $addr->fullname }}" 
                                            data-phone="{{ $addr->phone }}"
                                            data-address="{{ $addr->specific_address }}, {{ $addr->ward }}{{ $addr->district !== $addr->ward ? ', ' . $addr->district : '' }}, {{ $addr->province }}"
                                            {{ $addr->id == $defaultAddress->id ? 'checked' : '' }}>
                                        <div class="text-sm flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-on-surface">{{ $addr->fullname }}</span>
                                                <span class="text-xs text-on-surface-variant">({{ $addr->phone }})</span>
                                                @if($addr->type == 'home')
                                                    <span class="text-[10px] px-1.5 py-0.5 border border-outline-variant rounded text-on-surface-variant">Nhà</span>
                                                @else
                                                    <span class="text-[10px] px-1.5 py-0.5 border border-outline-variant rounded text-on-surface-variant">Cty</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-on-surface-variant mt-1">{{ $addr->specific_address }}, {{ $addr->ward }}{{ $addr->district !== $addr->ward ? ', ' . $addr->district : '' }}, {{ $addr->province }}</p>
                                        </div>
                                        <div class="ml-auto self-center flex items-center gap-3">
                                            <button type="button" class="edit-address-btn text-primary hover:text-[#005301] text-xs font-bold transition-all"
                                                data-address-id="{{ $addr->id }}"
                                                data-fullname="{{ $addr->fullname }}"
                                                data-phone="{{ $addr->phone }}"
                                                data-province="{{ $addr->province }}"
                                                data-district="{{ $addr->district }}"
                                                data-ward="{{ $addr->ward }}"
                                                data-specific-address="{{ $addr->specific_address }}"
                                                data-type="{{ $addr->type }}"
                                                data-is-default="{{ $addr->is_default }}"
                                                data-latitude="{{ $addr->latitude }}"
                                                data-longitude="{{ $addr->longitude }}"
                                                data-location-method="{{ $addr->location_method }}">
                                                Sửa
                                            </button>
                                            <button type="button" class="text-error hover:text-red-700 text-xs font-bold transition-all" onclick="event.preventDefault(); event.stopPropagation(); deleteAddressCheckout({{ $addr->id }})">
                                                Xóa
                                            </button>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                            <!-- New Address Form (Hidden by default) -->
                            <div id="addressModal" class="hidden mt-4 pt-4 border-t border-outline-variant/60">
                                <div class="border-b border-outline-variant pb-4 mb-4 flex items-center justify-between">
                                    <h2 id="addressModalTitle" class="font-headline-md text-lg text-on-surface font-bold">Thêm địa chỉ mới</h2>
                                    <button type="button" onclick="closeAddressModal()" class="text-on-surface-variant hover:bg-surface-container p-2 rounded-full transition-colors active:scale-95">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>
                                
                                <!-- Segmented control: 3 phương thức xác định vị trí độc lập (gps/map/manual) -->
                                {{-- Icon xếp TRÊN chữ trên mobile (flex-col), xếp NGANG lại như cũ từ sm trở lên
                                (sm:flex-row): 3 nút cùng hàng ngang với chữ 2-3 từ quá chật trên màn hình hẹp,
                                khiến chữ xuống dòng lệch lạc rất xấu. Xếp dọc trên mobile giải quyết dứt điểm mà
                                không phải rút ngắn nhãn hay đổi cỡ chữ. --}}
                                <div class="grid grid-cols-3 gap-1 p-1 bg-surface-container rounded-xl mb-4" role="tablist" aria-label="Phương thức xác định vị trí">
                                    <button type="button" data-method="gps" onclick="setLocationMethod('gps')" class="loc-method-btn min-h-[44px] rounded-lg text-[11px] sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 text-center leading-tight transition-all">
                                        <span class="material-symbols-outlined text-[18px]">my_location</span> Vị trí hiện tại
                                    </button>
                                    <button type="button" data-method="map" onclick="setLocationMethod('map')" class="loc-method-btn min-h-[44px] rounded-lg text-[11px] sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 text-center leading-tight transition-all">
                                        <span class="material-symbols-outlined text-[18px]">map</span> Chọn trên bản đồ
                                    </button>
                                    <button type="button" data-method="manual" onclick="setLocationMethod('manual')" class="loc-method-btn min-h-[44px] rounded-lg text-[11px] sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 text-center leading-tight transition-all">
                                        <span class="material-symbols-outlined text-[18px]">edit_location_alt</span> Nhập địa chỉ
                                    </button>
                                </div>

                                <!-- Trạng thái xác định vị trí -->
                                <div id="locStatus" class="mb-4 flex items-center gap-2 text-sm font-medium rounded-xl px-3 py-2.5 bg-surface-container-lowest text-on-surface-variant">
                                    <span id="locStatusIcon" class="material-symbols-outlined text-[18px]">location_searching</span>
                                    <span id="locStatusText">Chưa xác định vị trí</span>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="addressGrid">
                                    <!-- Left: Form Inputs -->
                                    <div class="space-y-4">
                                        <input type="hidden" id="addr_id">
                                        <input type="hidden" id="addr_location_method" value="map">
                                        <input type="hidden" id="addr_formatted">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Họ và tên</label>
                                                <input type="text" id="addr_fullname" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Nhập họ tên">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Số điện thoại</label>
                                                <input type="tel" id="addr_phone" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Nhập SĐT">
                                                <p id="addr_phone_error" class="text-xs text-error ml-1 hidden">Số điện thoại không đúng định dạng.</p>
                                            </div>
                                        </div>

                                        <!-- Nút GPS (chỉ hiện ở mode "Vị trí hiện tại") -->
                                        <div id="gpsBlock" class="hidden">
                                            <button type="button" onclick="getCurrentLocation()" class="w-full min-h-[44px] rounded-xl bg-primary/10 text-primary font-bold text-sm flex items-center justify-center gap-2 hover:bg-primary/20 transition-colors active:scale-[0.98]">
                                                <span class="material-symbols-outlined text-[20px]">my_location</span> Lấy vị trí hiện tại
                                            </button>
                                            <p class="text-xs text-on-surface-variant mt-2 ml-1">Cho phép trình duyệt truy cập vị trí. Sau khi có vị trí, bạn có thể kéo ghim trên bản đồ để chỉnh lại.</p>
                                        </div>

                                        <!-- Khu vực: 2 ô tìm chọn Tỉnh/Thành phố + Phường/Xã độc lập, hiển thị đủ chiều rộng -->
                                        <div class="grid grid-cols-1 gap-3.5" id="locSelectContainer">
                                            <div class="space-y-1">
                                                <label for="addr_province_search" class="text-xs font-bold text-on-surface-variant ml-1">Tỉnh/Thành phố <span class="text-error">*</span></label>
                                                <div class="relative" data-area-search-root="province">
                                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[19px] text-on-surface-variant pointer-events-none">search</span>
                                                    <input type="search" id="addr_province_search"
                                                        onfocus="openAreaSearch('province')" oninput="filterAreaOptions('province')"
                                                        onkeydown="handleAreaSearchKeydown(event, 'province')"
                                                        autocomplete="off" role="combobox" aria-autocomplete="list"
                                                        aria-controls="addr_province_options" aria-expanded="false" aria-invalid="false"
                                                        class="w-full min-h-[44px] bg-surface-container-lowest border border-outline-variant rounded-xl pl-10 pr-10 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all disabled:opacity-60 truncate"
                                                        placeholder="Tìm tỉnh/thành phố..." disabled>
                                                    <button type="button" onclick="toggleAreaSearch('province')" class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container" aria-label="Mở danh sách tỉnh/thành phố">
                                                        <span class="material-symbols-outlined text-[20px]">arrow_drop_down</span>
                                                    </button>
                                                    <div id="addr_province_dropdown" class="hidden absolute left-0 right-0 top-full z-[100] mt-1 bg-white border border-outline-variant rounded-xl shadow-xl overflow-hidden">
                                                        <div id="addr_province_options" role="listbox" class="max-h-64 overflow-y-auto p-1"></div>
                                                        <p id="addr_province_empty" class="hidden px-4 py-3 text-sm text-on-surface-variant">Không tìm thấy tỉnh/thành phố phù hợp.</p>
                                                    </div>
                                                </div>
                                                <select id="addr_province_select" class="hidden" tabindex="-1" aria-hidden="true">
                                                    <option value="">Đang tải tỉnh/thành phố...</option>
                                                </select>
                                                <p id="provinceHelpText" class="text-xs text-error ml-1 hidden"></p>
                                            </div>
                                            <div class="space-y-1">
                                                <label for="addr_ward_search" class="text-xs font-bold text-on-surface-variant ml-1">Phường/Xã <span class="text-error">*</span></label>
                                                <div class="relative" data-area-search-root="ward">
                                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[19px] text-on-surface-variant pointer-events-none">search</span>
                                                    <input type="search" id="addr_ward_search"
                                                        onfocus="openAreaSearch('ward')" oninput="filterAreaOptions('ward')"
                                                        onkeydown="handleAreaSearchKeydown(event, 'ward')"
                                                        autocomplete="off" role="combobox" aria-autocomplete="list"
                                                        aria-controls="addr_ward_options" aria-expanded="false" aria-invalid="false"
                                                        class="w-full min-h-[44px] bg-surface-container-lowest border border-outline-variant rounded-xl pl-10 pr-10 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all disabled:opacity-60 truncate"
                                                        placeholder="Chọn tỉnh/thành phố trước" disabled>
                                                    <button type="button" onclick="toggleAreaSearch('ward')" class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container" aria-label="Mở danh sách phường/xã">
                                                        <span class="material-symbols-outlined text-[20px]">arrow_drop_down</span>
                                                    </button>
                                                    <div id="addr_ward_dropdown" class="hidden absolute left-0 right-0 top-full z-[100] mt-1 bg-white border border-outline-variant rounded-xl shadow-xl overflow-hidden">
                                                        <div id="addr_ward_options" role="listbox" class="max-h-64 overflow-y-auto p-1"></div>
                                                        <p id="addr_ward_empty" class="hidden px-4 py-3 text-sm text-on-surface-variant">Không tìm thấy phường/xã phù hợp.</p>
                                                    </div>
                                                </div>
                                                <select id="addr_ward_select" class="hidden" tabindex="-1" aria-hidden="true" disabled>
                                                    <option value="">Vui lòng chọn tỉnh/thành phố trước</option>
                                                </select>
                                                <p id="wardHelpText" class="text-xs text-error ml-1 hidden"></p>
                                            </div>

                                            <input type="hidden" id="addr_province_code">
                                            <input type="hidden" id="addr_ward_code">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Địa chỉ cụ thể <span class="text-error">*</span></label>
                                            <textarea id="addr_specific" rows="2" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all resize-none" placeholder="Số nhà, tên đường..."></textarea>
                                        </div>

                                        <!-- Types & Default -->
                                        <div class="space-y-3 pt-2">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Loại địa chỉ</label>
                                            <div class="flex gap-3">
                                                <button type="button" id="btnTypeHome" onclick="setAddrType('home')" class="flex-1 min-h-[44px] rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">home</span> Nhà riêng
                                                </button>
                                                <button type="button" id="btnTypeOffice" onclick="setAddrType('office')" class="flex-1 min-h-[44px] rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">domain</span> Công ty
                                                </button>
                                            </div>
                                            <input type="hidden" id="addr_type" value="home">
                                        </div>

                                        <label class="flex items-center gap-3 cursor-pointer p-3 border border-outline-variant rounded-xl hover:bg-surface-container-lowest transition-colors mt-2">
                                            <input type="checkbox" id="addr_default" class="w-4 h-4 text-primary focus:ring-primary rounded border-outline-variant">
                                            <span class="text-sm font-medium text-on-surface">Đặt làm địa chỉ mặc định</span>
                                        </label>
                                    </div>

                                    <!-- Right: Map (mode gps/map; ẩn ở mode manual) -->
                                    <div id="mapColumn" class="flex flex-col h-full space-y-3">
                                        <label class="text-xs font-bold text-on-surface-variant ml-1">Vị trí trên bản đồ</label>
                                        <p id="mapHint" class="text-xs text-on-surface-variant ml-1 hidden">Chạm vào bản đồ hoặc kéo ghim đến đúng vị trí giao hàng, rồi bấm "Xác nhận vị trí này".</p>
                                        <div id="addressMap" class="w-full flex-1 min-h-[250px] max-h-[60vh] rounded-xl border border-outline-variant z-10"></div>
                                        <input type="hidden" id="addr_lat">
                                        <input type="hidden" id="addr_lng">
                                        <button type="button" id="btnConfirmMapLocation" onclick="confirmMapLocation()" class="hidden w-full min-h-[44px] rounded-xl bg-primary text-white font-bold text-sm hover:opacity-90 transition-opacity active:scale-[0.98]">
                                            Xác nhận vị trí này
                                        </button>
                                    </div>
                                </div>

                                <!-- Thông báo lỗi chung của form (không dùng alert() — mục 9) -->
                                <p id="addressFormError" class="text-sm text-error font-medium mt-3 hidden"></p>

                                <!-- Actions (full-width, hiện ở mọi mode) -->
                                <div class="grid grid-cols-2 gap-3 pt-4 mt-4 border-t border-outline-variant/60">
                                    <button type="button" onclick="closeAddressModal()" class="min-h-[44px] rounded-xl border border-outline-variant text-on-surface font-bold hover:bg-surface-container-low transition-colors">
                                        Hủy
                                    </button>
                                    <button type="button" id="btnSaveAddress" onclick="saveAddress()" class="min-h-[44px] rounded-xl bg-primary text-white font-bold hover:opacity-90 transition-opacity shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                        Hoàn thành
                                    </button>
                                </div>
                            </div>

                    </section>

                    <!-- 3. Payment Method Section -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                            <span class="material-symbols-outlined text-primary material-filled">payments</span>
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Phương thức thanh toán</h2>
                        </div>

                        @php
                            $codEnabled = (bool) \App\Models\Setting::getValue('cod_enabled', true);
                            $momoEnabled = (bool) \App\Models\Setting::getValue('momo_enabled', false);
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Cash On Delivery (COD) -->
                            @if($codEnabled)
                            <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                <input type="radio" name="payment_method" value="cod" checked class="text-primary focus:ring-primary">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-primary text-3xl">handshake</span>
                                    <div>
                                        <span class="block font-bold text-on-surface">Tiền mặt (COD)</span>
                                        <span class="text-xs text-on-surface-variant">Thanh toán khi nhận hàng</span>
                                    </div>
                                </div>
                            </label>
                            @endif

                            <!-- Chuyển khoản (MoMo - hiện đầy đủ các phương thức) -->
                            @if($momoEnabled)
                            <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                <input type="radio" name="payment_method" value="momo" {{ !$codEnabled ? 'checked' : '' }} class="text-primary focus:ring-primary">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-primary text-3xl material-filled">account_balance</span>
                                    <div>
                                        <span class="block font-bold text-on-surface">Chuyển khoản</span>
                                        <span class="text-xs text-on-surface-variant">Ví MoMo, ATM, Visa/Master...</span>
                                    </div>
                                </div>
                            </label>
                            @endif

                            @if(!$codEnabled && !$momoEnabled)
                            <div class="col-span-full p-4 bg-red-50 text-red-800 border border-red-200 rounded-xl text-sm font-semibold">
                                Cửa hàng hiện đang tạm ngắt toàn bộ cổng thanh toán. Không thể hoàn tất đặt hàng lúc này.
                            </div>
                            @endif
                        </div>
                    </section>
                </div>

                <!-- Right: Order Summary (1 column on desktop) -->
                <div class="space-y-6">
                    <!-- Order Items Summary Card -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="border-b border-outline-variant pb-4 mb-4">
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Tóm tắt đơn hàng</h2>
                        </div>

                        {{-- Hidden inputs truyền danh sách cart_item_id đã chọn vào form --}}
                        @foreach($items as $item)
                            <input type="hidden" name="selected_item_ids[]" value="{{ $item->id }}">
                        @endforeach

                        <!-- Product List -->
                        <div class="divide-y divide-outline-variant/50 max-h-96 overflow-y-auto pr-1">
                            @foreach($items as $item)
                                <div class="flex gap-4 py-4 first:pt-0 last:pb-0">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-surface-container flex-shrink-0 border border-outline-variant/60">
                                        <img src="{{ str_starts_with($item->image, 'storage/') ? asset($item->image) : asset('images/' . $item->image) }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-on-surface text-sm truncate" title="{{ $item->name }}">{{ $item->name }}</h4>
                                        <p class="text-xs text-on-surface-variant mt-0.5 font-medium">
                                            x{{ $item->quantity }} • Size {{ $item->size_name }}
                                            @if($item->sugar_level !== null) • Đường: {{ $item->sugar_level }}% @endif
                                            @if($item->ice_level !== null) • Đá: {{ $item->ice_level == 'normal' ? 'Thường' : ($item->ice_level == 'no' ? 'Không đá' : $item->ice_level) }} @endif
                                        </p>
                                        @if($item->toppings && $item->toppings->isNotEmpty())
                                            <p class="text-xs text-primary font-medium mt-1">
                                                + Topping: {{ $item->toppings->pluck('name')->implode(', ') }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-right flex-shrink-0 font-bold text-on-surface text-sm">
                                        {{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ
                                    </div>
                                </div>
                            @endforeach

                            {{-- Quà tặng Mua X tặng Y — hiển thị như một dòng riêng, giá 0đ. Khách KHÔNG
                            sửa được số lượng ở đây; hệ thống tự tính lại khi đặt hàng. --}}
                            @foreach($gifts ?? [] as $gift)
                                <div class="flex gap-4 py-4 first:pt-0 last:pb-0 bg-emerald-50/40">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-surface-container flex-shrink-0 border border-emerald-200">
                                        <img src="{{ $gift['gift_product']->image_url }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-on-surface text-sm truncate" title="{{ $gift['gift_product']->name }}">{{ $gift['gift_product']->name }}</h4>
                                        <p class="text-xs text-on-surface-variant mt-0.5 font-medium">x{{ $gift['granted_quantity'] }}</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold">🎁 Quà tặng</span>
                                        @if($gift['stock_limited'])
                                            <p class="text-[11px] text-amber-700 mt-1">Kho chỉ còn đủ tặng {{ $gift['granted_quantity'] }} phần.</p>
                                        @endif
                                    </div>
                                    <div class="text-right flex-shrink-0 font-bold text-emerald-700 text-sm">Miễn phí</div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <!-- Promotion Code Card -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="border-b border-outline-variant pb-4 mb-4">
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Mã giảm giá</h2>
                        </div>
                        
                        <div class="flex gap-2">
                            <input type="text" id="coupon_code_input" placeholder="Nhập mã HAPPY..." 
                                class="flex-1 bg-surface-container-low border-none rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary">
                            <button type="button" id="apply_coupon_btn" class="bg-primary text-white font-bold text-sm px-4 rounded-lg hover:opacity-90 transition active:scale-95">
                                Áp dụng
                            </button>
                        </div>
                        <div id="coupon_message" class="text-xs font-medium mt-1"></div>
                        <input type="hidden" name="coupon_code" id="hidden_coupon_code" value="">
                    </section>

                    @php
                        $loyaltyEnabled = (bool) \App\Models\Setting::getValue('loyalty_enabled', true);
                        $points = Auth::user()->points ?? 0;
                        $pointValue = (float) \App\Models\Setting::getValue('loyalty_point_value', 1);
                        $maxRedeemPercent = (float) \App\Models\Setting::getValue('loyalty_max_redeem_percent', 100);
                        $minPointsToRedeem = (int) \App\Models\Setting::getValue('loyalty_min_points_to_redeem', 10);
                    @endphp
                    @if($loyaltyEnabled && $points >= $minPointsToRedeem)
                    <!-- Points Redemption Card -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="border-b border-outline-variant pb-4 mb-4 flex items-center justify-between">
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Dùng điểm tích lũy</h2>
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold">Điểm</span>
                        </div>
                        
                        <div class="space-y-3">
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Bạn đang có <span class="font-bold text-primary">{{ $points }}</span> điểm (1 điểm = {{ number_format($pointValue, 0, ',', '.') }}đ). 
                                Bạn có thể đổi tối đa <span class="font-bold text-primary" id="max-redeemable-points">0</span> điểm cho đơn hàng này.
                            </p>
                            <div class="flex gap-2">
                                <input type="number" name="points_to_redeem" id="points_to_redeem_input" min="0" max="{{ $points }}" placeholder="Nhập số điểm muốn đổi..." 
                                    class="flex-1 bg-surface-container-low border-none rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary"
                                    data-points-balance="{{ $points }}"
                                    data-point-value="{{ $pointValue }}"
                                    data-max-redeem-percent="{{ $maxRedeemPercent }}"
                                    data-min-points-to-redeem="{{ $minPointsToRedeem }}">
                                <button type="button" id="apply_points_btn" class="bg-primary text-white font-bold text-sm px-4 rounded-lg hover:opacity-90 transition active:scale-95">
                                    Áp dụng
                                </button>
                            </div>
                            <div id="points_message" class="text-xs font-medium mt-1"></div>
                        </div>
                    </section>
                    @endif

                    <!-- Order Price Breakdown Card -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="border-b border-outline-variant pb-4 mb-4">
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Chi tiết thanh toán</h2>
                        </div>

                        <div class="space-y-3" id="price-summary" data-subtotal="{{ $subtotal }}" data-freeship-threshold="{{ $freeShipThreshold }}">
                            <div class="flex justify-between text-sm text-on-surface-variant font-medium">
                                <span>Tạm tính (Sản phẩm)</span>
                                <span id="summary-subtotal-text">{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between text-sm text-on-surface-variant font-medium" id="summary-shipping-distance-row">
                                <span>Phí giao hàng (<span id="summary-distance-km-val">0.0</span> km × {{ number_format((float) \App\Models\Setting::getValue('shipping_fee_per_km', 5000), 0, ',', '.') }}đ)</span>
                                <span id="summary-shipping-distance-text">0đ</span>
                            </div>
                            <div class="flex justify-between text-sm text-primary font-bold hidden" id="summary-free-ship-row">
                                <span id="summary-free-ship-text">
                                    @if($freeShipThreshold == 0)
                                        🎉 Miễn phí giao hàng (Ưu đãi Kim Cương)
                                    @else
                                        🎉 Miễn phí giao hàng (Đơn ≥ {{ number_format($freeShipThreshold, 0, ',', '.') }}đ)
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between text-sm text-on-surface-variant font-medium hidden" id="summary-weather-fee-row">
                                <span>Phụ thu thời tiết (<span id="summary-weather-condition-val">Bình thường</span>)</span>
                                <span id="summary-weather-fee-text" class="text-error font-bold">+0đ</span>
                            </div>

                            <div class="flex justify-between text-sm text-on-surface-variant font-medium hidden" id="summary-discount-row">
                                <span>Giảm giá</span>
                                <span class="text-error font-bold" id="summary-discount-text">-0đ</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-on-surface border-t border-outline-variant pt-3 mt-1">
                                <span>Tổng cộng</span>
                                <span id="summary-total-text" class="text-primary text-lg font-extrabold">{{ number_format($subtotal + 15000, 0, ',', '.') }}đ</span>
                            </div>
                        </div>

                        <!-- Customer Note -->
                        <div class="mt-4 pt-4 border-t border-outline-variant/60">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Ghi chú cho đơn hàng</label>
                            <textarea name="note" rows="3" placeholder="Ghi chú về thời gian giao hàng, địa chỉ chi tiết hoặc hướng dẫn giao hàng..." 
                                class="w-full bg-surface-container-low border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                        </div>

                        <!-- Order Action button -->
                        @if(isset($isClosed) && $isClosed)
                            <button type="button" disabled class="w-full bg-gray-300 text-gray-500 font-bold text-center py-3.5 rounded-xl cursor-not-allowed mt-6">
                                @if(!\App\Models\Setting::getValue('orders_enabled', true))
                                    Cửa hàng tạm ngưng nhận đơn
                                @else
                                    Cửa hàng đóng cửa ({{ \App\Models\Setting::getValue('store_open_time', '08:00') }} - {{ \App\Models\Setting::getValue('store_close_time', '22:00') }})
                                @endif
                            </button>
                        @elseif(!$addresses->isEmpty())
                            <button type="submit" id="order-submit-btn" data-closed="0" class="w-full bg-primary-container text-on-primary hover:bg-[#008f00] font-bold text-center py-3.5 rounded-xl shadow-sm hover:shadow-md transition-all active:scale-98 mt-6">
                                <span id="submit-btn-text">Đặt hàng (COD)</span>
                            </button>
                        @else
                            <button type="button" disabled class="w-full bg-gray-300 text-gray-500 font-bold text-center py-3.5 rounded-xl cursor-not-allowed mt-6">
                                Vui lòng thêm địa chỉ nhận hàng
                            </button>
                        @endif
                    </section>
                </div>
            </div>
        </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    window.checkoutConfig = {
        shippingBaseFee: {{ (float) \App\Models\Setting::getValue('shipping_base_fee', 15000) }},
        shippingFeePerKm: {{ (float) \App\Models\Setting::getValue('shipping_fee_per_km', 5000) }},
        shippingMaxDistanceKm: {{ (float) \App\Models\Setting::getValue('shipping_max_distance_km', 15) }},
        freeShippingMinimum: {{ (float) \App\Models\Setting::getValue('free_shipping_minimum', 150000) }},
        geoapifyKey: @json(config('services.geoapify.key')),
        shopLat: {{ (float) \App\Models\Setting::getValue('store_latitude', 10.73809) }},
        shopLng: {{ (float) \App\Models\Setting::getValue('store_longitude', 106.67812) }}
    };
</script>
<script src="{{ asset('js/frontend/orders/checkout.js') }}?v={{ filemtime(public_path('js/frontend/orders/checkout.js')) }}"></script>
@endsection
