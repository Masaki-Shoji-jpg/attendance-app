<?php

namespace App\Http\Controllers\Attendance;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Rest;
use App\Http\Requests\StoreAttendanceApplicationRequest;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻画面を表示する
     *
     * @return View 勤怠打刻ビュー
     */
    public function attendance(): View
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

    /**
     * 出勤・休憩開始・休憩終了・退勤の打刻処理を登録・更新する
     *
     * @param Request $request 打刻アクション種別を含むリクエスト
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', Auth::id())
                            ->where('date', $today)->first();
        $action = $request->input('action');

        if (!$attendance) {
            Attendance::create([
                'user_id'  => Auth::id(),
                'date'     => $today,
                'clock_in' => $now,
            ]);
            return redirect()->back();
        }

        if ($action === 'break_in') {
            Rest::create([
                'attendance_id' => $attendance->id,
                'rest_in'       => $now,
            ]);
            return redirect()->back();
        }

        if ($action === 'break_out') {
            $latestRest = Rest::where('attendance_id', $attendance->id)
                            ->whereNull('rest_out')
                            ->latest()->first();
            if ($latestRest) {
                $latestRest->update(['rest_out' => $now,]);
            }
        return redirect()->back();
        }

        if ($action === 'clock_out') {
            $attendance->update(['clock_out' => $now,]);
            return redirect()->back();
        }
    }

    /**
     * 月次勤怠一覧画面を表示する
     *
     * @param Request $request 対象年月を含むリクエスト
     * @return View 月次勤怠一覧ビュー
     */
    public function index(Request $request): View
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

    /**
     * 指定した勤怠データの詳細画面を表示する
     *
     * @param int $id 勤怠ID
     * @return View 勤怠詳細ビュー
     */
    public function show($id): View
    {
        $attendance = Attendance::with([
            'rests', 'user','attendanceCorrections.restCorrections',
            ])->findOrFail($id);
        $user = $attendance->user;
        $attendanceDate = \Carbon\Carbon::parse($attendance->date);
        $breaks = [];

        if($attendance->is_applied){
            $attendanceCorrection = $attendance->attendanceCorrections->first();
            foreach ($attendanceCorrection->restCorrections as $rest) {
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

    /**
     * 勤怠修正申請を登録する
     *
     * @param StoreAttendanceApplicationRequest $request
     * @param int $id 対象の勤怠ID
     * @return RedirectResponse
     */
    public function store(StoreAttendanceApplicationRequest $request, $id): RedirectResponse
    {
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
                $breakOut = $validated['new_break_out'][$index] ?? null;
                if (!empty($breakIn) && !empty($breakOut)) {
                    $application->restCorrections()->create([
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

    /**
     * 自身の勤怠修正申請一覧画面を表示する
     *
     * @return Response
     */
    public function correctList(): Response
    {
        $user = User::with(['attendances.attendancecorrections'])->find(Auth::id());
        $formattedApplications=[];

        foreach ($user->attendances as $attendance) {
            foreach ($attendance->attendanceCorrections as $attendanceCorrection) {
                $formattedApplications[] =[
                    'approval_status' =>$attendanceCorrection->status_label,
                    'date' =>$attendance->date->isoformat('YYYY/MM/DD'),
                    'comment' =>$attendanceCorrection->comment,
                    'application_date'=>$attendanceCorrection->created_at->isoformat('YYYY/MM/DD'),
                    'id' => $attendance->id,
                    'created_at' => $attendanceCorrection->created_at,
                ];
            }
        }
        $sortedApplications = collect($formattedApplications)->sortByDesc('created_at')->values()->all();
        $formattedApplications = $sortedApplications;
        return response(view('user.user-application-list',compact('user','formattedApplications')));
    }

     /**
     * マイ勤怠レポート画面を表示する
     *
     * @return \Illuminate\View\View マイ勤怠レポートビュー
     */
    public function report(): View
    {
        $firstMonthDay = Carbon::now()->subMonths(5)->startOfMonth();
        $lastDay = Carbon::now()->endOfMonth();

        $attendances = Attendance::with('user', 'rests')
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$firstMonthDay, $lastDay])
            ->get();

        $dayCount = $attendances->count();
        $totalWorkMinutes = $attendances->sum('total_work_minutes');

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $attendances->sum('total_overtime_minutes'),
            'avg_work_minutes' => $dayCount > 0 ? (int)($totalWorkMinutes / $dayCount) : 0,
        ];

        $groupedByMonth = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('Y-m');
        });

        $monthlyTrend = collect(range(0, 5))->map(function ($monthsAgo) use ($groupedByMonth) {
            $month = Carbon::now()->subMonths($monthsAgo)->format('Y-m');
            $monthRecords = $groupedByMonth->get($month, collect());

            return [
                'month' => $month,
                'work_minutes' => $monthRecords->sum('total_work_minutes'),
                'overtime_minutes' => $monthRecords->sum('total_overtime_minutes'),
            ];
        })->all();
        $thisMonth = Carbon::now()->format('Y-m');
        $currentMonthAttendances = $groupedByMonth->get($thisMonth, collect());

        $anomalies = [
            'late_count' => $currentMonthAttendances->sum('late_count'),
            'early_leave_count' => $currentMonthAttendances->sum('early_leave_count'),
            'long_work_count' => $currentMonthAttendances->sum('long_work_count'),
        ];

        return view('reports.index', compact('summary', 'monthlyTrend', 'anomalies'));
    }


}
