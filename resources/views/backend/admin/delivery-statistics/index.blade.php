@extends('backend.layouts.app')

@section('title', 'Thống kê giao hàng - Admin')

@section('content')
    <div class="delivery-statistics-page space-y-6 sm:space-y-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Thống kê giao hàng</h1>
                <p class="text-gray-500 text-sm mt-1">Số đơn hàng giao thành công của từng nhân viên giao hàng theo khoảng thời gian.</p>
            </div>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="stat-card group bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-5 border border-indigo-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-indigo-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">local_shipping</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-indigo-100 flex items-center justify-center text-indigo-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">local_shipping</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Nhân viên giao hàng</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($totalDeliveryStaff) }}</p>
                </div>
            </div>

            <div class="stat-card group bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl p-5 border border-emerald-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-emerald-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">check_circle</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-emerald-100 flex items-center justify-center text-emerald-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">check_circle</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đang hoạt động</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($activeDeliveryStaff) }}</p>
                </div>
            </div>

            <div class="stat-card group bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-2xl p-5 border border-amber-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-amber-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">inventory_2</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-amber-100 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">inventory_2</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đơn đã giao (trong kỳ)</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($totalCompletedOrders) }}</p>
                </div>
            </div>
        </div>

        <!-- Bộ lọc thời gian -->
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100">
            <form action="{{ route('admin.delivery_statistics.index') }}" method="GET" id="filter-form"
                class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-end w-full">

                <div class="flex flex-col gap-2 w-full sm:w-56">
                    <label for="preset-select" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Khoảng thời gian</label>
                    <select name="preset" id="preset-select" onchange="deliveryStatsHandlePresetChange(this)"
                        class="px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
                        <option value="today" {{ $preset == 'today' ? 'selected' : '' }}>Hôm nay</option>
                        <option value="this_week" {{ $preset == 'this_week' ? 'selected' : '' }}>Tuần này</option>
                        <option value="this_month" {{ $preset == 'this_month' ? 'selected' : '' }}>Tháng này</option>
                        <option value="this_year" {{ $preset == 'this_year' ? 'selected' : '' }}>Năm này</option>
                        <option value="custom" {{ $preset == 'custom' ? 'selected' : '' }}>Khoảng ngày tùy chọn</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 w-full sm:w-44 {{ $preset == 'custom' ? '' : 'hidden' }}" id="date-from-container">
                    <label for="date_from" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Từ ngày</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from', $start->toDateString()) }}"
                        class="px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                </div>

                <div class="flex flex-col gap-2 w-full sm:w-44 {{ $preset == 'custom' ? '' : 'hidden' }}" id="date-to-container">
                    <label for="date_to" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Đến ngày</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to', $end->toDateString()) }}"
                        class="px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all font-medium text-sm shadow-sm hover:shadow-md whitespace-nowrap">
                        <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                        Lọc
                    </button>
                </div>
            </form>
            <p class="text-xs text-gray-400 mt-3">Đang xem: <span class="font-semibold text-gray-600">{{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</span></p>
        </div>

        <!-- Bảng thống kê -->
        <div class="bg-white rounded-xl organic-shadow border border-gray-100 overflow-hidden">
            @if ($staffs->count() > 0)
                <!-- Giao diện Mobile (Card view) -->
                <div class="block md:hidden space-y-4 p-4">
                    @foreach ($staffs as $staff)
                        @php
                            $avatarUrl = $staff->avatar
                                ? (avatar_url($staff->avatar))
                                : 'https://ui-avatars.com/api/?name=' . urlencode($staff->name) . '&background=random';
                        @endphp
                        <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $avatarUrl }}" alt="{{ $staff->name }}" class="w-11 h-11 rounded-full object-cover border border-gray-200 shrink-0">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-sm font-bold text-gray-900 truncate" title="{{ $staff->name }}">{{ $staff->name }}</span>
                                    <span class="text-xs {{ $staff->is_active ? 'text-emerald-600' : 'text-rose-500' }}">{{ $staff->is_active ? 'Hoạt động' : 'Bị khóa' }}</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-gray-100">
                                <div class="text-center">
                                    <p class="text-lg font-extrabold text-emerald-600">{{ number_format($staff->completed_orders_count) }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Thành công</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-extrabold text-rose-500">{{ number_format($staff->failed_orders_count) }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Thất bại</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-extrabold text-gray-900">{{ number_format($staff->total_orders_count) }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Tổng đơn</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Giao diện Desktop (Table view) -->
                <div class="hidden md:block overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Nhân viên giao hàng</th>
                                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Số điện thoại</th>
                                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Trạng thái</th>
                                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Giao thành công</th>
                                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Giao thất bại</th>
                                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Tổng đơn</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($staffs as $staff)
                                @php
                                    $avatarUrl = $staff->avatar
                                        ? (avatar_url($staff->avatar))
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($staff->name) . '&background=random';
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <img src="{{ $avatarUrl }}" alt="{{ $staff->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                            <div class="font-bold text-gray-900 truncate" title="{{ $staff->name }}">{{ $staff->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ $staff->phone ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[10px] font-bold {{ $staff->is_active ? 'text-emerald-600' : 'text-rose-500' }}">
                                            {{ $staff->is_active ? 'Hoạt động' : 'Bị khóa' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-extrabold text-sm">
                                            {{ number_format($staff->completed_orders_count) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 rounded-full bg-rose-50 text-rose-600 font-extrabold text-sm">
                                            {{ number_format($staff->failed_orders_count) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-extrabold text-sm">
                                            {{ number_format($staff->total_orders_count) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-16 px-4 bg-white w-full">
                    <span class="material-symbols-outlined text-6xl text-gray-200 mb-4 select-none">local_shipping</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Chưa có nhân viên giao hàng nào</h3>
                    <p class="text-gray-500 text-sm max-w-sm mx-auto">Thêm nhân viên và gán loại "Nhân viên giao hàng" trong trang Quản lý Nhân viên.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Chọn nhanh hôm nay/tuần này/tháng này; chọn "tùy chọn" mới hiện 2 ô nhập ngày thủ công
        function deliveryStatsHandlePresetChange(select) {
            const isCustom = select.value === 'custom';
            document.getElementById('date-from-container').classList.toggle('hidden', !isCustom);
            document.getElementById('date-to-container').classList.toggle('hidden', !isCustom);
            if (!isCustom) {
                select.closest('form').submit();
            }
        }
    </script>
@endsection
