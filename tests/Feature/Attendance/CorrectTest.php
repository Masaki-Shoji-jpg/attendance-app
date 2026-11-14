<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
class CorrectTest extends TestCase
{
    use RefreshDataBase;

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
        ]);

        $response->assertRedirect('/attendance/detail/' . $attendance->id);
        $response->assertSessionHasErrors([
            'new_clock_out' => '出勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['20:00'],
                'new_break_out' => ['21:00'],
        ]);

        $response->assertRedirect('/attendance/detail/' . $attendance->id);
        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['11:00'],
                'new_break_out' => ['22:00'],
        ]);

        $response->assertRedirect('/attendance/detail/' . $attendance->id);
        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
        ]);

        $response->assertRedirect('/attendance/detail/' . $attendance->id);
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    public function test_修正申請処理が実行される(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '時間変更の為'
        ]);

        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '承認待ち',
            $user->name,
            '時間変更の為',
        ]);
    }

    public function test_「承認待ち」にログインユーザーが行った申請が全て表示されていること(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '時間変更の為'
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '承認待ち',
            $user->name,
            '時間変更の為',
        ]);
    }

    public function test_「承認済み」に管理者が承認した修正申請が全て表示されている(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '時間変更の為'
        ]);

        $application = AttendanceCorrection::where('attendance_id', $attendance->id)->first();
        $admin = User::factory()->create(['admin_status' =>true,]);
        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');
        $url = '/stamp_correction_request/approve/' . $application->id;

        $response = $this->from($url)->followingRedirects()->post($url);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');
        $response->assertSeeInOrder([
            '承認済',
            $user->name,
            '時間変更の為',
        ]);
    }

    public function test_各申請の「詳細」を押下すると勤怠詳細画面に遷移する(): void
    {
        $now = Carbon::now();

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response = $this->from('/attendance/detail/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '時間変更の為'
        ]);
        $application = AttendanceCorrection::where('attendance_id', $attendance->id)->first();

        $detailLink = '/attendance/detail/' . $attendance->id;
        $listResponse = $this->get('/stamp_correction_request/list');
        $listResponse->assertSee($detailLink);

        $response = $this->get($detailLink);
        $response->assertStatus(200);
    }
}
