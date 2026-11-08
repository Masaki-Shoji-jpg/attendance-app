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
                $appObject = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);

            $appObject['approval_status']  = $attendanceCorrection->status_label;
            $appObject['comment']          = $attendanceCorrection->comment;
            $appObject['application_date'] = $attendanceCorrection->created_at;
            $appObject['id']               = $attendance->id;

            $userObj = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
            $userObj['name'] = $attendance->user->name ?? '不明';
            $appObject['user'] = $userObj;

            $recordObj = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
            $recordObj['date'] = $attendance->date;
            $appObject['AttendanceRecord'] = $recordObj;

            $applications[] = $appObject;
            }
        }
        return response(view('admin.admin-application-list',compact('applications')));
    }

    public function correctShow(){
        return view('admin.admin-application-detail');
    }
}
