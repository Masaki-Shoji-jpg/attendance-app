<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_メールアドレスが未入力の場合、バリデーションメッセージが表示される(): void
    {
        $response = $this->from('admin/login')->post('admin/login', [
            'email' => '',
            'password' => 'password',
        ]);
        $response->assertRedirect('admin/login');

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
        $this->assertGuest();
    }

    public function test_パスワードが未入力の場合、バリデーションメッセージが表示される(): void
    {
        $response = $this->from('admin/login')->post('admin/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);
        $response->assertRedirect('admin/login');

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
        $this->assertGuest();
    }

    public function test_登録内容と一致しない場合、バリデーションメッセージが表示される(): void
    {
        $response = $this->from('admin/login')->post('admin/login', [
            'email' => 'notfound@example.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect('admin/login');

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
        $this->assertGuest();
    }
}
