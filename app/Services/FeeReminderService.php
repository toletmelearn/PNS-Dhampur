<?php

namespace App\Services;

use App\Models\StudentFee;
use App\Models\ParentStudentRelationship;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class FeeReminderService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send reminders for fees due within the next N days.
     */
    public function sendUpcomingDueReminders(int $daysAhead = 3): int
    {
        $count = 0;
        $now = now();
        $until = now()->addDays($daysAhead);

        $fees = StudentFee::with(['student.user'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '>=', $now->toDateString())
            ->whereDate('due_date', '<=', $until->toDateString())
            ->get();

        foreach ($fees as $fee) {
            $count += $this->notifyRecipients($fee, false) ? 1 : 0;
        }

        Log::info('FeeReminderService: Upcoming due reminders processed', [
            'days_ahead' => $daysAhead,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Send reminders for overdue fees.
     */
    public function sendOverdueReminders(int $minDaysOverdue = 1): int
    {
        $count = 0;
        $threshold = now()->subDays($minDaysOverdue);

        $fees = StudentFee::with(['student.user'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $threshold->toDateString())
            ->get();

        foreach ($fees as $fee) {
            $count += $this->notifyRecipients($fee, true) ? 1 : 0;
        }

        Log::info('FeeReminderService: Overdue reminders processed', [
            'min_days_overdue' => $minDaysOverdue,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Notify parents and student via SMS about fee status.
     */
    protected function notifyRecipients(StudentFee $fee, bool $isOverdue): bool
    {
        $student = $fee->student;
        $user = $student?->user;
        $studentName = $student?->name ?? $user?->name ?? 'Student';
        $dueDate = $fee->due_date?->format('Y-m-d');
        $itemName = $fee->item_name ?? 'Fee';
        $dueAmount = max(0, (float)($fee->amount + ($fee->late_fee ?? 0) - ($fee->paid_amount ?? 0) - ($fee->discount ?? 0)));

        $message = $isOverdue
            ? "Overdue: {$itemName} for {$studentName}. Due {$dueDate}. Amount: Rs {$dueAmount}. Please pay immediately."
            : "Reminder: {$itemName} for {$studentName} due {$dueDate}. Amount: Rs {$dueAmount}.";

        // Collect parent recipients from relationships using student's user_id
        $recipients = [];
        $studentUserId = $user?->id;
        if ($studentUserId) {
            $parents = ParentStudentRelationship::getNotificationRecipients($studentUserId);
            foreach ($parents as $parentInfo) {
                if (!empty($parentInfo['parent_phone'])) {
                    $recipients[] = $parentInfo['parent_phone'];
                }
            }
        }

        // Also notify student if phone is available
        if (!empty($user?->phone)) {
            $recipients[] = $user->phone;
        }

        // Deduplicate recipients
        $recipients = array_values(array_unique(array_filter($recipients)));

        if (empty($recipients)) {
            return false;
        }

        $this->smsService->sendBulk($recipients, $message);
        return true;
    }
}