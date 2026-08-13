@extends('backend.layouts.app')

@section('title', 'Quản lý Sản phẩm')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6 products-page">

        <!-- Tiêu đề trang & Nút Thêm mới -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Sản phẩm</h2>
                <p class="text-gray-500 text-sm mt-1">Quản lý danh sách, giá bán, và cấu hình các sản phẩm kinh doanh.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <button type="button" id="bulk-deselect-btn"
                    class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-50 text-gray-600 rounded-lg font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200"
                    title="Bỏ chọn tất cả">
                    <span class="material-symbols-outlined text-[18px] sm:text-[20px] shrink-0">deselect</span>
                    <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
                </button>

                <button type="button" id="bulk-delete-btn"
                    class="hidden flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100"
                    title="Xóa đã chọn">
                    <span class="material-symbols-outlined text-[20px] shrink-0">delete_sweep</span>
                    <span class="font-semibold whitespace-nowrap">Xóa <span id="selected-count" class="mx-1">0</span> sản
                        phẩm</span>
                </button>

                <a href="{{ route('admin.products.create') }}"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all w-full sm:w-auto mt-1 sm:mt-0">
                    <span class="material-symbols-outlined shrink-0">add</span>
                    <span class="whitespace-nowrap">Thêm sản phẩm mới</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Khung Thống kê -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
            <!-- Card 1 -->
            <div
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng sản phẩm</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalProducts) }}</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">inventory_2</span> sản phẩm
                    </p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">inventory</span>
                </div>
            </div>

            <!-- Card 2 -->
            <div
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đang kinh doanh</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($activeProducts) }}
                    </p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span> đang bán
                    </p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg icon-fill">check_circle</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div
                class="col-span-2 md:col-span-1 bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Ngừng kinh doanh</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($inactiveProducts) }}
                    </p>
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
        <div
            class="bg-white p-3 sm:p-4 rounded-xl organic-shadow border border-gray-100 flex flex-col gap-4 relative z-20 mb-6">

            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button"
                    onclick="toggleFilterPanel('filter-form')"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> <span class="hidden sm:inline">Bộ
                        lọc</span>
                </button>
            </div>

            <form action="{{ route('admin.products.index') }}" method="GET" id="filter-form"
                class="hidden xl:flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch xl:items-center w-full transition-all">

                <div
                    class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 w-full xl:max-w-[280px] relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                    <span class="material-symbols-outlined text-gray-400 text-[20px]">search</span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                        placeholder="Tìm tên sản phẩm, SKU...">
                </div>

                <select name="category_id" data-width-class="w-full sm:w-[160px]"
                    class="custom-select-init px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <option value="all">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" data-width-class="w-full sm:w-[160px]"
                    class="custom-select-init px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang kinh doanh</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Ngừng kinh doanh</option>
                </select>

                <select name="sort" data-width-class="w-full sm:w-[160px]"
                    class="custom-select-init px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Giá: Thấp đến cao
                    </option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Giá: Cao đến thấp
                    </option>
                </select>

                <div class="flex items-center gap-2 w-full xl:w-auto shrink-0">
                    <button type="submit" class="flex-1 xl:flex-none px-5 py-1.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors organic-shadow text-center w-full sm:w-auto">
                        Lọc
                    </button>
                    <a href="{{ route('admin.products.index') }}" id="btn-clear-filter"
                        class="flex-1 xl:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow text-center w-full sm:w-auto"
                        style="display: {{ (request('search') || (request('category_id') && request('category_id') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'flex' : 'none' }};">
                        <span class="material-symbols-outlined text-[20px]">filter_alt_off</span>
                        Xóa lọc
                    </a>
                </div>
            </form>
            <input type="hidden" id="total-products-count" value="{{ $products->total() }}">
        </div>

        <!-- Bảng danh sách Sản phẩm -->
        <div
            class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                {{-- Biểu tượng Loading hiển thị lên khi đang gửi request AJAX --}}
                <div id="table-loader"
                    class="absolute inset-0 bg-white/50 z-20 hidden items-center justify-center transition-all duration-300">
                    {{-- Đã bỏ biểu tượng xoay vòng theo yêu cầu --}}
                </div>

                <div id="products-table-wrapper" class="flex-1 flex flex-col min-h-0 overflow-x-auto custom-scrollbar relative w-full">
                    @include('backend.admin.products.partials.table', ['products' => $products])
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="bulk-delete-form" method="POST" action="{{ route('admin.products.bulk_delete') }}" class="hidden">
            @csrf
        </form>
    </div>

@endsection

@push('scripts')
<script>
// Tìm phần tử đang cuộn để giữ nguyên vị trí cuộn sau khi thao tác
function getScrollContainer() {
    return document.getElementById("main-content-area");
}

// Bật/tắt và cập nhật số đếm trên nút "Xóa đã chọn" theo số dòng đang tích
function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
    const selectedCountSpan = document.getElementById("selected-count");
    const count = document.querySelectorAll(".product-checkbox:checked").length;

    if (selectedCountSpan) {
        selectedCountSpan.textContent = count > 0 ? `(${count})` : "";
    }

    if (!bulkDeleteBtn) return;

    if (count > 0) {
        bulkDeleteBtn.classList.remove("hidden");
        bulkDeleteBtn.classList.add("flex");
        if (bulkDeselectBtn) {
            bulkDeselectBtn.classList.remove("hidden");
            bulkDeselectBtn.classList.add("flex");
        }
    } else {
        bulkDeleteBtn.classList.add("hidden");
        bulkDeleteBtn.classList.remove("flex");
        if (bulkDeselectBtn) {
            bulkDeselectBtn.classList.add("hidden");
            bulkDeselectBtn.classList.remove("flex");
        }
    }
}

// Tích/bỏ tích toàn bộ dòng trong trang khi bấm ô "chọn tất cả"
function handleSelectAll(checked) {
    document.querySelectorAll(".product-checkbox").forEach((checkbox) => {
        checkbox.checked = checked;
    });
    updateBulkDeleteButton();
}

// Đồng bộ ô "chọn tất cả" ở đầu bảng với trạng thái thực tế của các dòng bên dưới
function syncSelectAllCheckboxes() {
    const checkboxes = document.querySelectorAll(".product-checkbox");
    const allChecked = checkboxes.length > 0 && document.querySelectorAll(".product-checkbox:checked").length === checkboxes.length;
    document.querySelectorAll(".js-select-all").forEach((el) => (el.checked = allChecked));
}

// Gom id đã chọn, hỏi xác nhận rồi gửi form xóa hàng loạt lên server
function submitBulkDelete() {
    const ids = Array.from(document.querySelectorAll(".product-checkbox:checked")).map((cb) => cb.value);
    if (ids.length === 0) return;

    if (!confirm(`Bạn chuẩn bị xóa ${ids.length} sản phẩm đã chọn. Tiếp tục?`)) return;

    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    if (!bulkDeleteForm) return;

    bulkDeleteForm.querySelectorAll('input[name="product_ids[]"]').forEach((el) => el.remove());
    ids.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "product_ids[]";
        input.value = id;
        bulkDeleteForm.appendChild(input);
    });

    window.pendingScrollY = getScrollContainer()?.scrollTop ?? 0;
    bulkDeleteForm.submit();
}

// Hỏi xác nhận trước khi xóa một sản phẩm
function confirmDeleteProduct(event, formElement) {
    if (!confirm("Sản phẩm này sẽ bị xóa vĩnh viễn khỏi hệ thống. Tiếp tục?")) {
        event.preventDefault();
        return false;
    }
    window.pendingScrollY = getScrollContainer()?.scrollTop ?? 0;
    return true;
}

// Gắn sự kiện cho bảng danh sách sản phẩm
function initProductTableEvents(tableContainer) {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");

    window.submitBulkDelete = submitBulkDelete;
    window.confirmDeleteProduct = confirmDeleteProduct;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }
    if (bulkDeselectBtn) {
        bulkDeselectBtn.addEventListener("click", function () {
            document.querySelectorAll(".product-checkbox, .js-select-all").forEach((el) => (el.checked = false));
            updateBulkDeleteButton();
        });
    }

    // Lắng nghe sự kiện thay đổi checkbox trên bảng sản phẩm
    tableContainer.addEventListener("change", function (event) {
        if (event.target.classList.contains("js-select-all")) {
            handleSelectAll(event.target.checked);
            return;
        }
        if (event.target.classList.contains("product-checkbox")) {
            syncSelectAllCheckboxes();
            updateBulkDeleteButton();
        }
    });

    // Lắng nghe sự kiện submit form xóa từng sản phẩm
    tableContainer.addEventListener("submit", function (event) {
        const deleteForm = event.target.closest(".js-product-delete-form");
        if (deleteForm) {
            confirmDeleteProduct(event, deleteForm);
        }
    });
}

// Khởi tạo các sự kiện cho bảng sản phẩm khi tải xong trang
document.addEventListener("DOMContentLoaded", function () {
    const tableContainer = document.getElementById("table-container");
    if (!tableContainer) return;

    initProductTableEvents(tableContainer);
    updateBulkDeleteButton();
});

const PRODUCTS_SCROLL_STORAGE_KEY = "admin-products-scroll-y";

// Lưu vị trí cuộn trang vào sessionStorage trước khi tải lại
window.addEventListener("beforeunload", function () {
    const container = getScrollContainer();
    const scrollY = window.pendingScrollY !== undefined ? window.pendingScrollY : container?.scrollTop;
    if (scrollY !== undefined) sessionStorage.setItem(PRODUCTS_SCROLL_STORAGE_KEY, String(scrollY));
});

// Khôi phục vị trí cuộn trang đã lưu sau khi tải xong
window.addEventListener("load", function () {
    const savedScrollY = sessionStorage.getItem(PRODUCTS_SCROLL_STORAGE_KEY);
    const container = getScrollContainer();
    if (savedScrollY !== null && container) {
        container.scrollTop = parseInt(savedScrollY, 10);
        sessionStorage.removeItem(PRODUCTS_SCROLL_STORAGE_KEY);
    }
});
</script>
@endpush