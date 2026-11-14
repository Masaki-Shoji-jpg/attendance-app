<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class IndexTest extends TestCase
{
    use RefreshDataBase;

    public function test_自分が行った勤怠情報が全て表示されている(): void
    {
        $day1 = Carbon::now()->startOfMonth();
        Carbon::setTestNow($day1);

        $day2 = $day1->copy()->addDay();
        $day3 = $day2->copy()->addDays(2);

        $user = User::factory()->has(Attendance::factory()->count(3)->sequence(
            ['date' => $day1->format('Y-m-d'),],
            ['date' => $day2->format('Y-m-d'),],
            ['date' => $day3->format('Y-m-d'),],
        ))->create();

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertSeeInOrder([
            $day1->format('m/d'), '09:00', '18:00',
            $day2->format('m/d'), '09:00', '18:00',
            $day3->format('m/d'), '09:00', '18:00',
        ]);
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $user = User::factory()->create();
        $today = Carbon::now()->format('Y/m');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee($today);
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $user = User::factory()->create();

        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response = $this->get('/attendance/list?date=' . $previousMonth->format('Y-m'));
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            $previousMonth->format('m/d'),
        ]);
    }

    public function test_「翌月」を押下した時に表示月の翌月の情報が表示される(): void
    {
        $user = User::factory()->create();

        $now = Carbon::now();
        $nextMonth = $now->copy()->addMonth();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response = $this->get('/attendance/list?date=' . $nextMonth->format('Y-m'));
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            $nextMonth->format('m/d'),
        ]);
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
    {
        $day1 = Carbon::now()->startOfMonth();
        Carbon::setTestNow($day1);
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $day1->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertSee('/attendance/detail/' . $attendance->id);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertSee($day1->format('Y年'));
        $response->assertSee($day1->format('n月j日'));
    }
}
