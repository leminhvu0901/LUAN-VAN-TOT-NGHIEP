@extends('backend.layouts.app')

@section('title', 'Chi tiết Đơn hàng ' . ($order->order_code ?? '#HPY-' . $order->id))

@section('content')
    <div id="pos-order-page-content" class="flex flex-col gap-6 h-full pb-4 orders-page">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    {{-- Nút quay lại trang danh sách đơn hàng --}}
                    <a href="{{ route('staff.reception.orders.index') }}" onclick="smartGoBack(event)"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors shadow-sm"
                        title="Quay lại">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>

                    {{-- Mã đơn hàng --}}
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Đơn hàng <span
                            class="text-primary">{{ $order->order_code ?? '#HPY-' . $order->id }}</span></h1>
                </div>
                {{-- Hiển thị ngày giờ đặt hàng --}}
                <p class="text-sm text-gray-500 mt-1 ml-11">Ngày đặt:
                    {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex items-center flex-wrap gap-2 w-full sm:w-auto mt-4 sm:mt-0 print:hidden">
                {{-- Cho in ngay khi đơn đã xác NHẬN, pha chế cần --}}
                @if (in_array($order->status, ['confirmed', 'shipping', 'completed'], true))
                    <button id="print-prep-ticket-btn" type="button"
                        class="flex items-center justify-center gap-2 px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors text-sm">
                        <i class="fa-solid fa-receipt text-sm"></i>
                        Phiếu pha chế
                    </button>
                    <button id="print-invoice-btn" type="button"
                        class="flex items-center justify-center gap-2 px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors text-sm">
                        <i class="fa-solid fa-print text-sm"></i>
                        Hóa đơn khách
                    </button>
                @else
                    <span class="text-xs text-gray-400 italic">Xác nhận đơn để in hóa đơn/phiếu pha chế</span>
                @endif
            </div>
        </div>

        {{-- Thông báo lý do hủy đơn --}}
        @if ($order->status === 'cancelled' && $order->cancel_reason)
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3">
                <i class="fa-solid fa-circle-info text-red-500 mt-0.5 text-base"></i>
                <div>
                    <h4 class="font-bold text-red-800">Lý do hủy đơn hàng</h4>
                    <p class="text-sm text-red-600 mt-1">{{ $order->cancel_reason }}</p>
                </div>
            </div>
        @endif

        {{-- CẬP NHẬT TRẠNG thái ĐƠN hàng --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            @php
                $statusLabels = [
                    'pending' => ['Chờ xác nhận', 'badge-pending'],
                    'confirmed' => ['Đã xác nhận', 'badge-confirmed'],
                    'shipping' => ['Đang giao', 'badge-shipping'],
                    'completed' => ['Hoàn thành', 'badge-completed'],
                    'cancelled' => ['Đã hủy', 'badge-cancelled'],
                ];
                [$statusLabel, $statusBadgeClass] = $statusLabels[$order->status] ?? [$order->status, ''];
                $isDeliveryOrder = $order->delivery_type !== 'pickup';
                // Kiểm tra điều kiện hủy đơn hàng
                $canCancel =
                    in_array($order->status, ['pending', 'confirmed'], true) && $order->payment_status !== 'paid';
                // Kiểm tra điều kiện hoàn tiền và hủy đơn
                $canRefundAndCancel =
                    in_array($order->status, ['pending', 'confirmed'], true) &&
                    $order->payment_method === 'vnpay' &&
                    $order->payment_status === 'paid';
                // Kiểm tra trạng thái thu tiền mặt tại quầy
                $cashNotYetCollected = $order->payment_method === 'cash' && $order->payment_status !== 'paid';
            @endphp

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-rotate text-gray-400 text-base"></i>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Trạng thái đơn hàng</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                class="badge-status {{ $statusBadgeClass }} font-bold text-xs inline-block px-2.5 py-1">{{ $statusLabel }}</span>
                            @if ($order->needs_admin_approval)
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <i class="fa-solid fa-hourglass-half text-xs"></i> Chờ Admin phê
                                    duyệt
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($order->needs_admin_approval)
                        <div
                            class="text-xs text-amber-800 font-semibold bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-envelope text-amber-600 text-sm"></i>
                            <span>Đã gửi email yêu cầu Admin phê duyệt. Vui lòng chờ Admin xác nhận.</span>
                        </div>
                    @elseif($order->status === 'pending' && $cashNotYetCollected)
                        <p class="text-xs text-amber-600 font-medium flex items-center gap-1.5 max-w-xs">
                            <i class="fa-solid fa-circle-info text-amber-600 text-xs shrink-0"></i>
                            Cần xác nhận đã thu tiền mặt (khối "Thanh toán" bên dưới) trước khi xác nhận đơn.
                        </p>
                    @elseif($order->status === 'pending')
                        @if ((float) $order->final_amount >= 500000)
                            {{-- Đơn hàng từ 500k trở lên phải gửi Admin phê duyệt trước --}}
                            <form action="{{ route('staff.reception.orders.request_approval', $order->id) }}"
                                method="POST">
                                @csrf
                                <button type="submit"
                                    class="min-h-[40px] px-4 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg text-sm flex items-center gap-1.5 shadow-sm transition">
                                    <i class="fa-solid fa-shield-halved text-sm"></i>
                                    Gửi Admin phê duyệt
                                </button>
                            </form>
                        @else
                            <form action="{{ route('staff.reception.orders.status.update', $order->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit"
                                    class="min-h-[40px] px-4 bg-primary text-white font-bold rounded-lg text-sm">Xác nhận đơn</button>
                            </form>
                        @endif
                    @endif

                    {{-- Đơn tại quầy: khách nhận trực tiếp, không có bước giao hàng --}}
                    @if ($order->delivery_type === 'pickup' && in_array($order->status, ['confirmed', 'shipping'], true))
                        <form action="{{ route('staff.reception.orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit"
                                class="min-h-[40px] px-4 bg-emerald-600 text-white font-bold rounded-lg text-sm">Hoàn thành</button>
                        </form>
                    @endif

                    @if ($canCancel)
                        <form id="cancel-order-form"
                            action="{{ route('staff.reception.orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="cancel_reason" id="cancel_reason_input">
                            <button type="button" id="cancel-order-btn"
                                class="min-h-[40px] px-4 bg-red-50 text-red-600 border border-red-200 font-bold rounded-lg text-sm">Hủy đơn</button>
                        </form>
                    @elseif($canRefundAndCancel)
                        <form id="refund-cancel-order-form"
                            action="{{ route('staff.reception.orders.refund', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="cancel_reason" id="refund_cancel_reason_input">
                            <button type="button" id="refund-cancel-order-btn"
                                class="min-h-[40px] px-4 bg-red-50 text-red-600 border border-red-200 font-bold rounded-lg text-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-money-bill-transfer text-xs"></i> Hoàn tiền & Hủy
                                đơn
                            </button>
                        </form>
                    @endif

                    @if ($isDeliveryOrder && $order->status === 'shipping')
                        <p class="text-xs text-gray-400">Đơn đang được giao — chỉ nhân viên vận chuyển được cập nhật tiếp.
                        </p>
                    @elseif(in_array($order->status, ['completed', 'cancelled']))
                        <p class="text-xs text-gray-400">Đơn đã kết thúc, không thể thay đổi thêm.</p>
                    @elseif(!$canCancel && !$canRefundAndCancel && in_array($order->status, ['pending', 'confirmed']))
                        <p class="text-xs text-gray-400">Đơn đã thanh toán — cần hoàn tiền trước khi hủy.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tình TRẠNG thanh toán --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                <i class="fa-solid fa-money-bill-wave text-gray-400 text-base"></i>
                Thanh toán
            </h3>
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-bold text-gray-900 uppercase">
                        {{ match ($order->payment_method) {'vnpay' => 'Chuyển khoản (VNPay)','cash' => 'Tiền mặt',default => 'COD'} }}
                    </div>
                    @if ($order->payment_method === 'vnpay')
                        @if (($order->payment_status ?? '') === 'paid')
                            <div class="text-sm font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                                <i class="fa-solid fa-circle-check text-xs"></i> Đã thanh toán
                            </div>
                        @elseif(($order->payment_status ?? '') === 'refunded')
                            <div class="text-sm font-semibold text-slate-600 flex items-center gap-1 mt-1">
                                <i class="fa-solid fa-rotate-left text-xs"></i> Đã hoàn tiền
                            </div>
                            @if ($order->refunded_at)
                                <p class="text-xs text-gray-500 mt-1">Lúc
                                    {{ \Carbon\Carbon::parse($order->refunded_at)->format('H:i d/m/Y') }}</p>
                            @endif
                        @else
                            <div class="text-sm font-semibold text-amber-600 flex items-center gap-1 mt-1">
                                <i class="fa-solid fa-clock text-xs"></i> Chờ thanh toán
                            </div>
                        @endif
                    @elseif($order->payment_method === 'cash')
                        @if ($order->payment_status === 'paid')
                            <div class="text-sm font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                                <i class="fa-solid fa-circle-check text-xs"></i> Đã thu tiền mặt
                            </div>
                            @if ($order->amount_tendered !== null)
                                <p class="text-xs text-gray-500 mt-1">
                                    Khách đưa: {{ number_format($order->amount_tendered, 0, ',', '.') }}đ
                                    · Thối lại:
                                    {{ number_format(max(0, $order->amount_tendered - $order->final_amount), 0, ',', '.') }}đ
                                </p>
                            @endif
                        @else
                            <div class="text-sm font-semibold text-amber-600 flex items-center gap-1 mt-1">
                                <i class="fa-solid fa-clock text-xs"></i> Chờ thu tiền
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-gray-500 mt-1">Thanh toán khi nhận hàng</div>
                    @endif
                </div>
                <i class="fa-solid {{ $order->payment_method === 'vnpay' ? 'fa-wallet' : 'fa-money-bill-wave' }} text-4xl text-gray-200"></i>
            </div>

            @if ($order->payment_method === 'vnpay' && !in_array($order->payment_status, ['paid', 'refunded'], true))
                <form action="{{ route('staff.reception.orders.pay_online', $order->id) }}" method="POST"
                    class="mt-4">
                    @csrf
                    <button type="submit" class="w-full min-h-[44px] bg-pink-600 text-white font-bold rounded-xl">
                        Thanh toán chuyển khoản (VNPay)
                    </button>
                </form>
            @endif

            @if ($order->payment_method === 'cash' && $order->payment_status !== 'paid')
                <form action="{{ route('staff.reception.orders.confirm_cash', $order->id) }}" method="POST"
                    class="mt-4 space-y-2 pt-4 border-t border-gray-100">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">Tiền khách đưa</label>
                    <input type="text" id="cash-amount-tendered-display"
                        value="{{ old('amount_tendered', (int) $order->final_amount) }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" required>
                    <input type="hidden" name="amount_tendered" id="cash-amount-tendered"
                        value="{{ old('amount_tendered', (int) $order->final_amount) }}">
                    @error('amount_tendered')
                        <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Tiền thừa</span>
                        <span id="cash-change-preview" class="font-bold text-gray-900">0đ</span>
                    </div>
                    <input type="hidden" id="cash-final-amount" value="{{ (int) $order->final_amount }}">
                    <button type="submit" class="w-full min-h-[44px] bg-emerald-600 text-white font-bold rounded-xl">
                        Xác nhận đã thu tiền
                    </button>
                </form>
            @endif
        </div>

        {{-- Lưới giao diện chính --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- CỘT trái: Danh sách món ăn và tổng tiền --}}
            <div class="lg:col-span-2 flex flex-col gap-6">

                {{-- Khối 1: Danh sách món ăn --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-bold text-gray-900 text-lg">Chi tiết món</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            <div class="p-5 flex gap-4">
                                <div
                                    class="w-20 h-20 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden flex-shrink-0">
                                    @if ($item->product_image)
                                        <img src="{{ $item->product_image_url }}" alt="{{ $item->product_name }}"
                                            class="w-full h-full object-cover"
                                            data-fallback-src="{{ asset('images/products/placeholder.jpg') }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i class="fa-solid fa-mug-hot text-3xl"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1 flex flex-col justify-between overflow-hidden">
                                    @php
                                        $iceLabels = [
                                            'normal' => 'Đá chung',
                                            'full' => 'Đá riêng',
                                            'less' => 'Ít đá',
                                            'none' => 'Không đá',
                                        ];
                                        $itemToppings = is_array($item->options) ? $item->options : [];
                                    @endphp
                                    <div
                                        class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1 sm:gap-2">
                                        <div class="break-words overflow-wrap-anywhere">
                                            <h4 class="font-bold text-gray-900">{{ $item->product_name }}</h4>
                                            <div class="text-sm text-gray-500 mt-1 flex flex-wrap gap-1">
                                                @if ($item->size_name)
                                                    <span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-medium">Size
                                                        {{ $item->size_name }}</span>
                                                @endif
                                                @if ($item->sugar_level !== null)
                                                    <span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-medium">Đường
                                                        {{ $item->sugar_level }}%</span>
                                                @endif
                                                @if ($item->ice_level)
                                                    <span
                                                        class="bg-gray-100 px-2 py-0.5 rounded text-xs font-medium">{{ $iceLabels[$item->ice_level] ?? $item->ice_level }}</span>
                                                @endif
                                            </div>
                                            @if (!empty($itemToppings))
                                                <p class="text-xs text-gray-500 mt-1">+ Topping:
                                                    {{ implode(', ', $itemToppings) }}</p>
                                            @endif
                                            @if ($item->note)
                                                <p class="text-xs text-amber-600 mt-1">Ghi chú: {{ $item->note }}</p>
                                            @endif
                                        </div>
                                        <div class="text-left sm:text-right shrink-0">
                                            <span
                                                class="font-bold text-gray-900">{{ number_format($item->unit_price, 0, ',', '.') }}đ</span>
                                            <span
                                                class="text-sm text-gray-500 ml-2 sm:ml-0 sm:block">x{{ $item->quantity }}</span>
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
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính ({{ $items->sum('quantity') }} món)</span>
                            <span class="font-medium">{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí giao hàng ({{ $order->distance_km ?? 0 }}km)</span>
                            <span class="font-medium">{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }}đ</span>
                        </div>
                        @if ($order->weather_fee > 0)
                            <div class="flex justify-between text-gray-600">
                                <span>Phụ thu thời tiết xấu</span>
                                <span class="font-medium">{{ number_format($order->weather_fee, 0, ',', '.') }}đ</span>
                            </div>
                        @endif
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between text-emerald-600">
                                <span>Khuyến mãi {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}</span>
                                <span
                                    class="font-medium">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                            </div>
                        @endif
                        @if ($order->points_redeemed > 0)
                            <div class="flex justify-between text-emerald-600 text-sm">
                                <span>Đã dùng điểm tích lũy</span>
                                <span class="font-medium">{{ number_format($order->points_redeemed, 0, ',', '.') }} điểm</span>
                            </div>
                        @endif
                        <div class="pt-4 mt-2 border-t border-gray-100 flex justify-between items-center">
                            <span class="font-bold text-gray-900 text-lg">Tổng cộng</span>
                            <span
                                class="font-bold text-primary text-xl">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CỘT PHẢI: Khách hàng, Giao hàng --}}
            <div class="flex flex-col gap-6">

                {{-- Khối 3: Thông tin Khách hàng --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-user text-gray-400 text-base"></i>
                        Khách hàng
                    </h3>
                    <div class="flex items-center gap-3 mb-4">
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
                        <i class="fa-solid fa-truck text-gray-400 text-base"></i>
                        Giao hàng
                    </h3>
                    <div class="flex flex-col gap-4">
                        @if ($order->delivery_type === 'pickup')
                            <div>
                                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Hình thức nhận hàng</p>
                                <p class="text-sm text-gray-900">Khách nhận tại quầy (không cần giao hàng)</p>
                            </div>
                        @else
                            <div>
                                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Địa chỉ giao
                                </p>
                                <p class="text-sm text-gray-900">{{ $order->delivery_address }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Nhân viên giao hàng</p>
                                @if ($order->deliveryStaff)
                                    <p class="text-sm font-semibold text-gray-900">{{ $order->deliveryStaff->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $order->deliveryStaff->phone ?: 'Chưa cập nhật SĐT' }}
                                        @if ($order->assigned_at)
                                            · Phân công lúc
                                            {{ \Carbon\Carbon::parse($order->assigned_at)->format('d/m/Y H:i') }}
                                        @endif
                                    </p>
                                @elseif($order->status === 'confirmed')
                                    <form action="{{ route('staff.reception.orders.assign_delivery', $order->id) }}"
                                        method="POST" class="flex flex-col gap-2 mt-1">
                                        @csrf
                                        {{-- Custom-select-init thay khung sổ xuống mặc định --}}
                                        <select name="delivery_staff_id" required
                                            class="custom-select-init w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                            <option value="">-- Chọn nhân viên giao hàng --</option>
                                            @foreach ($deliveryStaffs as $staff)
                                                <option value="{{ $staff->id }}">
                                                    {{ $staff->name }}{{ $staff->phone ? ' — ' . $staff->phone : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($deliveryStaffs->isEmpty())
                                            <p class="text-xs text-red-500">Chưa có nhân viên giao hàng nào đang hoạt động.
                                            </p>
                                        @else
                                            <button type="submit"
                                                class="w-full min-h-[40px] bg-primary text-white font-bold rounded-lg text-sm">
                                                Phân công giao hàng
                                            </button>
                                        @endif
                                    </form>
                                @elseif($order->status === 'pending')
                                    <p class="text-sm text-gray-500">Cần xác nhận đơn trước khi phân công giao hàng.</p>
                                @else
                                    <p class="text-sm text-gray-500">Chưa được phân công.</p>
                                @endif
                            </div>
                        @endif
                        @if ($order->customer_note)
                            <div>
                                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Ghi chú của khách</p>
                                <p class="text-sm text-gray-900 bg-yellow-50 p-3 rounded-lg border border-yellow-100">
                                    {{ $order->customer_note }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    @php
        $printIceLabels = ['normal' => 'Đá chung', 'full' => 'Đá riêng', 'less' => 'Ít đá', 'none' => 'Không đá'];
        $pickupModeLabels = ['dine_in' => 'Tại quầy', 'takeaway' => 'Mang đi'];
    @endphp

    {{-- Phiếu pha chế: --}}
    <div id="print-prep-ticket" class="hidden">
        <div class="print-ticket">
            <h2 class="print-ticket__title">PHIẾU PHA CHẾ</h2>
            <p class="print-ticket__subtitle">
                {{ $order->delivery_type === 'pickup' ? $pickupModeLabels[$order->pickup_mode] ?? 'Tại quầy' : 'Giao hàng' }}
            </p>
            <hr>
            <p class="print-ticket__row"><strong>Mã đơn:</strong> {{ $order->order_code }}</p>
            <p class="print-ticket__row"><strong>Giờ tạo:</strong>
                {{ \Carbon\Carbon::parse($order->created_at)->format('H:i d/m/Y') }}</p>
            <hr>
            @foreach ($items as $item)
                @php $printToppings = is_array($item->options) ? $item->options : []; @endphp
                <div class="print-ticket__item">
                    <p class="print-ticket__item-name">{{ $item->quantity }} x {{ $item->product_name }}</p>
                    <p class="print-ticket__item-detail">
                        @if ($item->size_name)
                            Size {{ $item->size_name }}
                        @endif
                        @if ($item->sugar_level !== null)
                            · Đường {{ $item->sugar_level }}%
                        @endif
                        @if ($item->ice_level)
                            · {{ $printIceLabels[$item->ice_level] ?? $item->ice_level }}
                        @endif
                    </p>
                    @if (!empty($printToppings))
                        <p class="print-ticket__item-detail">+ {{ implode(', ', $printToppings) }}</p>
                    @endif
                    @if ($item->note)
                        <p class="print-ticket__item-note">Ghi chú: {{ $item->note }}</p>
                    @endif
                </div>
            @endforeach
            @if ($order->customer_note)
                <hr>
                <p class="print-ticket__note"><strong>Ghi chú đơn:</strong> {{ $order->customer_note }}</p>
            @endif
        </div>
    </div>

    {{-- Hóa ĐƠN khách hàng: đầy đủ giá + tổng + phương --}}
    <div id="print-customer-invoice" class="hidden">
        <div class="print-ticket">
            <h2 class="print-ticket__title--invoice">{{ $storeInfo['name'] }}</h2>
            @if ($storeInfo['address'])
                <p class="print-ticket__center-sm">{{ $storeInfo['address'] }}</p>
            @endif
            @if ($storeInfo['phone'])
                <p class="print-ticket__center-sm-mb">ĐT: {{ $storeInfo['phone'] }}</p>
            @endif
            <p class="print-ticket__center-bold">HÓA ĐƠN BÁN HÀNG</p>
            <hr>
            <p class="print-ticket__row-sm">Mã đơn: {{ $order->order_code }}</p>
            <p class="print-ticket__row-sm">Ngày: {{ \Carbon\Carbon::parse($order->created_at)->format('H:i d/m/Y') }}</p>
            <hr>
            @foreach ($items as $item)
                <div class="print-ticket__flex-row">
                    <span>{{ $item->product_name }}{{ $item->size_name ? ' (' . $item->size_name . ')' : '' }}
                        x{{ $item->quantity }}</span>
                    <span>{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</span>
                </div>
            @endforeach
            <hr>
            <div class="print-ticket__flex-row--plain">
                <span>Tạm tính</span>
                <span>{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
            </div>
            @if ($order->discount_amount > 0)
                <div class="print-ticket__flex-row--plain">
                    <span>Giảm giá{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</span>
                    <span>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                </div>
            @endif
            @if ($order->shipping_fee > 0)
                <div class="print-ticket__flex-row--plain">
                    <span>Phí giao hàng</span>
                    <span>{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</span>
                </div>
            @endif
            <div class="print-ticket__flex-row--total">
                <span>TỔNG CỘNG</span>
                <span>{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
            </div>
            <hr>
            <p class="print-ticket__row-sm">
                Thanh toán:
                {{ match ($order->payment_method) {'vnpay' => 'VNPay','cash' => 'Tiền mặt',default => 'COD'} }}
            </p>
            @if ($order->payment_method === 'cash' && $order->amount_tendered !== null)
                <div class="print-ticket__flex-row--small">
                    <span>Khách đưa</span>
                    <span>{{ number_format($order->amount_tendered, 0, ',', '.') }}đ</span>
                </div>
                <div class="print-ticket__flex-row--small">
                    <span>Tiền thừa</span>
                    <span>{{ number_format(max(0, $order->amount_tendered - $order->final_amount), 0, ',', '.') }}đ</span>
                </div>
            @endif
            <hr>
            <p class="print-ticket__footer">Cảm ơn quý khách!</p>
        </div>
    </div>

    @push('scripts')
        <script>
            // Thay bằng ảnh mặc định khi ảnh sản phẩm bị lỗi hoặc đã bị xóa khỏi máy chủ
            function applyFallbackImage(image) {
                if (image.dataset.fallbackApplied === "true") return;
                image.dataset.fallbackApplied = "true";
                image.src = image.dataset.fallbackSrc;
            }

            // In một khu vực của trang bằng cách gắn class riêng lên body rồi gọi window.print
            function printSection(bodyClass) {
                document.body.classList.add("pos-printing-ticket", bodyClass);

                // Gỡ class in khỏi body sau khi in xong; có hẹn giờ 3 giây dự phòng vì sự kiện afterprint không phải trình duyệt nào cũng bắn
                function cleanup() {
                    document.body.classList.remove("pos-printing-ticket", bodyClass);
                    window.removeEventListener("afterprint", cleanup);
                }
                window.addEventListener("afterprint", cleanup);
                setTimeout(cleanup, 3000);

                window.print();
            }

            // Bắt buộc nhập lý do hủy qua hộp thoại rồi mới gửi form, không cho hủy đơn suông
            function askCancelReasonAndSubmit(form, reasonInput, message) {
                const reason = prompt(message);
                if (reason === null) return;

                if (reason.trim().length < 5) {
                    alert('Lý do hủy đơn phải có ít nhất 5 ký tự.');
                    return;
                }

                reasonInput.value = reason.trim();
                form.submit();
            }

            // Khởi tạo trang chi tiết đơn hàng
            function initOrderShowPage() {
                const prepTicketBtn = document.getElementById("print-prep-ticket-btn");
                if (prepTicketBtn) {
                    prepTicketBtn.addEventListener("click", function() {
                        printSection("pos-printing-prep");
                    });
                }

                const invoiceBtn = document.getElementById("print-invoice-btn");
                if (invoiceBtn) {
                    invoiceBtn.addEventListener("click", function() {
                        printSection("pos-printing-invoice");
                    });
                }

                const tenderedDisplay = document.getElementById("cash-amount-tendered-display");
                const tenderedInput = document.getElementById("cash-amount-tendered");
                const changePreview = document.getElementById("cash-change-preview");
                const finalAmountInput = document.getElementById("cash-final-amount");
                if (tenderedDisplay && tenderedInput && changePreview && finalAmountInput) {
                    const finalAmount = Number(finalAmountInput.value);

                    // Định dạng số tiền để hiển thị
                    const formatValue = function(val) {
                        let raw = String(val).replace(/[^0-9]/g, '');
                        if (raw.length > 10) raw = raw.slice(0, 10);
                        tenderedInput.value = raw;
                        tenderedDisplay.value = raw === '' ? '' : new Intl.NumberFormat('vi-VN').format(parseInt(raw));

                        const tendered = Number(raw) || 0;
                        const change = Math.max(0, tendered - finalAmount);
                        changePreview.textContent = change.toLocaleString("vi-VN") + "đ";
                    };

                    if (tenderedDisplay.value) {
                        formatValue(tenderedDisplay.value);
                    }

                    tenderedDisplay.addEventListener("input", function() {
                        const selectionStart = this.selectionStart;
                        const prevLen = this.value.length;

                        formatValue(this.value);

                        const newLen = this.value.length;
                        const diff = newLen - prevLen;
                        const newPos = Math.max(0, selectionStart + diff);
                        this.setSelectionRange(newPos, newPos);
                    });
                }

                document.querySelectorAll("img[data-fallback-src]").forEach((image) => {
                    image.addEventListener("error", function() {
                        applyFallbackImage(this);
                    });

                    if (image.complete && image.naturalWidth === 0) {
                        applyFallbackImage(image);
                    }
                });

                const cancelBtn = document.getElementById('cancel-order-btn');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function() {
                        askCancelReasonAndSubmit(
                            document.getElementById('cancel-order-form'),
                            document.getElementById('cancel_reason_input'),
                            'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):'
                        );
                    });
                }

                const refundCancelBtn = document.getElementById('refund-cancel-order-btn');
                if (refundCancelBtn) {
                    refundCancelBtn.addEventListener('click', function() {
                        askCancelReasonAndSubmit(
                            document.getElementById('refund-cancel-order-form'),
                            document.getElementById('refund_cancel_reason_input'),
                            'Hệ thống sẽ gọi hoàn tiền cho khách rồi hủy đơn — không thể hoàn tác. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):'
                        );
                    });
                }
            }

            initOrderShowPage();
        </script>
    @endpush
@endsection
