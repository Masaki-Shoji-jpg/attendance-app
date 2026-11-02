<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'updated_clock_in',
        'updated_clock_out',
        'attendance_id',
        'comment',
        'is_approved',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function correctionRests()
    {
        return $this->hasMany(CorrectionRest::class);
    }

    //修正申請ステータス判定用
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => $this->is_approve ? '承認済み' : '承認待ち');
    }
}
