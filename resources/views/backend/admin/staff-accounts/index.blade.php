@extends('backend.layouts.app')

@section('title', 'Quản lý Nhân viên - Admin')

@section('content')
    <div class="staffs-page space-y-6 sm:space-y-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Quản lý Nhân viên</h1>
                <p class="text-gray-500 text-sm mt-1">Quản lý danh sách tài khoản nhân viên, cấp quyền và trạng thái hoạt động.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <a href="{{ route('admin.staff_accounts.create') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all font-medium text-sm shadow-sm hover:shadow-md whitespace-nowrap">
                    <span class="material-symbols-outlined text-[20px] shrink-0">add</span>
                    <span class="font-semibold whitespace-nowrap">Thêm Nhân viên</span>
                </a>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <!-- Card 1: Tổng nhân viên -->
            <div class="stat-card group bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-2xl p-5 border border-indigo-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-indigo-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">badge</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-indigo-100 flex items-center justify-center text-indigo-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">badge</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Tổng nhân viên</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($totalStaff) }}</p>
                </div>
            </div>

            <!-- Card 2: Hoạt động -->
            <div class="stat-card group bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-2xl p-5 border border-emerald-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-emerald-500/5 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 select-none">check_circle</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-emerald-100 flex items-center justify-center text-emerald-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">check_circle</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Hoạt động</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($activeStaff) }}</p>
                </div>
            </div>

            <!-- Card 3: Bị khóa -->
            <div class="stat-card group bg-gradient-to-br from-rose-50 to-rose-100/50 rounded-2xl p-5 border border-rose-100/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                <span class="material-symbols-outlined text-8xl absolute -bottom-4 -right-4 text-rose-500/5 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 select-none">block</span>
                <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-rose-100 flex items-center justify-center text-rose-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-300 z-10">
                    <span class="material-symbols-outlined text-2xl icon-fill">block</span>
                </div>
                <div class="space-y-1 min-w-0 z-10">
                    <p class="font-semibold text-xs text-gray-500 truncate uppercase tracking-wide">Đang khóa</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 truncate tracking-tight">{{ number_format($inactiveStaff) }}</p>
                </div>
            </div>
        </div>

        <!-- Thanh Tìm kiếm và Lọc dữ liệu -->
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 flex flex-col gap-4">
            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button" onclick="toggleFilterPanel('filter-wrapper')" class="w-10 h-10 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center transition-colors shrink-0 border border-gray-100" title="Mở bộ lọc">
                    <span class="material-symbols-outlined text-[20px]">filter_list</span>
                </button>
            </div>

            <div id="filter-wrapper" class="hidden xl:flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between w-full transition-all">
                <form action="{{ route('admin.staff_accounts.index') }}" method="GET" id="filter-form"
                    class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full">
    
                    <div class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-72 relative focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all">
                        <span class="material-symbols-outlined text-gray-400">search</span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none"
                            placeholder="Tìm tên, email, SĐT...">
                    </div>
    
                    <div class="grid grid-cols-2 sm:flex gap-3 w-full sm:w-auto">
                        <select name="status" id="status-select" class="custom-select-init px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                            <option value="all">Trạng thái</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Bị khóa</option>
                        </select>

                        <select name="staff_type" id="staff-type-filter-select" class="custom-select-init px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                            <option value="all">Loại nhân viên</option>
                            <option value="receptionist" {{ request('staff_type') == 'receptionist' ? 'selected' : '' }}>Nhân viên pha chế</option>
                            <option value="delivery" {{ request('staff_type') == 'delivery' ? 'selected' : '' }}>Nhân viên giao hàng</option>
                        </select>

                        <select name="sort" id="sort-select" class="custom-select-init px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                            <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto">
                        Lọc
                    </button>

                    <a href="{{ route('admin.staff_accounts.index') }}" id="btn-clear-filter"
                        class="px-4 py-2.5 text-gray-500 hover:text-red-500 hover:bg-red-50 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                        style="display: {{ (request('search') || (request('status') && request('status') != 'all') || (request('staff_type') && request('staff_type') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'inline-block' : 'none' }};">
                        Xóa lọc
                    </a>
                </form>
            </div>
        </div>

        <!-- Bảng danh sách Nhân viên -->
        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
            @include('backend.admin.staff-accounts.partials.table', ['staffs' => $staffs])
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Xử lý thay đổi loại vai trò nhân viên (pha chế hoặc giao hàng)
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('staff-type-select')) {
                    const select = e.target;
                    const oldValue = select.dataset.current;
                    const newValue = select.value;
                    if (newValue === oldValue) return;

                    const label = newValue === 'receptionist' ? 'Nhân viên pha chế' : 'Nhân viên giao hàng';
                    if (confirm(`Đổi loại nhân viên thành "${label}"? Quyền truy cập của tài khoản này sẽ thay đổi ngay lập tức.`)) {
                        select.closest('form').submit();
                    } else {
                        select.value = oldValue;
                    }
                }
            });

            // Xử lý thay đổi trạng thái hoạt động hoặc khóa tài khoản kèm lý do
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('toggle-status')) {
                    const checkbox = e.target;
                    const form = checkbox.closest('form');
                    const willBeActive = checkbox.checked;

                    if (!willBeActive) {
                        const reason = prompt('Vui lòng nhập lý do khóa tài khoản nhân viên này:');
                        if (reason === null || reason.trim() === '') {
                            checkbox.checked = true;
                            return;
                        }
                        form.querySelector('input[name="lock_reason"]').value = reason.trim();
                    }

                    form.querySelector('input[name="is_active"]').value = willBeActive ? '1' : '0';
                    form.submit();
                }
            });
        });
        </script>
    @endpush

@endsection
