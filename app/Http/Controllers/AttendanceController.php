<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Rest;

class AttendanceController extends Controller
{
    public function attendance()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $formattedDate = Carbon::today()->isoFormat('YYYY年MM月DD日(ddd)');
        $formattedTime = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

        return view('user.attendance-register',compact('user','attendance','formattedDate','formattedTime'));
    }


    public function update(Request $request){
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', Auth::id())
                            ->where('date', $today)->first();
        $action = $request->input('action');
        //出勤
        if (!$attendance) {
            Attendance::create([
                'user_id'  => Auth::id(),
                'date'     => $today,
                'clock_in' => $now,
                'status'   => '出勤中',
            ]);
            return redirect()->back();
        }
        //休憩入
        if ($action === 'break_in') {
            $attendance->update([
                'status' => '休憩中',
            ]);
            Rest::create([
                'attendance_id' => $attendance->id,
                'rest_in'       => $now,
            ]);
            return redirect()->back();
        }
        //休憩終
        if ($action === 'break_out') {
            $latestRest = Rest::where('attendance_id', $attendance->id)
                            ->whereNull('rest_out')
                            ->latest()->first();
            if ($latestRest) {
                $latestRest->update(['rest_out' => $now,]);
                $attendance->update(['status' => '出勤中',]);
            }
        return redirect()->back();
        }
        //退勤
        if ($action === 'clock_out') {
            $attendance->update(['status' => '退勤済','clock_out' => $now,]);
            return redirect()->back();
        }
    }

    public function index()
    {
        //
    }



}
