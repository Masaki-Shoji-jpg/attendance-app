<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_id',
        'updated_rest_in',
        'updated_rest_out',
    ];

    public function attendancecorrection(): BelongsTo
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
