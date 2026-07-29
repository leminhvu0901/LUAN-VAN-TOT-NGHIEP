<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Category;
use App\Models\Material;
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
            return response()->json([
                'html' =>  view('backend.admin.products.partials.table', ['products' => $products])->render(),
                'total' => $products->total(),
                'count_text' => 'Hiển thị ' . $products->count() . ' / ' . $products->total() . ' sản phẩm',
            ]);
        }
        return  view('backend.admin.products.index', $data);
    }

    public function create(Request $request)
    {
        return  view('backend.admin.products.create', [
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
            'materials' => Material::where('is_active', true)->orderBy('name')->get(),
            'toppings' => Topping::orderBy('name')->get(),
            'backUrl' => $this->resolveBackUrl($request),
        ]);
    }

    public function edit(Product $product, Request $request)
    {
        $product->load(['materials', 'sizes', 'toppings', 'images']);
        return  view('backend.admin.products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
            'materials' => Material::where('is_active', true)->orderBy('name')->get(),
            'toppings' => Topping::orderBy('name')->get(),
            'backUrl' => $this->resolveBackUrl($request),
        ]);
    }

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
            $this->deleteFiles(['images/' . $oldMainImage]);
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

    private function safeReturnUrl(Request $request): string
    {
        $backUrl = $request->input('back_url');
        if ($backUrl && str_starts_with($backUrl, url('/'))) {
            return $backUrl;
        }
        return route('admin.products.index');
    }

    public function destroy(Product $product, Request $request)
    {
        if (DB::table('order_items')->where('product_id', $product->id)->exists()) {
            $product->update(['is_active' => false]);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sản phẩm đã phát sinh đơn hàng nên được chuyển sang ngừng kinh doanh, không xóa lịch sử.']);
            }
            return back()->withErrors(['delete' => 'Sản phẩm đã phát sinh đơn hàng nên được chuyển sang ngừng kinh doanh, không xóa lịch sử.']);
        }
        $files = $this->deleteProduct($product);
        $this->deleteFiles($files);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
        }
        return back()->with('success', 'Xóa sản phẩm thành công!');
    }

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
        $this->deleteFiles($files);
        $message = "Đã xóa {$deleted} sản phẩm.";
        if ($blockedIds->isNotEmpty()) $message .= " {$blockedIds->count()} sản phẩm có lịch sử đơn hàng đã được ngừng kinh doanh.";
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    public function deleteGalleryImage($id)
    {
        $image = ProductImage::findOrFail($id);
        $path = 'images/' . $image->image_path;
        $image->delete();
        $this->deleteFiles([$path]);
        return response()->json(['success' => true, 'message' => 'Đã xóa ảnh']);
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
            'materials' => ['nullable', 'array'],
            'materials.*' => ['nullable', 'numeric', 'min:0.001', 'max:99999999'],
            'topping_ids' => ['nullable', 'array'],
            'topping_ids.*' => ['integer', 'distinct', 'exists:toppings,id'],
            'size_names' => ['nullable', 'array', 'max:10'],
            'size_names.*' => ['nullable', 'string', 'max:50'],
            'size_price_adjustments' => ['nullable', 'array', 'max:10'],
            'size_price_adjustments.*' => ['nullable', 'numeric', 'min:0', 'max:50000000'],
        ]);
    }

    private function productData(array $validated): array
    {
        return collect($validated)->only(['name', 'category_id', 'base_price', 'sku', 'description'])->toArray();
    }

    private function syncOptions(Product $product, array $validated): void
    {
        $materials = collect($validated['materials'] ?? [])->filter(fn ($quantity) => (float) $quantity > 0)
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => ['quantity_used' => (float) $quantity]])->all();
        $product->materials()->sync($materials);
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
        // Ghi vào public/uploads/ (gắn Railway Volume bền vững) thay vì public/images/ (chỉ có nội
        // dung commit sẵn trong code) - xem app/helpers.php::upload_url() để biết lý do đầy đủ.
        $filename = (string) Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move(public_path('uploads/' . $directory), $filename);
        $uploaded[] = 'uploads/' . $directory . '/' . $filename;
        return 'uploads/' . $directory . '/' . $filename;
    }

    private function storeGallery(Product $product, array $files, array &$uploaded): void
    {
        foreach ($files as $file) {
            ProductImage::create(['product_id' => $product->id, 'image_path' => $this->storeImage($file, 'products/gallery', $uploaded)]);
        }
    }

    private function deleteProduct(Product $product): array
    {
        $files = $product->images->pluck('image_path')->map(fn ($path) => 'images/' . $path)->all();
        if ($product->image && !str_contains($product->image, 'placeholder')) $files[] = 'images/' . $product->image;
        DB::table('favorites')->where('product_id', $product->id)->delete();
        DB::table('reviews')->where('product_id', $product->id)->delete();
        DB::table('cart_items')->where('product_id', $product->id)->delete();
        $product->delete();
        return $files;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) if ($path && is_file(public_path($path))) @unlink(public_path($path));
    }
}
