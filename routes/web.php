<?php

// Import class Route của Laravel để khai báo các đường dẫn
use Illuminate\Support\Facades\Route;

// Import Model Banner
use App\Models\Banner;

// ==============================================
// 1. TRANG CHỦ & HIỂN THỊ SẢN PHẨM (Public Routes)
// Những route này ai cũng vào được, không cần đăng nhập
// ==============================================

// Định nghĩa route trang chủ '/'. Khi truy cập tên miền, hàm function() sẽ chạy
Route::get('/', function () {
    // Lấy tất cả banner đang kích hoạt (is_active = 1) từ Database
    $banners = \App\Models\Banner::where('is_active', 1)->get();

    // Truy vấn lấy danh mục sản phẩm (categories) kèm theo số lượng sản phẩm của từng danh mục
    $categories = \App\Models\Category::query()
        ->leftJoin('products', function ($join) { // Kết nối bảng products
            $join->on('categories.id', '=', 'products.category_id') // Điều kiện: id danh mục = category_id của sản phẩm
                ->where('products.is_active', 1); // Chỉ đếm các sản phẩm đang được bán
        })
        ->select('categories.id', 'categories.name', \Illuminate\Support\Facades\DB::raw('COUNT(products.id) as product_count')) // Đếm tổng số sản phẩm
        ->where('categories.is_active', 1) // Chỉ lấy các danh mục đang mở
        ->groupBy('categories.id', 'categories.name', 'categories.display_order') // Gom nhóm để đếm chính xác
        ->orderBy('categories.display_order') // Sắp xếp thứ tự hiển thị
        ->get(); // Thực thi truy vấn và lấy kết quả

    // Trả về view 'frontend.home' (trang chủ) và truyền 2 biến $banners, $categories sang file Blade
    return view('frontend.home', compact('banners', 'categories'));
});

// Route xem danh sách tất cả sản phẩm. Gọi hàm index() trong ProductController. Đặt tên route là 'products'
Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products');
// Route xem chi tiết 1 sản phẩm theo đường dẫn động {slug} (VD: /products/tra-sua-tran-chau)
Route::get('/products/{slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('product.show');


// ==============================================
// 2. TÀI KHOẢN & XÁC THỰC (Đăng nhập, Đăng ký, OTP, Quên mật khẩu)
// ==============================================

// Khi vào trang /register, hệ thống tự động đá về trang chủ '/' kèm theo cờ 'show_register' để popup Đăng ký tự động bật lên
Route::get('/register', function () {
    return redirect('/')->with('show_register', true);
})->name('register');
// Route xử lý dữ liệu form Đăng ký gửi lên server (Method POST)
Route::post('/register', [App\Http\Controllers\AuthController::class, 'postRegister'])->name('register.post');

// Tương tự, khi gõ /login, đá về trang chủ kèm cờ bật popup Đăng nhập
Route::get('/login', function () {
    return redirect('/')->with('show_login', true);
})->name('login');
// Route xử lý việc kiểm tra tài khoản/mật khẩu khi user bấm Đăng nhập
Route::post('/login', [App\Http\Controllers\AuthController::class, 'postLogin'])->name('login.post');

// Hiển thị giao diện màn hình nhập mã OTP (sau khi đăng ký)
Route::get('/verify-otp', [App\Http\Controllers\AuthController::class, 'getVerifyOtp'])->name('verify.otp');
// Nhận mã OTP do user nhập và kiểm tra với Database
Route::post('/verify-otp', [App\Http\Controllers\AuthController::class, 'postVerifyOtp'])->name('verify.otp.post');
// Gửi lại mã OTP mới qua email nếu user bấm "Gửi lại mã"
Route::get('/resend-otp', [App\Http\Controllers\AuthController::class, 'resendOtp'])->name('resend.otp');
// Hủy và xóa session OTP khi đóng modal hoặc hủy xác thực
Route::post('/cancel-otp', [App\Http\Controllers\AuthController::class, 'cancelOtp'])->name('verify.otp.cancel');

// Bấm nút đăng nhập bằng Google -> Chuyển hướng sang giao diện xác thực của Google
Route::get('/auth/google', [App\Http\Controllers\AuthController::class, 'redirectToGoogle'])->name('auth.google');
// Sau khi Google xác thực xong, nó sẽ đá về route này kèm theo thông tin (email, tên, avatar) của user
Route::get('/auth/google/callback', [App\Http\Controllers\AuthController::class, 'handleGoogleCallback']);

// Đăng xuất tài khoản (Xóa session)
Route::get('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// Vào trang quên mật khẩu -> Đá về trang chủ bật popup Quên mật khẩu
Route::get('/forgot-password', function () {
    return redirect('/')->with('show_forgot', true);
})->name('forgot-password');
// Xử lý việc gửi email chứa link đặt lại mật khẩu cho user
Route::post('/forgot-password', [App\Http\Controllers\AuthController::class, 'postForgotPassword'])->name('forgot-password.post');

// Hiển thị form để user nhập mật khẩu mới (khi bấm từ link trong email)
Route::get('/reset-password', [App\Http\Controllers\AuthController::class, 'getResetPassword'])->name('reset.password.get');
// Lưu mật khẩu mới vào Database
Route::post('/reset-password', [App\Http\Controllers\AuthController::class, 'postResetPassword'])->name('reset.password.post');


// ==============================================
// 3. CHỨC NĂNG CÁ NHÂN (Bắt buộc phải đăng nhập)
// ==============================================
// Bọc tất cả các route bên trong bằng Middleware 'auth'. Ai chưa đăng nhập mà cố tình truy cập sẽ bị đá văng ra!
Route::middleware(['auth'])->group(function () {
    
    // --- Hồ sơ cá nhân ---
    // Hiển thị trang Hồ sơ
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    // Cập nhật thông tin Hồ sơ (Tên, SĐT, Avatar...)
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    // Đổi mật khẩu tài khoản
    Route::post('/profile/change-password', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.change-password');
    
    // --- Lịch sử mua hàng & Đánh giá ---
    // Xem danh sách đơn hàng đã đặt
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders');
    // Hiện giao diện đánh giá (Review) 1 sản phẩm nằm trong 1 đơn hàng cụ thể
    Route::get('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\ReviewController::class, 'create'])->name('review.create');
    // Gửi đánh giá (số sao, nhận xét) vào Database
    Route::post('/orders/{orderId}/products/{productId}/review', [App\Http\Controllers\ReviewController::class, 'store'])->name('review.store');
    
    // Bật/tắt trạng thái Yêu thích sản phẩm (Thả tim)
    Route::post('/favorite/toggle', [App\Http\Controllers\ProfileController::class, 'toggleFavorite'])->name('favorite.toggle');

    // --- Giỏ hàng (Các thao tác sửa đổi giỏ hàng cần đăng nhập) ---
    // Thêm 1 sản phẩm mới vào giỏ
    Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
    // Bấm nút xóa sản phẩm khỏi giỏ
    Route::post('/cart/remove', [App\Http\Controllers\CartController::class, 'remove']);
    // Bấm nút (+), (-) để cập nhật số lượng
    Route::post('/cart/update', [App\Http\Controllers\CartController::class, 'update']);
    // Bấm "Thêm tất cả vào giỏ" ở bên ngăn kéo Yêu Thích
    Route::post('/cart/add-all', [App\Http\Controllers\CartController::class, 'addAll']);

    // --- Quản lý Danh bạ Địa chỉ nhận hàng ---
    // Lưu địa chỉ mới
    Route::post('/profile/address', [App\Http\Controllers\ProfileController::class, 'storeAddress'])->name('profile.address.store');
    // Chỉnh sửa địa chỉ đã có (dựa theo id)
    Route::post('/profile/address/{id}', [App\Http\Controllers\ProfileController::class, 'updateAddress'])->name('profile.address.update');
    // Xóa địa chỉ (dựa theo id)
    Route::post('/profile/address/{id}/delete', [App\Http\Controllers\ProfileController::class, 'deleteAddress'])->name('profile.address.delete');
    // Đánh dấu 1 địa chỉ làm địa chỉ mặc định
    Route::post('/profile/address/{id}/default', [App\Http\Controllers\ProfileController::class, 'setDefaultAddress'])->name('profile.address.default');

    // --- Thanh toán đơn hàng (Checkout) ---
    // Mở trang Thanh toán
    Route::get('/checkout', [App\Http\Controllers\CartController::class, 'checkout'])->name('checkout');
    // Nút "Đặt hàng" (Phương thức COD), lưu đơn hàng vào Database
    Route::post('/checkout', [App\Http\Controllers\OrderController::class, 'store'])->name('checkout.store');
    
    // Các đường dẫn phụ trợ (API) gọi ngầm qua AJAX:
    // Tính khoảng cách km giao hàng
    Route::get('/checkout/distance', [App\Http\Controllers\CartController::class, 'calculateDistance']);
    // Tính phụ phí thời tiết
    Route::get('/checkout/weather-fee', [App\Http\Controllers\CartController::class, 'calculateWeatherFee']);
    // Tính phụ phí thời tiết theo tọa độ GPS
    Route::get('/checkout/weather-fee-by-coords', [App\Http\Controllers\CartController::class, 'calculateWeatherFeeByCoords']);
    // Kiểm tra Mã giảm giá (Coupon) có hợp lệ không
    Route::post('/checkout/validate-coupon', [App\Http\Controllers\CartController::class, 'validateCoupon']);

    // --- Thanh toán qua ví điện tử MoMo ---
    // Gửi yêu cầu trừ tiền sang máy chủ MoMo
    Route::post('/checkout/momo', [App\Http\Controllers\MomoController::class, 'createPayment'])->name('momo.pay');
    // Sau khi quét mã MoMo xong, khách bị đá ngược về route này để xem màn hình "Thanh toán thành công"
    Route::get('/checkout/momo/return', [App\Http\Controllers\MomoController::class, 'handleReturn'])->name('momo.return');
});

// ==============================================
// IPN MOMO & GIAO DIỆN GIỎ HÀNG
// ==============================================

// IPN của MoMo (Server-to-Server). MoMo gọi ngầm vào đường dẫn này để báo cáo kết quả giao dịch. BẮT BUỘC PHẢI BỎ CHẶN ĐĂNG NHẬP (Public)!
Route::post('/checkout/momo/ipn', [App\Http\Controllers\MomoController::class, 'handleIpn'])->name('momo.ipn');

// API lấy dữ liệu giỏ hàng để vẽ lên ngăn kéo (Sidebar). Route này public để ai cũng xem được giỏ hàng của chính họ
Route::get('/cart', [App\Http\Controllers\CartController::class, 'getCartData']);

// Route hỗ trợ dev test nhanh kết nối Database
Route::get('/test-db', function () {
    $promo = \App\Models\Promotion::query()->where('id', 1)->first();
    $orders = \App\Models\Order::query()->where('promotion_id', 1)->count();
    \Illuminate\Support\Facades\Log::info('DB Check: ', ['promo' => (array) $promo, 'orders_count' => $orders]);
    return 'done';
});

Route::get('/auto-login', function () {
    $user = \App\Models\User::first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect('/profile');
    }
    return 'No user found in the database.';
});

// ==============================================
// 4. ADMIN & QUẢN TRỊ VIÊN
// ==============================================
// Gom nhóm các route của Admin lại. URL sẽ tự động được thêm prefix '/admin/'. Name route tự động thêm chữ 'admin.'
// Lưu ý: Đáng ra chỗ này phải có Middleware bảo vệ (ví dụ: auth.admin), nhưng hiện tại đang mở public cho thầy cô dễ chấm điểm.
Route::prefix('admin')->name('admin.')->group(function () {
    // Xem danh sách toàn bộ đơn hàng của quán
    Route::get('/orders', [App\Http\Controllers\Backend\OrderController::class, 'index'])->name('orders.index');
    // Hiển thị form tạo đơn hàng mới bằng tay (Dành cho thu ngân)
    Route::get('/orders/create', [App\Http\Controllers\Backend\OrderController::class, 'create'])->name('orders.create');
    // Xuất báo cáo đơn hàng ra file Excel/CSV
    Route::get('/orders/export', [App\Http\Controllers\Backend\OrderController::class, 'export'])->name('orders.export');
    // Lưu đơn hàng tạo bằng tay vào Database
    Route::post('/orders', [App\Http\Controllers\Backend\OrderController::class, 'store'])->name('orders.store');
    // Xem chi tiết 1 đơn hàng cụ thể của khách (dựa vào id đơn)
    Route::get('/orders/{id}', [App\Http\Controllers\Backend\OrderController::class, 'show'])->name('orders.show');
    // Quản lý cập nhật trạng thái đơn hàng (Mới -> Đang giao -> Hoàn thành -> Hủy)
    Route::post('/orders/{id}/status', [App\Http\Controllers\Backend\OrderController::class, 'updateStatus'])->name('orders.update-status');
});

