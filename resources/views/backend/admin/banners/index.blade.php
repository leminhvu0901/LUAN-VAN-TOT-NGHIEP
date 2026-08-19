@extends('backend.layouts.app')

@section('title', 'Quản lý Banner')

@section('content')
    <div class="banners-page p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- Tiêu đề trang & Nút Thêm mới -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Banner</h2>
                <p class="text-gray-500 text-sm mt-1">Tạo và lên lịch hiển thị banner quảng cáo cho trang chủ.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <button type="button" id="bulk-deselect-btn"
                    class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-50 text-gray-600 rounded-lg font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200"
                    title="Bỏ chọn tất cả">
                    <i class="fa-solid fa-arrow-rotate-left text-[14px] shrink-0"></i>
                    <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
                </button>

                <button type="button" id="bulk-delete-btn"
                    class="hidden flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100"
                    title="Xóa đã chọn">
                    <i class="fa-solid fa-trash-can text-[14px] shrink-0"></i>
                    <span class="font-semibold whitespace-nowrap">Xóa <span id="selected-count" class="mx-1">0</span>
                        banner</span>
                </button>

                <a href="{{ route('admin.banners.create') }}"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all border border-emerald-600">
                    <i class="fa-solid fa-plus text-[14px] shrink-0"></i>
                    <span class="whitespace-nowrap">Thêm banner</span>
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
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- Tổng -->
            <div
                class="bg-white p-3 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng banner</p>
                    <p id="total-banners-stat" class="text-xl sm:text-2xl font-bold text-gray-900 truncate">
                        {{ number_format($totalBanners) }}</p>
                    <p class="text-emerald-600 font-medium text-[10px] sm:text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-images text-[11px]"></i> banner
                    </p>
                </div>
                <div
                    class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-images text-sm"></i>
                </div>
            </div>

            <!-- Đang hiển thị -->
            <div
                class="bg-white p-3 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đang hiển thị</p>
                    <p id="active-banners-stat" class="text-xl sm:text-2xl font-bold text-gray-900 truncate">
                        {{ number_format($activeBanners) }}</p>
                    <p class="text-emerald-600 font-medium text-[10px] sm:text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-circle-check text-[11px]"></i> hoạt động
                    </p>
                </div>
                <div
                    class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                </div>
            </div>

            <!-- Sắp diễn ra -->
            <div
                class="bg-white p-3 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-blue-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp diễn ra</p>
                    <p id="upcoming-banners-stat" class="text-xl sm:text-2xl font-bold text-gray-900 truncate">
                        {{ number_format($upcomingBanners) }}</p>
                    <p class="text-blue-500 font-medium text-[10px] sm:text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-clock text-[11px]"></i> lên lịch
                    </p>
                </div>
                <div
                    class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-clock text-sm"></i>
                </div>
            </div>

            <!-- Đã hết hạn -->
            <div
                class="bg-white p-3 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã hết hạn</p>
                    <p id="expired-banners-stat" class="text-xl sm:text-2xl font-bold text-gray-900 truncate">
                        {{ number_format($expiredBanners) }}</p>
                    <p class="text-red-500 font-medium text-[10px] sm:text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-calendar-xmark text-[11px]"></i> hết
                        hiệu lực
                    </p>
                </div>
                <div
                    class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-calendar-xmark text-sm"></i>
                </div>
            </div>

            <!-- Đang ẩn -->
            <div
                class="col-span-2 lg:col-span-1 bg-white p-3 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-gray-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đang ẩn</p>
                    <p id="inactive-banners-stat" class="text-xl sm:text-2xl font-bold text-gray-900 truncate">
                        {{ number_format($inactiveBanners) }}</p>
                    <p class="text-gray-500 font-medium text-[10px] sm:text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-eye-slash text-[11px]"></i> tắt hiển
                        thị
                    </p>
                </div>
                <div
                    class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 group-hover:bg-gray-100 group-hover:scale-110 transition-all flex-shrink-0">
                    <i class="fa-solid fa-eye-slash text-sm"></i>
                </div>
            </div>
        </div>

        <!-- Thanh Tìm kiếm và Lọc dữ liệu -->
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 flex flex-col gap-4">
            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button"
                    onclick="toggleFilterPanel('filter-wrapper')"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <i class="fa-solid fa-filter text-[14px]"></i> <span class="hidden sm:inline">Bộ
                        lọc</span>
                </button>
            </div>

            <div id="filter-wrapper"
                class="hidden xl:flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between w-full transition-all">
                <form action="{{ route('admin.banners.index') }}" method="GET" id="filter-form"
                    class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full">

                    <!-- Tìm kiếm -->
                    <div
                        class="flex items-center gap-2 px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[280px] shrink-0">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 text-[14px] shrink-0"></i>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                            placeholder="Tìm banner...">
                    </div>

                    <!-- Lọc theo trạng thái -->
                    <select name="status" id="status-select"
                        class="custom-select-init px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[160px] shrink-0">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hiển thị</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Sắp diễn ra</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Đang ẩn</option>
                    </select>

                    <!-- Sắp xếp -->
                    <select name="sort" id="sort-select"
                        class="custom-select-init px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-[calc(50%-0.375rem)] lg:w-[180px] shrink-0">
                        <option value="order_asc" {{ request('sort', 'order_asc') == 'order_asc' ? 'selected' : '' }}>Thứ tự:
                            Nhỏ → Lớn</option>
                        <option value="order_desc" {{ request('sort', 'order_desc') == 'order_desc' ? 'selected' : '' }}>Thứ tự: Lớn → Nhỏ
                        </option>
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                    </select>

                    <div class="w-full lg:w-auto shrink-0 lg:ml-auto flex items-center gap-2">
                        <button type="submit" class="flex-1 lg:flex-none px-5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors organic-shadow">
                            Lọc
                        </button>
                        <a href="{{ route('admin.banners.index') }}" id="btn-clear-filter"
                            class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-1.5 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow"
                            style="display: {{ (request('search') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'order_asc')) ? 'flex' : 'none' }};">
                            <i class="fa-solid fa-filter-circle-xmark text-[16px] shrink-0"></i>
                            <span class="whitespace-nowrap font-medium">Xóa lọc</span>
                        </a>
                    </div>
                </form>
                <form id="bulk-delete-form" action="{{ route('admin.banners.bulk_delete') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>

        <!-- Danh sách Banner -->
        <div
            class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100 flex flex-col lg:h-[calc(100vh-230px)] lg:min-h-[500px] h-auto min-h-0 w-full">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                <div id="table-loader"
                    class="absolute inset-0 bg-white/50 z-20 hidden items-center justify-center transition-all duration-300">
                </div>

                <div id="banners-table-wrapper" class="flex-1 flex flex-col min-h-0 relative w-full">
                    @include('backend.admin.banners.partials.table', ['banners' => $banners])
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

    // Lấy danh sách ID banner đang được chọn
    function getCheckedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    }

    // Cập nhật trạng thái hiển thị và số lượng của nút xóa hàng loạt
    function updateBulkDeleteButton() {
        const count = getCheckedIds().length;
        if (selectedCountSpan) selectedCountSpan.textContent = count;
        if (bulkDeleteBtn) {
            bulkDeleteBtn.classList.toggle('hidden', count === 0);
            bulkDeleteBtn.classList.toggle('flex', count > 0);
        }
        if (bulkDeselectBtn) {
            bulkDeselectBtn.classList.toggle('hidden', count === 0);
            bulkDeselectBtn.classList.toggle('flex', count > 0);
        }
    }

    // Xử lý sự kiện khi thay đổi trạng thái checkbox chọn tất cả hoặc từng dòng
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('js-select-all')) {
            const isChecked = e.target.checked;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = isChecked);
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = isChecked);
            updateBulkDeleteButton();
        } else if (e.target.classList.contains('row-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const allChecked = allCheckboxes.length > 0 && document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);
            updateBulkDeleteButton();
        }
    });

    // Bỏ chọn tất cả checkbox đang được tích
    if (bulkDeselectBtn) {
        bulkDeselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteButton();
        });
    }

    // Xác nhận và gửi yêu cầu xóa các banner đã chọn
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            window.AdminAlert.confirm(
                `Bạn chuẩn bị xóa ${ids.length} banner đã chọn. Tiếp tục?`,
                function () {
                    // Đưa danh sách ID vào form ẩn và submit
                    bulkDeleteForm.querySelectorAll('input[name="banner_ids[]"]').forEach(el => el.remove());
                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'banner_ids[]';
                        input.value = id;
                        bulkDeleteForm.appendChild(input);
                    });
                    bulkDeleteForm.submit();
                },
                'Xác nhận xóa hàng loạt'
            );
        });
    }
});
</script>
@endpush