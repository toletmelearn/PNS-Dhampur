<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for testing
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);
    }

    /** @test */
    public function test_json_error_response_structure()
    {
        $this->actingAs($this->adminUser);

        // Test validation error response format for JSON requests
        $response = $this->postJson('/students', [
            'name' => '', // Invalid - required field
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
            'timestamp'
        ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertIsArray($responseData['errors']);
        $this->assertArrayHasKey('name', $responseData['errors']);
    }

    /** @test */
    public function test_web_error_response_format()
    {
        $this->actingAs($this->adminUser);

        // Test validation error response for web requests
        $response = $this->post('/students', [
            'name' => '', // Invalid - required field
        ]);

        $response->assertStatus(302); // Redirect back
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function test_unauthorized_access_error_format()
    {
        // Test without authentication
        $response = $this->getJson('/students');
        $response->assertStatus(401);
    }

    /** @test */
    public function test_consistent_timestamp_format()
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson('/students', [
            'name' => '', // Invalid
        ]);

        $response->assertStatus(422);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('timestamp', $responseData);
        // Check if timestamp is in ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $responseData['timestamp']
        );
    }

    /** @test */
    public function test_csv_import_error_handling()
    {
        $this->actingAs($this->adminUser);

        // Create invalid CSV content
        $csvContent = "name,email,role\n,invalid-email,invalid-role";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->postJson('/users/import', [
            'csv_file' => $file
        ]);

        // Should return validation error or processing error
        $this->assertTrue(in_array($response->getStatusCode(), [422, 400, 500]));
        
        if ($response->getStatusCode() === 422) {
            $response->assertJsonStructure([
                'success',
                'message',
                'errors',
                'timestamp'
            ]);
        }
    }

    /** @test */
    public function test_file_upload_validation()
    {
        $this->actingAs($this->adminUser);

        // Test with invalid file type
        $invalidFile = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->postJson('/students', [
            'name' => 'Test Student',
            'father_name' => 'Test Father',
            'birth_certificate' => $invalidFile
        ]);

        // Should return validation error for invalid file type
        $this->assertTrue(in_array($response->getStatusCode(), [422, 400]));
        
        if ($response->getStatusCode() === 422) {
            $responseData = $response->json();
            $this->assertFalse($responseData['success']);
            $this->assertArrayHasKey('errors', $responseData);
        }
    }
}