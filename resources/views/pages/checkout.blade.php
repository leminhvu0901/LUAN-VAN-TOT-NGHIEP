@extends('layouts.app')

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

    <div class="max-w-6xl mx-auto px-4 md:px-8 mt-8">
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
                    <span class="font-bold text-sm block">Cửa hàng hiện đã đóng cửa!</span>
                    <span class="text-xs mt-1 block text-[#5d4037]">Giờ hoạt động của chúng tôi là từ 07:00 đến 19:00 hàng ngày. Quý khách hiện tại có thể tham khảo giỏ hàng nhưng không thể đặt hàng mới vào lúc này.</span>
                </div>
            </div>
        @endif

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form"
              data-cod-url="{{ route('checkout.store') }}"
              data-momo-url="{{ route('momo.pay') }}">
            @csrf
            <input type="hidden" name="distance_km" id="hidden_distance_km" value="2.5">
            <input type="hidden" name="weather_fee" id="hidden_weather_fee" value="0">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Shipping Address & Method & Payment (2 columns on desktop) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Shipping Address Section -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">location_on</span>
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
                                        data-longitude="{{ $defaultAddress->longitude }}">
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
                                                data-longitude="{{ $addr->longitude }}">
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
                                
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Left: Form Inputs -->
                                    <div class="space-y-4">
                                        <input type="hidden" id="addr_id">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Họ và tên</label>
                                                <input type="text" id="addr_fullname" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Nhập họ tên">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-on-surface-variant ml-1">Số điện thoại</label>
                                                <input type="tel" id="addr_phone" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Nhập SĐT">
                                            </div>
                                        </div>

                                        <!-- Location Picker -->
                                        <div class="space-y-1 relative" id="locPickerContainer">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Khu vực</label>
                                            <div class="relative">
                                                <input type="text" id="locPickerInputText" readonly class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm cursor-pointer hover:bg-surface-container-low focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Chọn Tỉnh/Thành, Phường/Xã" onclick="toggleLocPanel()">
                                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                                            </div>
                                            
                                            <!-- Dropdown Panel -->
                                            <div id="locPanel" class="loc-panel absolute z-50 w-full mt-1 bg-white border border-outline-variant rounded-xl shadow-lg overflow-hidden" style="display:none; top: 100%;">
                                                <div class="flex border-b border-outline-variant bg-surface-container-lowest">
                                                    <button type="button" id="tab_province" onclick="switchLocTab('province')" class="flex-1 py-3 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">Tỉnh/Thành</button>
                                                    <button type="button" id="tab_ward" onclick="switchLocTab('ward')" class="flex-1 py-3 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">Phường/Xã</button>
                                                </div>
                                                <div class="p-2 border-b border-outline-variant bg-surface-container-lowest">
                                                    <input type="text" id="locSearchInput" placeholder="Tìm kiếm..." class="w-full px-3 py-2 text-sm border border-outline-variant rounded-lg outline-none focus:ring-2 focus:ring-primary" oninput="filterLocItems(this.value)">
                                                </div>
                                                <div id="locList" class="max-h-60 overflow-y-auto text-sm"></div>
                                            </div>
                                            
                                            <input type="hidden" id="addr_province">
                                            <input type="hidden" id="addr_district">
                                            <input type="hidden" id="addr_ward">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Địa chỉ cụ thể <span class="text-error">*</span></label>
                                            <textarea id="addr_specific" rows="2" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition-all resize-none" placeholder="Số nhà, tên đường..."></textarea>
                                        </div>

                                        <!-- Types & Default -->
                                        <div class="space-y-3 pt-2">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Loại địa chỉ</label>
                                            <div class="flex gap-3">
                                                <button type="button" id="btnTypeHome" onclick="setAddrType('home')" class="flex-1 py-2 rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                    <span class="material-symbols-outlined text-[18px]">home</span> Nhà riêng
                                                </button>
                                                <button type="button" id="btnTypeOffice" onclick="setAddrType('office')" class="flex-1 py-2 rounded-lg border text-sm font-bold transition-all flex items-center justify-center gap-2">
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

                                    <!-- Right: Map -->
                                    <div class="flex flex-col h-full space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-bold text-on-surface-variant ml-1">Vị trí trên bản đồ</label>
                                            <button type="button" onclick="getCurrentLocation(this)" class="text-primary hover:text-green-800 font-bold text-xs flex items-center gap-1 bg-primary/10 px-3 py-1.5 rounded-full transition-colors active:scale-95">
                                                <span class="material-symbols-outlined text-[16px]">my_location</span>
                                                <span id="gps-btn-text">Định vị GPS</span>
                                            </button>
                                        </div>
                                        <div id="leafletMap" class="w-full flex-1 min-h-[250px] rounded-xl border border-outline-variant z-10"></div>
                                        <input type="hidden" id="addr_lat">
                                        <input type="hidden" id="addr_lng">
                                        
                                        <!-- Actions -->
                                        <div class="grid grid-cols-2 gap-3 pt-4">
                                            <button type="button" onclick="closeAddressModal()" class="py-3 rounded-xl border border-outline-variant text-on-surface font-bold hover:bg-surface-container-low transition-colors">
                                                Hủy
                                            </button>
                                            <button type="button" onclick="saveAddress()" class="py-3 rounded-xl bg-primary text-white font-bold hover:opacity-90 transition-opacity shadow-sm">
                                                Hoàn thành
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    </section>

                    <!-- 3. Payment Method Section -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">payments</span>
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Phương thức thanh toán</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Cash On Delivery (COD) -->
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

                            <!-- MoMo -->
                            <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                <input type="radio" name="payment_method" value="momo" class="text-primary focus:ring-primary">
                                <div class="flex items-center gap-3">
                                    <img src="{{ asset('images/payment/momo.svg') }}" alt="MoMo" class="w-8 h-8 rounded-lg" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                                    <div>
                                        <span class="block font-bold text-on-surface">Ví điện tử MoMo</span>
                                        <span class="text-xs text-on-surface-variant">Liên kết thanh toán online</span>
                                    </div>
                                </div>
                            </label>
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

                        <!-- Product List -->
                        <div class="divide-y divide-outline-variant/50 max-h-96 overflow-y-auto pr-1">
                            @foreach($items as $item)
                                <div class="flex gap-4 py-4 first:pt-0 last:pb-0">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-surface-container flex-shrink-0 border border-outline-variant/60">
                                        <img src="{{ asset('images/' . $item->image) }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'" class="w-full h-full object-cover">
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

                    <!-- Order Price Breakdown Card -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="border-b border-outline-variant pb-4 mb-4">
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Chi tiết thanh toán</h2>
                        </div>

                        <div class="space-y-3" id="price-summary" data-subtotal="{{ $subtotal }}">
                            <div class="flex justify-between text-sm text-on-surface-variant font-medium">
                                <span>Tạm tính (Sản phẩm)</span>
                                <span id="summary-subtotal-text">{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between text-sm text-on-surface-variant font-medium" id="summary-shipping-distance-row">
                                <span>Phí giao hàng (<span id="summary-distance-km-val">0.0</span> km × 3.000đ)</span>
                                <span id="summary-shipping-distance-text">0đ</span>
                            </div>
                            <div class="flex justify-between text-sm text-primary font-bold hidden" id="summary-free-ship-row">
                                <span>🎉 Miễn phí giao hàng (Đơn ≥ 150.000đ)</span>
                                
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
                                Cửa hàng đóng cửa (07:00 - 19:00)
                            </button>
                        @elseif(!$addresses->isEmpty())
                            <button type="submit" id="order-submit-btn" class="w-full bg-primary-container text-on-primary hover:bg-[#008f00] font-bold text-center py-3.5 rounded-xl shadow-sm hover:shadow-md transition-all active:scale-98 mt-6">
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
// ─── Xử lý chuyển đổi phương thức thanh toán (COD ↔ MoMo) ────────────────
(function () {
    const form       = document.getElementById('checkout-form');
    const submitBtn  = document.getElementById('order-submit-btn');
    const submitText = document.getElementById('submit-btn-text');
    if (!form || !submitBtn || !submitText) return;

    const codUrl  = form.dataset.codUrl;
    const momoUrl = form.dataset.momoUrl;

    function updateFormByPayment(method) {
        if (method === 'momo') {
            form.action = momoUrl;
            submitText.textContent = '💳 Thanh toán qua MoMo';
            submitBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]');
            submitBtn.classList.add('bg-[#ae2070]', 'hover:bg-[#8b1a5a]');
        } else {
            form.action = codUrl;
            submitText.textContent = 'Đặt hàng (COD)';
            submitBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]');
            submitBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]');
        }
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateFormByPayment(this.value);
        });
    });

    // Khởi tạo theo lựa chọn mặc định
    const defaultChecked = document.querySelector('input[name="payment_method"]:checked');
    if (defaultChecked) updateFormByPayment(defaultChecked.value);
})();
</script>

<script>
// ----- Location & Address Modal State -----
let locState = { province: null, ward: null, province_name: '', ward_name: '', currentTab: 'province' };

async function fetchProvinces() {
    renderLocLoading();
    try {
        const res = await fetch('https://provinces.open-api.vn/api/v2/p/');
        const data = await res.json();
        renderLocItems(data, 'province');
    } catch (e) { console.error(e); }
}

async function fetchWards(provinceCode) {
    renderLocLoading();
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/v2/p/${provinceCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.wards, 'ward');
    } catch (e) { console.error(e); }
}

function renderLocLoading() {
    const list = document.getElementById('locList');
    if (list) list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Đang tải...</div>';
}

let currentLocItems = [];
let currentLocType = '';

function renderLocItems(items, type) {
    currentLocItems = items;
    currentLocType = type;
    displayLocItems(items, type);
}

function displayLocItems(items, type) {
    const list = document.getElementById('locList');
    if (!list) return;
    list.innerHTML = '';
    if (!items || items.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Không có dữ liệu</div>';
        return;
    }

    let sortedItems = items;
    if (type === 'ward') {
        sortedItems = [...items].sort((a, b) => {
            const aIsPhuong = a.name.toLowerCase().startsWith('phường');
            const bIsPhuong = b.name.toLowerCase().startsWith('phường');
            const aIsXa = a.name.toLowerCase().startsWith('xã');
            const bIsXa = b.name.toLowerCase().startsWith('xã');
            
            if (aIsPhuong && !bIsPhuong) return -1;
            if (!aIsPhuong && bIsPhuong) return 1;
            if (aIsXa && !bIsXa) return -1;
            if (!aIsXa && bIsXa) return 1;
            return a.name.localeCompare(b.name);
        });
    }

    sortedItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-surface-container-lowest cursor-pointer transition-colors border-b border-outline-variant/50 last:border-0 loc-item';
        div.textContent = item.name;
        div.onclick = () => selectLocItem(item, type);
        list.appendChild(div);
    });
}

function removeAccents(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

function filterLocItems(keyword) {
    if (!keyword) {
        displayLocItems(currentLocItems, currentLocType);
        return;
    }
    const kw = removeAccents(keyword);
    const filtered = currentLocItems.filter(item => removeAccents(item.name).includes(kw));
    displayLocItems(filtered, currentLocType);
}

function selectLocItem(item, type) {
    if (type === 'province') {
        locState.province = item.code; locState.province_name = item.name;
        locState.ward = null; locState.ward_name = '';
        document.getElementById('addr_province').value = item.name;
        document.getElementById('addr_district').value = '';
        document.getElementById('addr_ward').value = '';
        switchLocTab('ward');
    } else if (type === 'ward') {
        locState.ward = item.code; locState.ward_name = item.name;
        document.getElementById('addr_ward').value = item.name;
        // Trick backend to pass validation/shipping by duplicating ward into district
        document.getElementById('addr_district').value = item.name;
        updateLocPickerText();
        document.getElementById('locPanel').style.display = 'none';
        geocodeAndUpdateMap();
    }
}

function geocodeAndUpdateMap() {
    const province = document.getElementById('addr_province').value;
    const district = document.getElementById('addr_district').value;
    const ward = document.getElementById('addr_ward').value;
    const specific = document.getElementById('addr_specific').value;

    let parts = [];
    if (specific) parts.push(specific);
    if (ward) parts.push(ward);
    if (province) parts.push(province);

    if (parts.length > 0) {
        const addressStr = parts.join(', ');
        geocodeAddress(addressStr).then(coords => {
            if (coords) {
                initLeafletMap(coords.lat, coords.lng);
            }
        });
    }
}

function updateLocPickerText() {
    const inputEl = document.getElementById('locPickerInputText');
    if(!inputEl) return;
    if (locState.province_name && locState.ward_name) {
        inputEl.value = `${locState.province_name}, ${locState.ward_name}`;
    } else if (locState.province_name) {
        inputEl.value = `${locState.province_name}`;
    } else {
        inputEl.value = '';
    }
}

function switchLocTab(tab) {
    locState.currentTab = tab;
    
    ['province', 'ward'].forEach(t => {
        const el = document.getElementById(`tab_${t}`);
        if(el) {
            if(t === tab) el.classList.add('bg-surface-container-low', 'text-primary');
            else el.classList.remove('bg-surface-container-low', 'text-primary');
        }
    });

    if (tab === 'province') {
        document.getElementById('locSearchInput').value = '';
        fetchProvinces();
    } else if (tab === 'ward') {
        document.getElementById('locSearchInput').value = '';
        if (locState.province) fetchWards(locState.province);
        else document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Tỉnh/Thành Phố trước</div>';
    }
}

function toggleLocPanel() {
    const panel = document.getElementById('locPanel');
    if (!panel) return;
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
    } else {
        panel.style.display = 'block';
        if (!locState.province) switchLocTab('province');
        else if (!locState.ward) switchLocTab('ward');
        else switchLocTab('province');
    }
}

// Click outside locPanel to close
document.addEventListener('click', function (e) {
    if (!document.body.contains(e.target)) return;
    const container = document.getElementById('locPickerContainer');
    if (container && !container.contains(e.target) && document.body.contains(e.target)) {
        const panel = document.getElementById('locPanel');
        if (panel) panel.style.display = 'none';
    }
});

let leafletMap = null;
let leafletMarker = null;

function initLeafletMap(lat, lng) {
    const defaultLat = lat || 10.73809;
    const defaultLng = lng || 106.67812;

    document.getElementById('addr_lat').value = defaultLat;
    document.getElementById('addr_lng').value = defaultLng;

    if (leafletMap) {
        leafletMap.setView([defaultLat, defaultLng], 15);
        if (leafletMarker) {
            leafletMarker.setLatLng([defaultLat, defaultLng]);
        } else {
            leafletMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(leafletMap);
            setupMarkerEvents(leafletMarker);
        }
        return;
    }

    leafletMap = L.map('leafletMap').setView([defaultLat, defaultLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(leafletMap);

    leafletMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(leafletMap);
    setupMarkerEvents(leafletMarker);

    leafletMap.on('click', function (e) {
        const newLat = e.latlng.lat;
        const newLng = e.latlng.lng;
        leafletMarker.setLatLng([newLat, newLng]);
        updateCoordinates(newLat, newLng);
        reverseGeocode(newLat, newLng);
    });
}

function setupMarkerEvents(marker) {
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
        reverseGeocode(position.lat, position.lng);
    });
}

function updateCoordinates(lat, lng) {
    document.getElementById('addr_lat').value = lat;
    document.getElementById('addr_lng').value = lng;
}

async function geocodeAddress(addressString) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addressString)}&addressdetails=1&limit=1&email=test@example.com`);
        const data = await res.json();
        if (data && data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
    } catch (e) {
        console.error("Geocoding error:", e);
    }
    return null;
}

async function reverseGeocode(lat, lng) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi&email=test@example.com`);
        const data = await res.json();
        if (data && data.address) {
            const addr = data.address;
            const province = addr.state || addr.province || addr.city || '';
            
            let district = addr.county || addr.city_district || addr.suburb || addr.town || '';
            if (!district && addr.city && addr.city !== province) {
                district = addr.city;
            }
            const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';
            
            let specific = data.display_name;
            if (addr.road) {
                specific = addr.road;
                if (addr.house_number) specific = addr.house_number + ' ' + specific;
            } else if (addr.village || addr.hamlet) {
                specific = addr.village || addr.hamlet;
            } else {
                specific = '';
            }

            document.getElementById('addr_province').value = province;
            
            // Combine Nominatim's district and ward into our single 'ward' field
            const combinedWard = [district, ward].filter(Boolean).join(', ');
            
            document.getElementById('addr_ward').value = combinedWard;
            // Trick backend again
            document.getElementById('addr_district').value = combinedWard;
            
            const currentSpecific = document.getElementById('addr_specific').value.trim();
            if (!currentSpecific || specific) {
                document.getElementById('addr_specific').value = specific || currentSpecific;
            }

            locState.province_name = province;
            locState.ward_name = combinedWard;
            locState.province = null;
            locState.ward = null;
            updateLocPickerText();
        }
    } catch (e) {
        console.error("Reverse geocoding error:", e);
    }
}

function openAddressModal(isEdit = false) {
    if (!isEdit) {
        document.getElementById('addressModalTitle').textContent = 'Thêm địa chỉ mới';
        document.getElementById('addr_id').value = '';
        document.getElementById('addr_fullname').value = '';
        document.getElementById('addr_phone').value = '';
        document.getElementById('addr_specific').value = '';
        document.getElementById('addr_province').value = '';
        document.getElementById('addr_district').value = '';
        document.getElementById('addr_ward').value = '';
        document.getElementById('addr_lat').value = '';
        document.getElementById('addr_lng').value = '';

        locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };
        updateLocPickerText();

        document.getElementById('locPickerContainer').style.display = 'block';
        document.getElementById('addr_specific').readOnly = false;

        setAddrType('home');
        document.getElementById('addr_default').checked = false;
    }
    
    // Show inline form, hide other blocks
    document.getElementById('addressModal').classList.remove('hidden');
    document.getElementById('address-info-block')?.classList.add('hidden');
    document.getElementById('address-action-buttons')?.classList.add('hidden');
    document.getElementById('address-list-panel')?.classList.add('hidden');
    document.getElementById('empty-address-block')?.classList.add('hidden');
    
    setTimeout(() => {
        const lat = document.getElementById('addr_lat').value;
        const lng = document.getElementById('addr_lng').value;
        if (lat && lng) {
            initLeafletMap(parseFloat(lat), parseFloat(lng));
        } else {
            initLeafletMap();
        }
        if (leafletMap) {
            leafletMap.invalidateSize();
        }
    }, 200);
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.add('hidden');
    document.getElementById('address-info-block')?.classList.remove('hidden');
    document.getElementById('address-action-buttons')?.classList.remove('hidden');
    document.getElementById('empty-address-block')?.classList.remove('hidden');
}

function setAddrType(type) {
    document.getElementById('addr_type').value = type;
    ['home', 'office'].forEach(t => {
        const btn = document.getElementById(t === 'home' ? 'btnTypeHome' : 'btnTypeOffice');
        if(btn) {
            if(t === type) {
                btn.classList.add('bg-primary/10', 'border-primary', 'text-primary');
                btn.classList.remove('bg-surface-container-lowest', 'border-outline-variant', 'text-on-surface-variant');
            } else {
                btn.classList.remove('bg-primary/10', 'border-primary', 'text-primary');
                btn.classList.add('bg-surface-container-lowest', 'border-outline-variant', 'text-on-surface-variant');
            }
        }
    });
}

function resetToManual() {
    document.getElementById('locPickerContainer').style.display = 'block';
    document.getElementById('addr_specific').readOnly = false;

    document.getElementById('addr_province').value = '';
    document.getElementById('addr_district').value = '';
    document.getElementById('addr_ward').value = '';
    document.getElementById('addr_specific').value = '';
    document.getElementById('addr_lat').value = '';
    document.getElementById('addr_lng').value = '';

    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };
    updateLocPickerText();
    
    initLeafletMap();
}

async function getCurrentLocation(btn) {
    const currentProvince = document.getElementById('addr_province').value;
    const currentSpecific = document.getElementById('addr_specific').value.trim();

    if (currentProvince || currentSpecific) {
        if (!confirm("Bạn đã nhập địa chỉ thủ công. Bạn có chắc muốn dùng địa chỉ GPS để thay thế không?")) return;
    }

    const textSpan = document.getElementById('gps-btn-text');
    const originalText = textSpan.innerText;

    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
        return;
    }

    btn.disabled = true;
    textSpan.innerText = "Đang lấy vị trí...";
    const originalBg = btn.style.backgroundColor;
    btn.style.backgroundColor = '#f3f4f6';
    btn.style.cursor = 'wait';

    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=vi&email=test@example.com`);
            const data = await res.json();

            if (data && data.display_name) {
                const addr = data.address || {};
                const province = addr.city || addr.state || addr.province || '';
                const district = addr.county || addr.city_district || addr.suburb || addr.town || '';
                const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

                document.getElementById('addr_province').value = province;
                document.getElementById('addr_district').value = district;
                document.getElementById('addr_ward').value = ward;
                
                let specific = data.display_name;
                if (addr.road) {
                    specific = addr.road;
                    if (addr.house_number) specific = addr.house_number + ' ' + specific;
                } else if (addr.village || addr.hamlet) {
                    specific = addr.village || addr.hamlet;
                } else {
                    specific = '';
                }
                document.getElementById('addr_specific').value = specific;

                locState.province_name = province;
                locState.district_name = district;
                locState.ward_name = ward;
                locState.province = null;
                locState.district = null;
                locState.ward = null;
                updateLocPickerText();

                // Initialize/update Leaflet map and marker
                initLeafletMap(lat, lon);
                if (leafletMap) leafletMap.invalidateSize();

                alert("Đã tự động điền địa chỉ dựa trên GPS!");
            } else {
                alert("Không thể chuyển đổi tọa độ thành địa chỉ.");
            }
        } catch (error) {
            console.error(error);
            alert("Có lỗi xảy ra khi gọi API bản đồ: " + (error.message || error));
        } finally {
            btn.disabled = false;
            textSpan.innerText = originalText;
            btn.style.backgroundColor = originalBg;
            btn.style.cursor = 'pointer';
        }
    }, (error) => {
        let msg = "Không thể lấy vị trí.";
        switch (error.code) {
            case error.PERMISSION_DENIED: msg = "Bạn đã từ chối quyền truy cập vị trí."; break;
            case error.POSITION_UNAVAILABLE: msg = "Thông vị trí không khả dụng."; break;
            case error.TIMEOUT: msg = "Yêu cầu lấy vị trí quá thời gian."; break;
        }
        alert(msg);
        btn.disabled = false;
        textSpan.innerText = originalText;
        btn.style.backgroundColor = originalBg;
        btn.style.cursor = 'pointer';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
}

async function saveAddress() {
    const id = document.getElementById('addr_id').value;
    const data = {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        fullname: document.getElementById('addr_fullname').value.trim(),
        phone: document.getElementById('addr_phone').value.trim(),
        province: document.getElementById('addr_province').value.trim(),
        district: document.getElementById('addr_district').value.trim(),
        ward: document.getElementById('addr_ward').value.trim(),
        specific_address: document.getElementById('addr_specific').value.trim(),
        type: document.getElementById('addr_type').value,
        is_default: document.getElementById('addr_default').checked ? 1 : 0,
        latitude: document.getElementById('addr_lat').value || null,
        longitude: document.getElementById('addr_lng').value || null
    };

    if (!data.fullname || !data.phone || !data.province || !data.district || !data.ward || !data.specific_address) {
        alert("Vui lòng điền đầy đủ thông tin.");
        return;
    }

    const url = id ? `/profile/address/${id}` : '/profile/address';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            // Save state to localStorage to restore after reload
            const state = {
                selected_address_id: json.id || id // use new ID or edited ID
            };
            localStorage.setItem('checkout_address_state', JSON.stringify(state));
            
            // Reload page to refresh blade address template lists
            window.location.reload();
        } else {
            alert(json.message || "Có lỗi xảy ra khi lưu địa chỉ.");
        }
    } catch (e) { 
        console.error(e);
        alert("Có lỗi xảy ra."); 
    }
}

// Bind helper function to window to expose to inline events
window.toggleLocPanel = toggleLocPanel;
window.switchLocTab = switchLocTab;
window.resetToManual = resetToManual;
window.getCurrentLocation = getCurrentLocation;
window.setAddrType = setAddrType;
window.closeAddressModal = closeAddressModal;
window.saveAddress = saveAddress;

document.addEventListener('DOMContentLoaded', function() {
    const priceSummaryEl = document.getElementById('price-summary');
    if (!priceSummaryEl) return;

    const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
    let discount = 0;


    const shippingBaseText = document.getElementById('summary-shipping-base-text');
    const shippingDistanceText = document.getElementById('summary-shipping-distance-text');
    const weatherFeeText = document.getElementById('summary-weather-fee-text');
    const peakHourFeeText = document.getElementById('summary-peak-hour-fee-text');

    const discountRow = document.getElementById('summary-discount-row');
    const discountText = document.getElementById('summary-discount-text');
    const totalText = document.getElementById('summary-total-text');
    const couponInput = document.getElementById('coupon_code_input');
    const applyCouponBtn = document.getElementById('apply_coupon_btn');
    const couponMessage = document.getElementById('coupon_message');
    const orderBtn = document.getElementById('order-submit-btn');

    // Simulator inputs
    const weatherSelect = document.getElementById('weather_select');
    const peakHourSelect = document.getElementById('peak_hour_select');

    // Auto Geocode on blur or enter
    const specificInput = document.getElementById('addr_specific');
    if (specificInput) {
        specificInput.addEventListener('blur', geocodeAndUpdateMap);
        specificInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent new line in textarea
                geocodeAndUpdateMap();
            }
        });
    }

    // Setup Edit / Add Address Triggers
    const editBtns = document.querySelectorAll('.edit-address-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const dataset = btn.dataset;
            document.getElementById('addressModalTitle').textContent = 'Cập nhật địa chỉ';
            document.getElementById('addr_id').value = dataset.addressId;
            document.getElementById('addr_fullname').value = dataset.fullname;
            document.getElementById('addr_phone').value = dataset.phone;
            document.getElementById('addr_specific').value = dataset.specificAddress;
            document.getElementById('addr_province').value = dataset.province;
            document.getElementById('addr_district').value = dataset.district;
            document.getElementById('addr_ward').value = dataset.ward;

            const lat = dataset.latitude ? parseFloat(dataset.latitude) : null;
            const lng = dataset.longitude ? parseFloat(dataset.longitude) : null;
            document.getElementById('addr_lat').value = lat || '';
            document.getElementById('addr_lng').value = lng || '';

            locState.province_name = dataset.province;
            locState.district_name = dataset.district;
            locState.ward_name = dataset.ward;
            locState.province = null; locState.district = null; locState.ward = null;
            updateLocPickerText();

            document.getElementById('locPickerContainer').style.display = 'block';
            document.getElementById('addr_specific').readOnly = false;

            setAddrType(dataset.type);
            document.getElementById('addr_default').checked = dataset.isDefault == '1';
            
            openAddressModal(true);
        });
    });

    const addBtns = document.querySelectorAll('.add-address-btn');
    addBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openAddressModal(false);
        });
    });

    // Restore simulator options and selected address from localStorage if saved
    const savedStateStr = localStorage.getItem('checkout_address_state');
    if (savedStateStr) {
        try {
            const state = JSON.parse(savedStateStr);
            localStorage.removeItem('checkout_address_state'); // Clear it immediately


            // 2. Select correct weather option
            if (weatherSelect && state.weather) {
                weatherSelect.value = state.weather;
                const hiddenWeather = document.getElementById('hidden_weather');
                if (hiddenWeather) hiddenWeather.value = state.weather;
            }

            // 3. Select correct peak hour option
            if (peakHourSelect && state.is_peak_hour !== undefined) {
                peakHourSelect.value = state.is_peak_hour;
                const hiddenPeak = document.getElementById('hidden_is_peak_hour');
                if (hiddenPeak) hiddenPeak.value = state.is_peak_hour;
            }

            // 4. Restore selected address id
            if (state.selected_address_id) {
                const addrRadio = document.querySelector(`input[name="address_selector"][value="${state.selected_address_id}"]`);
                const selectedAddressIdInput = document.getElementById('selected_address_id');
                if (selectedAddressIdInput) {
                    selectedAddressIdInput.value = state.selected_address_id;
                }
                if (addrRadio) {
                    addrRadio.checked = true;
                    // Highlight correct card initially by dispatching change
                    setTimeout(() => {
                        addrRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    }, 50);
                }
            }
        } catch (e) {
            console.error('Error restoring checkout state:', e);
        }
    }

    // Setup input visual borders
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


    // Payment method selection border update
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateBorders(paymentRadios);
            if (orderBtn) {
                if (radio.value === 'momo') {
                    orderBtn.innerText = 'Thanh toán qua MoMo';
                } else {
                    orderBtn.innerText = 'Đặt hàng (COD)';
                }
            }
        });
    });





    // Initialize borders
    updateBorders(paymentRadios);

    // Apply coupon
    let discountPercent = 0;
    let maxDiscountAmount = 0;
    if (applyCouponBtn && couponInput) {
        applyCouponBtn.addEventListener('click', () => {
            const code = couponInput.value.trim().toUpperCase();
            
            if (code === '') {
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponMessage.innerText = '';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
                calculateTotal();
                return;
            }

            const token = document.querySelector('input[name="_token"]').value;
            const priceSummaryEl = document.getElementById('price-summary');
            const currentSubtotal = priceSummaryEl ? parseInt(priceSummaryEl.dataset.subtotal) : 0;

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
                    if (data.discount_type === 'percent') {
                        discountPercent = data.discount_value;
                        maxDiscountAmount = data.max_discount_amount ? parseFloat(data.max_discount_amount) : 0;
                    } else {
                        discountPercent = 0;
                        maxDiscountAmount = 0;
                    }

                    couponMessage.innerText = data.message;
                    couponMessage.className = 'text-xs text-primary font-bold mt-1';
                    discountRow.classList.remove('hidden');
                    discountText.innerText = '-' + discount.toLocaleString('vi-VN') + 'đ';
                    document.getElementById('hidden_coupon_code').value = data.coupon_code;
                } else {
                    discount = 0;
                    discountPercent = 0;
                    couponMessage.innerText = data.message;
                    couponMessage.className = 'text-xs text-error font-bold mt-1';
                    discountRow.classList.add('hidden');
                    document.getElementById('hidden_coupon_code').value = '';
                }
                calculateTotal();
            })
            .catch(err => {
                console.error(err);
                discount = 0;
                discountPercent = 0;
                maxDiscountAmount = 0;
                couponMessage.innerText = 'Có lỗi xảy ra khi kiểm tra mã.';
                couponMessage.className = 'text-xs text-error font-bold mt-1';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
                calculateTotal();
            });
        });
    }

    function calculateTotal() {
        const hiddenDist = document.getElementById('hidden_distance_km');
        const distanceKm = hiddenDist ? parseFloat(hiddenDist.value) : 2.5;

        const hiddenWeatherFee = document.getElementById('hidden_weather_fee');
        const weatherFee = hiddenWeatherFee ? parseFloat(hiddenWeatherFee.value) : 0;

        // Free shipping for orders >= 150,000
        const freeShip = subtotal >= 150000;

        // Shipping fee: 3000 VND per km (free if order >= 150,000)
        const distanceFee = freeShip ? 0 : Math.round(distanceKm * 3000);

        // Calculate dynamic discount if it's percent based
        if (discountPercent > 0) {
            discount = Math.round(subtotal * (discountPercent / 100));
            if (maxDiscountAmount && maxDiscountAmount > 0 && discount > maxDiscountAmount) {
                discount = maxDiscountAmount;
            }
            if (discount > subtotal) discount = subtotal;
            if (discountText) {
                discountText.innerText = '-' + discount.toLocaleString('vi-VN') + 'đ';
            }
        }

        // Calculate final total
        const total = Math.max(0, subtotal + distanceFee + (freeShip ? 0 : weatherFee) - discount);

        // Update UI
        if (shippingBaseText) shippingBaseText.innerText = '0đ';
        const distValEl = document.getElementById('summary-distance-km-val');
        if (distValEl) distValEl.innerText = distanceKm.toFixed(1);

        const freeShipRow = document.getElementById('summary-free-ship-row');
        const shippingDistRow = document.getElementById('summary-shipping-distance-row');

        if (distanceKm > 10) {
            if (orderBtn) {
                orderBtn.disabled = true;
                orderBtn.innerText = 'Chỉ giao trong 10km';
                orderBtn.classList.add('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600');
                orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary', 'bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
            }
            if (shippingDistanceText) shippingDistanceText.innerHTML = '<span class="text-error font-bold">Không hỗ trợ giao quá 10km</span>';
            if (totalText) totalText.innerHTML = '<span class="text-error font-bold">---</span>';
            // Reset weather fee - ẩn hàng phụ thu và reset giá trị cũ khi địa chỉ quá xa
            const weatherRowOver = document.getElementById('summary-weather-fee-row');
            if (weatherRowOver) weatherRowOver.classList.add('hidden');
            if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
        } else {
            if (orderBtn) {
                orderBtn.disabled = false;
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                orderBtn.classList.remove('opacity-80', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-600', 'opacity-50');
                if (selectedPayment && selectedPayment.value === 'momo') {
                    orderBtn.innerText = 'Thanh toán qua MoMo';
                    orderBtn.classList.add('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                    orderBtn.classList.remove('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                } else {
                    orderBtn.innerText = 'Đặt hàng (COD)';
                    orderBtn.classList.add('bg-primary-container', 'hover:bg-[#008f00]', 'text-on-primary');
                    orderBtn.classList.remove('bg-[#ae2070]', 'hover:bg-[#8b1a5a]', 'text-white');
                }
            }

            // Free ship logic
            if (freeShip) {
                if (freeShipRow) freeShipRow.classList.remove('hidden');
                if (shippingDistRow) shippingDistRow.classList.add('hidden');
                if (shippingDistanceText) shippingDistanceText.innerText = '0đ';
                // Ẩn phụ thu thời tiết khi miễn ship
                const weatherRow = document.getElementById('summary-weather-fee-row');
                if (weatherRow) weatherRow.classList.add('hidden');
                if (hiddenWeatherFee) hiddenWeatherFee.value = 0;
            } else {
                if (freeShipRow) freeShipRow.classList.add('hidden');
                if (shippingDistRow) shippingDistRow.classList.remove('hidden');
                if (shippingDistanceText) shippingDistanceText.innerText = distanceFee.toLocaleString('vi-VN') + 'đ';

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
    }

    // Address list toggle
    const changeAddressBtn = document.getElementById('change-address-btn');
    const addressListPanel = document.getElementById('address-list-panel');
    if (changeAddressBtn && addressListPanel) {
        changeAddressBtn.addEventListener('click', () => {
            addressListPanel.classList.toggle('hidden');
        });
    }

    // Selecting alternative address
    const addressRadios = document.querySelectorAll('input[name="address_selector"]');
    const hiddenAddressIdInput = document.getElementById('selected_address_id');
    const activeAddressName = document.getElementById('active-address-name');
    const activeAddressPhone = document.getElementById('active-address-phone');
    const activeAddressDetails = document.getElementById('active-address-details');

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
                        if (data.is_mock) {
                            calcDesc.innerHTML = `<span style="color:#d97706; font-weight: 600;">⚠️ ${data.message}</span><br>Phí vận chuyển: 3.000đ / km.`;
                        } else {
                            calcDesc.innerHTML = `<span style="color:#15803d; font-weight: 600;">✅ Khoảng cách được tính thực tế bằng OpenRouteService API.</span><br>Phí vận chuyển: 3.000đ / km.`;
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

    function updateWeatherFeeForAddress(addressId, distanceKm) {
        if (!addressId) return;

        fetch(`/checkout/weather-fee?address_id=${addressId}&distance_km=${distanceKm}&subtotal=${subtotal}`)
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

    // Run initial calculate and fetch initial address distance
    calculateTotal();
    if (hiddenAddressIdInput && hiddenAddressIdInput.value) {
        updateDistanceForAddress(hiddenAddressIdInput.value);
    }

    addressRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (hiddenAddressIdInput) hiddenAddressIdInput.value = radio.value;
            if (activeAddressName) activeAddressName.innerText = radio.dataset.fullname;
            if (activeAddressPhone) activeAddressPhone.innerText = radio.dataset.phone;
            if (activeAddressDetails) activeAddressDetails.innerText = radio.dataset.address;
            
            // Fetch real distance on selection change, which then fetches weather
            updateDistanceForAddress(radio.value);
            
            // Highlight selected address card
            addressRadios.forEach(r => {
                const card = r.closest('.address-card');
                if (card) {
                    if (r.checked) {
                        card.classList.add('border-primary', 'bg-primary-container/5');
                        card.classList.remove('border-outline-variant');
                    } else {
                        card.classList.remove('border-primary', 'bg-primary-container/5');
                        card.classList.add('border-outline-variant');
                    }
                }
            });
        });
    });
});

async function deleteAddressCheckout(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) return;
    try {
        const res = await fetch(`/profile/address/${id}/delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content') })
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || "Có lỗi xảy ra");
        }
    } catch (e) { alert("Có lỗi xảy ra"); }
}
</script>
@endsection
