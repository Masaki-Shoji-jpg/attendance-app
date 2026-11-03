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
}
