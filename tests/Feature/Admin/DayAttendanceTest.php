<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
class DayAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $user1 = User::factory()
            ->has(Attendance::factory()->state([
                'clock_in' => '09:00:00',
                'clock_out' => null,
        ]))->create();
        $user2 = User::factory()
            ->has(Attendance::factory()->state([
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
        ]))->create();

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertSeeInOrder([
            $user1->name,'09:00',
        ]);
        $response->assertSeeInOrder([
            $user2->name,'09:00','18:00',
        ]);
    }

    public function test_遷移した際に現在の日付が表示される(): void
    {
        $today = Carbon::now()->format('Y/m/d');

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertSee($today);
    }

    public function test_「前日」を押下した時に前の日の勤怠情報が表示される(): void
    {
        $today = Carbon::now();
        $previousDay = $today->copy()->subDay();

        $user = User::factory()
            ->has(Attendance::factory()->state([
                'date' => $previousDay,
                'clock_in' => '09:00:00',
                'clock_out' => '18:30:00',
        ]))->create();

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response = $this->get('/admin/attendance/list?date=' . $previousDay->format('Y-m-d'));
        $response->assertStatus(200);

        $response->assertSee($previousDay->format('Y年m月d日'));
        $response->assertSeeInOrder([
            $user->name,'09:00','18:30',
        ]);
    }

    public function test_「翌日」を押下した時に次の日の勤怠情報が表示される(): void
    {
        $today = Carbon::now();
        $nextDay = $today->copy()->subDay();

        $user = User::factory()
            ->has(Attendance::factory()->state([
                'date' => $nextDay,
                'clock_in' => '09:00:00',
                'clock_out' => null,
        ]))->create();

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response = $this->get('/admin/attendance/list?date=' . $nextDay->format('Y-m-d'));
        $response->assertStatus(200);

        $response->assertSee($nextDay->format('Y年m月d日'));
        $response->assertSeeInOrder([
            $user->name,'09:00',
        ]);
    }
}
