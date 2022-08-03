<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::any('/clear-cache', function() {
    Artisan::call('optimize');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    return response()->json("Cleared", 200);
});
Route::middleware('cors')->group(function(){
Route::any('login', [MainController::class,'login']);
Route::any('signup', [MainController::class,'signup']);
Route::any('forgetpassword', [MainController::class,'forgetpassword']);
Route::any('verifycode', [MainController::class,'verifycode']);
Route::any('resetpassword', [MainController::class,'resetpassword']);
Route::middleware('jwt.verify')->group(function(){
Route::any('checklogin', [MainController::class,'checklogin']);
Route::any('logout', [MainController::class,'logout']);
Route::middleware('login.check')->group(function(){
Route::any('updateprofile', [MainController::class,'updateprofile']);
Route::any('dbtables', [MainController::class,'dbtables']);
Route::any('gettable', [MainController::class,'gettable']);
Route::any('savetag', [MainController::class,'savetag']);
Route::any('searchbytag', [MainController::class,'searchbytag']);
Route::any('usertaglist', [MainController::class,'usertaglist']);
Route::any('deletetag', [MainController::class,'deletetag']);
Route::any('getsavetagdata', [MainController::class,'getsavetagdata']);
});
});
});