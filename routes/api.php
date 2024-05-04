<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\API\AuthController;

use App\Http\Controllers\V1\DistrictCityController;
use App\Http\Controllers\V1\B2BAuthController;
use App\Http\Controllers\V1\B2BCustomerOrderController;
use App\Http\Controllers\V1\B2BCustomerController;
use App\Http\Controllers\V1\B2BCustomerSearchController;

use App\Models\Common;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



// Route::controller(AuthController::class)->group(function () {
//     Route::post('blogin', 'blogin');
//     Route::post('bregister', 'bregister');
//     Route::post('blogout', 'blogout');
//     Route::post('brefresh', 'brefresh');
// });


// ============= districts ===========================
Route::controller(DistrictCityController::class)->group(function () {
    Route::get('v1/b2b/districts', 'getDistricts')->name('districts');
    Route::get('v1/b2b/cities', 'getCities')->name('cities');
    Route::get('v1/b2b/send-mail', 'sendEmail');
});




//=============================================================== b2b customer api ===================================================================
Route::controller(B2BAuthController::class)->group(function () {
    Route::post('v1/b2b/register',  'register')->name('register');
    Route::post('v1/b2b/login', 'login')->name('login');

    Route::post('v1/b2b/login-otp-check', 'loginOtpVerification')->name('login-otp-check');
    Route::post('v1/b2b/login-otp-resend', 'loginOtpResend')->name('login-otp-resend');

    Route::post('v1/b2b/reg-otp-verify',  'registerOtpVerification')->name('reg-otp-verify');
    Route::post('v1/b2b/reg-otp-resend', 'registerOtpResend')->name('reg-otp-resend');

    Route::post('v1/b2b/logout', 'logout')->name('logout');
    Route::post('v1/b2b/refresh', 'refresh')->name('refresh');
});


Route::controller(B2BCustomerSearchController::class) ->group(function() {
    Route::post('v1/b2b/search',  'globalSearch');
});


Route::controller(B2BCustomerController::class)->group(function () {

    // ========== ios customer remove ==============
    Route::post('v1/b2b/customer-remove',  'customerRemove');

    // ========== ios customer remove ==============


    //================================================= forgot password ===============================================================================
    Route::post('v1/b2b/forgot-password',  'sendForgotPasswordVerificationCode')->name('forgot-password');
    Route::post('v1/b2b/resend-code',  'otpRsend')->name('resend-code');
    Route::post('v1/b2b/verify-code',  'verificationCodeCechk')->name('verify-code');
    Route::post('v1/b2b/reset-password',  'resetPassword')->name('reset-password');


    Route::post('v1/b2b/get-inv-all-data',  'getInvB2BAllData')->name('get-inv-all-data');
    Route::post('v1/b2b/get-inv-data',  'getInvB2BDataOnPage')->name('get-inv-data');


    // ============================= customer query ======================
    Route::post('v1/b2b/customer-query',  'customerQuery')->name('customer-query');
    // ============================= customer query ======================


    Route::post('v1/b2b/product-details',  'getProductDetailsAttribute')->name('product-details');
    Route::post('v1/b2b/change-password',  'changePassword');

    // ============================= customer profile ======================
    Route::post('v1/b2b/profile',  'getUserProfile');
    Route::post('v1/b2b/profile-update',  'userProfileUpdate');
    // ============================= customer profile ======================

    // ============================= searching ======================
    Route::post('v1/b2b/vsearch',  'globalSearch');
    Route::post('v1/b2b/vsearch2',  'globalSearch2');
    // ============================= searching ======================



    Route::get('v1/b2b/imp',  'invDataImport')->name('imp');


    // ============================= order history ======================
    Route::post('v1/b2b/history',   'getProductHistory');
    Route::post('v1/b2b/history-new', 'getQueryProducts');
    Route::post('v1/b2b/history-new-with-b2b-prices',   'getQueryProductsB2BPrices');
    Route::post('v1/b2b/my-ordered-list',  'getOrderedProductList')->name('my-ordered-list');
    // ============================= order history ======================


    Route::post('v1/b2b/order-save',  'saveFinalOrder');


    Route::get('v1/b2b/home-page-list',  'homePageList');
    Route::get('v1/home-page-list-detail', 'homePageListDetail');

    Route::get('v1/b2b/filter',  'getFilterData')->name('filter');
    Route::get('v1/b2b/categories',   'getCategories')->name('categories');
    Route::get('v1/b2b/attributes-list',   'getAttributesData')->name('attributes-list');


    Route::post('v1/b2b/web-query-products',   'getWebQueryProducts');
    Route::post('v1/b2b/web-query-products-remove',  'getWebQueryProductsRemove');
    Route::post('v1/b2b/order-status-counting',  'getOrderStatusCounting');


    Route::post('v1/b2b/order-send-to-history', 'orderSendToHistory');

    // app version checking in android
    Route::get('v1/b2b/app-version',  'getAppVersion')->name('app-version');
    Route::post('v1/b2b/app-version',  'getAppVersionForIos')->name('app-version');
});


//================== new order system
Route::controller(B2BCustomerOrderController::class)->group(function () {
    Route::post('v1/b2b/orders-list', 'getCustomerQueryLists')->name('orders-list');
    Route::post('/v1/b2b/ordered-list', 'getOrderedList')->name('ordered-list');

    Route::post('/v1/b2b/order-detail', 'getSpecificOrderDetail')->name('order-detail');


    Route::post('v1/b2b/order-multiple-query', 'orderMultipleQuery')->name('order-multiple-query');


    //======= new feature sent to (not available, send to expired) ========
    Route::post('v1/b2b/order-send-to-expired-and-not-available-history', 'orderSendToExpiredAndNotAvailableHistory');
    //======= new feature sent to (not available, send to expired) ========

});


