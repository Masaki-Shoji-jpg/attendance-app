<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
        'status',
        'is_applied',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    protected $casts = [
        'date' => 'date:Y-m-d',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
    ];

//休憩秒数
    private function getBreakSeconds(){
            $breakSeconds = 0;
            foreach ($this->rests as $rest) {
                if ($rest->rest_in && $rest->rest_out) {
                $breakSeconds += Carbon::parse($rest->rest_in)->diffInSeconds(Carbon::parse($rest->rest_out));
                }
            }
        return $breakSeconds;
    }
//休憩時間(表示用)
    public function getTotalBreakTimeAttribute()
    {
        $seconds = $this->getBreakSeconds();
        return $seconds > 0 ? gmdate('H:i', $seconds) : '00:00';
    }
//合計時間
    public function getTotalTimeAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return '00:00';
        }
        $staySeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->getBreakSeconds();

        $workSeconds = $staySeconds - $breakSeconds;
        return $workSeconds > 0 ? gmdate('H:i', $workSeconds) : '00:00';
    }

    //report画面
    private function getBreakSecond(){
        $breakSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $breakSeconds += Carbon::parse($rest->rest_in)->diffInSeconds(Carbon::parse($rest->rest_out));
            }
        }
        return $breakSeconds;
    }

    public function getTotalWorkMinutesAttribute(){
        $staySeconds = 0;
        $staySeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->getBreakSecond();
        $workMinutes = ($staySeconds - $breakSeconds)/60;
        return (int)$workMinutes;
    }

    public function getTotalOvertimeMinutesAttribute(){
        $workMinutes = $this->total_work_minutes;
        $overtimeMinutes = max(0, $workMinutes - 480);
        return (int)$overtimeMinutes;
    }

    public function getLateCountAttribute(){
        if (empty($this->clock_in)) {
            return 0;
        }
        $clockIn = Carbon::parse($this->clock_in)->format('H:i:s');
        return $clockIn > '09:00:00' ? 1 : 0;
    }

    public function getEarlyLeaveCountAttribute(){
        if (empty($this->clock_out)) {
            return 0;
        }
        $clockOut = Carbon::parse($this->clock_out)->format('H:i:s');
        return $clockOut < '18:00:00' ? 1 : 0;
    }

    public function getLongWorkCountAttribute(){
        $staySeconds = 0;
        $staySeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->getBreakSecond();
        $workMinutes = ($staySeconds - $breakSeconds)/60;
        return $workMinutes > 600 ? 1 : 0;
    }
}
