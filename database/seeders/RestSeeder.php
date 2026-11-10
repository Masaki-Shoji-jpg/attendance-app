<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class RestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = DB::table('attendances')->where('user_id', 1)->get();
        foreach ($attendances as $attendance){
            DB::table('rests')->insert([
                'attendance_id' => $attendance->id,
                'rest_in' => '12:00:00',
                'rest_out' => '13:00:00',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
