<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rest extends Model
{
    use HasFactory;
    protected $fillable = [
        'rest_in',
        'rest_out',
        'attendance_id',
    ];

    public function attendances(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
