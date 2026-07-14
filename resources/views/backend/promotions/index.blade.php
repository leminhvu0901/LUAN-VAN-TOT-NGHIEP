@extends('backend.layouts.app')

@section('title', 'Quản lý Khuyến mãi')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- Phần 1: Tiêu đề trang & Nút Thêm mới -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Khuyến mãi</h2>
                <p class="text-gray-500 text-sm mt-1">Tạo và quản lý mã giảm giá, voucher cho khách hàng.</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto items-center">
                <a href="{{ route('admin.promotions.create') }}"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">add</span>
                    Thêm khuyến mãi mới
                </a>
            </div>
        </div>

        @if(session('success') || $errors->any())
            {{-- Dữ liệu flash để file JS riêng đọc và hiển thị SweetAlert --}}
            <div id="promotion-flash-data"
                data-success="{{ session('success') }}"
                data-errors='@json($errors->all())'
                class="hidden">
            </div>
        @endif

        <!-- Phần 2: Khung Thống kê -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
            <!-- Card 1: Tổng -->
            <div
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng khuyến mãi</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalPromotions) }}
                    </p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">local_offer</span> mã khuyến mãi
                    </p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">sell</span>
                </div>
            </div>

            <!-- Card 2: Đang hoạt động -->
            <div
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đang diễn ra</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($activePromotions) }}
                    </p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span> đang áp dụng
                    </p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg"
                        style="font-variation-settings: 'FILL' 1;">check_circle</span>
                </div>
            </div>

            <!-- Card 3: Hết hạn / Vô hiệu -->
            <div
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Hết hạn / Vô hiệu</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiredPromotions) }}
                    </p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Đã vô hiệu hóa
                    </p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 group-hover:bg-gray-100 group-hover:scale-110 transition-all flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">block</span>
                </div>
            </div>
        </div>


        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6">
            <!-- lang nghe thay doi -->
            <form action="{{ route('admin.promotions.index') }}" method="GET" id="filter-form"
                class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center">
                <div
                    class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-64 relative">
                    <span class="material-symbols-outlined text-gray-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-4 w-full outline-none"
                        placeholder="Tìm mã khuyến mãi...">
                </div>

                <select name="type"
                    class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                    <option value="all">Tất cả loại</option>
                    <option value="percent" {{ request('type') == 'percent' ? 'selected' : '' }}>Giảm theo %</option>
                    <option value="fixed" {{ request('type') == 'fixed' ? 'selected' : '' }}>Giảm cố định (VNĐ)</option>
                </select>

                <select name="status"
                    class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang diễn ra</option>
                    <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Sắp tới</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Vô hiệu</option>
                </select>

                <select name="sort"
                    class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                    <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                    <option value="value_asc" {{ request('sort') == 'value_asc' ? 'selected' : '' }}>Giá trị: Thấp → Cao
                    </option>
                    <option value="value_desc" {{ request('sort') == 'value_desc' ? 'selected' : '' }}>Giá trị: Cao → Thấp
                    </option>
                </select>

                <!-- xoa loc -->
                <a href="{{ route('admin.promotions.index') }}" id="btn-clear-filter"
                    class="px-4 py-2.5 text-gray-500 hover:text-red-500 hover:bg-red-50 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                    style="display: {{ (request('search') || (request('type') && request('type') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'inline-block' : 'none' }};">
                    Xóa lọc
                </a>

                <!-- Nút xóa nhiều (ngay sát dropdown) -->
                <div id="bulk-delete-container" style="display:none;" class="w-full sm:w-auto">
                    <form id="bulk-delete-form" action="{{ route('admin.promotions.bulk_delete') }}" method="POST">
                        @csrf
                        <input type="hidden" name="total_promotions_count" id="total-promotions-count" value="0">
                    </form>
                    <button type="button" class="js-bulk-delete flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all w-full sm:w-auto">
                        <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                        Xóa <span id="selected-count" class="mx-1">0</span> khuyến mãi
                    </button>
                </div>
            </form>
        </div>

        <!-- Phần 4: Bảng danh sách Khuyến mãi -->
        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
            @include('backend.promotions.partials.table', ['promotions' => $promotions])
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Nạp SweetAlert2 để các đoạn JS phía trên và file index.js có thể dùng Swal --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- File JS riêng xử lý lọc AJAX, phân trang, checkbox và xoá hàng loạt --}}
    <script src="{{ asset('js/backend/promotions/index.js') }}"></script>
@endpush
