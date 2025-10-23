<?php

namespace App\Modules\Attendance\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ParentStudentRelationship;
use App\Models\NotificationTemplate;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send attendance-related notifications.
     * Currently supports type: 'absent_alert'.
     *
     * Expected payload keys:
     * - type: string ('absent_alert')
     * - date: string (Y-m-d)
     * - class_id: int|null
     * - section: string|null (ignored for now, reserved)
     * - student_ids: array<int>
     *
     * @param array $payload
     * @return array Summary of the send operation
     */
    public function sendNotifications(array $payload): array
    {
        $type = $payload['type'] ?? 'absent_alert';

        if ($type !== 'absent_alert') {
            return [
                'type' => $type,
                'supported' => false,
                'message' => 'Unsupported notification type',
            ];
        }

        $dateStr = $payload['date'] ?? Carbon::now()->toDateString();
        $classId = $payload['class_id'] ?? null;
        $studentIds = isset($payload['student_ids']) && is_array($payload['student_ids']) ? $payload['student_ids'] : [];

        $date = Carbon::parse($dateStr)->toDateString();

        $query = Attendance::with(['student' => function ($q) {
            $q->select('id', 'name', 'class_id');
        }])
            ->whereDate('date', $date)
            ->where('status', Attendance::STATUS_ABSENT);

        if (!empty($classId)) {
            $query->where('class_id', $classId);
        }
        if (!empty($studentIds)) {
            $query->whereIn('student_id', $studentIds);
        }

        $absentRecords = $query->get();

        $template = $this->resolveAbsentAlertTemplate();

        $totalRecipients = 0;
        $totalSent = 0;
        $details = [];
        $schoolName = config('app.name');

        foreach ($absentRecords as $record) {
            /** @var Attendance $record */
            $student = $record->student;
            if (!$student instanceof Student) {
                // Fetch student if not eager loaded for some reason
                $student = Student::find($record->student_id);
                if (!$student) {
                    Log::warning('AttendanceService: Student not found for attendance record', ['attendance_id' => $record->id]);
                    continue;
                }
            }

            $variables = [
                'student_name' => $student->name ?? 'Student',
                'date' => Carbon::parse($record->date)->format('d M Y'),
                'school_name' => $schoolName,
                'status' => 'absent',
            ];

            $message = $this->renderAbsentAlertBody($template, $variables);

            // Get parent recipients allowed to receive notifications
            $recipients = ParentStudentRelationship::getNotificationRecipients($student->id);
            // Normalize into phone list
            $phones = [];
            foreach ($recipients as $recipient) {
                // Support both array and object shapes
                if (is_array($recipient)) {
                    $phone = $recipient['parent_phone'] ?? $recipient['phone'] ?? $recipient['mobile'] ?? null;
                } else {
                    $phone = $recipient->parent_phone ?? $recipient->phone ?? $recipient->mobile ?? null;
                }
                if (!empty($phone)) {
                    $phones[] = $phone;
                }
            }

            // Deduplicate and basic sanitize
            $phones = array_values(array_unique(array_filter(array_map(function ($p) {
                // Strip spaces and common separators; leave leading + for country code
                $p = trim((string)$p);
                $p = str_replace([' ', '-', '(', ')'], '', $p);
                return $p;
            }, $phones))));

            $sentForStudent = 0;
            foreach ($phones as $phone) {
                $totalRecipients++;
                try {
                    if ($this->smsService->send($phone, $message)) {
                        $totalSent++;
                        $sentForStudent++;
                    }
                } catch (\Throwable $e) {
                    Log::error('AttendanceService: Failed to send absent alert SMS', [
                        'student_id' => $student->id,
                        'phone' => $phone,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $details[] = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'class_id' => $student->class_id,
                'recipient_phones' => $phones,
                'sms_sent_count' => $sentForStudent,
            ];
        }

        $summary = [
            'type' => $type,
            'date' => $date,
            'class_id' => $classId,
            'absent_count' => $absentRecords->count(),
            'sms_attempted' => $totalRecipients,
            'sms_sent' => $totalSent,
            'details' => $details,
        ];

        Log::info('AttendanceService: Absent alerts summary', $summary);
        return $summary;
    }

    protected function resolveAbsentAlertTemplate(): ?NotificationTemplate
    {
        // Try common slugs first
        $commonSlugs = [
            'attendance.absent_alert',
            'attendance_absent_alert',
            'attendance-absent-alert',
            'absent_alert',
            'absent-notification',
        ];

        foreach ($commonSlugs as $slug) {
            $tpl = NotificationTemplate::findBySlug($slug);
            if ($tpl && $tpl->is_active && $tpl->type === 'sms') {
                return $tpl;
            }
        }

        // Fallback: any active SMS template mentioning absent
        $fallback = NotificationTemplate::active()
            ->byType('sms')
            ->where(function ($q) {
                $q->where('slug', 'like', '%absent%')
                  ->orWhere('name', 'like', '%absent%');
            })
            ->first();

        return $fallback ?: null;
    }

    protected function renderAbsentAlertBody(?NotificationTemplate $template, array $variables): string
    {
        if ($template) {
            $rendered = $template->render($variables);
            if (is_array($rendered) && isset($rendered['body'])) {
                return (string)$rendered['body'];
            }
            if (!empty($template->body)) {
                // Best-effort variable replacement if render did not return array
                $body = $template->body;
                foreach ($variables as $key => $value) {
                    $body = str_replace('{{' . $key . '}}', (string)$value, $body);
                }
                return $body;
            }
        }

        // Default message if no template found
        $studentName = $variables['student_name'] ?? 'Student';
        $date = $variables['date'] ?? Carbon::now()->format('d M Y');
        $schoolName = $variables['school_name'] ?? config('app.name');
        return sprintf('Alert: %s is marked absent on %s. - %s', $studentName, $date, $schoolName);
    }
}