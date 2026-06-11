@extends('layouts.app')

@section('body_class', 'profile-body')

@section('content')
<div class="min-h-screen bg-background text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container pb-24">
    <!-- Header Page (Material Style) -->
    <header class="bg-surface/80 backdrop-blur-md border-b border-outline-variant sticky top-16 z-40 py-4 px-6 md:px-12 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform md:hidden">
                <span class="material-symbols-outlined text-primary">arrow_back</span>
            </a>
            <h1 class="font-headline-lg text-headline-md-mobile md:text-headline-lg text-primary">Thanh toán đơn hàng</h1>
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

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
            @csrf
            
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
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-xl flex flex-col items-center text-center">
                                <span class="material-symbols-outlined text-4xl mb-2 text-yellow-600">location_off</span>
                                <p class="font-bold">Bạn chưa có địa chỉ giao hàng!</p>
                                <p class="text-sm mt-1">Vui lòng thêm địa chỉ nhận hàng trong hồ sơ để hoàn thành đặt hàng.</p>
                                <a href="{{ route('profile') }}#address" class="mt-4 bg-primary text-white px-6 py-2 rounded-full font-bold text-sm hover:opacity-95 transition-all">
                                    Thêm địa chỉ ngay
                                </a>
                            </div>
                        @else
                            @php 
                                $defaultAddress = $addresses->where('is_default', 1)->first() ?? $addresses->first();
                            @endphp
                            
                            <!-- Active Address Info Block -->
                            <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60 relative">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="font-bold text-on-surface text-base" id="active-address-name">{{ $defaultAddress->fullname }}</span>
                                    <span class="text-outline-variant">|</span>
                                    <span class="text-on-surface-variant font-medium" id="active-address-phone">{{ $defaultAddress->phone }}</span>
                                    
                                    @if($defaultAddress->is_default)
                                        <span class="border border-primary text-primary px-2 py-0.5 text-[10px] rounded-sm bg-primary/5 uppercase font-bold ml-auto">Mặc định</span>
                                    @endif
                                </div>
                                <p class="text-sm text-on-surface-variant" id="active-address-details">
                                    {{ $defaultAddress->specific_address }}, {{ $defaultAddress->ward }}, {{ $defaultAddress->district }}, {{ $defaultAddress->province }}
                                </p>
                                
                                <input type="hidden" name="address_id" id="selected_address_id" value="{{ $defaultAddress->id }}">
                            </div>

                            @if($addresses->count() > 1)
                                <div class="mt-3 text-right">
                                    <button type="button" id="change-address-btn" class="text-primary hover:text-[#005301] text-sm font-bold flex items-center justify-end gap-1 ml-auto">
                                        <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                        Thay đổi địa chỉ
                                    </button>
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
                                                data-address="{{ $addr->specific_address }}, {{ $addr->ward }}, {{ $addr->district }}, {{ $addr->province }}"
                                                {{ $addr->id == $defaultAddress->id ? 'checked' : '' }}>
                                            <div class="text-sm">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-on-surface">{{ $addr->fullname }}</span>
                                                    <span class="text-xs text-on-surface-variant">({{ $addr->phone }})</span>
                                                    @if($addr->type == 'home')
                                                        <span class="text-[10px] px-1.5 py-0.5 border border-outline-variant rounded text-on-surface-variant">Nhà</span>
                                                    @else
                                                        <span class="text-[10px] px-1.5 py-0.5 border border-outline-variant rounded text-on-surface-variant">Cty</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-on-surface-variant mt-1">{{ $addr->specific_address }}, {{ $addr->ward }}, {{ $addr->district }}, {{ $addr->province }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </section>

                    <!-- 2. Shipping Delivery Method Section -->
                    <section class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">local_shipping</span>
                            <h2 class="font-headline-md text-lg text-on-surface font-bold">Phương thức giao hàng</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Standard Delivery -->
                            <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                <input type="radio" name="delivery_method" value="standard" checked class="text-primary focus:ring-primary">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-on-surface">Tiêu chuẩn</span>
                                        <span class="font-bold text-primary">15.000đ</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1">Dự kiến giao hàng trong 30 - 45 phút</p>
                                </div>
                            </label>

                            <!-- Express Delivery -->
                            <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-xl cursor-pointer hover:bg-surface-container-low transition-all">
                                <input type="radio" name="delivery_method" value="express" class="text-primary focus:ring-primary">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-on-surface">Hỏa tốc</span>
                                        <span class="font-bold text-primary">25.000đ</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1">Dự kiến giao hàng trong 15 - 30 phút</p>
                                </div>
                            </label>
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
                                    <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.svg" alt="MoMo" class="w-8 h-8 rounded-lg">
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
                                <span>Tạm tính</span>
                                <span id="summary-subtotal-text">{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex justify-between text-sm text-on-surface-variant font-medium">
                                <span>Phí vận chuyển</span>
                                <span id="summary-shipping-text">15.000đ</span>
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
                        @if(!$addresses->isEmpty())
                            <button type="submit" id="order-submit-btn" class="w-full bg-primary-container text-on-primary hover:bg-[#008f00] font-bold text-center py-3.5 rounded-xl shadow-sm hover:shadow-md transition-all active:scale-98 mt-6">
                                Đặt hàng (COD)
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceSummaryEl = document.getElementById('price-summary');
    if (!priceSummaryEl) return;

    const subtotal = parseInt(priceSummaryEl.dataset.subtotal);
    let shippingFee = 15000;
    let discount = 0;

    const shippingRadios = document.querySelectorAll('input[name="delivery_method"]');
    const shippingText = document.getElementById('summary-shipping-text');
    const discountRow = document.getElementById('summary-discount-row');
    const discountText = document.getElementById('summary-discount-text');
    const totalText = document.getElementById('summary-total-text');
    const couponInput = document.getElementById('coupon_code_input');
    const applyCouponBtn = document.getElementById('apply_coupon_btn');
    const couponMessage = document.getElementById('coupon_message');
    const orderBtn = document.getElementById('order-submit-btn');

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

    // Shipping selection change handler
    shippingRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateBorders(shippingRadios);
            shippingFee = radio.value === 'express' ? 25000 : 15000;
            shippingText.innerText = shippingFee.toLocaleString('vi-VN') + 'đ';
            calculateTotal();
        });
    });

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
    updateBorders(shippingRadios);
    updateBorders(paymentRadios);

    // Apply coupon
    if (applyCouponBtn && couponInput) {
        applyCouponBtn.addEventListener('click', () => {
            const code = couponInput.value.trim().toUpperCase();
            if (code === 'HAPPY') {
                discount = 10000;
                couponMessage.innerText = 'Áp dụng thành công mã giảm giá HAPPY!';
                couponMessage.className = 'text-xs text-primary font-bold mt-1';
                discountRow.classList.remove('hidden');
                discountText.innerText = '-10.000đ';
                document.getElementById('hidden_coupon_code').value = 'HAPPY';
            } else if (code === '') {
                discount = 0;
                couponMessage.innerText = '';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
            } else {
                discount = 0;
                couponMessage.innerText = 'Mã giảm giá không hợp lệ.';
                couponMessage.className = 'text-xs text-error font-bold mt-1';
                discountRow.classList.add('hidden');
                document.getElementById('hidden_coupon_code').value = '';
            }
            calculateTotal();
        });
    }

    function calculateTotal() {
        const total = Math.max(0, subtotal + shippingFee - discount);
        if (totalText) {
            totalText.innerText = total.toLocaleString('vi-VN') + 'đ';
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

    addressRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (hiddenAddressIdInput) hiddenAddressIdInput.value = radio.value;
            if (activeAddressName) activeAddressName.innerText = radio.dataset.fullname;
            if (activeAddressPhone) activeAddressPhone.innerText = radio.dataset.phone;
            if (activeAddressDetails) activeAddressDetails.innerText = radio.dataset.address;
            
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
</script>
@endsection
