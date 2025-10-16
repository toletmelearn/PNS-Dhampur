<?php

namespace App\Modules\Student\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth?->age,
            'gender' => $this->gender,
            'blood_group' => $this->blood_group,
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            'mother_tongue' => $this->mother_tongue,
            
            // Address Information
            'address' => [
                'street' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'full_address' => $this->getFullAddress(),
            ],
            
            // Academic Information
            'academic' => [
                'class_id' => $this->class_id,
                'class_name' => $this->whenLoaded('class', fn() => $this->class->name),
                'section' => $this->section,
                'roll_number' => $this->roll_number,
                'admission_date' => $this->admission_date?->format('Y-m-d'),
                'admission_year' => $this->admission_date?->year,
                'previous_school' => $this->previous_school,
                'current_academic_year' => $this->getCurrentAcademicYear(),
            ],
            
            // Medical Information
            'medical' => [
                'conditions' => $this->medical_conditions,
                'allergies' => $this->allergies,
                'emergency_contact' => [
                    'name' => $this->emergency_contact_name,
                    'phone' => $this->emergency_contact_phone,
                    'relation' => $this->emergency_contact_relation,
                ],
            ],
            
            // Parent/Guardian Information
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'father' => [
                        'name' => $this->parent->father_name,
                        'occupation' => $this->parent->father_occupation,
                        'phone' => $this->parent->father_phone,
                        'email' => $this->parent->father_email,
                    ],
                    'mother' => [
                        'name' => $this->parent->mother_name,
                        'occupation' => $this->parent->mother_occupation,
                        'phone' => $this->parent->mother_phone,
                        'email' => $this->parent->mother_email,
                    ],
                    'guardian' => [
                        'name' => $this->parent->guardian_name,
                        'relation' => $this->parent->guardian_relation,
                        'phone' => $this->parent->guardian_phone,
                        'email' => $this->parent->guardian_email,
                    ],
                    'annual_income' => $this->parent->annual_income,
                ];
            }),
            
            // Files and Media
            'media' => [
                'photo' => $this->getPhotoUrl(),
                'documents' => $this->getDocumentUrls(),
                'transfer_certificate' => $this->getTransferCertificateUrl(),
            ],
            
            // Additional Information
            'transportation' => [
                'type' => $this->transportation,
                'bus_route_id' => $this->bus_route_id,
                'bus_route' => $this->whenLoaded('busRoute', fn() => [
                    'id' => $this->busRoute->id,
                    'name' => $this->busRoute->name,
                    'route_number' => $this->busRoute->route_number,
                ]),
            ],
            
            'special_needs' => $this->special_needs,
            'extracurricular_interests' => $this->extracurricular_interests ?? [],
            'remarks' => $this->remarks,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            
            // Academic Performance (when loaded)
            'performance' => $this->when($this->relationLoaded('examResults'), function () {
                return [
                    'latest_results' => $this->examResults->take(5)->map(function ($result) {
                        return [
                            'exam_name' => $result->exam->name,
                            'subject' => $result->subject->name,
                            'marks_obtained' => $result->marks_obtained,
                            'total_marks' => $result->total_marks,
                            'percentage' => $result->percentage,
                            'grade' => $result->grade,
                            'exam_date' => $result->exam_date?->format('Y-m-d'),
                        ];
                    }),
                    'overall_percentage' => $this->getOverallPercentage(),
                    'overall_grade' => $this->getOverallGrade(),
                ];
            }),
            
            // Attendance Summary (when loaded)
            'attendance' => $this->when($this->relationLoaded('attendances'), function () {
                return [
                    'total_days' => $this->attendances->count(),
                    'present_days' => $this->attendances->where('status', 'present')->count(),
                    'absent_days' => $this->attendances->where('status', 'absent')->count(),
                    'late_days' => $this->attendances->where('status', 'late')->count(),
                    'attendance_percentage' => $this->getAttendancePercentage(),
                    'recent_attendance' => $this->attendances->take(10)->map(function ($attendance) {
                        return [
                            'date' => $attendance->date->format('Y-m-d'),
                            'status' => $attendance->status,
                            'remarks' => $attendance->remarks,
                        ];
                    }),
                ];
            }),
            
            // Fee Information (when loaded)
            'fees' => $this->when($this->relationLoaded('feeTransactions'), function () {
                $totalFee = $this->feeTransactions->sum('amount');
                $paidAmount = $this->feeTransactions->where('status', 'paid')->sum('amount');
                
                return [
                    'total_fee' => $totalFee,
                    'paid_amount' => $paidAmount,
                    'pending_amount' => $totalFee - $paidAmount,
                    'fee_status' => $paidAmount >= $totalFee ? 'paid' : 'pending',
                    'recent_transactions' => $this->feeTransactions->take(5)->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'amount' => $transaction->amount,
                            'status' => $transaction->status,
                            'payment_date' => $transaction->payment_date?->format('Y-m-d'),
                            'payment_method' => $transaction->payment_method,
                        ];
                    }),
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'updated_by' => $this->whenLoaded('updater', fn() => [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ]),
        ];
    }

    /**
     * Get full formatted address
     */
    private function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get current academic year
     */
    private function getCurrentAcademicYear(): string
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Academic year starts in April (month 4)
        if ($currentMonth >= 4) {
            return $currentYear . '-' . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . '-' . $currentYear;
        }
    }

    /**
     * Get photo URL
     */
    private function getPhotoUrl(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    /**
     * Get document URLs
     */
    private function getDocumentUrls(): array
    {
        if (!$this->documents) {
            return [];
        }
        
        return array_map(function ($document) {
            return [
                'name' => basename($document),
                'url' => asset('storage/' . $document),
                'type' => pathinfo($document, PATHINFO_EXTENSION),
            ];
        }, $this->documents);
    }

    /**
     * Get transfer certificate URL
     */
    private function getTransferCertificateUrl(): ?string
    {
        return $this->transfer_certificate ? asset('storage/' . $this->transfer_certificate) : null;
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'transferred' => 'Transferred',
            'graduated' => 'Graduated',
            'suspended' => 'Suspended',
            default => 'Unknown'
        };
    }

    /**
     * Get overall percentage
     */
    private function getOverallPercentage(): ?float
    {
        if (!$this->relationLoaded('examResults') || $this->examResults->isEmpty()) {
            return null;
        }
        
        return $this->examResults->avg('percentage');
    }

    /**
     * Get overall grade
     */
    private function getOverallGrade(): ?string
    {
        $percentage = $this->getOverallPercentage();
        
        if ($percentage === null) {
            return null;
        }
        
        return match(true) {
            $percentage >= 90 => 'A+',
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B+',
            $percentage >= 60 => 'B',
            $percentage >= 50 => 'C+',
            $percentage >= 40 => 'C',
            $percentage >= 33 => 'D',
            default => 'F'
        };
    }

    /**
     * Get attendance percentage
     */
    private function getAttendancePercentage(): ?float
    {
        if (!$this->relationLoaded('attendances') || $this->attendances->isEmpty()) {
            return null;
        }
        
        $totalDays = $this->attendances->count();
        $presentDays = $this->attendances->where('status', 'present')->count();
        
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }
}