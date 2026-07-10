@extends('backend.layouts.app')

@section('title', 'Lô Hàng Đã Hết Hạn')

@section('content')
<div class="p-6 sm:p-8 space-y-6 mat-animate">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-sm text-gray-400 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('admin.materials.index') }}" class="hover:text-gray-600 transition-colors">Quản lý Kho</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-gray-700 font-medium">Lô Hàng Đã Hết Hạn</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-red-100 text-red-600">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">delete_forever</span>
                </span>
                Lô Hàng Đã Hết Hạn
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-[52px]">Các lô hàng quá hạn sử dụng còn tồn trong kho — cần xử lý huỷ bỏ ngay.</p>
        </div>
        <a href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all self-start md:self-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="mat-stat-card bg-white rounded-2xl organic-shadow p-5 border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">inventory_2</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng lô hết hạn <span id="label-count-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-bold text-gray-900" id="stat-count">{{ $expiredBatches->count() }}</p>
            </div>
        </div>
        <div class="mat-stat-card mat-disposed-glow bg-red-50 rounded-2xl p-5 border border-red-100 flex items-center gap-4 sm:col-span-2">
            <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">monetization_on</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Giá trị mất mát ước tính <span id="label-val-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-bold text-red-700 truncate" id="total-expired-value">{{ number_format($expiredValue, 0, ',', '.') }}đ</p>
                <p class="text-xs text-red-400 mt-0.5">Giá trị hàng hoá bị hết hạn</p>
            </div>
        </div>
    </div>

    {{-- ===== TABLE CARD ===== --}}
    <div class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col">

        {{-- ===== TOOLBAR ===== --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/70">
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
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-red-500 transition-colors">search</span>
                        <input type="text" id="search-expired" onkeyup="filterExpiredTable()"
                            placeholder="Tìm tên vật tư, mã lô..."
                            class="text-sm text-gray-600 bg-transparent border-none p-0 focus:ring-0 outline-none w-44 sm:w-52">
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
                        <tr class="hover:bg-red-50/30 transition-colors mat-table-row"
                            data-value="{{ $batchValue }}"
                            data-sort-name="{{ $batch->material->name }}"
                            data-date="{{ \Carbon\Carbon::parse($batch->expiration_date)->format('Y-m-d') }}">
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer" onchange="calculateSelected()">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-red-500 text-[16px]" style="font-variation-settings:'FILL' 1">delete_forever</span>
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900">{{ $batch->material->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-red-600 font-bold">LOT-{{ $batch->id }}</span>
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
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-xs font-bold">
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
