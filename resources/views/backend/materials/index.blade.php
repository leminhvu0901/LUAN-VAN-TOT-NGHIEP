@extends('backend.layouts.app')

@section('title', 'Quản lý Kho Vật Tư')

@section('content')

    <div id="materials-index-page" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        <!-- Phần 1: Tiêu đề trang & Nút Thêm mới -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Kho Vật Tư</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi, cập nhật và quản lý tồn kho nguyên liệu chi tiết.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end">
                <button type="button" id="bulk-deselect-btn" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 bg-gray-100 text-gray-600 rounded-xl font-semibold text-sm hover:bg-gray-200 transition-all shadow-sm border border-gray-200" title="Bỏ chọn tất cả">
                    <span class="material-symbols-outlined text-[18px] sm:text-[20px] shrink-0">deselect</span>
                    <span class="font-semibold whitespace-nowrap">Bỏ chọn</span>
                </button>
                <button type="button" id="bulk-delete-btn" class="hidden flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-xl font-semibold text-sm hover:bg-red-100 transition-all shadow-sm border border-red-100" title="Xóa đã chọn">
                    <span class="material-symbols-outlined text-[20px] shrink-0">delete_sweep</span>
                    <span class="font-semibold whitespace-nowrap">Xóa <span id="selected-count" class="mx-1 text-red-700 font-bold">0</span> vật tư</span>
                </button>
                <button type="button" data-open-modal="modal-add"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all w-full sm:w-auto mt-1 sm:mt-0">
                    <span class="material-symbols-outlined shrink-0">add</span>
                    <span class="whitespace-nowrap">Thêm vật tư mới</span>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any() && !old('_form_context'))
            <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Phần 2: Khung Thống kê 7 thẻ -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Card 1: Tổng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng mặt hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalItems) }}</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">category</span> đang quản lý
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
                </div>
            </div>

            <!-- Card 2: Sắp hết hàng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-orange-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($lowStockItems) }}</p>
                    <p class="text-orange-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">warning</span> sắp hết
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">production_quantity_limits</span>
                </div>
            </div>

            <!-- Card 2.5: Đã hết hàng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($outOfStockItems ?? 0) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">error</span> cần nhập gấp
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">remove_shopping_cart</span>
                </div>
            </div>

            <!-- Card 3: Sắp hết hạn -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-amber-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiringItems ?? 0) }}</p>
                    <p class="text-amber-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Trong 30 ngày
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">event_upcoming</span>
                </div>
            </div>

            <!-- Card 4: Đã hết hạn -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiredItems ?? 0) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Cần tiêu huỷ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">event_busy</span>
                </div>
            </div>

            <!-- Card 5: Đã thu hồi -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-gray-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã thu hồi</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($disposedBatchesCount ?? 0) }}</p>
                    <p class="text-gray-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Lô hàng xuất huỷ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">remove_shopping_cart</span>
                </div>
            </div>

            <!-- Card 7: Tổng giá trị kho -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng giá trị</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ number_format(($totalValue ?? 0) / 1000000, 1) }}M</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        VNĐ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">payments</span>
                </div>
            </div>

            <!-- Card 8: Giá trị thu hồi -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Giá trị đã hủy</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ number_format(($disposedValue ?? 0) / 1000000, 1) }}M</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        VNĐ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">money_off</span>
                </div>
            </div>
        </div>

        <!-- Phần 3: Thanh Tìm kiếm và Lọc dữ liệu -->
        <div class="bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6 flex flex-col gap-4">
            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button" onclick="document.getElementById('filter-wrapper').classList.toggle('hidden'); document.getElementById('filter-wrapper').classList.toggle('flex');" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> <span class="hidden sm:inline">Bộ lọc</span>
                </button>
            </div>

            <div id="filter-wrapper" class="hidden xl:flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between w-full transition-all">
                <form action="{{ route('admin.materials.index') }}" method="GET" id="filter-form"
                    class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full xl:w-auto flex-1">
                    
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 items-stretch sm:items-center flex-1 w-full xl:w-auto">
                        <div class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:flex-1 xl:max-w-[280px] relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                            <span class="material-symbols-outlined text-gray-400 text-[20px] shrink-0">search</span>
                            <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                                class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none text-gray-700"
                                placeholder="Tìm kiếm vật tư...">
                        </div>

                    <select name="status" id="status-select" data-width-class="w-full sm:w-[180px]" class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Sắp hết hàng</option>
                        <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                        <option value="expiring" {{ request('status') == 'expiring' ? 'selected' : '' }}>Sắp hết hạn</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
                        <option value="disposed" {{ request('status') == 'disposed' ? 'selected' : '' }}>Đã thu hồi</option>
                    </select>

                    <select name="sort" id="sort-select" data-width-class="w-full sm:w-[210px]" class="custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="stock_asc" {{ request('sort') == 'stock_asc' ? 'selected' : '' }}>Tồn kho: Thấp đến cao</option>
                        <option value="stock_desc" {{ request('sort') == 'stock_desc' ? 'selected' : '' }}>Tồn kho: Cao đến thấp</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full xl:w-auto shrink-0">
                    <a href="{{ route('admin.materials.index') }}" id="btn-clear-filter"
                        class="flex-1 xl:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow text-center w-full sm:w-auto"
                        style="display: {{ (request('search') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'flex' : 'none' }};">
                        <span class="material-symbols-outlined text-[20px] shrink-0">filter_alt_off</span>
                        <span class="whitespace-nowrap font-medium">Xóa lọc</span>
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100 flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
            <div id="table-container-wrapper" class="flex-1 flex flex-col min-h-0 relative w-full">
                {{-- Biểu tượng Loading --}}
                <div id="table-loader" class="absolute inset-0 bg-white/50 z-20 hidden items-center justify-center transition-all duration-300">
                </div>
                <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                    @include('backend.materials.partials.table', ['materials' => $materials])
                </div>
            </div>
        </div>
    </div>








    <!-- Phan 5: Modal Them Vat tu moi -->
    <div id="modal-add" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center {{ $errors->any() && old('_form_context') === 'material-add' ? '' : 'hidden' }} z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden mat-modal-panel">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Thêm Vật Tư Mới</h3>
                <button type="button" data-close-modal="modal-add" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('admin.materials.store') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="_form_context" value="material-add">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên vật tư</label>
                        <input type="text" id="add-material-name" name="name" required minlength="2" value="{{ old('name') }}"
                            data-max-length="50" data-field-label="Tên vật tư" aria-describedby="add-material-name-error"
                            class="w-full px-4 py-2 border {{ $errors->has('name') && old('_form_context') === 'material-add' ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500' }} rounded-xl outline-none transition-all">
                        <p id="add-material-name-error" data-error-for="add-material-name"
                            class="{{ $errors->has('name') && old('_form_context') === 'material-add' ? '' : 'hidden' }} mt-1 text-xs font-medium text-red-600" aria-live="polite">{{ $errors->has('name') && old('_form_context') === 'material-add' ? $errors->first('name') : '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Đơn vị (Kg, Bao, Lốc, Cuộn,
                            Cái...)</label>
                        <input type="text" id="add-material-unit" name="unit" required value="{{ old('unit') }}"
                            data-material-unit data-max-length="20" data-field-label="Đơn vị" inputmode="text"
                            aria-describedby="add-material-unit-error"
                            class="w-full px-4 py-2 border {{ $errors->has('unit') && old('_form_context') === 'material-add' ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500' }} rounded-xl outline-none transition-all">
                        <p id="add-material-unit-error" data-error-for="add-material-unit"
                            class="{{ $errors->has('unit') && old('_form_context') === 'material-add' ? '' : 'hidden' }} mt-1 text-xs font-medium text-red-600" aria-live="polite">{{ $errors->has('unit') && old('_form_context') === 'material-add' ? $errors->first('unit') : '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá vốn dự kiến (VNĐ / Đơn vị)</label>
                        <input type="text" id="add-formatted-price" required inputmode="numeric"
                            data-max-value="999999999" data-max-message="Giá vốn dự kiến phải nhỏ hơn 1 tỷ đồng."
                            data-number-message="Giá vốn dự kiến chỉ được nhập số."
                            aria-describedby="add-formatted-price-error"
                            value="{{ old('unit_price') !== null ? number_format((float) old('unit_price'), 0, ',', '.') : '' }}"
                            class="w-full px-4 py-2 border {{ $errors->has('unit_price') && old('_form_context') === 'material-add' ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500' }} rounded-xl outline-none transition-all" placeholder="Ví dụ: 100,000">
                        <p id="add-formatted-price-error" data-error-for="add-formatted-price"
                            class="{{ $errors->has('unit_price') && old('_form_context') === 'material-add' ? '' : 'hidden' }} mt-1 text-xs font-medium text-red-600" aria-live="polite">{{ $errors->has('unit_price') && old('_form_context') === 'material-add' ? $errors->first('unit_price') : '' }}</p>
                        <input type="hidden" id="add-raw-price" name="unit_price" value="{{ old('unit_price') }}">
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" data-close-modal="modal-add"
                        class="w-full sm:w-auto text-center px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 sm:border-transparent">Hủy</button>
                    <button type="submit"
                        class="w-full sm:w-auto text-center px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Lưu vật tư</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form ẩn để xóa nhiều -->
    <form id="bulk-delete-form" method="POST" action="{{ route('admin.materials.bulk_delete') }}" class="hidden">
        @csrf
    </form>

@endsection

@push('scripts')
    <script src="{{ asset('js/backend/materials/common.js') }}"></script>
    <script src="{{ asset('js/backend/materials/index.js') }}"></script>
@endpush
