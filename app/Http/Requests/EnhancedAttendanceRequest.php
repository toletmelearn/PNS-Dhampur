<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EnhancedAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal', 'teacher']) ||
            auth()->user()->can('manage-attendance')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $attendanceId = $this->route('attendance') ? $this->route('attendance')->id : null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $isBulkOperation = $this->has('students') || $this->has('bulk_data');
        
        if ($isBulkOperation) {
            return $this->getBulkValidationRules();
        }
        
        return [
            // Student Information
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
                function ($attribute, $value, $fail) {
                    $student = \App\Models\Student::find($value);
                    if ($student && $student->status !== 'active') {
                        $fail('Attendance can only be marked for active students.');
                    }
                }
            ],
            
            // Date and Time Information
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . Carbon::now()->subYear()->format('Y-m-d'),
                function ($attribute, $value, $fail) {
                    $date = Carbon::parse($value);
                    
                    // Check if it's a weekend (Saturday/Sunday)
                    if ($date->isWeekend()) {
                        $fail('Attendance cannot be marked for weekends.');
                    }
                    
                    // Check if it's a holiday (you can implement holiday checking logic)
                    if ($this->isHoliday($date)) {
                        $fail('Attendance cannot be marked for holidays.');
                    }
                    
                    // Check if date is too far in the past (more than 30 days)
                    if ($date->diffInDays(Carbon::now()) > 30) {
                        $fail('Attendance cannot be marked for dates more than 30 days ago.');
                    }
                }
            ],
            'time_in' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $timeIn = Carbon::createFromFormat('H:i', $value);
                        $schoolStartTime = Carbon::createFromFormat('H:i', '07:00');
                        $schoolEndTime = Carbon::createFromFormat('H:i', '18:00');
                        
                        if ($timeIn->lt($schoolStartTime) || $timeIn->gt($schoolEndTime)) {
                            $fail('Time in must be within school hours (07:00 - 18:00).');
                        }
                    }
                }
            ],
            'time_out' => [
                'nullable',
                'date_format:H:i',
                'after:time_in',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $timeOut = Carbon::createFromFormat('H:i', $value);
                        $schoolStartTime = Carbon::createFromFormat('H:i', '07:00');
                        $schoolEndTime = Carbon::createFromFormat('H:i', '18:00');
                        
                        if ($timeOut->lt($schoolStartTime) || $timeOut->gt($schoolEndTime)) {
                            $fail('Time out must be within school hours (07:00 - 18:00).');
                        }
                    }
                }
            ],
            
            // Attendance Status
            'status' => [
                'required',
                'string',
                'in:present,absent,late,half_day,excused,medical_leave,sick_leave,authorized_absence',
            ],
            'attendance_type' => [
                'nullable',
                'string',
                'in:regular,makeup,extra_class,event,examination',
            ],
            
            // Session Information
            'session' => [
                'nullable',
                'string',
                'in:morning,afternoon,full_day,first_half,second_half',
            ],
            'period' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
            'subject_id' => [
                'nullable',
                'integer',
                'exists:subjects,id',
            ],
            
            // Class Information
            'class_id' => [
                'required',
                'integer',
                'exists:class_models,id',
                function ($attribute, $value, $fail) {
                    $studentId = $this->input('student_id');
                    if ($studentId && $value) {
                        $student = \App\Models\Student::find($studentId);
                        if ($student && $student->class_id != $value) {
                            $fail('The selected class does not match the student\'s current class.');
                        }
                    }
                }
            ],
            'section' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z]$/',
            ],
            
            // Teacher Information
            'marked_by' => [
                'nullable',
                'integer',
                'exists:teachers,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $teacher = \App\Models\Teacher::find($value);
                        if ($teacher && !$teacher->is_active) {
                            $fail('Attendance can only be marked by active teachers.');
                        }
                    }
                }
            ],
            
            // Academic Information
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                function ($attribute, $value, $fail) {
                    $years = explode('-', $value);
                    if (count($years) !== 2 || (int)$years[1] !== (int)$years[0] + 1) {
                        $fail('The ' . $attribute . ' must be in format YYYY-YYYY (consecutive years).');
                    }
                    
                    $currentYear = date('Y');
                    if ((int)$years[0] < $currentYear - 2 || (int)$years[0] > $currentYear + 1) {
                        $fail('The ' . $attribute . ' must be within reasonable range.');
                    }
                }
            ],
            'term' => [
                'nullable',
                'string',
                'in:annual,semester1,semester2,quarter1,quarter2,quarter3,quarter4,monthly',
            ],
            'month' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            
            // Additional Information
            'remarks' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    if ($value && preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'reason' => [
                'nullable',
                'string',
                'max:200',
                'required_if:status,absent,excused,medical_leave,sick_leave,authorized_absence',
                function ($attribute, $value, $fail) {
                    if ($value && preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'parent_notified' => 'nullable|boolean',
            'notification_sent' => 'nullable|boolean',
            
            // Biometric Information
            'biometric_id' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9\-_]+$/',
            ],
            'device_id' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9\-_]+$/',
            ],
            'verification_method' => [
                'nullable',
                'string',
                'in:manual,biometric,rfid,qr_code,mobile_app',
            ],
            
            // Location Information
            'location' => [
                'nullable',
                'string',
                'max:100',
                'in:classroom,library,laboratory,playground,auditorium,cafeteria,other',
            ],
            'gps_latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'gps_longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            
            // Weather and Environmental
            'temperature' => [
                'nullable',
                'numeric',
                'between:-10,50',
            ],
            'weather_condition' => [
                'nullable',
                'string',
                'in:sunny,cloudy,rainy,stormy,foggy,snowy',
            ],
            
            // Medical Information
            'health_status' => [
                'nullable',
                'string',
                'in:healthy,sick,injured,quarantine,recovered',
            ],
            'temperature_check' => [
                'nullable',
                'numeric',
                'between:95,110',
            ],
            'symptoms' => [
                'nullable',
                'string',
                'max:300',
            ],
            
            // Transport Information
            'transport_used' => 'nullable|boolean',
            'bus_route' => [
                'nullable',
                'string',
                'max:50',
            ],
            'pickup_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'drop_time' => [
                'nullable',
                'date_format:H:i',
            ],
        ];
    }

    /**
     * Get validation rules for bulk operations.
     */
    private function getBulkValidationRules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . Carbon::now()->subDays(30)->format('Y-m-d'),
            ],
            'class_id' => 'required|integer|exists:class_models,id',
            'section' => 'nullable|string|max:10',
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'marked_by' => 'nullable|integer|exists:teachers,id',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'period' => 'nullable|integer|min:1|max:10',
            'session' => 'nullable|string|in:morning,afternoon,full_day,first_half,second_half',
            
            // Bulk student data
            'students' => 'required|array|min:1|max:100',
            'students.*.student_id' => [
                'required',
                'integer',
                'exists:students,id',
                'distinct',
            ],
            'students.*.status' => [
                'required',
                'string',
                'in:present,absent,late,half_day,excused,medical_leave,sick_leave,authorized_absence',
            ],
            'students.*.time_in' => 'nullable|date_format:H:i',
            'students.*.time_out' => 'nullable|date_format:H:i|after:students.*.time_in',
            'students.*.remarks' => 'nullable|string|max:200',
            'students.*.reason' => 'nullable|string|max:200',
            
            // Alternative bulk data format
            'bulk_data' => 'nullable|array',
            'bulk_data.*.student_id' => 'required|integer|exists:students,id',
            'bulk_data.*.status' => 'required|string|in:present,absent,late,half_day,excused',
            'bulk_data.*.remarks' => 'nullable|string|max:200',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student selection is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'date.required' => 'Attendance date is required.',
            'date.before_or_equal' => 'Attendance date cannot be in the future.',
            'date.after' => 'Attendance date cannot be more than 1 year ago.',
            'time_in.date_format' => 'Time in must be in HH:MM format.',
            'time_out.date_format' => 'Time out must be in HH:MM format.',
            'time_out.after' => 'Time out must be after time in.',
            'status.required' => 'Attendance status is required.',
            'status.in' => 'Please select a valid attendance status.',
            'class_id.required' => 'Class selection is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'section.regex' => 'Section must be a single uppercase letter.',
            'marked_by.exists' => 'Selected teacher does not exist.',
            'academic_year.required' => 'Academic year is required.',
            'academic_year.regex' => 'Academic year must be in format YYYY-YYYY.',
            'reason.required_if' => 'Reason is required for absent/leave status.',
            'biometric_id.regex' => 'Biometric ID can only contain letters, numbers, hyphens, and underscores.',
            'device_id.regex' => 'Device ID can only contain letters, numbers, hyphens, and underscores.',
            'gps_latitude.between' => 'GPS latitude must be between -90 and 90.',
            'gps_longitude.between' => 'GPS longitude must be between -180 and 180.',
            'temperature.between' => 'Temperature must be between -10°C and 50°C.',
            'temperature_check.between' => 'Body temperature must be between 95°F and 110°F.',
            'pickup_time.date_format' => 'Pickup time must be in HH:MM format.',
            'drop_time.date_format' => 'Drop time must be in HH:MM format.',
            'students.required' => 'At least one student is required for bulk attendance.',
            'students.min' => 'At least one student is required.',
            'students.max' => 'Maximum 100 students can be processed at once.',
            'students.*.student_id.required' => 'Student ID is required for each entry.',
            'students.*.student_id.exists' => 'One or more selected students do not exist.',
            'students.*.student_id.distinct' => 'Duplicate students are not allowed.',
            'students.*.status.required' => 'Status is required for each student.',
            'students.*.status.in' => 'Invalid attendance status for one or more students.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'time_in' => 'time in',
            'time_out' => 'time out',
            'attendance_type' => 'attendance type',
            'class_id' => 'class',
            'subject_id' => 'subject',
            'marked_by' => 'marked by teacher',
            'academic_year' => 'academic year',
            'parent_notified' => 'parent notification',
            'notification_sent' => 'notification status',
            'biometric_id' => 'biometric ID',
            'device_id' => 'device ID',
            'verification_method' => 'verification method',
            'gps_latitude' => 'GPS latitude',
            'gps_longitude' => 'GPS longitude',
            'weather_condition' => 'weather condition',
            'health_status' => 'health status',
            'temperature_check' => 'body temperature',
            'transport_used' => 'transport usage',
            'bus_route' => 'bus route',
            'pickup_time' => 'pickup time',
            'drop_time' => 'drop time',
        ];
    }

    /**
     * Check if a given date is a holiday.
     */
    private function isHoliday(Carbon $date): bool
    {
        // You can implement your holiday checking logic here
        // This could check against a holidays table or predefined holiday list
        
        // Example: Check for common Indian holidays
        $holidays = [
            '01-26', // Republic Day
            '08-15', // Independence Day
            '10-02', // Gandhi Jayanti
        ];
        
        $dateString = $date->format('m-d');
        return in_array($dateString, $holidays);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic
            $this->validateDuplicateAttendance($validator);
            $this->validateTimeConsistency($validator);
            $this->validateBulkConsistency($validator);
            $this->validateLocationConsistency($validator);
        });
    }

    /**
     * Validate duplicate attendance.
     */
    private function validateDuplicateAttendance($validator): void
    {
        $studentId = $this->input('student_id');
        $date = $this->input('date');
        $session = $this->input('session');
        $period = $this->input('period');
        
        if ($studentId && $date) {
            $query = \App\Models\Attendance::where('student_id', $studentId)
                ->where('date', $date);
                
            if ($session) {
                $query->where('session', $session);
            }
            
            if ($period) {
                $query->where('period', $period);
            }
            
            // Exclude current record if updating
            $attendanceId = $this->route('attendance') ? $this->route('attendance')->id : null;
            if ($attendanceId) {
                $query->where('id', '!=', $attendanceId);
            }
            
            if ($query->exists()) {
                $validator->errors()->add('date', 'Attendance already exists for this student on the selected date and session.');
            }
        }
    }

    /**
     * Validate time consistency.
     */
    private function validateTimeConsistency($validator): void
    {
        $timeIn = $this->input('time_in');
        $timeOut = $this->input('time_out');
        $status = $this->input('status');

        if ($timeIn && $timeOut) {
            $timeInCarbon = Carbon::createFromFormat('H:i', $timeIn);
            $timeOutCarbon = Carbon::createFromFormat('H:i', $timeOut);
            
            $duration = $timeOutCarbon->diffInMinutes($timeInCarbon);
            
            // Minimum duration should be 30 minutes
            if ($duration < 30) {
                $validator->errors()->add('time_out', 'Duration between time in and time out should be at least 30 minutes.');
            }
            
            // Maximum duration should be 12 hours
            if ($duration > 720) {
                $validator->errors()->add('time_out', 'Duration between time in and time out cannot exceed 12 hours.');
            }
        }

        // Validate status consistency with times
        if ($status === 'absent' && ($timeIn || $timeOut)) {
            $validator->errors()->add('status', 'Absent students cannot have time in/out records.');
        }

        if ($status === 'present' && !$timeIn) {
            $validator->errors()->add('time_in', 'Time in is required for present students.');
        }
    }

    /**
     * Validate bulk operation consistency.
     */
    private function validateBulkConsistency($validator): void
    {
        $students = $this->input('students', []);
        $classId = $this->input('class_id');
        
        if ($students && $classId) {
            foreach ($students as $index => $studentData) {
                $studentId = $studentData['student_id'] ?? null;
                if ($studentId) {
                    $student = \App\Models\Student::find($studentId);
                    if ($student && $student->class_id != $classId) {
                        $validator->errors()->add("students.{$index}.student_id", 'Student does not belong to the selected class.');
                    }
                }
            }
        }
    }

    /**
     * Validate location consistency.
     */
    private function validateLocationConsistency($validator): void
    {
        $gpsLatitude = $this->input('gps_latitude');
        $gpsLongitude = $this->input('gps_longitude');
        
        if (($gpsLatitude && !$gpsLongitude) || (!$gpsLatitude && $gpsLongitude)) {
            $validator->errors()->add('gps_latitude', 'Both GPS latitude and longitude are required if location tracking is enabled.');
        }
    }
}