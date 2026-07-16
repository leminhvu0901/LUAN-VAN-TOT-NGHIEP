<?php
// Import class Route của Laravel để khai báo các đường dẫn
use Illuminate\Support\Facades\Route;

use App\Models\Banner;


Route::get('/', function () {
    // Lấy tất cả banner đang kích hoạt và trong thời gian áp dụng, sắp xếp theo thứ tự hiển thị
    $now = now();
    $banners = \App\Models\Banner::where('is_active', 1)
        ->where(function ($q) use ($now) {
            $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
        })
        ->orderBy('display_order', 'asc')
        ->get();

    // Truy vấn lấy danh mục sản phẩm (categories) kèm theo số lượng sản phẩm của từng danh mục
    $categories = \App\Models\Category::query()
        ->leftJoin('products', function ($join) { // Kết nối bảng products
            $join->on('categories.id', '=', 'products.category_id'); // Điều kiện: id danh mục = category_id của sản phẩm
        })
        ->select('categories.id', 'categories.name', \Illuminate\Support\Facades\DB::raw('COUNT(products.id) as product_count')) // Đếm tổng số sản phẩm
        ->where('categories.is_active', 1) // Chỉ lấy các danh mục đang mở
        ->groupBy('categories.id', 'categories.name', 'categories.display_order') // Gom nhóm để đếm chính xác
        ->orderBy('categories.display_order') // Sắp xếp thứ tự hiển thị
        ->get(); // Thực thi truy vấn và lấy kết quả

    return view('frontend.home', compact('banners', 'categories'));
});

// DS  SP
Route::get('/products', [App\Http\Controllers\Frontend\ProductController::class, 'index'])->name('products');

// CHI TIET SAN PHAM
Route::get('/products/{slug}', [App\Http\Controllers\Frontend\ProductController::class, 'show'])->name('product.show');



// TÀI KHOẢN & XÁC THỰC 
// ĐĂNG KÝ
Route::get('/register', function () {
    return redirect('/')->with('show_register', true);
})->name('register');

// FORM ĐĂNG KÝ
Route::post('/register', [App\Http\Controllers\Frontend\AuthController::class, 'postRegister'])->name('register.post');

// Đưa về trang chủ hiện model đăng nhập
Route::get('/login', function () {
    return redirect('/')->with('show_login', true);
})->name('login');


// FORM ĐĂNG NHẬP FORM
Route::post('/login', [App\Http\Controllers\Frontend\AuthController::class, 'postLogin'])->name('login.post');

// GIAO DIEN NHAP MA OTP
Route::get('/verify-otp', [App\Http\Controllers\Frontend\AuthController::class, 'getVerifyOtp'])->name('verify.otp');

// GIAO DIEN NHAP MA OTP + XAC MINH
Route::post('/verify-otp', [App\Http\Controllers\Frontend\AuthController::class, 'postVerifyOtp'])->name('verify.otp.post');

// GUI LAI MA OTP
Route::get('/resend-otp', [App\Http\Controllers\Frontend\AuthController::class, 'resendOtp'])->name('resend.otp');

// HUY XOA CS OTP KHI HUY
Route::post('/cancel-otp', [App\Http\Controllers\Frontend\AuthController::class, 'cancelOtp'])->name('verify.otp.cancel');

//  DANG NHAP BANG GG
Route::get('/auth/google', [App\Http\Controllers\Frontend\AuthController::class, 'redirectToGoogle'])->name('auth.google');

// TRA VE KEM THONG TIN
Route::get('/auth/google/callback', [App\Http\Controllers\Frontend\AuthController::class, 'handleGoogleCallback']);

// DANG XUAT
Route::get('/logout', [App\Http\Controllers\Frontend\AuthController::class, 'logout'])->name('logout');

// QUEN MAT KHAU GET
Route::get('/forgot-password', function () {
    return redirect('/')->with('show_forgot', true);
})->name('forgot-password');

// QUEN MAT KHAU POST
Route::post('/forgot-password', [App\Http\Controllers\Frontend\AuthController::class, 'postForgotPassword'])->name('forgot-password.post');

// THAY KHAU MOI GET
Route::get('/reset-password', [App\Http\Controllers\Frontend\AuthController::class, 'getResetPassword'])->name('reset.password.get');
// THAY KHAU MOI POST
Route::post('/reset-password', [App\Http\Controllers\Frontend\AuthController::class, 'postResetPassword'])->name('reset.password.post');

// CHUC NANG USER
Route::middleware(['auth'])->group(function () {

    // --- Hồ sơ cá nhân ---
    // Hiển thị trang Hồ sơ
    Route::get('/profile', [App\Http\Controllers\Frontend\ProfileController::class, 'index'])->name('profile');

    // Cập nhật thông tin Hồ sơ (Tên, SĐT, Avatar...)
    Route::post('/profile', [App\Http\Controllers\Frontend\ProfileController::class, 'update'])->name('profile.update');

    // Đổi mật khẩu tài khoản
    Route::post('/profile/change-password', [App\Http\Controllers\Frontend\ProfileController::class, 'changePassword'])->name('profile.change-password');

    // --- Lịch sử mua hàng & Đánh giá ---
    // Xem danh sách đơn hàng đã đặt
    Route::get('/orders', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'index'])->name('orders');
    Route::post('/orders/{order}/reorder', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'reorder'])->name('orders.reorder');

    // Hiện giao diện đánh giá (Review) 1 sản phẩm nằm trong 1 đơn hàng cụ thể
    Route::get('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\Frontend\ReviewController::class, 'create'])->name('review.create');

    // Gửi đánh giá (số sao, nhận xét) vào Database
    Route::post('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\Frontend\ReviewController::class, 'store'])->name('review.store');

    // Bật/tắt trạng thái Yêu thích sản phẩm (Thả tim)
    Route::post('/favorite/toggle', [App\Http\Controllers\Frontend\ProfileController::class, 'toggleFavorite'])->name('favorite.toggle');

    // --- Giỏ hàng (Các thao tác sửa đổi giỏ hàng cần đăng nhập) ---
    // Thêm 1 sản phẩm mới vào giỏ
    Route::post('/cart/add', [App\Http\Controllers\Frontend\CartController::class, 'add']);

    // Bấm nút xóa sản phẩm khỏi giỏ
    Route::post('/cart/remove', [App\Http\Controllers\Frontend\CartController::class, 'remove']);

    // Bấm nút (+), (-) để cập nhật số lượng
    Route::post('/cart/update', [App\Http\Controllers\Frontend\CartController::class, 'update']);

    // Bấm "Thêm tất cả vào giỏ" ở bên ngăn kéo Yêu Thích
    Route::post('/cart/add-all', [App\Http\Controllers\Frontend\CartController::class, 'addAll']);

    // --- Quản lý Danh bạ Địa chỉ nhận hàng ---
    // Lưu địa chỉ mới
    Route::post('/profile/address', [App\Http\Controllers\Frontend\ProfileController::class, 'storeAddress'])->name('profile.address.store');

    // Chỉnh sửa địa chỉ đã có (dựa theo id)
    Route::post('/profile/address/{id}', [App\Http\Controllers\Frontend\ProfileController::class, 'updateAddress'])->name('profile.address.update');
    // Xóa địa chỉ (dựa theo id)

    Route::post('/profile/address/{id}/delete', [App\Http\Controllers\Frontend\ProfileController::class, 'deleteAddress'])->name('profile.address.delete');
    // Đánh dấu 1 địa chỉ làm địa chỉ mặc định
    Route::post('/profile/address/{id}/default', [App\Http\Controllers\Frontend\ProfileController::class, 'setDefaultAddress'])->name('profile.address.default');

    // --- Thanh toán đơn hàng (Checkout) ---
    // Mở trang Thanh toán

    Route::get('/checkout', [App\Http\Controllers\Frontend\CartController::class, 'checkout'])->name('checkout');
    // Nút "Đặt hàng" (Phương thức COD), lưu đơn hàng vào Database
    Route::post('/checkout', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'store'])->name('checkout.store');

    // Các đường dẫn phụ trợ (API) gọi ngầm qua AJAX:
    // Tính khoảng cách km giao hàng
    Route::get('/checkout/distance', [App\Http\Controllers\Frontend\CartController::class, 'calculateDistance']);

    // Tính phụ phí thời tiết
    Route::get('/checkout/weather-fee', [App\Http\Controllers\Frontend\CartController::class, 'calculateWeatherFee']);

    // Tính phụ phí thời tiết theo tọa độ GPS
    Route::get('/checkout/weather-fee-by-coords', [App\Http\Controllers\Frontend\CartController::class, 'calculateWeatherFeeByCoords']);
    // Kiểm tra Mã giảm giá (Coupon) có hợp lệ không
    Route::post('/checkout/validate-coupon', [App\Http\Controllers\Frontend\CartController::class, 'validateCoupon']);

    // --- Thanh toán qua ví điện tử MoMo ---
    // Gửi yêu cầu trừ tiền sang máy chủ MoMo
    Route::post('/checkout/momo', [App\Http\Controllers\Frontend\MomoController::class, 'createPayment'])->name('momo.pay');

    // Sau khi quét mã MoMo xong, khách bị đá ngược về route này để xem màn hình "Thanh toán thành công"
    Route::get('/checkout/momo/return', [App\Http\Controllers\Frontend\MomoController::class, 'handleReturn'])->name('momo.return');
});

// ==============================================
// IPN MOMO & GIAO DIỆN GIỎ HÀNG
// ==============================================

// IPN của MoMo (Server-to-Server). MoMo gọi ngầm vào đường dẫn này để báo cáo kết quả giao dịch. BẮT BUỘC PHẢI BỎ CHẶN ĐĂNG NHẬP (Public)!
Route::post('/checkout/momo/ipn', [App\Http\Controllers\Frontend\MomoController::class, 'handleIpn'])->name('momo.ipn');

// API lấy dữ liệu giỏ hàng để vẽ lên ngăn kéo (Sidebar). Route này public để ai cũng xem được giỏ hàng của chính họ
Route::get('/cart', [App\Http\Controllers\Frontend\CartController::class, 'getCartData']);

// Route hỗ trợ dev test nhanh kết nối Database
// Development-only authentication and database routes were intentionally removed.


//4. ADMIN & QUẢN TRỊ VIÊN

Route::prefix('admin')->name('admin.')->middleware(['auth', \App\Http\Middleware\IsAdmin::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Backend\DashboardController::class, 'index'])->name('dashboard');

    // QUẢN LÝ KHÁCH HÀNG
    Route::post('customers/bulk-delete', [App\Http\Controllers\Backend\CustomerController::class, 'bulkDelete'])->name('customers.bulk_delete');
    Route::post('customers/{id}/toggle-status', [App\Http\Controllers\Backend\CustomerController::class, 'toggleStatus'])->name('customers.toggle_status');
    Route::resource('customers', App\Http\Controllers\Backend\CustomerController::class);

    // Xem danh sách toàn bộ đơn hàng của quán
    Route::get('/orders', [App\Http\Controllers\Backend\SecureOrderController::class, 'index'])->name('orders.index');

    // Xuất báo cáo đơn hàng ra file Excel/CSV
    Route::get('/orders/export', [App\Http\Controllers\Backend\SecureOrderController::class, 'export'])->name('orders.export');

    // Xem chi tiết đơn hàng 
    Route::get('/orders/{id}', [App\Http\Controllers\Backend\SecureOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/status', [App\Http\Controllers\Backend\SecureOrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::delete('/orders/{id}', [App\Http\Controllers\Backend\SecureOrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders/bulk-delete', [App\Http\Controllers\Backend\SecureOrderController::class, 'bulkDelete'])->name('orders.bulk_delete');

    // QUẢN LÝ SẢN PHẨM
    Route::post('products/bulk-delete', [App\Http\Controllers\Backend\HardenedProductController::class, 'bulkDelete'])->name('products.bulk_delete');
    Route::resource('products', App\Http\Controllers\Backend\HardenedProductController::class)->except(['show']);
    Route::delete('products/gallery/{id}', [App\Http\Controllers\Backend\HardenedProductController::class, 'deleteGalleryImage'])->name('products.gallery.destroy');

    // QUẢN LÝ KHUYẾN MÃI
    Route::post('promotions/bulk-delete', [App\Http\Controllers\Backend\PromotionController::class, 'bulkDelete'])->name('promotions.bulk_delete');
    Route::resource('promotions', App\Http\Controllers\Backend\PromotionController::class)->except(['show']);

    // QUẢN LÝ ĐÁNH GIÁ
    Route::post('reviews/bulk-delete', [App\Http\Controllers\Backend\ReviewController::class, 'bulkDelete'])->name('reviews.bulk_delete');
    Route::resource('reviews', App\Http\Controllers\Backend\ReviewController::class)->except(['show']);
    Route::post('reviews/{id}/toggle-visibility', [App\Http\Controllers\Backend\ReviewController::class, 'toggleVisibility'])->name('reviews.toggle_visibility');
    Route::delete('reviews/{id}/image', [App\Http\Controllers\Backend\ReviewController::class, 'deleteImage'])->name('reviews.delete_image');

    // QUẢN LÝ DANH MỤC
    Route::post('categories/bulk-delete', [App\Http\Controllers\Backend\CategoryController::class, 'bulkDelete'])->name('categories.bulk_delete');
    Route::resource('categories', App\Http\Controllers\Backend\CategoryController::class)->except(['show']);

    // QUẢN LÝ BANNER
    Route::post('banners/bulk-delete', [App\Http\Controllers\Backend\BannerController::class, 'bulkDelete'])->name('banners.bulk_delete');
    Route::post('banners/{id}/toggle-status', [App\Http\Controllers\Backend\BannerController::class, 'toggleStatus'])->name('banners.toggle_status');
    Route::resource('banners', App\Http\Controllers\Backend\BannerController::class)->except(['show']);

    // BÁO CÁO & THỐNG KÊ
    Route::get('reports', [App\Http\Controllers\Backend\ReportController::class, 'index'])->name('reports.index');

    // CẤU HÌNH HỆ THỐNG
    Route::get('settings', [App\Http\Controllers\Backend\SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [App\Http\Controllers\Backend\SettingController::class, 'update'])->name('settings.update');

    // QUẢN LÝ KHO
    Route::post('materials/bulk-delete', [App\Http\Controllers\Backend\MaterialController::class, 'bulkDelete'])->name('materials.bulk_delete');
    //danh mục gốc
    Route::resource('materials', App\Http\Controllers\Backend\MaterialController::class)->except(['create', 'edit', 'show']);

    // Nhập kho
    Route::get('materials/{material}/imports', [App\Http\Controllers\Backend\MaterialController::class, 'imports'])->name('materials.imports');
    Route::post('materials/{material}/imports', [App\Http\Controllers\Backend\MaterialController::class, 'storeImport'])->name('materials.imports.store');
    Route::put('materials/imports/{import}', [App\Http\Controllers\Backend\MaterialController::class, 'updateImport'])->name('materials.imports.update');

    //Hủy Kho (Vứt bỏ):
    Route::post('materials/imports/{import}/dispose-batch', [App\Http\Controllers\Backend\MaterialController::class, 'disposeBatch'])->name('materials.imports.dispose_batch');

});
