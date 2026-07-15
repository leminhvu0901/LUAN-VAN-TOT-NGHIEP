@extends('backend.layouts.app')

@section('title', 'Quản lý Đánh giá')

@section('content')
    <div class="reviews-page">
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
                    <p class="text-sm text-gray-500 mt-2 sm:mt-1 sm:ml-[58px]">Kiểm duyệt và quản lý nhận xét, đánh giá từ
                        khách
                        hàng.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                    <button type="button" id="bulk-deselect-btn"
                        class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-50 text-gray-600 rounded-lg font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200"
                        title="Bỏ chọn tất cả">
                        <span class="material-symbols-outlined text-[18px] sm:text-[20px] shrink-0">deselect</span>
                        <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
                    </button>

                    <div id="bulk-delete-container" class="hidden flex-1 sm:flex-none">
                        <button type="button" id="bulk-delete-btn"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100"
                            title="Xóa đã chọn">
                            <span class="material-symbols-outlined text-[20px] shrink-0">delete_sweep</span>
                            <span class="font-semibold whitespace-nowrap">Xóa (<span id="selected-count"
                                    class="font-bold">0</span>)</span>
                        </button>
                    </div>

                    <a href="{{ route('admin.reviews.create') }}"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all border border-emerald-600">
                        <span class="material-symbols-outlined text-[20px] shrink-0">add</span>
                        <span class="whitespace-nowrap">Thêm Đánh giá</span>
                    </a>
                </div>
            </div>

            <!-- Thống kê -->

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 pb-2 sm:pb-0">
                <div
                    class="stat-card bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-4 sm:p-5 border border-indigo-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-3 sm:gap-4 relative overflow-hidden group">
                    <span
                        class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-indigo-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">rate_review</span>
                    <div
                        class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-indigo-100 flex items-center justify-center text-indigo-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                        <span class="material-symbols-outlined text-2xl"
                            style="font-variation-settings: 'FILL' 1;">rate_review</span>
                    </div>
                    <div class="space-y-1 min-w-0 z-10">
                        <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Tổng đánh giá</p>
                        <p id="stat-total-reviews"
                            class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">
                            {{ number_format($totalReviews) }}
                        </p>
                    </div>
                </div>

                <div
                    class="stat-card bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl p-4 sm:p-5 border border-emerald-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-3 sm:gap-4 relative overflow-hidden group">
                    <span
                        class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-emerald-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">visibility</span>
                    <div
                        class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-emerald-100 flex items-center justify-center text-emerald-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                        <span class="material-symbols-outlined text-2xl"
                            style="font-variation-settings: 'FILL' 1;">visibility</span>
                    </div>
                    <div class="space-y-1 min-w-0 z-10">
                        <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đang hiển thị</p>
                        <p id="stat-active-reviews"
                            class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">
                            {{ number_format($activeReviews) }}
                        </p>
                    </div>
                </div>

                <div
                    class="stat-card col-span-2 lg:col-span-1 bg-gradient-to-br from-rose-50 to-rose-100/50 rounded-2xl p-4 sm:p-5 border border-rose-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-3 sm:gap-4 relative overflow-hidden group">
                    <span
                        class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-rose-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">visibility_off</span>
                    <div
                        class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-rose-100 flex items-center justify-center text-rose-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                        <span class="material-symbols-outlined text-2xl"
                            style="font-variation-settings: 'FILL' 1;">visibility_off</span>
                    </div>
                    <div class="space-y-1 min-w-0 z-10">
                        <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đang bị ẩn</p>
                        <p id="stat-hidden-reviews"
                            class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">
                            {{ number_format($hiddenReviews) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form ẩn dùng để xóa nhiều -->
            <form id="bulk-delete-form" action="{{ route('admin.reviews.bulk_delete') }}" method="POST" class="hidden">
                @csrf
            </form>

            <!-- Thanh Tìm kiếm và Lọc dữ liệu -->
            <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 flex flex-col gap-4">
                <div class="flex items-center justify-between xl:hidden">
                    <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                    <button type="button"
                        onclick="document.getElementById('filter-wrapper').classList.toggle('hidden'); document.getElementById('filter-wrapper').classList.toggle('flex');"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> <span
                            class="hidden sm:inline">Bộ lọc</span>
                    </button>
                </div>

                <div id="filter-wrapper"
                    class="hidden xl:flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between w-full transition-all">
                    <form action="{{ route('admin.reviews.index') }}" method="GET" id="filter-form"
                        class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full">

                        <div
                            class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-amber-300 focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[280px] shrink-0">
                            <span class="material-symbols-outlined text-gray-400 text-[20px] shrink-0">search</span>
                            <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                                class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full pr-2 outline-none"
                                placeholder="Tìm tên khách hàng, sản phẩm...">
                        </div>

                        <select name="rating" id="rating-select"
                            class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-amber-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[150px] shrink-0">
                            <option value="all">Tất cả sao</option>
                            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Sao</option>
                            <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Sao</option>
                            <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Sao</option>
                            <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Sao</option>
                            <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Sao</option>
                        </select>

                        <select name="status" id="status-select"
                            class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-amber-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[150px] shrink-0">
                            <option value="all">Trạng thái</option>
                            <option value="visible" {{ request('status') == 'visible' ? 'selected' : '' }}>Đang hiển thị
                            </option>
                            <option value="hidden" {{ request('status') == 'hidden' ? 'selected' : '' }}>Bị ẩn</option>
                        </select>

                        <select name="sort" id="sort-select"
                            class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-amber-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[180px] shrink-0">
                            <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất
                            </option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                            <option value="highest_rating" {{ request('sort') == 'highest_rating' ? 'selected' : '' }}>Đánh
                                giá cao nhất</option>
                            <option value="lowest_rating" {{ request('sort') == 'lowest_rating' ? 'selected' : '' }}>Đánh giá
                                thấp nhất</option>
                        </select>

                        <div class="w-full lg:w-auto shrink-0 flex items-center gap-2 lg:ml-auto">
                            <a href="{{ route('admin.reviews.index') }}" id="btn-clear-filter"
                                class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow"
                                style="display: {{ (request('search') || (request('rating') && request('rating') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'flex' : 'none' }};">
                                <span class="material-symbols-outlined text-[20px] shrink-0">filter_alt_off</span>
                                <span class="whitespace-nowrap font-medium">Xóa lọc</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bảng danh sách Đánh giá -->
            <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
                @include('backend.reviews.partials.table', ['reviews' => $reviews])
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/backend/reviews/index.js') }}?v={{ time() }}"></script>
@endpush