<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class ClockOutTest extends TestCase
{
    use RefreshDataBase;

    public function test_退勤ボタンが正しく機能する(): void
    {
        $now = Carbon::today()->setTime(18, 0, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('attendance__button--submit--clock-out');
        $response = $this->actingAs($user)->followingRedirects()->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create();

        $now = Carbon::today()->setTime(9, 0, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($user)->get('/attendance');
        $response = $this->actingAs($user)->followingRedirects()->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $clockOut = $now->copy()->addHours(9);
        Carbon::setTestNow($clockOut);
        $response = $this->actingAs($user)->followingRedirects()->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $today = $now->format('m/d');
        $response->assertSeeInOrder([
            $today,
            $now->format('H:i'),
            $clockOut->format('H:i'),
        ]);

        Carbon::setTestNow();
    }
}
