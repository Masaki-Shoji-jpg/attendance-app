<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\RestCorrection;
use App\Http\Requests\StoreAttendanceApplicationRequest;

class AdminCorrectController extends Controller
{
    public function correctList(){
        $attendances = Attendance::with('user','attendanceCorrections')->latest()->get();
        $applications = [];

        foreach ($attendances as $attendance) {
            foreach ($attendance->attendanceCorrections as $attendanceCorrection) {
                $application = $attendanceCorrection;
                $application->approval_status = $attendanceCorrection->status_label;
                $application->user = $attendance->user;
                $application->AttendanceRecord = $attendance;
                $application->id = $attendanceCorrection->id;
                $application->application_date = $attendanceCorrection->created_at;

                $applications[] = $application;
            }
        }
        $sortedApplications = collect($applications)->sortByDesc('created_at')->values()->all();
        return response(view('admin.admin-application-list',['applications'=> $sortedApplications]));
    }

    public function correctShow($id){
        $attendanceCorrection = AttendanceCorrection::with('attendance.user','restCorrections')
        ->where('id',$id)->first();
        $user = $attendanceCorrection;
        $application = $attendanceCorrection;

        $user->name = $attendanceCorrection->attendance->user->name;
        $application->new_date = $attendanceCorrection->attendance->date;
        $application->new_clock_in = \Carbon\Carbon::parse($attendanceCorrection->updated_clock_in)->format('H:i');
        $application->new_clock_out = \Carbon\Carbon::parse($attendanceCorrection->updated_clock_out)->format('H:i');
        $application->approval_status = $attendanceCorrection->status_label;

        $proposalBreaks = $attendanceCorrection->restCorrections;
        foreach ($proposalBreaks as $break) {
            $break->break_in = $break->updated_rest_in;
            $break->break_out = $break->updated_rest_out;
        }
        $application->proposalBreaks = $proposalBreaks;

        return view('admin.admin-application-detail',compact('application','user'));
    }

    public function approve($id){
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
