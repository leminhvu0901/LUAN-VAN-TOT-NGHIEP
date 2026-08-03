<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\Topping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HardenedProductController
{
    /**
     * Hàm lấy danh sách và hiển thị tất cả sản phẩm phục vụ trang quản trị.
     * Hỗ trợ tìm kiếm, lọc theo danh mục, trạng thái hoạt động, sắp xếp và phân trang.
     * 
     * Phản hồi trả về:
     * - Nếu là request AJAX: Trả về JSON chứa HTML của bảng mới để cập nhật giao diện 
     *   trong JS tại file [public/js/backend/admin/products/index.js].
     * - Nếu là request thường: Trả về trang giao diện HTML đầy đủ.
     */
    public function index(Request $request)
    {
        $query = Product::query()->with('category');
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($request->filled('category_id') && $request->input('category_id') !== 'all') $query->where('category_id', $request->input('category_id'));
        if ($request->filled('status') && $request->input('status') !== 'all') $query->where('is_active', $request->input('status') === 'active');
        match ($request->input('sort')) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            default => $query->latest(),
        };
        $products = $query->paginate(10)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('display_order')->get();
        $data = [
            'products' => $products, 'categories' => $categories,
            'totalProducts' => Product::count(),
            'activeProducts' => Product::where('is_active', true)->count(),
            'inactiveProducts' => Product::where('is_active', false)->count(),
        ];
        if ($request->ajax()) {
            return response()->json([ // Trả về JSON chứa HTML bảng cho JS nhận diện tại file [public/js/backend/admin/products/index.js]
                'html' =>  view('backend.admin.products.partials.table', ['products' => $products])->render(),
                'total' => $products->total(),
                'count_text' => 'Hiển thị ' . $products->count() . ' / ' . $products->total() . ' sản phẩm',
            ]);
        }
        return  view('backend.admin.products.index', $data);
    }

    /**
     * Hàm hiển thị giao diện form để thêm mới một sản phẩm.
     */
    public function create(Request $request)
    {
        return  view('backend.admin.products.create', [
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
            'toppings' => Topping::orderBy('name')->get(),
            'backUrl' => $this->resolveBackUrl($request),
        ]);
    }

    /**
     * Hàm hiển thị giao diện form chỉnh sửa thông tin cho một sản phẩm.
     * Nạp trước thông tin các kích cỡ (sizes), topping, và bộ sưu tập ảnh (gallery).
     */
    public function edit(Product $product, Request $request)
    {
        $product->load(['sizes', 'toppings', 'images']);
        return  view('backend.admin.products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
            'toppings' => Topping::orderBy('name')->get(),
            'backUrl' => $this->resolveBackUrl($request),
        ]);
    }

    /**
     * Hàm xử lý lưu thông tin sản phẩm mới vào Database.
     * Thực hiện validate dữ liệu form, tạo Slug, tự động sinh mã SKU nếu trống, 
     * chạy DB Transaction lưu thông tin sản phẩm, lưu album ảnh phụ và đồng bộ toppings/sizes.
     */
    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);
        $uploaded = [];
        try {
            $data = $this->productData($validated);
            $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(8));
            $data['sku'] = $data['sku'] ?: $this->generateSku();
            $data['is_active'] = $request->boolean('is_active');
            if ($request->hasFile('image')) $data['image'] = $this->storeImage($request->file('image'), 'products', $uploaded);

            DB::transaction(function () use (&$product, $data, $validated, $request, &$uploaded) {
                $product = Product::create($data);
                $this->storeGallery($product, $request->file('gallery', []), $uploaded);
                $this->syncOptions($product, $validated);
            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($uploaded);
            throw $exception;
        }
        return redirect($this->safeReturnUrl($request))->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Hàm xử lý cập nhật các thông tin chỉnh sửa của sản phẩm vào Database.
     * Thực hiện xác thực validate, chạy DB Transaction ghi đè dữ liệu, 
     * upload ảnh mới, xóa tệp ảnh cũ khỏi máy chủ để tránh rác ổ đĩa.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product);
        $uploaded = [];
        $oldMainImage = $product->image;
        try {
            $data = $this->productData($validated);
            $data['sku'] = $product->sku ?: ($data['sku'] ?: $this->generateSku());
            $data['is_active'] = $request->boolean('is_active');
            if ($request->hasFile('image')) $data['image'] = $this->storeImage($request->file('image'), 'products', $uploaded);

            DB::transaction(function () use ($product, $data, $validated, $request, &$uploaded) {
                $product->update($data);
                $this->storeGallery($product, $request->file('gallery', []), $uploaded);
                $this->syncOptions($product, $validated);
            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($uploaded);
            throw $exception;
        }
        if (isset($data['image']) && $oldMainImage && !str_contains($oldMainImage, 'placeholder')) {
            $this->deleteFiles([$oldMainImage]);
        }
        return redirect($this->safeReturnUrl($request))->with('success', 'Cập nhật sản phẩm thành công!');
    }

    // Trang danh sách lọc/phân trang bằng AJAX + history.pushState (xem products/index.js), nên URL
    // thật sự đang xem (kèm search/category_id/status/sort/page) chỉ tồn tại trên thanh địa chỉ
    // trình duyệt, không phải trong route Laravel — lấy qua Referer lúc vào trang Thêm/Sửa rồi gửi
    // xuôi qua field ẩn 'back_url' để khi lưu xong quay lại ĐÚNG view đã lọc, tránh mất lọc + tránh
    // cảm giác "nảy" giao diện do bất ngờ quay về trang mặc định không lọc.
    private function resolveBackUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            return $referer;
        }
        return route('admin.products.index');
    }

    // Hàm kiểm tra và lấy URL quay lại an toàn (Được dùng để chuyển hướng sau khi Lưu/Cập nhật thành công).
    // Giúp ngăn chặn lỗ hổng bảo mật Open Redirect (Kẻ xấu lợi dụng tham số URL để chuyển hướng người dùng sang trang web độc hại bên ngoài).
    private function safeReturnUrl(Request $request): string
    {
        $backUrl = $request->input('back_url'); // Đọc đường dẫn quay lại được truyền lên từ trường input ẩn của form
        if ($backUrl && str_starts_with($backUrl, url('/'))) { // Kiểm tra xem đường dẫn đó có bắt đầu bằng tên miền chính thức của quán hay không
            return $backUrl; // Chỉ cho phép chuyển hướng nếu đó là URL nội bộ của hệ thống
        }
        return route('admin.products.index'); // Nếu không an toàn hoặc trống, mặc định quay về trang danh sách sản phẩm
    }

    /**
     * Hàm xử lý xóa một sản phẩm cụ thể khỏi hệ thống.
     * Cơ chế an toàn: Nếu sản phẩm đã phát sinh đơn hàng trong lịch sử giao dịch (order_items), 
     * thay vì xóa cứng sẽ chuyển trạng thái sang "Ngừng kinh doanh" (is_active = 0) để bảo toàn dữ liệu báo cáo.
     * 
     * Phản hồi trả về:
     * - Nếu là AJAX: Trả về JSON thành công/thất bại cho JS tiếp nhận tại file [public/js/backend/admin/products/index.js].
     * - Nếu là request thường: Điều hướng back kèm thông báo.
     */
    public function destroy(Product $product, Request $request)
    {
        if (DB::table('order_items')->where('product_id', $product->id)->exists()) {
            $product->update(['is_active' => false]); // Đổi trạng thái sang ngừng kinh doanh nếu sản phẩm đã phát sinh đơn hàng
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sản phẩm đã phát sinh đơn hàng nên được chuyển sang ngừng kinh doanh, không xóa lịch sử.']); // Trả về thông báo lỗi cho JS nhận diện tại file [public/js/backend/admin/products/index.js]
            }
            return back()->withErrors(['delete' => 'Sản phẩm đã phát sinh đơn hàng nên được chuyển sang ngừng kinh doanh, không xóa lịch sử.']);
        }
        $files = $this->deleteProduct($product); // Xóa thông tin sản phẩm và quan hệ liên quan khỏi CSDL, trả về danh sách ảnh
        $this->deleteFiles($files); // Xóa thực tế các file ảnh của sản phẩm trên ổ đĩa máy chủ
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Xóa sản phẩm thành công!']); // Trả về JSON báo thành công cho JS nhận diện tại file [public/js/backend/admin/products/index.js]
        }
        return back()->with('success', 'Xóa sản phẩm thành công!');
    }

    /**
     * Hàm xử lý xóa hàng loạt sản phẩm được tích chọn ở giao diện.
     * Áp dụng nguyên lý an toàn: Các sản phẩm đã có đơn hàng được chuyển về Ngừng kinh doanh. 
     * Các sản phẩm còn lại được thực hiện xóa cứng khỏi DB và xóa tệp ảnh khỏi ổ đĩa.
     * 
     * Phản hồi trả về:
     * - Trả về kết quả JSON thông báo chi tiết cho JS tại file [public/js/backend/admin/products/index.js].
     */
    public function bulkDelete(Request $request)
    {
        $query = Product::query();
        if ($request->input('delete_all_pages') === '1') {
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            }
            if ($request->filled('category_id') && $request->input('category_id') !== 'all') $query->where('category_id', $request->input('category_id'));
            if ($request->filled('status') && $request->input('status') !== 'all') $query->where('is_active', $request->input('status') === 'active');
            $excluded = $request->validate(['excluded_product_ids' => ['sometimes', 'array'], 'excluded_product_ids.*' => ['integer']])['excluded_product_ids'] ?? [];
            if ($excluded) $query->whereNotIn('id', $excluded);
        } else {
            $ids = $request->validate(['product_ids' => ['required', 'array'], 'product_ids.*' => ['integer', 'exists:products,id']])['product_ids'];
            $query->whereIn('id', $ids);
        }

        $products = $query->with('images')->get();
        $blockedIds = DB::table('order_items')->whereIn('product_id', $products->pluck('id'))->distinct()->pluck('product_id');
        Product::whereIn('id', $blockedIds)->update(['is_active' => false]);
        $files = [];
        $deleted = 0;
        DB::transaction(function () use ($products, $blockedIds, &$files, &$deleted) {
            foreach ($products->whereNotIn('id', $blockedIds) as $product) {
                $files = array_merge($files, $this->deleteProduct($product));
                $deleted++;
            }
        });
        $this->deleteFiles($files); // Xóa sạch các file ảnh tương ứng của nhóm sản phẩm bị xóa khỏi máy chủ
        $message = "Đã xóa {$deleted} sản phẩm.";
        if ($blockedIds->isNotEmpty()) $message .= " {$blockedIds->count()} sản phẩm có lịch sử đơn hàng đã được ngừng kinh doanh.";
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]); // Trả về JSON báo thành công cho JS nhận diện tại file [public/js/backend/admin/products/index.js]
        }
        return back()->with('success', $message);
    }

    /**
     * API xóa một ảnh phụ trong bộ sưu tập ảnh (Gallery) của sản phẩm.
     * 
     * Phản hồi trả về:
     * - Trả về kết quả JSON báo thành công cho JS tại form sửa sản phẩm [public/js/backend/admin/products/edit.js]
     *   để JS gỡ ảnh này khỏi giao diện mà không cần tải lại trang.
     */
    public function deleteGalleryImage($id)
    {
        $image = ProductImage::findOrFail($id); // Tìm ảnh phụ theo ID hoặc ném lỗi 404
        $path = $image->image_path;
        $image->delete(); // Xóa bản ghi ảnh phụ khỏi Database
        $this->deleteFiles([$path]); // Xóa file ảnh phụ trên ổ đĩa máy chủ
        return response()->json(['success' => true, 'message' => 'Đã xóa ảnh']); // Trả về JSON cho JS cập nhật UI gỡ thumbnail ảnh phụ
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $galleryLimit = max(0, 5 - ($product?->images()->count() ?? 0));
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:50000000'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'gallery' => ['nullable', 'array', 'max:' . $galleryLimit],
            'gallery.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'topping_ids' => ['nullable', 'array'],
            'topping_ids.*' => ['integer', 'distinct', 'exists:toppings,id'],
            'size_names' => ['nullable', 'array', 'max:10'],
            'size_names.*' => ['nullable', 'string', 'max:50'],
            'size_price_adjustments' => ['nullable', 'array', 'max:10'],
            'size_price_adjustments.*' => ['nullable', 'numeric', 'min:0', 'max:50000000'],
        ], [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được vượt quá 50 ký tự.',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không hợp lệ.',
            'base_price.required' => 'Vui lòng nhập giá bán.',
            'base_price.numeric' => 'Giá bán phải là số.',
            'base_price.min' => 'Giá bán không được nhỏ hơn 0.',
            'base_price.max' => 'Giá bán không được vượt quá 50.000.000đ.',
            'sku.unique' => 'Mã SKU này đã tồn tại.',
            'sku.max' => 'Mã SKU không được vượt quá 50 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.mimes' => 'Ảnh phải có định dạng jpeg, png, jpg, gif hoặc webp.',
            'image.max' => 'Ảnh không được vượt quá 2MB.',
            'gallery.max' => 'Chỉ được tải tối đa ' . $galleryLimit . ' ảnh phụ nữa.',
            'gallery.*.image' => 'File tải lên phải là hình ảnh.',
            'gallery.*.mimes' => 'Ảnh phụ phải có định dạng jpeg, png, jpg, gif hoặc webp.',
            'gallery.*.max' => 'Mỗi ảnh phụ không được vượt quá 2MB.',
            'topping_ids.*.exists' => 'Topping không hợp lệ.',
            'topping_ids.*.distinct' => 'Topping bị chọn trùng lặp.',
            'size_names.max' => 'Chỉ được tối đa 10 kích cỡ.',
            'size_names.*.max' => 'Tên kích cỡ không được vượt quá 50 ký tự.',
            'size_price_adjustments.*.numeric' => 'Giá cộng thêm phải là số.',
            'size_price_adjustments.*.min' => 'Giá cộng thêm không được nhỏ hơn 0.',
            'size_price_adjustments.*.max' => 'Giá cộng thêm không được vượt quá 50.000.000đ.',
        ]);
    }

    private function productData(array $validated): array
    {
        return collect($validated)->only(['name', 'category_id', 'base_price', 'sku', 'description'])->toArray();
    }

    private function syncOptions(Product $product, array $validated): void
    {
        $product->toppings()->sync($validated['topping_ids'] ?? []);
        $product->sizes()->delete();
        $seen = [];
        foreach (($validated['size_names'] ?? []) as $index => $name) {
            $name = trim((string) $name);
            if ($name === '' || in_array(mb_strtolower($name), $seen, true)) continue;
            $seen[] = mb_strtolower($name);
            ProductSize::create(['product_id' => $product->id, 'size_name' => $name,
                'price_adjustment' => (float) ($validated['size_price_adjustments'][$index] ?? 0)]);
        }
    }

    private function generateSku(): string
    {
        do $sku = 'SP-' . strtoupper(Str::random(8)); while (Product::where('sku', $sku)->exists());
        return $sku;
    }

    private function storeImage($file, string $directory, array &$uploaded): string
    {
        $filename = (string) Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move(public_path('images/' . $directory), $filename);
        $uploaded[] = $directory . '/' . $filename;
        return $directory . '/' . $filename;
    }

    private function storeGallery(Product $product, array $files, array &$uploaded): void
    {
        foreach ($files as $file) {
            ProductImage::create(['product_id' => $product->id, 'image_path' => $this->storeImage($file, 'products/gallery', $uploaded)]);
        }
    }

    private function deleteProduct(Product $product): array
    {
        $files = $product->images->pluck('image_path')->all();
        if ($product->image && !str_contains($product->image, 'placeholder')) $files[] = $product->image;
        DB::table('favorites')->where('product_id', $product->id)->delete();
        DB::table('reviews')->where('product_id', $product->id)->delete();
        DB::table('cart_items')->where('product_id', $product->id)->delete();
        $product->delete();
        return $files;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            $full = upload_path($path);
            if ($full && is_file($full)) @unlink($full);
        }
    }
}
