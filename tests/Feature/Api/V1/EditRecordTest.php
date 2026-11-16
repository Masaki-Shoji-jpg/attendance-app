<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class EditRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_POSTで勤怠が作成される(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $today = Carbon::now()->today()->format('Y-m-d');

        $payload = [
            'date'      => $today,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト出勤',
        ];

        $response = $this->postJson('/api/v1/attendance-records', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    public function test_バリデーションエラー時に422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/attendance-records', [])
            ->assertStatus(422)->assertJsonValidationErrors([
                'date'     => '勤怠日は必須です。',
                'clock_in' => '出勤時刻は必須です。',
            ]);

        $this->postJson('/api/v1/attendance-records', [
            'date'      => '2026/11/16',
            'clock_in'  => '09:00',
            'clock_out' => '08:00',
            'comment'   => str_repeat('あ', 256),
        ])
            ->assertStatus(422)->assertJsonValidationErrors([
                'date'      => '勤怠日は YYYY-MM-DD 形式で指定してください。',
                'clock_in'  => '出勤時刻は HH:MM:SS 形式で指定してください。',
                'clock_out' => '退勤時刻は HH:MM:SS 形式で指定してください。',
                'comment'   => '備考は255文字以内で入力してください。',
            ]);

        $this->postJson('/api/v1/attendance-records', [
            'date'      => '2026-11-16',
            'clock_in'  => '18:00:00',
            'clock_out' => '09:00:00',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'clock_out' => '退勤時刻は出勤時刻より後の時刻を指定してください。',
            ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2026-11-16',
        ]);

        $this->postJson('/api/v1/attendance-records', [
            'date'     => '2026-11-16',
            'clock_in' => '09:00:00',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date' => 'この日付の勤怠は既に登録されています。',
            ]);
    }

    public function test_PUTで勤怠が更新される(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-11-16',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date'      => '2026-11-16',
            'clock_in'  => '09:30:00',
            'clock_out' => '18:30:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => '09:30:00',
            'clock_out' => '18:30:00',
        ]);
    }

    public function test_DELETEで勤怠が削除される(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2026-11-16',
        ]);

        $response = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }
}
