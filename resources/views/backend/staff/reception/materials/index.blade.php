@extends('backend.layouts.app')

@section('title', 'Quản lý Kho Vật Tư - Nhân viên')

@section('content')

    <div id="materials-index-page" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        <!-- Phần 1: Tiêu đề trang -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Kho Vật Tư</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi, cập nhật và quản lý tồn kho nguyên liệu chi tiết.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <!-- Phần 2: Khung Thống kê 7 thẻ -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Card 1: Tổng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng mặt hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalItems) }}</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">category</span> đang quản lý
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">inventory_2</span>
                </div>
            </div>

            <!-- Card 2: Sắp hết hàng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-orange-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($lowStockItems) }}</p>
                    <p class="text-orange-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">warning</span> sắp hết
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">production_quantity_limits</span>
                </div>
            </div>

            <!-- Card 2.5: Đã hết hàng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($outOfStockItems ?? 0) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">error</span> cần nhập gấp
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">remove_shopping_cart</span>
                </div>
            </div>

            <!-- Card 3: Sắp hết hạn -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-amber-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiringItems ?? 0) }}</p>
                    <p class="text-amber-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Trong 30 ngày
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">event_upcoming</span>
                </div>
            </div>

            <!-- Card 4: Đã hết hạn -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiredItems ?? 0) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Cần tiêu huỷ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">event_busy</span>
                </div>
            </div>

            <!-- Card 5: Đã thu hồi -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-gray-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã thu hồi</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($disposedBatchesCount ?? 0) }}</p>
                    <p class="text-gray-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Lô hàng xuất huỷ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">remove_shopping_cart</span>
                </div>
            </div>

            <!-- Card 7: Tổng giá trị kho -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng giá trị</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ number_format(($totalValue ?? 0) / 1000000, 1) }}M</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        VNĐ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">payments</span>
                </div>
            </div>

            <!-- Card 8: Giá trị thu hồi -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Giá trị đã hủy</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ number_format(($disposedValue ?? 0) / 1000000, 1) }}M</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        VNĐ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">assignment_return</span>
                </div>
            </div>
        </div>

        <!-- Phần 3: Bộ lọc -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-4 relative z-20">
            <div class="flex items-center justify-between lg:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button"
                    onclick="toggleFilterPanel('filter-form')"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> <span class="hidden sm:inline">Bộ lọc</span>
                </button>
            </div>

            <form id="filter-form" action="{{ route('staff.reception.materials.index') }}" method="GET" class="hidden lg:flex flex-col w-full transition-all">
                <div class="flex flex-wrap items-center gap-3 w-full">
                    <div class="w-full sm:w-[calc(50%-0.375rem)] lg:w-auto lg:flex-1 flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[20px] shrink-0">search</span>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                            placeholder="Tên vật tư, mã VT...">
                    </div>

                    <select name="status" id="status-select" class="w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Sắp hết hàng</option>
                        <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                        <option value="expiring" {{ request('status') === 'expiring' ? 'selected' : '' }}>Sắp hết hạn</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
                        <option value="disposed" {{ request('status') === 'disposed' ? 'selected' : '' }}>Đã có xuất hủy</option>
                    </select>

                    <select name="sort" id="sort-select" class="w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Tồn kho tăng dần</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Tồn kho giảm dần</option>
                    </select>

                    <div class="w-full lg:w-auto shrink-0 lg:ml-auto flex items-center gap-2">
                        <button type="button" id="btn-clear-filter" style="display: none;"
                            class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-2 bg-gray-100 text-gray-600 border border-gray-200 font-semibold text-sm rounded-xl hover:bg-gray-200 transition-all shadow-sm">
                            <span class="material-symbols-outlined text-[20px] shrink-0">filter_alt_off</span>
                            <span class="whitespace-nowrap">Xóa lọc</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Phần 4: Bảng dữ liệu -->
        <div class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                <div id="table-loader" class="absolute inset-0 bg-white/50 z-20 hidden items-center justify-center transition-all duration-300"></div>
                <div class="flex-1 flex flex-col min-h-0 relative w-full" id="materials-table-wrapper">
                    @include('backend.staff.reception.materials.partials.table')
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/backend/staff/reception/materials/common.js') }}"></script>
        <script src="{{ asset('js/backend/staff/reception/materials/index.js') }}"></script>
    @endpush

@endsection
