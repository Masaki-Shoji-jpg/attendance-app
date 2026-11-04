<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AdminController extends Controller
{

    public function todayList(Request $request)
    {
        $dateString = $request->input('date', Carbon::today()->toDateString());

        $date = Carbon::parse($dateString);
        $previousDay = $date->copy()->subDay();
        $nextDay = $date->copy()->addDay();

        $users = User::where('is_admin', false)->get();
        $attendanceRecords = Attendance::whereDate('date', $date)->get();

        return view('admin/admin-attendance-list',
        compact('date','previousDay','nextDay','users','attendanceRecords'));
    }

    public function staffList(){
        $users = User::where('is_admin', false)->get();
        return view('admin.staff-list',compact('users'));
    }

    public function staffIndex(Request $request,$id){
        $user = User::with(['attendances.rests'])->where('id', $id)->first();
        $currentMonthStr = $request->input('date', Carbon::now()->format('Y-m'));

        $startOfMonth = Carbon::parse($currentMonthStr)->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonthStr)->endOfMonth();

        $date = $startOfMonth;
        $previousMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

        $attendances = $user->attendances->keyBy(function($item) {
            return Carbon::parse($item->date)->format('Y-m-d');});

        $formattedAttendanceRecords=[];

        for ($day = $date->copy(); $day <= $endOfMonth; $day->addDay()) {
            $viewDate = $day->isoFormat('MM/DD(ddd)');
            $currentDateStr = $day->format('Y-m-d');

            if($attendances->has($currentDateStr)){
                $attendance = $attendances->get($currentDateStr);
                $formattedAttendanceRecords[]=[
                    'date'=> $viewDate,
                    'clock_in'=>$attendance->clock_in->format('H:i'),
                    'clock_out'=>$attendance->clock_out?->format('H:i'),
                    'total_break_time'=>$attendance->total_break_time,
                    'total_time'=>$attendance->total_time,
                    'id'=>$attendance->id
                ];
            }else{
                $formattedAttendanceRecords[]=[
                    'date'=> $viewDate,
                    'clock_in'=>null,
                    'clock_out'=>null,
                    'total_break_time'=>null,
                    'total_time'=>null,
                ];
            }
        }
        return view('admin.staff-attendance-list',
        compact('user','date','previousMonth','nextMonth','formattedAttendanceRecords'));
    }

    public function staffShow(){
        return view('admin.admin-detail');
    }
}
