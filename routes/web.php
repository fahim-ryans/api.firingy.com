<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    try {
        $path1 = "/home/samer-ryans-api/htdocs/api.ryans.com/storage/logs/laravel.log";

        $path2 = "/home/samer-ryans-api/htdocs/api.ryans.com/storage/logs/". time() ."_laravel.log";
        if (file_exists($path1)) {
            rename($path1 , $path2 );
        }
        return response()->json([ "status" => "ok" ]);
    } catch(\Exception $e) {
        // echo $e->getMessage() . "<br/>";
        return response()->json([ "status" => "ok", "msg" => $e->getMessage() ]);
    }

    // return phpinfo();
    // return view('welcome');
});
