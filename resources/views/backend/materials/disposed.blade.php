@extends('backend.layouts.app')

@section('title', 'Lịch Sử Xuất Hủy Kho')

@section('content')

<div class="p-4 sm:p-6 lg:p-8 space-y-4 sm:space-y-6 animate-fade-in-up">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            {{-- Breadcrumb --}}
            <div class="flex items-center flex-wrap gap-1.5 text-sm text-gray-400 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('admin.materials.index') }}" class="hover:text-gray-600 transition-colors">Quản lý Kho</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-gray-700 font-medium">Lịch Sử Xuất Hủy</span>
            </div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-start sm:items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-red-100 to-red-200 text-red-600 shadow-inner border border-red-50 flex-shrink-0">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">remove_shopping_cart</span>
                </span>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">Lịch Sử Xuất Hủy</span>
            </h1>
            <p class="text-sm text-gray-500 mt-2 sm:mt-1 sm:ml-[58px]">Theo dõi toàn bộ các lô hàng đã bị xuất hủy khỏi kho.</p>
        </div>

        <a href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/80 backdrop-blur-md border border-gray-200/80 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:shadow hover:-translate-y-0.5 transition-all duration-300 w-full md:w-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Tổng số bản ghi --}}
        <div class="stat-card group bg-gradient-to-br from-red-50 to-red-100/50 rounded-2xl p-5 border border-red-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
            <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-red-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">inventory_2</span>
            <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-red-100 flex items-center justify-center text-red-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">inventory_2</span>
            </div>
            <div class="z-10">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng lần hủy <span id="label-count-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-extrabold text-gray-900 tracking-tight" id="stat-count">{{ $disposedBatches->count() }}</p>
            </div>
        </div>

        {{-- Tổng giá trị thiệt hại --}}
        <div class="stat-card disposed-card-glow group bg-gradient-to-br from-red-50 to-red-100/50 rounded-2xl p-5 border border-red-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 sm:col-span-2 relative overflow-hidden">
            <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-red-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">monetization_on</span>
            <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-red-100 flex items-center justify-center text-red-600 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">monetization_on</span>
            </div>
            <div class="min-w-0 z-10">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Tổng thiệt hại <span id="label-val-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-extrabold text-red-700 tracking-tight truncate" id="total-disposed-value">
                    {{ number_format($disposedValue, 0, ',', '.') }}đ
                </p>
                <p class="text-xs text-red-400 mt-0.5">Giá trị hàng hóa bị xuất hủy</p>
            </div>
        </div>
    </div>

    {{-- ===== MAIN TABLE CARD ===== --}}
    <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100/80 overflow-hidden flex flex-col">

        {{-- Toolbar --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <h2 class="font-bold text-base text-gray-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-500 text-[20px]">list_alt</span>
                    Danh Sách Chi Tiết
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Nhấn vào tiêu đề cột để sắp xếp</p>
            </div>

            <div class="flex items-start sm:items-center gap-2 flex-col sm:flex-row w-full sm:w-auto">
                {{-- Date range filter --}}
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                    <div class="flex flex-col gap-1 w-full">
                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Từ ngày</label>
                        <div class="flex items-center gap-2 bg-white border border-gray-200 hover:border-red-300 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-500 focus-within:ring-4 focus-within:ring-red-500/10 transition-all duration-300">
                            <span class="material-symbols-outlined text-gray-400 text-[16px] group-focus-within:text-red-500 transition-colors">calendar_month</span>
                            <input type="date" id="date-from" onchange="filterDisposedTable()"
                                class="text-sm text-gray-700 bg-transparent border-none p-0 focus:ring-0 outline-none w-full sm:w-32 cursor-pointer">
                        </div>
                    </div>
                    <div class="mt-5 text-gray-300 font-bold hidden sm:block">—</div>
                    <div class="flex flex-col gap-1 w-full">
                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Đến ngày</label>
                        <div class="flex items-center gap-2 bg-white border border-gray-200 hover:border-red-300 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-500 focus-within:ring-4 focus-within:ring-red-500/10 transition-all duration-300">
                            <span class="material-symbols-outlined text-gray-400 text-[16px] group-focus-within:text-red-500 transition-colors">calendar_today</span>
                            <input type="date" id="date-to" onchange="filterDisposedTable()"
                                class="text-sm text-gray-700 bg-transparent border-none p-0 focus:ring-0 outline-none w-full sm:w-32 cursor-pointer">
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-1 hidden w-full sm:w-auto" id="wrap-clear-filter">
                    <label class="text-[10px] font-semibold text-transparent uppercase tracking-widest select-none hidden sm:block">.</label>
                    <button onclick="clearDateFilter()" id="btn-clear-date"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 text-red-500 text-sm font-semibold rounded-xl border border-red-100 hover:bg-red-100 active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                        Xóa lọc
                    </button>
                </div>

                {{-- Search filter --}}
                <div class="flex flex-col gap-1 w-full sm:w-auto mt-2 sm:mt-0">
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Tìm kiếm</label>
                    <div class="flex items-center gap-2 bg-white border border-gray-200 hover:border-red-300 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-500 focus-within:ring-4 focus-within:ring-red-500/10 transition-all duration-300 h-[42px] w-full sm:w-auto">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-red-500 transition-colors">search</span>
                        <input type="text" id="search-disposed" onkeyup="filterDisposedTable()"
                            placeholder="Tìm vật tư, lý do..."
                            class="text-sm text-gray-700 bg-transparent border-none p-0 focus:ring-0 outline-none w-full sm:w-48 placeholder-gray-400">
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto flex-1">
            <table id="table-disposed" data-total-id="total-disposed-value" data-highlight="bg-red-50" class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                    <tr>
                        <th class="px-6 py-3.5 w-10 text-center">
                            <input type="checkbox" id="check-all" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(0)">
                            <span class="flex items-center gap-1">Tên vật tư <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-0">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(1)">
                            <span class="flex items-center gap-1 justify-end">Số lượng hủy <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-1">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(2)">
                            <span class="flex items-center gap-1 justify-end">Giá trị (VNĐ) <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-2">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(3)">
                            <span class="flex items-center gap-1">Ngày hủy <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-3">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(4)">
                            <span class="flex items-center gap-1">Ngày hết hạn <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-4">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Lý do / Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="table-body">
                    @forelse($disposedBatches as $batch)
                        <tr class="hover:bg-red-50/40 transition-colors duration-200 group mat-table-row"
                            data-date="{{ $batch->expiration_date ? \Carbon\Carbon::parse($batch->expiration_date)->format('Y-m-d') : '' }}"
                            data-value="{{ abs($batch->total_price) }}"
                            data-sort-name="{{ $batch->material->name }}"
                            data-sort-qty="{{ abs($batch->quantity) }}"
                            data-sort-value="{{ abs($batch->total_price) }}"
                            data-sort-date="{{ $batch->created_at->timestamp }}"
                            data-sort-exp="{{ $batch->expiration_date ? \Carbon\Carbon::parse($batch->expiration_date)->timestamp : 0 }}">
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer" onchange="calculateSelected()">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-red-50 to-red-100 border border-red-100 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                        <span class="material-symbols-outlined text-red-500 text-[18px]" style="font-variation-settings:'FILL' 1">inventory_2</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm text-gray-900">{{ $batch->material->name }}</span>
                                        @php
                                            preg_match('/(LOT-\d+)/', $batch->note, $matches);
                                            $lotCode = $matches[1] ?? 'Không rõ';
                                        @endphp
                                        <span class="text-xs font-medium text-gray-400">Lô: <span class="font-mono text-gray-600">{{ $lotCode }}</span></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-red-600 text-right">
                                -{{ number_format(abs($batch->quantity), 2, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400 ml-1">{{ $batch->material->unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-50 text-red-700 text-sm font-bold border border-red-100">
                                    {{ number_format(abs($batch->total_price), 0, ',', '.') }}d
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-700">{{ $batch->created_at->format('d/m/Y') }}</span>
                                    <span class="text-xs text-gray-400">{{ $batch->created_at->format('H:i') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-semibold">
                                {{ $batch->expiration_date ? \Carbon\Carbon::parse($batch->expiration_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-[280px]">
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-gray-300 text-[16px] mt-0.5 flex-shrink-0">notes</span>
                                    <span class="truncate" title="{{ $batch->note }}">{{ $batch->note ?: '-' }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-4xl text-green-400">check_circle</span>
                                    </div>
                                    <p class="font-semibold text-gray-700">Chua co lo hang nao bi xuat huy</p>
                                    <p class="text-sm text-gray-400">Kho hang dang hoat dong on dinh.</p>
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
                    <p class="font-semibold text-gray-500">Khong tim thay ket qua nao</p>
                    <p class="text-sm text-gray-400">Thu thay doi tu khoa hoac bo loc ngay.</p>
                </div>
            </div>
        </div>

        {{-- Footer with count --}}
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Hien thi <span class="font-semibold text-gray-600" id="visible-count">{{ $disposedBatches->count() }}</span>
                / {{ $disposedBatches->count() }} ban ghi
            </p>
            <p class="text-xs text-gray-400 hidden sm:block">Du lieu duoc cap nhat theo thoi gian thuc</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/backend/materials/common.js') }}"></script>
<script src="{{ asset('js/backend/materials/disposed.js') }}"></script>
@endpush
