@extends('backend.layouts.app')

@section('title', 'Lịch Sử Xuất Hủy Kho')

@section('content')

<div class="p-6 sm:p-8 space-y-6 animate-fade-in-up">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1.5 text-sm text-gray-400 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('admin.materials.index') }}" class="hover:text-gray-600 transition-colors">Quản lý Kho</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-gray-700 font-medium">Lịch Sử Xuất Hủy</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-red-100 text-red-600">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">remove_shopping_cart</span>
                </span>
                Lịch Sử Xuất Hủy
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-[52px]">Theo dõi toàn bộ các lô hàng đã bị xuất hủy khỏi kho.</p>
        </div>

        <a href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 hover:shadow-md transition-all self-start md:self-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Tổng số bản ghi --}}
        <div class="stat-card bg-white rounded-2xl organic-shadow p-5 border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">inventory_2</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng lần hủy <span id="label-count-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-bold text-gray-900" id="stat-count">{{ $disposedBatches->count() }}</p>
            </div>
        </div>

        {{-- Tổng giá trị thiệt hại --}}
        <div class="stat-card disposed-card-glow bg-red-50 rounded-2xl p-5 border border-red-100 flex items-center gap-4 sm:col-span-2">
            <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">monetization_on</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Tổng thiệt hại <span id="label-val-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-bold text-red-700 truncate" id="total-disposed-value">
                    {{ number_format($disposedValue, 0, ',', '.') }}đ
                </p>
                <p class="text-xs text-red-400 mt-0.5">Giá trị hàng hóa bị xuất hủy</p>
            </div>
        </div>
    </div>

    {{-- ===== MAIN TABLE CARD ===== --}}
    <div class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col">

        {{-- Toolbar --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-base text-gray-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-500 text-[20px]">list_alt</span>
                    Danh Sách Chi Tiết
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Nhấn vào tiêu đề cột để sắp xếp</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                {{-- Date range filter --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Từ ngày</label>
                        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all">
                            <span class="material-symbols-outlined text-gray-400 text-[16px]">calendar_month</span>
                            <input type="date" id="date-from" onchange="filterDisposedTable()"
                                class="text-sm text-gray-600 bg-transparent border-none p-0 focus:ring-0 outline-none w-32 cursor-pointer">
                        </div>
                    </div>
                    <div class="mt-5 text-gray-300 font-bold hidden sm:block">—</div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Đến ngày</label>
                        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all">
                            <span class="material-symbols-outlined text-gray-400 text-[16px]">calendar_today</span>
                            <input type="date" id="date-to" onchange="filterDisposedTable()"
                                class="text-sm text-gray-600 bg-transparent border-none p-0 focus:ring-0 outline-none w-32 cursor-pointer">
                        </div>
                    </div>
                    <div class="flex flex-col gap-1" id="wrap-clear-filter">
                        <label class="text-[10px] font-semibold text-transparent uppercase tracking-widest select-none">.</label>
                        <button onclick="clearDateFilter()" id="btn-clear-date"
                            class="hidden inline-flex items-center gap-1.5 px-3 py-2 bg-red-50 text-red-500 text-sm font-semibold rounded-xl border border-red-100 hover:bg-red-100 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-[16px]">close</span>
                            Xóa lọc
                        </button>
                    </div>
                </div>

                {{-- Search filter --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Tìm kiếm</label>
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all h-[42px]">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-red-500 transition-colors">search</span>
                        <input type="text" id="search-disposed" onkeyup="filterDisposedTable()"
                            placeholder="Tìm vật tư, lý do..."
                            class="text-sm text-gray-600 bg-transparent border-none p-0 focus:ring-0 outline-none w-48">
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
                        <tr class="hover:bg-red-50/30 transition-colors mat-table-row"
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
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-red-500 text-[16px]" style="font-variation-settings:'FILL' 1">inventory_2</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-sm text-gray-900">{{ $batch->material->name }}</span>
                                        @php
                                            preg_match('/(LOT-\d+)/', $batch->note, $matches);
                                            $lotCode = $matches[1] ?? 'Không rõ';
                                        @endphp
                                        <span class="text-xs font-medium text-gray-400">Lô: {{ $lotCode }}</span>
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
