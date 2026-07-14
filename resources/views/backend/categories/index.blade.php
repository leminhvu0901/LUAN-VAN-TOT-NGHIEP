@extends('backend.layouts.app')

@section('title', 'Quản lý Danh mục')

@section('content')
<div class="p-6 space-y-6">

    <!-- Phần 1: Tiêu đề trang & Nút Thêm mới -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Danh mục</h2>
            <p class="text-gray-500 text-sm mt-1">Tạo và quản lý các danh mục sản phẩm (Trà sữa, Cà phê,...).</p>
        </div>
        <div class="flex gap-4 w-full sm:w-auto">
            <a href="{{ route('admin.categories.create') }}"
                class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all w-full sm:w-auto">
                <span class="material-symbols-outlined">add</span>
                Thêm danh mục mới
            </a>
        </div>
    </div>

    @if(session('success') || $errors->any())
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    @if(session('success'))
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: '{{ session('success') }}',
                            timer: 2000,
                            showConfirmButton: false,
                            width: '320px',
                            padding: '1rem',
                            customClass: {
                                popup: 'rounded-xl shadow-xl border border-gray-100',
                                title: 'text-base font-bold text-gray-800',
                                htmlContainer: 'text-sm text-gray-500 mt-1',
                                icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            }
                        });
                    @endif

                    @if($errors->any())
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            html: `
                                <ul class="text-left text-sm text-gray-600 list-disc pl-5 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            `,
                            width: '320px',
                            padding: '1rem',
                            confirmButtonText: 'Đóng',
                            buttonsStyling: false,
                            customClass: {
                                popup: 'rounded-xl shadow-xl border border-gray-100',
                                title: 'text-base font-bold text-gray-800',
                                htmlContainer: 'mt-1',
                                confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                                icon: 'transform scale-[0.6] -mt-3 -mb-2',
                                actions: 'mt-3 w-full flex justify-center'
                            }
                        });
                    @endif
                }
            });
        </script>
        @endpush
    @endif

    <!-- Phần 2: Khung Thống kê -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Tổng -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Tổng danh mục</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalCategories) }}</p>
                <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                    <span class="material-symbols-outlined text-[14px]">category</span> danh mục
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">category</span>
            </div>
        </div>

        <!-- Card 2: Đang hoạt động -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Đang hiển thị</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($activeCategories) }}</p>
                <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                    <span class="material-symbols-outlined text-[14px]">check_circle</span> đang hoạt động
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
        </div>

        <!-- Card 3: Vô hiệu -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Đang ẩn / Vô hiệu</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($inactiveCategories) }}</p>
                <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                    Đã vô hiệu hóa
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 group-hover:bg-gray-100 group-hover:scale-110 transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">block</span>
            </div>
        </div>
    </div>

    <!-- Phần 3: Thanh Tìm kiếm và Lọc dữ liệu -->
    <div class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6">
        <form action="{{ route('admin.categories.index') }}" method="GET" id="filter-form"
            class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full xl:w-auto">
            <div class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-64 relative">
                <span class="material-symbols-outlined text-gray-400">search</span>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-4 w-full outline-none"
                    placeholder="Tìm danh mục...">
            </div>

            <select name="status" class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                <option value="all">Tất cả trạng thái</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Đang ẩn</option>
            </select>

            <select name="sort" class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                <option value="order_asc" {{ request('sort', 'order_asc') == 'order_asc' ? 'selected' : '' }}>Thứ tự: Nhỏ → Lớn</option>
                <option value="order_desc" {{ request('sort') == 'order_desc' ? 'selected' : '' }}>Thứ tự: Lớn → Nhỏ</option>
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
            </select>

            <a href="{{ route('admin.categories.index') }}" id="btn-clear-filter"
                class="px-4 py-2 text-gray-500 hover:text-red-500 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                style="display: {{ (request('search') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'order_asc')) ? 'inline-block' : 'none' }};">
                Xóa lọc
            </a>

            <!-- Bulk Delete Form -->
            <div id="bulk-delete-container" style="display: none;" class="w-full sm:w-auto ml-auto">
                <button type="button" onclick="submitBulkDelete()"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                    Xóa <span id="selected-count" class="mx-1">0</span> danh mục
                </button>
            </div>
        </form>
        <form id="bulk-delete-form" action="{{ route('admin.categories.bulk_delete') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>

    <!-- Phần 4: Bảng danh sách Danh mục -->
    <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
        @include('backend.categories.partials.table', ['categories' => $categories])
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/backend/categories/index.js') }}"></script>
@endpush
