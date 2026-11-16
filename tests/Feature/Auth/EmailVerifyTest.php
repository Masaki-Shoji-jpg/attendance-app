<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class EmailVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録後、認証メールが送信される(): void
    {
        Notification::fake();

        $response = $this->from('/register')->post('/register', [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertRedirect('/attendance');
        $this->followRedirects($response)->assertSee('認証はこちらから');

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class,function ($notification, $channels, $notifiable) {
            return $notifiable->email === 'test@example.com';
        });
    }

    public function test_メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する(): void
    {
        $user = User::factory()->unverified()->create();
        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertSee('href="http://localhost:8025"', false);
        $response->assertSee('認証はこちらから');
    }

    public function test_メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $response->assertRedirect('/attendance');

        $attendanceResponse = $this->actingAs($user->fresh())->get('/attendance');
        $attendanceResponse->assertStatus(200);
    }
}
