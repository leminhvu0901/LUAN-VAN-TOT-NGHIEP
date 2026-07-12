@extends('backend.layouts.app')

@section('title', 'Lô Hàng Đã Hết Hạn')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-4 sm:space-y-6 mat-animate">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center flex-wrap gap-1.5 text-sm text-gray-400 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('admin.materials.index') }}" class="hover:text-gray-600 transition-colors">Quản lý Kho</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-gray-700 font-medium">Lô Hàng Đã Hết Hạn</span>
            </div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-start sm:items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-red-100 to-red-200 text-red-600 shadow-inner border border-red-50 flex-shrink-0">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">delete_forever</span>
                </span>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">Lô Hàng Đã Hết Hạn</span>
            </h1>
            <p class="text-sm text-gray-500 mt-2 sm:mt-1 sm:ml-[58px]">Các lô hàng quá hạn sử dụng còn tồn trong kho — cần xử lý huỷ bỏ ngay.</p>
        </div>
        <a href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/80 backdrop-blur-md border border-gray-200/80 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:shadow hover:-translate-y-0.5 transition-all duration-300 w-full md:w-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="mat-stat-card group bg-gradient-to-br from-red-50 to-red-100/50 rounded-2xl p-5 border border-red-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
            <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-red-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">inventory_2</span>
            <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-red-100 flex items-center justify-center text-red-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">inventory_2</span>
            </div>
            <div class="z-10">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng lô hết hạn <span id="label-count-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-extrabold text-gray-900 tracking-tight" id="stat-count">{{ $expiredBatches->count() }}</p>
            </div>
        </div>
        <div class="mat-stat-card mat-disposed-glow group bg-gradient-to-br from-red-50 to-red-100/50 rounded-2xl p-5 border border-red-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 sm:col-span-2 relative overflow-hidden">
            <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-red-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">monetization_on</span>
            <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-red-100 flex items-center justify-center text-red-600 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">monetization_on</span>
            </div>
            <div class="min-w-0 z-10">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Giá trị mất mát ước tính <span id="label-val-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-extrabold text-red-700 tracking-tight truncate" id="total-expired-value">{{ number_format($expiredValue, 0, ',', '.') }}đ</p>
                <p class="text-xs text-red-400 mt-0.5">Giá trị hàng hoá bị hết hạn</p>
            </div>
        </div>
    </div>

    {{-- ===== TABLE CARD ===== --}}
    <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100/80 overflow-hidden flex flex-col">

        {{-- ===== TOOLBAR ===== --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 backdrop-blur-sm">
            <div class="flex flex-col gap-3">

                {{-- Row 1: Title + Search --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-base text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-red-500 text-[20px]">list_alt</span>
                            Danh Sách Chi Tiết
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">Nhấn <strong>"Xử lý huỷ"</strong> để tiến hành huỷ bỏ lô hàng hết hạn</p>
                    </div>

                    {{-- Search --}}
                    <div class="flex items-center gap-2 bg-white border border-gray-200 hover:border-red-300 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-500 focus-within:ring-4 focus-within:ring-red-500/10 transition-all duration-300 w-full sm:w-auto">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-red-500 transition-colors">search</span>
                        <input type="text" id="search-expired" onkeyup="filterExpiredTable()"
                            placeholder="Tìm tên vật tư, mã lô..."
                            class="text-sm text-gray-700 bg-transparent border-none p-0 focus:ring-0 outline-none w-full sm:w-60 placeholder-gray-400">
                    </div>
                </div>

            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto flex-1">
            <table id="table-expired" data-total-id="total-expired-value" data-highlight="bg-red-50" class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                    <tr>
                        <th class="px-6 py-3.5 w-10 text-center">
                            <input type="checkbox" id="check-all" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Tên vật tư</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã lô</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right">Số lượng tồn</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right">Giá trị (VNĐ)</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Hạn sử dụng</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="table-body">
                    @forelse($expiredBatches as $batch)
                        @php
                            $unitPrice  = $batch->quantity > 0 ? ($batch->total_price / $batch->quantity) : 0;
                            $batchValue = $batch->remaining_quantity * $unitPrice;
                            $daysExpired = abs((int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($batch->expiration_date)->startOfDay(), false));
                        @endphp
                        <tr class="hover:bg-red-50/40 transition-colors duration-200 group mat-table-row"
                            data-value="{{ $batchValue }}"
                            data-sort-name="{{ $batch->material->name }}"
                            data-date="{{ \Carbon\Carbon::parse($batch->expiration_date)->format('Y-m-d') }}">
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer" onchange="calculateSelected()">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-red-50 to-red-100 border border-red-100 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                        <span class="material-symbols-outlined text-red-500 text-[18px]" style="font-variation-settings:'FILL' 1">delete_forever</span>
                                    </div>
                                    <span class="font-bold text-sm text-gray-900">{{ $batch->material->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-xs font-bold font-mono">LOT-{{ $batch->id }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700 text-right">
                                {{ number_format($batch->remaining_quantity, 2, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400 ml-1">{{ $batch->material->unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-50 text-red-700 text-sm font-bold border border-red-100">
                                    {{ number_format($batchValue, 0, ',', '.') }}đ
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-red-600">{{ \Carbon\Carbon::parse($batch->expiration_date)->format('d/m/Y') }}</span>
                                    <span class="text-xs text-red-400">Đã quá {{ $daysExpired }} ngày</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.materials.imports', $batch->material_id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl shadow-md shadow-red-500/20 hover:shadow-lg hover:shadow-red-500/40 hover:-translate-y-0.5 transition-all duration-300 text-xs font-bold">
                                    <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                                    Xử lý huỷ
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-4xl text-green-400">verified</span>
                                    </div>
                                    <p class="font-semibold text-gray-700">Không có lô hàng nào hết hạn</p>
                                    <p class="text-sm text-gray-400">Tất cả lô hàng đang trong hạn sử dụng.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- No results after filter --}}
            <div id="no-results" class="hidden px-6 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                    <span class="material-symbols-outlined text-5xl text-gray-200">search_off</span>
                    <p class="font-semibold text-gray-500">Không tìm thấy kết quả nào</p>
                    <p class="text-sm text-gray-400">Thử thay đổi từ khoá hoặc khoảng ngày.</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Hiển thị <span class="font-semibold text-red-600" id="visible-count">{{ $expiredBatches->count() }}</span> / {{ $expiredBatches->count() }} lô hàng cần xử lý
            </p>
            <p class="text-xs text-gray-400 hidden sm:block">Dữ liệu được cập nhật theo thời gian thực</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/materials/common.js') }}"></script>
<script src="{{ asset('js/backend/materials/expired.js') }}"></script>
@endpush
