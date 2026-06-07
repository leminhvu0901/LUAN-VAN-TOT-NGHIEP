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
    return view('auth.register');
})->name('register');
Route::post('/register', [App\Http\Controllers\AuthController::class, 'postRegister'])->name('register.post');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'postLogin'])->name('login.post');

Route::get('/verify-otp', [App\Http\Controllers\AuthController::class, 'getVerifyOtp'])->name('verify.otp');
Route::post('/verify-otp', [App\Http\Controllers\AuthController::class, 'postVerifyOtp'])->name('verify.otp.post');
Route::get('/resend-otp', [App\Http\Controllers\AuthController::class, 'resendOtp'])->name('resend.otp');

Route::get('/auth/google', [App\Http\Controllers\AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [App\Http\Controllers\AuthController::class, 'handleGoogleCallback']);

Route::get('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('forgot-password');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders');
    Route::post('/favorite/toggle', [App\Http\Controllers\ProfileController::class, 'toggleFavorite'])->name('favorite.toggle');
    
    // Protected Cart Routes
    Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
    Route::post('/cart/remove', [App\Http\Controllers\CartController::class, 'remove']);
    Route::post('/cart/update', [App\Http\Controllers\CartController::class, 'update']);
    Route::post('/cart/add-all', [App\Http\Controllers\CartController::class, 'addAll']);
});

// Public Cart Route (View cart data)
Route::get('/cart', [App\Http\Controllers\CartController::class, 'getCartData']);

