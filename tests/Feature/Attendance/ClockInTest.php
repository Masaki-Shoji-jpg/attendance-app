<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('attendance__button--submit--clock-in');

        $response = $this->actingAs($user)->followingRedirects()->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);
    }

    public function test_出勤は一日一回のみできる(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->from('/attendance')->followingRedirects()->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertStatus(200);
        $response->assertDontSee('attendance__button--submit--clock-in');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/attendance')->followingRedirects()->post('/attendance', [
            'action' => 'clock_in',
        ]);
        $response = $this->actingAs($user)->get('/attendance/list');

        $today = $now->format('m/d');
        $clockIn = $now->format('H:i');

        $response->assertSeeInOrder([
            $today,
            $clockIn,
        ]);

        Carbon::setTestNow();
    }
}
