<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InputValidationSecurityTest extends TestCase
{
    use  WithFaker;

    protected $admin;
    protected $classModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'password' => bcrypt('password123')
        ]);
        
        // Create test class
        $this->classModel = ClassModel::factory()->create([
            'name' => 'Test Class',
            'section' => 'A'
        ]);
    }

    /** @test */
    public function test_xss_protection_in_student_registration()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<svg onload=alert("XSS")>',
            '"><script>alert("XSS")</script>',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            '<body onload=alert("XSS")>',
            '<div onclick="alert(\'XSS\')">Click me</div>'
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => '123456789012',
                    'contact_number' => '9876543210',
                    'email' => 'test@example.com',
                    'address' => 'Test Address',
                    'class_id' => $this->classModel->id
                ]);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('contains potentially malicious content', 
                $response->json('errors.first_name.0'));
        }
    }

    /** @test */
    public function test_sql_injection_protection()
    {
        $sqlInjectionPayloads = [
            "'; DROP TABLE students; --",
            "' OR '1'='1",
            "'; DELETE FROM users; --",
            "' UNION SELECT * FROM users --",
            "'; INSERT INTO users (role) VALUES ('admin'); --",
            "' OR 1=1 --",
            "'; UPDATE users SET role='admin' WHERE id=1; --",
            "' AND (SELECT COUNT(*) FROM users) > 0 --"
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => $payload,
                    'last_name' => 'Test',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => '123456789012',
                    'contact_number' => '9876543210',
                    'email' => 'test@example.com',
                    'address' => 'Test Address',
                    'class_id' => $this->classModel->id
                ]);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('contains potentially malicious content', 
                $response->json('errors.first_name.0'));
        }
    }

    /** @test */
    public function test_disposable_email_detection()
    {
        $disposableEmails = [
            'test@10minutemail.com',
            'user@guerrillamail.com',
            'temp@mailinator.com',
            'fake@tempmail.org',
            'spam@yopmail.com',
            'test@throwaway.email',
            'user@temp-mail.org'
        ];

        foreach ($disposableEmails as $email) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => '123456789012',
                    'contact_number' => '9876543210',
                    'email' => $email,
                    'address' => 'Test Address',
                    'class_id' => $this->classModel->id
                ]);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('disposable email', 
                $response->json('errors.email.0'));
        }
    }

    /** @test */
    public function test_suspicious_address_patterns()
    {
        $suspiciousAddresses = [
            'DROP TABLE students',
            '<script>alert("hack")</script>',
            'javascript:void(0)',
            'SELECT * FROM users',
            'UNION SELECT password FROM users',
            '<iframe src="malicious.com"></iframe>'
        ];

        foreach ($suspiciousAddresses as $address) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => '123456789012',
                    'contact_number' => '9876543210',
                    'email' => 'test@example.com',
                    'address' => $address,
                    'class_id' => $this->classModel->id
                ]);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('contains suspicious patterns', 
                $response->json('errors.address.0'));
        }
    }

    /** @test */
    public function test_file_upload_security()
    {
        Storage::fake('public');

        // Test malicious file types
        $maliciousFiles = [
            UploadedFile::fake()->create('malicious.php', 100),
            UploadedFile::fake()->create('script.js', 100),
            UploadedFile::fake()->create('executable.exe', 100),
            UploadedFile::fake()->create('virus.bat', 100),
            UploadedFile::fake()->create('hack.sh', 100)
        ];

        foreach ($maliciousFiles as $file) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => '123456789012',
                    'contact_number' => '9876543210',
                    'email' => 'test@example.com',
                    'address' => 'Test Address',
                    'class_id' => $this->classModel->id,
                    'documents' => [$file]
                ]);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('must be a file of type', 
                $response->json('errors.documents.0.0'));
        }
    }

    /** @test */
    public function test_valid_file_upload()
    {
        Storage::fake('public');

        $validFile = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->admin)
            ->post('/students', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'date_of_birth' => '2010-01-01',
                'aadhaar_number' => '123456789012',
                'contact_number' => '9876543210',
                'email' => 'test@example.com',
                'address' => 'Test Address',
                'class_id' => $this->classModel->id,
                'documents' => [$validFile]
            ]);

        $this->assertEquals(302, $response->getStatusCode()); // Redirect on success
    }

    /** @test */
    public function test_aadhaar_validation_with_invalid_checksums()
    {
        $invalidAadhaarNumbers = [
            '123456789013', // Invalid checksum
            '987654321098', // Invalid checksum
            '111111111111', // Invalid checksum
            '000000000000', // Invalid checksum
            '123456789000'  // Invalid checksum
        ];

        foreach ($invalidAadhaarNumbers as $aadhaar) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => $aadhaar,
                    'contact_number' => '9876543210',
                    'email' => 'test@example.com',
                    'address' => 'Test Address',
                    'class_id' => $this->classModel->id
                ]);

            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('invalid Aadhaar number', 
                $response->json('errors.aadhaar_number.0'));
        }
    }

    /** @test */
    public function test_age_validation_boundaries()
    {
        // Test too young (under 3 years)
        $response = $this->actingAs($this->admin)
            ->post('/students', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'date_of_birth' => now()->subYears(2)->format('Y-m-d'),
                'aadhaar_number' => '123456789012',
                'contact_number' => '9876543210',
                'email' => 'test@example.com',
                'address' => 'Test Address',
                'class_id' => $this->classModel->id
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('must be at least 3 years old', 
            $response->json('errors.date_of_birth.0'));

        // Test too old (over 25 years)
        $response = $this->actingAs($this->admin)
            ->post('/students', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'date_of_birth' => now()->subYears(26)->format('Y-m-d'),
                'aadhaar_number' => '123456789012',
                'contact_number' => '9876543210',
                'email' => 'test@example.com',
                'address' => 'Test Address',
                'class_id' => $this->classModel->id
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('must not be older than 25 years', 
            $response->json('errors.date_of_birth.0'));
    }

    /** @test */
    public function test_contact_number_validation()
    {
        $invalidContactNumbers = [
            '123456789',     // Too short
            '12345678901',   // Too long
            'abcdefghij',    // Non-numeric
            '0000000000',    // All zeros
            '1111111111',    // All ones
            '+91123456789',  // With country code (should be rejected)
            '9876543210a'    // Contains letter
        ];

        foreach ($invalidContactNumbers as $contact) {
            $response = $this->actingAs($this->admin)
                ->post('/students', [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'date_of_birth' => '2010-01-01',
                    'aadhaar_number' => '123456789012',
                    'contact_number' => $contact,
                    'email' => 'test@example.com',
                    'address' => 'Test Address',
                    'class_id' => $this->classModel->id
                ]);

            $this->assertEquals(422, $response->getStatusCode());
        }
    }

    /** @test */
    public function test_meta_field_security()
    {
        $maliciousMeta = [
            'script' => '<script>alert("XSS")</script>',
            'sql' => "'; DROP TABLE students; --",
            'iframe' => '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            'eval' => 'eval("malicious code")'
        ];

        $response = $this->actingAs($this->admin)
            ->post('/students', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'date_of_birth' => '2010-01-01',
                'aadhaar_number' => '123456789012',
                'contact_number' => '9876543210',
                'email' => 'test@example.com',
                'address' => 'Test Address',
                'class_id' => $this->classModel->id,
                'meta' => $maliciousMeta
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('contains malicious content', 
            $response->json('errors.meta.0'));
    }

    /** @test */
    public function test_valid_student_registration()
    {
        $validData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-01',
            'aadhaar_number' => '123456789012',
            'contact_number' => '9876543210',
            'email' => 'john.doe@example.com',
            'address' => '123 Main Street, City, State',
            'class_id' => $this->classModel->id,
            'father_name' => 'Robert Doe',
            'mother_name' => 'Jane Doe',
            'admission_no' => 'ADM2024001',
            'roll_number' => '001'
        ];

        $response = $this->actingAs($this->admin)
            ->post('/students', $validData);

        $this->assertEquals(302, $response->getStatusCode()); // Redirect on success
        
        // Verify student was created
        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
    }

    /** @test */
    public function test_mass_assignment_protection()
    {
        $maliciousData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'date_of_birth' => '2010-01-01',
            'aadhaar_number' => '123456789012',
            'contact_number' => '9876543210',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'class_id' => $this->classModel->id,
            'id' => 999,                    // Trying to set ID
            'created_at' => '2020-01-01',   // Trying to manipulate timestamp
            'updated_at' => '2020-01-01',   // Trying to manipulate timestamp
            'deleted_at' => null,           // Trying to manipulate soft delete
            'status' => 'admin'             // Trying to set unauthorized status
        ];

        $response = $this->actingAs($this->admin)
            ->post('/students', $maliciousData);

        // Should succeed but ignore protected fields
        $this->assertEquals(302, $response->getStatusCode());
        
        // Verify protected fields were not set
        $student = Student::where('email', 'test@example.com')->first();
        $this->assertNotEquals(999, $student->id);
        $this->assertNotEquals('2020-01-01', $student->created_at->format('Y-m-d'));
    }
}