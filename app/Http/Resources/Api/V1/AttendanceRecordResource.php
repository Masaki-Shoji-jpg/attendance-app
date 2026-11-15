<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
        public function toArray(Request $request): array
    {
        if ($request->routeIs('*.show')) {
            return [
                'id' => $this->id,
                'user' => [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ],
                'date' => $this->date?->format('Y-m-d'),
                'clock_in' => $this->clock_in,
                'clock_out' => $this->clock_out,
                'breaks' => $this->rests->map(function ($rest) {
                    return [
                        'id' => $rest->id,
                        'break_in'  => $rest->rest_in,
                        'break_out' => $rest->rest_out,
                    ];
                }),
                'applications' => $this->attendance_corrections->isNotEmpty() ? $this->attendance_corrections->map(function ($attendance_corrections) {
                    return [
                        'id' => $attendance_corrections->id,
                        'comment' => $attendance_corrections->comment,
                    ];}): [],
            ];
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'date' => $this->date?->format('Y-m-d'),
            'clock_in' => $this->clock_in ? substr($this->clock_in, 0, 5) : null,
            'clock_out' => $this->clock_out ? substr($this->clock_out, 0, 5) : null,
            'total_time' => $this->total_time,
            'total_break_time' => $this->total_break_time,
            'comment' => $request->input('comment'),
        ];
    }
}
