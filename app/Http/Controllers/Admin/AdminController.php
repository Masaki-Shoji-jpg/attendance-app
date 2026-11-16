<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\RestCorrection;
use App\Http\Requests\Admin\AttendanceUpdateRequest;

class AdminController extends Controller
{
    /**
     * 一般ユーザーの日次勤怠一覧画面を表示する
     *
     * @param Request $request 日付指定パラメータを含むリクエスト
     * @return View 日次勤怠一覧ビュー
     */
    public function todayList(Request $request): View
    {
        $dateString = $request->input('date', Carbon::today()->toDateString());

        $date = Carbon::parse($dateString);
        $previousDay = $date->copy()->subDay();
        $nextDay = $date->copy()->addDay();

        $users = User::where('admin_status', false)->get();
        $attendanceRecords = Attendance::with(['user', 'rests'])->whereDate('date', $date)->get();

        return view('admin/admin-attendance-list',
        compact('date','previousDay','nextDay','users','attendanceRecords'));
    }

    /**
     * 一般ユーザー一覧画面を表示する
     *
     * @return View 一般ユーザー一覧ビュー
     */
    public function staffList(): View
    {
        $users = User::where('admin_status', false)->get();
        return view('admin.staff-list',compact('users'));
    }

    /**
     * 一般ユーザーの月次勤怠一覧画面を表示する
     *
     * @param Request $request 対象年月パラメータを含むリクエスト
     * @param int $id　対象ユーザーID
     * @return View 月次勤怠一覧ビュー
     */
    public function staffIndex(Request $request,$id): View
    {
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

    /**
     * 一般ユーザーの勤怠詳細画面を表示する
     *
     * @param int $id 対象勤怠ID
     * @return View 勤怠詳細ビュー
     */
    public function staffShow($id): View
    {
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

    /**
     * 一般ユーザーの勤怠を修正する
     *
     * @param AttendanceUpdateRequest $request バリデーション済みのリクエスト
     * @param int $id 対象の勤怠ID
     * @return RedirectResponse
     */
    public function update(AttendanceUpdateRequest $request,$id): RedirectResponse
    {
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
