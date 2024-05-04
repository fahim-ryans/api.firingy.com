<?php

use App\Http\Controllers\V1\CustomerController;
use App\Http\Controllers\V1\CustomerClaimController;
use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;


Route::get('/test', function() {
    return json_encode(['msg' => 'test']);
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');



Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/purchase-history', [CustomerController::class, 'purchaseHistory'])->name('purchaseHistory');
    Route::get('/account-information', [CustomerController::class, 'accountInformation'])->name('accountInformation');
    Route::post('/profile-update', [CustomerController::class, 'profileUpdate'])->name('profileUpdate');
    Route::post('/change-password', [CustomerController::class, 'changePassword'])->name('change.password');
    Route::get('/orders', [CustomerController::class, 'customerOrders'])->name('customerOrders');
    Route::post('/customer-support', [CustomerController::class, 'customerSupport'])->name('customerSupport');
    Route::get('/customer-claims', [CustomerClaimController::class, 'customerClaims'])->name('customerClaims');
});



