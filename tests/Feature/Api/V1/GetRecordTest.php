<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Laravel\Sanctum\Sanctum;

class GetRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_GETで勤怠一覧がJSONで取得できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        collect(range(0, 2))->each(function ($daysAgo) use ($user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'date'    => now()->subDays($daysAgo)->format('Y-m-d'),
            ]);
        });
        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

    }

    public function test_GETで勤怠詳細がJSONで取得できる(): void
    {
        $now =Carbon::now()->today();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
        ]);
        $response = $this->getJson('/api/v1/attendance-records/' . $attendance->id);
        $response->assertStatus(200)->assertJsonPath('data.id', $attendance->id);
    }

    public function test_存在しないIDでは404とエラーJSONが返る(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
