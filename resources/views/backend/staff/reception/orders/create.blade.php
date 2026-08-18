@extends('backend.layouts.app')

@section('title', 'Tạo đơn tại quầy - Nhân viên pha chế')

@section('content')
    <div class="p-4 sm:p-6 pb-24 sm:pb-24 lg:pb-6">
        <div class="mb-4">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Tạo đơn tại quầy</h2>
            <p class="text-gray-500 text-sm mt-1">Dùng cho khách tại quầy, mang đi, hoặc đặt giao hàng qua điện thoại.</p>
        </div>

        <div id="pos-alert-area">
            @if (session('error'))
                <div class="mb-4 p-3 rounded-xl text-sm font-medium bg-red-50 text-red-700 border border-red-200">
                    {{ session('error') }}</div>
            @elseif($errors->any())
                <div class="mb-4 p-3 rounded-xl text-sm font-medium bg-red-50 text-red-700 border border-red-200">
                    {{ $errors->first() }}</div>
            @elseif(session('success'))
                <div
                    class="mb-4 p-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ session('success') }}</div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cột sản phẩm --}}
            <div class="lg:col-span-2 space-y-3">
                <input type="text" id="pos-product-search" placeholder="Tìm sản phẩm..."
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm">

                <div class="flex gap-2 overflow-x-auto pb-1" id="pos-category-filter">
                    <button type="button"
                        class="pos-category-chip is-active shrink-0 px-3 py-1.5 rounded-full text-xs font-bold border-2 border-primary bg-primary text-white"
                        data-category-id="">
                        Tất cả
                    </button>
                    @foreach ($categories as $category)
                        <button type="button"
                            class="pos-category-chip shrink-0 px-3 py-1.5 rounded-full text-xs font-bold border-2 border-gray-200 text-gray-600"
                            data-category-id="{{ $category->id }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 xl:grid-cols-5 gap-2.5" id="pos-product-grid">
                    @foreach ($products as $product)
                        @php
                            $productPayload = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'image_url' => $product->image_url,
                                'base_price' => (float) $product->base_price,
                                'sizes' => $product->sizes
                                    ->map(
                                        fn($s) => [
                                            'size_name' => $s->size_name,
                                            'price_adjustment' => (float) $s->price_adjustment,
                                        ],
                                    )
                                    ->values(),
                                'toppings' => $product->toppings
                                    ->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'price' => (float) $t->price])
                                    ->values(),
                            ];
                        @endphp
                        <div class="pos-product-card bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden {{ !$product->is_active ? 'opacity-50' : '' }}"
                            data-name="{{ mb_strtolower($product->name) }}" data-category-id="{{ $product->category_id }}">
                            <div class="aspect-[4/3] bg-gray-50 overflow-hidden">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover">
                            </div>
                            <div class="p-1.5">
                                <p class="font-semibold text-[11px] text-gray-900 truncate" title="{{ $product->name }}">
                                    {{ $product->name }}</p>
                                <p class="text-emerald-600 font-bold text-xs mt-0.5">
                                    {{ number_format($product->base_price, 0, ',', '.') }}đ</p>
                                @if ($product->is_active)
                                    <button type="button"
                                        class="pos-add-btn mt-1.5 w-full min-h-[28px] bg-primary text-white text-[11px] font-bold rounded-lg"
                                        data-product='@json($productPayload)'>
                                        + Thêm
                                    </button>
                                @else
                                    <button type="button" disabled
                                        class="mt-1.5 w-full min-h-[28px] bg-gray-100 text-gray-400 text-[11px] font-bold rounded-lg">Hết hàng</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            
            <div id="pos-cart-column"
                class="hidden lg:block fixed inset-x-0 lg:inset-auto bottom-0 lg:bottom-auto z-50 lg:z-auto space-y-4 max-h-[85vh] lg:max-h-[calc(100vh-6rem)] overflow-y-auto lg:pr-1 rounded-t-2xl lg:rounded-none bg-white lg:bg-transparent shadow-2xl lg:shadow-none p-4 lg:p-0 lg:sticky lg:top-0 lg:self-start">
                <div class="lg:hidden flex justify-end">
                    <button type="button" id="pos-cart-close-btn"
                        class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition-colors"
                        aria-label="Đóng giỏ hàng">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <h3 class="text-lg font-black text-gray-900 mb-3">Giỏ hàng</h3>
                    <div id="pos-cart-items" class="space-y-2">
                        <p class="text-sm text-gray-400 text-center py-4" id="pos-cart-empty">Chưa có sản phẩm nào.</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>Tạm tính</span>
                            <span id="pos-cart-subtotal">0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden"
                            id="pos-cart-discount-row">
                            <span id="pos-cart-discount-label">Khuyến mãi</span>
                            <span id="pos-cart-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden"
                            id="pos-cart-membership-row">
                            <span>Ưu đãi hạng thành viên</span>
                            <span id="pos-cart-membership-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden"
                            id="pos-cart-points-row">
                            <span>Điểm tích lũy</span>
                            <span id="pos-cart-points-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-500 hidden"
                            id="pos-cart-shipping-row">
                            <span>Phí giao hàng</span>
                            <span id="pos-cart-shipping">0đ</span>
                        </div>
                        {{-- Quà tặng Mua X Tặng Y: ẩn mặc định, JS hiện khi có dữ liệu --}}
                        <div id="pos-cart-gifts-row" class="hidden">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-amber-600 mb-1 mt-0.5">
                                <i class="fa-solid fa-gift text-xs"></i>
                                Quà tặng kèm
                            </div>
                            <ul id="pos-cart-gifts-list" class="space-y-0.5"></ul>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                            <span class="text-base font-bold text-gray-800">Tổng cộng phải trả</span>
                            <span class="font-black text-2xl text-emerald-600" id="pos-cart-total">0đ</span>
                        </div>
                    </div>
                </div>

                <form id="pos-order-form" action="{{ route('staff.reception.orders.store') }}" method="POST"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                    @csrf
                    {{-- Bấm đúp sẽ gửi lên cùng token này, server nhận ra là một lần đặt hàng nên không tạo đơn thứ hai --}}
                    <input type="hidden" name="idempotency_key" value="{{ $posToken }}">

                    @php
                        // Lấy hình thức nhận món tại quầy
                        $posOrderType = old('pickup_mode', 'dine_in');
                        $posPaymentMethod = old('payment_method', 'cash');
                        $posActiveOrderType = 'border-primary bg-primary/5 text-primary';
                        $posInactiveOrderType = 'border-gray-200 text-gray-600';
                        $posActivePayment = 'border-emerald-500 bg-emerald-50 text-emerald-700';
                        $posInactivePayment = 'border-gray-200 text-gray-600';
                    @endphp

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại đơn</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label
                                class="pos-order-type-option flex flex-col items-center justify-center gap-1 min-h-[52px] border-2 rounded-xl text-xs font-bold cursor-pointer {{ $posOrderType === 'dine_in' ? $posActiveOrderType : $posInactiveOrderType }}">
                                <input type="radio" name="order_type" value="dine_in"
                                    {{ $posOrderType === 'dine_in' ? 'checked' : '' }} class="sr-only">
                                <i class="fa-solid fa-utensils text-sm"></i> Tại quầy
                            </label>
                            <label
                                class="pos-order-type-option flex flex-col items-center justify-center gap-1 min-h-[52px] border-2 rounded-xl text-xs font-bold cursor-pointer {{ $posOrderType === 'takeaway' ? $posActiveOrderType : $posInactiveOrderType }}">
                                <input type="radio" name="order_type" value="takeaway"
                                    {{ $posOrderType === 'takeaway' ? 'checked' : '' }} class="sr-only">
                                <i class="fa-solid fa-bag-shopping text-sm"></i> Mang đi
                            </label>
                        </div>
                        {{-- Gửi thật lên server --}}
                        <input type="hidden" name="pickup_mode" id="pos-pickup-mode" value="{{ $posOrderType }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Khách hàng</label>
                        <input type="hidden" name="customer_id" id="pos-customer-id"
                            value="{{ old('customer_id', '') }}">

                        <div id="pos-customer-selected"
                            class="{{ $selectedCustomer ? '' : 'hidden' }} bg-emerald-50 border border-emerald-200 rounded-lg mb-2 p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-emerald-800 truncate"
                                        id="pos-customer-selected-name">{{ $selectedCustomer->name ?? '' }}</p>
                                    <p class="text-xs text-emerald-600" id="pos-customer-selected-phone">
                                        {{ $selectedCustomer->phone ?? 'Chưa có SĐT' }}</p>
                                </div>
                                <button type="button" id="pos-customer-clear"
                                    class="text-emerald-600 hover:text-emerald-800 shrink-0">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-2 pt-2 border-t border-emerald-100">
                                <label class="text-xs text-emerald-700 font-medium shrink-0">Dùng điểm (đang có <span
                                        id="pos-customer-points-balance">{{ $selectedCustomer->points ?? 0 }}</span>
                                    điểm):</label>
                                <input type="number" name="points_to_redeem" id="pos-points-to-redeem" min="0"
                                    step="1" value="{{ old('points_to_redeem', 0) }}"
                                    max="{{ $selectedCustomer->points ?? 0 }}"
                                    class="w-24 px-2 py-1 border border-emerald-200 rounded-lg text-sm">
                            </div>
                            <p id="pos-points-feedback" class="text-xs"></p>
                        </div>

                        <div id="pos-customer-search-wrap" class="relative {{ $selectedCustomer ? 'hidden' : '' }}">
                            <input type="text" id="pos-customer-search" autocomplete="off"
                                placeholder="Tìm SĐT/tên khách (bỏ trống = khách vãng lai)"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <div id="pos-customer-results"
                                class="hidden absolute z-10 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mã khuyến mãi (tùy chọn)</label>
                        {{-- Danh sách mã dùng được với giỏ hiện tại --}}
                        <div id="pos-coupon-chips" class="flex flex-wrap gap-2 mb-2"></div>
                        <div class="flex gap-2">
                            <input type="text" name="coupon_code" id="pos-coupon-code" placeholder="Nhập mã..."
                                value="{{ old('coupon_code', '') }}"
                                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm uppercase">
                            <button type="button" id="pos-coupon-apply"
                                class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-sm shrink-0">Áp
                                dụng</button>
                        </div>
                        <p id="pos-coupon-feedback" class="text-xs mt-1"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phương thức thanh toán <span
                                class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2" id="pos-payment-method-grid">
                            <label
                                class="pos-payment-option flex items-center justify-center gap-2 min-h-[46px] border-2 rounded-xl text-sm font-bold cursor-pointer {{ $posPaymentMethod === 'cash' ? $posActivePayment : $posInactivePayment }}">
                                <input type="radio" name="payment_method" value="cash"
                                    {{ $posPaymentMethod === 'cash' ? 'checked' : '' }} class="sr-only">
                                <i class="fa-solid fa-money-bill-wave text-sm"></i> Tiền mặt
                            </label>

                            @if ($vnpayEnabled)
                                <label
                                    class="pos-payment-option flex items-center justify-center gap-2 min-h-[46px] border-2 rounded-xl text-sm font-bold cursor-pointer {{ $posPaymentMethod === 'vnpay' ? $posActivePayment : $posInactivePayment }}">
                                    <input type="radio" name="payment_method" value="vnpay"
                                        {{ $posPaymentMethod === 'vnpay' ? 'checked' : '' }} class="sr-only">
                                    <i class="fa-solid fa-credit-card text-sm"></i> VNPay
                                </label>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (tùy chọn)</label>
                        <textarea name="note" rows="2" placeholder="Ví dụ: ít đá, không đường..."
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">{{ old('note', '') }}</textarea>
                    </div>

                    <button type="submit" id="pos-submit-btn"
                        class="w-full min-h-[46px] bg-emerald-600 text-white font-bold rounded-xl">Tạo đơn</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Lớp phủ nền khi giỏ hàng dạng bottom-sheet đang --}}
    <div id="pos-cart-backdrop" class="hidden lg:hidden fixed inset-0 bg-black/40 z-40"></div>

    {{-- Thanh giỏ hàng nổi cố định đáy màn hình --}}
    <div id="pos-mobile-cart-bar"
        class="hidden lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs text-gray-500">Giỏ hàng (<span id="pos-mobile-cart-count">0</span> món)</p>
            <p class="font-black text-emerald-600 text-lg" id="pos-mobile-cart-total">0đ</p>
        </div>
        <button type="button" id="pos-mobile-cart-open-btn"
            class="shrink-0 px-5 py-2.5 bg-primary text-white font-bold rounded-xl text-sm">Xem giỏ hàng</button>
    </div>

    {{-- Modal chọn size/topping/đường/đá/số lượng khi thêm sản phẩm --}}
    <div id="pos-product-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" id="pos-modal-backdrop"></div>
        <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
            <div
                class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md max-h-[90vh] overflow-y-auto p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-lg" id="pos-modal-product-name">Sản phẩm</h3>
                    <button type="button" id="pos-modal-close"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition-colors">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <div id="pos-modal-size-section" class="hidden">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Kích cỡ</label>
                    <div id="pos-modal-sizes" class="flex flex-wrap gap-2"></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Mức đường</label>
                    <div id="pos-modal-sugar" class="flex flex-wrap gap-2">
                        <button type="button" class="pos-chip-btn" data-value="100">100%</button>
                        <button type="button" class="pos-chip-btn" data-value="70">70%</button>
                        <button type="button" class="pos-chip-btn" data-value="50">50%</button>
                        <button type="button" class="pos-chip-btn" data-value="0">0%</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Mức đá</label>
                    <div id="pos-modal-ice" class="flex flex-wrap gap-2">
                        <button type="button" class="pos-chip-btn" data-value="normal">Đá chung</button>
                        <button type="button" class="pos-chip-btn" data-value="full">Đá riêng</button>
                        <button type="button" class="pos-chip-btn" data-value="less">Ít đá</button>
                        <button type="button" class="pos-chip-btn" data-value="none">Không đá</button>
                    </div>
                </div>

                <div id="pos-modal-topping-section" class="hidden">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Topping thêm</label>
                    <div id="pos-modal-toppings" class="space-y-1.5"></div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Số lượng</label>
                    <div class="flex items-center gap-3">
                        <button type="button" id="pos-modal-qty-minus"
                            class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 font-bold">−</button>
                        <span id="pos-modal-qty" class="font-bold text-lg w-6 text-center">1</span>
                        <button type="button" id="pos-modal-qty-plus"
                            class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 font-bold">+</button>
                    </div>
                </div>

                <button type="button" id="pos-modal-add"
                    class="w-full min-h-[46px] bg-primary text-white font-bold rounded-xl flex items-center justify-center gap-2">
                    <span>Thêm vào giỏ</span>
                    <span id="pos-modal-price" class="font-black"></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.posPreviewTotalUrl = '{{ route('staff.reception.orders.preview_total') }}';
        window.posCustomerSearchUrl = '{{ route('staff.reception.customers.search') }}';

        // Toàn bộ logic trang POS tạo đơn tại quầy, bọc trong IIFE để không rò biến ra global
        (function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value;

            // Hiện thông báo nổi 4 giây rồi tự biến mất
            function showAlert(message, type) {
                const area = document.getElementById('pos-alert-area');
                area.innerHTML = '<div class="mb-4 p-3 rounded-xl text-sm font-medium ' +
                    (type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' :
                        'bg-emerald-50 text-emerald-700 border border-emerald-200') +
                    '">' + message + '</div>';
                setTimeout(() => {
                    area.innerHTML = '';
                }, 4000);
            }

            // Định dạng số tiền kiểu Việt Nam, vd 50000 -> "50.000đ"
            function formatMoney(value) {
                return Number(value).toLocaleString('vi-VN') + 'đ';
            }

            // IIFE riêng cho khối "Tìm khách hàng", cô lập biến searchTimer/selectedBox... khỏi phần còn lại của trang
            (function() {
                const searchInput = document.getElementById('pos-customer-search');
                const resultsBox = document.getElementById('pos-customer-results');
                const searchWrap = document.getElementById('pos-customer-search-wrap');
                const selectedBox = document.getElementById('pos-customer-selected');
                const selectedName = document.getElementById('pos-customer-selected-name');
                const selectedPhone = document.getElementById('pos-customer-selected-phone');
                const customerIdInput = document.getElementById('pos-customer-id');
                const clearBtn = document.getElementById('pos-customer-clear');
                const pointsBalanceEl = document.getElementById('pos-customer-points-balance');
                const pointsInput = document.getElementById('pos-points-to-redeem');

                let searchTimer = null;

                // Gán khách vừa chọn từ kết quả tìm kiếm vào form, ẩn ô search + hiện thẻ thông tin khách
                function selectCustomer(customer) {
                    customerIdInput.value = customer.id;
                    selectedName.textContent = customer.name;
                    selectedPhone.textContent = customer.phone || 'Chưa có SĐT';
                    pointsBalanceEl.textContent = customer.points || 0;
                    pointsInput.value = 0;
                    pointsInput.max = customer.points || 0;
                    selectedBox.classList.remove('hidden');
                    searchWrap.classList.add('hidden');
                    resultsBox.classList.add('hidden');
                    searchInput.value = '';
                    refreshPreviewTotal();
                }

                // Bỏ chọn khách -> quay về đơn khách vãng lai, xóa luôn điểm đang định quy đổi
                function clearCustomer() {
                    customerIdInput.value = '';
                    pointsInput.value = 0;
                    selectedBox.classList.add('hidden');
                    searchWrap.classList.remove('hidden');
                    refreshPreviewTotal();
                }

                clearBtn.addEventListener('click', clearCustomer);

                // Debounce 400ms, gõ số điểm xong mới tính lại tổng, không gọi API mỗi lần gõ 1 ký tự
                let pointsTimer = null;
                pointsInput.addEventListener('input', function() {
                    clearTimeout(pointsTimer);
                    pointsTimer = setTimeout(refreshPreviewTotal, 400);
                });

                // Debounce 300ms + yêu cầu tối thiểu 2 ký tự mới gọi API tìm khách
                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    clearTimeout(searchTimer);

                    if (query.length < 2) {
                        resultsBox.classList.add('hidden');
                        resultsBox.innerHTML = '';
                        return;
                    }

                    searchTimer = setTimeout(function() {
                        fetch(window.posCustomerSearchUrl + '?q=' + encodeURIComponent(query), {
                                headers: {
                                    Accept: 'application/json'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                resultsBox.innerHTML = '';
                                if (!data.results || data.results.length === 0) {
                                    resultsBox.innerHTML =
                                        '<p class="px-3 py-2 text-sm text-gray-400">Không tìm thấy khách hàng nào.</p>';
                                    resultsBox.classList.remove('hidden');
                                    return;
                                }
                                data.results.forEach(function(customer) {
                                    const row = document.createElement('button');
                                    row.type = 'button';
                                    row.className =
                                        'w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-50 last:border-0';
                                    row.innerHTML =
                                        '<span class="font-semibold text-gray-800">' +
                                        customer.name + '</span>' +
                                        '<span class="text-gray-400 ml-1">' + (customer
                                            .phone || '') + '</span>';
                                    row.addEventListener('click', function() {
                                        selectCustomer(customer);
                                    });
                                    resultsBox.appendChild(row);
                                });
                                resultsBox.classList.remove('hidden');
                            });
                    }, 300);
                });

                // Bấm ra ngoài khung tìm kiếm thì tự đóng dropdown kết quả
                document.addEventListener('click', function(event) {
                    if (!searchWrap.contains(event.target)) {
                        resultsBox.classList.add('hidden');
                    }
                });
            })();

            let lastCartItemCount = 0;

            // Cập nhật thanh giỏ hàng nổi ở mobile, ẩn hẳn nếu giỏ trống
            function updateMobileCartBar(total) {
                const bar = document.getElementById('pos-mobile-cart-bar');
                if (!bar) return;

                if (lastCartItemCount <= 0) {
                    bar.classList.add('hidden');
                    return;
                }

                bar.classList.remove('hidden');
                const countEl = document.getElementById('pos-mobile-cart-count');
                const totalEl = document.getElementById('pos-mobile-cart-total');
                if (countEl) countEl.textContent = lastCartItemCount;
                if (totalEl) totalEl.textContent = formatMoney(total);
            }

            // Đổ dữ liệu từ API preview-total vào bảng tổng tiền, mỗi dòng chỉ hiện khi giá trị lớn hơn 0
            function updatePreviewTotal(subtotal, discount, promotionLabel, shippingFee, finalAmount, gifts,
                membershipDiscount, pointsDiscount) {
                document.getElementById('pos-cart-subtotal').textContent = formatMoney(subtotal);

                const discountRow = document.getElementById('pos-cart-discount-row');
                if (discount > 0) {
                    document.getElementById('pos-cart-discount-label').textContent = promotionLabel || 'Khuyến mãi';
                    document.getElementById('pos-cart-discount').textContent = '-' + formatMoney(discount);
                    discountRow.classList.remove('hidden');
                } else {
                    discountRow.classList.add('hidden');
                }

                const membershipRow = document.getElementById('pos-cart-membership-row');
                if (membershipDiscount > 0) {
                    document.getElementById('pos-cart-membership-discount').textContent = '-' + formatMoney(
                        membershipDiscount);
                    membershipRow.classList.remove('hidden');
                } else {
                    membershipRow.classList.add('hidden');
                }

                const pointsRow = document.getElementById('pos-cart-points-row');
                if (pointsDiscount > 0) {
                    document.getElementById('pos-cart-points-discount').textContent = '-' + formatMoney(pointsDiscount);
                    pointsRow.classList.remove('hidden');
                } else {
                    pointsRow.classList.add('hidden');
                }

                const shippingRow = document.getElementById('pos-cart-shipping-row');
                if (shippingFee > 0) {
                    document.getElementById('pos-cart-shipping').textContent = formatMoney(shippingFee);
                    shippingRow.classList.remove('hidden');
                } else {
                    shippingRow.classList.add('hidden');
                }

                const giftsRow = document.getElementById('pos-cart-gifts-row');
                const giftsList = document.getElementById('pos-cart-gifts-list');
                if (giftsRow && giftsList) {
                    if (gifts && gifts.length > 0) {
                        giftsList.innerHTML = gifts.map(function(g) {
                            return '<li class="flex items-center justify-between text-xs text-gray-600">' +
                                '<span class="flex items-center gap-1">' +
                                '<span class="text-amber-500">&#127873;</span>' +
                                '<span class="font-medium">' + g.gift_product_name + '</span>' +
                                '</span>' +
                                '<span class="font-bold text-amber-600">x' + g.quantity + ' Miễn phí</span>' +
                                '</li>';
                        }).join('');
                        giftsRow.classList.remove('hidden');
                    } else {
                        giftsRow.classList.add('hidden');
                        giftsList.innerHTML = '';
                    }
                }

                document.getElementById('pos-cart-total').textContent = formatMoney(finalAmount);
                updateMobileCartBar(finalAmount);
            }

            // Đọc loại đơn đang chọn, mặc định 'dine_in' nếu chưa chọn gì
            function getCurrentOrderType() {
                const checked = document.querySelector('.pos-order-type-option input[name="order_type"]:checked');
                return checked ? checked.value : 'dine_in';
            }

            // Gọi API preview-total lấy số liệu mới nhất mỗi khi có yếu tố ảnh hưởng tới tổng tiền
            function refreshPreviewTotal() {
                const customerIdInput = document.getElementById('pos-customer-id');
                const couponInput = document.getElementById('pos-coupon-code');
                const feedbackEl = document.getElementById('pos-coupon-feedback');
                const pointsInput = document.getElementById('pos-points-to-redeem');
                const pointsFeedbackEl = document.getElementById('pos-points-feedback');

                const params = new URLSearchParams();
                if (customerIdInput && customerIdInput.value) params.set('customer_id', customerIdInput.value);
                if (couponInput && couponInput.value.trim()) params.set('coupon_code', couponInput.value.trim());
                if (customerIdInput && customerIdInput.value && pointsInput && Number(pointsInput.value) > 0) {
                    params.set('points_to_redeem', pointsInput.value);
                }
                params.set('_ts', Date.now());

                fetch(window.posPreviewTotalUrl + '?' + params.toString(), {
                        headers: {
                            Accept: 'application/json'
                        },
                        cache: 'no-store'
                    })
                    .then(r => r.json())
                    .then(data => {
                        updatePreviewTotal(
                            data.subtotal, data.discount, data.promotion_label, data.shipping_fee || 0, data
                            .final_amount,
                            data.gifts || [], data.membership_discount || 0, data.points_discount || 0
                        );

                        if (feedbackEl) {
                            if (data.coupon_error) {
                                feedbackEl.textContent = data.coupon_error;
                                feedbackEl.className = 'text-xs mt-1 text-red-600';
                            } else if (couponInput && couponInput.value.trim() && data.promotion_code) {
                                feedbackEl.textContent = 'Áp dụng thành công: -' + formatMoney(data.discount);
                                feedbackEl.className = 'text-xs mt-1 text-emerald-600';
                            } else {
                                feedbackEl.textContent = '';
                            }
                        }

                        if (pointsFeedbackEl) {
                            if (data.points_error) {
                                pointsFeedbackEl.textContent = data.points_error;
                                pointsFeedbackEl.className = 'text-xs text-red-600';
                            } else if (pointsInput && Number(pointsInput.value) > 0 && data.points_discount > 0) {
                                pointsFeedbackEl.textContent = 'Áp dụng thành công: -' + formatMoney(data
                                    .points_discount);
                                pointsFeedbackEl.className = 'text-xs text-emerald-600';
                            } else {
                                pointsFeedbackEl.textContent = '';
                            }
                        }

                        renderCouponChips(data.available_promotions || []);
                    });
            }

            // Vẽ lại chip mã khả dụng theo giỏ hiện tại; bấm chip = điền mã vào ô rồi tính lại tổng.
            function renderCouponChips(promotions) {
                const wrap = document.getElementById('pos-coupon-chips');
                const couponInput = document.getElementById('pos-coupon-code');
                if (!wrap) return;

                wrap.innerHTML = '';
                const current = couponInput ? couponInput.value.trim().toUpperCase() : '';

                promotions.forEach(promo => {
                    const isActive = current === promo.code;
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.title = promo.label;
                    chip.className = 'px-3 py-1.5 rounded-full border text-xs font-bold transition-all ' +
                        (isActive ?
                            'bg-emerald-600 border-emerald-600 text-white' :
                            'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100');
                    chip.textContent = promo.code + ' — ' + promo.label;

                    chip.addEventListener('click', function() {
                        if (!couponInput) return;
                        // Bấm lại chip đang chọn = bỏ mã, vì mỗi đơn chỉ giữ đúng 1 mã.
                        couponInput.value = isActive ? '' : promo.code;
                        refreshPreviewTotal();
                    });

                    wrap.appendChild(chip);
                });
            }

            const couponApplyBtn = document.getElementById('pos-coupon-apply');
            const couponCodeInput = document.getElementById('pos-coupon-code');
            if (couponApplyBtn && couponCodeInput) {
                couponApplyBtn.addEventListener('click', refreshPreviewTotal);
                couponCodeInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        refreshPreviewTotal();
                    }
                });
            }

            // Ghép mô tả ngắn cho 1 dòng giỏ hàng: "Size L • Đường 50% • Ít đá • + Trân châu, Thạch"
            function describeVariant(item) {
                const parts = [];
                if (item.size_name) parts.push('Size ' + item.size_name);
                if (item.sugar_level !== null && item.sugar_level !== undefined) parts.push('Đường ' + item
                    .sugar_level + '%');
                if (item.ice_level) {
                    const iceLabels = {
                        normal: 'Đá chung',
                        full: 'Đá riêng',
                        less: 'Ít đá',
                        none: 'Không đá'
                    };
                    parts.push(iceLabels[item.ice_level] || item.ice_level);
                }
                if (item.toppings && item.toppings.length > 0) {
                    parts.push('+ ' + item.toppings.map(t => t.name).join(', '));
                }
                return parts.join(' • ');
            }

            // Tải lại toàn bộ giỏ hàng từ server và vẽ lại danh sách sau mỗi lần thêm hoặc xóa món
            function refreshCart() {
                fetch('/cart', {
                        headers: {
                            Accept: 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        const container = document.getElementById('pos-cart-items');

                        if (!data.items || data.items.length === 0) {
                            container.innerHTML =
                                '<p class="text-sm text-gray-400 text-center py-4" id="pos-cart-empty">Chưa có sản phẩm nào.</p>';
                            lastCartItemCount = 0;
                            updatePreviewTotal(0, 0, null, 0, 0, [], 0, 0);
                            return;
                        }

                        lastCartItemCount = data.items.reduce(function(sum, item) {
                            return sum + (item.quantity || 0);
                        }, 0);

                        container.innerHTML = '';
                        data.items.forEach(function(item) {
                            const row = document.createElement('div');
                            row.className =
                                'flex items-start justify-between gap-2 pb-2.5 mb-2.5 border-b border-gray-100 last:border-0 last:pb-0 last:mb-0';
                            const variant = describeVariant(item);
                            row.innerHTML = '<div class="flex-1 min-w-0">' +
                                '<p class="text-base font-bold text-gray-900 truncate">' + item.name +
                                ' <span class="text-primary">x' + item.quantity + '</span></p>' +
                                (variant ?
                                    '<p class="text-xs text-gray-500 break-words leading-snug mt-0.5">' +
                                    variant + '</p>' : '') +
                                '</div>' +
                                '<span class="text-base font-black text-emerald-600 shrink-0">' +
                                formatMoney(item.unit_price * item.quantity) + '</span>' +
                                '<button type="button" class="pos-remove-btn text-red-400 hover:text-red-600 shrink-0" data-item-id="' +
                                item.id + '">' +
                                '<i class="fa-solid fa-xmark text-sm"></i></button>';
                            container.appendChild(row);
                        });

                        refreshPreviewTotal();
                    });
            }

            let activeCategoryId = '';

            // Lọc lưới sản phẩm theo cả từ khóa tìm kiếm và danh mục đang chọn, thuần CSS không gọi server
            function applyProductFilters() {
                const needle = document.getElementById('pos-product-search').value.trim().toLowerCase();
                document.querySelectorAll('.pos-product-card').forEach(function(card) {
                    const matchesName = card.dataset.name.includes(needle);
                    const matchesCategory = activeCategoryId === '' || card.dataset.categoryId ===
                        activeCategoryId;
                    card.style.display = (matchesName && matchesCategory) ? '' : 'none';
                });
            }

            document.getElementById('pos-product-search').addEventListener('input', applyProductFilters);

            document.getElementById('pos-category-filter').addEventListener('click', function(event) {
                const chip = event.target.closest('.pos-category-chip');
                if (!chip) return;

                activeCategoryId = chip.dataset.categoryId;
                document.querySelectorAll('.pos-category-chip').forEach(function(c) {
                    const active = c === chip;
                    c.classList.toggle('is-active', active);
                    c.classList.toggle('border-primary', active);
                    c.classList.toggle('bg-primary', active);
                    c.classList.toggle('text-white', active);
                    c.classList.toggle('border-gray-200', !active);
                    c.classList.toggle('text-gray-600', !active);
                });
                applyProductFilters();
            });

            // State của modal chọn size, đường, đá, topping, reset mỗi lần mở lại cho sản phẩm khác
            const modal = document.getElementById('pos-product-modal');
            let currentProduct = null;
            let selectedSize = null;
            let selectedSugar = '100';
            let selectedIce = 'normal';
            let selectedToppingIds = [];
            let quantity = 1;

            // Tô đậm đúng 1 chip đang chọn trong 1 nhóm, bỏ tô các chip còn lại
            function setActiveChip(container, value) {
                container.querySelectorAll('.pos-chip-btn').forEach(function(btn) {
                    btn.classList.toggle('is-active', btn.dataset.value === value);
                });
            }

            // Tính giá 1 dòng trong modal =, giá gốc + phụ thu size + tổng topping × số lượng
            function computeModalPrice() {
                let price = currentProduct.base_price;
                if (selectedSize) {
                    const size = currentProduct.sizes.find(s => s.size_name === selectedSize);
                    if (size) price += size.price_adjustment;
                }
                selectedToppingIds.forEach(function(id) {
                    const topping = currentProduct.toppings.find(t => t.id === id);
                    if (topping) price += topping.price;
                });
                return price * quantity;
            }

            // Hiện giá vừa tính lên hộp thoại chọn món ngay khi đổi lựa chọn
            function renderModalPrice() {
                document.getElementById('pos-modal-price').textContent = formatMoney(computeModalPrice());
            }

            // Mở modal cho 1 sản phẩm, dựng lại toàn bộ chip size và topping từ dữ liệu JSON gắn sẵn
            function openProductModal(product) {
                currentProduct = product;
                selectedSize = product.sizes.length > 0 ? product.sizes[0].size_name : null;
                selectedSugar = '100';
                selectedIce = 'normal';
                selectedToppingIds = [];
                quantity = 1;

                document.getElementById('pos-modal-product-name').textContent = product.name;
                document.getElementById('pos-modal-qty').textContent = '1';

                const sizeSection = document.getElementById('pos-modal-size-section');
                const sizesContainer = document.getElementById('pos-modal-sizes');
                sizesContainer.innerHTML = '';
                if (product.sizes.length > 0) {
                    sizeSection.classList.remove('hidden');
                    product.sizes.forEach(function(size, index) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'pos-chip-btn' + (index === 0 ? ' is-active' : '');
                        btn.dataset.value = size.size_name;
                        btn.textContent = 'Size ' + size.size_name + (size.price_adjustment > 0 ? ' (+' +
                            formatMoney(size.price_adjustment) + ')' : '');
                        sizesContainer.appendChild(btn);
                    });
                } else {
                    sizeSection.classList.add('hidden');
                }

                const toppingSection = document.getElementById('pos-modal-topping-section');
                const toppingsContainer = document.getElementById('pos-modal-toppings');
                toppingsContainer.innerHTML = '';
                if (product.toppings.length > 0) {
                    toppingSection.classList.remove('hidden');
                    product.toppings.forEach(function(topping) {
                        const label = document.createElement('label');
                        label.className =
                            'flex items-center justify-between gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer text-sm';
                        label.innerHTML =
                            '<span class="flex items-center gap-2"><input type="checkbox" class="pos-modal-topping-checkbox" data-topping-id="' +
                            topping.id + '"> ' + topping.name + '</span>' +
                            '<span class="text-gray-500 font-semibold">+' + formatMoney(topping.price) +
                            '</span>';
                        toppingsContainer.appendChild(label);
                    });
                } else {
                    toppingSection.classList.add('hidden');
                }

                setActiveChip(document.getElementById('pos-modal-sugar'), selectedSugar);
                setActiveChip(document.getElementById('pos-modal-ice'), selectedIce);
                renderModalPrice();

                modal.classList.remove('hidden');
            }

            // Đóng hộp thoại chọn size/topping của món
            function closeProductModal() {
                modal.classList.add('hidden');
                currentProduct = null;
            }

            // Lưới sản phẩm dùng 1 listener chung thay vì gắn riêng từng nút, vẫn chạy khi lưới lọc lại
            document.getElementById('pos-product-grid').addEventListener('click', function(event) {
                const btn = event.target.closest('.pos-add-btn');
                if (!btn) return;
                openProductModal(JSON.parse(btn.dataset.product));
            });

            document.getElementById('pos-modal-close').addEventListener('click', closeProductModal);
            document.getElementById('pos-modal-backdrop').addEventListener('click', closeProductModal);

            document.getElementById('pos-modal-sizes').addEventListener('click', function(event) {
                const btn = event.target.closest('.pos-chip-btn');
                if (!btn) return;
                selectedSize = btn.dataset.value;
                setActiveChip(this, selectedSize);
                renderModalPrice();
            });

            document.getElementById('pos-modal-sugar').addEventListener('click', function(event) {
                const btn = event.target.closest('.pos-chip-btn');
                if (!btn) return;
                selectedSugar = btn.dataset.value;
                setActiveChip(this, selectedSugar);
            });

            document.getElementById('pos-modal-ice').addEventListener('click', function(event) {
                const btn = event.target.closest('.pos-chip-btn');
                if (!btn) return;
                selectedIce = btn.dataset.value;
                setActiveChip(this, selectedIce);
            });

            document.getElementById('pos-modal-toppings').addEventListener('change', function(event) {
                const checkbox = event.target.closest('.pos-modal-topping-checkbox');
                if (!checkbox) return;
                const id = parseInt(checkbox.dataset.toppingId, 10);
                if (checkbox.checked) {
                    selectedToppingIds.push(id);
                } else {
                    selectedToppingIds = selectedToppingIds.filter(t => t !== id);
                }
                renderModalPrice();
            });

            document.getElementById('pos-modal-qty-minus').addEventListener('click', function() {
                if (quantity <= 1) return;
                quantity -= 1;
                document.getElementById('pos-modal-qty').textContent = quantity;
                renderModalPrice();
            });

            document.getElementById('pos-modal-qty-plus').addEventListener('click', function() {
                if (quantity >= 99) return;
                quantity += 1;
                document.getElementById('pos-modal-qty').textContent = quantity;
                renderModalPrice();
            });

            // Bấm Thêm vào giỏ trong modal, disable nút trong lúc chờ để tránh bấm 2 lần thêm trùng
            document.getElementById('pos-modal-add').addEventListener('click', function() {
                if (!currentProduct) return;
                const addBtn = this;
                addBtn.disabled = true;

                fetch('/cart/add', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            product_id: currentProduct.id,
                            quantity: quantity,
                            size_name: selectedSize,
                            sugar_level: selectedSugar,
                            ice_level: selectedIce,
                            toppings: selectedToppingIds,
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success === false) {
                            showAlert(data.message || 'Không thể thêm sản phẩm.', 'error');
                        } else {
                            closeProductModal();
                            refreshCart();
                        }
                    })
                    .finally(() => {
                        addBtn.disabled = false;
                    });
            });

            // Cũng dùng listener chung vì danh sách giỏ hàng bị vẽ lại toàn bộ mỗi lần refreshCart
            document.getElementById('pos-cart-items').addEventListener('click', function(event) {
                const btn = event.target.closest('.pos-remove-btn');
                if (!btn) return;

                fetch('/cart/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        item_id: btn.dataset.itemId
                    }),
                }).then(() => refreshCart());
            });

            const cartColumn = document.getElementById('pos-cart-column');
            const cartBackdrop = document.getElementById('pos-cart-backdrop');
            const mobileCartOpenBtn = document.getElementById('pos-mobile-cart-open-btn');
            const cartCloseBtn = document.getElementById('pos-cart-close-btn');

            // Trên mobile giỏ hàng mặc định ẩn, mở/đóng như 1 drawer
            function openMobileCart() {
                if (!cartColumn) return;
                cartColumn.classList.remove('hidden');
                if (cartBackdrop) cartBackdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            // Chỉ ẩn cột giỏ hàng trên mobile, desktop luôn hiện cột giỏ cố định, không đóng được
            function closeMobileCart() {
                if (!cartColumn) return;
                if (window.innerWidth < 1024) {
                    cartColumn.classList.add('hidden');
                }
                if (cartBackdrop) cartBackdrop.classList.add('hidden');
                document.body.style.overflow = '';
            }

            if (mobileCartOpenBtn) mobileCartOpenBtn.addEventListener('click', openMobileCart);
            if (cartCloseBtn) cartCloseBtn.addEventListener('click', closeMobileCart);
            if (cartBackdrop) cartBackdrop.addEventListener('click', closeMobileCart);

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    document.body.style.overflow = '';
                    if (cartBackdrop) cartBackdrop.classList.add('hidden');
                }
            });

            // Style lại radio button dạng thẻ bấm, dùng chung cho cả nhóm thanh toán và loại đơn
            function wireToggleGroup(selector, activeClasses) {
                document.querySelectorAll(selector).forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        document.querySelectorAll(selector).forEach(function(r) {
                            const label = r.closest('label');
                            const active = r.checked;
                            activeClasses.forEach(function(cls) {
                                label.classList.toggle(cls, active);
                            });
                            label.classList.toggle('border-gray-200', !active);
                            label.classList.toggle('text-gray-600', !active);
                        });
                    });
                });
            }

            wireToggleGroup('.pos-payment-option input[name="payment_method"]', ['border-emerald-500', 'bg-emerald-50',
                'text-emerald-700'
            ]);
            wireToggleGroup('.pos-order-type-option input[name="order_type"]', ['border-primary', 'bg-primary/5',
                'text-primary'
            ]);

            // Đổi loại đơn thì ghi vào input ẩn pickup-mode và tính lại tổng vì có mã chỉ áp cho pickup
            function applyOrderType() {
                document.getElementById('pos-pickup-mode').value = getCurrentOrderType();
                refreshPreviewTotal();
            }

            document.querySelectorAll('.pos-order-type-option input[name="order_type"]').forEach(function(radio) {
                radio.addEventListener('change', applyOrderType);
            });

            // Disable nút "Đặt hàng" ngay khi submit, chặn bấm đúp tạo trùng đơn khi mạng chậm
            const orderForm = document.getElementById('pos-order-form');
            const submitBtn = document.getElementById('pos-submit-btn');
            if (orderForm && submitBtn) {
                orderForm.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                });
            }

            // Tải giỏ hàng thật ngay khi mở trang
            refreshCart();
        })();
    </script>
@endsection
