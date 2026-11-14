<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class StaffAttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $today = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertSee($today->format('Y年'));
        $response->assertSee($today->format('m月d日'));
        $response->assertSeeInOrder([
            $user->name,'09:00','18:30',
        ]);
    }

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response = $this->post('/admin/attendance/' . $attendance->id, [
            'new_clock_in' => '20:00',
            'new_clock_out' => '18:00',
        ]);

        $response->assertRedirect('/admin/attendance/' . $attendance->id);
        $response->assertSessionHasErrors([
            'new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response = $this->post('/admin/attendance/' . $attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['19:00'],
            'new_break_out' => ['20:00'],
        ]);

        $response->assertRedirect('/admin/attendance/' . $attendance->id);
        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response = $this->post('/admin/attendance/' . $attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['20:00'],
        ]);

        $response->assertRedirect('/admin/attendance/' . $attendance->id);
        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response = $this->post('/admin/attendance/' . $attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => null
        ]);

        $response->assertRedirect('/admin/attendance/' . $attendance->id);
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }
}
