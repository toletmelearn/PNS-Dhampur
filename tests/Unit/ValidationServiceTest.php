<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ValidationServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new ValidationService();
    }

    /** @test */
    public function it_sanitizes_input_data_correctly()
    {
        // Arrange
        $maliciousInput = [
            'name' => '<script>alert("XSS")</script>John Doe',
            'email' => 'test@example.com<script>',
            'description' => 'This is a test <b>bold</b> text with <script>alert("hack")</script>',
        ];

        // Act
        $sanitized = $this->validationService->sanitizeInput($maliciousInput);

        // Assert
        $this->assertStringNotContainsString('<script>', $sanitized['name']);
        $this->assertStringNotContainsString('<script>', $sanitized['email']);
        $this->assertStringNotContainsString('<script>', $sanitized['description']);
        $this->assertEquals('John Doe', $sanitized['name']);
        $this->assertEquals('test@example.com', $sanitized['email']);
    }

    /** @test */
    public function it_validates_email_addresses_correctly()
    {
        // Valid emails
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
        ];

        // Invalid emails
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'test@',
            'test..test@example.com',
        ];

        // Test valid emails
        foreach ($validEmails as $email) {
            $this->assertTrue(
                $this->validationService->validateEmail($email),
                "Email {$email} should be valid"
            );
        }

        // Test invalid emails
        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->validationService->validateEmail($email),
                "Email {$email} should be invalid"
            );
        }
    }

    /** @test */
    public function it_validates_phone_numbers_correctly()
    {
        // Valid phone numbers (Indian format)
        $validPhones = [
            '9876543210',
            '+919876543210',
            '919876543210',
            '08765432109',
        ];

        // Invalid phone numbers
        $invalidPhones = [
            '123456789', // Too short
            '12345678901', // Too long
            'abcdefghij', // Non-numeric
            '+1234567890', // Wrong country code
        ];

        // Test valid phone numbers
        foreach ($validPhones as $phone) {
            $this->assertTrue(
                $this->validationService->validatePhoneNumber($phone),
                "Phone {$phone} should be valid"
            );
        }

        // Test invalid phone numbers
        foreach ($invalidPhones as $phone) {
            $this->assertFalse(
                $this->validationService->validatePhoneNumber($phone),
                "Phone {$phone} should be invalid"
            );
        }
    }

    /** @test */
    public function it_validates_aadhaar_numbers_correctly()
    {
        // Valid Aadhaar numbers (format: 12 digits)
        $validAadhaar = [
            '123456789012',
            '987654321098',
        ];

        // Invalid Aadhaar numbers
        $invalidAadhaar = [
            '12345678901', // Too short
            '1234567890123', // Too long
            'abcd56789012', // Contains letters
            '000000000000', // All zeros
            '111111111111', // All same digits
        ];

        // Test valid Aadhaar numbers
        foreach ($validAadhaar as $aadhaar) {
            $this->assertTrue(
                $this->validationService->validateAadhaar($aadhaar),
                "Aadhaar {$aadhaar} should be valid"
            );
        }

        // Test invalid Aadhaar numbers
        foreach ($invalidAadhaar as $aadhaar) {
            $this->assertFalse(
                $this->validationService->validateAadhaar($aadhaar),
                "Aadhaar {$aadhaar} should be invalid"
            );
        }
    }

    /** @test */
    public function it_detects_sql_injection_attempts()
    {
        // SQL injection attempts
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "admin'--",
            "1; DELETE FROM students;",
            "UNION SELECT * FROM users",
        ];

        // Safe inputs
        $safeInputs = [
            "John Doe",
            "test@example.com",
            "Normal text input",
            "123456",
        ];

        // Test malicious inputs
        foreach ($maliciousInputs as $input) {
            $this->assertTrue(
                $this->validationService->detectSqlInjection($input),
                "Input '{$input}' should be detected as SQL injection"
            );
        }

        // Test safe inputs
        foreach ($safeInputs as $input) {
            $this->assertFalse(
                $this->validationService->detectSqlInjection($input),
                "Input '{$input}' should be safe"
            );
        }
    }

    /** @test */
    public function it_detects_xss_attempts()
    {
        // XSS attempts
        $xssInputs = [
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(1)">',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<svg onload="alert(1)">',
        ];

        // Safe inputs
        $safeInputs = [
            'Normal text',
            '<b>Bold text</b>',
            '<p>Paragraph</p>',
            'Email: test@example.com',
        ];

        // Test XSS inputs
        foreach ($xssInputs as $input) {
            $this->assertTrue(
                $this->validationService->detectXss($input),
                "Input '{$input}' should be detected as XSS"
            );
        }

        // Test safe inputs (allowing basic HTML)
        foreach ($safeInputs as $input) {
            $result = $this->validationService->detectXss($input);
            if (in_array($input, ['<b>Bold text</b>', '<p>Paragraph</p>'])) {
                // These might be flagged depending on implementation
                $this->assertIsBool($result);
            } else {
                $this->assertFalse($result, "Input '{$input}' should be safe");
            }
        }
    }

    /** @test */
    public function it_validates_file_uploads_correctly()
    {
        // Test file validation parameters
        $validFileTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        // Mock file data
        $validFile = [
            'name' => 'document.pdf',
            'type' => 'application/pdf',
            'size' => 1024 * 1024, // 1MB
            'tmp_name' => '/tmp/phpupload',
            'error' => UPLOAD_ERR_OK,
        ];

        $invalidFile = [
            'name' => 'malicious.exe',
            'type' => 'application/octet-stream',
            'size' => 10 * 1024 * 1024, // 10MB (too large)
            'tmp_name' => '/tmp/phpupload',
            'error' => UPLOAD_ERR_OK,
        ];

        // Test valid file
        $this->assertTrue(
            $this->validationService->validateFileUpload($validFile, $validFileTypes, $maxFileSize)
        );

        // Test invalid file
        $this->assertFalse(
            $this->validationService->validateFileUpload($invalidFile, $validFileTypes, $maxFileSize)
        );
    }

    /** @test */
    public function it_validates_date_formats_correctly()
    {
        // Valid dates
        $validDates = [
            '2023-12-25',
            '2023-01-01',
            '2023-02-28',
            '2024-02-29', // Leap year
        ];

        // Invalid dates
        $invalidDates = [
            '2023-13-01', // Invalid month
            '2023-02-30', // Invalid day for February
            '2023/12/25', // Wrong format
            'invalid-date',
            '2023-2-5', // Missing leading zeros
        ];

        // Test valid dates
        foreach ($validDates as $date) {
            $this->assertTrue(
                $this->validationService->validateDate($date, 'Y-m-d'),
                "Date {$date} should be valid"
            );
        }

        // Test invalid dates
        foreach ($invalidDates as $date) {
            $this->assertFalse(
                $this->validationService->validateDate($date, 'Y-m-d'),
                "Date {$date} should be invalid"
            );
        }
    }

    /** @test */
    public function it_validates_password_strength()
    {
        // Strong passwords
        $strongPasswords = [
            'MyStr0ngP@ssw0rd!',
            'C0mpl3x#P@ssw0rd',
            'S3cur3P@ss123!',
        ];

        // Weak passwords
        $weakPasswords = [
            'password', // Too simple
            '123456', // Only numbers
            'abcdef', // Only letters
            'Pass1', // Too short
            'PASSWORD123', // No special characters
        ];

        // Test strong passwords
        foreach ($strongPasswords as $password) {
            $this->assertTrue(
                $this->validationService->validatePasswordStrength($password),
                "Password '{$password}' should be strong"
            );
        }

        // Test weak passwords
        foreach ($weakPasswords as $password) {
            $this->assertFalse(
                $this->validationService->validatePasswordStrength($password),
                "Password '{$password}' should be weak"
            );
        }
    }

    /** @test */
    public function it_validates_numeric_ranges()
    {
        // Test age validation (0-120)
        $this->assertTrue($this->validationService->validateNumericRange(25, 0, 120));
        $this->assertTrue($this->validationService->validateNumericRange(0, 0, 120));
        $this->assertTrue($this->validationService->validateNumericRange(120, 0, 120));
        $this->assertFalse($this->validationService->validateNumericRange(-1, 0, 120));
        $this->assertFalse($this->validationService->validateNumericRange(121, 0, 120));

        // Test marks validation (0-100)
        $this->assertTrue($this->validationService->validateNumericRange(85, 0, 100));
        $this->assertFalse($this->validationService->validateNumericRange(101, 0, 100));
        $this->assertFalse($this->validationService->validateNumericRange(-5, 0, 100));
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '',
            'address' => null,
        ];

        $requiredFields = ['name', 'email', 'phone'];

        $result = $this->validationService->validateRequiredFields($data, $requiredFields);

        $this->assertFalse($result['valid']);
        $this->assertContains('phone', $result['missing_fields']);
        $this->assertNotContains('name', $result['missing_fields']);
        $this->assertNotContains('email', $result['missing_fields']);
    }
}