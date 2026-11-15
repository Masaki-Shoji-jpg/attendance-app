<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendanceRecordPolicy
{
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $this->isAdminOrOwner($user, $attendanceRecord);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $this->isAdminOrOwner($user, $attendanceRecord);
    }

    private function isAdminOrOwner(User $user, AttendanceRecord $attendanceRecord): bool
    {
        $isAdmin = $user->admin_status === true;

        return $isAdmin || $attendanceRecord->user_id === $user->id;
    }
}
