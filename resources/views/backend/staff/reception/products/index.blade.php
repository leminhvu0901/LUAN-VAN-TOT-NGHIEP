@extends('backend.layouts.app')

@section('title', 'Sản phẩm - Nhân viên pha chế')

@section('content')
    <div class="p-4 sm:p-6 space-y-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Sản phẩm</h2>
            <p class="text-gray-500 text-sm mt-1">Xem sản phẩm đang bán và sản phẩm hết hàng để không nhận nhầm đơn.</p>
        </div>

        <form id="reception-products-filter-form" method="GET" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm sản phẩm..."
                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                <option value="">Tất cả</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang bán</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Hết hàng / ngừng bán</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold">Lọc</button>
        </form>

        @include('backend.staff.reception.products.partials.grid')
    </div>

    @push('scripts')
        <script src="{{ asset('js/backend/staff/reception/products-filter.js') }}"></script>
    @endpush
@endsection
