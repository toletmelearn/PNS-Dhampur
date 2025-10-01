<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentVerification;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;

class DocumentVerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $students = Student::all();
        $verifiers = User::whereIn('role', ['admin', 'teacher'])->get();
        
        if ($students->isEmpty() || $verifiers->isEmpty()) {
            $this->command->info('No students or verifiers found. Please run StudentSeeder and UserSeeder first.');
            return;
        }

        $documentTypes = [
            'birth_certificate',
            'aadhaar_card',
            'transfer_certificate',
            'caste_certificate',
            'income_certificate',
            'passport_photo',
            'medical_certificate',
            'previous_marksheet'
        ];

        $verificationStatuses = ['pending', 'verified', 'rejected', 'expired'];

        foreach ($students as $student) {
            // Create 3-5 document verification records per student
            $numDocuments = rand(3, 5);
            $selectedDocuments = collect($documentTypes)->random($numDocuments);

            foreach ($selectedDocuments as $docType) {
                $status = collect($verificationStatuses)->random();
                $isVerified = $status === 'verified';
                $isExpired = $status === 'expired';
                $isRejected = $status === 'rejected';

                DocumentVerification::create([
                    'student_id' => $student->id,
                    'document_type' => $docType,
                    'document_name' => ucwords(str_replace('_', ' ', $docType)),
                    'file_path' => 'documents/students/' . $student->id . '/' . $docType . '.pdf',
                    'file_hash' => hash('sha256', 'sample_file_content_' . $student->id . '_' . $docType),
                    'verification_status' => $status,
                    'verification_notes' => $isRejected 
                        ? 'Document quality is poor, please resubmit with clearer image'
                        : ($isVerified ? 'Document verified successfully' : null),
                    'verified_by' => $isVerified || $isRejected ? $verifiers->random()->id : null,
                    'verified_at' => $isVerified || $isRejected ? Carbon::now()->subDays(rand(1, 30)) : null,
                    'expiry_date' => in_array($docType, ['medical_certificate', 'income_certificate']) 
                        ? Carbon::now()->addYear() 
                        : null,
                    'metadata' => [
                        'file_size' => rand(100000, 2000000),
                        'mime_type' => 'application/pdf',
                        'uploaded_at' => Carbon::now()->subDays(rand(1, 60))->toISOString(),
                        'original_filename' => $docType . '_' . $student->admission_no . '.pdf'
                    ],
                    'is_mandatory' => in_array($docType, ['birth_certificate', 'aadhaar_card', 'transfer_certificate']),
                    'verification_attempts' => $isRejected ? rand(1, 3) : ($isVerified ? 1 : 0),
                    'last_verification_attempt' => $isVerified || $isRejected 
                        ? Carbon::now()->subDays(rand(1, 30)) 
                        : null,
                ]);
            }
        }

        $this->command->info('Document verification records seeded successfully!');
    }
}
