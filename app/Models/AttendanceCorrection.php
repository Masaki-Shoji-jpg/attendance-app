<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function restCorrections(): HasMany
    {
        return $this->hasMany(RestCorrection::class);
    }

    /**
     * 承認状態を表す表示用ラベル（承認済み / 承認待ち）を取得するアクセサ
     *
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => $this->is_approved ? '承認済み' : '承認待ち');
    }
}
