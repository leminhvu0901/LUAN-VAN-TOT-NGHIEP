<?php

use Illuminate\Support\Facades\Route;

// =========================================================================
// ╔═══════════════════════════════════════════════════════════════════════╗
// ║    1. KHÁCH HÀNG - TUYẾN ĐƯỜNG CÔNG KHAI (CUSTOMER - PUBLIC ROUTES)   ║
// ╚═══════════════════════════════════════════════════════════════════════╝
// =========================================================================
Route::middleware([\App\Http\Middleware\RedirectStaffFromFrontend::class])->group(function () {

    // Trang chủ hiển thị banner và danh mục sản phẩm
    Route::get('/', [App\Http\Controllers\Frontend\HomeController::class, 'index'])->name('home');

    // Trang danh sách sản phẩm
    Route::get('/products', [App\Http\Controllers\Frontend\ProductController::class, 'index'])->name('products');

    // Trang chi tiết sản phẩm
    Route::get('/products/{slug}', [App\Http\Controllers\Frontend\ProductController::class, 'show'])->name('product.show');

    // Lấy danh sách đánh giá sản phẩm qua AJAX [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/products/{productId}/reviews', [App\Http\Controllers\Frontend\ProductController::class, 'reviews'])->name('products.reviews');
});


// =========================================================================
// ╔═══════════════════════════════════════════════════════════════════════╗
// ║          2. XÁC THỰC TÀI KHOẢN (AUTHENTICATION & OTP ROUTES)          ║
// ╚═══════════════════════════════════════════════════════════════════════╝
// =========================================================================
// Chuyển hướng đến trang chủ và hiển thị modal đăng ký
Route::get('/register', function () {
    return redirect('/')->with('show_register', true);
})->name('register');

// Xử lý thông tin đăng ký tài khoản mới
Route::post('/register', [App\Http\Controllers\Frontend\AuthController::class, 'postRegister'])->name('register.post');

// Chuyển hướng đến trang chủ và hiển thị modal đăng nhập
Route::get('/login', function () {
    return redirect('/')->with('show_login', true);
})->name('login');

// Xử lý thông tin đăng nhập tài khoản
Route::post('/login', [App\Http\Controllers\Frontend\AuthController::class, 'postLogin'])->name('login.post');

// Trang nhập mã xác nhận OTP
Route::get('/verify-otp', [App\Http\Controllers\Frontend\AuthController::class, 'getVerifyOtp'])->name('verify.otp');

// Xử lý mã OTP người dùng gửi lên
Route::post('/verify-otp', [App\Http\Controllers\Frontend\AuthController::class, 'postVerifyOtp'])->name('verify.otp.post');

// Gửi lại mã xác nhận OTP mới
Route::get('/resend-otp', [App\Http\Controllers\Frontend\AuthController::class, 'resendOtp'])->name('resend.otp');

// Hủy bỏ phiên xác minh OTP hiện tại
Route::post('/cancel-otp', [App\Http\Controllers\Frontend\AuthController::class, 'cancelOtp'])->name('verify.otp.cancel');

// Chuyển hướng đăng nhập bằng tài khoản Google
Route::get('/auth/google', [App\Http\Controllers\Frontend\AuthController::class, 'redirectToGoogle'])->name('auth.google');

// Xử lý thông tin đăng nhập Google trả về
Route::get('/auth/google/callback', [App\Http\Controllers\Frontend\AuthController::class, 'handleGoogleCallback']);

// Đăng xuất tài khoản người dùng
Route::get('/logout', [App\Http\Controllers\Frontend\AuthController::class, 'logout'])->name('logout');

// Chuyển hướng đến trang chủ và hiển thị modal quên mật khẩu
Route::get('/forgot-password', function () {
    return redirect('/')->with('show_forgot', true);
})->name('forgot-password');

// Gửi link yêu cầu thiết lập lại mật khẩu
Route::post('/forgot-password', [App\Http\Controllers\Frontend\AuthController::class, 'postForgotPassword'])->name('forgot-password.post');

// Trang hiển thị giao diện nhập mật khẩu mới
Route::get('/reset-password', [App\Http\Controllers\Frontend\AuthController::class, 'getResetPassword'])->name('reset.password.get');

// Xử lý cập nhật mật khẩu mới của người dùng
Route::post('/reset-password', [App\Http\Controllers\Frontend\AuthController::class, 'postResetPassword'])->name('reset.password.post');


// =========================================================================
// ╔═══════════════════════════════════════════════════════════════════════╗
// ║    3. KHÁCH HÀNG - ĐÃ ĐĂNG NHẬP (CUSTOMER - AUTHENTICATED ROUTES)     ║
// ╚═══════════════════════════════════════════════════════════════════════╝
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // Trang hồ sơ thông tin cá nhân
    Route::get('/profile', [App\Http\Controllers\Frontend\ProfileController::class, 'index'])
        ->middleware(\App\Http\Middleware\RedirectStaffFromFrontend::class)->name('profile');

    // Cập nhật thông tin tài khoản cá nhân
    Route::post('/profile', [App\Http\Controllers\Frontend\ProfileController::class, 'update'])->name('profile.update');

    // Kiểm tra trùng lặp số điện thoại qua AJAX [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/profile/check-phone', [App\Http\Controllers\Frontend\ProfileController::class, 'checkPhone'])->name('profile.check_phone');

    // Đổi mật khẩu tài khoản người dùng
    Route::post('/profile/change-password', [App\Http\Controllers\Frontend\ProfileController::class, 'changePassword'])->name('profile.change-password');

    // Trang lịch sử danh sách đơn hàng đã mua
    Route::get('/orders', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'index'])
        ->middleware(\App\Http\Middleware\RedirectStaffFromFrontend::class)->name('orders');

    // Đặt lại đơn hàng cũ đã mua
    Route::post('/orders/{order}/reorder', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'reorder'])->name('orders.reorder');

    // Hủy bỏ đơn hàng đang chờ xác nhận
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'cancel'])->name('orders.cancel');

    // Trang viết đánh giá cho sản phẩm đã mua
    Route::get('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\Frontend\ReviewController::class, 'create'])
        ->middleware(\App\Http\Middleware\RedirectStaffFromFrontend::class)->name('review.create');

    // Lưu đánh giá sản phẩm mới vào CSDL
    Route::post('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\Frontend\ReviewController::class, 'store'])->name('review.store');

    // Cập nhật nội dung đánh giá sản phẩm
    Route::put('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\Frontend\ReviewController::class, 'update'])->name('review.update');

    // Bật/Tắt yêu thích sản phẩm (nút thả tim) [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/favorite/toggle', [App\Http\Controllers\Frontend\ProfileController::class, 'toggleFavorite'])->name('favorite.toggle');

    // Thêm sản phẩm vào giỏ hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/add', [App\Http\Controllers\Frontend\CartController::class, 'add']);

    // Xóa một món ra khỏi giỏ hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/remove', [App\Http\Controllers\Frontend\CartController::class, 'remove']);

    // Xóa nhiều sản phẩm đã chọn trong giỏ hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/remove-many', [App\Http\Controllers\Frontend\CartController::class, 'removeMany'])->name('cart.remove-many');

    // Xóa toàn bộ sản phẩm trong giỏ hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/clear', [App\Http\Controllers\Frontend\CartController::class, 'clear'])->name('cart.clear');

    // Cập nhật số lượng của món trong giỏ hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/update', [App\Http\Controllers\Frontend\CartController::class, 'update']);

    // Thêm toàn bộ danh sách sản phẩm yêu thích vào giỏ hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/add-all', [App\Http\Controllers\Frontend\CartController::class, 'addAll']);

    // Lưu các sản phẩm được chọn thanh toán vào Session [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cart/set-selected', [App\Http\Controllers\Frontend\CartController::class, 'setSelected'])->name('cart.set-selected');

    // Lấy danh sách Tỉnh/Thành phố hành chính [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/administrative/provinces', [App\Http\Controllers\Frontend\AdministrativeDivisionController::class, 'provinces'])->name('administrative.provinces');

    // Lấy danh sách Quận/Huyện dựa theo Tỉnh/Thành phố [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/administrative/provinces/{provinceCode}/wards', [App\Http\Controllers\Frontend\AdministrativeDivisionController::class, 'wards'])->name('administrative.wards');

    // Lưu địa chỉ giao hàng mới của khách
    Route::post('/profile/address', [App\Http\Controllers\Frontend\ProfileController::class, 'storeAddress'])->name('profile.address.store');

    // Cập nhật thông tin địa chỉ giao hàng
    Route::post('/profile/address/{id}', [App\Http\Controllers\Frontend\ProfileController::class, 'updateAddress'])->name('profile.address.update');

    // Xóa địa chỉ giao hàng ra khỏi danh sách
    Route::post('/profile/address/{id}/delete', [App\Http\Controllers\Frontend\ProfileController::class, 'deleteAddress'])->name('profile.address.delete');

    // Cấu hình địa chỉ làm địa chỉ mặc định nhận hàng
    Route::post('/profile/address/{id}/default', [App\Http\Controllers\Frontend\ProfileController::class, 'setDefaultAddress'])->name('profile.address.default');

    // Trang điền thông tin và thanh toán đơn hàng (Checkout)
    Route::get('/checkout', [App\Http\Controllers\Frontend\CartController::class, 'checkout'])
        ->middleware(\App\Http\Middleware\RedirectStaffFromFrontend::class)->name('checkout');

    // Lưu đơn hàng mới với hình thức thanh toán COD
    Route::post('/checkout', [App\Http\Controllers\Frontend\CustomerOrderController::class, 'store'])->name('checkout.store');

    // Tính toán khoảng cách giao hàng bằng tọa độ [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/checkout/distance', [App\Http\Controllers\Frontend\CartController::class, 'calculateDistance']);

    // Tính toán phụ phí vận chuyển thời tiết xấu [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/checkout/weather-fee', [App\Http\Controllers\Frontend\CartController::class, 'calculateWeatherFee']);

    // Kiểm tra tính hợp lệ của mã giảm giá khi áp dụng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/checkout/validate-coupon', [App\Http\Controllers\Frontend\CartController::class, 'validateCoupon']);

    // Tạo yêu cầu thanh toán qua cổng điện tử MoMo
    Route::post('/checkout/momo', [App\Http\Controllers\Frontend\MomoController::class, 'createPayment'])->name('momo.pay');

    // Nhận thông tin phản hồi từ cổng MoMo sau khi thanh toán
    Route::get('/checkout/momo/return', [App\Http\Controllers\Frontend\MomoController::class, 'handleReturn'])->name('momo.return');

    // Tạo yêu cầu thanh toán qua cổng điện tử VNPay
    Route::post('/checkout/vnpay', [App\Http\Controllers\Frontend\VnpayController::class, 'createPayment'])->name('vnpay.pay');

    // Nhận thông tin phản hồi từ cổng VNPay sau khi thanh toán
    Route::get('/checkout/vnpay/return', [App\Http\Controllers\Frontend\VnpayController::class, 'handleReturn'])->name('vnpay.return');
});


// =========================================================================
// ╔═══════════════════════════════════════════════════════════════════════╗
// ║ 4. THANH TOÁN ONLINE - KÊNH IPN SERVER-TO-SERVER (PAYMENT IPN ROUTING)║
// ╚═══════════════════════════════════════════════════════════════════════╝
// =========================================================================
// Kênh nhận dữ liệu trạng thái IPN tự động từ MoMo (Server-to-Server)
Route::post('/checkout/momo/ipn', [App\Http\Controllers\Frontend\MomoController::class, 'handleIpn'])->name('momo.ipn');

// Kênh nhận dữ liệu trạng thái IPN tự động từ VNPay (Server-to-Server)
Route::get('/checkout/vnpay/ipn', [App\Http\Controllers\Frontend\VnpayController::class, 'handleIpn'])->name('vnpay.ipn');


// =========================================================================
// ╔═══════════════════════════════════════════════════════════════════════╗
// ║               5. TIỆN ÍCH CHUNG (GENERAL UTILITIES)                   ║
// ╚═══════════════════════════════════════════════════════════════════════╝
// =========================================================================
// Lấy thông tin giỏ hàng hiện tại phục vụ hiển thị Sidebar [TRẢ VỀ JSON/AJAX CHO JS]
Route::get('/cart', [App\Http\Controllers\Frontend\CartController::class, 'getCartData']);


// =========================================================================
// ╔═══════════════════════════════════════════════════════════════════════╗
// ║              6. QUẢN TRỊ VIÊN - ADMIN (ADMINISTRATOR ROUTES)          ║
// ╚═══════════════════════════════════════════════════════════════════════╝
// =========================================================================
Route::prefix('admin')->name('admin.')->middleware(['auth', \App\Http\Middleware\IsAdmin::class])->group(function () {

    // Trang tổng quan báo cáo quản trị Admin Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Backend\Admin\DashboardController::class, 'index'])->name('dashboard');

    // --- QUẢN LÝ KHÁCH HÀNG (CUSTOMER MANAGEMENT) ---
    // Xóa hàng loạt tài khoản khách hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('customers/bulk-delete', [App\Http\Controllers\Backend\Admin\CustomerController::class, 'bulkDelete'])->name('customers.bulk_delete');

    // Khóa hoặc mở khóa tài khoản khách hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('customers/{id}/toggle-status', [App\Http\Controllers\Backend\Admin\CustomerController::class, 'toggleStatus'])->name('customers.toggle_status');

    // Quản lý thông tin tài khoản khách hàng (CRUD)
    Route::resource('customers', App\Http\Controllers\Backend\Admin\CustomerController::class);

    // --- QUẢN LÝ ĐƠN HÀNG (ORDER MANAGEMENT) ---
    // Xem danh sách toàn bộ đơn hàng của cửa hàng
    Route::get('/orders', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'index'])->name('orders.index');

    // Xuất báo cáo đơn hàng ra tệp Excel/CSV
    Route::get('/orders/export', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'export'])->name('orders.export');

    // Xem thông tin chi tiết đơn hàng
    Route::get('/orders/{id}', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'show'])->name('orders.show');

    // Cập nhật trạng thái xử lý đơn hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{id}/status', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'updateStatus'])->name('orders.status.update');

    // Admin phê duyệt đơn hàng giá trị lớn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{id}/approve', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'approveOrder'])->name('orders.approve');

    // Thực hiện hoàn tiền cho các đơn thanh toán điện tử [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{order}/refund', [App\Http\Controllers\Frontend\OnlinePaymentGatewayController::class, 'refund'])->name('orders.refund');

    // Xóa đơn hàng ra khỏi hệ thống
    Route::delete('/orders/{id}', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'destroy'])->name('orders.destroy');

    // Xóa hàng loạt các đơn hàng được chọn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/bulk-delete', [App\Http\Controllers\Backend\Admin\SecureOrderController::class, 'bulkDelete'])->name('orders.bulk_delete');

    // --- QUẢN LÝ SẢN PHẨM & DANH MỤC (PRODUCT & CATEGORY MANAGEMENT) ---
    // Xóa hàng loạt sản phẩm được chọn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('products/bulk-delete', [App\Http\Controllers\Backend\Admin\HardenedProductController::class, 'bulkDelete'])->name('products.bulk_delete');

    // Quản lý danh mục món nước sản phẩm (CRUD)
    Route::resource('products', App\Http\Controllers\Backend\Admin\HardenedProductController::class)->except(['show']);

    // Xóa hình ảnh thư viện sản phẩm (Gallery)
    Route::delete('products/gallery/{id}', [App\Http\Controllers\Backend\Admin\HardenedProductController::class, 'deleteGalleryImage'])->name('products.gallery.destroy');

    // Xóa hàng loạt danh mục được chọn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('categories/bulk-delete', [App\Http\Controllers\Backend\Admin\CategoryController::class, 'bulkDelete'])->name('categories.bulk_delete');

    // Quản lý danh mục món nước của cửa hàng (CRUD)
    Route::resource('categories', App\Http\Controllers\Backend\Admin\CategoryController::class)->except(['show']);

    // --- QUẢN LÝ KHUYẾN MÃI (PROMOTION MANAGEMENT) ---
    // Xóa hàng loạt mã khuyến mãi được chọn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('promotions/bulk-delete', [App\Http\Controllers\Backend\Admin\PromotionController::class, 'bulkDelete'])->name('promotions.bulk_delete');

    // Quản lý các chương trình khuyến mãi và mã ưu đãi (CRUD)
    Route::resource('promotions', App\Http\Controllers\Backend\Admin\PromotionController::class)->except(['show']);

    // --- QUẢN LÝ ĐÁNH GIÁ (REVIEW MANAGEMENT) ---
    // Xóa hàng loạt đánh giá sản phẩm được chọn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('reviews/bulk-delete', [App\Http\Controllers\Backend\Admin\ReviewController::class, 'bulkDelete'])->name('reviews.bulk_delete');

    // Quản lý danh sách đánh giá của khách hàng (CRUD)
    Route::resource('reviews', App\Http\Controllers\Backend\Admin\ReviewController::class)->except(['show']);

    // Ẩn hoặc hiện bình luận đánh giá ngoài giao diện khách [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('reviews/{id}/toggle-visibility', [App\Http\Controllers\Backend\Admin\ReviewController::class, 'toggleVisibility'])->name('reviews.toggle_visibility');

    // Xóa hình ảnh đi kèm bình luận đánh giá
    Route::delete('reviews/{id}/image', [App\Http\Controllers\Backend\Admin\ReviewController::class, 'deleteImage'])->name('reviews.delete_image');

    // --- QUẢN LÝ BANNER QUẢNG CÁO (BANNER MANAGEMENT) ---
    // Xóa hàng loạt các banner được chọn [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('banners/bulk-delete', [App\Http\Controllers\Backend\Admin\BannerController::class, 'bulkDelete'])->name('banners.bulk_delete');

    // Khóa hoặc mở hoạt động của các banner trang chủ [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('banners/{id}/toggle-status', [App\Http\Controllers\Backend\Admin\BannerController::class, 'toggleStatus'])->name('banners.toggle_status');

    // Quản lý các banner trang chủ của cửa hàng (CRUD)
    Route::resource('banners', App\Http\Controllers\Backend\Admin\BannerController::class)->except(['show']);

    // --- BÁO CÁO THỐNG KÊ (REPORTS MANAGEMENT) ---
    // Trang hiển thị thống kê báo cáo của cửa hàng
    Route::get('reports', [App\Http\Controllers\Backend\Admin\ReportController::class, 'index'])->name('reports.index');

    // Xuất báo cáo hoạt động thống kê ra tệp tin
    Route::get('reports/export', [App\Http\Controllers\Backend\Admin\ReportController::class, 'export'])->name('reports.export');

    // --- CẤU HÌNH HỆ THỐNG (SYSTEM SETTINGS) ---
    // Trang cấu hình thông số và thiết lập hệ thống
    Route::get('settings', [App\Http\Controllers\Backend\Admin\SettingController::class, 'index'])->name('settings.index');

    // Cập nhật giá trị các thiết lập hệ thống
    Route::put('settings', [App\Http\Controllers\Backend\Admin\SettingController::class, 'update'])->name('settings.update');

    // --- QUẢN LÝ KHO NGUYÊN LIỆU (INVENTORY MANAGEMENT) ---
    // Xóa hàng loạt các loại nguyên vật liệu trong kho [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('materials/bulk-delete', [App\Http\Controllers\Backend\Admin\MaterialController::class, 'bulkDelete'])->name('materials.bulk_delete');

    // Quản lý danh mục các loại nguyên vật liệu trong kho (CRUD)
    Route::resource('materials', App\Http\Controllers\Backend\Admin\MaterialController::class)->except(['create', 'edit', 'show']);

    // Xem lịch sử nhập kho của một loại nguyên liệu
    Route::get('materials/{material}/imports', [App\Http\Controllers\Backend\Admin\MaterialController::class, 'imports'])->name('materials.imports');

    // Tạo phiếu nhập kho lô nguyên vật liệu mới
    Route::post('materials/{material}/imports', [App\Http\Controllers\Backend\Admin\MaterialController::class, 'storeImport'])->name('materials.imports.store');

    // Cập nhật thông tin phiếu nhập kho nguyên liệu
    Route::put('materials/imports/{import}', [App\Http\Controllers\Backend\Admin\MaterialController::class, 'updateImport'])->name('materials.imports.update');

    // Thực hiện hủy bỏ lô nguyên vật liệu hết hạn sử dụng
    Route::post('materials/imports/{import}/dispose-batch', [App\Http\Controllers\Backend\Admin\MaterialController::class, 'disposeBatch'])->name('materials.imports.dispose_batch');

    // Thực hiện xuất kho nguyên vật liệu để pha chế nước uống
    Route::post('materials/imports/{import}/consume-batch', [App\Http\Controllers\Backend\Admin\MaterialController::class, 'consumeBatch'])->name('materials.imports.consume_batch');

    // --- QUẢN LÝ TÀI KHOẢN NHÂN VIÊN (STAFF ACCOUNTS MANAGEMENT) ---
    // Khóa hoặc kích hoạt tài khoản nhân viên [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('staff-accounts/{id}/toggle-status', [App\Http\Controllers\Backend\Staff\StaffAccountController::class, 'toggleStatus'])->name('staff_accounts.toggle_status');

    // Cập nhật quyền hạn/phân loại vai trò của nhân viên [TRẢ VỀ JSON/AJAX CHO JS]
    Route::patch('staff-accounts/{id}/staff-type', [App\Http\Controllers\Backend\Staff\StaffAccountController::class, 'updateType'])->name('staff_accounts.update_type');

    // Quản lý thông tin tài khoản các nhân viên cửa hàng (CRUD)
    Route::resource('staff-accounts', App\Http\Controllers\Backend\Staff\StaffAccountController::class)->names('staff_accounts')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Thống kê số đơn hàng giao thành công của từng nhân viên giao hàng theo ngày/tuần/tháng/năm/tùy chọn
    Route::get('delivery-statistics', [App\Http\Controllers\Backend\Admin\DeliveryStatisticsController::class, 'index'])->name('delivery_statistics.index');
});

/*
|--------------------------------------------------------------------------
| 7. NHÂN VIÊN - LỄ TÂN (STAFF RECEPTIONIST ROUTES)
|--------------------------------------------------------------------------
*/
Route::prefix('staff/reception')->name('staff.reception.')->middleware(['auth', \App\Http\Middleware\IsReceptionist::class])->group(function () {

    // Trang tổng quan của nhân viên lễ tân
    Route::get('/dashboard', [App\Http\Controllers\Backend\Staff\Reception\DashboardController::class, 'index'])->name('dashboard');

    // --- QUẢN LÝ ĐƠN HÀNG (POS & ORDER MANAGEMENT) ---
    // Xem danh sách đơn hàng cần xử lý
    Route::get('/orders', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'index'])->name('orders.index');

    // Mở trang tạo đơn hàng trực tiếp tại quầy cho khách (POS)
    Route::get('/orders/create', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'createOrder'])->name('orders.create');

    // Tính toán trước tổng tiền đơn hàng tại quầy qua AJAX [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/orders/preview-total', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'previewTotal'])->name('orders.preview_total');

    // Lưu đơn hàng trực tiếp tại quầy vào hệ thống [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'storeOrder'])->name('orders.store');

    // Tìm nhanh thông tin khách hàng bằng số điện thoại [TRẢ VỀ JSON/AJAX CHO JS]
    Route::get('/customers/search', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'searchCustomer'])->name('customers.search');

    // Xem chi tiết một đơn hàng cụ thể
    Route::get('/orders/{order}', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'show'])->name('orders.show');

    // Cập nhật trạng thái xử lý đơn hàng của lễ tân [TRẢ VỀ JSON/AJAX CHO JS]
    Route::patch('/orders/{order}/status', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'updateStatus'])->name('orders.status.update');

    // Lễ tân gửi yêu cầu Admin phê duyệt đơn hàng [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{order}/request-approval', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'requestAdminApproval'])->name('orders.request_approval');

    // Xử lý yêu cầu hoàn tiền cho các đơn thanh toán điện tử [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{order}/refund', [App\Http\Controllers\Frontend\OnlinePaymentGatewayController::class, 'refund'])->name('orders.refund');

    // Phân công nhân viên shipper đi giao đơn hàng cụ thể [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{order}/assign-delivery', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'assignDelivery'])->name('orders.assign_delivery');

    // Khởi tạo yêu cầu thanh toán online cho đơn hàng sẵn có [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{order}/pay-online', [App\Http\Controllers\Frontend\OnlinePaymentGatewayController::class, 'payExisting'])->name('orders.pay_online');

    // Lễ tân xác nhận đã nhận đủ tiền mặt từ khách tại quầy [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/orders/{order}/confirm-cash', [App\Http\Controllers\Backend\Staff\Reception\OrderController::class, 'confirmCashPayment'])->name('orders.confirm_cash');

    // --- QUẢN LÝ KHO VẬT TƯ TẠI QUẦY (LOCAL INVENTORY MANAGEMENT) ---
    // Xem danh sách nguyên vật liệu kho tại quầy lễ tân
    Route::get('/materials', [App\Http\Controllers\Backend\Staff\Reception\MaterialController::class, 'index'])->name('materials.index');

    // Xem lịch sử nhập kho các lô nguyên vật liệu của quầy lễ tân
    Route::get('/materials/{material}/imports', [App\Http\Controllers\Backend\Staff\Reception\MaterialController::class, 'imports'])->name('materials.imports');

    // Nhập thêm lô nguyên vật liệu mới tại quầy lễ tân
    Route::post('/materials/{material}/imports', [App\Http\Controllers\Backend\Staff\Reception\MaterialController::class, 'storeImport'])->name('materials.imports.store');

    // Thực hiện xuất kho nguyên vật liệu để pha chế tại quầy
    Route::post('/materials/imports/{import}/consume-batch', [
        App\Http\Controllers\Backend\Staff\Reception\MaterialController::class,
        'consumeBatch'
    ])->name('materials.imports.consume_batch');

    // --- QUẢN LÝ KHUYẾN MÃI & HỒ SƠ CÁ NHÂN (PROMOTIONS & PROFILE) ---
    // Xem danh sách các mã khuyến mãi đang hoạt động
    Route::get('/promotions', [App\Http\Controllers\Backend\Staff\Reception\PromotionController::class, 'index'])->name('promotions.index');

    // Xem thông tin hồ sơ tài khoản cá nhân của lễ tân
    Route::get('/profile', [App\Http\Controllers\Backend\Staff\StaffProfileController::class, 'edit'])->name('profile.edit');

    // --- ĐỐI SOÁT COD SHIPPER (COD SETTLEMENT) ---
    // Trang đối soát nộp tiền mặt COD của nhân viên giao hàng
    Route::get('/cod-settlement', [App\Http\Controllers\Backend\Staff\Reception\CodController::class, 'index'])->name('cod_settlement.index');

    // Xác nhận đã nhận tiền COD nộp về của một đơn hàng cụ thể [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cod-settlement/orders/{order}/settle', [App\Http\Controllers\Backend\Staff\Reception\CodController::class, 'settleOne'])->name('cod_settlement.settle_one');

    // Xác nhận đã nhận đủ toàn bộ tiền COD nộp về của shipper trong ca [TRẢ VỀ JSON/AJAX CHO JS]
    Route::post('/cod-settlement/staff/{deliveryStaff}/settle-all', [App\Http\Controllers\Backend\Staff\Reception\CodController::class, 'settleAll'])->name('cod_settlement.settle_all');
});

/*
|--------------------------------------------------------------------------
| 8. NHÂN VIÊN - GIAO HÀNG (STAFF DELIVERY/SHIPPER ROUTES)
|--------------------------------------------------------------------------
*/
Route::prefix('staff/delivery')->name('staff.delivery.')->middleware(['auth', \App\Http\Middleware\IsDelivery::class])->group(function () {

    // Trang tổng quan của nhân viên shipper giao hàng
    Route::get('/dashboard', [App\Http\Controllers\Backend\Staff\Delivery\DashboardController::class, 'index'])->name('dashboard');

    // Xem danh sách các đơn hàng được giao đi giao
    Route::get('/orders', [App\Http\Controllers\Backend\Staff\Delivery\OrderController::class, 'index'])->name('orders.index');

    // Xem chi tiết thông tin và địa chỉ đơn hàng cần giao
    Route::get('/orders/{order}', [App\Http\Controllers\Backend\Staff\Delivery\OrderController::class, 'show'])->name('orders.show');

    // Shipper xác nhận đang đi giao đơn hàng này [TRẢ VỀ JSON KHI GỌI AJAX]
    Route::patch('/orders/{order}/ship', [App\Http\Controllers\Backend\Staff\Delivery\OrderController::class, 'ship'])->name('orders.ship');

    // Shipper xác nhận đã giao đơn hàng thành công và thu tiền COD (nếu có) [TRẢ VỀ JSON KHI GỌI AJAX]
    Route::patch('/orders/{order}/complete', [App\Http\Controllers\Backend\Staff\Delivery\OrderController::class, 'complete'])->name('orders.complete');

    // Shipper báo cáo giao hàng thất bại kèm theo lý do cụ thể [TRẢ VỀ JSON KHI GỌI AJAX]
    Route::patch('/orders/{order}/fail', [App\Http\Controllers\Backend\Staff\Delivery\OrderController::class, 'fail'])->name('orders.fail');

    // Xem thông tin hồ sơ tài khoản cá nhân của shipper
    Route::get('/profile', [App\Http\Controllers\Backend\Staff\StaffProfileController::class, 'edit'])->name('profile.edit');
});
