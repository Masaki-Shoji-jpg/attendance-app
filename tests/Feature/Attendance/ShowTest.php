<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class ShowTest extends TestCase
{
    use RefreshDataBase;

    public function test_勤怠詳細画面の「名前」がログインユーザーの氏名になっている(): void
    {
        $now = Carbon::now();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee($user->name);
    }

    public function test_勤怠詳細画面の「日付」が選択した日付になっている(): void
    {
        $now = Carbon::now();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee($attendance->date->format('n月j日'));
    }

    public function test_「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee($attendance->clock_in->format('H:i'));
        $response->assertSee($attendance->clock_out->format('H:i'));
    }

    public function test_「休憩」にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee(Carbon::parse($rest->rest_in)->format('H:i'));
        $response->assertSee(Carbon::parse($rest->rest_out)->format('H:i'));
    }
}
