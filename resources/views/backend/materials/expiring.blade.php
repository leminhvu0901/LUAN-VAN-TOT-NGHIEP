@extends('backend.layouts.app')

@section('title', 'Lô hàng sắp hết hạn')

@section('content')
<div class="p-4 sm:p-6 lg:p-10 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center flex-wrap gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600 transition-colors">Dashboard</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <a href="{{ route('admin.materials.index') }}" class="hover:text-emerald-600 transition-colors">Quản lý Kho</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="text-gray-900 font-medium">Lô Hàng Sắp Hết Hạn</span>
            </div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-start sm:items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-200 text-amber-600 shadow-inner border border-amber-50 flex-shrink-0">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">event_busy</span>
                </span>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">Lô Hàng Sắp Hết Hạn</span>
            </h1>
            <p class="text-sm text-gray-500 mt-2 sm:mt-1 sm:ml-[58px]">Các lô hàng sẽ hết hạn trong vòng <span class="font-semibold text-amber-600">30 ngày</span> tới</p>
        </div>
        
        <a href="{{ route('admin.materials.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/80 backdrop-blur-md border border-gray-200/80 rounded-xl shadow-sm text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:shadow hover:-translate-y-0.5 transition-all duration-300 w-full md:w-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Quay lại Kho
        </a>
    </div>

    <!-- Main Content -->
    <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100/80 overflow-hidden flex flex-col">
        {{-- ===== TOOLBAR ===== --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 backdrop-blur-sm">
            <div class="flex flex-col gap-3">

                {{-- Row 1: Title + Search --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-base text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-500 text-[20px]">list_alt</span>
                            Danh Sách Chi Tiết
                        </h2>
                    </div>

                    {{-- Search --}}
                    <div class="flex items-center gap-2 bg-white border border-gray-200 hover:border-amber-300 rounded-xl px-3 py-2 shadow-sm group focus-within:border-amber-500 focus-within:ring-4 focus-within:ring-amber-500/10 transition-all duration-300 w-full sm:w-auto">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] group-focus-within:text-amber-500 transition-colors">search</span>
                        <input type="text" id="search-expiring" onkeyup="filterSimpleTable('search-expiring')"
                            placeholder="Tìm tên vật tư, mã lô..."
                            class="text-sm text-gray-700 bg-transparent border-none p-0 focus:ring-0 outline-none w-full sm:w-60 placeholder-gray-400">
                    </div>
                </div>

            </div>
        </div>
        
        <div class="p-0 overflow-x-auto">
            <table id="table-expiring" class="w-full text-left border-collapse whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Tên vật tư</th>
                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã lô</th>
                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Số lượng tồn</th>
                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Hạn sử dụng</th>
                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="table-body">
                    @forelse($expiringBatches as $batch)
                        @php
                            $daysDiff = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($batch->expiration_date)->startOfDay(), false);
                        @endphp
                        <tr class="hover:bg-amber-50/40 transition-colors duration-200 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-100 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                        <span class="material-symbols-outlined text-amber-500 text-[18px]" style="font-variation-settings:'FILL' 1">event_busy</span>
                                    </div>
                                    <span class="font-bold text-sm text-gray-900">{{ $batch->material->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-xs font-bold font-mono">LOT-{{ $batch->id }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700">
                                {{ number_format($batch->remaining_quantity, 2, ',', '.') }} {{ $batch->material->unit }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold {{ $daysDiff <= 15 ? 'text-red-600' : 'text-amber-600' }} drop-shadow-sm">
                                {{ \Carbon\Carbon::parse($batch->expiration_date)->format('d/m/Y') }}
                                <span class="text-xs font-bold text-white px-2 py-0.5 rounded-full {{ $daysDiff <= 15 ? 'bg-gradient-to-r from-red-500 to-rose-500 shadow-sm shadow-red-500/30' : 'bg-gradient-to-r from-amber-400 to-orange-400 shadow-sm shadow-amber-500/30' }} ml-2">({{ $daysDiff }} ngày nữa)</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.materials.imports', $batch->material_id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl shadow-md shadow-amber-500/20 hover:shadow-lg hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all duration-300 text-xs font-bold">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    Kiểm tra
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <span class="material-symbols-outlined text-4xl text-gray-300 block mb-2">check_circle</span>
                                Không có lô hàng nào sắp hết hạn.
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
                Hiển thị <span class="font-semibold text-amber-600" id="visible-count">{{ $expiringBatches->count() }}</span> / {{ $expiringBatches->count() }} lô hàng
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
