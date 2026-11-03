<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoginController;
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
Route::middleware(['auth'])->group(function () {
    Route::get('/',[AttendanceController::class,'welcome']);
    Route::get('/attendance',[AttendanceController::class,'attendance']);
    Route::post('/attendance',[AttendanceController::class,'update']);
    Route::get('/attendance/list',[AttendanceController::class,'index']);

    Route::get('/attendance/detail/{id}',[AttendanceController::class,'show']);
    Route::post('/attendance/{id}',[AttendanceController::class,'store']);

    Route::get('stamp_correction_request/list',[AttendanceController::class,'correctList']);
});

Route::get('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/login', [AdminLoginController::class, 'doLogin']);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminController::class, 'todayList']);
    Route::get('admin/staff/list', [AdminLoginController::class, 'login']);
});
