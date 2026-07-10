@extends('backend.layouts.app')

@section('title', 'Vật Tư Dưới Mức Tồn Tối Thiểu')

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
                <span class="text-gray-700 font-medium">Vật Tư Dưới Mức Tồn Tối Thiểu</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-red-100 text-red-600">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">warning</span>
                </span>
                Vật Tư Dưới Mức Tồn Tối Thiểu
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-[52px]">Các vật tư có tồn kho dưới <span class="font-semibold text-red-600">5 đơn vị</span> — cần nhập kho sớm.</p>
        </div>
        <a href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all self-start md:self-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="mat-stat-card bg-red-50 rounded-2xl organic-shadow p-5 border border-red-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">warning</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Tổng vật tư cảnh báo</p>
                <p class="text-3xl font-bold text-red-700">{{ $lowStockMaterials->count() }}</p>
            </div>
        </div>
        <div class="mat-stat-card bg-white rounded-2xl organic-shadow p-5 border border-gray-100 flex items-center gap-4 sm:col-span-2">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-500 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">info</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ngưỡng cảnh báo</p>
                <p class="text-xl font-bold text-gray-800">Dưới 5 đơn vị tồn kho</p>
                <p class="text-xs text-gray-400 mt-0.5">Bao gồm cả vật tư hết hàng (= 0)</p>
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
                        <p class="text-xs text-gray-400 mt-0.5">Nhấn vào nút <strong>Nhập thêm</strong> để bổ sung tồn kho ngay</p>
                    </div>

                    {{-- Search --}}
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm group focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-red-500 transition-colors">search</span>
                        <input type="text" id="search-low-stock" onkeyup="filterSimpleTable('search-low-stock')"
                            placeholder="Tìm mã, tên vật tư..."
                            class="text-sm text-gray-600 bg-transparent border-none p-0 focus:ring-0 outline-none w-44 sm:w-52">
                    </div>
                </div>

            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto flex-1">
            <table id="table-low-stock" class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã VT</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Tên vật tư</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right">Tồn kho</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Đơn vị</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-3.5 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="table-body">
                    @forelse($lowStockMaterials as $item)
                        <tr class="hover:bg-red-50/30 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500 font-semibold">
                                VT-{{ str_pad($item->id, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-red-500 text-[16px]" style="font-variation-settings:'FILL' 1">warning</span>
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900">{{ $item->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold {{ $item->current_stock == 0 ? 'text-red-600' : 'text-orange-600' }}">
                                    {{ $item->current_stock }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ $item->unit }}</td>
                            <td class="px-6 py-4">
                                @if($item->current_stock == 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mat-pulse-dot"></span>
                                        Hết hàng
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-100 text-orange-700 text-xs font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        Sắp hết
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.materials.imports', $item->id) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition-colors text-xs font-bold">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    Nhập thêm
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-4xl text-green-400">check_circle</span>
                                    </div>
                                    <p class="font-semibold text-gray-700">Tất cả vật tư đều ở mức an toàn</p>
                                    <p class="text-sm text-gray-400">Tồn kho đang được đảm bảo tốt.</p>
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
                    <p class="text-sm text-gray-400">Thử thay đổi từ khoá tìm kiếm.</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Hiển thị <span class="font-semibold text-red-600" id="visible-count">{{ $lowStockMaterials->count() }}</span> / {{ $lowStockMaterials->count() }} vật tư cảnh báo
            </p>
            <p class="text-xs text-gray-400 hidden sm:block">Dữ liệu được cập nhật theo thời gian thực</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/materials/common.js') }}"></script>
<script src="{{ asset('js/backend/materials/simple-filter.js') }}"></script>
@endpush
