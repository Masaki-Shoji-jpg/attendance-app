<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
