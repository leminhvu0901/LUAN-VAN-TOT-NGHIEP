<?php

use Illuminate\Support\Facades\Route;

use App\Banner;

Route::get('/', function () {
    $banners = App\Banner::where('is_active', 1)->get();

    // Fetch active categories with their actual product counts
    $categories = \Illuminate\Support\Facades\DB::table('categories')
        ->leftJoin('products', function ($join) {
            $join->on('categories.id', '=', 'products.category_id')
                ->where('products.is_active', 1);
        })
        ->select('categories.id', 'categories.name', \Illuminate\Support\Facades\DB::raw('COUNT(products.id) as product_count'))
        ->where('categories.is_active', 1)
        ->groupBy('categories.id', 'categories.name', 'categories.display_order')
        ->orderBy('categories.display_order')
        ->get();

    return view('pages.home', compact('banners', 'categories'));
});

Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products');
Route::get('/products/{slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('product.show');

Route::get('/register', function () {
    return redirect('/')->with('show_register', true);
})->name('register');
Route::post('/register', [App\Http\Controllers\AuthController::class, 'postRegister'])->name('register.post');

Route::get('/login', function () {
    return redirect('/')->with('show_login', true);
})->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'postLogin'])->name('login.post');

Route::get('/verify-otp', [App\Http\Controllers\AuthController::class, 'getVerifyOtp'])->name('verify.otp');
Route::post('/verify-otp', [App\Http\Controllers\AuthController::class, 'postVerifyOtp'])->name('verify.otp.post');
Route::get('/resend-otp', [App\Http\Controllers\AuthController::class, 'resendOtp'])->name('resend.otp');

Route::get('/auth/google', [App\Http\Controllers\AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [App\Http\Controllers\AuthController::class, 'handleGoogleCallback']);

Route::get('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', function () {
    return redirect('/')->with('show_forgot', true);
})->name('forgot-password');
Route::post('/forgot-password', [App\Http\Controllers\AuthController::class, 'postForgotPassword'])->name('forgot-password.post');

Route::get('/reset-password', [App\Http\Controllers\AuthController::class, 'getResetPassword'])->name('reset.password.get');
Route::post('/reset-password', [App\Http\Controllers\AuthController::class, 'postResetPassword'])->name('reset.password.post');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/change-password', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders');
    Route::post('/favorite/toggle', [App\Http\Controllers\ProfileController::class, 'toggleFavorite'])->name('favorite.toggle');

    // Protected Cart Routes
    Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
    Route::post('/cart/remove', [App\Http\Controllers\CartController::class, 'remove']);
    Route::post('/cart/update', [App\Http\Controllers\CartController::class, 'update']);
    Route::post('/cart/add-all', [App\Http\Controllers\CartController::class, 'addAll']);

    // Address Routes
    Route::post('/profile/address', [App\Http\Controllers\ProfileController::class, 'storeAddress'])->name('profile.address.store');
    Route::post('/profile/address/{id}', [App\Http\Controllers\ProfileController::class, 'updateAddress'])->name('profile.address.update');
    Route::post('/profile/address/{id}/delete', [App\Http\Controllers\ProfileController::class, 'deleteAddress'])->name('profile.address.delete');
    Route::post('/profile/address/{id}/default', [App\Http\Controllers\ProfileController::class, 'setDefaultAddress'])->name('profile.address.default');

    // Checkout Routes
    Route::get('/checkout', [App\Http\Controllers\CartController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [App\Http\Controllers\OrderController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/distance', [App\Http\Controllers\CartController::class, 'calculateDistance']);
    Route::get('/checkout/weather-fee', [App\Http\Controllers\CartController::class, 'calculateWeatherFee']);
});

// Public Cart Route (View cart data)
Route::get('/cart', [App\Http\Controllers\CartController::class, 'getCartData']);




