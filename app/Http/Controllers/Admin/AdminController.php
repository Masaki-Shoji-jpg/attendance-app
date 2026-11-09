<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\RestCorrection;
use App\Http\Requests\StoreAttendanceApplicationRequest;

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

    public function staffShow($id){
        $attendance = Attendance::with('rests')->find($id);
        $user = $attendance->user;
        $breaks = [];
        if ($attendance->rests->isNotEmpty()) {
            foreach ($attendance->rests as $rest) {
                $breaks[] = [
                    'break_in'  => $rest->rest_in  ? \Carbon\Carbon::parse($rest->rest_in)->format('H:i')  : null,
                    'break_out' => $rest->rest_out ? \Carbon\Carbon::parse($rest->rest_out)->format('H:i') : null,
                ];
            }
        }
        $attendanceRecord=[
            'year'=>$attendance->date->format('Y年'),
            'date'=>$attendance->date->isoFormat('M月D日'),
            'clock_in'=>$attendance->clock_in->format('H:i'),
            'clock_out'=>$attendance->clock_out?->format('H:i'),
            'breaks' =>$breaks,
            'comment'=>null,
            'id'=>$id,
        ];
        return view('admin.admin-detail',compact('user','attendanceRecord'));
    }

    public function update(StoreAttendanceApplicationRequest $request,$id){
        $attendance = Attendance::with('rests')->find($id);
        $validated = $request->validated();
        $latestCorrection = AttendanceCorrection::where('attendance_id', $id)->latest()->first();


        DB::transaction(function () use ($validated, $attendance,$request,$latestCorrection){
            if($latestCorrection){
                $latestCorrection->update([
                    'is_approved'=>true,
                ]);
            }

            $attendance->rests()->delete();
            $attendance->update([
                'clock_in'=>$request->input('new_clock_in'),
                'clock_out'=>$request->input('new_clock_out'),
                'status'=>'closed',
                'is_applied'=>false,
            ]);

            $attendanceCorrection = AttendanceCorrection::create([
                'attendance_id'=> $attendance->id,
                'updated_clock_in'=> $request->input('new_clock_in'),
                'updated_clock_out'=> $request->input('new_clock_out'),
                'comment'=>$request->input('comment'),
                'is_approved'=>true,
            ]);

            $breakIns = $request->input('new_break_in', []);
            $breakOuts = $request->input('new_break_out', []);
            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;
                if (is_null($breakIn) || $breakIn === '') {
                    continue;
                }
                $attendance->rests()->create([
                    'rest_in'=> $breakIn,
                    'rest_out'=> $breakOut,
                ]);
                RestCorrection::create([
                    'attendance_correction_id' => $attendanceCorrection->id,
                    'updated_rest_in' => $breakIn,
                    'updated_rest_out' => $breakOut,
                ]);
            }
        });
        return redirect()->back();
    }

}
