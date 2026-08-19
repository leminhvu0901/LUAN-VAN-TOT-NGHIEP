@extends('backend.layouts.app')

@section('title', 'Chi tiết Đơn hàng ' . $order->order_code)

@section('content')
    <div class="flex flex-col gap-6 h-full pb-4 orders-page">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    {{-- Nút quay lại trang danh sách đơn hàng --}}
                    <a href="{{ route('admin.orders.index') }}"
                        onclick="smartGoBack(event)"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors"
                        title="Quay lại">
                        <i class="fa-solid fa-arrow-left text-[14px]"></i>
                    </a>

                    {{-- Mã đơn hàng --}}
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Đơn hàng <span
                            class="text-primary">{{ $order->order_code ?? ('#HPY-' . $order->id) }}</span></h1>
                </div>
                {{-- Hiển thị ngày giờ đặt hàng --}}
                <p class="text-sm text-gray-500 mt-1 ml-11">Ngày đặt:
                    {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto mt-4 sm:mt-0">
                {{-- Nút in hóa đơn: Lớp 'print:hidden' để ẩn chính --}}
                <button id="order-print-btn" type="button"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 w-full sm:w-auto rounded-lg hover:bg-gray-50 font-medium transition-colors print:hidden">
                    <i class="fa-solid fa-print text-[14px]"></i>
                    In hóa đơn
                </button>
            </div>
        </div>

        {{-- Cập nhật trạng thái đơn hàng --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            @php
                $isDeliveryOrder = $order->delivery_type !== 'pickup';
                // Kiểm tra điều kiện hủy đơn hàng
                $canCancel = in_array($order->status, ['pending', 'confirmed'], true)
                    && $order->payment_status !== 'paid';
                // Đơn VNPay đã thanh toán ở pending/confirmed -> hủy phải đi kèm hoàn tiền tự động.
                $canRefundAndCancel = in_array($order->status, ['pending', 'confirmed'], true)
                    && $order->payment_method === 'vnpay'
                    && $order->payment_status === 'paid';
                $statusLabels2 = [
                    'pending' => ['Chờ xác nhận', 'badge-pending'],
                    'confirmed' => ['Đã xác nhận', 'badge-confirmed'],
                    'shipping' => ['Đang giao', 'badge-shipping'],
                    'completed' => ['Hoàn thành', 'badge-completed'],
                    'cancelled' => ['Đã hủy', 'badge-cancelled'],
                ];
                [$statusLabel2, $statusBadgeClass2] = $statusLabels2[$order->status] ?? [$order->status, ''];
            @endphp

            @if($order->needs_admin_approval)
                <div class="mb-4 p-4 bg-amber-50 border border-amber-300 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-shield-halved text-amber-600 text-[24px]"></i>
                        <div>
                            <h4 class="font-bold text-amber-900">Yêu cầu phê duyệt đơn hàng (≥ {{ number_format($largeOrderThreshold ?? 500000, 0, ',', '.') }}đ)</h4>
                            <p class="text-xs text-amber-700 mt-0.5">Đơn hàng này cần Admin phê duyệt trước khi nhân viên lễ tân có thể tiếp tục các bước tiếp theo.</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.orders.approve', $order->id) }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-lg shadow-sm flex items-center gap-1.5 transition active:scale-95">
                            <i class="fa-solid fa-circle-check text-[16px]"></i>
                            Phê duyệt đơn hàng
                        </button>
                    </form>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-arrows-rotate text-gray-400"></i>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Trạng thái đơn hàng</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="badge-status {{ $statusBadgeClass2 }} font-bold text-xs inline-block px-2.5 py-1">{{ $statusLabel2 }}</span>
                            @if($order->needs_admin_approval)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <i class="fa-solid fa-hourglass-half text-[11px]"></i> Chờ Admin phê duyệt
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($order->status === 'pending')
                        <form action="{{ route('admin.orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="min-h-[40px] px-4 bg-primary text-white font-bold rounded-lg text-sm">Xác nhận đơn</button>
                        </form>
                    @endif

                    @if($order->delivery_type === 'pickup' && in_array($order->status, ['confirmed', 'shipping'], true))
                        <form action="{{ route('admin.orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="min-h-[40px] px-4 bg-emerald-600 text-white font-bold rounded-lg text-sm">Hoàn thành</button>
                        </form>
                    @endif

                    @if($canCancel)
                        <form id="cancel-order-form" action="{{ route('admin.orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="cancel_reason" id="cancel_reason_input">
                            <button type="button" id="cancel-order-btn" class="min-h-[40px] px-4 bg-red-50 text-red-600 border border-red-200 font-bold rounded-lg text-sm">Hủy đơn</button>
                        </form>
                    @elseif($canRefundAndCancel)
                        <form id="refund-cancel-order-form" action="{{ route('admin.orders.refund', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="cancel_reason" id="refund_cancel_reason_input">
                            <button type="button" id="refund-cancel-order-btn" class="min-h-[40px] px-4 bg-red-50 text-red-600 border border-red-200 font-bold rounded-lg text-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-money-bill-transfer text-[14px]"></i> Hoàn tiền & Hủy đơn
                            </button>
                        </form>
                    @endif

                    @if($isDeliveryOrder && $order->status === 'shipping')
                        <p class="text-xs text-gray-400">Đơn đang được giao — chỉ nhân viên vận chuyển được cập nhật tiếp.</p>
                    @elseif(in_array($order->status, ['completed', 'cancelled']))
                        <p class="text-xs text-gray-400">Đơn đã kết thúc, không thể thay đổi thêm.</p>
                    @elseif(!$canCancel && !$canRefundAndCancel && in_array($order->status, ['pending', 'confirmed']))
                        <p class="text-xs text-gray-400">Đơn đã thanh toán — cần hoàn tiền trước khi hủy.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Thông báo lý do HỦY ĐƠN --}}
        @if($order->status === 'cancelled' && $order->cancel_reason)
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3">
                <i class="fa-solid fa-circle-info text-red-500 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-red-800">Lý do hủy đơn hàng</h4>
                    <p class="text-sm text-red-600 mt-1">{{ $order->cancel_reason }}</p>
                </div>
            </div>
        @endif

        {{-- LƯỚI giao DIỆN chính --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- CỘT trái: Hiển thị danh sách món và tổng tiền --}}
            <div class="lg:col-span-2 flex flex-col gap-6">

                {{-- Khối 1: Danh sách món ăn --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-gray-900 text-lg">Chi tiết món</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        {{-- Vòng lặp lấy từng món trong mảng $items --}}
                        @foreach($items as $item)
                            <div class="flex gap-4 p-5">
                                {{-- Ảnh sản phẩm --}}
                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                                    @if($item->product_image)
                                        <img src="{{ $item->product_image_url }}" alt="{{ $item->product_name }}"
                                             class="w-full h-full object-cover"
                                             data-fallback-src="{{ asset('images/products/placeholder.jpg') }}">
                                     @else
                                         <div class="w-full h-full flex items-center justify-center text-gray-300">
                                             <i class="fa-solid fa-mug-hot text-3xl"></i>
                                         </div>
                                     @endif
                                </div>

                                {{-- Thông tin món: Tên, Size, Topping, Đơn giá, Số lượng --}}
                                <div class="flex-1 flex flex-col justify-between overflow-hidden">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1 sm:gap-2">
                                        <div class="break-words overflow-wrap-anywhere">
                                            <h4 class="font-bold text-gray-900">{{ $item->product_name }}</h4>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-medium">Size
                                                    {{ $item->size ?? 'M' }}</span>
                                            </div>
                                            @if(isset($item->toppings) && $item->toppings)
                                                <p class="text-xs text-gray-500 mt-1">+ Topping: {{ $item->toppings }}</p>
                                            @endif
                                        </div>
                                        <div class="text-left sm:text-right shrink-0">
                                            <span
                                                class="font-bold text-gray-900">{{ number_format($item->unit_price, 0, ',', '.') }}đ</span>
                                            <span class="text-sm text-gray-500 ml-2 sm:ml-0 sm:block">x{{ $item->quantity }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right mt-2 border-t border-gray-50 pt-2 sm:border-none sm:pt-0">
                                        <span class="text-gray-500 text-xs sm:hidden mr-1">Thành tiền:</span>
                                        <span
                                            class="font-bold text-primary">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                     </div>
                 </div>

                 {{-- Khối 2: Tạm tính và Tổng tiền --}}
                 <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                     <div class="flex flex-col gap-3">
                         {{-- Tạm tính tiền món --}}
                         <div class="flex justify-between text-gray-600">
                             <span>Tạm tính ({{ $items->sum('quantity') }} món)</span>
                             <span class="font-medium">{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                         </div>
                         {{-- Phí giao hàng dựa theo khoảng cách --}}
                         <div class="flex justify-between text-gray-600">
                             <span>Phí giao hàng ({{ $order->distance_km ?? 0 }}km)</span>
                             <span class="font-medium">{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }}đ</span>
                         </div>
                         {{-- Phụ thu thời tiết xấu --}}
                         @if($order->weather_fee > 0)
                             <div class="flex justify-between text-gray-600">
                                 <span>Phụ thu thời tiết xấu</span>
                                 <span class="font-medium">{{ number_format($order->weather_fee, 0, ',', '.') }}đ</span>
                             </div>
                         @endif
                         {{-- Tiền được giảm giá từ Coupon --}}
                         @if($order->discount_amount > 0)
                             <div class="flex justify-between text-emerald-600">
                                 <span>Khuyến mãi {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}</span>
                                 <span class="font-medium">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                             </div>
                         @endif
                         @if($order->points_redeemed > 0)
                             <div class="flex justify-between text-emerald-600 text-sm">
                                 <span>Đã dùng điểm tích lũy</span>
                                 <span class="font-medium">{{ number_format($order->points_redeemed, 0, ',', '.') }} điểm</span>
                             </div>
                         @endif
                         {{-- Tổng tiền cuối cùng khách phải trả --}}
                         <div class="pt-4 mt-2 border-t border-gray-100 flex justify-between items-center">
                             <span class="font-bold text-gray-900 text-lg">Tổng cộng</span>
                             <span
                                 class="font-bold text-primary text-xl">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                         </div>
                     </div>
                 </div>
             </div>

             {{-- CỘT PHẢI: Hiển thị thông tin khách hàng, giao --}}
             <div class="flex flex-col gap-6">

                 {{-- Khối 3: Thông tin Khách hàng --}}
                 <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                     <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                         <i class="fa-solid fa-user text-gray-400"></i>
                         Khách hàng
                     </h3>
                     <div class="flex items-center gap-3 mb-4">
                         {{-- Avatar tự động lấy chữ cái đầu tiên của tên khách hàng --}}
                         <div
                             class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
                             {{ substr($order->customer_name, 0, 1) }}
                         </div>
                         <div>
                             <div class="font-bold text-gray-900">{{ $order->customer_name }}</div>
                             <div class="text-sm text-gray-500">{{ $order->customer_phone }}</div>
                         </div>
                     </div>
                 </div>

                 {{-- Khối 4: Thông tin Giao hàng --}}
                 <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                     <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                         <i class="fa-solid fa-truck-fast text-gray-400"></i>
                         Giao hàng
                     </h3>
                     <div class="flex flex-col gap-4">
                         <div>
                             <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Địa chỉ giao</p>
                             <p class="text-sm text-gray-900">{{ $order->delivery_address }}</p>
                         </div>
                         {{-- Ghi chú của khách hàng khi đặt đơn --}}
                         @if($order->customer_note)
                             <div>
                                 <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Ghi chú của khách
                                 </p>
                                 <p class="text-sm text-gray-900 bg-yellow-50 p-3 rounded-lg border border-yellow-100">
                                     {{ $order->customer_note }}</p>
                             </div>
                         @endif
                     </div>
                 </div>

                 {{-- Khối 5: Tình trạng Thanh toán --}}
                 <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                     <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                         <i class="fa-solid fa-money-bill-wave text-gray-400"></i>
                         Thanh toán
                     </h3>
                     <div class="flex items-center justify-between">
                         <div>
                             {{-- Phương thức thanh toán --}}
                             <div class="font-bold text-gray-900 uppercase">{{ $order->payment_method ?? 'COD' }}</div>

                             {{-- Kiểm tra nếu thanh toán online thì in ra trạng --}}
                             @if($order->payment_method === 'vnpay')
                                 @if(($order->payment_status ?? '') === 'paid')
                                     <div class="text-sm font-semibold text-emerald-600 flex items-center gap-1.5 mt-1">
                                         <i class="fa-solid fa-circle-check text-[14px]"></i> Đã thanh toán
                                     </div>
                                 @elseif(($order->payment_status ?? '') === 'refunded')
                                     <div class="text-sm font-semibold text-slate-600 flex items-center gap-1.5 mt-1">
                                         <i class="fa-solid fa-rotate-left text-[14px]"></i> Đã hoàn tiền
                                     </div>
                                     @if($order->refunded_at)
                                         <p class="text-xs text-gray-500 mt-1">Lúc {{ \Carbon\Carbon::parse($order->refunded_at)->format('H:i d/m/Y') }}</p>
                                     @endif
                                 @else
                                     <div class="text-sm font-semibold text-amber-600 flex items-center gap-1.5 mt-1">
                                         <i class="fa-solid fa-clock text-[14px]"></i> Chờ thanh toán
                                     </div>
                                 @endif
                             @else
                                 <div class="text-sm text-gray-500 mt-1">Thanh toán khi nhận hàng</div>
                             @endif
                        {{-- Đổi Icon tương ứng với ví điện tử hoặc tiền mặt --}}
                        <i class="fa-solid {{ ($order->payment_method ?? '') === 'vnpay' ? 'fa-wallet' : 'fa-money-bill-wave' }} text-4xl text-gray-200"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        // Thay bằng ảnh mặc định khi ảnh sản phẩm bị lỗi
        function applyFallbackImage(image) {
            if (image.dataset.fallbackApplied === "true") return;
            image.dataset.fallbackApplied = "true";
            image.src = image.dataset.fallbackSrc;
        }

        // Khởi tạo các sự kiện trên trang chi tiết đơn hàng
        function initOrderShowPage() {
            // Gắn sự kiện in hóa đơn
            const printButton = document.getElementById("order-print-btn");
            if (printButton) {
                printButton.addEventListener("click", function () {
                    window.print();
                });
            }

            // Xử lý ảnh lỗi và hiển thị ảnh dự phòng
            document.querySelectorAll("img[data-fallback-src]").forEach((image) => {
                image.addEventListener("error", function () {
                    applyFallbackImage(this);
                });

                if (image.complete && image.naturalWidth === 0) {
                    applyFallbackImage(image);
                }
            });

            // Xử lý hủy đơn hàng thường kèm lý do
            const cancelBtn = document.getElementById('cancel-order-btn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    window.AdminAlert.prompt(
                        'Hủy đơn hàng',
                        'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):',
                        'Nhập lý do...',
                        function (reason, isConfirmed) {
                            if (!isConfirmed || !reason || reason.trim().length < 5) return;
                            const form = document.getElementById('cancel-order-form');
                            const input = document.getElementById('cancel_reason_input');
                            if (form && input) {
                                input.value = reason.trim();
                                form.submit();
                            }
                        },
                        'Vui lòng nhập lý do hủy đơn!',
                        'Xác nhận hủy',
                        5
                    );
                });
            }

            // Xử lý hủy đơn và hoàn tiền trực tuyến cho khách
            const refundCancelBtn = document.getElementById('refund-cancel-order-btn');
            if (refundCancelBtn) {
                refundCancelBtn.addEventListener('click', function () {
                    window.AdminAlert.prompt(
                        'Hoàn tiền & Hủy đơn',
                        'Hệ thống sẽ gọi hoàn tiền cho khách rồi hủy đơn. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):',
                        'Nhập lý do...',
                        function (reason, isConfirmed) {
                            if (!isConfirmed || !reason || reason.trim().length < 5) return;
                            const form = document.getElementById('refund-cancel-order-form');
                            const input = document.getElementById('refund_cancel_reason_input');
                            if (form && input) {
                                input.value = reason.trim();
                                form.submit();
                            }
                        },
                        'Vui lòng nhập lý do hủy đơn!',
                        'Xác nhận',
                        5
                    );
                });
            }
        }

        // Tự động kích hoạt khi tải xong trang
        initOrderShowPage();
        </script>
    @endpush
@endsection

