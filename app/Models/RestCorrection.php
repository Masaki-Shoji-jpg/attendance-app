<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_id',
        'updated_rest_in',
        'updated_rest_out',
    ];

    public function attendancecorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
