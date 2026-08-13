@extends('backend.layouts.app')

@section('title', 'Quản lý Danh mục')

@section('content')
<div class="p-6 space-y-6">

    <!-- Tiêu đề trang & Nút Thêm mới -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Danh mục</h2>
            <p class="text-gray-500 text-sm mt-1">Tạo và quản lý các danh mục sản phẩm (Trà sữa, Cà phê,...).</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
            <button type="button" id="bulk-deselect-btn" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-50 text-gray-600 rounded-lg font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200" title="Bỏ chọn tất cả">
                <span class="material-symbols-outlined text-[18px] sm:text-[20px] shrink-0">deselect</span>
                <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
            </button>

            <button type="button" id="bulk-delete-btn" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100" title="Xóa đã chọn">
                <span class="material-symbols-outlined text-[20px] shrink-0">delete_sweep</span>
                <span class="font-semibold whitespace-nowrap">Xóa <span id="selected-count" class="mx-1">0</span> danh mục</span>
            </button>

            <a href="{{ route('admin.categories.create') }}"
                class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all border border-emerald-600">
                <span class="material-symbols-outlined text-[20px] shrink-0">add</span>
                <span class="whitespace-nowrap">Thêm mới</span>
            </a>
        </div>
    </div>

    @if(session('success') || $errors->any())
        @push('scripts')
        <script>
            @if(session('success'))
                window.flashSuccessMessage = {!! json_encode(session('success')) !!};
            @endif
            @if($errors->any())
                window.flashErrorMessages = {!! json_encode($errors->all()) !!};
                window.flashErrorTitle = 'Lỗi';
            @endif
        </script>
        @endpush
    @endif

    <!-- Khung Thống kê -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Tổng -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Tổng danh mục</p>
                <p id="total-categories-stat" class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalCategories) }}</p>
                <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                    <span class="material-symbols-outlined text-[14px]">category</span> danh mục
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                <span class="material-symbols-outlined text-lg icon-fill">category</span>
            </div>
        </div>

        <!-- Card 2: Đang hoạt động -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Đang hiển thị</p>
                <p id="active-categories-stat" class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($activeCategories) }}</p>
                <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                    <span class="material-symbols-outlined text-[14px]">check_circle</span> đang hoạt động
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                <span class="material-symbols-outlined text-lg icon-fill">check_circle</span>
            </div>
        </div>

        <!-- Card 3: Vô hiệu -->
        <div class="col-span-2 md:col-span-1 bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Đang ẩn / Vô hiệu</p>
                <p id="inactive-categories-stat" class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($inactiveCategories) }}</p>
                <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                    Đã vô hiệu hóa
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 group-hover:bg-gray-100 group-hover:scale-110 transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-lg icon-fill">block</span>
            </div>
        </div>
    </div>

    <!-- Thanh Tìm kiếm và Lọc dữ liệu -->
    <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 flex flex-col gap-4">
        <div class="flex items-center justify-between xl:hidden">
            <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
            <button type="button" onclick="toggleFilterPanel('filter-wrapper')" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                <span class="material-symbols-outlined text-[18px]">filter_list</span> <span class="hidden sm:inline">Bộ lọc</span>
            </button>
        </div>
        
        <div id="filter-wrapper" class="hidden xl:flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between w-full transition-all">
            <form action="{{ route('admin.categories.index') }}" method="GET" id="filter-form"
                class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full">
                
                <div class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[280px] shrink-0">
                    <span class="material-symbols-outlined text-gray-400 text-[20px] shrink-0">search</span>
                    <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                        class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                        placeholder="Tìm danh mục...">
                </div>

                <select name="status" id="status-select" class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[160px] shrink-0">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Đang ẩn</option>
                </select>

                <select name="sort" id="sort-select" class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[160px] shrink-0">
                    <option value="order_asc" {{ request('sort', 'order_asc') == 'order_asc' ? 'selected' : '' }}>Thứ tự: Nhỏ → Lớn</option>
                    <option value="order_desc" {{ request('sort') == 'order_desc' ? 'selected' : '' }}>Thứ tự: Lớn → Nhỏ</option>
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                </select>

                <div class="w-full lg:w-auto shrink-0 lg:ml-auto flex items-center gap-2">
                    <button type="submit" class="flex-1 lg:flex-none px-5 py-1.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors organic-shadow">
                        Lọc
                    </button>
                    <a href="{{ route('admin.categories.index') }}" id="btn-clear-filter"
                        class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow"
                        style="display: {{ (request('search') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'order_asc')) ? 'flex' : 'none' }};">
                        <span class="material-symbols-outlined text-[20px] shrink-0">filter_alt_off</span>
                        <span class="whitespace-nowrap font-medium">Xóa lọc</span>
                    </a>
                </div>
            </form>
            <form id="bulk-delete-form" action="{{ route('admin.categories.bulk_delete') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    <!-- Bảng danh sách Danh mục -->
    <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100 flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
        <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
            <div id="table-loader" class="absolute inset-0 bg-white/50 z-20 hidden items-center justify-center transition-all duration-300">
            </div>
            
            <div id="categories-table-wrapper" class="flex-1 flex flex-col min-h-0 relative w-full">
                @include('backend.admin.categories.partials.table', ['categories' => $categories])
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkDeselectBtn = document.getElementById('bulk-deselect-btn');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    // Lấy danh sách ID danh mục đang được chọn
    function getCheckedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    }

    // Cập nhật trạng thái hiển thị và số lượng của nút xóa hàng loạt
    function updateBulkDeleteUI() {
        const count = getCheckedIds().length;
        if (bulkDeleteBtn) bulkDeleteBtn.classList.toggle('hidden', count === 0);
        if (bulkDeselectBtn) bulkDeselectBtn.classList.toggle('hidden', count === 0);
        if (selectedCountSpan) selectedCountSpan.textContent = count;
    }

    // Xử lý sự kiện khi thay đổi trạng thái checkbox chọn tất cả hoặc từng dòng
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('js-select-all')) {
            const isChecked = e.target.checked;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = isChecked);
            document.querySelectorAll('.row-checkbox:not(:disabled)').forEach(cb => cb.checked = isChecked);
            updateBulkDeleteUI();
        } else if (e.target.classList.contains('row-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
            const allChecked = allCheckboxes.length > 0 && document.querySelectorAll('.row-checkbox:not(:disabled):checked').length === allCheckboxes.length;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);
            updateBulkDeleteUI();
        }
    });

    // Bỏ chọn tất cả checkbox đang được tích
    if (bulkDeselectBtn) {
        bulkDeselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteUI();
        });
    }

    // Xác nhận và gửi yêu cầu xóa các danh mục đã chọn
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm(`Bạn chuẩn bị xóa ${ids.length} danh mục đã chọn. Tiếp tục?`)) return;

            // Đưa danh sách ID vào form ẩn và submit
            bulkDeleteForm.querySelectorAll('input[name="category_ids[]"]').forEach(el => el.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'category_ids[]';
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    }
});
</script>
@endpush

