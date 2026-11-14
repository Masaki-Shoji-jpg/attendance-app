<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class RestTest extends TestCase
{
    use RefreshDataBase;

    private User $user;
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'clock_out' => null,
        ]);
    }

    public function test_休憩ボタンが正しく機能する(): void
    {
        $now = Carbon::today()->setTime(12, 0, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertSee('attendance__button--submit--break-in');

        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertSee('休憩中');

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $this->attendance->id,
            'rest_in' => Carbon::now(),
        ]);

        Carbon::setTestNow();
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $now = Carbon::today()->setTime(12, 0, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($this->user)->get('/attendance');

        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_in',]);
        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_out',]);

        $response->assertSee('attendance__button--submit--break-in');

        Carbon::setTestNow();
    }

    public function test_休憩戻ボタンが正しく機能する(): void
    {
        $now = Carbon::today()->setTime(12, 0, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($this->user)->get('/attendance');

        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_in',]);

        $response->assertSee('attendance__button--submit--break-out');

        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_out',]);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $this->attendance->id,
            'rest_out' => Carbon::now(),
        ]);

        Carbon::setTestNow();
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $response = $this->actingAs($this->user)->get('/attendance');

        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_in',]);
        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_out',]);
        $response = $this->actingAs($this->user)->followingRedirects()->post('/attendance', [
            'action' => 'break_in',]);

        $response->assertSee('attendance__button--submit--break-out');
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        $now = Carbon::today()->setTime(12, 0, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($this->user)->get('/attendance');

        $response = $this->actingAs($this->user)->post('/attendance', [
            'action' => 'break_in',]);
        Carbon::setTestNow($now->copy()->addMinutes(30));

        $response = $this->actingAs($this->user)->post('/attendance', [
            'action' => 'break_out',]);

        $breakTime =$this->attendance->fresh()->total_break_time;
        $today = $now->format('m/d');

        $response = $this->actingAs($this->user)->get('/attendance/list');
        $response->assertSeeInOrder([
            $today,
            '0:30',
        ]);
        Carbon::setTestNow();
    }
}
