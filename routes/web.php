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
//Route::get('/attendance',fn() => response('準備中'));
Route::get('/attendance',[AttendanceController::class,'attendance']);
Route::get('/attendance/list',[AttendanceController::class,'index']);
Route::get('/attendance/list/{id}',[AttendanceController::class,'show']);
