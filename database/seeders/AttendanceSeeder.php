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
    public function run(): void{
        for ($i = 1; $i <= 5; $i++) {
            $targetMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $weekdaysInMonth = [];
            $daysInMonth = $targetMonth->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $targetMonth->copy()->day($day);

                if ($date->isWeekday()) {
                    $weekdaysInMonth[] = $date->format('Y-m-d');
                }
            }
            $randomKeys = array_rand($weekdaysInMonth, 15);
            $selectedDays = [];
            foreach ($randomKeys as $key) {
                $selectedDays[] = $weekdaysInMonth[$key];
            }
            foreach ($selectedDays as $selectedDate) {
                DB::table('attendances')->insert([
                    'user_id' => 1,
                    'date' => $selectedDate,
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' =>'closed',
                ]);
            }
        }

        $currentMonth = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $weekdaysInMonth = [];
        for ($day = $startOfMonth->day; $day <= $endOfMonth->day; $day++){
            $date = $currentMonth->copy()->day($day);
            if ($date->isWeekday()) {
                $weekdaysInMonth[] = $date->copy();
            }
        }
        $randomKeys = array_rand($weekdaysInMonth, 17);
        $selectedDays = [];
        foreach ($randomKeys as $key) {
            $selectedDays[] = $weekdaysInMonth[$key];
        }
        shuffle($selectedDays);
        for($day = 0 ;$day <17 ;$day++){
            $date = $selectedDays[$day]->format('Y-m-d');
            if($day<10){
                DB::table('attendances')->insert([
                    'user_id' => 1,
                    'date' => $date,
                    'clock_in' => $date . ' 09:00:00',
                    'clock_out' => $date . ' 18:00:00',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' =>'closed',
                ]);
            }elseif($day<13){
                DB::table('attendances')->insert([
                    'user_id' => 1,
                    'date' => $date,
                    'clock_in' => $date . ' 09:00:00',
                    'clock_out' => $date . ' 20:00:00',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' =>'closed',
                ]);
            }elseif($day<15){
                DB::table('attendances')->insert([
                    'user_id' => 1,
                    'date' => $date,
                    'clock_in' => $date . ' 09:30:00',
                    'clock_out' => $date . ' 18:00:00',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' =>'closed',
                ]);
            }elseif($day<16){
                DB::table('attendances')->insert([
                    'user_id' => 1,
                    'date' => $date,
                    'clock_in' => $date . ' 09:00:00',
                    'clock_out' => $date . ' 17:00:00',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' =>'closed',
                ]);
            }else{
                DB::table('attendances')->insert([
                    'user_id' => 1,
                    'date' => $date,
                    'clock_in' => $date . ' 08:00:00',
                    'clock_out' => $date . ' 21:00:00',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' =>'closed',
                ]);
            }
        }
    }
}
