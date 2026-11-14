<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class StaffAttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertSeeInOrder([
            $user1->name,$user1->email,
        ]);
        $response->assertSeeInOrder([
            $user2->name,$user2->email,
        ]);
    }

    public function test_ユーザーの勤怠情報が正しく表示される(): void
    {
        $day1 = Carbon::now()->startOfMonth();
        $day2 = $day1->copy()->addDay();
        $day3 = $day1->copy()->addDays(2);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->count(3)->sequence(
            ['user_id'=>$user->id,'date' => $day1->format('Y-m-d'),],
            ['user_id'=>$user->id,'date' => $day2->format('Y-m-d'),],
            ['user_id'=>$user->id,'date' => $day3->format('Y-m-d'),],
        )->create();

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id);

        $response->assertSeeInOrder([$day1->format('m/d'),'09:00',]);
        $response->assertSeeInOrder([$day2->format('m/d'),'09:00',]);
        $response->assertSeeInOrder([$day3->format('m/d'),'09:00',]);
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' =>true,]);

        $now = Carbon::now();
        $previousMonth = $now->copy()->addMonth();

        $response = $this->actingAs($admin)->get('attendance/staff/' . $user->id);

        $response = $this->get('/admin/attendance/staff/' . $user->id . '?date=' . $previousMonth->format('Y-m'));
        $response->assertStatus(200);

        $response->assertSee($previousMonth->format('Y/m'));
    }

    public function test_「翌月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' =>true,]);

        $now = Carbon::now();
        $nextMonth = $now->copy()->addMonth();

        $response = $this->actingAs($admin)->get('attendance/staff/' . $user->id);

        $response = $this->get('/admin/attendance/staff/' . $user->id . '?date=' . $nextMonth->format('Y-m'));
        $response->assertStatus(200);

        $response->assertSee($nextMonth->format('Y/m'));
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id);

        $response->assertSee('/admin/attendance/' . $attendance->id);
        $response = $this->actingAs($user)->get('/admin/attendance/' . $attendance->id);

        $response = $this->actingAs($admin)->get('/admin/attendance/' . $user->id);

        $response->assertSeeInOrder([$user->name,$attendance->date->format('m月d日'),]);
    }
}
