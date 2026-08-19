@extends('backend.layouts.app')

@section('title', 'Danh sách Đơn hàng - Admin')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6 orders-page admin-orders-page">

        {{-- Phần tiêu đề trang và nút chức năng xuất báo cáo --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Danh sách Đơn hàng</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi và quản lý các đơn đặt hàng từ hệ thống Happy Tea.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <button type="button" id="bulk-deselect-btn" onclick="bulkDeselectAllRows('.order-checkbox', 'resetOrderSelection')" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-50 text-gray-600 rounded-lg font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200" title="Bỏ chọn tất cả">
                    <i class="fa-solid fa-arrow-rotate-left text-[14px] shrink-0"></i>
                    <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
                </button>

                <button type="button" id="bulk-delete-btn" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100" title="Xóa đã chọn">
                    <i class="fa-solid fa-trash-can text-[14px] shrink-0"></i>
                    <span class="orders-page-bulk-text font-semibold whitespace-nowrap">Xóa <span id="selected-count" class="mx-1">0</span> đơn hàng</span>
                </button>
            </div>
        </div>
        {{-- Khu vực các thẻ thống kê nhanh hiển thị ở đầu trang --}}
        <div id="stats-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 shrink-0">
            @include('backend.admin.orders.partials.stats')
        </div>

        {{-- Form --}}
        <div class="bg-white p-3 sm:p-4 rounded-xl organic-shadow border border-gray-100 flex flex-col gap-4 relative z-20">
            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button"
                    onclick="toggleFilterPanel('search-form')"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <i class="fa-solid fa-filter text-[14px]"></i> <span class="hidden sm:inline">Bộ
                        lọc</span>
                </button>
            </div>

            <form id="search-form" action="{{ route('admin.orders.index') }}" method="GET"
                class="hidden xl:flex flex-col w-full transition-all orders-page-filter-form">
                
                <div class="flex flex-wrap items-center gap-3 w-full">
                    
                    <div class="orders-page-search w-full sm:w-[calc(50%-0.375rem)] lg:w-auto lg:flex-1 flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 text-[14px] shrink-0"></i>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                            placeholder="Mã đơn, Tên, SĐT...">
                    </div>

                    <select name="status" id="status-select" class="orders-page-status w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>

                    <select name="sort" id="sort-select" class="orders-page-sort w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="desc" {{ request('sort', 'desc') == 'desc' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Cũ nhất</option>
                    </select>

                    <div class="orders-page-date w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <i class="fa-solid fa-calendar-days text-gray-400 text-[14px] shrink-0"></i>
                        <input type="text" name="date_from" id="date-from-input" value="{{ request('date_from') }}"
                            class="orders-date-picker bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none text-gray-700" title="Từ ngày" placeholder="Từ ngày">
                    </div>

                    <div class="orders-page-date w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <i class="fa-solid fa-calendar-days text-gray-400 text-[14px] shrink-0"></i>
                        <input type="text" name="date_to" id="date-to-input" value="{{ request('date_to') }}"
                            class="orders-date-picker bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none text-gray-700" title="Đến ngày" placeholder="Đến ngày">
                    </div>

                    <div class="orders-page-actions w-full lg:w-auto shrink-0 lg:ml-auto flex items-center gap-2">
                        <button type="submit" class="flex-1 lg:flex-none px-5 py-1.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors organic-shadow">
                            Lọc
                        </button>
                        <a href="{{ route('admin.orders.index') }}"
                            class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow">
                            <i class="fa-solid fa-filter-circle-xmark text-[16px] shrink-0"></i>
                            <span class="whitespace-nowrap font-medium">Xóa lọc</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Khu vực chứa bảng dữ liệu --}}
        <div
            class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                @include('backend.admin.orders.partials.table')
            </div>
        </div>

    </div>

    <!-- Form -->
    <form id="bulk-delete-form" method="POST" action="{{ route('admin.orders.bulk_delete') }}" class="hidden">
        @csrf
    </form>

    @push('scripts')
        <script>
        let tableContainer;

        // Xóa sạch lựa chọn hiện tại, dùng sau khi lọc lại danh sách
        function resetOrderSelection() {
            if (!window.selectedOrderIds) return;
            window.selectedOrderIds.clear();
            updateBulkDeleteButton();
        }

        // Bật/tắt và cập nhật số đếm trên nút "Xóa đã chọn" theo số dòng đang tích
        function updateBulkDeleteButton() {
            const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
            const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
            const selectedCountSpan = document.getElementById("selected-count");

            if (!bulkDeleteBtn || !selectedCountSpan) return;

            // Cập nhật trạng thái hiển thị và số lượng của nút xóa hàng loạt
            const count = window.selectedOrderIds.size;
            selectedCountSpan.textContent = count;

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

        // Gom danh sách ID đã chọn, hỏi xác nhận và gửi form xóa hàng loạt
        function submitBulkDelete() {
            if (window.selectedOrderIds.size === 0) return;
            window.AdminAlert.confirm(
                `Bạn chuẩn bị xóa ${window.selectedOrderIds.size} đơn hàng đã chọn. Tiếp tục?`,
                function () {
                    const bulkDeleteForm = document.getElementById("bulk-delete-form");
                    bulkDeleteForm.querySelectorAll('input[name="order_ids[]"]').forEach((el) => el.remove());

                    window.selectedOrderIds.forEach((id) => {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "order_ids[]";
                        input.value = id;
                        bulkDeleteForm.appendChild(input);
                    });

                    bulkDeleteForm.submit();
                },
                'Xác nhận xóa hàng loạt'
            );
        }

        // Khởi tạo bộ chọn ngày Flatpickr cho bộ lọc danh sách đơn hàng
        function initSearchAndFilters() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".orders-date-picker", {
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y",
                    allowInput: true,
                    disableMobile: true,
                    locale: "vn",
                    monthSelectorType: "static",
                    appendTo: document.querySelector('.orders-page') || document.body,
                });
            }
        }

        // Gắn sự kiện tương tác cho bảng đơn hàng
        function initTableEvents() {
            tableContainer = document.getElementById("table-container");
            const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

            window.selectedOrderIds = new Set();
            window.submitBulkDelete = submitBulkDelete;

            // Gắn sự kiện click cho nút xóa hàng loạt
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener("click", submitBulkDelete);
            }

            // Xử lý xác nhận xóa đơn hàng lẻ bằng SweetAlert2
            tableContainer.addEventListener("submit", function (e) {
                const deleteForm = e.target.closest(".js-delete-order-form");
                if (deleteForm) {
                    e.preventDefault();
                    window.AdminAlert.confirm(
                        "Đơn hàng này sẽ bị xóa vĩnh viễn khỏi hệ thống. Tiếp tục?",
                        function () {
                            deleteForm.submit();
                        },
                        "Xác nhận xóa"
                    );
                }
            });

            // Lắng nghe các sự kiện thay đổi trên bảng đơn hàng
            tableContainer.addEventListener("change", function (e) {
                // Xử lý khi nhấn chọn tất cả đơn hàng trên trang
                if (e.target.classList.contains("js-select-all")) {
                    const isChecked = e.target.checked;
                    document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = isChecked);

                    window.selectedOrderIds.clear();
                    document.querySelectorAll(".order-checkbox").forEach((cb) => {
                        cb.checked = isChecked;
                        if (isChecked) window.selectedOrderIds.add(cb.value);
                    });

                    updateBulkDeleteButton();
                    return;
                }

                // Xử lý khi chọn hoặc bỏ chọn từng đơn hàng lẻ
                if (e.target.classList.contains("order-checkbox")) {
                    if (e.target.checked) {
                        window.selectedOrderIds.add(e.target.value);
                    } else {
                        window.selectedOrderIds.delete(e.target.value);
                    }

                    const allCheckboxes = document.querySelectorAll(".order-checkbox");
                    const allChecked =
                        document.querySelectorAll(".order-checkbox:checked").length === allCheckboxes.length;
                    document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = allChecked);

                    updateBulkDeleteButton();
                    return;
                }

                // Xử lý khi thay đổi trạng thái đơn hàng từ dropdown
                if (e.target.classList.contains("js-order-status-select")) {
                    const select = e.target;

                    // Nếu không phải trạng thái hủy thì submit trực tiếp
                    if (select.value !== "cancelled") {
                        select.form.submit();
                        return;
                    }

                    // Yêu cầu nhập lý do hủy đơn hàng bằng SweetAlert2
                    window.AdminAlert.prompt(
                        "Hủy đơn hàng",
                        "Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):",
                        "Nhập lý do...",
                        function (reason, isConfirmed) {
                            if (!isConfirmed || !reason || reason.trim().length < 5) {
                                select.value = select.dataset.currentStatus;
                                return;
                            }

                            // Đưa lý do hủy vào input ẩn và gửi form
                            const reasonInput = document.createElement("input");
                            reasonInput.type = "hidden";
                            reasonInput.name = "cancel_reason";
                            reasonInput.value = reason.trim();
                            select.form.appendChild(reasonInput);
                            select.form.submit();
                        },
                        "Lý do hủy đơn phải có ít nhất 5 ký tự.",
                        "Xác nhận hủy",
                        5
                    );
                }
            });

            // Lắng nghe thay đổi DOM trong bảng để cập nhật lại nút xóa
            const observer = new MutationObserver(function () {
                updateBulkDeleteButton();
            });
            observer.observe(tableContainer, { childList: true, subtree: true });
        }

        // Khởi tạo các bộ lọc và sự kiện bảng khi tải xong trang
        document.addEventListener("DOMContentLoaded", function () {
            initSearchAndFilters();
            initTableEvents();
        });
        </script>
    @endpush
@endsection