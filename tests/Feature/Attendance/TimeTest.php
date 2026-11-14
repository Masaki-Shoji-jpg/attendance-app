<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class TimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報がUIと同じ形式で出力されている(): void
    {
        $now = Carbon::create(2026, 11, 14, 10, 40, 0);
        Carbon::setTestNow($now);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee($now->format('H:i'));
        $response->assertSee($now->format('Y年n月j日'));

        Carbon::setTestNow();
    }
}
