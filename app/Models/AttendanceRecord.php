<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rests(): HasMany
    {
        return $this->hasMany(Rest::class, 'attendance_id');
    }

    public function attendance_corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'attendance_id');
    }

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
     * API表示用の合計休憩時間（HH:MM形式）を取得する
     *
     * @return string 合計休憩時間
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $seconds = $this->getBreakSeconds();
        return $seconds > 0 ? gmdate('H:i', $seconds) : '00:00';
    }

    /**
     * API表示用の実働時間（HH:MM形式）を取得する
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
}
