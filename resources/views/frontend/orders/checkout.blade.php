@extends('frontend.layouts.app')

@section('body_class', 'profile-body')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <div
        class="min-h-screen bg-background text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container pb-24">
        <!-- Header -->
        <header
            class="bg-white border-b border-outline-variant py-4 px-6 md:px-12 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}"
                    class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform md:hidden">
                    <span class="material-symbols-outlined text-primary">arrow_back</span>
                </a>
                <h1 class="font-headline-lg text-xl md:text-headline-lg text-primary font-bold">Thanh toán đơn hàng</h1>
            </div>
            <p class="hidden md:block text-sm text-on-surface-variant font-medium">Bảo mật &amp; Đáng tin cậy</p>
        </header>

        <div class="max-w-7xl mx-auto px-4 md:px-8 mt-8">
            
            @if (session('error') || $errors->any())
                <div
                    class="bg-error-container text-on-error-container border border-error p-4 rounded-xl mb-6 flex items-center gap-3 shadow-sm">
                    <span class="material-symbols-outlined">error</span>
                    <span class="font-bold text-sm">{{ session('error') ?: $errors->first() }}</span>
                </div>
            @endif

            @if (isset($isClosed) && $isClosed)
                <div
                    class="bg-[#ffebee] text-[#c62828] border border-[#ffcdd2] p-4 rounded-xl mb-6 flex items-start gap-3 shadow-sm">
                    <span class="material-symbols-outlined text-[#c62828] mt-0.5">schedule</span>
                    <div>
                        <span class="font-bold text-sm block">Thông báo từ cửa hàng</span>
                        <span
                            class="text-xs mt-1 block text-[#5d4037]">{{ $closedReason ?? 'Cửa hàng hiện đang tạm ngưng tiếp nhận đơn hàng mới. Quý khách vui lòng quay lại sau!' }}</span>
                    </div>
                </div>
            @endif

            <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form"
                data-cod-url="{{ route('checkout.store') }}" data-vnpay-url="{{ route('vnpay.pay') }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $checkoutToken }}">
                <input type="hidden" name="distance_km" id="hidden_distance_km" value="2.5">
                <input type="hidden" name="weather_fee" id="hidden_weather_fee" value="0">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Shipping Address & Method & Payment -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Shipping Address Section -->
                        <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                            <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                                <span class="material-symbols-outlined text-primary material-filled">location_on</span>
                                <h2 class="font-headline-md text-lg text-on-surface font-bold">Địa chỉ giao hàng</h2>
                            </div>

                            @if ($addresses->isEmpty())
                                <div id="empty-address-block"
                                    class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-xl flex flex-col items-center text-center">
                                    <span
                                        class="material-symbols-outlined text-4xl mb-2 text-yellow-600">location_off</span>
                                    <p class="font-bold">Bạn chưa có địa chỉ giao hàng!</p>
                                    <p class="text-sm mt-1">Vui lòng thêm địa chỉ nhận hàng để hoàn thành đặt hàng.</p>
                                    <button type="button"
                                        class="add-address-btn mt-4 bg-primary text-white px-6 py-2 rounded-full font-bold text-sm hover:opacity-95 transition-all">
                                        Thêm địa chỉ ngay
                                    </button>
                                </div>
                            @else
                                @php
                                    $defaultAddress =
                                        $addresses->where('is_default', 1)->first() ?? $addresses->first();
                                @endphp

                                <!-- Active Address Info Block -->
                                <div id="address-info-block"
                                    class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60 relative">
                                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                                        <span class="font-bold text-on-surface text-base"
                                            id="active-address-name">{{ $defaultAddress->fullname }}</span>
                                        <span class="text-outline-variant">|</span>
                                        <span class="text-on-surface-variant font-medium"
                                            id="active-address-phone">{{ $defaultAddress->phone }}</span>

                                        <span id="active-address-default-badge"
                                            class="border border-primary text-primary px-2 py-0.5 text-[10px] rounded-sm bg-primary/5 uppercase font-bold {{ $defaultAddress->is_default ? '' : 'hidden' }}">Mặc
                                            định</span>

                                        <button type="button"
                                            class="edit-address-btn text-primary hover:text-[#005301] text-xs font-bold transition-all ml-auto"
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
                                        {{ $defaultAddress->specific_address }},
                                        {{ $defaultAddress->ward }}{{ $defaultAddress->district !== $defaultAddress->ward ? ', ' . $defaultAddress->district : '' }},
                                        {{ $defaultAddress->province }}
                                    </p>

                                    <input type="hidden" name="address_id" id="selected_address_id"
                                        value="{{ $defaultAddress->id }}">
                                </div>

                                <div id="address-action-buttons"
                                    class="mt-3 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                                    <button type="button"
                                        class="add-address-btn text-primary hover:text-[#005301] text-sm font-bold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                        Thêm địa chỉ mới
                                    </button>
                                    @if ($addresses->count() > 1)
                                        <button type="button" id="change-address-btn"
                                            class="text-primary hover:text-[#005301] text-sm font-bold flex items-center gap-1 sm:ml-auto">
                                            <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                            Thay đổi địa chỉ
                                        </button>
                                    @endif
                                </div>

                                <!-- Collapsible Address Select List -->
                                <div id="address-list-panel"
                                    class="hidden mt-4 space-y-3 p-4 border border-outline-variant rounded-xl bg-surface/50">
                                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Chọn
                                        địa chỉ nhận hàng:</p>
                                    @foreach ($addresses as $addr)
                                        <label
                                            class="address-card flex items-start gap-3 p-3 border {{ $addr->id == $defaultAddress->id ? 'border-primary bg-primary-container/5' : 'border-outline-variant' }} rounded-lg cursor-pointer hover:bg-surface-container-low transition-all">
                                            <input type="radio" name="address_selector" value="{{ $addr->id }}"
                                                class="mt-1 text-primary focus:ring-primary"
                                                data-fullname="{{ $addr->fullname }}" data-phone="{{ $addr->phone }}"
                                                data-address="{{ $addr->specific_address }}, {{ $addr->ward }}{{ $addr->district !== $addr->ward ? ', ' . $addr->district : '' }}, {{ $addr->province }}"
                                                {{ $addr->id == $defaultAddress->id ? 'checked' : '' }}>
                                            <div class="text-sm flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-on-surface">{{ $addr->fullname }}</span>
                                                    <span
                                                        class="text-xs text-on-surface-variant">({{ $addr->phone }})</span>
                                                    @if ($addr->type == 'home')
                                                        <span
                                                            class="text-[10px] px-1.5 py-0.5 border border-outline-variant rounded text-on-surface-variant">Nhà</span>
                                                    @else
                                                        <span
                                                            class="text-[10px] px-1.5 py-0.5 border border-outline-variant rounded text-on-surface-variant">Cty</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-on-surface-variant mt-1">
                                                    {{ $addr->specific_address }},
                                                    {{ $addr->ward }}{{ $addr->district !== $addr->ward ? ', ' . $addr->district : '' }},
                                                    {{ $addr->province }}</p>
                                            </div>
                                            <div class="ml-auto self-center flex items-center gap-3">
                                                <button type="button"
                                                    class="edit-address-btn text-primary hover:text-[#005301] text-xs font-bold transition-all"
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
                                                <button type="button"
                                                    class="text-error hover:text-red-700 text-xs font-bold transition-all"
                                                    onclick="event.preventDefault(); event.stopPropagation(); deleteAddressCheckout({{ $addr->id }})">
                                                    Xóa
                                                </button>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <!-- New Address Form -->
                            <div id="addressModal" class="hidden mt-4 pt-4 border-t border-outline-variant/60">
                                <div class="border-b border-outline-variant pb-4 mb-4 flex items-center justify-between">
                                    <h2 id="addressModalTitle" class="font-headline-md text-lg text-on-surface font-bold">
                                        Thêm địa chỉ mới</h2>
                                    <button type="button" onclick="closeAddressModal()"
                                        class="text-on-surface-variant hover:bg-surface-container p-2 rounded-full transition-colors active:scale-95">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>

                                <!-- Segmented control: 3 phương thức xác định vị trí độc lập -->
                                
                                <div class="grid grid-cols-3 gap-1 p-1 bg-surface-container rounded-xl mb-4"
                                    role="tablist" aria-label="Phương thức xác định vị trí">
                                    <button type="button" data-method="gps" onclick="setLocationMethod('gps')"
                                        class="loc-method-btn min-h-[44px] rounded-lg text-[11px] sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 text-center leading-tight transition-all">
                                        <span class="material-symbols-outlined text-[18px]">my_location</span> Vị trí hiện
                                        tại
                                    </button>
                                    <button type="button" data-method="map" onclick="setLocationMethod('map')"
                                        class="loc-method-btn min-h-[44px] rounded-lg text-[11px] sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 text-center leading-tight transition-all">
                                        <span class="material-symbols-outlined text-[18px]">map</span> Chọn trên bản đồ
                                    </button>
                                    <button type="button" data-method="manual" onclick="setLocationMethod('manual')"
                                        class="loc-method-btn min-h-[44px] rounded-lg text-[11px] sm:text-sm font-bold flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 text-center leading-tight transition-all">
                                        <span class="material-symbols-outlined text-[18px]">edit_location_alt</span> Nhập
                                        địa chỉ
                                    </button>
                                </div>

                                <!-- Trạng thái xác định vị trí -->
                                <div id="locStatus"
                                    class="mb-4 flex items-center gap-2 text-sm font-medium rounded-xl px-3 py-2.5 bg-surface-container-lowest text-on-surface-variant">
                                    <span id="locStatusIcon"
                                        class="material-symbols-outlined text-[18px]">location_searching</span>
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
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Họ và
                                                    tên</label>
                                                <input type="text" id="addr_fullname"
                                                    class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all"
                                                    placeholder="Nhập họ tên">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Số điện
                                                    thoại</label>
                                                <input type="tel" id="addr_phone"
                                                    class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all"
                                                    placeholder="Nhập SĐT">
                                                <p id="addr_phone_error" class="text-xs text-error ml-1 hidden">Số điện
                                                    thoại không đúng định dạng.</p>
                                            </div>
                                        </div>

                                        <!-- Nút gps -->
                                        <div id="gpsBlock" class="hidden">
                                            <button type="button" onclick="getCurrentLocation()"
                                                class="w-full min-h-[44px] rounded-xl bg-primary/10 text-primary font-bold text-sm flex items-center justify-center gap-2 hover:bg-primary/20 transition-colors active:scale-[0.98]">
                                                <span class="material-symbols-outlined text-[20px]">my_location</span> Lấy
                                                vị trí hiện tại
                                            </button>
                                            <p class="text-xs text-on-surface-variant mt-2 ml-1">Cho phép trình duyệt truy
                                                cập vị trí. Sau khi có vị trí, bạn có thể kéo ghim trên bản đồ để chỉnh lại.
                                            </p>
                                        </div>

                                        <!-- Khu vực: 2 ô tìm chọn Tỉnh/Thành phố + Phường/Xã -->
                                        <div class="grid grid-cols-1 gap-3.5" id="locSelectContainer">
                                            <div class="space-y-1">
                                                <label for="addr_province_search"
                                                    class="text-xs font-bold text-on-surface-variant ml-1">Tỉnh/Thành phố
                                                    <span class="text-error">*</span></label>
                                                <div class="relative" data-area-search-root="province">
                                                    <span
                                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[19px] text-on-surface-variant pointer-events-none">search</span>
                                                    <input type="search" id="addr_province_search"
                                                        onfocus="openAreaSearch('province')"
                                                        oninput="filterAreaOptions('province')"
                                                        onkeydown="handleAreaSearchKeydown(event, 'province')"
                                                        autocomplete="off" role="combobox" aria-autocomplete="list"
                                                        aria-controls="addr_province_options" aria-expanded="false"
                                                        aria-invalid="false"
                                                        class="w-full min-h-[44px] bg-surface-container-lowest border border-outline-variant rounded-xl pl-10 pr-10 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all disabled:opacity-60 truncate"
                                                        placeholder="Tìm tỉnh/thành phố..." disabled>
                                                    <button type="button" onclick="toggleAreaSearch('province')"
                                                        class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container"
                                                        aria-label="Mở danh sách tỉnh/thành phố">
                                                        <span
                                                            class="material-symbols-outlined text-[20px]">arrow_drop_down</span>
                                                    </button>
                                                    <div id="addr_province_dropdown"
                                                        class="hidden absolute left-0 right-0 top-full z-[100] mt-1 bg-white border border-outline-variant rounded-xl shadow-xl overflow-hidden">
                                                        <div id="addr_province_options" role="listbox"
                                                            class="max-h-64 overflow-y-auto p-1"></div>
                                                        <p id="addr_province_empty"
                                                            class="hidden px-4 py-3 text-sm text-on-surface-variant">Không
                                                            tìm thấy tỉnh/thành phố phù hợp.</p>
                                                    </div>
                                                </div>
                                                <select id="addr_province_select" class="hidden" tabindex="-1"
                                                    aria-hidden="true">
                                                    <option value="">Đang tải tỉnh/thành phố...</option>
                                                </select>
                                                <p id="provinceHelpText" class="text-xs text-error ml-1 hidden"></p>
                                            </div>
                                            <div class="space-y-1">
                                                <label for="addr_ward_search"
                                                    class="text-xs font-bold text-on-surface-variant ml-1">Phường/Xã <span
                                                        class="text-error">*</span></label>
                                                <div class="relative" data-area-search-root="ward">
                                                    <span
                                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[19px] text-on-surface-variant pointer-events-none">search</span>
                                                    <input type="search" id="addr_ward_search"
                                                        onfocus="openAreaSearch('ward')"
                                                        oninput="filterAreaOptions('ward')"
                                                        onkeydown="handleAreaSearchKeydown(event, 'ward')"
                                                        autocomplete="off" role="combobox" aria-autocomplete="list"
                                                        aria-controls="addr_ward_options" aria-expanded="false"
                                                        aria-invalid="false"
                                                        class="w-full min-h-[44px] bg-surface-container-lowest border border-outline-variant rounded-xl pl-10 pr-10 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all disabled:opacity-60 truncate"
                                                        placeholder="Chọn tỉnh/thành phố trước" disabled>
                                                    <button type="button" onclick="toggleAreaSearch('ward')"
                                                        class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container"
                                                        aria-label="Mở danh sách phường/xã">
                                                        <span
                                                            class="material-symbols-outlined text-[20px]">arrow_drop_down</span>
                                                    </button>
                                                    <div id="addr_ward_dropdown"
                                                        class="hidden absolute left-0 right-0 top-full z-[100] mt-1 bg-white border border-outline-variant rounded-xl shadow-xl overflow-hidden">
                                                        <div id="addr_ward_options" role="listbox"
                                                            class="max-h-64 overflow-y-auto p-1"></div>
                                                        <p id="addr_ward_empty"
                                                            class="hidden px-4 py-3 text-sm text-on-surface-variant">Không
                                                            tìm thấy phường/xã phù hợp.</p>
                                                    </div>
                                                </div>
                                                <select id="addr_ward_select" class="hidden" tabindex="-1"
                                                    aria-hidden="true" disabled>
                                                    <option value="">Vui lòng chọn tỉnh/thành phố trước</option>
                                                </select>
                                                <p id="wardHelpText" class="text-xs text-error ml-1 hidden"></p>
                                            </div>

                                            <input type="hidden" id="addr_province_code">
                                            <input type="hidden" id="addr_ward_code">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Địa chỉ cụ thể
                                                <span class="text-error">*</span></label>
                                            <textarea id="addr_specific" rows="2"
                                                class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all resize-none"
                                                placeholder="Số nhà, tên đường..."></textarea>
                                        </div>

                                        <!-- Types & Default -->
                                        <div class="space-y-3 pt-2">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Loại địa
                                                chỉ</label>
                                            <div class="flex gap-3">
                                                <button type="button" id="btnTypeHome" onclick="setAddrType('home')"
                                                    class="flex-1 min-h-[44px] rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">home</span> Nhà
                                                    riêng
                                                </button>
                                                <button type="button" id="btnTypeOffice" onclick="setAddrType('office')"
                                                    class="flex-1 min-h-[44px] rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">domain</span> Công
                                                    ty
                                                </button>
                                            </div>
                                            <input type="hidden" id="addr_type" value="home">
                                        </div>

                                        <label
                                            class="flex items-center gap-3 cursor-pointer p-3 border border-outline-variant rounded-xl hover:bg-surface-container-lowest transition-colors mt-2">
                                            <input type="checkbox" id="addr_default"
                                                class="w-4 h-4 text-primary focus:ring-primary rounded border-outline-variant">
                                            <span class="text-sm font-medium text-on-surface">Đặt làm địa chỉ mặc
                                                định</span>
                                        </label>
                                    </div>

                                    
                                    <div id="mapColumn" class="flex flex-col h-full space-y-3">
                                        <label class="text-xs font-bold text-on-surface-variant ml-1">Vị trí trên bản
                                            đồ</label>
                                        <p id="mapHint" class="text-xs text-on-surface-variant ml-1 hidden">Chạm vào bản
                                            đồ hoặc kéo ghim đến đúng vị trí giao hàng, rồi bấm "Xác nhận vị trí này".</p>
                                        <p id="manualMapHint" class="text-xs text-on-surface-variant ml-1 hidden">Hệ thống
                                            tự dò vị trí từ địa chỉ bạn nhập. Vui lòng kiểm tra ghim trên bản đồ — nếu chưa
                                            đúng, chạm vào bản đồ hoặc kéo ghim để chỉnh lại.</p>
                                        <div id="addressMap"
                                            class="w-full flex-1 min-h-[250px] max-h-[60vh] rounded-xl border border-outline-variant z-10">
                                        </div>
                                        <input type="hidden" id="addr_lat">
                                        <input type="hidden" id="addr_lng">
                                        <button type="button" id="btnConfirmMapLocation" onclick="confirmMapLocation()"
                                            class="hidden w-full min-h-[44px] rounded-xl bg-primary text-white font-bold text-sm hover:opacity-90 transition-opacity active:scale-[0.98]">
                                            Xác nhận vị trí này
                                        </button>
                                    </div>
                                </div>

                                <!-- Thông báo lỗi chung của form — mục 9) -->
                                <p id="addressFormError" class="text-sm text-error font-medium mt-3 hidden"></p>

                                <!-- Actions -->
                                <div class="grid grid-cols-2 gap-3 pt-4 mt-4 border-t border-outline-variant/60">
                                    <button type="button" onclick="closeAddressModal()"
                                        class="min-h-[44px] rounded-xl border border-outline-variant text-on-surface font-bold hover:bg-surface-container-low transition-colors">
                                        Hủy
                                    </button>
                                    <button type="button" id="btnSaveAddress" onclick="saveAddress()"
                                        class="min-h-[44px] rounded-xl bg-primary text-white font-bold hover:opacity-90 transition-opacity shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                        Hoàn thành
                                    </button>
                                </div>
                            </div>

                        </section>

                        <!-- Payment Method Section -->
                        <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                            <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                                <span class="material-symbols-outlined text-primary material-filled">payments</span>
                                <h2 class="font-headline-md text-lg text-on-surface font-bold">Phương thức thanh toán</h2>
                            </div>

                            @php
                                $codEnabled = (bool) \App\Models\Setting::getValue('cod_enabled', true);
                                $vnpayEnabled = (bool) \App\Models\Setting::getValue('vnpay_enabled', false);
                                // COD tắt -> tự chọn phương thức online ĐẦU tiên còn bật.
                                $autoCheckOnline = !$codEnabled;
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Cash On Delivery -->
                                @if ($codEnabled)
                                    <label
                                        class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                        <input type="radio" name="payment_method" value="cod" checked
                                            class="text-primary focus:ring-primary">
                                        <div class="flex items-center gap-3">
                                            <span class="material-symbols-outlined text-primary text-3xl">handshake</span>
                                            <div>
                                                <span class="block font-bold text-on-surface">Tiền mặt (COD)</span>
                                                <span class="text-xs text-on-surface-variant">Thanh toán khi nhận
                                                    hàng</span>
                                            </div>
                                        </div>
                                    </label>
                                @endif

                                <!-- Chuyển khoản qua VNPay -->
                                @if ($vnpayEnabled)
                                    <label
                                        class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                        <input type="radio" name="payment_method" value="vnpay"
                                            {{ $autoCheckOnline ? 'checked' : '' }}
                                            class="text-primary focus:ring-primary">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="material-symbols-outlined text-primary text-3xl material-filled">credit_card</span>
                                            <div>
                                                <span class="block font-bold text-on-surface">Chuyển khoản (VNPay)</span>
                                                <span class="text-xs text-on-surface-variant">ATM, Visa/Master/JCB,
                                                    QR...</span>
                                            </div>
                                        </div>
                                    </label>
                                    @php $autoCheckOnline = false; @endphp
                                @endif

                                @if (!$codEnabled && !$vnpayEnabled)
                                    <div
                                        class="col-span-full p-4 bg-red-50 text-red-800 border border-red-200 rounded-xl text-sm font-semibold">
                                        Cửa hàng hiện đang tạm ngắt toàn bộ cổng thanh toán. Không thể hoàn tất đặt hàng lúc
                                        này.
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>

                    <!-- Right: Order Summary -->
                    <div class="space-y-6">
                        <!-- Order Items Summary Card -->
                        <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                            <div class="border-b border-outline-variant pb-4 mb-4">
                                <h2 class="font-headline-md text-lg text-on-surface font-bold">Tóm tắt đơn hàng</h2>
                            </div>

                            {{-- Hidden inputs truyền danh sách cart_item_id đã chọn vào form --}}
                            @foreach ($items as $item)
                                <input type="hidden" name="selected_item_ids[]" value="{{ $item->id }}">
                            @endforeach

                            <!-- Product List -->
                            <div class="divide-y divide-outline-variant/50 max-h-96 overflow-y-auto pr-1">
                                @foreach ($items as $item)
                                    <div class="flex gap-4 py-4 first:pt-0 last:pb-0">
                                        <div
                                            class="w-16 h-16 rounded-lg overflow-hidden bg-surface-container flex-shrink-0 border border-outline-variant/60">
                                            <img src="{{ upload_url($item->image) }}"
                                                onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'"
                                                class="w-full h-full object-cover">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-on-surface text-sm truncate"
                                                title="{{ $item->name }}">{{ $item->name }}</h4>
                                            <p class="text-xs text-on-surface-variant mt-0.5 font-medium">
                                                x{{ $item->quantity }} • Size {{ $item->size_name }}
                                                @if ($item->sugar_level !== null)
                                                    • Đường: {{ $item->sugar_level }}%
                                                @endif
                                                @if ($item->ice_level !== null)
                                                    • Đá:
                                                    {{ $item->ice_level == 'normal' ? 'Thường' : ($item->ice_level == 'no' ? 'Không đá' : $item->ice_level) }}
                                                @endif
                                            </p>
                                            @if ($item->toppings && $item->toppings->isNotEmpty())
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

                                {{-- Quà tặng combo --}}
                                <div id="combo-gift-list"></div>
                            </div>
                        </section>

                        <!-- Promotion Code Card -->
                        <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                            <div class="border-b border-outline-variant pb-4 mb-4">
                                <h2 class="font-headline-md text-lg text-on-surface font-bold">Mã giảm giá</h2>
                            </div>

                            @if ($availablePromotions->isNotEmpty())
                                {{-- Danh sách mã có thể áp dụng --}}
                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">
                                        Mã khả dụng</p>
                                    <div class="flex flex-wrap gap-2" id="coupon-chip-list">
                                        @foreach ($availablePromotions as $promo)
                                            @php
                                                // Tạo nhãn mô tả ngắn gọn cho chip
                                                $isCombo = $promo->scope === 'combo';
                                                if ($isCombo) {
                                                    // Lấy cấu hình phần thưởng combo
                                                    $cfg = $promo->combo;
                                                    $parts = [];
                                                    if ($cfg->hasDiscount()) {
                                                        $parts[] =
                                                            $cfg->discount_type === 'percent'
                                                                ? 'giảm ' . (float) $cfg->discount_value . '%'
                                                                : 'giảm ' .
                                                                    number_format($cfg->discount_value, 0, ',', '.') .
                                                                    'đ';
                                                    }
                                                    if ($cfg->hasGift() && $cfg->giftProduct) {
                                                        $parts[] =
                                                            'tặng ' .
                                                            $cfg->gift_quantity .
                                                            ' ' .
                                                            $cfg->giftProduct->name;
                                                    }
                                                    $label = 'Combo: ' . implode(', ', $parts);
                                                } elseif ($promo->type === 'percent') {
                                                    $label = 'Giảm ' . (int) $promo->value . '%';
                                                    if ($promo->max_discount_amount) {
                                                        $label .=
                                                            ' (tối đa ' .
                                                            number_format($promo->max_discount_amount, 0, ',', '.') .
                                                            'đ)';
                                                    }
                                                } else {
                                                    $label = 'Giảm ' . number_format($promo->value, 0, ',', '.') . 'đ';
                                                }
                                                // Điều kiện tối thiểu
                                                $condition = $promo->min_order_amount
                                                    ? 'Đơn từ ' .
                                                        number_format($promo->min_order_amount, 0, ',', '.') .
                                                        'đ'
                                                    : null;
                                                // Hạng thành viên
                                                $memberLabels = [
                                                    'silver' => 'Bạc',
                                                    'gold' => 'Vàng',
                                                    'diamond' => 'Kim cương',
                                                ];
                                                $memberReq =
                                                    $promo->apply_for && $promo->apply_for !== 'all'
                                                        ? $memberLabels[$promo->apply_for] ?? $promo->apply_for
                                                        : null;
                                            @endphp
                                            <button type="button"
                                                class="coupon-chip group flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-primary/40 bg-primary/5 hover:bg-primary/15 hover:border-primary text-xs font-semibold text-primary transition-all active:scale-95"
                                                data-code="{{ $promo->code }}"
                                                title="{{ $label }}{{ $condition ? ' · ' . $condition : '' }}{{ $memberReq ? ' · Hạng ' . $memberReq : '' }}">
                                                <span
                                                    class="material-symbols-outlined text-[14px]">{{ $isCombo ? 'redeem' : 'local_offer' }}</span>
                                                <span>{{ $promo->code }}</span>
                                                <span class="text-primary/70 font-normal hidden sm:inline">—
                                                    {{ $label }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-2">
                                <input type="text" id="coupon_code_input" placeholder="Nhập mã HAPPY..."
                                    class="flex-1 bg-surface-container-low border-none rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary">
                                <button type="button" id="apply_coupon_btn"
                                    class="bg-primary text-white font-bold text-sm px-4 rounded-lg hover:opacity-90 transition active:scale-95">
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
                            $maxRedeemPercent = (float) \App\Models\Setting::getValue(
                                'loyalty_max_redeem_percent',
                                100,
                            );
                            $minPointsToRedeem = (int) \App\Models\Setting::getValue(
                                'loyalty_min_points_to_redeem',
                                10,
                            );
                        @endphp
                        @if ($loyaltyEnabled && $points >= $minPointsToRedeem)
                            <!-- Points Redemption Card -->
                            <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                                <div class="border-b border-outline-variant pb-4 mb-4 flex items-center justify-between">
                                    <h2 class="font-headline-md text-lg text-on-surface font-bold">Dùng điểm tích lũy</h2>
                                    <span
                                        class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold">Điểm</span>
                                </div>

                                <div class="space-y-3">
                                    <p class="text-xs text-on-surface-variant leading-relaxed">
                                        Bạn đang có <span class="font-bold text-primary">{{ $points }}</span> điểm
                                        (1 điểm = {{ number_format($pointValue, 0, ',', '.') }}đ).
                                        Bạn có thể đổi tối đa <span class="font-bold text-primary"
                                            id="max-redeemable-points">0</span> điểm cho đơn hàng này.
                                    </p>
                                    <div class="flex gap-2">
                                        <input type="number" name="points_to_redeem" id="points_to_redeem_input"
                                            min="0" max="{{ $points }}"
                                            placeholder="Nhập số điểm muốn đổi..."
                                            class="flex-1 bg-surface-container-low border-none rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary"
                                            data-points-balance="{{ $points }}"
                                            data-point-value="{{ $pointValue }}"
                                            data-max-redeem-percent="{{ $maxRedeemPercent }}"
                                            data-min-points-to-redeem="{{ $minPointsToRedeem }}">
                                        <button type="button" id="apply_points_btn"
                                            class="bg-primary text-white font-bold text-sm px-4 rounded-lg hover:opacity-90 transition active:scale-95">
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

                            <div class="space-y-3" id="price-summary" data-subtotal="{{ $subtotal }}"
                                data-freeship-threshold="{{ $freeShipThreshold }}">
                                <div class="flex justify-between text-sm text-on-surface-variant font-medium">
                                    <span>Tạm tính (Sản phẩm)</span>
                                    <span id="summary-subtotal-text">{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="flex justify-between text-sm text-on-surface-variant font-medium"
                                    id="summary-shipping-distance-row">
                                    <span>Phí giao hàng (<span id="summary-distance-km-val">0.0</span> km ×
                                        {{ number_format((float) \App\Models\Setting::getValue('shipping_fee_per_km', 5000), 0, ',', '.') }}đ)</span>
                                    <span id="summary-shipping-distance-text">0đ</span>
                                </div>
                                <div class="flex justify-between text-sm text-primary font-bold hidden"
                                    id="summary-free-ship-row">
                                    <span id="summary-free-ship-text">
                                        @if ($freeShipThreshold == 0)
                                            🎉 Miễn phí giao hàng (Ưu đãi Kim Cương)
                                        @else
                                            🎉 Miễn phí giao hàng (Đơn ≥
                                            {{ number_format($freeShipThreshold, 0, ',', '.') }}đ)
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm text-on-surface-variant font-medium hidden"
                                    id="summary-weather-fee-row">
                                    <span>Phụ thu thời tiết (<span id="summary-weather-condition-val">Bình
                                            thường</span>)</span>
                                    <span id="summary-weather-fee-text" class="text-error font-bold">+0đ</span>
                                </div>

                                <div class="flex justify-between text-sm text-on-surface-variant font-medium hidden"
                                    id="summary-discount-row">
                                    <span>Giảm giá</span>
                                    <span class="text-error font-bold" id="summary-discount-text">-0đ</span>
                                </div>
                                <div
                                    class="flex justify-between text-base font-bold text-on-surface border-t border-outline-variant pt-3 mt-1">
                                    <span>Tổng cộng</span>
                                    <span id="summary-total-text"
                                        class="text-primary text-lg font-extrabold">{{ number_format($subtotal + 15000, 0, ',', '.') }}đ</span>
                                </div>
                            </div>

                            <!-- Customer Note -->
                            <div class="mt-4 pt-4 border-t border-outline-variant/60">
                                <label
                                    class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Ghi
                                    chú cho đơn hàng</label>
                                <textarea name="note" rows="3"
                                    placeholder="Ghi chú về thời gian giao hàng, địa chỉ chi tiết hoặc hướng dẫn giao hàng..."
                                    class="w-full bg-surface-container-low border-none rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                            </div>

                            <!-- Order Action button -->
                            @if (isset($isClosed) && $isClosed)
                                <button type="button" disabled
                                    class="w-full bg-gray-300 text-gray-500 font-bold text-center py-3.5 rounded-xl cursor-not-allowed mt-6">
                                    @if (!\App\Models\Setting::getValue('orders_enabled', true))
                                        Cửa hàng tạm ngưng nhận đơn
                                    @else
                                        Cửa hàng đóng cửa ({{ \App\Models\Setting::getValue('store_open_time', '08:00') }}
                                        - {{ \App\Models\Setting::getValue('store_close_time', '22:00') }})
                                    @endif
                                </button>
                            @elseif(!$addresses->isEmpty())
                                <button type="submit" id="order-submit-btn" data-closed="0"
                                    class="w-full bg-primary-container text-on-primary hover:bg-[#008f00] font-bold text-center py-3.5 rounded-xl shadow-sm hover:shadow-md transition-all active:scale-98 mt-6">
                                    <span id="submit-btn-text">Đặt hàng (COD)</span>
                                </button>
                            @else
                                <button type="button" disabled
                                    class="w-full bg-gray-300 text-gray-500 font-bold text-center py-3.5 rounded-xl cursor-not-allowed mt-6">
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
        <script>
            let map;
            let marker;

            let provincesData = null;
            let provincesLoading = false;
            let wardsDataByProvince = {};
            let wardsLoading = false;

            const areaSearchItems = {
                province: [],
                ward: []
            };

            let locationMethod = 'map';
            let areaUserSelected = false;
            let specificUserEdited = false;
            let pendingMapLatLng = null;
            let mapReverseTimer = null;

            // Lấy API key bản đồ Geoapify từ cấu hình nhúng trong trang
            function geoapifyKey() {
                return (window.checkoutConfig && window.checkoutConfig.geoapifyKey) || '';
            }

            // Hiện trạng thái của bước lấy vị trí
            function setLocStatus(state, extraText) {
                const iconEl = document.getElementById('locStatusIcon');
                const textEl = document.getElementById('locStatusText');
                const boxEl = document.getElementById('locStatus');
                if (!iconEl || !textEl || !boxEl) return;

                const map = {
                    idle: ['location_searching', 'Chưa xác định vị trí', 'text-on-surface-variant'],
                    manual: ['edit_location_alt',
                        'Nhập đầy đủ khu vực + địa chỉ cụ thể — hệ thống sẽ tự ghim vị trí lên bản đồ để bạn kiểm tra',
                        'text-on-surface-variant'
                    ],
                    locating: ['pending', 'Đang xác định vị trí...', 'text-amber-600'],
                    ok: ['check_circle', 'Đã xác định vị trí', 'text-primary'],
                    notfound: ['error', 'Không tìm thấy địa chỉ', 'text-error'],
                    outofrange: ['wrong_location', 'Ngoài phạm vi giao hàng', 'text-error'],
                };
                const cfg = map[state] || map.idle;
                iconEl.textContent = cfg[0];
                textEl.textContent = extraText || cfg[1];
                boxEl.className =
                    'mb-4 flex items-center gap-2 text-sm font-medium rounded-xl px-3 py-2.5 bg-surface-container-lowest ' +
                    cfg[2];
            }

            // Ghi nhận khách lấy tọa độ bằng cách nào: định vị gps, chọn trên bản đồ hay gõ tay
            function setLocationMethod(method) {
                if (!['gps', 'map', 'manual'].includes(method)) method = 'map';
                const methodChanged = method !== locationMethod;
                locationMethod = method;
                if (methodChanged) {
                    areaUserSelected = false;
                    specificUserEdited = false;
                }
                const hidden = document.getElementById('addr_location_method');
                if (hidden) hidden.value = method;

                document.querySelectorAll('.loc-method-btn').forEach(function(btn) {
                    const active = btn.dataset.method === method;
                    btn.classList.toggle('bg-primary', active);
                    btn.classList.toggle('text-white', active);
                    btn.classList.toggle('shadow-sm', active);
                    btn.classList.toggle('text-on-surface-variant', !active);
                });

                const gpsBlock = document.getElementById('gpsBlock');
                const mapColumn = document.getElementById('mapColumn');
                const mapHint = document.getElementById('mapHint');
                const manualMapHint = document.getElementById('manualMapHint');
                const grid = document.getElementById('addressGrid');
                const confirmBtn = document.getElementById('btnConfirmMapLocation');

                if (gpsBlock) gpsBlock.classList.toggle('hidden', method !== 'gps');
                if (mapHint) mapHint.classList.toggle('hidden', method !== 'map');
                if (manualMapHint) manualMapHint.classList.toggle('hidden', method !== 'manual');
                if (confirmBtn) confirmBtn.classList.add('hidden');

                if (mapColumn) mapColumn.classList.remove('hidden');
                if (grid) {
                    grid.classList.add('lg:grid-cols-2');
                    grid.classList.remove('lg:grid-cols-1');
                }
                setTimeout(function() {
                    initMapIfNeeded();
                    if (map) {
                        map.invalidateSize();
                        const lat = parseFloat(document.getElementById('addr_lat').value) || 10.73809;
                        const lng = parseFloat(document.getElementById('addr_lng').value) || 106.67812;
                        map.setView([lat, lng], 15);
                        if (marker) marker.setLatLng([lat, lng]);
                    }
                }, 50);

                if (method === 'manual') {
                    setLocStatus(document.getElementById('addr_lat').value ? 'ok' : 'manual');
                    scheduleManualForwardGeocode();
                } else {
                    setLocStatus(document.getElementById('addr_lat').value ? 'ok' : 'idle');
                }

                updateSaveButtonState();
            }

            // Khởi tạo bản đồ Leaflet khi cần sử dụng
            function initMapIfNeeded() {
                if (map) return;
                const mapEl = document.getElementById('addressMap');
                if (!mapEl) return;
                const key = geoapifyKey();
                if (!key) return;

                const lat = 10.7433;
                const lng = 106.6738;

                map = L.map('addressMap').setView([lat, lng], 14);
                L.tileLayer(`https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png?apiKey=${key}`, {
                    attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank" rel="noopener">Geoapify</a> | © OpenStreetMap contributors'
                }).addTo(map);

                marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);

                marker.on('dragend', function() {
                    const position = marker.getLatLng();
                    onMapPointPicked(position.lat, position.lng);
                });

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    onMapPointPicked(e.latlng.lat, e.latlng.lng);
                });
            }

            // Xử lý khi khách bấm chọn một điểm trên bản đồ
            function onMapPointPicked(lat, lng) {
                if (locationMethod === 'gps') {
                    document.getElementById('addr_lat').value = lat.toFixed(6);
                    document.getElementById('addr_lng').value = lng.toFixed(6);
                    reverseGeocode(lat, lng);
                    if (!flagOutOfRange(lat, lng)) {
                        setLocStatus('ok', 'Đã xác định vị trí hiện tại');
                    }
                    updateSaveButtonState();
                    return;
                }

                pendingMapLatLng = {
                    lat: lat,
                    lng: lng
                };
                const confirmBtn = document.getElementById('btnConfirmMapLocation');
                if (confirmBtn) confirmBtn.classList.remove('hidden');
                setLocStatus('locating', 'Đã chọn 1 điểm — bấm "Xác nhận vị trí này" để dùng.');

                if (mapReverseTimer) clearTimeout(mapReverseTimer);
                mapReverseTimer = setTimeout(function() {
                    reverseGeocode(lat, lng);
                }, 500);
            }

            // Chốt vị trí đã chọn trên bản đồ làm địa chỉ giao hàng
            function confirmMapLocation() {
                if (!pendingMapLatLng) return;
                document.getElementById('addr_lat').value = pendingMapLatLng.lat.toFixed(6);
                document.getElementById('addr_lng').value = pendingMapLatLng.lng.toFixed(6);
                reverseGeocode(pendingMapLatLng.lat, pendingMapLatLng.lng);
                const confirmBtn = document.getElementById('btnConfirmMapLocation');
                if (confirmBtn) confirmBtn.classList.add('hidden');
                if (!flagOutOfRange(pendingMapLatLng.lat, pendingMapLatLng.lng)) {
                    setLocStatus('ok', 'Đã xác định vị trí trên bản đồ');
                }
                updateSaveButtonState();
            }

            let manualGeocodeTimer = null;
            const MANUAL_GEOCODE_MIN_CONFIDENCE = 0.3;

            // Ghép địa chỉ đầy đủ để tra cứu tọa độ; thiếu phần nào thì trả null để khỏi gọi API
            function buildManualAddressQuery() {
                const specific = (document.getElementById('addr_specific').value || '').trim();
                const wardName = (document.getElementById('addr_ward_search').value || '').trim();
                const provinceName = (document.getElementById('addr_province_search').value || '').trim();
                if (!specific || !wardName || !provinceName) return null;

                let query = [specific, wardName, provinceName].join(', ');
                query = query.replace(/phường\s*\d+/giu, '');
                query = query.replace(/,\s*,/g, ',');
                query = query.replace(/,\s*$/, '').trim() + ', Việt Nam';
                return query;
            }

            // Hoãn việc tra tọa độ vài trăm mili-giây sau khi khách ngừng gõ, tránh gọi API liên tục
            function scheduleManualForwardGeocode() {
                if (locationMethod !== 'manual') return;
                if (manualGeocodeTimer) clearTimeout(manualGeocodeTimer);
                manualGeocodeTimer = setTimeout(runManualForwardGeocode, 900);
            }

            // Đổi địa chỉ khách gõ tay thành tọa độ
            function runManualForwardGeocode() {
                if (locationMethod !== 'manual') return;
                const query = buildManualAddressQuery();
                if (!query) return;

                const key = geoapifyKey();
                if (!key) return;

                const cfg = window.checkoutConfig || {};
                const biasLat = cfg.shopLat || 10.73809;
                const biasLng = cfg.shopLng || 106.67812;

                setLocStatus('locating', 'Đang xác định vị trí từ địa chỉ đã nhập...');

                const url =
                    `https://api.geoapify.com/v1/geocode/search?text=${encodeURIComponent(query)}&lang=vi&limit=1&bias=proximity:${biasLng},${biasLat}&apiKey=${key}`;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (locationMethod !== 'manual') return;

                        const props = data && data.features && data.features[0] && data.features[0].properties;
                        if (!props || props.lat === undefined || props.lon === undefined) {
                            setLocStatus('notfound',
                                'Không tìm thấy vị trí cho địa chỉ này. Vui lòng kiểm tra lại hoặc chạm/kéo ghim trên bản đồ bên dưới để chọn thủ công.'
                            );
                            return;
                        }

                        const lat = props.lat;
                        const lng = props.lon;
                        const confidence = (props.rank && props.rank.confidence) || 0;

                        initMapIfNeeded();
                        if (map) {
                            map.setView([lat, lng], 16);
                            if (marker) marker.setLatLng([lat, lng]);
                        }
                        document.getElementById('addr_lat').value = lat.toFixed(6);
                        document.getElementById('addr_lng').value = lng.toFixed(6);
                        document.getElementById('addr_formatted').value = props.formatted || '';

                        if (flagOutOfRange(lat, lng)) {} else if (confidence < MANUAL_GEOCODE_MIN_CONFIDENCE) {
                            setLocStatus('locating',
                                'Chưa chắc chắn vị trí này đúng — vui lòng nhìn kỹ ghim trên bản đồ, kéo lại nếu chưa đúng.'
                            );
                        } else {
                            setLocStatus('ok', 'Đã xác định vị trí — kiểm tra ghim trên bản đồ, kéo chỉnh nếu chưa đúng.');
                        }
                        updateSaveButtonState();
                    })
                    .catch(() => {
                        if (locationMethod !== 'manual') return;
                        setLocStatus('notfound',
                            'Không thể xác định vị trí lúc này. Vui lòng chạm/kéo ghim trên bản đồ để chọn thủ công.');
                    });
            }

            // Tính khoảng cách đường chim bay bằng công thức Haversine, dùng kiểm tra nhanh phạm vi giao ngay tại trình duyệt
            function straightLineKm(lat1, lng1, lat2, lng2) {
                // Đổi độ sang radian cho công thức tính khoảng cách
                const toRad = function(d) {
                    return d * Math.PI / 180;
                };
                const R = 6371;
                const dLat = toRad(lat2 - lat1);
                const dLng = toRad(lng2 - lng1);
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            // Cảnh báo ngay khi địa chỉ vượt quá bán kính giao hàng tối đa, kèm số km cụ thể
            function flagOutOfRange(lat, lng) {
                const cfg = window.checkoutConfig || {};
                if (!cfg.shopLat || !cfg.shopLng || !cfg.shippingMaxDistanceKm) return false;
                const km = straightLineKm(cfg.shopLat, cfg.shopLng, lat, lng);
                if (km > cfg.shippingMaxDistanceKm) {
                    setLocStatus('outofrange', 'Ngoài phạm vi giao hàng (khoảng ' + km.toFixed(1) + ' km, tối đa ' + cfg
                        .shippingMaxDistanceKm + ' km)');
                    return true;
                }
                return false;
            }

            // Từ kết quả bản đồ trả về, tự đoán và chọn sẵn đúng Tỉnh/Phường; bỏ qua nếu khách đã tự chọn tay để không ghi đè lựa chọn của họ
            function applyLocationProperties(props) {
                if (areaUserSelected) return;
                if (!provincesData) return;

                const provinceGuess = normalizeVN(props.state || '');
                if (!provinceGuess) return;
                const province = provincesData.find(p => normalizeVN(p.name) === provinceGuess);
                if (!province) return;

                const provinceSel = document.getElementById('addr_province_select');
                if (provinceSel && provinceSel.value !== String(province.code)) {
                    provinceSel.value = String(province.code);
                    setAreaSearchValue('province', province.name);
                    document.getElementById('addr_province_code').value = String(province.code);
                    showAreaError('province', '');
                }

                const wardGuess = normalizeVN(props.suburb || props.district || '');
                loadWardsFor(province.code).then(wards => {
                    if (!wards || areaUserSelected || !wardGuess) return;
                    const ward = wards.find(w => normalizeVN(w.name) === wardGuess);
                    if (!ward) return;
                    const wardSel = document.getElementById('addr_ward_select');
                    if (wardSel) {
                        wardSel.value = String(ward.code);
                        setAreaSearchValue('ward', ward.name);
                        document.getElementById('addr_ward_code').value = String(ward.code);
                        showAreaError('ward', '');
                    }
                    updateSaveButtonState();
                });

                updateSaveButtonState();
            }

            // Gọi API đổi tọa độ ngược thành địa chỉ chữ
            function fetchReverse(lat, lng, type) {
                const key = geoapifyKey();
                let url = `https://api.geoapify.com/v1/geocode/reverse?lat=${lat}&lon=${lng}&lang=vi&apiKey=${key}`;
                if (type) url += `&type=${type}`;
                return fetch(url)
                    .then(res => res.json())
                    .then(data => (data && data.features && data.features[0] && data.features[0].properties) || null);
            }

            // Đổi tọa độ thành địa chỉ chữ để điền sẵn vào form
            function reverseGeocode(lat, lng) {
                const key = geoapifyKey();
                if (!key) return;
                fetchReverse(lat, lng, 'building')
                    .then(props => props || fetchReverse(lat, lng, null))
                    .then(props => {
                        if (!props) return;
                        applyLocationProperties(props);

                        const specificParts = [props.housenumber, props.street].filter(Boolean);
                        const specificEl = document.getElementById('addr_specific');
                        if (specificEl && !specificUserEdited && specificParts.length > 0) {
                            specificEl.value = specificParts.join(' ');
                            updateSaveButtonState();
                        }
                    })
                    .catch(err => console.error(err));
            }

            // Chuyển chuỗi tiếng Việt sang dạng không dấu để tìm kiếm
            function normalizeVN(str) {
                return (str || '')
                    .toString()
                    .toLowerCase()
                    .replace(/đ/g, 'd')
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/\b(thanh pho|tinh|phuong|xa|thi tran)\b/g, ' ')
                    .replace(/[^a-z0-9]+/g, ' ')
                    .trim();
            }

            // Truy xuất nhóm phần tử của khối tìm kiếm khu vực
            function areaSearchElements(which) {
                return {
                    search: document.getElementById(which === 'province' ? 'addr_province_search' : 'addr_ward_search'),
                    select: document.getElementById(which === 'province' ? 'addr_province_select' : 'addr_ward_select'),
                    dropdown: document.getElementById(which === 'province' ? 'addr_province_dropdown' : 'addr_ward_dropdown'),
                    options: document.getElementById(which === 'province' ? 'addr_province_options' : 'addr_ward_options'),
                    empty: document.getElementById(which === 'province' ? 'addr_province_empty' : 'addr_ward_empty'),
                };
            }

            // Nạp danh sách dữ liệu cho ô tìm kiếm khu vực
            function setAreaSearchItems(which, items) {
                areaSearchItems[which] = Array.isArray(items) ? items : [];
                renderAreaOptions(which, '');
            }

            // Điền giá trị đã chọn vào ô tìm kiếm khu vực
            function setAreaSearchValue(which, value) {
                const {
                    search
                } = areaSearchElements(which);
                if (search) search.value = value || '';
            }

            // Vẽ lại danh sách gợi ý tỉnh/phường
            function renderAreaOptions(which, query) {
                const {
                    options,
                    empty,
                    select
                } = areaSearchElements(which);
                if (!options || !empty) return;

                const normalizedQuery = normalizeVN(query || '');
                const filtered = areaSearchItems[which].filter(item =>
                    !normalizedQuery || normalizeVN(item.name).includes(normalizedQuery)
                );
                options.innerHTML = '';

                filtered.forEach(item => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.code = String(item.code);
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', select && select.value === String(item.code) ? 'true' :
                        'false');
                    button.className =
                        'w-full text-left px-3 py-2.5 rounded-lg text-sm hover:bg-primary-container/20 focus:bg-primary-container/20 focus:outline-none transition-colors';
                    if (select && select.value === String(item.code)) {
                        button.classList.add('bg-primary-container/20', 'text-primary', 'font-bold');
                    }
                    button.textContent = item.name;
                    button.addEventListener('click', function() {
                        chooseAreaOption(which, item.code, item.name);
                    });
                    options.appendChild(button);
                });

                empty.classList.toggle('hidden', filtered.length > 0);
            }

            // Mở danh sách gợi ý tỉnh/phường
            function openAreaSearch(which) {
                const {
                    search,
                    dropdown
                } = areaSearchElements(which);
                if (!search || !dropdown || search.disabled) return;

                ['province', 'ward'].forEach(other => {
                    if (other !== which) closeAreaSearch(other);
                });
                renderAreaOptions(which, '');
                dropdown.classList.remove('hidden');
                search.setAttribute('aria-expanded', 'true');
                requestAnimationFrame(function() {
                    search.select();
                });
            }

            // Đóng danh sách gợi ý tỉnh/phường
            function closeAreaSearch(which) {
                const {
                    search,
                    select,
                    dropdown
                } = areaSearchElements(which);
                if (!dropdown) return;
                dropdown.classList.add('hidden');
                if (search) {
                    search.setAttribute('aria-expanded', 'false');
                    const selected = areaSearchItems[which].find(item => select && String(item.code) === select.value);
                    search.value = selected ? selected.name : '';
                }
            }

            // Mở/đóng danh sách gợi ý tỉnh/phường
            function toggleAreaSearch(which) {
                const {
                    search,
                    dropdown
                } = areaSearchElements(which);
                if (!search || !dropdown || search.disabled) return;
                if (dropdown.classList.contains('hidden')) {
                    search.focus();
                    openAreaSearch(which);
                } else {
                    closeAreaSearch(which);
                }
            }

            // Lọc danh sách gợi ý theo từ khóa đang gõ
            function filterAreaOptions(which) {
                const {
                    search,
                    dropdown
                } = areaSearchElements(which);
                if (!search || !dropdown) return;

                renderAreaOptions(which, search.value);
                dropdown.classList.remove('hidden');
                search.setAttribute('aria-expanded', 'true');
            }

            // Chốt lựa chọn tỉnh/phường và điền vào ô hiển thị
            function chooseAreaOption(which, code, name) {
                const {
                    search,
                    select,
                    dropdown
                } = areaSearchElements(which);
                if (!search || !select) return;

                const oldCode = select.value;
                select.value = String(code);
                search.value = name;
                if (dropdown) dropdown.classList.add('hidden');
                search.setAttribute('aria-expanded', 'false');

                if (oldCode === String(code)) {
                    updateSaveButtonState();
                    return;
                }
                if (which === 'province') onProvinceChange();
                else onWardChange();
            }

            // Cho phép dùng phím mũi tên và Enter để chọn trong danh sách gợi ý
            function handleAreaSearchKeydown(event, which) {
                if (event.key === 'Escape') {
                    closeAreaSearch(which);
                    event.target.blur();
                    return;
                }
                if (event.key !== 'Enter') return;

                const {
                    options,
                    dropdown
                } = areaSearchElements(which);
                const first = options ? options.querySelector('button') : null;
                if (first && dropdown && !dropdown.classList.contains('hidden')) {
                    event.preventDefault();
                    first.click();
                }
            }

            // Mã hóa ký tự đặc biệt trước khi chèn chuỗi vào HTML, chống lỗi xss
            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str == null ? '' : String(str);
                return div.innerHTML;
            }

            // Hiển thị thông tin mã giảm giá đã áp dụng
            function renderCouponSuccessInfo(data, code) {
                const lines = [];
                lines.push(
                    '<p class="flex items-center gap-1 text-primary font-bold">' +
                    '<span class="material-symbols-outlined text-sm">check_circle</span>' +
                    'Áp dụng thành công mã ' + escapeHtml(code) + '!' +
                    '</p>'
                );

                const discountAmount = parseFloat(data.discount_amount);
                if (!isNaN(discountAmount) && discountAmount > 0) {
                    lines.push('<p class="text-on-surface font-semibold mt-1">Giảm ' + discountAmount.toLocaleString('vi-VN') +
                        'đ</p>');
                }

                if (data.description) {
                    lines.push('<p class="text-on-surface-variant font-normal mt-0.5">' + escapeHtml(data.description) +
                        '</p>');
                }

                if (data.scope_label) {
                    lines.push('<p class="text-on-surface-variant font-normal mt-0.5">' + escapeHtml(data.scope_label) +
                        '</p>');
                }

                if (data.end_at) {
                    lines.push('<p class="text-on-surface-variant font-normal mt-0.5">Hạn dùng: ' + escapeHtml(data.end_at) +
                        '</p>');
                }

                return lines.join('');
            }

            // Hiện lỗi cho khối chọn khu vực
            function showAreaError(which, msg) {
                const help = document.getElementById(which === 'province' ? 'provinceHelpText' : 'wardHelpText');
                const sel = document.getElementById(which === 'province' ? 'addr_province_select' : 'addr_ward_select');
                const search = document.getElementById(which === 'province' ? 'addr_province_search' : 'addr_ward_search');
                if (help) {
                    help.textContent = msg || '';
                    help.classList.toggle('hidden', !msg);
                }
                if (sel) sel.setAttribute('aria-invalid', msg ? 'true' : 'false');
                if (search) search.setAttribute('aria-invalid', msg ? 'true' : 'false');
            }

            // Tải danh sách Tỉnh/Thành phố từ API
            function loadProvinces() {
                if (provincesData) {
                    setAreaSearchItems('province', provincesData);
                    return Promise.resolve(provincesData);
                }

                provincesLoading = true;
                const sel = document.getElementById('addr_province_select');
                const search = document.getElementById('addr_province_search');
                if (sel) {
                    sel.disabled = true;
                    sel.innerHTML = '<option value="">Đang tải tỉnh/thành phố...</option>';
                }
                if (search) {
                    search.disabled = true;
                    search.value = '';
                    search.placeholder = 'Đang tải tỉnh/thành phố...';
                }
                updateSaveButtonState();

                return fetch('/administrative/provinces')
                    .then(res => res.json().then(data => ({
                        ok: res.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        provincesLoading = false;
                        if (!ok || !data.success) {
                            showAreaError('province', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
                            if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
                            if (search) {
                                search.disabled = true;
                                search.placeholder = 'Không tải được dữ liệu';
                            }
                            updateSaveButtonState();
                            return null;
                        }
                        provincesData = data.data;
                        if (sel) {
                            sel.disabled = false;
                            sel.innerHTML = '<option value="">Chọn tỉnh/thành phố</option>' +
                                provincesData.map(p => `<option value="${p.code}">${escapeHtml(p.name)}</option>`).join('');
                        }
                        setAreaSearchItems('province', provincesData);
                        if (search) {
                            search.disabled = false;
                            search.placeholder = 'Tìm tỉnh/thành phố...';
                        }
                        updateSaveButtonState();
                        return provincesData;
                    })
                    .catch(() => {
                        provincesLoading = false;
                        showAreaError('province', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
                        if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
                        if (search) {
                            search.disabled = true;
                            search.placeholder = 'Không tải được dữ liệu';
                        }
                        updateSaveButtonState();
                        return null;
                    });
            }

            // Tải danh sách Phường/Xã thuộc một tỉnh
            function loadWardsFor(provinceCode) {
                // Đổ dữ liệu phường/xã vào ô chọn
                const fillWardOptions = function(wards) {
                    const provinceSelect = document.getElementById('addr_province_select');
                    if (provinceSelect && provinceSelect.value !== String(provinceCode)) return;
                    const wardSelect = document.getElementById('addr_ward_select');
                    const wardSearch = document.getElementById('addr_ward_search');
                    if (wardSelect) {
                        wardSelect.disabled = false;
                        wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>' +
                            wards.map(w => `<option value="${w.code}">${escapeHtml(w.name)}</option>`).join('');
                    }
                    setAreaSearchItems('ward', wards);
                    if (wardSearch) {
                        wardSearch.disabled = false;
                        wardSearch.placeholder = 'Tìm phường/xã...';
                    }
                };

                if (wardsDataByProvince[provinceCode]) {
                    fillWardOptions(wardsDataByProvince[provinceCode]);
                    return Promise.resolve(wardsDataByProvince[provinceCode]);
                }

                wardsLoading = true;
                const sel = document.getElementById('addr_ward_select');
                const search = document.getElementById('addr_ward_search');
                if (sel) {
                    sel.disabled = true;
                    sel.innerHTML = '<option value="">Đang tải phường/xã...</option>';
                }
                if (search) {
                    search.disabled = true;
                    search.value = '';
                    search.placeholder = 'Đang tải phường/xã...';
                }
                updateSaveButtonState();

                return fetch(`/administrative/provinces/${provinceCode}/wards`)
                    .then(res => res.json().then(data => ({
                        ok: res.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        wardsLoading = false;
                        if (!ok || !data.success) {
                            showAreaError('ward', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
                            if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
                            if (search) {
                                search.disabled = true;
                                search.placeholder = 'Không tải được dữ liệu';
                            }
                            updateSaveButtonState();
                            return null;
                        }
                        wardsDataByProvince[provinceCode] = data.data;
                        fillWardOptions(data.data);
                        updateSaveButtonState();
                        return data.data;
                    })
                    .catch(() => {
                        wardsLoading = false;
                        showAreaError('ward', 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.');
                        if (sel) sel.innerHTML = '<option value="">Không tải được — thử lại</option>';
                        if (search) {
                            search.disabled = true;
                            search.placeholder = 'Không tải được dữ liệu';
                        }
                        updateSaveButtonState();
                        return null;
                    });
            }

            // Chọn tỉnh xong thì nạp lại danh sách phường tương ứng
            function onProvinceChange() {
                areaUserSelected = true;
                const sel = document.getElementById('addr_province_select');
                const code = sel && sel.value ? sel.value : '';
                document.getElementById('addr_province_code').value = code;
                showAreaError('province', '');

                document.getElementById('addr_ward_code').value = '';
                showAreaError('ward', '');
                const wardSel = document.getElementById('addr_ward_select');
                if (wardSel) {
                    wardSel.disabled = true;
                    wardSel.innerHTML = '<option value="">Vui lòng chọn tỉnh/thành phố trước</option>';
                }
                const wardSearch = document.getElementById('addr_ward_search');
                if (wardSearch) {
                    wardSearch.disabled = true;
                    wardSearch.value = '';
                    wardSearch.placeholder = 'Chọn tỉnh/thành phố trước';
                }
                setAreaSearchItems('ward', []);

                document.getElementById('addr_lat').value = '';
                document.getElementById('addr_lng').value = '';
                pendingMapLatLng = null;
                const confirmBtn = document.getElementById('btnConfirmMapLocation');
                if (confirmBtn) confirmBtn.classList.add('hidden');
                setLocStatus('idle');

                updateSaveButtonState();
                if (code) loadWardsFor(parseInt(code, 10));
            }

            // Xử lý khi khách đổi phường/xã
            function onWardChange() {
                areaUserSelected = true;
                const sel = document.getElementById('addr_ward_select');
                document.getElementById('addr_ward_code').value = sel && sel.value ? sel.value : '';
                showAreaError('ward', '');
                updateSaveButtonState();
                scheduleManualForwardGeocode();
            }

            // Xóa trắng ô chọn khu vực khi đổi tỉnh
            function resetAreaSelects() {
                areaUserSelected = false;
                document.getElementById('addr_province_code').value = '';
                document.getElementById('addr_ward_code').value = '';
                showAreaError('province', '');
                showAreaError('ward', '');
                const provinceSel = document.getElementById('addr_province_select');
                if (provinceSel && provincesData) provinceSel.value = '';
                setAreaSearchValue('province', '');
                const wardSel = document.getElementById('addr_ward_select');
                if (wardSel) {
                    wardSel.disabled = true;
                    wardSel.innerHTML = '<option value="">Vui lòng chọn tỉnh/thành phố trước</option>';
                }
                const wardSearch = document.getElementById('addr_ward_search');
                if (wardSearch) {
                    wardSearch.disabled = true;
                    wardSearch.value = '';
                    wardSearch.placeholder = 'Chọn tỉnh/thành phố trước';
                }
                setAreaSearchItems('ward', []);
            }

            // Chọn sẵn tỉnh/phường theo tên, dùng khi sửa địa chỉ đã lưu hoặc sau khi tra từ bản đồ
            function preselectAreaByName(provinceName, wardName) {
                loadProvinces().then(provinces => {
                    if (!provinces) return;
                    const provinceGuess = normalizeVN(provinceName);
                    const province = provinces.find(p => normalizeVN(p.name) === provinceGuess);
                    if (!province) return;

                    const provinceSel = document.getElementById('addr_province_select');
                    if (provinceSel) provinceSel.value = String(province.code);
                    setAreaSearchValue('province', province.name);
                    document.getElementById('addr_province_code').value = String(province.code);

                    loadWardsFor(province.code).then(wards => {
                        if (!wards) return;
                        const wardGuess = normalizeVN(wardName);
                        const ward = wards.find(w => normalizeVN(w.name) === wardGuess);
                        if (!ward) return;
                        const wardSel = document.getElementById('addr_ward_select');
                        if (wardSel) wardSel.value = String(ward.code);
                        setAreaSearchValue('ward', ward.name);
                        document.getElementById('addr_ward_code').value = String(ward.code);
                        updateSaveButtonState();
                    });
                });
            }

            // Chỉ mở nút Lưu khi form đã đủ thông tin hợp lệ
            function updateSaveButtonState() {
                const btn = document.getElementById('btnSaveAddress');
                if (!btn) return;
                const fullname = (document.getElementById('addr_fullname').value || '').trim();
                const phone = (document.getElementById('addr_phone').value || '').trim();
                const specific = (document.getElementById('addr_specific').value || '').trim();
                const hasCoords = !!document.getElementById('addr_lat').value;
                const phoneOk = /^(0[3|5|7|8|9])+([0-9]{8})$/.test(phone);
                const areaOk = !!(document.getElementById('addr_province_code').value && document.getElementById(
                    'addr_ward_code').value);
                const locationOk = hasCoords || locationMethod === 'manual';
                const notLoading = !provincesLoading && !wardsLoading;

                const phoneErrorEl = document.getElementById('addr_phone_error');
                if (phoneErrorEl) phoneErrorEl.classList.toggle('hidden', phone === '' || phoneOk);

                btn.disabled = !(fullname && phoneOk && areaOk && specific && locationOk && notLoading);
            }

            // Xin quyền định vị trình duyệt để lấy vị trí hiện tại của khách
            function getCurrentLocation() {
                if (!navigator.geolocation) {
                    setLocStatus('notfound', 'Trình duyệt không hỗ trợ định vị GPS');
                    if (window.FrontendAlert) window.FrontendAlert.error(
                        'Trình duyệt của bạn không hỗ trợ định vị GPS. Vui lòng chọn trên bản đồ hoặc nhập địa chỉ.');
                    else alert('Trình duyệt của bạn không hỗ trợ định vị GPS. Vui lòng chọn trên bản đồ hoặc nhập địa chỉ.');
                    return;
                }

                setLocStatus('locating', 'Đang xác định vị trí hiện tại...');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        document.getElementById('addr_lat').value = lat.toFixed(6);
                        document.getElementById('addr_lng').value = lng.toFixed(6);

                        initMapIfNeeded();
                        if (map) {
                            map.setView([lat, lng], 16);
                            if (marker) marker.setLatLng([lat, lng]);
                        }
                        reverseGeocode(lat, lng);

                        if (!flagOutOfRange(lat, lng)) {
                            if (accuracy && accuracy > 100) {
                                setLocStatus('ok', 'Đã xác định vị trí (độ chính xác thấp ~' + Math.round(accuracy) +
                                    'm) — hãy kéo ghim để chỉnh lại');
                            } else {
                                setLocStatus('ok', 'Đã xác định vị trí hiện tại');
                            }
                        }
                        updateSaveButtonState();
                    },
                    (error) => {
                        let msg = 'Không thể lấy vị trí hiện tại.';
                        if (error.code === error.PERMISSION_DENIED) {
                            msg =
                                'Bạn đã từ chối quyền truy cập vị trí. Vui lòng cấp quyền, hoặc chọn trên bản đồ / nhập địa chỉ thủ công.';
                        } else if (error.code === error.TIMEOUT) {
                            msg =
                                'Xác định vị trí quá lâu (hết thời gian chờ). Vui lòng thử lại, hoặc chọn trên bản đồ / nhập địa chỉ.';
                        } else if (error.code === error.POSITION_UNAVAILABLE) {
                            msg =
                                'Không lấy được vị trí (thiết bị/mạng không hỗ trợ). Vui lòng chọn trên bản đồ / nhập địa chỉ.';
                        }
                        setLocStatus('notfound', 'Không lấy được vị trí GPS');
                        if (window.FrontendAlert) window.FrontendAlert.error(msg);
                        else alert(msg);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }

            // Chọn loại địa chỉ: nhà riêng hay công ty
            function setAddrType(type) {
                document.getElementById('addr_type').value = type;
                const homeBtn = document.getElementById('btnTypeHome');
                const officeBtn = document.getElementById('btnTypeOffice');
                [
                    [homeBtn, 'home'],
                    [officeBtn, 'office']
                ].forEach(([btn, btnType]) => {
                    if (!btn) return;
                    const isActive = btnType === type;
                    btn.classList.toggle('bg-primary', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('border-primary', isActive);
                });
            }

            // Mở hộp thoại thêm/sửa địa chỉ
            function openAddressModal(isEdit = false, data = null) {
                const modal = document.getElementById('addressModal');
                if (!modal) return;
                modal.classList.remove('hidden');

                pendingMapLatLng = null;
                const confirmBtn = document.getElementById('btnConfirmMapLocation');
                if (confirmBtn) confirmBtn.classList.add('hidden');

                loadProvinces();

                if (isEdit && data) {
                    document.getElementById('addressModalTitle').textContent = 'Sửa địa chỉ';
                    document.getElementById('addr_id').value = data.id || '';
                    document.getElementById('addr_fullname').value = data.fullname || '';
                    document.getElementById('addr_phone').value = data.phone || '';
                    document.getElementById('addr_specific').value = data.specificAddress || '';
                    document.getElementById('addr_lat').value = data.latitude || '';
                    document.getElementById('addr_lng').value = data.longitude || '';
                    document.getElementById('addr_formatted').value = '';
                    document.getElementById('addr_default').checked = String(data.isDefault) === '1' || data.isDefault === true;

                    specificUserEdited = false;
                    areaUserSelected = false;
                    setAddrType(data.type === 'office' ? 'office' : 'home');

                    preselectAreaByName(data.province || '', data.ward || '');
                } else {
                    document.getElementById('addressModalTitle').textContent = 'Thêm địa chỉ mới';
                    document.getElementById('addr_id').value = '';
                    document.getElementById('addr_fullname').value = '';
                    document.getElementById('addr_phone').value = '';
                    document.getElementById('addr_specific').value = '';
                    document.getElementById('addr_lat').value = '';
                    document.getElementById('addr_lng').value = '';
                    document.getElementById('addr_formatted').value = '';
                    document.getElementById('addr_default').checked = false;

                    specificUserEdited = false;
                    resetAreaSelects();
                    setAddrType('home');
                }

                setLocationMethod(isEdit && data && data.locationMethod ? data.locationMethod : 'map');
                updateSaveButtonState();
            }

            // Đóng hộp thoại địa chỉ
            function closeAddressModal() {
                const modal = document.getElementById('addressModal');
                if (modal) modal.classList.add('hidden');
            }

            document.addEventListener('click', function(event) {
                const addBtn = event.target.closest('.add-address-btn');
                if (addBtn) {
                    openAddressModal(false);
                    return;
                }

                const editBtn = event.target.closest('.edit-address-btn');
                if (editBtn) {
                    openAddressModal(true, {
                        id: editBtn.dataset.addressId,
                        fullname: editBtn.dataset.fullname,
                        phone: editBtn.dataset.phone,
                        province: editBtn.dataset.province,
                        district: editBtn.dataset.district,
                        ward: editBtn.dataset.ward,
                        specificAddress: editBtn.dataset.specificAddress,
                        type: editBtn.dataset.type,
                        isDefault: editBtn.dataset.isDefault,
                        latitude: editBtn.dataset.latitude,
                        longitude: editBtn.dataset.longitude,
                        locationMethod: editBtn.dataset.locationMethod,
                    });
                }
            });

            // Gửi địa chỉ mới hoặc đã sửa lên server
            function saveAddress() {
                const id = document.getElementById('addr_id').value;
                const fullname = document.getElementById('addr_fullname').value.trim();
                const phone = document.getElementById('addr_phone').value.trim();
                const specific = document.getElementById('addr_specific').value.trim();
                const lat = document.getElementById('addr_lat').value;
                const lng = document.getElementById('addr_lng').value;
                const type = document.getElementById('addr_type').value;
                const isDefault = document.getElementById('addr_default').checked ? 1 : 0;
                const method = document.getElementById('addr_location_method').value || 'map';
                const formatted = document.getElementById('addr_formatted').value || '';
                const provinceCode = document.getElementById('addr_province_code').value;
                const wardCode = document.getElementById('addr_ward_code').value;

                if (!fullname || !phone || !specific) {
                    if (window.FrontendAlert) window.FrontendAlert.error(
                        'Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ cụ thể.');
                    else alert('Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ cụ thể.');
                    return;
                }
                if (!provinceCode || !wardCode) {
                    if (!provinceCode) showAreaError('province', 'Vui lòng chọn Tỉnh/Thành phố.');
                    if (!wardCode) showAreaError('ward', 'Vui lòng chọn Phường/Xã.');
                    return;
                }
                if (method !== 'manual' && !lat) {
                    if (window.FrontendAlert) window.FrontendAlert.error(
                        'Vui lòng xác định vị trí trên bản đồ (bấm "Xác nhận vị trí này") hoặc bấm "Lấy vị trí hiện tại".');
                    else alert(
                        'Vui lòng xác định vị trí trên bản đồ (bấm "Xác nhận vị trí này") hoặc bấm "Lấy vị trí hiện tại".');
                    return;
                }

                const payload = {
                    fullname,
                    phone,
                    specific_address: specific,
                    province_code: parseInt(provinceCode, 10),
                    ward_code: parseInt(wardCode, 10),
                    latitude: lat || null,
                    longitude: lng || null,
                    location_method: method,
                    formatted_address: formatted,
                    type,
                    is_default: isDefault,
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                };

                const url = id ? `/profile/address/${id}` : '/profile/address';

                const stateObj = {
                    selected_address_id: id || 'new',
                    weather: document.getElementById('weather_select') ? document.getElementById('weather_select').value :
                        null,
                    is_peak_hour: document.getElementById('peak_hour_select') ? document.getElementById('peak_hour_select')
                        .value : null
                };
                localStorage.setItem('checkout_address_state', JSON.stringify(stateObj));

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';
                Object.keys(payload).forEach(function(key) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = payload[key] === null || payload[key] === undefined ? '' : payload[key];
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            }

            window.getCurrentLocation = getCurrentLocation;
            window.setAddrType = setAddrType;
            window.closeAddressModal = closeAddressModal;
            window.saveAddress = saveAddress;
            window.onProvinceChange = onProvinceChange;
            window.onWardChange = onWardChange;
            window.setLocationMethod = setLocationMethod;
            window.confirmMapLocation = confirmMapLocation;
            window.updateSaveButtonState = updateSaveButtonState;
            window.openAreaSearch = openAreaSearch;
            window.closeAreaSearch = closeAreaSearch;
            window.toggleAreaSearch = toggleAreaSearch;
            window.filterAreaOptions = filterAreaOptions;
            window.handleAreaSearchKeydown = handleAreaSearchKeydown;

            document.addEventListener('DOMContentLoaded', function() {
                ['addr_fullname', 'addr_phone', 'addr_specific'].forEach(function(elId) {
                    const el = document.getElementById(elId);
                    if (el) el.addEventListener('input', updateSaveButtonState);
                });
                const specificEl = document.getElementById('addr_specific');
                if (specificEl) {
                    specificEl.addEventListener('input', function() {
                        specificUserEdited = true;
                        scheduleManualForwardGeocode();
                    });
                }
                const provSel = document.getElementById('addr_province_select');
                if (provSel) provSel.addEventListener('change', function() {
                    onProvinceChange();
                    updateSaveButtonState();
                });
                const wardSel = document.getElementById('addr_ward_select');
                if (wardSel) wardSel.addEventListener('change', function() {
                    onWardChange();
                    updateSaveButtonState();
                });

                document.addEventListener('click', function(event) {
                    ['province', 'ward'].forEach(function(which) {
                        const root = document.querySelector(`[data-area-search-root="${which}"]`);
                        if (root && !root.contains(event.target)) closeAreaSearch(which);
                    });
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const priceSummaryEl = document.getElementById('price-summary');
                if (!priceSummaryEl) return;

                const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
                let discount = 0;
                let pointsDiscount = 0;

                const shippingDistanceText = document.getElementById('summary-shipping-distance-text');
                const weatherFeeText = document.getElementById('summary-weather-fee-text');

                const discountRow = document.getElementById('summary-discount-row');
                const discountText = document.getElementById('summary-discount-text');
                const totalText = document.getElementById('summary-total-text');
                const couponInput = document.getElementById('coupon_code_input');
                const applyCouponBtn = document.getElementById('apply_coupon_btn');
                const couponMessage = document.getElementById('coupon_message');
                const orderBtn = document.getElementById('order-submit-btn');

                const pointsInput = document.getElementById('points_to_redeem_input');
                const applyPointsBtn = document.getElementById('apply_points_btn');
                const pointsMessage = document.getElementById('points_message');
                const maxRedeemablePointsText = document.getElementById('max-redeemable-points');

                const config = window.checkoutConfig || {
                    shippingBaseFee: 15000,
                    shippingFeePerKm: 5000,
                    shippingMaxDistanceKm: 15,
                    freeShippingMinimum: 150000
                };

                const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
                const checkoutForm = document.getElementById('checkout-form');

                if (checkoutForm) {
                    checkoutForm.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                            e.preventDefault();
                        }
                    });
                }

                // Đổi đích gửi form theo phương thức thanh toán khách chọn
                function updateFormAction() {
                    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                    if (selectedPayment && checkoutForm) {
                        if (selectedPayment.value === 'vnpay' && checkoutForm.dataset.vnpayUrl) {
                            checkoutForm.action = checkoutForm.dataset.vnpayUrl;
                        } else if (checkoutForm.dataset.codUrl) {
                            checkoutForm.action = checkoutForm.dataset.codUrl;
                        }
                    }
                }

                paymentRadios.forEach(radio => {
                    radio.addEventListener('change', () => {
                        updateBorders(paymentRadios);
                        updateFormAction();
                        if (orderBtn) {
                            if (radio.value === 'vnpay') {
                                orderBtn.innerText = 'Chuyển khoản (VNPay)';
                            } else {
                                orderBtn.innerText = 'Đặt hàng (COD)';
                            }
                        }
                    });
                });

                updateFormAction();

                // Cập nhật màu viền các ô theo trạng thái hợp lệ
                function updateBorders(radios) {
                    radios.forEach(radio => {
                        const label = radio.closest('label');
                        if (label) {
                            if (radio.checked) {
                                label.classList.add('border-2', 'border-primary', 'bg-primary-container/10');
                                label.classList.remove('border-outline-variant');
                            } else {
                                label.classList.remove('border-2', 'border-primary', 'bg-primary-container/10');
                                label.classList.add('border-outline-variant');
                            }
                        }
                    });
                }

                updateBorders(paymentRadios);

                let discountPercent = 0;
                let maxDiscountAmount = 0;
                let couponScope = 'order';

                // Vẽ lại danh sách quà tặng theo mã đang chọn. Mỗi đơn
                const comboGiftList = document.getElementById('combo-gift-list');

                // Hiện danh sách quà tặng kèm khi khách áp mã combo
                function renderComboGifts(gifts) {
                    if (!comboGiftList) return;
                    comboGiftList.innerHTML = '';
                    (gifts || []).forEach(gift => {
                        const row = document.createElement('div');
                        row.className = 'flex gap-4 py-4 first:pt-0 last:pb-0 bg-emerald-50/40';
                        const name = document.createElement('h4');
                        name.className = 'font-bold text-on-surface text-sm truncate';
                        name.innerText = gift.name;
                        const qty = document.createElement('p');
                        qty.className = 'text-xs text-on-surface-variant mt-0.5 font-medium';
                        qty.innerText = 'x' + gift.quantity;
                        const badge = document.createElement('span');
                        badge.className =
                            'inline-block mt-1 px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold';
                        badge.innerText = '🎁 Quà tặng';
                        const info = document.createElement('div');
                        info.className = 'flex-1 min-w-0';
                        info.append(name, qty, badge);
                        const price = document.createElement('div');
                        price.className = 'text-right flex-shrink-0 font-bold text-emerald-700 text-sm';
                        price.innerText = 'Miễn phí';
                        row.append(info, price);
                        comboGiftList.appendChild(row);
                    });
                }

                document.querySelectorAll('.coupon-chip').forEach(chip => {
                    chip.addEventListener('click', () => {
                        const code = chip.dataset.code;
                        if (!code || !couponInput) return;

                        couponInput.value = code;

                        document.querySelectorAll('.coupon-chip').forEach(c => {
                            c.classList.remove('!bg-primary', '!border-primary', '!text-white',
                                'ring-2', 'ring-primary/30');
                        });
                        chip.classList.add('!bg-primary', '!border-primary', '!text-white', 'ring-2',
                            'ring-primary/30');

                        if (applyCouponBtn) applyCouponBtn.click();
                    });
                });

                if (applyCouponBtn && couponInput) {
                    couponInput.addEventListener('input', () => {
                        document.querySelectorAll('.coupon-chip').forEach(c => {
                            c.classList.remove('!bg-primary', '!border-primary', '!text-white',
                                'ring-2', 'ring-primary/30');
                        });
                    });

                    applyCouponBtn.addEventListener('click', () => {
                        const code = couponInput.value.trim().toUpperCase();

                        if (code === '') {
                            discount = 0;
                            discountPercent = 0;
                            maxDiscountAmount = 0;
                            couponScope = 'order';
                            couponMessage.innerText = '';
                            discountRow.classList.add('hidden');
                            document.getElementById('hidden_coupon_code').value = '';
                            renderComboGifts([]);
                            calculateTotal();
                            return;
                        }

                        const token = document.querySelector('input[name="_token"]').value;
                        const currentSubtotal = subtotal;

                        couponMessage.innerText = 'Đang kiểm tra...';
                        couponMessage.className = 'text-xs text-on-surface-variant font-medium mt-1';

                        fetch('/checkout/validate-coupon', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: JSON.stringify({
                                    coupon_code: code,
                                    subtotal: currentSubtotal
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.valid) {
                                    discount = parseFloat(data.discount_amount) || 0;
                                    couponScope = data.scope || 'order';

                                    if (data.discount_type === 'percent') {
                                        discountPercent = data.discount_value;
                                        maxDiscountAmount = data.max_discount_amount ? parseFloat(data
                                            .max_discount_amount) : 0;
                                    } else {
                                        discountPercent = 0;
                                        maxDiscountAmount = 0;
                                    }

                                    // Quan TRỌNG: đây là ô thật sự được gửi lên server lúc
                                    document.getElementById('hidden_coupon_code').value = code;

                                    couponMessage.innerHTML = renderCouponSuccessInfo(data, code);
                                    couponMessage.className = 'text-xs mt-1.5';
                                    renderComboGifts(data.gifts);
                                } else {
                                    discount = 0;
                                    discountPercent = 0;
                                    couponScope = 'order';
                                    // Mã vừa gõ bị từ chối -> phải xóa luôn ô ẩn, nếu không
                                    document.getElementById('hidden_coupon_code').value = '';
                                    couponMessage.innerText = data.message;
                                    couponMessage.className = 'text-xs text-error font-bold mt-1';
                                    renderComboGifts([]);
                                }

                                calculateTotal();
                            })
                            .catch(err => {
                                console.error('Lỗi kiểm tra mã giảm giá:', err);
                                discount = 0;
                                discountPercent = 0;
                                maxDiscountAmount = 0;
                                document.getElementById('hidden_coupon_code').value = '';
                                couponMessage.innerText = 'Có lỗi xảy ra khi kiểm tra mã.';
                                couponMessage.className = 'text-xs text-error font-bold mt-1';
                                renderComboGifts([]);
                                calculateTotal();
                            });
                    });
                }

                if (pointsInput) {
                    const pointsBalance = parseInt(pointsInput.dataset.pointsBalance || 0);
                    const pointValue = parseFloat(pointsInput.dataset.pointValue || 1000);
                    const maxRedeemPercent = parseFloat(pointsInput.dataset.maxRedeemPercent || 100);
                    const minPointsToRedeem = parseInt(pointsInput.dataset.minPointsToRedeem || 10);

                    const maxDiscountMoney = subtotal * (maxRedeemPercent / 100);
                    const maxPointsRedeemable = Math.min(pointsBalance, Math.floor(maxDiscountMoney / pointValue));

                    if (maxRedeemablePointsText) {
                        maxRedeemablePointsText.innerText = maxPointsRedeemable;
                    }

                    if (applyPointsBtn) {
                        applyPointsBtn.addEventListener('click', () => {
                            const enteredPoints = parseInt(pointsInput.value || 0);
                            if (isNaN(enteredPoints) || enteredPoints < 0) {
                                pointsDiscount = 0;
                                pointsMessage.innerText = 'Số điểm không hợp lệ.';
                                pointsMessage.className = 'text-xs text-error font-bold mt-1';
                                calculateTotal();
                                return;
                            }

                            if (enteredPoints > pointsBalance) {
                                pointsDiscount = 0;
                                pointsMessage.innerText = 'Số điểm nhập vượt quá số dư hiện có.';
                                pointsMessage.className = 'text-xs text-error font-bold mt-1';
                                calculateTotal();
                                return;
                            }

                            if (enteredPoints > maxPointsRedeemable) {
                                pointsDiscount = 0;
                                pointsMessage.innerText = 'Số điểm vượt quá giới hạn tối đa cho đơn này (' +
                                    maxPointsRedeemable + ' điểm).';
                                pointsMessage.className = 'text-xs text-error font-bold mt-1';
                                calculateTotal();
                                return;
                            }

                            if (enteredPoints > 0 && enteredPoints < minPointsToRedeem) {
                                pointsDiscount = 0;
                                pointsMessage.innerText = 'Số điểm tối thiểu để được đổi là ' +
                                    minPointsToRedeem + ' điểm.';
                                pointsMessage.className = 'text-xs text-error font-bold mt-1';
                                calculateTotal();
                                return;
                            }

                            pointsDiscount = enteredPoints * pointValue;
                            if (enteredPoints > 0) {
                                pointsMessage.innerText = 'Áp dụng đổi ' + enteredPoints +
                                    ' điểm thành công (Giảm -' + pointsDiscount.toLocaleString('vi-VN') + 'đ)';
                                pointsMessage.className = 'text-xs text-primary font-bold mt-1';
                            } else {
                                pointsMessage.innerText = '';
                            }
                            calculateTotal();
                        });
                    }
                }

                // Cộng lại tổng tiền cuối cùng mỗi khi có thay đổi
                function calculateTotal() {
                    const hiddenDist = document.getElementById('hidden_distance_km');
                    const distanceKm = hiddenDist ? parseFloat(hiddenDist.value) : 2.5;

                    const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
                    const weatherFee = hiddenWeatherFee ? parseFloat(hiddenWeatherFee.value) : 0;

                    const freeShipThreshold = config.freeShippingMinimum;
                    const freeShip = subtotal >= freeShipThreshold;

                    let distanceFee = 0;
                    if (!freeShip) {
                        if (distanceKm <= 2) {
                            distanceFee = config.shippingBaseFee;
                        } else {
                            distanceFee = config.shippingBaseFee + (distanceKm - 2) * config.shippingFeePerKm;
                        }
                    }
                    distanceFee = Math.round(distanceFee);

                    if (discountPercent > 0 && couponScope === 'order') {
                        discount = Math.round(subtotal * (discountPercent / 100));
                        if (maxDiscountAmount && maxDiscountAmount > 0 && discount > maxDiscountAmount) {
                            discount = maxDiscountAmount;
                        }
                        if (discount > subtotal) discount = subtotal;
                    }

                    const totalDiscount = discount + pointsDiscount;

                    const total = Math.max(0, subtotal + distanceFee + (freeShip ? 0 : weatherFee) - totalDiscount);

                    const distValEl = document.getElementById('summary-distance-km-val');
                    if (distValEl) distValEl.innerText = distanceKm.toFixed(1);

                    const freeShipRow = document.getElementById('summary-free-ship-row');
                    const shippingDistRow = document.getElementById('summary-shipping-distance-row');

                    if (distanceKm > config.shippingMaxDistanceKm) {
                        if (orderBtn) {
                            orderBtn.disabled = true;
                            orderBtn.innerText = 'Chỉ giao trong ' + config.shippingMaxDistanceKm + 'km';
                            orderBtn.classList.add('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600');
                            orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary',
                                'bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                        }
                        if (shippingDistanceText) shippingDistanceText.innerHTML =
                            '<span class="text-error font-bold">Không hỗ trợ giao quá ' + config
                            .shippingMaxDistanceKm + 'km</span>';
                        if (totalText) totalText.innerHTML = '<span class="text-error font-bold">---</span>';

                        const weatherRowOver = document.getElementById('summary-weather-fee-row');
                        if (weatherRowOver) weatherRowOver.classList.add('hidden');
                        if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
                    } else {
                        if (orderBtn) {
                            const isClosed = document.getElementById('order-submit-btn').dataset.closed === '1';
                            if (!isClosed) {
                                orderBtn.disabled = false;
                                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                                orderBtn.classList.remove('opacity-80', 'cursor-not-allowed', 'bg-gray-300',
                                    'text-gray-600', 'opacity-50');
                                if (selectedPayment && selectedPayment.value === 'vnpay') {
                                    orderBtn.innerText = 'Chuyển khoản (VNPay)';
                                    orderBtn.classList.add('bg-[#003c71]', 'hover:bg-[#002e57]', 'text-white');
                                    orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]',
                                        'text-on-primary', 'bg-[#ae2070]', 'hover:bg-[#8b1a5a]');
                                } else {
                                    orderBtn.innerText = 'Đặt hàng (COD)';
                                    orderBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]',
                                        'text-on-primary');
                                    orderBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white',
                                        'bg-[#003c71]', 'hover:bg-[#002e57]');
                                }
                            }
                        }

                        if (freeShip) {
                            if (freeShipRow) freeShipRow.classList.remove('hidden');
                            if (shippingDistRow) shippingDistRow.classList.add('hidden');
                            if (shippingDistanceText) shippingDistanceText.innerText = '0đ';

                            const weatherRow = document.getElementById('summary-weather-fee-row');
                            if (weatherRow) weatherRow.classList.add('hidden');
                            if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
                        } else {
                            if (freeShipRow) freeShipRow.classList.add('hidden');
                            if (shippingDistRow) shippingDistRow.classList.remove('hidden');
                            if (shippingDistanceText) shippingDistanceText.innerText = distanceFee.toLocaleString(
                                'vi-VN') + 'đ';

                            const weatherRow = document.getElementById('summary-weather-fee-row');
                            if (weatherFee > 0) {
                                if (weatherRow) weatherRow.classList.remove('hidden');
                                const weatherText = document.getElementById('summary-weather-fee-text');
                                if (weatherText) weatherText.innerText = '+' + weatherFee.toLocaleString('vi-VN') + 'đ';
                            } else {
                                if (weatherRow) weatherRow.classList.add('hidden');
                            }
                        }

                        if (totalText) totalText.innerText = total.toLocaleString('vi-VN') + 'đ';
                    }

                    if (totalDiscount > 0) {
                        if (discountRow) discountRow.classList.remove('hidden');
                        if (discountText) discountText.innerText = '-' + totalDiscount.toLocaleString('vi-VN') + 'đ';
                    } else {
                        if (discountRow) discountRow.classList.add('hidden');
                    }
                }

                const changeAddressBtn = document.getElementById('change-address-btn');
                const addressListPanel = document.getElementById('address-list-panel');
                if (changeAddressBtn && addressListPanel) {
                    changeAddressBtn.addEventListener('click', () => {
                        addressListPanel.classList.toggle('hidden');
                    });
                }

                const addressRadios = document.querySelectorAll('input[name="address_selector"]');
                const hiddenAddressIdInput = document.getElementById('selected_address_id');
                const activeAddressName = document.getElementById('active-address-name');
                const activeAddressPhone = document.getElementById('active-address-phone');
                const activeAddressDetails = document.getElementById('active-address-details');

                // Gọi server tính khoảng cách giao tới địa chỉ đang chọn để ra phí ship
                function updateDistanceForAddress(addressId) {
                    if (!addressId) return;

                    const distanceValText = document.getElementById('distance-calc-desc');
                    if (distanceValText) distanceValText.innerText = 'Đang tính toán...';

                    fetch(`/checkout/distance?address_id=${addressId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const distanceKm = data.distance_km;
                                const hiddenDist = document.getElementById('hidden_distance_km');
                                if (hiddenDist) {
                                    hiddenDist.value = distanceKm;
                                }

                                updateWeatherFeeForAddress(addressId, distanceKm);
                                calculateTotal();

                                const calcDesc = document.getElementById('distance-calc-desc');
                                if (calcDesc) {
                                    const fmtBase = config.shippingBaseFee.toLocaleString('vi-VN');
                                    const fmtPerKm = config.shippingFeePerKm.toLocaleString('vi-VN');
                                    const text =
                                        `Phí vận chuyển: ${fmtBase}đ (2km đầu) + ${fmtPerKm}đ / km tiếp theo.`;
                                    if (data.is_mock) {
                                        calcDesc.innerHTML =
                                            `<span style="color:#d97706; font-weight: 600;">⚠️ ${data.message}</span><br>${text}`;
                                    } else {
                                        calcDesc.innerHTML =
                                            `<span style="color:#15803d; font-weight: 600;">✅ Khoảng cách được tính thực tế bằng Geoapify Routing API.</span><br>${text}`;
                                    }
                                }
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching distance:', err);
                            const errValText = document.getElementById('distance-calc-desc');
                            if (errValText) errValText.innerText = 'Lỗi kết nối';
                        });
                }

                // Lấy phụ thu thời tiết hiện tại cho địa chỉ đó
                function updateWeatherFeeForAddress(addressId, distanceKm) {
                    if (!addressId) return;

                    fetch(
                            `/checkout/weather-fee?address_id=${addressId}&distance_km=${distanceKm}&subtotal=${subtotal}`
                            )
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const fee = data.fee;
                                const condition = data.condition;

                                const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
                                if (hiddenWeatherFee) hiddenWeatherFee.value = fee;

                                const conditionValEl = document.getElementById('summary-weather-condition-val');
                                if (conditionValEl) conditionValEl.innerText = condition;

                                calculateTotal();
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching weather fee:', err);
                        });
                }

                calculateTotal();
                if (hiddenAddressIdInput && hiddenAddressIdInput.value) {
                    updateDistanceForAddress(hiddenAddressIdInput.value);
                }

                addressRadios.forEach(radio => {
                    radio.addEventListener('change', () => {
                        if (hiddenAddressIdInput) hiddenAddressIdInput.value = radio.value;
                        if (activeAddressName) activeAddressName.innerText = radio.dataset.fullname;
                        if (activeAddressPhone) activeAddressPhone.innerText = radio.dataset.phone;
                        if (activeAddressDetails) activeAddressDetails.innerText = radio.dataset
                            .address;

                        updateDistanceForAddress(radio.value);

                        addressRadios.forEach(r => {
                            const card = r.closest('.address-card');
                            if (card) {
                                if (r.checked) {
                                    card.classList.add('border-primary',
                                        'bg-primary-container/5');
                                    card.classList.remove('border-outline-variant');
                                } else {
                                    card.classList.remove('border-primary',
                                        'bg-primary-container/5');
                                    card.classList.add('border-outline-variant');
                                }
                            }
                        });
                    });
                });
            });

            // Xóa một địa chỉ ngay tại trang thanh toán
            function deleteAddressCheckout(id) {
                if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/profile/address/' + id + '/delete';
                form.style.display = 'none';

                const tokenInput = document.createElement('input');
                tokenInput.type = '_token';
                tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.appendChild(tokenInput);

                document.body.appendChild(form);
                form.submit();
            }
        </script>
    @endsection
