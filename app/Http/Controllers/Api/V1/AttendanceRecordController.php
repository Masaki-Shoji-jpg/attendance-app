<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use App\Models\AttendanceRecord;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
class AttendanceRecordController extends Controller
{
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $attendanceRecords = AttendanceRecord::with(['user', 'rests'])
        ->when(!empty($validated['user_id']), function ($query) use ($validated) {
            $query->where('user_id', $validated['user_id']);
        })
        ->when(!empty($validated['date']), function ($query) use ($validated) {
            $query->whereDate('date', $validated['date']);
        })
        ->when(!empty($validated['month']), function ($query) use ($validated) {
            $query->where('date', 'like', "{$validated['month']}%");
        })
        ->orderBy('date', 'desc')
        ->paginate(
            perPage: $validated['per_page'],
            page: $validated['page']
        );

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    public function store(StoreAttendanceRecordRequest $request): AttendanceRecordResource
    {
        $data = Arr::except($request->validated(), ['comment']);
        $attendanceRecord = $request->user()->attendanceRecords()->create($data);

        $attendanceRecord->load(['user', 'rests']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'rests','attendance_corrections']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $validated = Arr::except($request->validated(), ['comment']);
        $attendanceRecord->update($validated);

        $attendanceRecord->load(['user', 'rests', 'attendance_corrections']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);
        $attendanceRecord->delete();
        return response()->noContent();
    }
}
