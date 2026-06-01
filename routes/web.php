<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/products', function () {
    return view('products');
});

Route::get('/register', function () {
    return view('partials.register');
})->name('register');

Route::get('/login', function () {
    return view('partials.login');
})->name('login');

Route::get('/forgot-password', function () {
    return view('partials.forgot-password');
})->name('forgot-password');


