@extends('backend.layouts.app')

@section('title', 'Quản lý Khách hàng - Admin')

@section('content')
    <div class="customers-page space-y-6 sm:space-y-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Quản lý Khách hàng</h1>
                <p class="text-gray-500 text-sm mt-1">Quản lý danh sách thành viên, hạng và lịch sử tích lũy.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <button type="button" id="bulk-deselect-btn" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-50 text-gray-600 rounded-lg font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200" title="Bỏ chọn tất cả">
                    <span class="material-symbols-outlined text-[18px] sm:text-[20px] shrink-0">deselect</span>
                    <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
                </button>

                <div id="bulk-delete-container" class="hidden flex-1 sm:flex-none">
                    <button type="button" class="js-bulk-delete w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100" title="Xóa đã chọn">
                        <span class="material-symbols-outlined text-[20px] shrink-0">delete_sweep</span>
                        <span class="font-semibold whitespace-nowrap">Xóa (<span id="selected-count" class="font-bold">0</span>)</span>
                    </button>
                </div>

                <a href="{{ route('admin.customers.create') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all font-medium text-sm shadow-sm hover:shadow-md whitespace-nowrap">
                    <span class="material-symbols-outlined text-[20px] shrink-0">add</span>
                    <span class="font-semibold whitespace-nowrap">Thêm Khách hàng</span>
                </a>
            </div>
        </div>

        <!-- Thống kê -->

        <div class="flex overflow-x-auto sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pb-2 sm:pb-0 snap-x snap-mandatory hide-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
            <!-- Card 1: Tổng khách hàng -->
            <div class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-5 border border-indigo-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-indigo-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">group</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-indigo-100 flex items-center justify-center text-indigo-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">group</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Tổng tài khoản</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($totalCustomers) }}</p>
                </div>
            </div>

            <!-- Card 2: Hạng Diamond & Gold -->
            <div class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-2xl p-5 border border-amber-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-amber-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">workspace_premium</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-amber-100 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">workspace_premium</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Diamond & Gold</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($diamondCount + $goldCount) }}</p>
                </div>
            </div>

            <!-- Card 3: Khách hàng mới (New) -->
            <div class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl p-5 border border-emerald-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-emerald-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">person_add</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-emerald-100 flex items-center justify-center text-emerald-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">person_add</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Mới đăng ký</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($newCount) }}</p>
                </div>
            </div>

            <!-- Card 4: Bị khóa -->
            <div class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-rose-50 to-rose-100/50 rounded-2xl p-5 border border-rose-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-rose-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">block</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-rose-100 flex items-center justify-center text-rose-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">block</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Tài khoản bị khóa</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($inactiveCount) }}</p>
                </div>
            </div>
        </div>

        <!-- Form ẩn dùng để xóa nhiều -->
        <form id="bulk-delete-form" action="{{ route('admin.customers.bulk_delete') }}" method="POST" style="display: none;">
            @csrf
        </form>

        <!-- Thanh Tìm kiếm và Lọc dữ liệu -->
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 mt-4 sm:mt-0 flex flex-col gap-4">
            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button" onclick="toggleFilterPanel('filter-wrapper')" class="w-10 h-10 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center transition-colors shrink-0 border border-gray-100" title="Mở bộ lọc">
                    <span class="material-symbols-outlined text-[20px]">filter_list</span>
                </button>
            </div>

            <div id="filter-wrapper" class="hidden xl:flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between w-full transition-all">
                <form action="{{ route('admin.customers.index') }}" method="GET" id="filter-form"
                    class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full">
    
                    <div class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-72 relative focus-within:ring-2 focus-within:ring-amber-500/20 focus-within:border-amber-500 transition-all">
                        <span class="material-symbols-outlined text-gray-400">search</span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none"
                            placeholder="Tìm tên, email, SĐT...">
                    </div>
    
                    <div class="grid grid-cols-2 sm:flex gap-3 w-full sm:w-auto">
                        <select name="membership" id="membership-select" data-width-class="w-full sm:w-[150px]"
                            class="custom-select-init px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                            <option value="all">Tất cả hạng</option>
                            <option value="diamond" {{ request('membership') == 'diamond' ? 'selected' : '' }}>Kim cương</option>
                            <option value="gold" {{ request('membership') == 'gold' ? 'selected' : '' }}>Vàng</option>
                            <option value="silver" {{ request('membership') == 'silver' ? 'selected' : '' }}>Bạc</option>
                            <option value="new" {{ request('membership') == 'new' ? 'selected' : '' }}>Mới</option>
                        </select>
    
                        <select name="status" id="status-select" data-width-class="w-full sm:w-[150px]"
                            class="custom-select-init px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                            <option value="all">Trạng thái</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Bị khóa</option>
                        </select>
    
                        <select name="sort" id="sort-select" data-width-class="w-full sm:w-[180px]"
                            class="custom-select-init col-span-2 sm:col-span-1 px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                            <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                            <option value="points_desc" {{ request('sort') == 'points_desc' ? 'selected' : '' }}>Điểm: Cao → Thấp</option>
                            <option value="points_asc" {{ request('sort') == 'points_asc' ? 'selected' : '' }}>Điểm: Thấp → Cao</option>
                        </select>
                    </div>
    
                    <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto">
                        Lọc
                    </button>

                    <a href="{{ route('admin.customers.index') }}" id="btn-clear-filter"
                        class="px-4 py-2.5 text-gray-500 hover:text-red-500 hover:bg-red-50 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                        style="display: {{ (request('search') || (request('membership') && request('membership') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'inline-block' : 'none' }};">
                        Xóa lọc
                    </a>
    
                    <!-- Nút xóa nhiều đã chuyển lên header -->
                </form>
            </div>
        </div>

        <!-- Bảng danh sách Khách hàng -->
        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
            @include('backend.admin.customers.partials.table', ['customers' => $customers])
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/backend/admin/customers/index.js') }}?v={{ time() }}"></script>
@endpush
