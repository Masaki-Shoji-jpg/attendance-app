<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
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

    Route::redirect('/attendance/{id}', '/attendance/detail/{id}');
    Route::get('/attendance/detail/{id}',[AttendanceController::class,'show']);
    Route::post('/attendance/{id}',[AttendanceController::class,'store']);

    Route::get('stamp_correction_request/list',[AttendanceController::class,'correctList']);
    Route::redirect('/application/{id}', '/attendance/detail/{id}');

});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/staff/list', [AdminController::class, 'staffList']);

});
