@extends('backend.layouts.app')

@section('title', 'Quản lý Sản phẩm')

@section('content')
<div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

    <!-- Phần 1: Tiêu đề trang & Nút Thêm mới -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Sản phẩm</h2>
            <p class="text-gray-500 text-sm mt-1">Quản lý danh sách, giá bán, và cấu hình các sản phẩm kinh doanh.</p>
        </div>
        <div class="flex gap-4 w-full sm:w-auto">
            <a href="{{ route('admin.products.create') }}"
                class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all w-full sm:w-auto">
                <span class="material-symbols-outlined">add</span>
                Thêm sản phẩm mới
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

    <!-- Phần 2: Khung Thống kê -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
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
                <span class="material-symbols-outlined text-lg"
                    style="font-variation-settings: 'FILL' 1;">inventory</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div
            class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Đang kinh doanh</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($activeProducts) }}</p>
                <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span> đang bán
                </p>
            </div>
            <div
                class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                <span class="material-symbols-outlined text-lg"
                    style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div
            class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
            <div class="space-y-1 min-w-0">
                <p class="font-semibold text-xs text-gray-500 truncate">Ngừng kinh doanh</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($inactiveProducts) }}</p>
                <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                    Đã vô hiệu hóa
                </p>
            </div>
            <div
                class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 group-hover:bg-gray-100 group-hover:scale-110 transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-lg"
                    style="font-variation-settings: 'FILL' 1;">block</span>
            </div>
        </div>
    </div>


    <!-- Phần 3: Thanh Tìm kiếm và Lọc dữ liệu -->
    <div
        class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6">
        <form action="{{ route('admin.products.index') }}" method="GET" id="filter-form"
            class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full xl:w-auto">
            <div class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-64 relative">
                <span class="material-symbols-outlined text-gray-400">search</span>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-4 w-full outline-none"
                    placeholder="Tìm tên sản phẩm, SKU...">
            </div>

            <select name="category_id" class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                <option value="all">Tất cả danh mục</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <select name="status" class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                <option value="all">Tất cả trạng thái</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang kinh doanh</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Ngừng kinh doanh</option>
            </select>

            <select name="sort" class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto">
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Giá: Thấp đến cao</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Giá: Cao đến thấp</option>
            </select>

            <a href="{{ route('admin.products.index') }}" id="btn-clear-filter"
                class="px-4 py-2 text-gray-500 hover:text-red-500 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto"
                style="display: {{ (request('search') || (request('category_id') && request('category_id') != 'all') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'inline-block' : 'none' }};">
                Xóa lọc
            </a>
        </form>
        <div class="text-sm font-medium text-gray-500 w-full xl:w-auto text-right" id="display-count">
            Hiển thị {{ $products->count() }} / {{ $products->total() }} sản phẩm
        </div>
    </div>

    <!-- Phần 4: Bảng danh sách Sản phẩm -->
    <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100" id="table-container">
        @include('backend.products.partials.table', ['products' => $products])
    </div>

</div>

@endsection

@push('scripts')
<script src="{{ asset('js/backend/products/index.js') }}"></script>
@endpush

