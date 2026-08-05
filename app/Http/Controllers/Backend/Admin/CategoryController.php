<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController
{
    /**
     * Cột "slug" là NOT NULL + UNIQUE nhưng form không có ô nhập riêng - tự sinh từ "name" ở đây.
     * $ignoreId dùng khi cập nhật để không tự đụng vào chính bản ghi đang sửa.
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'danh-muc';
        $slug = $base;
        $i = 1;
        while (Category::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    /**
     * HIỂN THỊ DANH SÁCH DANH MỤC
     */
    public function index(Request $request)
    {
        $query = Category::query()->withCount('products');

        // 1. Tìm kiếm theo tên
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // 2. Lọc theo trạng thái (active / inactive / all)
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', 1);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', 0);
            }
        }

        // 3. Sắp xếp
        switch ($request->sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'order_asc':
                $query->orderBy('display_order', 'asc');
                break;
            case 'order_desc':
                $query->orderBy('display_order', 'desc');
                break;
            default:
                $query->orderBy('display_order', 'asc');
                break;
        }

        $categories = $query->paginate(10)->withQueryString();

        // Thống kê tổng quan
        $totalCategories = Category::count();
        $activeCategories = Category::where('is_active', 1)->count();
        $inactiveCategories = Category::where('is_active', 0)->count();

        return  view('backend.admin.categories.index', compact(
            'categories',
            'totalCategories',
            'activeCategories',
            'inactiveCategories'
        ));
    }

    /**
     * FORM THÊM MỚI
     */
    public function create()
    {
        return  view('backend.admin.categories.create');
    }

    /**
     * LƯU DANH MỤC MỚI
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:categories,name',
            'display_order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được vượt quá 20 ký tự.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',
        ]);

        $data = $request->except(['image_url']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        if (empty($data['display_order'])) {
            $data['display_order'] = Category::max('display_order') + 1;
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã tạo danh mục thành công!');
    }

    /**
     * FORM CHỈNH SỬA
     */
    public function edit(Category $category)
    {
        return  view('backend.admin.categories.edit', compact('category'));
    }

    /**
     * CẬP NHẬT DANH MỤC
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:categories,name,' . $category->id,
            'display_order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được vượt quá 20 ký tự.',
            'name.unique' => 'Tên danh mục này đã tồn tại.',
        ]);

        $data = $request->except(['image_url']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        if (empty($data['display_order'])) {
            $data['display_order'] = 0;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã cập nhật danh mục thành công!');
    }

    /**
     * XÓA DANH MỤC
     */
    public function destroy(Category $category)
    {
        // Kiểm tra xem có sản phẩm nào thuộc danh mục này không
        if ($category->products()->count() > 0) {
            return redirect()->back()->with('error', 'Không thể xóa danh mục đang có sản phẩm!');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Đã xóa danh mục thành công!');
    }

    /**
     * XÓA NHIỀU DANH MỤC (chỉ các dòng đang chọn trong trang hiện tại)
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        $categories = Category::whereIn('id', $request->category_ids)->get();
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($categories as $category) {
            if ($category->products()->count() > 0) {
                $failedCount++;
            } else {
                $category->delete();
                $deletedCount++;
            }
        }

        $msg = "Đã xóa {$deletedCount} danh mục được chọn.";
        if ($failedCount > 0) {
            $msg .= " Có {$failedCount} danh mục không thể xóa vì đang chứa sản phẩm.";
        }

        return redirect()->back()->with('success', $msg);
    }
}
