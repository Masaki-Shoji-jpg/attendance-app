<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

class AdminCorrectController extends Controller
{
    /**
     * 勤怠修正申請一覧画面を表示する
     *
     * @return Response 修正申請一覧レスポンス
     */
    public function correctList(): Response
    {
         $attendances = Attendance::with('user', 'attendanceCorrections')->latest()->get();

        $sortedApplications = $attendances->flatMap(function ($attendance) {
                return $attendance->attendanceCorrections->map(function ($correction) use ($attendance) {
                    $correction->approval_status = $correction->status_label;
                    $correction->user = $attendance->user;
                    $correction->AttendanceRecord = $attendance;
                    $correction->application_date = $correction->created_at;
                    return $correction;
                });
            })->sortByDesc('created_at')->values()->all();

        return response(view('admin.admin-application-list',['applications'=> $sortedApplications]));
    }

    /**
     * 指定した勤怠修正申請の詳細画面を表示する
     *
     * @param int $attendance_correct_request_id 修正申請ID
     * @return View 修正申請詳細ビュー
     */
    public function correctShow($attendance_correct_request_id): View
    {
        $id = $attendance_correct_request_id;
        $attendanceCorrection = AttendanceCorrection::with('attendance.user','restCorrections')
        ->where('id',$id)->first();
        $user = $attendanceCorrection;
        $application = $attendanceCorrection;

        $user->name = $attendanceCorrection->attendance->user->name;
        $application->new_date = $attendanceCorrection->attendance->date;
        $application->new_clock_in = \Carbon\Carbon::parse($attendanceCorrection->updated_clock_in)->format('H:i');
        $application->new_clock_out = \Carbon\Carbon::parse($attendanceCorrection->updated_clock_out)->format('H:i');
        $application->approval_status = $attendanceCorrection->status_label;

        $application->proposalBreaks = $attendanceCorrection->restCorrections->map(function ($break) {
            $break->break_in = $break->updated_rest_in;
            $break->break_out = $break->updated_rest_out;
            return $break;
        });

        return view('admin.admin-application-detail',compact('application','user'));
    }

    /**
     * 勤怠修正申請を承認し、勤怠データおよび休憩データを更新する
     *
     * @param int $attendance_correct_request_id 修正申請ID
     * @return RedirectResponse 元の画面へのリダイレクトレスポンス
     */
    public function approve($attendance_correct_request_id): RedirectResponse
    {
        $id = $attendance_correct_request_id;
        $attendanceCorrection = AttendanceCorrection::with('attendance.user','restCorrections')
        ->where('id',$id)->first();

        DB::transaction(function() use($attendanceCorrection){
            $attendanceCorrection->update([
                'is_approved'=>true,
            ]);
            $attendanceCorrection->attendance->update([
                'clock_in'=>$attendanceCorrection->updated_clock_in,
                'clock_out'=>$attendanceCorrection->updated_clock_out,
                'is_applied'=>false,
            ]);

            $attendance = $attendanceCorrection->attendance;
            $attendance->rests()->delete();
            if ($attendanceCorrection->restCorrections->isNotEmpty()){
                foreach ($attendanceCorrection->restCorrections as $restCorrection){
                    $attendance->rests()->create([
                        'attendance_id'=>$attendance->id,
                        'rest_in'=>$restCorrection->updated_rest_in,
                        'rest_out'=>$restCorrection->updated_rest_out,
                    ]);
                }
            }
        });
        return redirect()->back();
    }
}
