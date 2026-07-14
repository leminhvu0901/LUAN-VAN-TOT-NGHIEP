@extends('backend.layouts.app')

@section('title', 'Quản lý Đánh giá')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <div class="flex items-center flex-wrap gap-1.5 text-sm text-gray-400 mb-2">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-600 transition-colors">Dashboard</a>
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    <span class="text-gray-700 font-medium">Quản lý Đánh giá</span>
                </div>
                <h1
                    class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-start sm:items-center gap-2.5">
                    <span
                        class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-200 text-amber-600 shadow-inner border border-amber-50 flex-shrink-0">
                        <span class="material-symbols-outlined text-2xl"
                            style="font-variation-settings:'FILL' 1">reviews</span>
                    </span>
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">Quản lý Đánh
                        giá</span>
                </h1>
                <p class="text-sm text-gray-500 mt-2 sm:mt-1 sm:ml-[58px]">Kiểm duyệt và quản lý nhận xét, đánh giá từ khách
                    hàng.</p>
            </div>

            <div class="w-full sm:w-auto mt-2 sm:mt-0">
                <a href="{{ route('admin.reviews.create') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 hover:-translate-y-0.5 shadow-sm hover:shadow-md transition-all duration-300">
                    <span class="material-symbols-outlined text-[20px] font-bold">add</span>
                    Thêm Đánh giá
                </a>
            </div>
        </div>

        <!-- Thống kê -->

        <div
            class="flex overflow-x-auto sm:grid sm:grid-cols-3 gap-3 sm:gap-4 pb-2 sm:pb-0 snap-x snap-mandatory hide-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
            <div
                class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-5 border border-indigo-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span
                    class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-indigo-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">rate_review</span>
                <div
                    class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-indigo-100 flex items-center justify-center text-indigo-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl"
                        style="font-variation-settings: 'FILL' 1;">rate_review</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Tổng đánh giá</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">
                        {{ number_format($totalReviews) }}</p>
                </div>
            </div>

            <div
                class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl p-5 border border-emerald-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span
                    class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-emerald-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">visibility</span>
                <div
                    class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-emerald-100 flex items-center justify-center text-emerald-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl"
                        style="font-variation-settings: 'FILL' 1;">visibility</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đang hiển thị</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">
                        {{ number_format($activeReviews) }}</p>
                </div>
            </div>

            <div
                class="stat-card snap-center shrink-0 w-[85%] sm:w-auto group bg-gradient-to-br from-rose-50 to-rose-100/50 rounded-2xl p-5 border border-rose-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span
                    class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-rose-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">visibility_off</span>
                <div
                    class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-rose-100 flex items-center justify-center text-rose-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl"
                        style="font-variation-settings: 'FILL' 1;">visibility_off</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đang bị ẩn</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">
                        {{ number_format($hiddenReviews) }}</p>
                </div>
            </div>
        </div>

        <!-- Form ẩn dùng để xóa nhiều -->
        <form id="bulk-delete-form" action="{{ route('admin.reviews.bulk_delete') }}" method="POST" style="display: none;">
            @csrf
        </form>

        <!-- Thanh Tìm kiếm và Lọc dữ liệu -->
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 mt-4 sm:mt-0">
            <form action="{{ route('admin.reviews.index') }}" method="GET" id="filter-form"
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                <div
                    class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-72 relative focus-within:ring-2 focus-within:ring-amber-500/20 focus-within:border-amber-500 transition-all">
                    <span class="material-symbols-outlined text-gray-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none"
                        placeholder="Tìm tên khách hàng, sản phẩm...">
                </div>

                <div class="grid grid-cols-2 sm:flex gap-3 w-full sm:w-auto">
                    <select name="rating"
                        class="px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                        <option value="all">Tất cả sao</option>
                        <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Sao</option>
                        <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Sao</option>
                        <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Sao</option>
                        <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Sao</option>
                        <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Sao</option>
                    </select>

                    <select name="status"
                        class="px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                        <option value="all">Trạng thái</option>
                        <option value="visible" {{ request('status') == 'visible' ? 'selected' : '' }}>Đang hiển thị</option>
                        <option value="hidden" {{ request('status') == 'hidden' ? 'selected' : '' }}>Bị ẩn</option>
                    </select>

                    <select name="sort"
                        class="col-span-2 sm:col-span-1 px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="highest_rating" {{ request('sort') == 'highest_rating' ? 'selected' : '' }}>Đánh giá
                            cao nhất</option>
                        <option value="lowest_rating" {{ request('sort') == 'lowest_rating' ? 'selected' : '' }}>Đánh giá thấp
                            nhất</option>
                    </select>
                </div>

                <a href="{{ route('admin.reviews.index') }}" id="btn-clear-filter"
                    class="px-4 py-2.5 text-gray-500 hover:text-red-500 hover:bg-red-50 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                    style="display: {{ (request('search') || (request('rating') && request('rating') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'inline-block' : 'none' }};">
                    Xóa lọc
                </a>

                <!-- Nút xóa nhiều (cùng hàng, bên trái) -->
                <div id="bulk-delete-container" style="display:none;" class="w-full sm:w-auto">
                    <button type="button" class="js-bulk-delete flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all w-full sm:w-auto">
                        <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                        Xóa <span id="selected-count" class="mx-1">0</span> đánh giá
                    </button>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách Đánh giá -->
        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
            @include('backend.reviews.partials.table', ['reviews' => $reviews])
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/backend/reviews/index.js') }}?v={{ time() }}"></script>
@endpush