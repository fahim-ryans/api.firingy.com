<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1;

use App\Models\Common;




// ============= districts ===========================
Route::get('/districts', [App\Http\Controllers\V1\DistrictCityController::class, 'getDistricts'])->name('districts');
Route::get('/cities', [App\Http\Controllers\V1\DistrictCityController::class, 'getCities'])->name('cities');


Route::get('/send-mail', [App\Http\Controllers\V1\DistrictCityController::class, 'sendEmail']);


// === ios customer remove ======
Route::post('/customer-remove', [App\Http\Controllers\V1\B2BCustomerController::class, 'customerRemove']); 




//=============================================================== b2b customer api ===================================================================
Route::post('/register', [App\Http\Controllers\V1\B2BAuthController::class, 'register'])->name('register');
Route::post('/login', [App\Http\Controllers\V1\B2BAuthController::class, 'login'])->name('login');

Route::post('/login-otp-check', [App\Http\Controllers\V1\B2BAuthController::class, 'loginOtpVerification'])->name('login-otp-check');
Route::post('/login-otp-resend', [App\Http\Controllers\V1\B2BAuthController::class, 'loginOtpResend'])->name('login-otp-resend');

Route::post('/reg-otp-verify', [App\Http\Controllers\V1\B2BAuthController::class, 'registerOtpVerification'])->name('reg-otp-verify');
Route::post('/reg-otp-resend', [App\Http\Controllers\V1\B2BAuthController::class, 'registerOtpResend'])->name('reg-otp-resend');


//================================================= forgot password ===============================================================================
Route::post('/forgot-password', [App\Http\Controllers\V1\B2BCustomerController::class , 'sendForgotPasswordVerificationCode'])->name('forgot-password');
Route::post('/resend-code', [App\Http\Controllers\V1\B2BCustomerController::class , 'otpRsend'])->name('resend-code');
Route::post('/verify-code', [App\Http\Controllers\V1\B2BCustomerController::class , 'verificationCodeCechk'])->name('verify-code');
Route::post('/reset-password', [App\Http\Controllers\V1\B2BCustomerController::class , 'resetPassword'])->name('reset-password');


Route::post('/get-inv-all-data', [App\Http\Controllers\V1\B2BCustomerController::class, 'getInvB2BAllData'])->name('get-inv-all-data');
Route::post('/get-inv-data', [App\Http\Controllers\V1\B2BCustomerController::class, 'getInvB2BDataOnPage'])->name('get-inv-data');
Route::post('/customer-query', [App\Http\Controllers\V1\B2BCustomerController::class, 'customerQuery'])->name('customer-query');


Route::post('/product-details', [App\Http\Controllers\V1\B2BCustomerController::class, 'getProductDetailsAttribute'])->name('product-details');
Route::post('/change-password', [App\Http\Controllers\V1\B2BCustomerController::class, 'changePassword']);        

Route::post('/profile', [App\Http\Controllers\V1\B2BCustomerController::class, 'getUserProfile']);        
Route::post('/profile-update', [App\Http\Controllers\V1\B2BCustomerController::class, 'userProfileUpdate']);  



Route::post('/search', [App\Http\Controllers\V1\B2BCustomerController::class, 'globalSearch']);  
Route::post('/search2', [App\Http\Controllers\V1\B2BCustomerController::class, 'globalSearch2']);  

Route::get('/imp', [App\Http\Controllers\V1\B2BCustomerController::class, 'invDataImport'])->name('imp');

Route::post('/history', [App\Http\Controllers\V1\B2BCustomerController::class, 'getProductHistory']);
Route::post('/history-new', [App\Http\Controllers\V1\B2BCustomerController::class, 'getQueryProducts']);
Route::post('/history-new-with-b2b-prices', [App\Http\Controllers\V1\B2BCustomerController::class, 'getQueryProductsB2BPrices']);


Route::post('/my-ordered-list', [App\Http\Controllers\V1\B2BCustomerController::class, 'getOrderedProductList'])->name('my-ordered-list');
Route::post('/order-save', [App\Http\Controllers\V1\B2BCustomerController::class, 'saveFinalOrder']);

Route::get('/home-page-list', [App\Http\Controllers\V1\B2BCustomerController::class, 'homePageList']);
Route::get('/home-page-list-detail', [App\Http\Controllers\V1\B2BCustomerController::class, 'homePageListDetail']);


Route::get('/order-save-multiple-query', [App\Http\Controllers\V1\B2BCustomerController::class, 'orderMultipleQuery']);




Route::get('/filter', [App\Http\Controllers\V1\B2BCustomerController::class, 'getFilterData'])->name('filter');
Route::get('/categories', [App\Http\Controllers\V1\B2BCustomerController::class, 'getCategories'])->name('categories');
Route::get('/attributes-list', [App\Http\Controllers\V1\B2BCustomerController::class, 'getAttributesData'])->name('attributes-list');

Route::post('/web-query-products', [App\Http\Controllers\V1\B2BCustomerController::class, 'getWebQueryProducts']);
Route::post('/web-query-products-remove', [App\Http\Controllers\V1\B2BCustomerController::class, 'getWebQueryProductsRemove']);
Route::get('/app-version', [App\Http\Controllers\V1\B2BCustomerController::class, 'getAppVersion'])->name('app-version');


Route::post('/order-status-counting', [App\Http\Controllers\V1\B2BCustomerController::class, 'getOrderStatusCounting']);

Route::post('/order-send-to-history', [App\Http\Controllers\V1\B2BCustomerController::class, 'orderSendToHistory']);



Route::post('/logout', [App\Http\Controllers\V1\B2BCustomerController::class, 'logout'])->name('logout');

// //jwt.verify
// Route::middleware(['auth'])->group(function () {    
// });








