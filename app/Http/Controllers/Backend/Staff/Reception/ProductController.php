<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Product;
use Illuminate\Http\Request;

// Lễ tân chỉ xem sản phẩm (đọc-only) — không có store/update/destroy.
// Hiển thị cả hàng "hết hàng" (is_active=false) để không nhận nhầm đơn cho món hiện không bán.
class ProductController
{
    public function index(Request $request)
    {
        $query = Product::with('category')->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $products = $query->paginate(20)->withQueryString();

        // Lọc/chuyển trang gửi lên qua fetch (xem products-filter.js) -> chỉ trả về đúng phần lưới +
        // phân trang, không render lại toàn bộ trang, tránh tải lại cả trang khi đổi từ khóa/trạng thái.
        if ($request->expectsJson()) {
            return view('backend.staff.reception.products.partials.grid', compact('products'))->render();
        }

        return view('backend.staff.reception.products.index', compact('products'));
    }
}
