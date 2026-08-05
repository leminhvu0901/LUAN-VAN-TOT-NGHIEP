@extends('backend.layouts.app')

@section('title', 'Tạo đơn tại quầy - Nhân viên pha chế')

@section('content')
    <div class="p-4 sm:p-6 pb-24 sm:pb-24 lg:pb-6">
        <div class="mb-4">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Tạo đơn tại quầy</h2>
            <p class="text-gray-500 text-sm mt-1">Dùng cho khách tại quầy, mang đi, hoặc đặt giao hàng qua điện thoại.</p>
        </div>

        <div id="pos-alert-area">
            @if(session('error'))
                <div class="mb-4 p-3 rounded-xl text-sm font-medium bg-red-50 text-red-700 border border-red-200">{{ session('error') }}</div>
            @elseif($errors->any())
                <div class="mb-4 p-3 rounded-xl text-sm font-medium bg-red-50 text-red-700 border border-red-200">{{ $errors->first() }}</div>
            @elseif(session('success'))
                <div class="mb-4 p-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ session('success') }}</div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cột sản phẩm --}}
            <div class="lg:col-span-2 space-y-3">
                <input type="text" id="pos-product-search" placeholder="Tìm sản phẩm..."
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm">

                <div class="flex gap-2 overflow-x-auto pb-1" id="pos-category-filter">
                    <button type="button" class="pos-category-chip is-active shrink-0 px-3 py-1.5 rounded-full text-xs font-bold border-2 border-primary bg-primary text-white" data-category-id="">
                        Tất cả
                    </button>
                    @foreach($categories as $category)
                        <button type="button" class="pos-category-chip shrink-0 px-3 py-1.5 rounded-full text-xs font-bold border-2 border-gray-200 text-gray-600" data-category-id="{{ $category->id }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 xl:grid-cols-5 gap-2.5" id="pos-product-grid">
                    @foreach($products as $product)
                        @php
                            $productPayload = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'image_url' => $product->image_url,
                                'base_price' => (float) $product->base_price,
                                'sizes' => $product->sizes->map(fn ($s) => ['size_name' => $s->size_name, 'price_adjustment' => (float) $s->price_adjustment])->values(),
                                'toppings' => $product->toppings->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'price' => (float) $t->price])->values(),
                            ];
                        @endphp
                        <div class="pos-product-card bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden {{ !$product->is_active ? 'opacity-50' : '' }}"
                            data-name="{{ mb_strtolower($product->name) }}" data-category-id="{{ $product->category_id }}">
                            <div class="aspect-[4/3] bg-gray-50 overflow-hidden">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            </div>
                            <div class="p-1.5">
                                <p class="font-semibold text-[11px] text-gray-900 truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                                <p class="text-emerald-600 font-bold text-xs mt-0.5">{{ number_format($product->base_price, 0, ',', '.') }}đ</p>
                                @if($product->is_active)
                                    <button type="button" class="pos-add-btn mt-1.5 w-full min-h-[28px] bg-primary text-white text-[11px] font-bold rounded-lg"
                                        data-product='@json($productPayload)'>
                                        + Thêm
                                    </button>
                                @else
                                    <button type="button" disabled class="mt-1.5 w-full min-h-[28px] bg-gray-100 text-gray-400 text-[11px] font-bold rounded-lg">Hết hàng</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Cột giỏ hàng + thông tin khách. Ở lg:+ (desktop): dính trên cùng, tự cuộn nội bộ,
                 giống hệt hành vi cũ. Dưới lg: (tablet/điện thoại): ẩn mặc định và hiện dưới dạng
                 bottom-sheet khi lễ tân bấm nút "Xem giỏ hàng" ở thanh nổi đáy màn hình (JS bên dưới)
                 — tránh phải cuộn qua hết lưới sản phẩm mới tới được nút "Tạo đơn". --}}
            <div id="pos-cart-column" class="hidden lg:block fixed inset-x-0 lg:inset-auto bottom-0 lg:bottom-auto z-50 lg:z-auto space-y-4 max-h-[85vh] lg:max-h-[calc(100vh-6rem)] overflow-y-auto lg:pr-1 rounded-t-2xl lg:rounded-none bg-white lg:bg-transparent shadow-2xl lg:shadow-none p-4 lg:p-0 lg:sticky lg:top-0 lg:self-start">
                <div class="lg:hidden flex justify-end">
                    <button type="button" id="pos-cart-close-btn" class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500" aria-label="Đóng giỏ hàng">
                        <span class="material-symbols-outlined text-[20px]">close</span>
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
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden" id="pos-cart-discount-row">
                            <span id="pos-cart-discount-label">Khuyến mãi</span>
                            <span id="pos-cart-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden" id="pos-cart-combo-discount-row">
                            <span>Giảm giá combo</span>
                            <span id="pos-cart-combo-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden" id="pos-cart-membership-row">
                            <span>Ưu đãi hạng thành viên</span>
                            <span id="pos-cart-membership-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-emerald-600 font-semibold hidden" id="pos-cart-points-row">
                            <span>Điểm tích lũy</span>
                            <span id="pos-cart-points-discount">-0đ</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-500 hidden" id="pos-cart-shipping-row">
                            <span>Phí giao hàng</span>
                            <span id="pos-cart-shipping">0đ</span>
                        </div>
                        {{-- Quà tặng Mua X Tặng Y: ẩn mặc định, JS hiện khi có dữ liệu --}}
                        <div id="pos-cart-gifts-row" class="hidden">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-amber-600 mb-1 mt-0.5">
                                <span class="material-symbols-outlined text-[15px]">redeem</span>
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

                <form id="pos-order-form" action="{{ route('staff.reception.orders.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                    @csrf

                    @php
                        // Đơn tại quầy submit qua form thật giờ đây có thể redirect-back kèm lỗi validate
                        // (vd. điểm vượt số dư) - dùng old() để khôi phục đúng lựa chọn trước đó của lễ
                        // tân thay vì reset về mặc định, tránh mất thông tin khách hàng đang thao tác dở.
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
                            <label class="pos-order-type-option flex flex-col items-center justify-center gap-1 min-h-[52px] border-2 rounded-xl text-xs font-bold cursor-pointer {{ $posOrderType === 'dine_in' ? $posActiveOrderType : $posInactiveOrderType }}">
                                <input type="radio" name="order_type" value="dine_in" {{ $posOrderType === 'dine_in' ? 'checked' : '' }} class="sr-only">
                                <span class="material-symbols-outlined text-[18px]">restaurant</span> Tại quầy
                            </label>
                            <label class="pos-order-type-option flex flex-col items-center justify-center gap-1 min-h-[52px] border-2 rounded-xl text-xs font-bold cursor-pointer {{ $posOrderType === 'takeaway' ? $posActiveOrderType : $posInactiveOrderType }}">
                                <input type="radio" name="order_type" value="takeaway" {{ $posOrderType === 'takeaway' ? 'checked' : '' }} class="sr-only">
                                <span class="material-symbols-outlined text-[18px]">takeout_dining</span> Mang đi
                            </label>
                        </div>
                        {{-- Gửi thật lên server — JS đồng bộ theo lựa chọn order_type ở trên --}}
                        <input type="hidden" name="pickup_mode" id="pos-pickup-mode" value="{{ $posOrderType }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Khách hàng</label>
                        <input type="hidden" name="customer_id" id="pos-customer-id" value="{{ old('customer_id', '') }}">

                        <div id="pos-customer-selected" class="{{ $selectedCustomer ? '' : 'hidden' }} bg-emerald-50 border border-emerald-200 rounded-lg mb-2 p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-emerald-800 truncate" id="pos-customer-selected-name">{{ $selectedCustomer->name ?? '' }}</p>
                                    <p class="text-xs text-emerald-600" id="pos-customer-selected-phone">{{ $selectedCustomer->phone ?? 'Chưa có SĐT' }}</p>
                                </div>
                                <button type="button" id="pos-customer-clear" class="text-emerald-600 hover:text-emerald-800 shrink-0">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-2 pt-2 border-t border-emerald-100">
                                <label class="text-xs text-emerald-700 font-medium shrink-0">Dùng điểm (đang có <span id="pos-customer-points-balance">{{ $selectedCustomer->points ?? 0 }}</span> điểm):</label>
                                <input type="number" name="points_to_redeem" id="pos-points-to-redeem" min="0" step="1" value="{{ old('points_to_redeem', 0) }}" max="{{ $selectedCustomer->points ?? 0 }}"
                                    class="w-24 px-2 py-1 border border-emerald-200 rounded-lg text-sm">
                            </div>
                            <p id="pos-points-feedback" class="text-xs"></p>
                        </div>

                        <div id="pos-customer-search-wrap" class="relative {{ $selectedCustomer ? 'hidden' : '' }}">
                            <input type="text" id="pos-customer-search" autocomplete="off" placeholder="Tìm SĐT/tên khách (bỏ trống = khách vãng lai)"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <div id="pos-customer-results" class="hidden absolute z-10 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mã khuyến mãi (tùy chọn)</label>
                        <div class="flex gap-2">
                            <input type="text" name="coupon_code" id="pos-coupon-code" placeholder="Nhập mã..." value="{{ old('coupon_code', '') }}"
                                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm uppercase">
                            <button type="button" id="pos-coupon-apply" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-sm shrink-0">Áp dụng</button>
                        </div>
                        <p id="pos-coupon-feedback" class="text-xs mt-1"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phương thức thanh toán <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2" id="pos-payment-method-grid">
                            <label class="pos-payment-option flex items-center justify-center gap-2 min-h-[46px] border-2 rounded-xl text-sm font-bold cursor-pointer {{ $posPaymentMethod === 'cash' ? $posActivePayment : $posInactivePayment }}">
                                <input type="radio" name="payment_method" value="cash" {{ $posPaymentMethod === 'cash' ? 'checked' : '' }} class="sr-only">
                                <span class="material-symbols-outlined text-[18px]">payments</span> Tiền mặt
                            </label>
                            @if($momoEnabled)
                                <label class="pos-payment-option flex items-center justify-center gap-2 min-h-[46px] border-2 rounded-xl text-sm font-bold cursor-pointer {{ $posPaymentMethod === 'momo' ? $posActivePayment : $posInactivePayment }}">
                                    <input type="radio" name="payment_method" value="momo" {{ $posPaymentMethod === 'momo' ? 'checked' : '' }} class="sr-only">
                                    <span class="material-symbols-outlined text-[18px]">account_balance</span> MoMo
                                </label>
                            @endif
                            @if($vnpayEnabled)
                                <label class="pos-payment-option flex items-center justify-center gap-2 min-h-[46px] border-2 rounded-xl text-sm font-bold cursor-pointer {{ $posPaymentMethod === 'vnpay' ? $posActivePayment : $posInactivePayment }}">
                                    <input type="radio" name="payment_method" value="vnpay" {{ $posPaymentMethod === 'vnpay' ? 'checked' : '' }} class="sr-only">
                                    <span class="material-symbols-outlined text-[18px]">credit_card</span> VNPay
                                </label>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (tùy chọn)</label>
                        <textarea name="note" rows="2" placeholder="Ví dụ: ít đá, không đường..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">{{ old('note', '') }}</textarea>
                    </div>

                    <button type="submit" id="pos-submit-btn" class="w-full min-h-[46px] bg-emerald-600 text-white font-bold rounded-xl">Tạo đơn</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Lớp phủ nền khi giỏ hàng dạng bottom-sheet đang mở trên tablet/điện thoại (dưới lg:) --}}
    <div id="pos-cart-backdrop" class="hidden lg:hidden fixed inset-0 bg-black/40 z-40"></div>

    {{-- Thanh giỏ hàng nổi cố định đáy màn hình — chỉ hiện dưới lg: (tablet/điện thoại), JS tự ẩn khi
         giỏ hàng trống. Bấm vào để mở bottom-sheet giỏ hàng + form thanh toán ở trên. --}}
    <div id="pos-mobile-cart-bar" class="hidden lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs text-gray-500">Giỏ hàng (<span id="pos-mobile-cart-count">0</span> món)</p>
            <p class="font-black text-emerald-600 text-lg" id="pos-mobile-cart-total">0đ</p>
        </div>
        <button type="button" id="pos-mobile-cart-open-btn" class="shrink-0 px-5 py-2.5 bg-primary text-white font-bold rounded-xl text-sm">Xem giỏ hàng</button>
    </div>

    {{-- Modal chọn size/topping/đường/đá/số lượng khi thêm sản phẩm --}}
    <div id="pos-product-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" id="pos-modal-backdrop"></div>
        <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md max-h-[90vh] overflow-y-auto p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-lg" id="pos-modal-product-name">Sản phẩm</h3>
                    <button type="button" id="pos-modal-close" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500">
                        <span class="material-symbols-outlined text-[18px]">close</span>
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
                        <button type="button" id="pos-modal-qty-minus" class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 font-bold">−</button>
                        <span id="pos-modal-qty" class="font-bold text-lg w-6 text-center">1</span>
                        <button type="button" id="pos-modal-qty-plus" class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 font-bold">+</button>
                    </div>
                </div>

                <button type="button" id="pos-modal-add" class="w-full min-h-[46px] bg-primary text-white font-bold rounded-xl flex items-center justify-center gap-2">
                    <span>Thêm vào giỏ</span>
                    <span id="pos-modal-price" class="font-black"></span>
                </button>
            </div>
        </div>
    </div>


    <script>
        window.posPreviewTotalUrl = '{{ route('staff.reception.orders.preview_total') }}';
        window.posCustomerSearchUrl = '{{ route('staff.reception.customers.search') }}';
    </script>
    <script src="{{ asset('js/backend/staff/reception/orders/create.js') }}"></script>
@endsection
