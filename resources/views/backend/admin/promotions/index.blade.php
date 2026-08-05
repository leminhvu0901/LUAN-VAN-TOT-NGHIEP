@extends('backend.layouts.app')

@section('title', 'Quản lý Khuyến mãi')

@section('content')
    <div class="promotions-page">
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

            <!-- Phần 1: Tiêu đề trang & Nút Thêm mới -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Khuyến mãi</h2>
                    <p class="text-gray-500 text-sm mt-1">Tạo và quản lý mã giảm giá, voucher cho khách hàng.</p>
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

                    <a href="{{ route('admin.promotions.create') }}"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all border border-emerald-600">
                        <span class="material-symbols-outlined text-[20px] shrink-0">add</span>
                        <span class="whitespace-nowrap">Thêm mới</span>
                    </a>
                </div>
            </div>

            @if(session('success') || $errors->any())
                {{-- Dữ liệu flash để file JS riêng đọc và hiển thị SweetAlert --}}
                <div id="promotion-flash-data" data-success="{{ session('success') }}" data-errors='@json($errors->all())'
                    class="hidden">
                </div>
            @endif

            <!-- Phần 2: Khung Thống kê -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <!-- Card 1: Tổng -->
                <div
                    class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                    <div class="space-y-1 min-w-0">
                        <p class="font-semibold text-xs text-gray-500 truncate">Tổng khuyến mãi</p>
                        <p id="stat-total" class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">
                            {{ number_format($totalPromotions) }}</p>
                        <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                            <span class="material-symbols-outlined text-[14px]">local_offer</span> mã khuyến mãi
                        </p>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                        <span class="material-symbols-outlined text-lg icon-fill">sell</span>
                    </div>
                </div>

                <!-- Card 2: Đang hoạt động -->
                <div
                    class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                    <div class="space-y-1 min-w-0">
                        <p class="font-semibold text-xs text-gray-500 truncate">Đang diễn ra</p>
                        <p id="stat-active" class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">
                            {{ number_format($activePromotions) }}</p>
                        <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                            <span class="material-symbols-outlined text-[14px]">check_circle</span> đang áp dụng
                        </p>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                        <span class="material-symbols-outlined text-lg icon-fill">check_circle</span>
                    </div>
                </div>

                <!-- Card 3: Hết hạn / Vô hiệu -->
                <div
                    class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2 col-span-2 lg:col-span-1">
                    <div class="space-y-1 min-w-0">
                        <p class="font-semibold text-xs text-gray-500 truncate">Hết hạn / Vô hiệu</p>
                        <p id="stat-expired" class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">
                            {{ number_format($expiredPromotions) }}</p>
                        <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                            Đã vô hiệu hóa
                        </p>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 group-hover:bg-gray-100 group-hover:scale-110 transition-all flex-shrink-0">
                        <span class="material-symbols-outlined text-lg icon-fill">block</span>
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
                        <span class="material-symbols-outlined text-[18px]">filter_list</span> <span
                            class="hidden sm:inline">Bộ lọc</span>
                    </button>
                </div>

                <div id="filter-wrapper"
                    class="hidden xl:flex flex-col xl:flex-row gap-3 items-start xl:items-center w-full transition-all">
                    <form action="{{ route('admin.promotions.index') }}" method="GET" id="filter-form"
                        class="flex flex-col sm:flex-row flex-wrap xl:flex-nowrap gap-2 items-stretch sm:items-center w-full min-w-0">

                        {{-- Ô tìm kiếm --}}
                        <div class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500 w-full sm:w-auto xl:w-[200px] xl:shrink-0">
                            <span class="material-symbols-outlined text-gray-400 text-[18px] shrink-0">search</span>
                            <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                                class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none min-w-0"
                                placeholder="Tìm mã...">
                        </div>

                        {{-- Loại --}}
                        <select name="type"
                            class="custom-select-init px-2 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-auto xl:min-w-0 xl:flex-1">
                            <option value="all">Tất cả loại</option>
                            <option value="percent" {{ request('type') == 'percent' ? 'selected' : '' }}>Giảm %</option>
                            <option value="fixed" {{ request('type') == 'fixed' ? 'selected' : '' }}>Giảm tiền</option>
                        </select>

                        {{-- Kênh --}}
                        <select name="applies_to"
                            class="custom-select-init px-2 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-auto xl:min-w-0 xl:flex-1">
                            <option value="all">Tất cả kênh</option>
                            <option value="pickup" {{ request('applies_to') == 'pickup' ? 'selected' : '' }}>Tại quầy</option>
                            <option value="delivery" {{ request('applies_to') == 'delivery' ? 'selected' : '' }}>Giao hàng</option>
                        </select>

                        {{-- Trạng thái --}}
                        <select name="status"
                            class="custom-select-init px-2 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-auto xl:min-w-0 xl:flex-1">
                            <option value="all">Tất cả trạng thái</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang diễn ra</option>
                            <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Sắp tới</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Vô hiệu</option>
                        </select>

                        {{-- Xác nhận --}}
                        <select name="verification"
                            class="custom-select-init px-2 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-auto xl:min-w-0 xl:flex-1">
                            <option value="all">Tất cả mã</option>
                            <option value="yes" {{ request('verification') == 'yes' ? 'selected' : '' }}>Cần xác nhận</option>
                            <option value="no" {{ request('verification') == 'no' ? 'selected' : '' }}>Tự động áp</option>
                        </select>

                        {{-- Sắp xếp --}}
                        <select name="sort"
                            class="custom-select-init px-2 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-full sm:w-auto xl:min-w-0 xl:flex-1">
                            <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                            <option value="value_asc" {{ request('sort') == 'value_asc' ? 'selected' : '' }}>Giá trị ↑</option>
                            <option value="value_desc" {{ request('sort') == 'value_desc' ? 'selected' : '' }}>Giá trị ↓</option>
                        </select>

                        <button type="submit" class="flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors xl:shrink-0 whitespace-nowrap">
                            Lọc
                        </button>

                        {{-- Nút Xóa lọc — luôn inline trong cùng hàng --}}
                        <a href="{{ route('admin.promotions.index') }}" id="btn-clear-filter"
                            class="flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors xl:shrink-0 whitespace-nowrap"
                            style="display: {{ (request('search') || (request('type') && request('type') != 'all') || (request('applies_to') && request('applies_to') != 'all') || (request('status') && request('status') != 'all') || (request('verification') && request('verification') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'flex' : 'none' }};">
                            <span class="material-symbols-outlined text-[18px] shrink-0">filter_alt_off</span>
                            <span class="hidden sm:inline font-medium">Xóa lọc</span>
                        </a>
                    </form>

                    <form id="bulk-delete-form" action="{{ route('admin.promotions.bulk_delete') }}" method="POST"
                        class="hidden">
                        @csrf
                        <input type="hidden" name="total_promotions_count" id="total-promotions-count" value="0">
                    </form>
                </div>
            </div>

            <!-- Phần 4: Bảng danh sách Khuyến mãi -->
            <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
                @include('backend.admin.promotions.partials.table', ['promotions' => $promotions])
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const flashData = document.getElementById("promotion-flash-data");
    if (flashData && typeof Swal !== "undefined") {
        const successMessage = flashData.dataset.success || "";
        const errors = flashData.dataset.errors ? JSON.parse(flashData.dataset.errors) : [];

        if (successMessage) {
            Swal.fire({
                icon: "success",
                title: "Thành công!",
                text: successMessage,
                timer: 2000,
                showConfirmButton: false,
                width: "320px",
                padding: "1rem",
                customClass: {
                    popup: "rounded-xl shadow-xl border border-gray-100",
                    title: "text-base font-bold text-gray-800",
                    htmlContainer: "text-sm text-gray-500 mt-1",
                    icon: "transform scale-[0.6] -mt-3 -mb-2",
                },
            });
        } else if (errors.length > 0) {
            const errorHtml = errors
                .map((error) => `<li>${escapeHtml(error)}</li>`)
                .join("");

            Swal.fire({
                icon: "error",
                title: "Lỗi",
                html: `<ul class="text-left text-sm text-gray-600 list-disc pl-5 space-y-1">${errorHtml}</ul>`,
                width: "320px",
                padding: "1rem",
                confirmButtonText: "Đóng",
                buttonsStyling: false,
                customClass: {
                    popup: "rounded-xl shadow-xl border border-gray-100",
                    title: "text-base font-bold text-gray-800",
                    confirmButton:
                        "px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm",
                    icon: "transform scale-[0.6] -mt-3 -mb-2",
                    actions: "mt-3 w-full flex justify-center",
                },
            });
        }
    }

    const bulkDeleteContainer = document.getElementById("bulk-delete-container");
    const selectedCountSpan = document.getElementById("selected-count");
    const deselectBtn = document.getElementById("bulk-deselect-btn");
    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    function getCheckedIds() {
        return Array.from(document.querySelectorAll(".row-checkbox:checked")).map((cb) => cb.value);
    }

    function updateBulkDeleteButton() {
        const count = getCheckedIds().length;
        if (bulkDeleteContainer) bulkDeleteContainer.classList.toggle("hidden", count === 0);
        if (deselectBtn) deselectBtn.classList.toggle("hidden", count === 0);
        if (selectedCountSpan) selectedCountSpan.textContent = count;
    }

    function syncSelectAllCheckboxes() {
        const allCheckboxes = document.querySelectorAll(".row-checkbox");
        const isAllChecked = allCheckboxes.length > 0 && document.querySelectorAll(".row-checkbox:checked").length === allCheckboxes.length;
        const selectAllEl = document.getElementById("selectAll");
        const selectAllMobileEl = document.getElementById("selectAll-mobile");
        if (selectAllEl) selectAllEl.checked = isAllChecked;
        if (selectAllMobileEl) selectAllMobileEl.checked = isAllChecked;
    }

    document.addEventListener("change", function (e) {
        if (e.target && e.target.classList.contains("js-select-all")) {
            const checked = e.target.checked;
            document.querySelectorAll(".js-select-all").forEach((el) => (el.checked = checked));
            document.querySelectorAll(".row-checkbox").forEach((cb) => (cb.checked = checked));
            updateBulkDeleteButton();
        }
        if (e.target && e.target.classList.contains("row-checkbox")) {
            syncSelectAllCheckboxes();
            updateBulkDeleteButton();
        }
    });

    if (deselectBtn) {
        deselectBtn.addEventListener("click", function () {
            document.querySelectorAll(".js-select-all, .row-checkbox").forEach((el) => (el.checked = false));
            updateBulkDeleteButton();
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm(`Xóa ${ids.length} mã khuyến mãi đã chọn? Hành động này không thể hoàn tác.`)) return;

            bulkDeleteForm.querySelectorAll('input[name="promotion_ids[]"]').forEach((el) => el.remove());
            ids.forEach((id) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "promotion_ids[]";
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    }
});

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}
</script>
@endpush