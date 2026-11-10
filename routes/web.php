<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminCorrectController;
use App\Http\Middleware\RoleCheckMiddleware;
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
    Route::get('/attendance',[AttendanceController::class,'attendance']);
    Route::post('/attendance',[AttendanceController::class,'update']);
    Route::get('/attendance/list',[AttendanceController::class,'index']);

    Route::get('/attendance/detail/{id}',[AttendanceController::class,'show']);
    Route::post('/attendance/{id}',[AttendanceController::class,'store']);

    Route::get('stamp_correction_request/list',[AttendanceController::class,'correctList'])
    ->middleware(RoleCheckMiddleware::class);
    Route::get('/attendance/report',[AttendanceController::class,'report']);
});

Route::get('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/login', [AdminLoginController::class, 'doLogin']);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminController::class, 'todayList']);
    Route::get('admin/staff/list', [AdminController::class, 'staffList']);
    Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'staffIndex']);
    Route::get('/admin/attendance/{id}', [AdminController::class, 'staffShow']);
    Route::post('/admin/attendance/{id}', [AdminController::class, 'update']);
    Route::get('/attendance/{id}', [AdminController::class, 'staffShow']);

    Route::get('/stamp_correction_request/approve/{id}', [AdminCorrectController::class, 'correctShow']);
    Route::post('/stamp_correction_request/approve/{id}', [AdminCorrectController::class, 'approve']);

    Route::post('/admin/logout', [AdminLoginController::class, 'logout']);
});
