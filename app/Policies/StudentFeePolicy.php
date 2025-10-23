<?php

namespace App\Policies;

use App\Models\StudentFee;
use App\Models\ParentStudentRelationship;
use App\Models\User;

class StudentFeePolicy
{
    /**
     * Determine whether the user can view a student fee record.
     */
    public function view(User $user, StudentFee $studentFee): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('principal')) {
            return true;
        }

        // Allow parent to view if they are linked to the student
        if ($user->hasRole('parent') && $studentFee->student && $studentFee->student->user_id) {
            return ParentStudentRelationship::hasParentAccess($user->id, $studentFee->student->user_id, 'academic');
        }

        // Allow the student to view their own fees
        if ($user->hasRole('student') && $studentFee->student && $studentFee->student->user_id) {
            return $user->id === $studentFee->student->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can initiate payment for a student fee record.
     */
    public function pay(User $user, StudentFee $studentFee): bool
    {
        // Basic view access plus fee not fully paid
        if (!$this->view($user, $studentFee)) {
            return false;
        }

        return $studentFee->status !== 'paid';
    }
}