<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
class CorrectTest extends TestCase
{
    use RefreshDatabase;
    public function test_承認待ちの修正申請が全て表示されている(): void
    {
        $day1 = Carbon::now()->startOfMonth();
        $day2 = $day1->copy()->addDay();
        $day3 = $day1->copy()->addDays(2);
        $user = User::factory()->create();

        $attendance1 = Attendance::factory()->create(['user_id'=>$user->id,'date' => $day1->format('Y-m-d'),]);
        $attendance2 = Attendance::factory()->create(['user_id'=>$user->id,'date' => $day2->format('Y-m-d'),]);
        $attendance3 = Attendance::factory()->create(['user_id'=>$user->id,'date' => $day3->format('Y-m-d'),]);

        $attendanceCorrection1 = AttendanceCorrection::create([
            'attendance_id' => $attendance1->id,
            'updated_clock_in' => '09:00:00',
            'updated_clock_out' => '19:00:00',
            'comment' => 'test用1'
        ]);
        $attendanceCorrection2 = AttendanceCorrection::create([
            'attendance_id' => $attendance2->id,
            'updated_clock_in' => '09:30:00',
            'updated_clock_out' => '20:00:00',
            'comment' => 'test用2'
        ]);
        $attendanceCorrection3 = AttendanceCorrection::create([
            'attendance_id' => $attendance3->id,
            'updated_clock_in' => '09:00:00',
            'updated_clock_out' => '19:30:00',
            'comment' => 'test用3'
        ]);
        $admin = User::factory()->create(['admin_status' =>true,]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');
        $response->assertSeeInOrder(['承認待ち',$attendance1->date->format('Y/m/d'),'test用1']);
        $response->assertSeeInOrder(['承認待ち',$attendance2->date->format('Y/m/d'),'test用2']);
        $response->assertSeeInOrder(['承認待ち',$attendance3->date->format('Y/m/d'),'test用3']);
    }

    public function test_承認済みの修正申請が全て表示されている(): void
    {
        $day1 = Carbon::now()->startOfMonth();
        $day2 = $day1->copy()->addDay();
        $day3 = $day1->copy()->addDays(2);
        $user = User::factory()->create();

        $attendance1 = Attendance::factory()->create(['user_id'=>$user->id,'date' => $day1->format('Y-m-d'),]);
        $attendance2 = Attendance::factory()->create(['user_id'=>$user->id,'date' => $day2->format('Y-m-d'),]);
        $attendance3 = Attendance::factory()->create(['user_id'=>$user->id,'date' => $day3->format('Y-m-d'),]);

        $attendanceCorrection1 = AttendanceCorrection::create([
            'attendance_id' => $attendance1->id,
            'updated_clock_in' => '09:00:00',
            'updated_clock_out' => '19:00:00',
            'comment' => 'test用1',
            'is_approved' => true
        ]);
        $attendanceCorrection2 = AttendanceCorrection::create([
            'attendance_id' => $attendance2->id,
            'updated_clock_in' => '09:30:00',
            'updated_clock_out' => '20:00:00',
            'comment' => 'test用2',
            'is_approved' => true
        ]);
        $attendanceCorrection3 = AttendanceCorrection::create([
            'attendance_id' => $attendance3->id,
            'updated_clock_in' => '09:00:00',
            'updated_clock_out' => '19:30:00',
            'comment' => 'test用3',
            'is_approved' => true
        ]);
        $admin = User::factory()->create(['admin_status' =>true,]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');
        $response->assertSeeInOrder(['承認済',$attendance1->date->format('Y/m/d'),'test用1']);
        $response->assertSeeInOrder(['承認済',$attendance2->date->format('Y/m/d'),'test用2']);
        $response->assertSeeInOrder(['承認済',$attendance3->date->format('Y/m/d'),'test用3']);
    }

    public function test_修正申請の詳細内容が正しく表示されている(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id'=>$user->id]);
        $attendanceCorrection = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'updated_clock_in' => '09:00:00',
            'updated_clock_out' => '19:00:00',
            'comment' => 'test用1',
        ]);
        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $attendanceCorrection->id);

        $response->assertSee($attendanceCorrection->comment);
    }

    public function test_修正申請の承認処理が正しく行われる(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id'=>$user->id]);
        $attendanceCorrection = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'updated_clock_in' => '10:00:00',
            'updated_clock_out' => '22:00:00',
            'comment' => 'test用1',
        ]);
        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $attendanceCorrection->id);

        $response->assertDontSee('承認済み');
        $response = $this->followingRedirects()->post('/stamp_correction_request/approve/' . $attendanceCorrection->id);
        $response->assertSee('承認済み');

        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => $attendanceCorrection->updated_clock_in,
            'clock_out' => $attendanceCorrection->updated_clock_out,
        ]);
        $this->assertDatabaseHas('attendance_corrections', [
            'id'          => $attendanceCorrection->id,
            'is_approved' => true,
        ]);
    }
}
