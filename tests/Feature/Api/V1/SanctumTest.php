<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Laravel\Sanctum\Sanctum;
class SanctumTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証時に書き込み系APIで401が返る(): void
    {
        $attendance = Attendance::factory()->create();

        $this->postJson('/api/v1/attendance-records', [
            'date'      => '2026-11-16',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ])->assertStatus(401);

        $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date'      => '2026-11-16',
            'clock_in'  => '09:30:00',
            'clock_out' => '18:30:00',
        ])->assertStatus(401);

        $this->deleteJson("/api/v1/attendance-records/{$attendance->id}")->assertStatus(401);
    }

    public function test_認証済みユーザーは自分の勤怠を更新・削除できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'date'      => '2026-11-16',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $updateResponse = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date'      => '2026-11-16',
            'clock_in'  => '09:30:00',
            'clock_out' => '18:30:00',
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => '09:30:00',
            'clock_out' => '18:30:00',
        ]);

        $deleteResponse = $this->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $deleteResponse->assertStatus(204);
        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    public function test_他ユーザーの勤怠を更新・削除しようとすると403が返る(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date'    => '2026-11-16',
        ]);

        $this->putJson("/api/v1/attendance-records/{$otherAttendance->id}", [
            'date'      => '2026-11-16',
            'clock_in'  => '10:00:00',
            'clock_out' => '19:00:00',
        ])->assertStatus(403);

        $this->deleteJson("/api/v1/attendance-records/{$otherAttendance->id}")->assertStatus(403);
    }
}
