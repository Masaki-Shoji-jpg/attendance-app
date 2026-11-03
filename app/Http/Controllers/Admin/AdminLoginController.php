<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\LoginRequest;

class AdminLoginController extends Controller
{
    public function login(){
        return view('admin.admin-login');
    }

    public function doLogin(LoginRequest $request){
    $credentials = $request->validated();
        if (Auth::attempt($credentials)) {
            if (Auth::user()->is_admin) {
                $request->session()->regenerate();
                return redirect()->intended('/admin/attendance/list');
            }
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
