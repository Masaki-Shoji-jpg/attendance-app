<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Support\Facades\Response;


class ExportController extends Controller
{
    public function export(Request $request){
        $currentMonthStr = $request->input('year_month', Carbon::now()->format('Y-m'));

        $startOfMonth = Carbon::parse($currentMonthStr)->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonthStr)->endOfMonth();

        $attendances = Attendance::with('user','rests')->where('user_id',$request->user_id)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])->get();

        $csvContent = fopen('php://temp', 'r+b');

        fwrite($csvContent, "\xEF\xBB\xBF");

        fputcsv($csvContent, ['日付', '出勤', '退勤', '休憩', '合計']);
        foreach ($attendances as $attendance) {
            fputcsv($csvContent, [
                $attendance->date->format('Y-m-d'),
                Carbon::parse($attendance->clock_in)->format('H:i'),
                $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '未入力',
                $attendance->total_break_time,
                $attendance->total_time,
            ]);
        }

        rewind($csvContent);
        $csvData = stream_get_contents($csvContent);
        fclose($csvContent);

        $userName = $attendances->first()?->user?->name ?? 'user';
        $fileName = "勤怠一覧_{$userName}_{$currentMonthStr}.csv";
        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
