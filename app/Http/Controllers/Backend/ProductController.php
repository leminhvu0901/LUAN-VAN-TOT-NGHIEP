<?php

namespace App\Http\Controllers\Backend;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController
{
    /**
     * HIEN THI DANH SACH SAN PHAM CHINH
     */
    public function index(Request $request)
    {
        // Khởi tạo truy vấn lấy sản phẩm kèm theo thông tin danh mục 
        $query = Product::with('category');

        // 1. Bộ lọc: Tìm kiếm theo Tên sản phẩm hoặc Mã SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // 2. Bộ lọc: Theo Danh mục sản phẩm
        if ($request->filled('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 3. Bộ lọc: Theo Trạng thái kinh doanh (Đang bán / Ngừng bán)
        if ($request->filled('status') && $request->status != 'all') {
            $query->where('is_active', $request->status == 'active' ? 1 : 0);
        }


        // 4. Chức năng: Sắp xếp theo tiêu chí (Mới nhất, Giá tăng dần, Giá giảm dần)
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('base_price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('base_price', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 5. Phân trang kết quả (10 sản phẩm mỗi trang) và giữ lại các tham số lọc hiện tại trên URL
        $products = $query->paginate(10)->withQueryString();

        $categories = Category::where('is_active', 1)->orderBy('display_order')->get();

        // 6. Lấy dữ liệu thống kê tổng quan hiển thị trên thẻ Card (Tổng số, Đang bán, Ngừng bán)
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', 1)->count();
        $inactiveProducts = Product::where('is_active', 0)->count();

        if ($request->ajax()) {
            $html = view('backend.products.partials.table', compact('products'))->render();
            return response()->json([
                'html' => $html,
                'count_text' => 'Hiển thị ' . $products->count() . ' / ' . $products->total() . ' sản phẩm'
            ]);
        }

        return view('backend.products.index', compact(
            'products',
            'categories',
            'totalProducts',
            'activeProducts',
            'inactiveProducts'
        ));
    }

    /**
     * Hiển thị Form để thêm Sản phẩm mới
     */
    public function create()
    {
        // Lấy danh sách các Danh mục đang hoạt động để đổ vào dropdown
        $categories = Category::where('is_active', 1)->orderBy('display_order')->get();
        return view('backend.products.create', compact('categories'));
    }

    /**
     * Xử lý lưu dữ liệu Sản phẩm mới vào Cơ sở dữ liệu
     */
    public function store(Request $request)
    {
        // 1. Kiểm tra tính hợp lệ của dữ liệu đầu vào (Validation)
        $request->validate([
            'name' => 'required|string|max:50',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0|max:50000000',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:500',
            'gallery' => 'nullable|array|max:5',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.max' => 'Tên sản phẩm không được vượt quá 50 ký tự.',
            'base_price.max' => 'Giá bán không được vượt quá 50.000.000 VNĐ.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',
            'gallery.max' => 'Chỉ được tải lên tối đa 5 ảnh phụ trong bộ sưu tập.',
        ]);

        // 2. Lấy toàn bộ dữ liệu từ form ngoại trừ 2 trường chứa file ảnh
        $data = $request->except(['image', 'gallery']);

        // 3. Tự động tạo Slug (đường dẫn thân thiện) từ Tên sản phẩm + Timestamp chống trùng lặp
        $data['slug'] = Str::slug($request->name) . '-' . time();

        // 4. Nếu người dùng không nhập mã SKU, hệ thống tự động sinh ngẫu nhiên mã SKU (VD: SP-A1B2C3)
        if (empty($data['sku'])) {
            $data['sku'] = 'SP-' . strtoupper(Str::random(6));
        }

        // 5. Xử lý Upload Ảnh đại diện chính (nếu có)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/products'), $filename);
            $data['image'] = 'products/' . $filename;
        }

        // 6. Xử lý Trạng thái bật/tắt hiển thị (Checkbox)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        // 7. Lưu bản ghi Sản phẩm vào DB và lấy ra đối tượng vừa tạo
        $product = Product::create($data);

        // 8. Xử lý Upload các Ảnh phụ (Gallery) vào thư mục và lưu thông tin vào DB
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/products/gallery'), $filename);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/gallery/' . $filename,
                ]);
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Hiển thị Form để cập nhật thông tin một Sản phẩm đã có
     */
    public function edit(Product $product)
    {
        // Lấy danh sách danh mục để đổ vào Dropdown Select
        $categories = Category::where('is_active', 1)->orderBy('display_order')->get();
        return view('backend.products.edit', compact('product', 'categories'));
    }

    /**
     * Xử lý cập nhật thông tin Sản phẩm vào Cơ sở dữ liệu
     */
    public function update(Request $request, Product $product)
    {
        // Tính toán số lượng ảnh phụ hiện tại để giới hạn tối đa chỉ được 5 ảnh tổng cộng
        $existingGalleryCount = $product->images()->count();
        $maxAllowed = max(0, 5 - $existingGalleryCount);

        // 1. Kiểm tra tính hợp lệ dữ liệu (Lưu ý: Bỏ qua kiểm tra trùng lặp SKU đối với chính bản thân sản phẩm này)
        $request->validate([
            'name' => 'required|string|max:50',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0|max:50000000',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:500',
            'gallery' => 'nullable|array|max:' . $maxAllowed,
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.max' => 'Tên sản phẩm không được vượt quá 50 ký tự.',
            'base_price.max' => 'Giá bán không được vượt quá 50.000.000 VNĐ.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',
            'gallery.max' => 'Sản phẩm đã có ' . $existingGalleryCount . ' ảnh. Bạn chỉ có thể tải lên tối đa ' . $maxAllowed . ' ảnh nữa (Tổng cộng 5 ảnh).',
        ]);

        // 2. Lấy dữ liệu ngoại trừ 2 trường file ảnh
        $data = $request->except(['image', 'gallery']);

        // 3. Xử lý mã SKU: Giữ nguyên mã cũ nếu có, tự sinh ngẫu nhiên nếu bị xóa rỗng
        if (empty($data['sku'])) {
            $data['sku'] = $product->sku ?: 'SP-' . strtoupper(Str::random(6));
        }

        // 4. Xử lý Thay thế Ảnh đại diện (nếu người dùng upload ảnh mới)
        if ($request->hasFile('image')) {
            // Bước quan trọng: Xóa file ảnh cũ khỏi hệ thống để giải phóng dung lượng ổ cứng
            if ($product->image) {
                $oldImagePath = public_path('images/' . $product->image);
                if (file_exists($oldImagePath) && !str_contains($product->image, 'placeholder')) {
                    @unlink($oldImagePath);
                }
            }

            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/products'), $filename);
            $data['image'] = 'products/' . $filename;
        }

        // 5. Xử lý trạng thái bật tắt
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        // 6. Cập nhật thông tin mới vào Cơ sở dữ liệu
        $product->update($data);

        // 7. Xử lý lưu thêm các Ảnh phụ mới vào Gallery (nếu có)
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/products/gallery'), $filename);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/gallery/' . $filename,
                ]);
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Xóa hoàn toàn Sản phẩm khỏi hệ thống
     */
    public function destroy(Product $product)
    {
        // 1. Xóa vật lý file Ảnh đại diện của sản phẩm khỏi ổ cứng
        if ($product->image) {
            $oldImagePath = public_path('images/' . $product->image);
            if (file_exists($oldImagePath) && !str_contains($product->image, 'placeholder')) {
                @unlink($oldImagePath);
            }
        }

        // 2. Quét qua và Xóa vật lý toàn bộ các file Ảnh phụ (Gallery) của sản phẩm
        foreach ($product->images as $galleryImg) {
            $oldGalleryPath = public_path('images/' . $galleryImg->image_path);
            if (file_exists($oldGalleryPath)) {
                @unlink($oldGalleryPath);
            }
        }

        // 3. Cuối cùng mới Xóa bản ghi Sản phẩm trong Database (Các ảnh con sẽ bị xóa theo tự động)
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Xóa sản phẩm thành công!');
    }

    /**
     * API: Xóa 1 tấm ảnh phụ 
     */
    public function deleteGalleryImage($id)
    {
        // Tìm bản ghi ảnh trong Database
        $image = ProductImage::findOrFail($id);

        // 1. Xóa file vật lý tương ứng trên ổ cứng
        $oldGalleryPath = public_path('images/' . $image->image_path);
        if (file_exists($oldGalleryPath)) {
            @unlink($oldGalleryPath);
        }

        // 2. Xóa bản ghi lưu thông tin ảnh trong Cơ sở dữ liệu
        $image->delete();

        // 3. Phản hồi định dạng JSON cho Javascript phía giao diện (Frontend) xử lý ẩn tấm ảnh
        return response()->json(['success' => true, 'message' => 'Đã xóa ảnh']);
    }
}
