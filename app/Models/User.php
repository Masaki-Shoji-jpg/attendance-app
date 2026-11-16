<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status'      => 'boolean',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * ユーザーの現在の勤怠ステータス（勤務外・出勤中・休憩中・退勤済）を取得する
     *
     * @return string 現在の勤怠ステータス
     */
    public function getAttendanceStatusAttribute(): string
    {
        $today = Carbon::today()->format('Y-m-d');
        $attendance = $this->attendances()->where('date', $today)->first();

        if (!$attendance) {
            return '勤務外';
        }
        if ($attendance->clock_out) {
            return '退勤済';
        }
        if ($attendance->rests()->whereNull('rest_out')->exists()) {
            return '休憩中';
        }
            return '出勤中';
    }
}
