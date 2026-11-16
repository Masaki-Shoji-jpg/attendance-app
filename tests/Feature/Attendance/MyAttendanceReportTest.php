<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Rest;
use Carbon\Carbon;

class MyAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ゲストはレポートページにアクセスできない(): void
    {
        $response = $this->get('/attendance/report');
        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報が正しく計算される(): void
    {
        $user = User::factory()->create();

        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $records = [
            //実働600分, 残業120分, 遅刻
            ['date' => $thisMonth->copy()->day(1), 'clock_in' => '09:30:00', 'clock_out' => '20:30:00'],
            //実働420分, 残業0分, 早退
            ['date' => $thisMonth->copy()->day(2), 'clock_in' => '09:00:00', 'clock_out' => '17:00:00'],
            //実働630分, 残業150分, 長時間労働
            ['date' => $thisMonth->copy()->day(3), 'clock_in' => '08:00:00', 'clock_out' => '19:30:00'],
            //実働480分, 残業0分
            ['date' => $lastMonth->copy()->day(1), 'clock_in' => '09:00:00', 'clock_out' => '18:00:00'],
        ];
        collect($records)->each(function ($data) use ($user) {
            $attendance = Attendance::factory()->create([
                'user_id'   => $user->id,
                'date'      => $data['date']->format('Y-m-d'),
                'clock_in'  => $data['clock_in'],
                'clock_out' => $data['clock_out'],
            ]);
            Rest::factory()->create([
                'attendance_id' => $attendance->id,
            ]);
        });
        $response = $this->actingAs($user)->get('/attendance/report');
        $response->assertStatus(200);

        $response->assertSee('マイ勤怠レポート');

        //総労働時間総残業時間平均労働時間 / 日
        $response->assertViewHas('summary', [
            'total_work_minutes' => 2130,
            'total_overtime_minutes' => 270,
            'avg_work_minutes' => 532,
        ]);

        //月次推移
        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) use ($thisMonth, $lastMonth) {
            $trendCollection = collect($monthlyTrend);

            $thisMonthData = $trendCollection->firstWhere('month', $thisMonth->format('Y-m'));
            $lastMonthData = $trendCollection->firstWhere('month', $lastMonth->format('Y-m'));

            return $trendCollection->count() === 6
                && $thisMonthData['work_minutes'] === 1650
                && $thisMonthData['overtime_minutes'] === 270
                && $lastMonthData['work_minutes'] === 480
                && $lastMonthData['overtime_minutes'] === 0;
        });

        //遅刻回数早退回数長時間労働日数
        $response->assertViewHas('anomalies', [
            'late_count'        => 1,
            'early_leave_count' => 1,
            'long_work_count'   => 1,
        ]);
    }

    public function test_勤怠記録がないユーザーで安全に処理される(): void
    {
        $user = User::factory()->create();
        $thisMonth = Carbon::now()->startOfMonth()->format('Y-m');

        $response = $this->actingAs($user)->get('/attendance/report');
        $response->assertStatus(200);

        $response->assertSeeInOrder(['総労働時間','0h 0m','総残業時間','0h 0m','平均労働時間',
        '0h 0m','月次推移']);
        $response->assertSeeInOrder([$thisMonth,'0h 0m','0h 0m',]);
        $response->assertSeeInOrder(['0 回','0 回','0 日']);
    }
}
