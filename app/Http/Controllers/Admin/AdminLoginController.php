<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    /**
     * 管理者用のログイン画面を表示する
     *
     * @return View 管理者ログインビュー
     */
    public function login(): View
    {
        return view('admin.admin-login');
    }

    /**
     * 管理者のログイン認証処理を実行する
     *
     * @param LoginRequest $request
     * @return RedirectResponse 勤怠一覧画面へのリダイレクト
     */
    public function doLogin(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        if (Auth::attempt($credentials)) {
            if (Auth::user()->admin_status) {
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

    /**
     * 管理者のログアウト処理を実行し、セッションを破棄する
     *
     * @param Request $request HTTPリクエスト
     * @return RedirectResponse 管理者ログイン画面へのリダイレクトレスポンス
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->to('/admin/login');
    }
}
