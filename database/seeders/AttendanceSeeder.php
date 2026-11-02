<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('attendances')->insert([
            [
                'user_id' => 2,
                'date' => '2026-10-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'status' => 'closed',
                'is_applied' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 2,
                'date' => '2026-10-02',
                'clock_in' => '09:15:00',
                'clock_out' => '18:00:00',
                'status' => 'closed',
                'is_applied' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 2,
                'date' => '2026-10-03',
                'clock_in' => '09:00:00',
                'clock_out' => '19:15:00',
                'status' => 'closed',
                'is_applied' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
