@extends('backend.layouts.app')

@section('title', 'Sản phẩm - Nhân viên pha chế')

@section('content')
    <div class="p-4 sm:p-6 space-y-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Sản phẩm</h2>
            <p class="text-gray-500 text-sm mt-1">Xem sản phẩm đang bán và sản phẩm hết hàng để không nhận nhầm đơn.</p>
        </div>

        <form method="GET" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm sản phẩm..."
                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                <option value="">Tất cả</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang bán</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Hết hàng / ngừng bán</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold">Lọc</button>
        </form>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($products as $product)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden {{ !$product->is_active ? 'opacity-60' : '' }}">
                    <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3">
                        <p class="font-semibold text-sm text-gray-900 truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $product->category->name ?? '' }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-emerald-600 font-bold text-sm">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                            @if($product->is_active)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Đang bán</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700">Hết hàng</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-400">Không tìm thấy sản phẩm nào.</div>
            @endforelse
        </div>

        @if(method_exists($products, 'hasPages') && $products->hasPages())
            <div class="pt-2">{{ $products->links('pagination::tailwind') }}</div>
        @endif
    </div>
@endsection
