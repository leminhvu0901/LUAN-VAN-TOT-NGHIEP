@extends('backend.layouts.app')

@section('title', 'Chi tiết Khách hàng - ' . $customer->name)

@section('content')
    <div class="flex flex-col gap-6 h-full pb-4">

        {{-- PHẦN 1: HEADER (Tiêu đề, Trạng thái) --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    {{-- Nút quay lại trang danh sách --}}
                    <a href="{{ route('admin.customers.index') }}"
                        onclick="smartGoBack(event)"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                    </a>

                    {{-- Tiêu đề --}}
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Chi tiết Khách hàng</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($customer->is_active)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-sm font-semibold shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Đang hoạt động
                    </span>
                @else
                    <div class="flex flex-col items-end gap-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg text-sm font-semibold shadow-sm" {!! $customer->lock_reason ? 'title="Lý do: '.e($customer->lock_reason).'"' : '' !!}>
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Đang bị khóa
                        </span>
                        @if($customer->lock_reason)
                            <span class="text-xs text-rose-500 font-medium bg-rose-50 px-2 py-0.5 rounded border border-rose-100">Lý do: {{ $customer->lock_reason }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- PHẦN 2: LƯỚI GIAO DIỆN CHÍNH --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- CỘT TRÁI (Chiếm 1/3 không gian): Thông Đài cá nhân & Thống kê --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                
                {{-- Card Thông tin cá nhân --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900">Thông tin cá nhân</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-col items-center mb-6">
                            @php
                                if ($customer->avatar) {
                                    $avatarUrl = str_starts_with($customer->avatar, 'http') ? $customer->avatar : asset('images/avatars/' . $customer->avatar);
                                } else {
                                    $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($customer->name).'&background=random';
                                }
                            @endphp
                            <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($customer->name) }}&background=random'" class="w-24 h-24 rounded-full object-cover border-4 border-gray-50 shadow-sm mb-3">
                            <h2 class="text-xl font-bold text-gray-900 text-center">{{ $customer->name }}</h2>
                            <p class="text-sm text-gray-500 mt-1">Khách hàng từ {{ \Carbon\Carbon::parse($customer->created_at)->format('d/m/Y') }}</p>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <span class="material-symbols-outlined text-[20px] text-gray-400">mail</span>
                                <span>{{ $customer->email }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <span class="material-symbols-outlined text-[20px] text-gray-400">call</span>
                                <span>{{ $customer->phone ?? 'Chưa cập nhật SĐT' }}</span>
                            </div>
                            <div class="flex items-start gap-3 text-sm text-gray-600">
                                <span class="material-symbols-outlined text-[20px] text-gray-400 mt-0.5">location_on</span>
                                <span>{{ $customer->address ?? 'Chưa cập nhật địa chỉ' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Thống kê & Hạng --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900">Thông tin mua hàng</h3>
                    </div>
                    <div class="p-6 space-y-5">
                        {{-- Hạng --}}
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Hạng thành viên</p>
                            @php
                                $badgeClass = '';
                                $badgeName = '';
                                switch ($customer->membership_level) {
                                    case 'diamond':
                                        $badgeClass = 'bg-blue-100 text-blue-700 border-blue-200';
                                        $badgeName = 'Kim Cương';
                                        break;
                                    case 'gold':
                                        $badgeClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                        $badgeName = 'Vàng';
                                        break;
                                    case 'silver':
                                        $badgeClass = 'bg-gray-200 text-gray-700 border-gray-300';
                                        $badgeName = 'Bạc';
                                        break;
                                    case 'new':
                                    default:
                                        $badgeClass = 'bg-green-100 text-green-700 border-green-200';
                                        $badgeName = 'Mới';
                                        break;
                                }
                            @endphp
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold border {{ $badgeClass }}">
                                <span class="material-symbols-outlined text-[18px]">workspace_premium</span>
                                {{ $badgeName }}
                            </span>
                        </div>

                        {{-- Điểm --}}
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Điểm tích lũy</p>
                            <p class="text-xl font-bold text-gray-900">{{ number_format($customer->points ?? 0) }} <span class="text-sm font-normal text-gray-500">điểm</span></p>
                        </div>
                        
                        <hr class="border-gray-100">

                        {{-- Tổng chi tiêu --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Tổng đơn</p>
                                <p class="text-lg font-bold text-gray-900">{{ $totalOrders }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Tổng chi tiêu</p>
                                <p class="text-lg font-bold text-indigo-600">{{ number_format($totalSpent ?? 0, 0, ',', '.') }} đ</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- CỘT PHẢI (Chiếm 2/3 không gian): Lịch sử đơn hàng --}}
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden h-full">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-900">5 Đơn hàng gần nhất</h3>
                        <a href="{{ route('admin.orders.index') }}?search={{ $customer->phone ?? $customer->email }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                            Xem tất cả &rarr;
                        </a>
                    </div>
                    
                    <div class="p-0 overflow-x-auto">
                        @if($recentOrders->count() > 0)
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 border-b border-gray-100">
                                        <th class="px-5 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã đơn</th>
                                        <th class="px-5 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Ngày đặt</th>
                                        <th class="px-5 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right">Tổng tiền</th>
                                        <th class="px-5 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Trạng thái</th>
                                        <th class="px-5 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right">Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($recentOrders as $order)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-5 py-4">
                                                <span class="font-semibold text-indigo-600">{{ $order->order_code ?? ('#HPY-'.$order->id) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-600">
                                                {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="font-bold text-gray-900">{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                @php
                                                    $statusColor = '';
                                                    $statusLabel = '';
                                                    switch ($order->status) {
                                                        case 'pending': $statusColor = 'bg-yellow-100 text-yellow-700 border-yellow-200'; $statusLabel = 'Chờ xử lý'; break;
                                                        case 'processing': $statusColor = 'bg-blue-100 text-blue-700 border-blue-200'; $statusLabel = 'Đang chuẩn bị'; break;
                                                        case 'delivering': $statusColor = 'bg-indigo-100 text-indigo-700 border-indigo-200'; $statusLabel = 'Đang giao'; break;
                                                        case 'completed': $statusColor = 'bg-green-100 text-green-700 border-green-200'; $statusLabel = 'Hoàn thành'; break;
                                                        case 'cancelled': $statusColor = 'bg-red-100 text-red-700 border-red-200'; $statusLabel = 'Đã hủy'; break;
                                                        default: $statusColor = 'bg-gray-100 text-gray-700 border-gray-200'; $statusLabel = 'Chưa rõ'; break;
                                                    }
                                                @endphp
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusColor }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('admin.orders.show', $order->id) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                    <span class="material-symbols-outlined text-3xl text-gray-400">receipt_long</span>
                                </div>
                                <h3 class="text-base font-medium text-gray-900 mb-1">Chưa có đơn hàng nào</h3>
                                <p class="text-gray-500 text-sm">Khách hàng này chưa thực hiện bất kỳ giao dịch nào.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
