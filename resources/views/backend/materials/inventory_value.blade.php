@extends('backend.layouts.app')

@section('title', 'Chi Tiết Giá Trị Kho Hàng')

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
                <span class="text-gray-700 font-medium">Giá Trị Kho Hàng</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-600">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">payments</span>
                </span>
                Giá Trị Kho Hàng
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-[52px]">Theo dõi chi tiết giá trị của từng loại mặt hàng đang tồn trong kho.</p>
        </div>

        <a href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 hover:shadow-md transition-all self-start md:self-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Tổng số mặt hàng --}}
        <div class="stat-card bg-white rounded-2xl organic-shadow p-5 border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-500 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">category</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Mặt hàng <span id="label-count-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-bold text-gray-900" id="stat-count">{{ $materials->count() }}</p>
            </div>
        </div>

        {{-- Tổng giá trị kho --}}
        <div class="stat-card bg-emerald-50 rounded-2xl p-5 border border-emerald-100 flex items-center gap-4 sm:col-span-2">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">account_balance_wallet</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Tổng giá trị <span id="label-val-suffix">(theo bộ lọc)</span></p>
                <p class="text-3xl font-bold text-emerald-700 truncate" id="total-inventory-value">
                    {{ number_format($totalValue, 0, ',', '.') }}đ
                </p>
                <p class="text-xs text-emerald-500 mt-0.5">Giá trị ròng dựa trên số lượng tồn kho</p>
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
                {{-- Search filter --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Tìm kiếm</label>
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm group focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition-all h-[42px]">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-emerald-500 transition-colors">search</span>
                        <input type="text" id="search-inventory" onkeyup="filterInventoryTable()"
                            placeholder="Tìm vật tư..."
                            class="text-sm text-gray-600 bg-transparent border-none p-0 focus:ring-0 outline-none w-48">
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto flex-1">
            <table id="table-inventory" data-total-id="total-inventory-value" data-highlight="bg-emerald-50" class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                    <tr>
                        <th class="px-6 py-3.5 w-10 text-center">
                            <input type="checkbox" id="check-all" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã VT</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(0)">
                            <span class="flex items-center gap-1">Tên vật tư <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-0">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(1)">
                            <span class="flex items-center gap-1 justify-end">Tồn kho <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-1">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(2)">
                            <span class="flex items-center gap-1 justify-end">Đơn giá <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-2">unfold_more</span></span>
                        </th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right cursor-pointer hover:text-gray-700 select-none" onclick="sortTable(3)">
                            <span class="flex items-center gap-1 justify-end">Tổng giá trị (VNĐ) <span class="material-symbols-outlined text-[14px] text-gray-300" id="sort-icon-3">unfold_more</span></span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="table-body">
                    @forelse($materials as $material)
                        @php
                            $value = $material->current_stock * $material->unit_price;
                        @endphp
                        <tr class="hover:bg-emerald-50/30 transition-colors mat-table-row"
                            data-value="{{ $value }}"
                            data-sort-name="{{ $material->name }}"
                            data-sort-qty="{{ $material->current_stock }}"
                            data-sort-value="{{ $material->unit_price }}"
                            data-sort-date="{{ $value }}">
                            
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" onchange="calculateSelected()">
                            </td>
                            <td class="px-6 py-4 font-semibold text-sm text-gray-500">VT-{{ str_pad($material->id, 2, '0', STR_PAD_LEFT) }}</td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-emerald-600 text-[16px]" style="font-variation-settings:'FILL' 1">inventory_2</span>
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900">{{ $material->name }}</span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700 text-right">
                                {{ $material->current_stock }} {{ $material->unit }}
                            </td>
                            
                            <td class="px-6 py-4 text-sm font-semibold text-gray-600 text-right">
                                {{ number_format($material->unit_price, 0, ',', '.') }}đ
                            </td>

                            <td class="px-6 py-4 text-sm font-bold text-emerald-700 text-right">
                                {{ number_format($value, 0, ',', '.') }}đ
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-4xl text-gray-400">inventory</span>
                                    </div>
                                    <p class="font-semibold text-gray-700">Chưa có vật tư nào trong kho</p>
                                    <p class="text-sm text-gray-400">Kho hàng hiện tại đang trống.</p>
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
                    <p class="text-sm text-gray-400">Thử thay đổi từ khóa tìm kiếm.</p>
                </div>
            </div>
        </div>

        {{-- Footer with count --}}
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Hiển thị <span class="font-semibold text-gray-600" id="visible-count">{{ $materials->count() }}</span>
                / {{ $materials->count() }} mặt hàng
            </p>
            <p class="text-xs text-gray-400 hidden sm:block">Dữ liệu được tính dựa trên số lượng tồn kho khả dụng</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/backend/materials/common.js') }}"></script>
<script src="{{ asset('js/backend/materials/inventory_value.js') }}"></script>
@endpush
