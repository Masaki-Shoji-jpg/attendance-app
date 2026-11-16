<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
        'is_applied',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rests(): HasMany
    {
        return $this->hasMany(Rest::class);
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    protected $casts = [
        'date' => 'date:Y-m-d',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
    ];

    /**
     * 合計休憩時間（秒単位）を計算して取得する
     *
     * @return int 合計休憩秒数
     */
    private function getBreakSeconds(): int
    {
            $breakSeconds = 0;
            foreach ($this->rests as $rest) {
                if ($rest->rest_in && $rest->rest_out) {
                $breakSeconds += Carbon::parse($rest->rest_in)->diffInSeconds(Carbon::parse($rest->rest_out));
                }
            }
        return $breakSeconds;
    }

    /**
     * 画面表示用の合計休憩時間（HH:MM形式）を取得する
     *
     * @return string 合計休憩時間
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $seconds = $this->getBreakSeconds();
        return $seconds > 0 ? gmdate('H:i', $seconds) : '00:00';
    }
    /**
     * 実働時間（HH:MM形式）を取得する
     *
     * @return string 実働時間
     */
    public function getTotalTimeAttribute(): string
    {
        if (!$this->clock_in || !$this->clock_out) {
            return '00:00';
        }
        $staySeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->getBreakSeconds();

        $workSeconds = $staySeconds - $breakSeconds;
        return $workSeconds > 0 ? gmdate('H:i', $workSeconds) : '00:00';
    }

    /**
     * マイ勤怠レポート用の休憩時間を取得する
     *
     * @return int 休憩時間
     */
    private function getBreakSecond(): int
    {
        $breakSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $breakSeconds += Carbon::parse($rest->rest_in)->diffInSeconds(Carbon::parse($rest->rest_out));
            }
        }
        return $breakSeconds;
    }

    /**
     * マイ勤怠レポート用の実働時間を取得する
     *
     * @return int 実働時間
     */
    public function getTotalWorkMinutesAttribute(): int
    {
        $staySeconds = 0;
        $staySeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->getBreakSecond();
        $workMinutes = ($staySeconds - $breakSeconds)/60;
        return (int)$workMinutes;
    }

    /**
     * マイ勤怠レポート用の残業時間（8時間超過分）を取得する
     *
     * @return int 残業時間
     */
    public function getTotalOvertimeMinutesAttribute(): int
    {
        $workMinutes = $this->total_work_minutes;
        $overtimeMinutes = max(0, $workMinutes - 480);
        return (int)$overtimeMinutes;
    }

    /**
     * 遅刻判定（9:00以降の出勤）のカウントを取得する
     *
     * @return int
     */
    public function getLateCountAttribute(): int
    {
        if (empty($this->clock_in)) {
            return 0;
        }
        $clockIn = Carbon::parse($this->clock_in)->format('H:i:s');
        return $clockIn > '09:00:00' ? 1 : 0;
    }

    /**
     * 早退判定（18:00前の退勤）のカウントを取得する
     *
     * @return int
     */
    public function getEarlyLeaveCountAttribute(): int
    {
        if (empty($this->clock_out)) {
            return 0;
        }
        $clockOut = Carbon::parse($this->clock_out)->format('H:i:s');
        return $clockOut < '18:00:00' ? 1 : 0;
    }

    /**
     * 長時間労働判定（実働10時間超過）のカウントを取得する
     *
     * @return int
     */
    public function getLongWorkCountAttribute(): int
    {
        $staySeconds = 0;
        $staySeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->getBreakSecond();
        $workMinutes = ($staySeconds - $breakSeconds)/60;
        return $workMinutes > 600 ? 1 : 0;
    }
}
