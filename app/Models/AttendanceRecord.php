<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;
    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
        'is_applied',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class, 'attendance_id');
    }

    public function attendance_corrections()
    {
        return $this->hasMany(AttendanceCorrection::class, 'attendance_id');
    }

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
}
