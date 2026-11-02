<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Rest;
use App\Http\Requests\StoreAttendanceApplicationRequest;

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
                'status'   => 'working',
            ]);
            return redirect()->back();
        }
        //休憩入
        if ($action === 'break_in') {
            $attendance->update([
                'status' => 'on_break',
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
                $attendance->update(['status' => 'working',]);
            }
        return redirect()->back();
        }
        //退勤
        if ($action === 'clock_out') {
            $attendance->update(['status' => 'closed','clock_out' => $now,]);
            return redirect()->back();
        }
    }


    public function index(Request $request)
    {
        $currentMonthStr = $request->input('date', Carbon::now()->format('Y-m'));

        $startOfMonth = Carbon::parse($currentMonthStr)->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonthStr)->endOfMonth();


        $previousMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth     = $startOfMonth->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::with('rests')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()->keyBy(function ($item) {
            return \Carbon\Carbon::parse($item->date)->format('Y-m-d');
        });

        $formattedAttendanceRecords = [];
        for ($viewDate = $startOfMonth->copy(); $viewDate->lte($endOfMonth); $viewDate->addDay()) {
            $currentDateStr = $viewDate->format('Y-m-d');
            $displayDate = $viewDate->isoFormat('MM/DD(ddd)');

            if($attendances->has($currentDateStr)){
                $attendance = $attendances->get($currentDateStr);
                $formattedAttendanceRecords[] = [
                    'date' => $displayDate,
                    'clock_in' => $attendance -> clock_in->format('H:i'),
                    'clock_out' => $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : null,
                    'total_break_time' =>$attendance->total_break_time,
                    'total_time' =>$attendance->total_time,
                    'id'               => $attendance->id,
                ];
            }else{$formattedAttendanceRecords[] = [
                'date' => $displayDate,
                'clock_in' => null,
                'clock_out' => null,
                'total_break_time' =>null,
                'total_time' =>null,
            ];}
        }
        $date = $startOfMonth;
        return view('user.user-attendance-list', compact('formattedAttendanceRecords', 'previousMonth', 'nextMonth', 'date'));
    }



    public function show($id){
        $attendance = Attendance::with([
            'rests', 'user','attendanceCorrections.correctionRests',
            ])->findOrFail($id);
        $user = $attendance->user;
        $attendanceDate = \Carbon\Carbon::parse($attendance->date);
        $breaks = [];

        if($attendance->is_applied){
            $attendanceCorrection = $attendance->attendanceCorrections->first();
            foreach ($attendanceCorrection->correctionRests as $rest) {
                $breaks[] = [
                    'break_in'  => \Carbon\Carbon::parse($rest->updated_rest_in)->format('H:i'),
                    'break_out' => \Carbon\Carbon::parse($rest->updated_rest_out)->format('H:i'),
                ];
            }

            $data = [
                'id'     => $attendance->id,
                'clock_in'    => \Carbon\Carbon::parse($attendanceCorrection->updated_clock_in)->format('H:i'),
                'clock_out'   => \Carbon\Carbon::parse($attendanceCorrection->updated_clock_out)->format('H:i'),
                'year'   => $attendanceDate->format('Y年'),
                'date'   => $attendanceDate->isoFormat('M月D日'),
                'comment' =>$attendanceCorrection->comment,
                'breaks' => $breaks,
                'application' =>true,
            ];
        }else{
            foreach ($attendance->rests as $rest) {
                $breaks[] = [
                    'break_in'  => $rest->rest_in  ? \Carbon\Carbon::parse($rest->rest_in)->format('H:i')  : null,
                    'break_out' => $rest->rest_out ? \Carbon\Carbon::parse($rest->rest_out)->format('H:i') : null,
                ];
            }

            $data = [
                'id'     => $attendance->id,
                'clock_in'  => $attendance->clock_in->format('H:i'),
                'clock_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : null,
                'year'   => $attendanceDate->format('Y年'),
                'date'   => $attendanceDate->isoFormat('M月D日'),
                'comment' =>null,
                'breaks' => $breaks,
                'application' =>null,
            ];
        }

    return view('user.user-detail', compact('user','data'));
    }

    public function store(StoreAttendanceApplicationRequest $request, $id){
        $attendance = Attendance::findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $attendance) {
            $application = $attendance->attendanceCorrections()->create([
                'updated_clock_in' => $validated['new_clock_in'],
                'updated_clock_out'   => $validated['new_clock_out'],
                'comment'           => $validated['comment'],
                'is_approved'       => false,
            ]);
            if (!empty($validated['new_break_in'])){
            foreach ($validated['new_break_in'] as $index => $breakIn) {
                // 終了時間も存在するかチェック
                $breakOut = $validated['new_break_out'][$index] ?? null;

                if (!empty($breakIn) && !empty($breakOut)) {
                    $application->correctionRests()->create([
                        'updated_rest_in'  => $breakIn,
                        'updated_rest_out' => $breakOut,
                    ]);
                }
            }
        }
            $attendance->update(['is_applied' => true]);
        });
        return redirect()->back();
    }
}
