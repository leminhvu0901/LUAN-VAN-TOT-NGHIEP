{{-- Kế thừa cấu trúc giao diện chính của toàn bộ trang web (Layout App) --}}
@extends('frontend.layouts.app')

{{-- Đặt tên class CSS riêng cho thẻ body của trang quản lý đơn hàng. Thêm "orders-page-body" để
     bật lại navbar chung (search/yêu thích/giỏ hàng/tài khoản) trên mobile — mặc định class
     "profile-body" ẩn navbar chung để dùng header riêng, nhưng trang Đơn hàng muốn dùng navbar
     chung giống trang Sản phẩm (xem override trong users.css). --}}
@section('body_class', 'profile-body orders-page-body')


@section('content')
    {{-- Khung chứa chính của trang web. Nếu có mã đơn hàng từ session (ví dụ sau khi đặt hàng/đánh giá xong), thuộc tính data-open-order-id sẽ truyền dữ liệu cho JS tự động mở chi tiết --}}
    <div class="min-h-screen md:flex bg-background text-on-surface md:text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container relative pb-24 md:pb-0"
        data-open-order-id="{{ session('open_order_id') }}">

        <!-- Khu vực hiển thị nội dung chính -->
        <main class="flex-1 pt-4 md:pt-stack_lg px-4 md:px-stack_lg pb-stack_lg w-full max-w-6xl mx-auto relative z-10">
            <div class="w-full">
                {{-- Hiển thị thông báo thành công từ Session (ví dụ khi hủy đơn, đánh giá thành công) --}}
                @if (session('success'))
                    <div
                        class="bg-secondary-container text-on-secondary-container border border-outline-variant px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">check_circle</span>
                        <span class="font-bold text-sm">{{ session('success') }}</span>
                    </div>
                @endif

                {{-- Hiển thị thông báo lỗi từ Session --}}
                @if (session('error'))
                    <div
                        class="bg-red-50 text-red-800 border border-red-200 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <span class="font-bold text-sm">{{ session('error') }}</span>
                    </div>
                @endif

                {{-- Hiển thị lỗi Validation --}}
                @if ($errors->any())
                    <div class="bg-red-50 text-red-800 border border-red-200 px-4 py-3 rounded-xl mb-6 shadow-sm">
                        <ul class="list-disc list-inside text-xs font-bold">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- DESKTOP: Tiêu đề trang dành cho màn hình lớn -->
                <div class="hidden md:block mb-stack_lg">
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Đơn hàng của tôi</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Xem lại lịch sử các đơn hàng bạn đã đặt</p>
                </div>

                <!-- Thanh lọc đơn hàng theo trạng thái (Tất cả, Chờ xác nhận, Đang giao,...) -->
                <div
                    class="flex gap-2 md:gap-4 mb-6 md:mb-8 overflow-x-auto pb-2 hide-scrollbar sticky md:static top-16 z-10 bg-background/95 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none pt-2 md:pt-0">
                    <a href="{{ route('orders') }}"
                        class="px-5 md:px-6 py-2 {{ !$status ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Tất
                        cả</a>
                    <a href="{{ route('orders', ['status' => 'pending']) }}"
                        class="px-5 md:px-6 py-2 {{ $status == 'pending' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Chờ
                        xác nhận</a>
                    <a href="{{ route('orders', ['status' => 'confirmed']) }}"
                        class="px-5 md:px-6 py-2 {{ $status == 'confirmed' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Đã
                        xác nhận</a>
                    <a href="{{ route('orders', ['status' => 'shipping']) }}"
                        class="px-5 md:px-6 py-2 {{ $status == 'shipping' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Đang
                        giao</a>
                    <a href="{{ route('orders', ['status' => 'completed']) }}"
                        class="px-5 md:px-6 py-2 {{ $status == 'completed' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Hoàn
                        thành</a>
                    <a href="{{ route('orders', ['status' => 'cancelled']) }}"
                        class="px-5 md:px-6 py-2 {{ $status == 'cancelled' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Đã
                        hủy</a>
                </div>

                <!-- Khung chứa danh sách các đơn hàng -->
                <div class="space-y-4 md:space-y-6">
                    @forelse($orders as $order)
                        <div
                            class="bg-white rounded-2xl md:rounded-xl border border-outline-variant p-4 md:p-6 shadow-sm md:shadow-none md:order-card-hover transition-all duration-300">

                            <!-- Phần đầu của mỗi Thẻ Đơn hàng (Mã đơn hàng, ngày đặt, trạng thái đơn) -->
                            <div
                                class="flex justify-between items-start mb-3 md:mb-6 border-b border-gray-100 md:border-outline-variant pb-3 md:pb-4">
                                <div>
                                    <div class="flex flex-col md:flex-row md:items-center gap-0.5 md:gap-3 mb-0 md:mb-1">
                                        <span
                                            class="font-bold text-gray-800 md:text-primary text-sm md:text-base">#{{ $order->order_code ?? 'HPY-' . $order->id }}</span>
                                        <div class="flex items-center gap-2 mt-0.5 md:mt-0">
                                            <span
                                                class="hidden md:inline-block w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                                            <span
                                                class="text-gray-500 md:text-on-surface-variant text-[11px] md:text-sm">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y • H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="hidden md:flex items-center gap-2 mt-1">
                                        <span
                                            class="material-symbols-outlined text-sm text-on-surface-variant">local_shipping</span>
                                        <span class="text-sm text-on-surface-variant">
                                            @if ($order->delivery_type == 'delivery')
                                                Giao hàng tận nơi
                                            @else
                                                Nhận tại cửa hàng
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- Hiển thị trạng thái đơn hàng --}}
                                @switch($order->status)
                                    @case('pending')
                                        <span id="order-status-{{ $order->id }}"
                                            class="px-2.5 py-1 md:px-4 md:py-1.5 bg-yellow-100 text-yellow-800 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Chờ
                                            xác nhận</span>
                                    @break

                                    @case('confirmed')
                                        <span id="order-status-{{ $order->id }}"
                                            class="px-2.5 py-1 md:px-4 md:py-1.5 bg-blue-100 text-blue-800 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Đã
                                            xác nhận</span>
                                    @break

                                    @case('shipping')
                                        <span id="order-status-{{ $order->id }}"
                                            class="px-2.5 py-1 md:px-4 md:py-1.5 bg-green-100 md:bg-primary-container/20 text-green-800 md:text-primary rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Đang
                                            giao</span>
                                    @break

                                    @case('completed')
                                        <span id="order-status-{{ $order->id }}"
                                            class="px-2.5 py-1 md:px-4 md:py-1.5 bg-secondary-container text-on-secondary-container rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Hoàn
                                            thành</span>
                                    @break

                                    @case('cancelled')
                                        <span id="order-status-{{ $order->id }}"
                                            class="px-2.5 py-1 md:px-4 md:py-1.5 bg-error-container text-on-error-container rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Đã
                                            hủy</span>
                                    @break
                                @endswitch
                            </div>

                            <!-- Phần thân Thẻ Đơn hàng (Hình ảnh minh họa sản phẩm, Tên món, Tổng tiền, Nút tương tác) -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="flex items-center gap-3 md:gap-4 mb-3 md:mb-0 flex-1 min-w-0">
                                    {{-- Giao diện di động: Chỉ hiển thị hình ảnh của sản phẩm đầu tiên --}}
                                    <div
                                        class="md:hidden w-16 h-16 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0 border border-outline-variant">
                                        @if ($order->items->first())
                                            <img src="{{ $order->items->first()->product_image_url }}"
                                                onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'"
                                                alt="Product" class="w-full h-full object-cover">
                                        @else
                                            <img src="{{ asset('images/products/placeholder.jpg') }}" alt="Product"
                                                class="w-full h-full object-cover">
                                        @endif
                                    </div>

                                    {{-- Giao diện máy tính: Xếp lớp ảnh đại diện các sản phẩm nếu có nhiều món --}}
                                    <div class="hidden md:flex -space-x-3 overflow-hidden">
                                        @foreach ($order->items->take(2) as $item)
                                            <img alt="{{ $item->product_name }}"
                                                class="inline-block h-16 w-16 rounded-lg ring-4 ring-white object-cover bg-surface-container-low border border-outline-variant"
                                                src="{{ $item->product_image_url }}"
                                                onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                                        @endforeach
                                        @if ($order->items->count() > 2)
                                            <div
                                                class="h-16 w-16 rounded-lg ring-4 ring-white bg-surface-container-highest flex items-center justify-center text-xs font-bold text-primary border border-outline-variant">
                                                +{{ $order->items->count() - 2 }}</div>
                                        @endif
                                    </div>

                                    {{-- Tên sản phẩm và tóm tắt thông số đơn hàng (Số lượng, size, đường, đá) --}}
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 md:text-on-surface text-base truncate">
                                            {{ $order->items->first()->product_name ?? 'Sản phẩm đồ uống' }}
                                            <span class="hidden md:inline">
                                                @if ($order->items->count() > 1)
                                                    và {{ $order->items->count() - 1 }} sản phẩm khác
                                                @endif
                                            </span>
                                        </h3>
                                        <p class="md:hidden text-gray-600 text-xs mt-0.5">
                                            @if ($order->items->first())
                                                x{{ $order->items->first()->quantity }} • Size
                                                {{ $order->items->first()->size_name ?? 'M' }}
                                                @if ($order->items->first()->sugar_level)
                                                    • {{ $order->items->first()->sugar_level }} đường
                                                @endif
                                            @endif
                                        </p>
                                        @if ($order->items->count() > 1)
                                            <p class="md:hidden text-gray-500 text-xs mt-1 font-medium">+
                                                {{ $order->items->count() - 1 }} sản phẩm khác</p>
                                        @endif
                                        <p class="hidden md:block text-sm text-on-surface-variant">
                                            @if ($order->payment_status == 'paid')
                                                Đã thanh toán trực tuyến
                                            @else
                                                Thanh toán khi nhận hàng (COD)
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <!-- Khu vực thanh toán và các nút chi tiết/mua lại của đơn hàng -->
                                <div
                                    class="flex justify-between md:justify-end items-center gap-2 md:gap-8 border-t border-gray-100 md:border-t-0 pt-3 md:pt-0 text-left md:text-right shrink-0">
                                    <div>
                                        <p
                                            class="text-[10px] md:text-xs text-gray-600 md:text-on-surface-variant uppercase font-semibold md:font-bold mb-0 md:mb-1 whitespace-nowrap">
                                            Tổng cộng</p>
                                        <p
                                            class="text-base md:text-xl font-bold md:font-extrabold text-primary whitespace-nowrap">
                                            {{ number_format($order->final_amount, 0, ',', '.') }}đ</p>
                                    </div>
                                    <div class="flex gap-2 md:gap-3 shrink-0">
                                        <button type="button" data-toggle-order="{{ $order->id }}"
                                            class="px-4 py-1.5 md:px-6 md:py-2.5 border border-primary text-primary font-bold text-xs md:text-base rounded-full md:rounded-lg hover:bg-primary/5 transition-all active:scale-95 whitespace-nowrap">Chi
                                            tiết</button>
                                        {{-- Đơn "Chờ xác nhận" luôn được khách tự hủy, kể cả đã thanh toán online:
                                     với đơn đã trả tiền, hệ thống tự gọi API hoàn tiền của cổng thanh toán
                                     rồi mới hủy (CustomerOrderController::refundAndCancelForCustomer()).
                                     data-paid-online để JS cảnh báo đúng nội dung trước khi xác nhận. --}}
                                        @if ($order->status === 'pending')
                                            @php
                                                $paidOnline =
                                                    $order->payment_status === 'paid' &&
                                                    $order->payment_method === 'vnpay';
                                            @endphp
                                            <button type="button" id="cancel-btn-{{ $order->id }}"
                                                data-paid-online="{{ $paidOnline ? '1' : '0' }}" data-gateway-label="VNPay"
                                                onclick="confirmCancelOrder('{{ $order->id }}', '{{ $order->order_code ?? 'HPY-' . $order->id }}')"
                                                class="px-4 py-1.5 md:px-6 md:py-2.5 bg-red-50 border border-red-300 text-red-600 font-bold text-xs md:text-base rounded-full md:rounded-lg hover:bg-red-100 transition-all active:scale-95 whitespace-nowrap">
                                                {{ $paidOnline ? 'Hủy & hoàn tiền' : 'Hủy đơn' }}
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('orders.reorder', $order) }}" class="inline-block">
                                            @csrf
                                            <button type="submit"
                                                class="px-4 py-1.5 md:px-6 md:py-2.5 bg-primary text-white font-bold text-xs md:text-base rounded-full md:rounded-lg shadow-sm hover:shadow-md transition-all active:scale-95 whitespace-nowrap">Mua
                                                lại</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Phần chi tiết thông tin đơn hàng bị ẩn đi (Chỉ hiện ra khi bấm nút Chi tiết) -->
                            <div id="order-details-{{ $order->id }}"
                                class="hidden mt-6 pt-6 border-t border-dashed border-outline-variant/60 transition-all duration-300">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

                                    <!-- Cột 1: Thông tin người nhận và địa chỉ giao hàng -->
                                    <div class="space-y-2">
                                        <h4
                                            class="font-bold text-on-surface text-sm border-b border-outline-variant pb-2 flex items-center gap-1.5">
                                            <span
                                                class="material-symbols-outlined text-primary text-lg">local_shipping</span>
                                            Thông tin giao nhận
                                        </h4>
                                        <p class="text-on-surface-variant"><span class="font-bold text-on-surface">Người
                                                nhận:</span> {{ $order->customer_name }}</p>
                                        <p class="text-on-surface-variant"><span class="font-bold text-on-surface">Số điện
                                                thoại:</span> {{ $order->customer_phone }}</p>
                                        <p class="text-on-surface-variant"><span class="font-bold text-on-surface">Địa chỉ
                                                giao:</span> {{ $order->delivery_address }}</p>
                                        @if ($order->customer_note)
                                            <p class="text-on-surface-variant italic"><span
                                                    class="font-bold text-on-surface">Ghi chú:</span>
                                                "{{ $order->customer_note }}"</p>
                                        @endif
                                    </div>

                                    <!-- Cột 2: Bảng chi tiết tính toán tiền thanh toán (Tạm tính, Ship, phụ phí, giảm giá...) -->
                                    <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/60">
                                        <h4
                                            class="font-bold text-on-surface text-sm border-b border-outline-variant pb-2 mb-3 flex items-center gap-1.5">
                                            <span
                                                class="material-symbols-outlined text-primary text-lg">receipt_long</span>
                                            Chi tiết thanh toán đơn hàng
                                        </h4>

                                        <div class="flex justify-between items-center mb-2">
                                            <span
                                                class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Hình
                                                thức thanh toán</span>
                                            <span class="text-xs font-bold text-on-surface">
                                                {{ match ($order->payment_method) {'vnpay' => 'VNPay','cash' => 'Tiền mặt',default => 'COD (khi nhận hàng)'} }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex justify-between items-center mb-3 pb-3 border-b border-outline-variant">
                                            <span
                                                class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Trạng
                                                thái thanh toán</span>
                                            @if ($order->payment_status === 'paid')
                                                <span class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                                    Đã thanh toán
                                                </span>
                                            @elseif($order->payment_status === 'refunded')
                                                <span class="text-xs font-bold text-slate-600 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[14px]">undo</span> Đã hoàn tiền
                                                </span>
                                            @elseif(in_array($order->payment_method, ['cash', 'cod'], true) || !$order->payment_method)
                                                <span class="text-xs font-bold text-on-surface-variant">Thanh toán khi nhận hàng</span>
                                            @else
                                                <span class="text-xs font-bold text-amber-600 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[14px]">pending</span> Chờ thanh toán
                                                </span>
                                            @endif
                                        </div>
                                        @if ($order->payment_status === 'refunded' && $order->refunded_at)
                                            <div
                                                class="flex justify-between items-center mb-3 -mt-2 text-[11px] text-on-surface-variant">
                                                <span>Hoàn tiền lúc</span>
                                                <span>{{ \Carbon\Carbon::parse($order->refunded_at)->format('H:i d/m/Y') }}</span>
                                            </div>
                                        @endif

                                        <div class="space-y-2 text-xs text-on-surface-variant font-medium">
                                            <div class="flex justify-between">
                                                <span>Tạm tính (Sản phẩm)</span>
                                                <span
                                                    class="text-on-surface font-bold">{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Phí vận chuyển (khoảng cách:
                                                    {{ number_format($order->distance_km, 1) }} km)</span>
                                                <span
                                                    class="text-on-surface font-bold">{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</span>
                                            </div>
                                            @if ($order->weather_fee > 0)
                                                <div class="flex justify-between">
                                                    <span>Phụ phí thời tiết</span>
                                                    <span
                                                        class="text-on-surface font-bold text-primary">+{{ number_format($order->weather_fee, 0, ',', '.') }}đ</span>
                                                </div>
                                            @endif
                                            @if ($order->peak_hour_fee > 0)
                                                <div class="flex justify-between">
                                                    <span>Phụ phí giờ cao điểm</span>
                                                    <span
                                                        class="text-on-surface font-bold text-primary">+{{ number_format($order->peak_hour_fee, 0, ',', '.') }}đ</span>
                                                </div>
                                            @endif
                                            @if ($order->discount_amount > 0)
                                                <div class="flex justify-between text-error font-bold">
                                                    <span>Khuyến mãi ({{ $order->coupon_code ?? 'HAPPY' }})</span>
                                                    <span>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                                                </div>
                                            @endif
                                            <div
                                                class="flex justify-between font-bold text-sm text-on-surface border-t border-outline-variant pt-2 mt-2">
                                                <span>Tổng cộng</span>
                                                <span
                                                    class="text-primary text-base font-extrabold">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Danh sách chi tiết từng sản phẩm đã mua cùng nút Đánh giá món ăn -->
                                <div class="mt-6 pt-4 border-t border-outline-variant/60">
                                    <h4 class="font-bold text-on-surface text-sm mb-3 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-primary text-lg">local_cafe</span>
                                        Sản phẩm đã mua
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($order->items as $item)
                                            <div
                                                class="flex items-center justify-between text-xs text-on-surface-variant bg-surface-container-low p-3 rounded-xl border border-outline-variant/50">
                                                <div class="flex items-center gap-3">
                                                    {{-- Hình ảnh sản phẩm --}}
                                                    <div
                                                        class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 border border-outline-variant">
                                                        <img src="{{ $item->product_image_url }}"
                                                            onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'"
                                                            class="w-full h-full object-cover">
                                                    </div>
                                                    {{-- Tên và các tùy chọn (Size, đường, đá, topping) --}}
                                                    <div>
                                                        <span
                                                            class="font-bold text-on-surface text-sm block">{{ $item->product_name }}</span>
                                                        <div class="flex flex-wrap gap-2 mt-1">
                                                            <span
                                                                class="bg-primary/5 text-primary border border-primary/20 rounded-full px-2 py-0.5 text-[10px] font-bold">Size
                                                                {{ $item->size_name ?? 'M' }}</span>
                                                            @if ($item->sugar_level !== null)
                                                                <span
                                                                    class="bg-yellow-500/5 text-yellow-700 border border-yellow-500/20 rounded-full px-2 py-0.5 text-[10px] font-bold">{{ $item->sugar_level }}%
                                                                    đường</span>
                                                            @endif
                                                            @if ($item->ice_level !== null)
                                                                <span
                                                                    class="bg-blue-500/5 text-blue-700 border border-blue-500/20 rounded-full px-2 py-0.5 text-[10px] font-bold">Đá:
                                                                    {{ match ($item->ice_level) {'full' => 'Đá riêng', 'normal' => 'Đá chung', 'less' => 'Ít đá', 'none' => 'Không đá', default => $item->ice_level} }}</span>
                                                            @endif
                                                        </div>
                                                        @if ($item->options)
                                                            @php $toppingsArr = is_array($item->options) ? $item->options : json_decode($item->options, true); @endphp
                                                            @if (!empty($toppingsArr))
                                                                <div class="text-[10px] text-primary font-semibold mt-1.5">
                                                                    + Toppings: {{ implode(', ', $toppingsArr) }}</div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Số lượng, Đơn giá và nút Đánh giá/Đã đánh giá (nếu đơn hàng đã hoàn thành) --}}
                                                <div class="text-right flex flex-col items-end justify-center">
                                                    <div>
                                                        <span
                                                            class="text-on-surface-variant font-bold">x{{ $item->quantity }}</span>
                                                        <span
                                                            class="font-extrabold text-on-surface ml-4 text-sm">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</span>
                                                    </div>
                                                    @if ($order->status == 'completed')
                                                        @php
                                                            $hasReviewed = $reviewedItems
                                                                ->where('order_id', $order->id)
                                                                ->where('product_id', $item->product_id)
                                                                ->isNotEmpty();
                                                        @endphp
                                                        @if ($hasReviewed)
                                                            <a href="{{ route('review.create', ['orderId' => $order->id, 'productId' => $item->product_id]) }}"
                                                                class="mt-2 whitespace-nowrap text-[10px] md:text-xs text-primary font-bold px-3 py-1 bg-primary/10 rounded-full border border-primary/20 hover:bg-primary/20 transition-colors">Xem đánh giá</a>
                                                        @else
                                                            <a href="{{ route('review.create', ['orderId' => $order->id, 'productId' => $item->product_id]) }}"
                                                                class="mt-2 whitespace-nowrap text-[10px] md:text-xs text-white bg-primary font-bold px-4 py-1.5 rounded-full shadow-sm hover:shadow-md transition-all active:scale-95">Đánh giá</a>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                            {{-- Trạng thái danh sách rỗng (Chưa có đơn hàng nào) --}}
                            <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl md:rounded-xl border border-outline-variant p-6"
                                id="empty-state">
                                <span
                                    class="material-symbols-outlined text-6xl text-outline-variant mb-4">shopping_cart_off</span>
                                <h3 class="text-lg md:text-xl font-bold text-on-background mb-2">Chưa có đơn hàng nào</h3>
                                <p class="text-sm md:text-base text-on-surface-variant mb-6">Có vẻ như bạn chưa đặt món đồ uống
                                    nào từ chúng tôi.</p>
                                <a href="{{ url('/products') }}"
                                    class="bg-primary text-white px-6 py-2.5 md:px-8 md:py-3 rounded-full font-bold shadow-sm md:shadow-md hover:shadow-lg transition-transform active:scale-95">Bắt
                                    đầu mua sắm</a>
                            </div>
                        @endforelse
                    </div>

                    <!-- Phân trang danh sách đơn hàng (Pagination) -->
                    @if ($orders->lastPage() > 1)
                        <div class="mt-8 md:mt-12 flex justify-center items-center gap-2 pb-6 md:pb-0">
                            @if ($orders->onFirstPage())
                                <span
                                    class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant opacity-50 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-lg md:text-base">chevron_left</span>
                                </span>
                            @else
                                <a href="{{ $orders->previousPageUrl() }}"
                                    class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant hover:bg-surface-container-low transition-all">
                                    <span class="material-symbols-outlined text-lg md:text-base">chevron_left</span>
                                </a>
                            @endif

                            @foreach ($orders->getUrlRange(max(1, $orders->currentPage() - 1), min($orders->lastPage(), $orders->currentPage() + 1)) as $page => $url)
                                @if ($page == $orders->currentPage())
                                    <span
                                        class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full bg-primary text-white text-sm md:text-base font-bold">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}"
                                        class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant text-sm md:text-base hover:bg-surface-container-low transition-all">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($orders->hasMorePages())
                                <a href="{{ $orders->nextPageUrl() }}"
                                    class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant hover:bg-surface-container-low transition-all">
                                    <span class="material-symbols-outlined text-lg md:text-base">chevron_right</span>
                                </a>
                            @else
                                <span
                                    class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant opacity-50 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-lg md:text-base">chevron_right</span>
                                </span>
                            @endif
                        </div>
                    @endif

                </div>
            </main>

            @include('frontend.components.bottom-nav')
            {{-- Form ẩn dùng để gửi yêu cầu hủy đơn hàng --}}
            <form id="cancel-order-form" method="POST" action="" class="hidden">
                @csrf
                <input type="hidden" name="cancel_reason" id="cancel-reason-input">
            </form>
        </div>
    @endsection
    {{-- Đẩy tệp tin JavaScript chuyên biệt vào khu vực chứa script của layout --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Event delegation: gắn 1 listener duy nhất lên document thay vì lặp gắn cho từng nút
                document.addEventListener('click', function(event) {
                    const toggle = event.target.closest('[data-toggle-order]');
                    if (toggle) window.toggleOrderDetails(toggle.dataset.toggleOrder);
                });

                // Tự động mở sẵn phần chi tiết của 1 đơn cụ thể ngay khi vừa tải trang
                const mainContainer = document.querySelector('[data-open-order-id]');
                if (mainContainer) {
                    const orderId = mainContainer.getAttribute('data-open-order-id');
                    if (orderId) {
                        // Trễ 300ms để chờ layout ổn định trước khi cuộn tới, tránh lệch vị trí
                        setTimeout(() => {
                            if (typeof toggleOrderDetails === 'function') {
                                toggleOrderDetails(orderId);
                            }
                            const el = document.getElementById('order-details-' + orderId);
                            if (el) {
                                el.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }
                        }, 300);
                    }
                }
            });

            // Ẩn/hiện khối chi tiết đơn hàng
            window.toggleOrderDetails = function(orderId) {
                const detailsEl = document.getElementById('order-details-' + orderId);
                if (detailsEl) {
                    detailsEl.classList.toggle('hidden');
                }
            };

            // Bấm nút "Hủy đơn"/"Hủy & hoàn tiền" - mở hộp thoại tùy chỉnh
            window.confirmCancelOrder = function(orderId, orderCode) {
                const btn = document.getElementById('cancel-btn-' + orderId);
                const paidOnline = btn && btn.dataset.paidOnline === '1';
                const gatewayLabel = (btn && btn.dataset.gatewayLabel) || 'VNPay';

                window.FrontendAlert.prompt({
                    title: paidOnline ?
                        'Hủy đơn ' + orderCode + ' và hoàn tiền?' :
                        'Hủy đơn hàng ' + orderCode + '?',
                    text: paidOnline ?
                        'Số tiền đã thanh toán sẽ được hoàn tự động qua ' + gatewayLabel +
                        ' (có thể mất vài phút đến vài ngày làm việc tùy ngân hàng). Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):' :
                        'Vui lòng nhập lý do hủy đơn hàng (tối thiểu 5 ký tự):',
                    placeholder: 'Lý do hủy đơn hàng',
                    defaultValue: 'Khách hàng tự hủy đơn hàng',
                    minLength: 5,
                    confirmText: paidOnline ? 'Hủy & hoàn tiền' : 'Hủy đơn',
                }).then(function(result) {
                    // isConfirmed=false nghĩa là khách bấm Hủy/đóng hộp thoại - không làm gì thêm.
                    if (!result.isConfirmed) return;
                    submitCancelOrder(orderId, result.value.trim());
                });
            };

            // Điền động action (route theo đúng orderId) và lý do hủy vào form ẩn
            function submitCancelOrder(orderId, cleanReason) {
                const form = document.getElementById('cancel-order-form');
                const input = document.getElementById('cancel-reason-input');

                if (!form || !input) {
                    if (window.FrontendAlert) window.FrontendAlert.error(
                        'Không tìm thấy form hủy đơn hàng. Vui lòng tải lại trang.');
                    else alert('Không tìm thấy form hủy đơn hàng. Vui lòng tải lại trang.');
                    return;
                }

                form.action = '/orders/' + orderId + '/cancel';
                input.value = cleanReason;
                form.submit();
            }
        </script>
    @endpush
