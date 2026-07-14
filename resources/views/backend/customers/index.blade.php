@extends('backend.layouts.app')

@section('title', 'Quản lý Khách hàng - Admin')

@section('content')
    <div class="space-y-6 sm:space-y-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Quản lý Khách hàng</h1>
                <p class="text-gray-500 text-sm mt-1">Quản lý danh sách thành viên, hạng và lịch sử tích lũy.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all font-medium text-sm shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Thêm Khách hàng
                </a>
            </div>
        </div>

        <!-- Thống kê -->

        <div class="flex overflow-x-auto sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pb-2 sm:pb-0 snap-x snap-mandatory hide-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
            <!-- Card 1: Tổng khách hàng -->
            <div class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-5 border border-indigo-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-indigo-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">group</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-indigo-100 flex items-center justify-center text-indigo-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">group</span>
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
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
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
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">person_add</span>
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
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">block</span>
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
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 mt-4 sm:mt-0">
            <form action="{{ route('admin.customers.index') }}" method="GET" id="filter-form"
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                <div class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-72 relative focus-within:ring-2 focus-within:ring-amber-500/20 focus-within:border-amber-500 transition-all">
                    <span class="material-symbols-outlined text-gray-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none"
                        placeholder="Tìm tên, email, SĐT...">
                </div>

                <div class="grid grid-cols-2 sm:flex gap-3 w-full sm:w-auto">
                    <select name="membership"
                        class="px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                        <option value="all">Tất cả hạng</option>
                        <option value="diamond" {{ request('membership') == 'diamond' ? 'selected' : '' }}>Kim cương</option>
                        <option value="gold" {{ request('membership') == 'gold' ? 'selected' : '' }}>Vàng</option>
                        <option value="silver" {{ request('membership') == 'silver' ? 'selected' : '' }}>Bạc</option>
                        <option value="new" {{ request('membership') == 'new' ? 'selected' : '' }}>Mới</option>
                    </select>

                    <select name="status"
                        class="px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                        <option value="all">Trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Bị khóa</option>
                    </select>

                    <select name="sort"
                        class="col-span-2 sm:col-span-1 px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="points_desc" {{ request('sort') == 'points_desc' ? 'selected' : '' }}>Điểm: Cao → Thấp</option>
                        <option value="points_asc" {{ request('sort') == 'points_asc' ? 'selected' : '' }}>Điểm: Thấp → Cao</option>
                    </select>
                </div>

                <a href="{{ route('admin.customers.index') }}" id="btn-clear-filter"
                    class="px-4 py-2.5 text-gray-500 hover:text-red-500 hover:bg-red-50 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                    style="display: {{ (request('search') || (request('membership') && request('membership') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'inline-block' : 'none' }};">
                    Xóa lọc
                </a>

                <!-- Nút xóa nhiều -->
                <div id="bulk-delete-container" style="display:none;" class="w-full sm:w-auto">
                    <button type="button" class="js-bulk-delete flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all w-full sm:w-auto">
                        <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                        Xóa <span id="selected-count" class="mx-1">0</span> tài khoản
                    </button>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách Khách hàng -->
        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
            @include('backend.customers.partials.table', ['customers' => $customers])
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/backend/customers/index.js') }}?v={{ time() }}"></script>
@endpush
