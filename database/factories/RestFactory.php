<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;

class RestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'rest_in' => '12:00:00',
            'rest_out' => '13:00:00',
        ];
    }
}

//$user = User::factory()->create();
//$task = Task::factory()->create(['user_id' => $user->id]);
