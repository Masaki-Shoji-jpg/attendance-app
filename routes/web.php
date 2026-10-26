<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

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
    Route::get('/attendance/list/{id}',[AttendanceController::class,'show']);
});
